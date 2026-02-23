<?php
require_once '../config/config.php';
require_login();

$page_title = 'Resource & Service Automation';
$user_id = get_user_id();
$db = Database::getInstance()->getConnection();

// Initialize message variables
$message = '';
$message_type = '';

// Handle resource reservation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reserve_resource'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $message = 'Invalid security token.';
        $message_type = 'error';
    } else {
        $resource_id = (int)($_POST['resource_id'] ?? 0);
        $start_date = $_POST['start_date'] ?? '';
        $end_date = $_POST['end_date'] ?? '';
        $purpose = sanitize_input($_POST['purpose'] ?? '');
        
        try {
            $db->beginTransaction();
            
            // Check resource availability
            $stmt = $db->prepare("
                SELECT available_quantity, total_quantity, name, requires_approval, auto_approval,
                       (SELECT COUNT(*) FROM resource_reservations 
                        WHERE resource_id = ? AND status = 'approved' 
                        AND ((start_date <= ? AND end_date > ?) OR (start_date < ? AND end_date >= ?))
                ) as conflicting_reservations
                FROM resources WHERE id = ? AND is_active = true
            ");
            $stmt->execute([$resource_id, $start_date, $start_date, $end_date, $end_date, $resource_id]);
            $resource = $stmt->fetch();
            
            if (!$resource) {
                throw new Exception('Resource not found.');
            }
            
            if ($resource['conflicting_reservations'] >= $resource['available_quantity']) {
                throw new Exception('Resource not available for selected time period.');
            }
            
            // Check automation rules for auto-approval
            $auto_approve = false;
            $stmt = $db->prepare("
                SELECT actions FROM automation_rules 
                WHERE rule_type = 'approval' AND is_active = true 
                ORDER BY priority DESC
            ");
            $stmt->execute();
            $rules = $stmt->fetchAll();
            
            foreach ($rules as $rule) {
                $conditions = json_decode($rule['actions'], true);
                if (isset($conditions['auto_approve']) && $conditions['auto_approve']) {
                    $user_level = get_user_level($user_id);
                    if (isset($conditions['user_level']) && $conditions['user_level'] === $user_level) {
                        $auto_approve = true;
                        break;
                    }
                }
            }
            
            $status = $auto_approve ? 'approved' : 'pending';
            $approved_by = $auto_approve ? $user_id : null;
            $approved_at = $auto_approve ? date('Y-m-d H:i:s') : null;
            
            // Create reservation
            $stmt = $db->prepare("
                INSERT INTO resource_reservations 
                (resource_id, user_id, start_date, end_date, purpose, status, approved_by, approved_at, auto_approval)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$resource_id, $user_id, $start_date, $end_date, $purpose, $status, $approved_by, $approved_at, $auto_approve]);
            
            // Update resource quantity if needed
            if ($status === 'approved') {
                $stmt = $db->prepare("UPDATE resources SET available_quantity = available_quantity - 1 WHERE id = ?");
                $stmt->execute([$resource_id]);
            }
            
            $db->commit();
            $message = $auto_approve ? 
                'Reservation approved automatically!' : 
                'Reservation submitted successfully! Pending admin approval.';
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
        
        // Handle file upload for printing
        if ($service_type === 'printing' && isset($_FILES['print_file']) && $_FILES['print_file']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = __DIR__ . '/../uploads/printing/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            $file_name = 'print_' . $user_id . '_' . time() . '_' . basename($_FILES['print_file']['name']);
            $file_path = $upload_dir . $file_name;
            
            if (move_uploaded_file($_FILES['print_file']['tmp_name'], $file_path)) {
                $file_path = '/uploads/printing/' . $file_name;
            }
        }
        
        try {
            $db->beginTransaction();
            
            // Calculate tokens based on automation rules
            $tokens_required = 0;
            $stmt = $db->prepare("
                SELECT actions FROM automation_rules 
                WHERE rule_type = 'pricing' AND is_active = true 
                ORDER BY priority DESC
            ");
            $stmt->execute();
            $rules = $stmt->fetchAll();
            
            $user_level = get_user_level($user_id);
            foreach ($rules as $rule) {
                $conditions = json_decode($rule['actions'], true);
                if (isset($conditions['service_type']) && $conditions['service_type'] === $service_type) {
                    if (isset($conditions['user_level']) && $conditions['user_level'] === $user_level) {
                        if (isset($conditions['charge_tokens']) && $conditions['charge_tokens']) {
                            // Use standard pricing
                            if ($service_type === 'printing') {
                                $pages = (int)($_POST['pages'] ?? 1);
                                $stmt = $db->prepare("SELECT price_per_page FROM printing_services WHERE is_active = true LIMIT 1");
                                $stmt->execute();
                                $printing = $stmt->fetch();
                                $tokens_required = $printing['price_per_page'] * $pages;
                            } else {
                                $tokens_required = 50.00; // Default service charge
                            }
                        }
                    }
                }
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

// Get available resources
$resources = [];
$stmt = $db->prepare("
    SELECT r.*, 
           (SELECT COUNT(*) FROM resource_reservations rr 
            WHERE rr.resource_id = r.id AND rr.status = 'approved' 
            AND rr.end_date > NOW()) as active_reservations
    FROM resources r 
    WHERE r.is_active = true 
    ORDER BY r.category, r.name
");
$stmt->execute();
$resources = $stmt->fetchAll();

// Get user's current reservations
$user_reservations = [];
$stmt = $db->prepare("
    SELECT rr.*, r.name as resource_name, r.category as resource_category
    FROM resource_reservations rr
    JOIN resources r ON rr.resource_id = r.id
    WHERE rr.user_id = ? AND rr.end_date > NOW()
    ORDER BY rr.start_date DESC
");
$stmt->execute([$user_id]);
$user_reservations = $stmt->fetchAll();

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
        <h1 class="section-title">Resource & Service Automation</h1>
        
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <!-- Quick Actions -->
        <div class="grid grid-3" style="margin-bottom: 3rem;">
            <div class="card">
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
            
            <div class="card">
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
                            <label>Pages to Print:</label>
                            <input type="number" name="pages" min="1" max="50" value="1" required style="width: 100%; padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px;">
                        </div>
                        
                        <div class="form-group">
                            <label>Color:</label>
                            <select name="color" required style="width: 100%; padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px;">
                                <option value="bw">Black & White (1 token/page)</option>
                                <option value="color">Color (5 tokens/page)</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>Upload File to Print:</label>
                            <input type="file" name="print_file" accept=".pdf,.doc,.docx,.txt" required style="width: 100%; padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px;">
                            <small style="color: var(--medium-gray);">Supported formats: PDF, DOC, DOCX, TXT</small>
                        </div>
                        
                        <button type="submit" class="btn btn-primary" style="width: 100%; padding: 0.75rem; border: none; border-radius: 4px; background: #007bff; color: white; cursor: pointer; font-weight: 500;">Request Printing Service</button>
                    </form>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header" style="text-align: center; padding: 1rem;">
                    <h3 class="card-title" style="margin: 0;">Equipment Borrowing</h3>
                </div>
                <div class="card-body">
                    <form method="POST" action="" style="display: flex; flex-direction: column; gap: 1rem;">
                        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                        <input type="hidden" name="request_service" value="1">
                        <input type="hidden" name="service_type" value="equipment_borrowing">
                        
                        <div class="form-group">
                            <label>Equipment Type:</label>
                            <select name="title" required style="width: 100%; padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px;">
                                <option value="">Select Equipment...</option>
                                <option value="Projector">Projector</option>
                                <option value="HDMI Cable">HDMI Cable</option>
                                <option value="VGA Cable">VGA Cable</option>
                                <option value="Power Extension">Power Extension Cord</option>
                                <option value="Audio Speaker">Audio Speaker</option>
                                <option value="Microphone">Microphone</option>
                                <option value="Whiteboard">Whiteboard</option>
                                <option value="Marker Set">Marker Set</option>
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
        
        <!-- Resource Reservation -->
        <div class="admin-section">
            <h2 class="admin-section-title">Reserve Resources</h2>
            
            <div class="grid grid-3">
                <?php foreach ($resources as $resource): ?>
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title"><?php echo htmlspecialchars($resource['name']); ?></h3>
                            <span class="badge badge-<?php echo $resource['category'] === 'equipment' ? 'success' : 'info'; ?>">
                                <?php echo ucfirst($resource['category']); ?>
                            </span>
                        </div>
                        <div class="card-body">
                            <p><?php echo htmlspecialchars($resource['description']); ?></p>
                            
                            <div style="display: flex; justify-content: space-between; margin: 1rem 0;">
                                <span>
                                    <strong>Available:</strong> 
                                    <?php echo $resource['available_quantity']; ?> / <?php echo $resource['total_quantity']; ?>
                                </span>
                                <span>
                                    <strong>Location:</strong> 
                                    <?php echo htmlspecialchars($resource['location']); ?>
                                </span>
                            </div>
                            
                            <?php if ($resource['available_quantity'] > 0): ?>
                                <form method="POST" action="" style="margin-top: 1rem;">
                                    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                    <input type="hidden" name="reserve_resource" value="1">
                                    <input type="hidden" name="resource_id" value="<?php echo $resource['id']; ?>">
                                    
                                    <div class="form-group">
                                        <label>Start Date & Time:</label>
                                        <input type="datetime-local" name="start_date" required>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>End Date & Time:</label>
                                        <input type="datetime-local" name="end_date" required>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>Purpose:</label>
                                        <textarea name="purpose" placeholder="Describe how you'll use this resource..." required></textarea>
                                    </div>
                                    
                                    <button type="submit" class="btn btn-primary">
                                        <?php echo $resource['requires_approval'] ? 'Request Reservation' : 'Reserve Now'; ?>
                                    </button>
                                </form>
                            <?php else: ?>
                                <div class="alert alert-warning" style="margin-top: 1rem;">
                                    Currently not available
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- Current Reservations & Requests -->
        <div class="admin-section">
            <h2 class="admin-section-title">Your Active Requests</h2>
            
            <div class="grid grid-2">
                <!-- Resource Reservations -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Resource Reservations</h3>
                    </div>
                    <div class="card-body">
                        <?php if (empty($user_reservations)): ?>
                            <p style="color: var(--medium-gray); text-align: center; padding: 2rem;">
                                No active reservations
                            </p>
                        <?php else: ?>
                            <?php foreach ($user_reservations as $reservation): ?>
                                <div style="padding: 1rem; border-bottom: 1px solid var(--light-gray); margin-bottom: 1rem;">
                                    <div style="display: flex; justify-content: space-between; align-items: center;">
                                        <div>
                                            <strong><?php echo htmlspecialchars($reservation['resource_name'] ?? ''); ?></strong>
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

<?php include '../includes/footer.php'; ?>
