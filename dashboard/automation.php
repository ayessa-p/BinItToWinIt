<?php
require_once '../config/config.php';
require_login();

$page_title = 'Room Reservation & Service Automation';
$user_id = get_user_id();
$db = Database::getInstance()->getConnection();

// Initialize message variables
$message = '';
$message_type = '';

// Fixed IT building rooms
// Rooms list (prefer DB resources with category 'room', fallback to defaults)
$rooms = [];
try {
    $stmt = $db->prepare("SELECT name FROM resources WHERE category = 'facility' AND type = 'room' AND is_active = 1 ORDER BY name ASC");
    $stmt->execute();
    $rooms = array_map(fn($r) => $r['name'], $stmt->fetchAll(PDO::FETCH_ASSOC));
} catch (PDOException $e) {
    $rooms = [];
}
if (empty($rooms)) {
    $rooms = ['RM101', 'RM102', 'RM103', 'RM201', 'RM202', 'RM203', 'RM301', 'RM302'];
}

// Fetch active borrowable equipment from resources, grouped by category
$equipment_by_category = [];
try {
    $stmt = $db->prepare("SELECT id, name, category, is_consumable FROM resources WHERE category != 'facility' AND is_active = 1 ORDER BY category, name ASC");
    $stmt->execute();
    $resources = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($resources as $res) {
        $cat = $res['category'] ?: 'Other';
        $equipment_by_category[$cat][] = $res;
    }
} catch (PDOException $e) {
    $equipment_by_category = [];
}

// Ensure common borrowables show up even if DB is missing seeds
$default_equipment_names = [
    'Projector' => 'Tech',
    'HDMI Cable' => 'Tech',
    'VGA Cable' => 'Tech',
    'Power Extension Cord' => 'Tech',
    'Audio Speaker' => 'Tech',
    'Microphone' => 'Tech',
    'Whiteboard' => 'Office Supplies',
    'Marker Set' => 'Office Supplies',
    'Stapler' => 'Office Supplies'
];

$existing_names_lc = [];
foreach ($equipment_by_category as $cat => $items) {
    foreach ($items as $item) {
        $existing_names_lc[] = strtolower($item['name'] ?? '');
    }
}

foreach ($default_equipment_names as $name => $cat) {
    if (!in_array(strtolower($name), $existing_names_lc, true)) {
        $is_consumable = (strtolower($name) === 'stapler' || strtolower($name) === 'marker set') ? 1 : 0;
        $equipment_by_category[$cat][] = ['id' => 0, 'name' => $name, 'category' => $cat, 'is_consumable' => $is_consumable];
    }
}

// Handle room reservation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reserve_room'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $message = 'Invalid security token.';
        $message_type = 'error';
    } else {
        $room_code = sanitize_input($_POST['room_code'] ?? '');
        $purpose_type = sanitize_input($_POST['purpose_type'] ?? 'general');
        $professor_name = sanitize_input($_POST['professor_name'] ?? '');
        $start_date = $_POST['start_date'] ?? '';
        $end_date = $_POST['end_date'] ?? '';
        $purpose = sanitize_input($_POST['purpose'] ?? '');
        
        try {
            $db->beginTransaction();
            
            if (!in_array($room_code, $rooms, true)) {
                throw new Exception('Invalid room selected.');
            }
            if (!in_array($purpose_type, ['general', 'class'], true)) {
                $purpose_type = 'general';
            }
            if (empty($start_date) || empty($end_date)) {
                throw new Exception('Please select start and end date/time.');
            }
            if (strtotime($start_date) === false || strtotime($end_date) === false) {
                throw new Exception('Invalid date/time.');
            }
            if (strtotime($end_date) <= strtotime($start_date)) {
                throw new Exception('End date/time must be after start date/time.');
            }

            if ($purpose_type === 'class') {
                if ($professor_name === '') {
                    throw new Exception('Please specify the professor for class reservations.');
                }
                $purpose = 'Class with Prof. ' . $professor_name . ' - ' . $purpose;
            }

            // Prevent double-booking (blocks both pending and approved overlaps)
            $stmt = $db->prepare("
                SELECT COUNT(*) FROM room_reservations
                WHERE room_code = ?
                  AND status IN ('pending', 'approved')
                  AND ((start_date <= ? AND end_date > ?) OR (start_date < ? AND end_date >= ?))
            ");
            $stmt->execute([$room_code, $start_date, $start_date, $end_date, $end_date]);
            $conflicts = (int)$stmt->fetchColumn();
            if ($conflicts > 0) {
                throw new Exception('Room not available for selected time period.');
            }

            $status = 'pending';

            $stmt = $db->prepare("
                INSERT INTO room_reservations
                (room_code, user_id, start_date, end_date, purpose, status)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$room_code, $user_id, $start_date, $end_date, $purpose, $status]);
            
            $db->commit();
            $message = 'Room reservation request submitted successfully! Pending admin approval.';
            $message_type = 'success';
            
        } catch (Exception $e) {
            $db->rollBack();
            $message = 'Error: ' . $e->getMessage();
            $message_type = 'error';
        }
    }
}

// Handle service requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_service'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $message = 'Invalid security token.';
        $message_type = 'error';
    } else {
        $service_type = sanitize_input($_POST['service_type'] ?? '');
        $title = sanitize_input($_POST['title'] ?? '');
        $description = sanitize_input($_POST['description'] ?? '');
        $urgency = sanitize_input($_POST['urgency'] ?? 'medium');
        $file_path = '';

        if ($service_type === 'printing' && $title === '') {
            $title = 'Printing';
        }
        
        // Handle file upload for printing (PDF only) and auto-detect pages
        $detected_pages = null;
        if ($service_type === 'printing' && isset($_FILES['print_file']) && $_FILES['print_file']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = __DIR__ . '/../uploads/printing/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            $original_name = $_FILES['print_file']['name'] ?? '';
            $ext = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));
            if ($ext !== 'pdf') {
                $message = 'Only PDF files are allowed for printing.';
                $message_type = 'error';
            } else {
                $file_name = 'print_' . $user_id . '_' . time() . '_' . basename($original_name);
                $target_path = $upload_dir . $file_name;
                
                if (move_uploaded_file($_FILES['print_file']['tmp_name'], $target_path)) {
                    $file_path = '/uploads/printing/' . $file_name;
                    
                    // Auto-count PDF pages by scanning for /Type /Page markers
                    $contents = @file_get_contents($target_path);
                    if ($contents !== false) {
                        if (preg_match_all('/\/Type\s*\/Page[^s]/', $contents, $matches)) {
                            $page_count = count($matches[0]);
                            if ($page_count > 0) {
                                $detected_pages = $page_count;
                            }
                        }
                    }
                } else {
                    $message = 'Failed to upload PDF file.';
                    $message_type = 'error';
                }
            }
        }
        
        try {
            $db->beginTransaction();
            
            // Calculate tokens based on automation rules
            $tokens_required = 0;
            $stmt = $db->prepare("
                SELECT conditions, actions FROM automation_rules 
                WHERE rule_type = 'pricing' AND is_active = true 
                ORDER BY priority DESC
            ");
            $stmt->execute();
            $rules = $stmt->fetchAll();
            
            $user_level = get_user_level($user_id);
            foreach ($rules as $rule) {
                $rule_conditions = json_decode($rule['conditions'] ?? '{}', true);
                $rule_actions = json_decode($rule['actions'] ?? '{}', true);
                
                if (($rule_conditions['service_type'] ?? null) !== $service_type) {
                    continue;
                }
                if (($rule_conditions['user_level'] ?? null) !== $user_level) {
                    continue;
                }
                if (empty($rule_actions['charge_tokens'])) {
                    continue;
                }
                
                // Use standard pricing
                if ($service_type === 'printing') {
                    // Prefer auto-detected page count for PDFs; default to 1 if unknown
                    $pages = max(1, (int)($detected_pages ?? 1));
                    $color = sanitize_input($_POST['color'] ?? 'bw');
                    if (!in_array($color, ['bw', 'color'], true)) {
                        $color = 'bw';
                    }
                    
                    // Pick the active printing service matching color
                    $stmt = $db->prepare("
                        SELECT price_per_page
                        FROM printing_services
                        WHERE is_active = true AND color_options = ?
                        ORDER BY price_per_page ASC
                        LIMIT 1
                    ");
                    $stmt->execute([$color]);
                    $printing = $stmt->fetch();
                    $price_per_page = (float)($printing['price_per_page'] ?? 0);
                    $tokens_required = $price_per_page * $pages;
                } else {
                    $tokens_required = 50.00; // Default service charge
                }
                
                // First matching rule wins (rules are priority-ordered)
                break;
            }
            
            // Create service request
            $stmt = $db->prepare("
                INSERT INTO service_requests 
                (user_id, service_type, title, description, urgency, status, tokens_required, tokens_charged, file_path)
                VALUES (?, ?, ?, ?, ?, 'pending', ?, ?, ?)
            ");
            $stmt->execute([$user_id, $service_type, $title, $description, $urgency, $tokens_required, $tokens_required, $file_path]);
            
            $db->commit();
            $message = 'Service request submitted successfully! Tokens will be charged upon completion.';
            $message_type = 'success';
            
        } catch (Exception $e) {
            $db->rollBack();
            $message = 'Error: ' . $e->getMessage();
            $message_type = 'error';
        }
    }
}

// Get user's reservations and requests
function get_user_level($user_id) {
    global $db;
    $stmt = $db->prepare("SELECT is_admin FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    return $user['is_admin'] ? 'admin' : 'student';
}

// Get user's current room reservations
$user_room_reservations = [];
$stmt = $db->prepare("
    SELECT rr.*
    FROM room_reservations rr
    WHERE rr.user_id = ? AND rr.end_date > NOW()
    ORDER BY rr.start_date DESC
");
$stmt->execute([$user_id]);
$user_room_reservations = $stmt->fetchAll();

// Get user's service requests
$user_requests = [];
$stmt = $db->prepare("
    SELECT sr.*, 
           CASE WHEN sr.assigned_to IS NULL THEN 'Unassigned' ELSE u.full_name END as assigned_to_name
    FROM service_requests sr
    LEFT JOIN users u ON sr.assigned_to = u.id
    WHERE sr.user_id = ? AND sr.status IN ('pending', 'in_progress')
    ORDER BY sr.created_at DESC
");
$stmt->execute([$user_id]);
$user_requests = $stmt->fetchAll();

// Get printing services for quick access
$printing_services = [];
$stmt = $db->prepare("SELECT * FROM printing_services WHERE is_active = true ORDER BY price_per_page ASC");
$stmt->execute();
$printing_services = $stmt->fetchAll();

// Get internet plans
$internet_plans = [];
$stmt = $db->prepare("SELECT * FROM internet_plans WHERE is_active = true ORDER BY token_cost ASC");
$stmt->execute();
$internet_plans = $stmt->fetchAll();

include '../includes/header.php';
?>

<section class="section">
    <div class="container">
        <h1 class="section-title">Services</h1>

        <!-- Quick Access -->
        <nav aria-label="Quick access" style="margin-bottom: 1.5rem;">
            <div style="display: flex; flex-wrap: wrap; gap: 0.75rem; justify-content: center;">
                <a href="#quick-pair-device" class="btn btn-secondary">Pair Device</a>
                <a href="#quick-printing" class="btn btn-secondary">Printing</a>
                <a href="#quick-equipment" class="btn btn-secondary">Equipment Borrowing</a>
                <a href="#quick-room" class="btn btn-secondary">Room Reservation</a>
                <a href="#quick-active-requests" class="btn btn-secondary">Your Active Requests</a>
            </div>
        </nav>
        
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <!-- Quick Actions -->
        <div style="margin-bottom: 3rem; display: flex; flex-direction: column; gap: 1.5rem; align-items: center;">
            <div id="quick-pair-device" class="card" style="width: 100%; max-width: 720px;">
                <div class="card-header" style="text-align: center; padding: 1rem;">
                    <h3 class="card-title" style="margin: 0;">Pair Device</h3>
                </div>
                <div class="card-body">
                    <p style="text-align: center; margin-bottom: 1rem;">
                        Pair your ESP32 device to earn tokens from recycling!
                    </p>
                    <a href="pair_device.php" class="btn btn-primary" style="display: block; text-align: center; padding: 0.75rem;">
                        Pair ESP32 Device
                    </a>
                </div>
            </div>
            
            <div id="quick-printing" class="card" style="width: 100%; max-width: 720px;">
                <div class="card-header" style="text-align: center; padding: 1rem;">
                    <h3 class="card-title" style="margin: 0;">Printing</h3>
                </div>
                <div class="card-body">
                    <form method="POST" action="" enctype="multipart/form-data" style="display: flex; flex-direction: column; gap: 1rem;">
                        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                        <input type="hidden" name="request_service" value="1">
                        <input type="hidden" name="service_type" value="printing">
                        <input type="hidden" name="title" value="Printing">
                        
                        <div class="form-group">
                            <label>Color / Printing Type:</label>
                            <select name="color" id="print_color" required style="width: 100%; padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px;">
                                <?php foreach ($printing_services as $service): ?>
                                    <option value="<?php echo htmlspecialchars($service['color_options']); ?>" data-ppp="<?php echo (float)$service['price_per_page']; ?>">
                                        <?php echo htmlspecialchars($service['name']); ?>
                                        (<?php echo format_tokens((float)$service['price_per_page']); ?> tokens/page)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>Upload File to Print (PDF only):</label>
                            <input type="file" name="print_file" id="print_file" accept=".pdf" required style="width: 100%; padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px;">
                            <small style="color: var(--medium-gray);">Only PDF files are allowed for printing.</small>
                        </div>
                        
                        <div id="print_estimate" style="background:#f8fafc;border:1px solid #e5e7eb;border-radius:6px;padding:.75rem;display:none;">
                            <div style="font-size:.9rem;color:#374151;">
                                Estimated pages: <strong id="est_pages">-</strong> • Estimated tokens: <strong id="est_tokens">-</strong>
                            </div>
                            <small style="color:#6b7280;">Estimate updates after selecting a PDF and choosing type.</small>
                        </div>
                        
                        <button type="submit" class="btn btn-primary" style="width: 100%; padding: 0.75rem; border: none; border-radius: 4px; background: #007bff; color: white; cursor: pointer; font-weight: 500;">Request Printing Service</button>
                    </form>
                </div>
            </div>
            
            <div id="quick-equipment" class="card" style="width: 100%; max-width: 720px;">
                <div class="card-header" style="text-align: center; padding: 1rem;">
                    <h3 class="card-title" style="margin: 0;">Equipment Borrowing</h3>
                </div>
                <div class="card-body">
                    <form method="POST" action="" style="display: flex; flex-direction: column; gap: 1rem;">
                        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                        <input type="hidden" name="request_service" value="1">
                        <input type="hidden" name="service_type" value="equipment_borrowing">
                        
                        <div class="form-group">
                            <label>Item Category & Name:</label>
                            <select name="title" required style="width: 100%; padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px;">
                                <option value="">Select Item...</option>
                                <?php foreach ($equipment_by_category as $category => $items): ?>
                                    <optgroup label="<?php echo htmlspecialchars($category); ?>">
                                        <?php foreach ($items as $eq): ?>
                                            <option value="<?php echo htmlspecialchars($eq['name']); ?>">
                                                <?php echo htmlspecialchars($eq['name']); ?>
                                                <?php echo !empty($eq['is_consumable']) ? ' (Consumable)' : ''; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </optgroup>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>Reason for Borrowing:</label>
                            <textarea name="description" placeholder="Describe why you need this equipment..." required style="width: 100%; padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px; min-height: 80px; resize: vertical;"></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label>Borrowing Start Date & Time:</label>
                            <input type="datetime-local" name="start_date" required style="width: 100%; padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px;">
                        </div>
                        
                        <div class="form-group">
                            <label>Borrowing End Date & Time:</label>
                            <input type="datetime-local" name="end_date" required style="width: 100%; padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px;">
                        </div>
                        
                        <button type="submit" class="btn btn-primary" style="width: 100%; padding: 0.75rem; border: none; border-radius: 4px; background: #007bff; color: white; cursor: pointer; font-weight: 500;">Submit Borrowing Request</button>
                    </form>
                </div>
            </div>
        </div>
        
        <script>
        (function(){
            const fileInput = document.getElementById('print_file');
            const colorSel = document.getElementById('print_color');
            const estWrap = document.getElementById('print_estimate');
            const estPages = document.getElementById('est_pages');
            const estTokens = document.getElementById('est_tokens');
            
            let detectedPages = null;
            function getPricePerPage() {
                const opt = colorSel.options[colorSel.selectedIndex];
                return parseFloat(opt?.dataset?.ppp || '0');
            }
            function updateEstimate() {
                if (!detectedPages) return;
                const ppp = getPricePerPage();
                if (!ppp) return;
                const tokens = (ppp * detectedPages);
                estPages.textContent = detectedPages;
                estTokens.textContent = tokens.toFixed(2);
                estWrap.style.display = 'block';
            }
            function detectPdfPages(file) {
                detectedPages = null;
                estWrap.style.display = 'none';
                if (!file) return;
                const reader = new FileReader();
                reader.onload = function() {
                    try {
                        const text = new TextDecoder('utf-8').decode(reader.result);
                        const match = text.match(/\/Type\s*\/Page[^s]/g);
                        const count = match ? match.length : 1;
                        detectedPages = Math.max(1, count);
                        updateEstimate();
                    } catch (e) {
                        // Fallback minimal
                        detectedPages = 1;
                        updateEstimate();
                    }
                };
                reader.readAsArrayBuffer(file);
            }
            if (fileInput) {
                fileInput.addEventListener('change', (e) => detectPdfPages(e.target.files?.[0]));
            }
            if (colorSel) {
                colorSel.addEventListener('change', updateEstimate);
            }
        })();
        </script>
        
        <!-- Room Reservation -->
        <div id="quick-room" class="admin-section">
            <div class="card" style="width: 100%; max-width: 720px; margin: 0 auto;">
                <div class="card-header">
                    <h3 class="card-title">Request a Room</h3>
                </div>
                <div class="card-body">
                    <form method="POST" action="" style="display: grid; gap: 1rem;">
                        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                        <input type="hidden" name="reserve_room" value="1">

                        <div class="grid grid-2" style="gap: 1rem;">
                            <div class="form-group">
                                <label>Room:</label>
                                <select name="room_code" required>
                                    <option value="">Select room...</option>
                                    <?php foreach ($rooms as $room): ?>
                                        <option value="<?php echo htmlspecialchars($room); ?>"><?php echo htmlspecialchars($room); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Purpose Type:</label>
                                <select name="purpose_type" id="purpose_type" required>
                                    <option value="general">General</option>
                                    <option value="class">Class</option>
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-2" style="gap: 1rem;">
                            <div class="form-group">
                                <label>Start Date & Time:</label>
                                <input type="datetime-local" name="start_date" required>
                            </div>
                            <div class="form-group">
                                <label>End Date & Time:</label>
                                <input type="datetime-local" name="end_date" required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Purpose / Details:</label>
                            <textarea
                                name="purpose"
                                placeholder="e.g., Group study, project meeting, consultation..."
                                required
                                style="width: 100%; padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px; min-height: 80px; resize: vertical;"
                            ></textarea>
                        </div>

                        <div class="form-group" id="professor_group" style="display: none;">
                            <label>Professor (for class):</label>
                            <input type="text" name="professor_name" id="professor_name" placeholder="e.g., Prof. Santos">
                        </div>

                        <button type="submit" class="btn btn-primary">Request Room Reservation</button>
                        <small style="color: var(--medium-gray);">
                            Requests are reviewed by admins. Pending requests also block the same time slot.
                        </small>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Current Reservations & Requests -->
        <div id="quick-active-requests" class="admin-section">
            <h2 class="admin-section-title">Your Active Requests</h2>
            
            <div class="grid grid-2">
                <!-- Room Reservations -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Room Reservations</h3>
                    </div>
                    <div class="card-body">
                        <?php if (empty($user_room_reservations)): ?>
                            <p style="color: var(--medium-gray); text-align: center; padding: 2rem;">
                                No active reservations
                            </p>
                        <?php else: ?>
                            <?php foreach ($user_room_reservations as $reservation): ?>
                                <div style="padding: 1rem; border-bottom: 1px solid var(--light-gray); margin-bottom: 1rem;">
                                    <div style="display: flex; justify-content: space-between; align-items: center;">
                                        <div>
                                            <strong><?php echo htmlspecialchars($reservation['room_code'] ?? ''); ?></strong>
                                            <br>
                                            <small style="color: var(--medium-gray);">
                                                <?php echo date('M j, Y g:i A', strtotime($reservation['start_date'] ?? '')); ?> - 
                                                <?php echo date('M j, Y g:i A', strtotime($reservation['end_date'] ?? '')); ?>
                                            </small>
                                        </div>
                                        <span class="badge badge-<?php 
                                            echo match($reservation['status'] ?? '') {
                                                'approved' => 'success',
                                                'pending' => 'warning',
                                                'rejected' => 'danger',
                                                default => 'secondary'
                                            }; 
                                        ?>">
                                            <?php echo ucfirst($reservation['status'] ?? ''); ?>
                                        </span>
                                    </div>
                                    <p style="margin-top: 0.5rem; color: var(--medium-gray);">
                                        <strong>Purpose:</strong> <?php echo htmlspecialchars($reservation['purpose'] ?? ''); ?>
                                    </p>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Service Requests -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Service Requests</h3>
                    </div>
                    <div class="card-body">
                        <?php if (empty($user_requests)): ?>
                            <p style="color: var(--medium-gray); text-align: center; padding: 2rem;">
                                No active service requests
                            </p>
                        <?php else: ?>
                            <?php foreach ($user_requests as $request): ?>
                                <div style="padding: 1rem; border-bottom: 1px solid var(--light-gray); margin-bottom: 1rem;">
                                    <div style="display: flex; justify-content: space-between; align-items: center;">
                                        <div>
                                            <strong><?php echo htmlspecialchars($request['title'] ?? ''); ?></strong>
                                            <br>
                                            <small style="color: var(--medium-gray);">
                                                Type: <?php echo ucfirst($request['service_type'] ?? ''); ?> | 
                                                Urgency: <?php echo ucfirst($request['urgency'] ?? ''); ?> | 
                                                Created: <?php echo date('M j, Y g:i A', strtotime($request['created_at'] ?? '')); ?>
                                                <?php 
                                                if ($request['service_type'] === 'equipment_borrowing') {
                                                    echo '<small>' . date('M j, Y g:i A', strtotime($request['start_date'] ?? '')) . '</small><br>';
                                                    echo '<small>' . date('M j, Y g:i A', strtotime($request['end_date'] ?? '')) . '</small>';
                                                } else {
                                                    echo '<span class="badge badge-' . match($request['urgency'] ?? '') {
                                                        'low' => 'success',
                                                        'medium' => 'warning',
                                                        'high' => 'danger',
                                                        'critical' => 'danger',
                                                        default => 'secondary'
                                                    } . '">' . ucfirst($request['urgency'] ?? '') . '</span>';
                                                }
                                                ?>
                                            </small>
                                        </div>
                                        <span class="badge badge-<?php 
                                            echo match($request['status'] ?? '') {
                                                'completed' => 'success',
                                                'in_progress' => 'info',
                                                'pending' => 'warning',
                                                'rejected' => 'danger',
                                                default => 'secondary'
                                            }; 
                                        ?>">
                                            <?php echo ucfirst($request['status'] ?? ''); ?>
                                        </span>
                                    </div>
                                    <p style="margin-top: 0.5rem; color: var(--medium-gray);">
                                        <?php echo htmlspecialchars($request['description'] ?? ''); ?>
                                    </p>
                                    <?php if ($request['assigned_to_name'] !== 'Unassigned'): ?>
                                        <p style="margin-top: 0.5rem;">
                                            <strong>Assigned to:</strong> <?php echo htmlspecialchars($request['assigned_to_name'] ?? ''); ?>
                                        </p>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <div style="text-align: center; margin-top: 3rem;">
            <a href="index.php" class="btn btn-secondary btn-large">Back to Dashboard</a>
        </div>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const purposeType = document.getElementById('purpose_type');
    const professorGroup = document.getElementById('professor_group');
    const professorInput = document.getElementById('professor_name');

    if (!purposeType || !professorGroup || !professorInput) {
        return;
    }

    function updateProfessorField() {
        if (purposeType.value === 'class') {
            professorGroup.style.display = '';
            professorInput.required = true;
        } else {
            professorGroup.style.display = 'none';
            professorInput.required = false;
            professorInput.value = '';
        }
    }

    purposeType.addEventListener('change', updateProfessorField);
    updateProfessorField();

    // Smooth scroll for quick access links
    document.querySelectorAll('a[href^="#quick-"]').forEach(function (link) {
        link.addEventListener('click', function (e) {
            const targetId = this.getAttribute('href').substring(1);
            const target = document.getElementById(targetId);
            if (target) {
                e.preventDefault();
                target.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        });
    });
});
</script>

<?php include '../includes/footer.php'; ?>
