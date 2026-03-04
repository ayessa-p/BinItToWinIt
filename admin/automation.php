<?php
require_once '../config/config.php';
require_admin();

$page_title = 'Service Management';
$admin_user_id = get_user_id();
$db = Database::getInstance()->getConnection();

// Initialize message variables
$message = '';
$message_type = '';

// Handle resource creation/update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_resource'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $message = 'Invalid security token.';
        $message_type = 'error';
    } else {
        $id = isset($_POST['resource_id']) ? (int)$_POST['resource_id'] : 0;
        $name = sanitize_input($_POST['name'] ?? '');
        $description = sanitize_input($_POST['description'] ?? '');
        $category = sanitize_input($_POST['category'] ?? '');
        $type = sanitize_input($_POST['type'] ?? '');
        $total_quantity = (int)($_POST['total_quantity'] ?? 0);
        $location = sanitize_input($_POST['location'] ?? '');
        $condition_status = sanitize_input($_POST['condition_status'] ?? 'good');
        $requires_approval = isset($_POST['requires_approval']);
        $min_user_level = sanitize_input($_POST['min_user_level'] ?? 'student');
        $is_active = isset($_POST['is_active']);
        
        try {
            if ($id > 0) {
                // Update existing resource (keep available_quantity consistent with total_quantity)
                $stmt = $db->prepare("SELECT total_quantity, available_quantity FROM resources WHERE id = ?");
                $stmt->execute([$id]);
                $current = $stmt->fetch();
                if (!$current) {
                    throw new Exception('Resource not found.');
                }

                $old_total = (int)($current['total_quantity'] ?? 0);
                $old_available = (int)($current['available_quantity'] ?? 0);
                $delta = $total_quantity - $old_total;
                $new_available = $old_available + $delta;
                if ($new_available < 0) $new_available = 0;
                if ($new_available > $total_quantity) $new_available = $total_quantity;

                $stmt = $db->prepare("
                    UPDATE resources SET 
                    name = ?, description = ?, category = ?, type = ?, total_quantity = ?, available_quantity = ?,
                    location = ?, condition_status = ?, requires_approval = ?,
                    min_user_level = ?, is_active = ?, updated_at = NOW()
                    WHERE id = ?
                ");
                $stmt->execute([$name, $description, $category, $type, $total_quantity, $new_available,
                               $location, $condition_status, $requires_approval, $min_user_level, $is_active, $id]);
                $message = 'Resource updated successfully!';
            } else {
                // Create new resource
                $stmt = $db->prepare("
                    INSERT INTO resources 
                    (name, description, category, type, total_quantity, available_quantity, location, 
                     condition_status, requires_approval, min_user_level, is_active)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$name, $description, $category, $type, $total_quantity, 
                               $total_quantity, $location, $condition_status, $requires_approval, 
                               $min_user_level, $is_active]);
                $message = 'Resource created successfully!';
            }
            $message_type = 'success';
        } catch (Exception $e) {
            $message = 'Database error: ' . $e->getMessage();
            $message_type = 'error';
        }
    }
}

// Handle resource deletion (used for equipment management)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_resource'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $message = 'Invalid security token.';
        $message_type = 'error';
    } else {
        $resource_id = (int)($_POST['resource_id'] ?? 0);
        try {
            $stmt = $db->prepare("SELECT name FROM resources WHERE id = ?");
            $stmt->execute([$resource_id]);
            $resource = $stmt->fetch();

            if (!$resource) {
                $message = 'Resource not found.';
                $message_type = 'error';
            } else {
                $stmt = $db->prepare("DELETE FROM resources WHERE id = ?");
                $stmt->execute([$resource_id]);
                $message = "Resource '{$resource['name']}' deleted successfully!";
                $message_type = 'success';
            }
        } catch (Exception $e) {
            $message = 'Database error: ' . $e->getMessage();
            $message_type = 'error';
        }
    }
}

// Handle service request updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_service_request'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $message = 'Invalid security token.';
        $message_type = 'error';
    } else {
        $request_id = (int)($_POST['request_id'] ?? 0);
        $status = sanitize_input($_POST['status'] ?? '');
        $assigned_to = !empty($_POST['assigned_to']) ? (int)$_POST['assigned_to'] : null;
        $completion_notes = sanitize_input($_POST['completion_notes'] ?? '');
        $tokens_charged = (float)($_POST['tokens_charged'] ?? 0.00);
        
        try {
            $db->beginTransaction();
            
            // Get current request
            $stmt = $db->prepare("SELECT * FROM service_requests WHERE id = ?");
            $stmt->execute([$request_id]);
            $request = $stmt->fetch();
            
            if (!$request) {
                throw new Exception('Service request not found.');
            }
            
            // Update request
            $stmt = $db->prepare("
                UPDATE service_requests SET 
                status = ?, assigned_to = ?, completion_notes = ?, tokens_charged = ?,
                actual_completion_date = CASE WHEN ? = 'completed' THEN NOW() ELSE actual_completion_date END,
                updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$status, $assigned_to, $completion_notes, $tokens_charged, $status, $request_id]);
            
            // Charge tokens only on transition to completed (avoid double-charging)
            if ($status === 'completed' && ($request['status'] ?? '') !== 'completed' && $tokens_charged > 0) {
                // Update user tokens
                $stmt = $db->prepare("UPDATE users SET eco_tokens = eco_tokens - ? WHERE id = ?");
                $stmt->execute([$tokens_charged, $request['user_id']]);
                
                // Create transaction
                $stmt = $db->prepare("
                    INSERT INTO transactions (user_id, transaction_type, amount, description) 
                    VALUES (?, 'redeemed', ?, ?)
                ");
                $stmt->execute([$request['user_id'], $tokens_charged, "Service: " . $request['title']]);
            }
            
            $db->commit();
            $message = 'Service request updated successfully!';
            $message_type = 'success';
            
        } catch (Exception $e) {
            $db->rollBack();
            $message = 'Database error: ' . $e->getMessage();
            $message_type = 'error';
        }
    }
}

// Handle service request deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_service_request'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $message = 'Invalid security token.';
        $message_type = 'error';
    } else {
        $request_id = (int)($_POST['request_id'] ?? 0);
        
        if ($request_id > 0) {
            try {
                $db->beginTransaction();
                
                // Get request details before deletion
                $stmt = $db->prepare("SELECT * FROM service_requests WHERE id = ?");
                $stmt->execute([$request_id]);
                $request = $stmt->fetch();
                
                if ($request) {
                    // If request was completed and tokens were charged, refund tokens
                    if ($request['status'] === 'completed' && $request['tokens_charged'] > 0) {
                        $stmt = $db->prepare("UPDATE users SET eco_tokens = eco_tokens + ? WHERE id = ?");
                        $stmt->execute([$request['tokens_charged'], $request['user_id']]);
                        
                        // Create refund transaction
                        $stmt = $db->prepare("
                            INSERT INTO transactions (user_id, transaction_type, amount, description) 
                            VALUES (?, 'earned', ?, ?)
                        ");
                        $stmt->execute([$request['user_id'], $request['tokens_charged'], "Refund for deleted service: " . $request['title']]);
                    }
                    
                    // Delete the service request
                    $stmt = $db->prepare("DELETE FROM service_requests WHERE id = ?");
                    $stmt->execute([$request_id]);
                    
                    $db->commit();
                    $message = 'Service request deleted successfully!';
                    $message_type = 'success';
                } else {
                    $message = 'Service request not found.';
                    $message_type = 'error';
                }
                
            } catch (Exception $e) {
                $db->rollBack();
                $message = 'Database error: ' . $e->getMessage();
                $message_type = 'error';
            }
        } else {
            $message = 'Invalid request ID.';
            $message_type = 'error';
        }
    }
}

// Handle printing service creation/update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_printing_service'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $message = 'Invalid security token.';
        $message_type = 'error';
    } else {
        $service_id = (int)($_POST['printing_service_id'] ?? 0);
        $name = sanitize_input($_POST['name'] ?? '');
        $price_per_page = (float)($_POST['price_per_page'] ?? 0);
        $color_options = sanitize_input($_POST['color_options'] ?? 'bw');
        $is_available = isset($_POST['is_available']);
        $unavailable_reason = sanitize_input($_POST['unavailable_reason'] ?? '');
        $unavailable_from = $_POST['unavailable_from'] ?? null;
        $unavailable_to = $_POST['unavailable_to'] ?? null;
        $is_active = isset($_POST['is_active']);
        
        try {
            if ($service_id > 0) {
                $stmt = $db->prepare("
                    UPDATE printing_services SET
                        name = ?, description = ?, price_per_page = ?, color_options = ?, 
                        is_available = ?, unavailable_reason = ?, unavailable_from = ?, unavailable_to = ?, 
                        is_active = ?
                    WHERE id = ?
                ");
                $stmt->execute([$name, $name, $price_per_page, $color_options, $is_available, $unavailable_reason ?: null, $unavailable_from ?: null, $unavailable_to ?: null, $is_active, $service_id]);
                $message = 'Printing service updated successfully!';
            } else {
                $stmt = $db->prepare("
                    INSERT INTO printing_services 
                    (name, description, price_per_page, color_options, paper_size, is_available, unavailable_reason, unavailable_from, unavailable_to, is_active)
                    VALUES (?, ?, ?, ?, 'a4', ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$name, $name, $price_per_page, $color_options, $is_available, $unavailable_reason ?: null, $unavailable_from ?: null, $unavailable_to ?: null, $is_active]);
                $message = 'Printing service added successfully!';
            }
            $message_type = 'success';
        } catch (Exception $e) {
            $message = 'Database error: ' . $e->getMessage();
            $message_type = 'error';
        }
    }
}

// Handle internet plan creation/update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_internet_plan'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $message = 'Invalid security token.';
        $message_type = 'error';
    } else {
        $name = sanitize_input($_POST['name'] ?? '');
        $duration_minutes = (int)($_POST['duration_minutes'] ?? 0);
        $token_cost = (float)($_POST['token_cost'] ?? 0);
        $speed_mbps = (int)($_POST['speed_mbps'] ?? 0);
        $data_limit_mb = (int)($_POST['data_limit_mb'] ?? 0);
        $is_active = isset($_POST['is_active']);
        
        try {
            $stmt = $db->prepare("
                INSERT INTO internet_plans 
                (name, description, duration_minutes, token_cost, speed_mbps, data_limit_mb, is_active)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$name, $name, $duration_minutes, $token_cost, $speed_mbps, $data_limit_mb, $is_active]);
            $message = 'Internet plan added successfully!';
            $message_type = 'success';
        } catch (Exception $e) {
            $message = 'Database error: ' . $e->getMessage();
            $message_type = 'error';
        }
    }
}

// Handle printing service deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_printing_service'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $message = 'Invalid security token.';
        $message_type = 'error';
    } else {
        $service_id = (int)($_POST['service_id'] ?? 0);
        
        try {
            // Check if service exists
            $stmt = $db->prepare("SELECT name FROM printing_services WHERE id = ?");
            $stmt->execute([$service_id]);
            $service = $stmt->fetch();
            
            if (!$service) {
                $message = 'Printing service not found.';
                $message_type = 'error';
            } else {
                // Delete the service
                $stmt = $db->prepare("DELETE FROM printing_services WHERE id = ?");
                $stmt->execute([$service_id]);
                
                $message = "Printing service '{$service['name']}' deleted successfully!";
                $message_type = 'success';
            }
        } catch (Exception $e) {
            $message = 'Database error: ' . $e->getMessage();
            $message_type = 'error';
        }
    }
}

// Handle internet plan deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_internet_plan'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $message = 'Invalid security token.';
        $message_type = 'error';
    } else {
        $plan_id = (int)($_POST['plan_id'] ?? 0);
        
        try {
            // Check if plan exists
            $stmt = $db->prepare("SELECT name FROM internet_plans WHERE id = ?");
            $stmt->execute([$plan_id]);
            $plan = $stmt->fetch();
            
            if (!$plan) {
                $message = 'Internet plan not found.';
                $message_type = 'error';
            } else {
                // Delete the plan
                $stmt = $db->prepare("DELETE FROM internet_plans WHERE id = ?");
                $stmt->execute([$plan_id]);
                
                $message = "Internet plan '{$plan['name']}' deleted successfully!";
                $message_type = 'success';
            }
        } catch (Exception $e) {
            $message = 'Database error: ' . $e->getMessage();
            $message_type = 'error';
        }
    }
}


// Handle room reservation approval/rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_room_reservation'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $message = 'Invalid security token.';
        $message_type = 'error';
    } else {
        $reservation_id = (int)($_POST['reservation_id'] ?? 0);
        $action = sanitize_input($_POST['action'] ?? '');
        $notes = sanitize_input($_POST['notes'] ?? '');

        if ($reservation_id <= 0 || !in_array($action, ['approve', 'reject'], true)) {
            $message = 'Invalid room reservation request.';
            $message_type = 'error';
        } else {
            try {
                $db->beginTransaction();

                // Lock row to avoid race conditions
                $stmt = $db->prepare("SELECT * FROM room_reservations WHERE id = ? FOR UPDATE");
                $stmt->execute([$reservation_id]);
                $reservation = $stmt->fetch();

                if (!$reservation) {
                    throw new Exception('Room reservation not found.');
                }
                if (($reservation['status'] ?? '') !== 'pending') {
                    throw new Exception('Only pending reservations can be updated.');
                }

                if ($action === 'approve') {
                    // Ensure no overlap with other approved reservations
                    $stmt = $db->prepare("
                        SELECT COUNT(*) FROM room_reservations
                        WHERE room_code = ?
                          AND status = 'approved'
                          AND id <> ?
                          AND ((start_date <= ? AND end_date > ?) OR (start_date < ? AND end_date >= ?))
                    ");
                    $stmt->execute([
                        $reservation['room_code'],
                        $reservation_id,
                        $reservation['start_date'],
                        $reservation['start_date'],
                        $reservation['end_date'],
                        $reservation['end_date']
                    ]);
                    $conflicts = (int)$stmt->fetchColumn();
                    if ($conflicts > 0) {
                        throw new Exception('Cannot approve: room is already booked for that time period.');
                    }

                    $stmt = $db->prepare("
                        UPDATE room_reservations
                        SET status = 'approved',
                            approved_by = ?,
                            approved_at = NOW(),
                            approval_notes = ?,
                            updated_at = NOW()
                        WHERE id = ?
                    ");
                    $stmt->execute([$admin_user_id, $notes, $reservation_id]);

                    // Optionally set room as inactive for maintenance
                    if (!empty($_POST['set_room_inactive'])) {
                        $stmt = $db->prepare("UPDATE resources SET is_active = 0, updated_at = NOW() WHERE category = 'facility' AND type = 'room' AND name = ? LIMIT 1");
                        $stmt->execute([$reservation['room_code']]);
                    }

                    $message = 'Room reservation approved successfully!';
                    $message_type = 'success';
                } else {
                    $stmt = $db->prepare("
                        UPDATE room_reservations
                        SET status = 'rejected',
                            approved_by = ?,
                            approved_at = NOW(),
                            rejection_reason = ?,
                            updated_at = NOW()
                        WHERE id = ?
                    ");
                    $stmt->execute([$admin_user_id, $notes, $reservation_id]);

                    $message = 'Room reservation rejected successfully!';
                    $message_type = 'success';
                }

                $db->commit();
            } catch (Exception $e) {
                $db->rollBack();
                $message = 'Database error: ' . $e->getMessage();
                $message_type = 'error';
            }
        }
    }
}

// Get all resources
$resources = [];
$stmt = $db->prepare("
    SELECT r.*, 
           (SELECT COUNT(*) FROM resource_reservations rr WHERE rr.resource_id = r.id AND rr.status = 'approved' AND rr.end_date > NOW()) as active_reservations
    FROM resources r 
    ORDER BY r.category, r.name
");
$stmt->execute();
$resources = $stmt->fetchAll();

// Get pending/upcoming room reservations
$room_reservation_requests = [];
$stmt = $db->prepare("
    SELECT rr.*, u.full_name AS user_name, u.student_id AS user_student_id
    FROM room_reservations rr
    JOIN users u ON rr.user_id = u.id
    WHERE rr.end_date > NOW()
    ORDER BY FIELD(rr.status, 'pending', 'approved', 'rejected', 'cancelled', 'completed', 'no_show'),
             rr.start_date ASC
    LIMIT 200
");
$stmt->execute();
$room_reservation_requests = $stmt->fetchAll();

// Get all pending service requests
$pending_requests = [];
$stmt = $db->prepare("
    SELECT sr.*, u.full_name as user_name, u.student_id as user_student_id
    FROM service_requests sr
    JOIN users u ON sr.user_id = u.id
    WHERE sr.status IN ('pending', 'in_progress')
    ORDER BY sr.urgency DESC, sr.created_at DESC
");
$stmt->execute();
$pending_requests = $stmt->fetchAll();

// Editing context for Printing Services and Equipment Management
$editing_printing_service = null;
if (isset($_GET['edit_printing_service'])) {
    $edit_id = (int)$_GET['edit_printing_service'];
    if ($edit_id > 0) {
        $stmt = $db->prepare("SELECT * FROM printing_services WHERE id = ?");
        $stmt->execute([$edit_id]);
        $editing_printing_service = $stmt->fetch();
    }
}

$editing_equipment = null;
if (isset($_GET['edit_equipment'])) {
    $edit_id = (int)$_GET['edit_equipment'];
    if ($edit_id > 0) {
        $stmt = $db->prepare("SELECT * FROM resources WHERE id = ? AND category = 'equipment'");
        $stmt->execute([$edit_id]);
        $editing_equipment = $stmt->fetch();
    }
}

// Editing context for Rooms Management
$editing_room = null;
if (isset($_GET['edit_room'])) {
    $edit_id = (int)$_GET['edit_room'];
    if ($edit_id > 0) {
        $stmt = $db->prepare("SELECT * FROM resources WHERE id = ? AND category = 'facility' AND type = 'room'");
        $stmt->execute([$edit_id]);
        $editing_room = $stmt->fetch();
    }
}

// Get service request stats
$service_stats = [];
$stmt = $db->query("
    SELECT * FROM service_request_stats 
    ORDER BY total_requests DESC
");
$service_stats = $stmt->fetchAll();

include '../includes/admin_header.php';
?>

<div class="admin-content">
    <div class="admin-container">
        <div class="admin-page-header">
            <h1 class="admin-page-title">Service Management</h1>
            <p class="admin-page-subtitle">Manage services and equipment</p>
            <div class="admin-breadcrumb">
                <a href="<?php echo SITE_URL; ?>/admin/index.php">Home</a> / Automation
            </div>
        </div>
        
        <style>
            .admin-subnav { display: flex; flex-wrap: wrap; gap: .5rem; margin-bottom: 1rem; position: relative; z-index: 5; pointer-events: auto; }
            .admin-subnav .subnav-btn { text-decoration: none; cursor: pointer; pointer-events: auto; }
            .mgmt-section { display: none; }
            .mgmt-section.active { display: block; }
        </style>
        <div class="admin-subnav">
            <a href="#services" role="button" class="admin-btn admin-btn-secondary subnav-btn" data-target="services">Services</a>
            <a href="#equipment" role="button" class="admin-btn admin-btn-secondary subnav-btn" data-target="equipment">Equipment</a>
            <a href="#room-reservations" role="button" class="admin-btn admin-btn-secondary subnav-btn" data-target="room-reservations">Room Reservations</a>
            <a href="#service-requests" role="button" class="admin-btn admin-btn-secondary subnav-btn" data-target="service-requests">Service Requests</a>
            <a href="#statistics" role="button" class="admin-btn admin-btn-secondary subnav-btn" data-target="statistics">Statistics</a>
        </div>
        
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?>" style="margin-bottom: var(--spacing-md);">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <!-- Service Management -->
        <div class="admin-section mgmt-section active" id="services">
            <h2 class="admin-section-title"><i class="fa-solid fa-screwdriver-wrench"></i> Service Management</h2>

            <div class="grid grid-1">
                <!-- Printing Services Management -->
                <div class="card" id="printing-services">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fa-solid fa-print"></i> Printing Services</h3>
                        <p style="color: var(--medium-gray); margin: 0.5rem 0;">Manage printing services and pricing</p>
                    </div>
                    <div class="card-body">
                        <?php $is_editing_print = !empty($editing_printing_service); ?>

                        <form method="POST" action="#printing-services" style="margin-bottom: 2rem;">
                            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                            <input type="hidden" name="save_printing_service" value="1">
                            <input type="hidden" name="printing_service_id" value="<?php echo $is_editing_print ? (int)$editing_printing_service['id'] : 0; ?>">

                            <div class="admin-form-group">
                                <label>Service Name</label>
                                <input
                                    type="text"
                                    name="name"
                                    class="admin-form-input"
                                    placeholder="e.g., Black & White Printing"
                                    required
                                    value="<?php echo $is_editing_print ? htmlspecialchars($editing_printing_service['name'] ?? '') : ''; ?>"
                                >
                            </div>

                            <div class="admin-form-group">
                                <label>Price Per Page (tokens)</label>
                                <input
                                    type="number"
                                    name="price_per_page"
                                    class="admin-form-input"
                                    step="0.01"
                                    min="0"
                                    required
                                    value="<?php echo $is_editing_print ? htmlspecialchars((string)($editing_printing_service['price_per_page'] ?? '')) : ''; ?>"
                                >
                            </div>

                            <div class="admin-form-group">
                                <label>Color Type</label>
                                <select name="color_options" class="admin-form-input" required>
                                    <?php $color = $is_editing_print ? ($editing_printing_service['color_options'] ?? 'bw') : 'bw'; ?>
                                    <option value="bw" <?php echo $color === 'bw' ? 'selected' : ''; ?>>Black & White</option>
                                    <option value="color" <?php echo $color === 'color' ? 'selected' : ''; ?>>Color</option>
                                </select>
                            </div>

                            <div class="admin-form-group">
                                <?php 
                                $is_available = $is_editing_print ? !empty($editing_printing_service['is_available']) : true;
                                $unavailable_reason = $is_editing_print ? ($editing_printing_service['unavailable_reason'] ?? '') : '';
                                ?>
                                <label style="display: flex; align-items: center; gap: 0.5rem;">
                                    <input type="checkbox" name="is_available" <?php echo $is_available ? 'checked' : ''; ?>>
                                    <span>Available</span>
                                </label>
                                <small style="color: var(--medium-gray);">Uncheck to mark this printing service as temporarily unavailable.</small>
                            </div>

                            <div class="admin-form-group">
                                <label>Unavailable Reason (optional)</label>
                                <input
                                    type="text"
                                    name="unavailable_reason"
                                    class="admin-form-input"
                                    placeholder="e.g., Printer under maintenance"
                                    value="<?php echo htmlspecialchars($unavailable_reason); ?>"
                                >
                            </div>

                            <div class="admin-form-group">
                                <label>Unavailable From / To (optional)</label>
                                <div style="display:flex; gap:0.5rem; flex-wrap:wrap;">
                                    <input
                                        type="datetime-local"
                                        name="unavailable_from"
                                        class="admin-form-input"
                                        value="<?php echo $is_editing_print && !empty($editing_printing_service['unavailable_from']) ? date('Y-m-d\TH:i', strtotime($editing_printing_service['unavailable_from'])) : ''; ?>"
                                    >
                                    <input
                                        type="datetime-local"
                                        name="unavailable_to"
                                        class="admin-form-input"
                                        value="<?php echo $is_editing_print && !empty($editing_printing_service['unavailable_to']) ? date('Y-m-d\TH:i', strtotime($editing_printing_service['unavailable_to'])) : ''; ?>"
                                    >
                                </div>
                            </div>

                            <div class="admin-form-group">
                                <?php $print_active = $is_editing_print ? !empty($editing_printing_service['is_active']) : true; ?>
                                <label style="display: flex; align-items: center; gap: 0.5rem;">
                                    <input type="checkbox" name="is_active" <?php echo $print_active ? 'checked' : ''; ?>>
                                    <span>Active</span>
                                </label>
                            </div>

                            <div style="display:flex; gap: .75rem; flex-wrap: wrap;">
                                <button type="submit" class="admin-btn admin-btn-primary">
                                    <?php echo $is_editing_print ? 'Update Printing Service' : 'Add Printing Service'; ?>
                                </button>
                                <?php if ($is_editing_print): ?>
                                    <a href="automation.php#printing-services" class="admin-btn admin-btn-secondary">Cancel</a>
                                <?php endif; ?>
                            </div>
                        </form>

                        <div class="admin-table-container">
                            <table class="admin-table">
                                <thead>
                                    <tr>
                                        <th>Service Name</th>
                                        <th>Price/Page</th>
                                        <th>Type</th>
                                        <th>Availability</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $printing_services = $db->query("SELECT * FROM printing_services ORDER BY price_per_page ASC")->fetchAll();
                                    if (empty($printing_services)):
                                    ?>
                                        <tr>
                                            <td colspan="6" style="text-align:center; padding: var(--spacing-md); color: var(--medium-gray);">
                                                No printing services yet
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($printing_services as $service): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($service['name']); ?></td>
                                                <td><?php echo format_tokens($service['price_per_page']); ?></td>
                                                <td>
                                                    <span class="badge badge-<?php echo $service['color_options'] === 'color' ? 'info' : 'secondary'; ?>">
                                                        <?php echo ucfirst($service['color_options']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php if (!empty($service['is_available'])): ?>
                                                        <span class="badge badge-success">Available</span>
                                                    <?php else: ?>
                                                        <span class="badge badge-danger">Unavailable</span>
                                                        <?php if (!empty($service['unavailable_reason'])): ?>
                                                            <div style="margin-top:0.25rem; font-size:0.8rem; color:var(--medium-gray);">
                                                                <?php echo htmlspecialchars($service['unavailable_reason']); ?>
                                                            </div>
                                                        <?php endif; ?>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <span class="badge badge-<?php echo $service['is_active'] ? 'success' : 'warning'; ?>">
                                                        <?php echo $service['is_active'] ? 'Active' : 'Inactive'; ?>
                                                    </span>
                                                </td>
                                                <td style="white-space: nowrap;">
                                                    <a class="admin-btn admin-btn-secondary admin-btn-sm" href="automation.php?edit_printing_service=<?php echo (int)$service['id']; ?>#printing-services">
                                                        Edit
                                                    </a>
                                                    <form method="POST" action="#printing-services" style="display: inline;">
                                                        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                                        <input type="hidden" name="delete_printing_service" value="1">
                                                        <input type="hidden" name="service_id" value="<?php echo (int)$service['id']; ?>">
                                                        <button type="submit" class="admin-btn admin-btn-danger admin-btn-sm" onclick="return confirm('Delete this printing service?')">
                                                            Delete
                                                        </button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Equipment Management -->
        <div class="admin-section mgmt-section" id="equipment">
            <h2 class="admin-section-title"><i class="fa-solid fa-briefcase"></i> Equipment Management</h2>
            <div class="grid grid-1">
                <div class="card" id="equipment-management">
                    <div class="card-header">
                        <h3 class="card-title">Inventory</h3>
                        <p style="color: var(--medium-gray); margin: 0.5rem 0;">Manage equipment inventory and availability</p>
                    </div>
                    <div class="card-body">
                        <?php $is_editing_equipment = !empty($editing_equipment); ?>
                        <form method="POST" action="#equipment-management" style="margin-bottom: 1.5rem;">
                            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                            <input type="hidden" name="save_resource" value="1">
                            <input type="hidden" name="resource_id" value="<?php echo $is_editing_equipment ? (int)$editing_equipment['id'] : 0; ?>">
                            <input type="hidden" name="category" value="equipment">
                            
                            <div class="grid grid-2">
                                <div class="admin-form-group">
                                    <label class="admin-form-label">Equipment Name *</label>
                                    <input type="text" name="name" class="admin-form-input" required placeholder="e.g., Laptop, Projector"
                                           value="<?php echo $is_editing_equipment ? htmlspecialchars($editing_equipment['name'] ?? '') : ''; ?>">
                                </div>
                                <div class="admin-form-group">
                                    <label class="admin-form-label">Type</label>
                                    <?php $eq_type = $is_editing_equipment ? ($editing_equipment['type'] ?? '') : ''; ?>
                                    <select name="type" class="admin-form-select">
                                        <option value="">Select type...</option>
                                        <option value="laptop" <?php echo $eq_type === 'laptop' ? 'selected' : ''; ?>>Laptop</option>
                                        <option value="projector" <?php echo $eq_type === 'projector' ? 'selected' : ''; ?>>Projector</option>
                                        <option value="camera" <?php echo $eq_type === 'camera' ? 'selected' : ''; ?>>Camera</option>
                                        <option value="printer" <?php echo $eq_type === 'printer' ? 'selected' : ''; ?>>Printer</option>
                                        <option value="other" <?php echo $eq_type === 'other' ? 'selected' : ''; ?>>Other</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="admin-form-group">
                                <label class="admin-form-label">Description</label>
                                <textarea name="description" class="admin-form-textarea" placeholder="Specifications and notes"><?php 
                                    echo $is_editing_equipment ? htmlspecialchars($editing_equipment['description'] ?? '') : ''; 
                                ?></textarea>
                            </div>
                            
                            <div class="grid grid-3">
                                <div class="admin-form-group">
                                    <label class="admin-form-label">Total Quantity *</label>
                                    <input type="number" min="1" name="total_quantity" class="admin-form-input" required
                                           value="<?php echo $is_editing_equipment ? (int)($editing_equipment['total_quantity'] ?? 1) : ''; ?>">
                                </div>
                                <div class="admin-form-group">
                                    <label class="admin-form-label">Location</label>
                                    <input type="text" name="location" class="admin-form-input" placeholder="e.g., IT Lab, Room 301"
                                           value="<?php echo $is_editing_equipment ? htmlspecialchars($editing_equipment['location'] ?? '') : ''; ?>">
                                </div>
                                <div class="admin-form-group">
                                    <label class="admin-form-label">Condition</label>
                                    <?php $cond = $is_editing_equipment ? ($editing_equipment['condition_status'] ?? 'good') : 'good'; ?>
                                    <select name="condition_status" class="admin-form-select">
                                        <option value="excellent" <?php echo $cond === 'excellent' ? 'selected' : ''; ?>>Excellent</option>
                                        <option value="good" <?php echo $cond === 'good' ? 'selected' : ''; ?>>Good</option>
                                        <option value="fair" <?php echo $cond === 'fair' ? 'selected' : ''; ?>>Fair</option>
                                        <option value="poor" <?php echo $cond === 'poor' ? 'selected' : ''; ?>>Poor</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="grid grid-2">
                                <div class="admin-form-group">
                                    <label class="admin-form-label">Minimum User Level</label>
                                    <?php $lvl = $is_editing_equipment ? ($editing_equipment['min_user_level'] ?? 'student') : 'student'; ?>
                                    <select name="min_user_level" class="admin-form-select">
                                        <option value="student" <?php echo $lvl === 'student' ? 'selected' : ''; ?>>Student</option>
                                        <option value="officer" <?php echo $lvl === 'officer' ? 'selected' : ''; ?>>Officer</option>
                                        <option value="admin" <?php echo $lvl === 'admin' ? 'selected' : ''; ?>>Admin</option>
                                    </select>
                                </div>
                                <div class="admin-form-group">
                                    <label style="display:flex;align-items:center;gap:.5rem;">
                                        <?php $req = $is_editing_equipment ? !empty($editing_equipment['requires_approval']) : false; ?>
                                        <input type="checkbox" name="requires_approval" <?php echo $req ? 'checked' : ''; ?>>
                                        <span>Requires Approval</span>
                                    </label>
                                </div>
                            </div>
                            
                            <div class="admin-form-group">
                                <label style="display:flex;align-items:center;gap:.5rem;">
                                    <?php $active = $is_editing_equipment ? !empty($editing_equipment['is_active']) : true; ?>
                                    <input type="checkbox" name="is_active" <?php echo $active ? 'checked' : ''; ?>>
                                    <span>Active</span>
                                </label>
                            </div>
                            
                            <div style="display:flex; gap:.75rem; flex-wrap: wrap;">
                                <button type="submit" class="admin-btn admin-btn-primary">
                                    <?php echo $is_editing_equipment ? 'Update Equipment' : 'Add Equipment'; ?>
                                </button>
                                <?php if ($is_editing_equipment): ?>
                                    <a href="automation.php#equipment" class="admin-btn admin-btn-secondary">Cancel</a>
                                <?php endif; ?>
                            </div>
                        </form>
                        
                        <div class="admin-table-container">
                            <table class="admin-table">
                                <thead>
                                    <tr>
                                        <th>Equipment</th>
                                        <th>Type</th>
                                        <th>Total</th>
                                        <th>Available</th>
                                        <th>Location</th>
                                        <th>Condition</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $eq_rows = $db->query("SELECT * FROM resources WHERE category = 'equipment' ORDER BY name ASC")->fetchAll();
                                    if (empty($eq_rows)):
                                    ?>
                                        <tr>
                                            <td colspan="8" style="text-align:center; padding: var(--spacing-md); color: var(--medium-gray);">
                                                No equipment added yet
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($eq_rows as $item): ?>
                                            <tr>
                                                <td>
                                                    <div style="font-weight: bold;"><?php echo htmlspecialchars($item['name']); ?></div>
                                                    <?php if (!empty($item['description'])): ?>
                                                        <div style="font-size: 0.8rem; color: var(--medium-gray);">
                                                            <?php echo htmlspecialchars(substr($item['description'], 0, 60)) . (strlen($item['description']) > 60 ? '…' : ''); ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </td>
                                                <td><span class="badge badge-info"><?php echo ucfirst(htmlspecialchars($item['type'] ?: 'other')); ?></span></td>
                                                <td><?php echo (int)$item['total_quantity']; ?></td>
                                                <td>
                                                    <span style="color: <?php echo ((int)$item['available_quantity'] > 0) ? '#28a745' : '#dc3545'; ?>; font-weight: bold;">
                                                        <?php echo (int)$item['available_quantity']; ?>
                                                    </span>
                                                </td>
                                                <td><?php echo htmlspecialchars($item['location'] ?? 'N/A'); ?></td>
                                                <td>
                                                    <span class="badge badge-<?php 
                                                        echo match($item['condition_status'] ?? '') {
                                                            'excellent' => 'success',
                                                            'good' => 'info',
                                                            'fair' => 'warning',
                                                            'poor' => 'danger',
                                                            default => 'secondary'
                                                        };
                                                    ?>">
                                                        <?php echo ucfirst($item['condition_status'] ?? 'unknown'); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge badge-<?php echo !empty($item['is_active']) ? 'success' : 'warning'; ?>">
                                                        <?php echo !empty($item['is_active']) ? 'Active' : 'Inactive'; ?>
                                                    </span>
                                                </td>
                                                <td style="white-space: nowrap;">
                                                    <a class="admin-btn admin-btn-secondary admin-btn-sm" href="automation.php?edit_equipment=<?php echo (int)$item['id']; ?>#equipment">Edit</a>
                                                    <form method="POST" action="#equipment-management" style="display:inline;">
                                                        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                                        <input type="hidden" name="delete_resource" value="1">
                                                        <input type="hidden" name="resource_id" value="<?php echo (int)$item['id']; ?>">
                                                        <button type="submit" class="admin-btn admin-btn-danger admin-btn-sm" onclick="return confirm('Delete this equipment?')">Delete</button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Room Reservation Management -->
        <div class="admin-section mgmt-section" id="room-reservations">
            <h2 class="admin-section-title"><i class="fa-solid fa-building"></i> Room Reservation Management</h2>

            <div class="card" id="rooms-management" style="margin-bottom: 1rem;">
                <div class="card-header">
                    <h3 class="card-title">Rooms</h3>
                    <p style="color: var(--medium-gray); margin: 0.5rem 0;">Add, edit, or remove rooms available for reservation</p>
                </div>
                <div class="card-body">
                    <?php $is_editing_room = !empty($editing_room); ?>
                    <form method="POST" action="#room-reservations" style="margin-bottom: 1rem; display: grid; gap: 1rem;">
                        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                        <input type="hidden" name="save_resource" value="1">
                        <input type="hidden" name="resource_id" value="<?php echo $is_editing_room ? (int)$editing_room['id'] : 0; ?>">
                        <input type="hidden" name="category" value="facility">
                        <input type="hidden" name="type" value="room">
                        <input type="hidden" name="total_quantity" value="1">
                        <input type="hidden" name="condition_status" value="<?php echo $is_editing_room ? htmlspecialchars($editing_room['condition_status'] ?? 'good') : 'good'; ?>">
                        <input type="hidden" name="min_user_level" value="<?php echo $is_editing_room ? htmlspecialchars($editing_room['min_user_level'] ?? 'student') : 'student'; ?>">
                        <input type="hidden" name="requires_approval" value="">

                        <div class="grid grid-3">
                            <div class="admin-form-group">
                                <label class="admin-form-label">Room Code *</label>
                                <input type="text" name="name" class="admin-form-input" required placeholder="e.g., RM101"
                                       value="<?php echo $is_editing_room ? htmlspecialchars($editing_room['name'] ?? '') : ''; ?>">
                            </div>
                            <div class="admin-form-group">
                                <label class="admin-form-label">Location</label>
                                <input type="text" name="location" class="admin-form-input" placeholder="e.g., IT Building, Floor 2"
                                       value="<?php echo $is_editing_room ? htmlspecialchars($editing_room['location'] ?? '') : ''; ?>">
                            </div>
                            <div class="admin-form-group" style="display:flex; align-items:center; gap:.5rem;">
                                <?php $room_active = $is_editing_room ? !empty($editing_room['is_active']) : true; ?>
                                <label class="admin-form-label" style="margin:0;">Active</label>
                                <input type="checkbox" name="is_active" <?php echo $room_active ? 'checked' : ''; ?>>
                            </div>
                        </div>

                        <div style="display:flex; gap:.75rem; flex-wrap: wrap;">
                            <button type="submit" class="admin-btn admin-btn-primary">
                                <?php echo $is_editing_room ? 'Update Room' : 'Add Room'; ?>
                            </button>
                            <?php if ($is_editing_room): ?>
                                <a href="automation.php#room-reservations" class="admin-btn admin-btn-secondary">Cancel</a>
                            <?php endif; ?>
                        </div>
                    </form>

                    <div class="admin-table-container">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>Room</th>
                                    <th>Location</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $room_rows = $db->query("SELECT * FROM resources WHERE category = 'facility' AND type = 'room' ORDER BY name ASC")->fetchAll();
                                if (empty($room_rows)):
                                ?>
                                    <tr>
                                        <td colspan="4" style="text-align:center; padding: var(--spacing-md); color: var(--medium-gray);">
                                            No rooms added yet
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($room_rows as $room): ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($room['name']); ?></strong></td>
                                            <td><?php echo htmlspecialchars($room['location'] ?? ''); ?></td>
                                            <td>
                                                <span class="badge badge-<?php echo !empty($room['is_active']) ? 'success' : 'warning'; ?>">
                                                    <?php echo !empty($room['is_active']) ? 'Active' : 'Inactive'; ?>
                                                </span>
                                            </td>
                                            <td style="white-space:nowrap;">
                                                <a class="admin-btn admin-btn-secondary admin-btn-sm" href="automation.php?edit_room=<?php echo (int)$room['id']; ?>#room-reservations">Edit</a>
                                                <form method="POST" action="#room-reservations" style="display:inline;">
                                                    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                                    <input type="hidden" name="delete_resource" value="1">
                                                    <input type="hidden" name="resource_id" value="<?php echo (int)$room['id']; ?>">
                                                    <button type="submit" class="admin-btn admin-btn-danger admin-btn-sm" onclick="return confirm('Delete this room?')">Delete</button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <p style="margin-top: .75rem; color: var(--medium-gray); font-size: .9rem;">
                        Approving a reservation automatically blocks overlapping bookings. You may also mark a room as inactive for maintenance using the toggle above.
                    </p>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Room Reservations (IT Building)</h3>
                </div>
                <div class="card-body">
                    <div class="admin-table-container">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>Room</th>
                                    <th>User</th>
                                    <th>Start</th>
                                    <th>End</th>
                                    <th>Purpose</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($room_reservation_requests)): ?>
                                    <tr>
                                        <td colspan="7" style="text-align:center; padding: var(--spacing-lg); color: var(--medium-gray);">
                                            No room reservations found
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($room_reservation_requests as $rr): ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($rr['room_code'] ?? ''); ?></strong></td>
                                            <td>
                                                <?php echo htmlspecialchars($rr['user_name'] ?? ''); ?>
                                                <br>
                                                <small style="color: var(--medium-gray);"><?php echo htmlspecialchars($rr['user_student_id'] ?? ''); ?></small>
                                            </td>
                                            <td><small><?php echo date('M j, Y g:i A', strtotime($rr['start_date'] ?? '')); ?></small></td>
                                            <td><small><?php echo date('M j, Y g:i A', strtotime($rr['end_date'] ?? '')); ?></small></td>
                                            <td style="max-width: 380px;">
                                                <small><?php echo htmlspecialchars($rr['purpose'] ?? ''); ?></small>
                                            </td>
                                            <td>
                                                <span class="badge badge-<?php
                                                    echo match($rr['status'] ?? '') {
                                                        'pending' => 'warning',
                                                        'approved' => 'success',
                                                        'rejected' => 'danger',
                                                        'cancelled' => 'secondary',
                                                        default => 'secondary'
                                                    };
                                                ?>">
                                                    <?php echo ucfirst(str_replace('_', ' ', $rr['status'] ?? '')); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if (($rr['status'] ?? '') === 'pending'): ?>
                                                    <form method="POST" action="#room-reservations" style="display:flex; gap:.5rem; flex-wrap: wrap; align-items:center;">
                                                        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                                        <input type="hidden" name="update_room_reservation" value="1">
                                                        <input type="hidden" name="reservation_id" value="<?php echo (int)($rr['id'] ?? 0); ?>">
                                                        <input
                                                            type="text"
                                                            name="notes"
                                                            class="admin-form-input"
                                                            placeholder="Notes (optional)"
                                                            style="min-width: 180px;"
                                                        >
                                                        <label style="display:flex; align-items:center; gap:.35rem;">
                                                            <input type="checkbox" name="set_room_inactive">
                                                            <span style="font-size:.85rem; color: var(--medium-gray);">Set room inactive</span>
                                                        </label>
                                                        <button type="submit" name="action" value="approve" class="admin-btn admin-btn-primary admin-btn-sm">
                                                            Approve
                                                        </button>
                                                        <button type="submit" name="action" value="reject" class="admin-btn admin-btn-secondary admin-btn-sm" onclick="return confirm('Reject this room reservation?')">
                                                            Reject
                                                        </button>
                                                    </form>
                                                <?php else: ?>
                                                    <span style="color: var(--medium-gray);">—</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Service Request Management -->
        <div class="admin-section mgmt-section" id="service-requests">
            <h2 class="admin-section-title"><i class="fa-solid fa-wrench"></i> Service Request Management</h2>
            
            <!-- Request Statistics -->
            <div style="margin-bottom: 2rem;">
                <div style="display: flex; flex-wrap: wrap; gap: 2rem; justify-content: space-between; align-items: stretch;">
                    <div class="card" style="flex: 1 1 160px; min-width: 0; text-align: center;">
                        <div class="card-body">
                            <h3 style="color: var(--warning-color); margin: 0;"><?php 
                                $pending_count = $db->query("SELECT COUNT(*) FROM service_requests WHERE status = 'pending'")->fetchColumn();
                                echo $pending_count; 
                            ?></h3>
                            <p style="margin: 0; color: var(--medium-gray);">Pending Requests</p>
                        </div>
                    </div>
                    <div class="card" style="flex: 1 1 160px; min-width: 0; text-align: center;">
                        <div class="card-body">
                            <h3 style="color: var(--info-color); margin: 0;"><?php 
                                $progress_count = $db->query("SELECT COUNT(*) FROM service_requests WHERE status = 'in_progress'")->fetchColumn();
                                echo $progress_count; 
                            ?></h3>
                            <p style="margin: 0; color: var(--medium-gray);">In Progress</p>
                        </div>
                    </div>
                    <div class="card" style="flex: 1 1 160px; min-width: 0; text-align: center;">
                        <div class="card-body">
                            <h3 style="color: var(--success-color); margin: 0;"><?php 
                                $completed_count = $db->query("SELECT COUNT(*) FROM service_requests WHERE status = 'completed'")->fetchColumn();
                                echo $completed_count; 
                            ?></h3>
                            <p style="margin: 0; color: var(--medium-gray);">Completed Today</p>
                        </div>
                    </div>
                    <div class="card" style="flex: 1 1 160px; min-width: 0; text-align: center;">
                        <div class="card-body">
                            <h3 style="color: var(--primary-blue); margin: 0;"><?php echo format_tokens(
                                $db->query("SELECT SUM(tokens_charged) FROM service_requests WHERE status = 'completed' AND DATE(created_at) = CURDATE()")->fetchColumn() ?: 0
                            ); ?></h3>
                            <p style="margin: 0; color: var(--medium-gray);">Tokens Earned Today</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Filter and Search -->
            <div class="card" style="margin-bottom: 2rem;">
                <div class="card-body">
                    <form method="GET" action="" style="display: flex; gap: 1rem; align-items: flex-end; flex-wrap: wrap;">
                        <div class="admin-form-group" style="margin: 0;">
                            <label>Search</label>
                            <input type="text" name="search" class="admin-form-input" placeholder="Search requests..." value="<?php echo sanitize_input($_GET['search'] ?? ''); ?>">
                        </div>
                        <div class="admin-form-group" style="margin: 0;">
                            <label>Status</label>
                            <select name="status_filter" class="admin-form-input">
                                <option value="">All Status</option>
                                <option value="pending" <?php echo (($_GET['status_filter'] ?? '') === 'pending' ? 'selected' : ''); ?>>Pending</option>
                                <option value="in_progress" <?php echo (($_GET['status_filter'] ?? '') === 'in_progress' ? 'selected' : ''); ?>>In Progress</option>
                                <option value="completed" <?php echo (($_GET['status_filter'] ?? '') === 'completed' ? 'selected' : ''); ?>>Completed</option>
                                <option value="rejected" <?php echo (($_GET['status_filter'] ?? '') === 'rejected' ? 'selected' : ''); ?>>Rejected</option>
                            </select>
                        </div>
                        <div class="admin-form-group" style="margin: 0;">
                            <label>Service Type</label>
                            <select name="type_filter" class="admin-form-input">
                                <option value="">All Types</option>
                                <option value="printing" <?php echo (($_GET['type_filter'] ?? '') === 'printing' ? 'selected' : ''); ?>>Printing</option>
                                <option value="internet_access" <?php echo (($_GET['type_filter'] ?? '') === 'internet_access' ? 'selected' : ''); ?>>Internet Access</option>
                                <option value="equipment_borrowing" <?php echo (($_GET['type_filter'] ?? '') === 'equipment_borrowing' ? 'selected' : ''); ?>>Equipment Borrowing</option>
                            </select>
                        </div>
                        <div class="admin-form-group" style="margin: 0; display:flex; gap:0.5rem; align-items:flex-end;">
                            <button type="submit" class="admin-btn admin-btn-primary">Filter</button>
                            <a href="automation.php" class="admin-btn admin-btn-secondary">Clear</a>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Service Requests Table -->
            <div class="admin-table-container">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Request</th>
                            <th>User</th>
                            <th>Type</th>
                            <th>File</th>
                            <th>Start Date & Time</th>
                            <th>End Date & Time</th>
                            <th>Tokens</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        // Build query with filters
                        $where_conditions = ["1=1"];
                        $params = [];
                        
                        if (!empty($_GET['search'])) {
                            $where_conditions[] = "(sr.title LIKE ? OR sr.description LIKE ?)";
                            $search_term = '%' . sanitize_input($_GET['search']) . '%';
                            $params[] = $search_term;
                            $params[] = $search_term;
                        }
                        
                        if (!empty($_GET['status_filter'])) {
                            $where_conditions[] = "sr.status = ?";
                            $params[] = sanitize_input($_GET['status_filter']);
                        }
                        
                        if (!empty($_GET['type_filter'])) {
                            $where_conditions[] = "sr.service_type = ?";
                            $params[] = sanitize_input($_GET['type_filter']);
                        }
                        
                        $where_clause = implode(' AND ', $where_conditions);
                        
                        $stmt = $db->prepare("
                            SELECT sr.*, 
                                   u.full_name as user_name, 
                                   u.student_id as user_student_id,
                                   CASE WHEN sr.assigned_to IS NULL THEN 'Unassigned' ELSE assigned_user.full_name END as assigned_to_name
                            FROM service_requests sr
                            JOIN users u ON sr.user_id = u.id
                            LEFT JOIN users assigned_user ON sr.assigned_to = assigned_user.id
                            WHERE $where_clause
                            ORDER BY sr.created_at DESC
                            LIMIT 50
                        ");
                        $stmt->execute($params);
                        $all_requests = $stmt->fetchAll();
                        
                        if (empty($all_requests)): 
                        ?>
                            <tr>
                                <td colspan="8" style="text-align: center; padding: var(--spacing-lg); color: var(--medium-gray);">
                                    No service requests found
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($all_requests as $request): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($request['title']); ?></strong>
                                        <br>
                                        <small style="color: var(--medium-gray);">
                                            <?php echo date('M j, Y g:i A', strtotime($request['created_at'])); ?>
                                        </small>
                                        <br>
                                        <small style="color: var(--medium-gray);">
                                            ID: #<?php echo $request['id']; ?>
                                        </small>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars($request['user_name']); ?>
                                        <br>
                                        <small style="color: var(--medium-gray);"><?php echo htmlspecialchars($request['user_student_id']); ?></small>
                                    </td>
                                    <td>
                                        <span class="badge badge-info"><?php echo ucfirst(str_replace('_', ' ', $request['service_type'])); ?></span>
                                    </td>
                                    <td>
                                        <?php if (!empty($request['file_path'])): ?>
                                            <a href="<?php echo SITE_URL . $request['file_path']; ?>" target="_blank" class="admin-btn admin-btn-secondary admin-btn-sm" style="white-space: nowrap;">
                                                View File
                                            </a>
                                        <?php else: ?>
                                            <span style="color: var(--medium-gray);">No file</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php 
                                        if ($request['service_type'] === 'equipment_borrowing') {
                                            echo '<small>' . date('M j, Y g:i A', strtotime($request['start_date'] ?? '')) . '</small>';
                                        } else {
                                            echo '<span class="badge badge-' . match($request['urgency']) {
                                                'low' => 'success',
                                                'medium' => 'warning',
                                                'high' => 'danger',
                                                'critical' => 'danger',
                                                default => 'secondary'
                                            } . '">' . ucfirst($request['urgency']) . '</span>';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <?php 
                                        if ($request['service_type'] === 'equipment_borrowing') {
                                            echo '<small>' . date('M j, Y g:i A', strtotime($request['end_date'] ?? '')) . '</small>';
                                        } else {
                                            echo '<span style="color: var(--medium-gray);">—</span>';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <?php echo format_tokens($request['tokens_required']); ?>
                                        <?php if ($request['tokens_charged'] > 0): ?>
                                            <br><small style="color: var(--success-color);">Charged: <?php echo format_tokens($request['tokens_charged']); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge badge-<?php 
                                            echo match($request['status']) {
                                                'pending' => 'warning',
                                                'in_progress' => 'info',
                                                'completed' => 'success',
                                                'rejected' => 'danger',
                                                'cancelled' => 'secondary',
                                                default => 'secondary'
                                            }; 
                                        ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $request['status'])); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div style="display:flex; gap:.5rem; flex-wrap: wrap;">
                                            <?php if ($request['status'] === 'pending'): ?>
                                                <form method="POST" action="" style="display:inline;">
                                                    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                                    <input type="hidden" name="update_service_request" value="1">
                                                    <input type="hidden" name="request_id" value="<?php echo (int)$request['id']; ?>">
                                                    <input type="hidden" name="status" value="in_progress">
                                                    <input type="hidden" name="assigned_to" value="<?php echo (int)$admin_user_id; ?>">
                                                    <input type="hidden" name="completion_notes" value="">
                                                    <input type="hidden" name="tokens_charged" value="<?php echo htmlspecialchars((string)($request['tokens_charged'] ?? 0)); ?>">
                                                    <button type="submit" class="admin-btn admin-btn-primary admin-btn-sm">
                                                        Accept
                                                    </button>
                                                </form>

                                                <form method="POST" action="" style="display:inline;">
                                                    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                                    <input type="hidden" name="update_service_request" value="1">
                                                    <input type="hidden" name="request_id" value="<?php echo (int)$request['id']; ?>">
                                                    <input type="hidden" name="status" value="rejected">
                                                    <input type="hidden" name="assigned_to" value="">
                                                    <input type="hidden" name="completion_notes" value="">
                                                    <input type="hidden" name="tokens_charged" value="0">
                                                    <button type="submit" class="admin-btn admin-btn-secondary admin-btn-sm" onclick="return confirm('Reject this request?')">
                                                        Reject
                                                    </button>
                                                </form>
                                            <?php endif; ?>

                                            <?php if ($request['status'] === 'in_progress'): ?>
                                                <form method="POST" action="" style="display:inline;">
                                                    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                                    <input type="hidden" name="update_service_request" value="1">
                                                    <input type="hidden" name="request_id" value="<?php echo (int)$request['id']; ?>">
                                                    <input type="hidden" name="status" value="completed">
                                                    <input type="hidden" name="assigned_to" value="<?php echo (int)($request['assigned_to'] ?? $admin_user_id); ?>">
                                                    <input type="hidden" name="completion_notes" value="<?php echo htmlspecialchars((string)($request['completion_notes'] ?? '')); ?>">
                                                    <input type="hidden" name="tokens_charged" value="<?php echo htmlspecialchars((string)($request['tokens_required'] ?? $request['tokens_charged'] ?? 0)); ?>">
                                                    <button type="submit" class="admin-btn admin-btn-primary admin-btn-sm" onclick="return confirm('Mark this request as completed? Tokens will be deducted.')">
                                                        Complete
                                                    </button>
                                                </form>

                                                <form method="POST" action="" style="display:inline;">
                                                    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                                    <input type="hidden" name="update_service_request" value="1">
                                                    <input type="hidden" name="request_id" value="<?php echo (int)$request['id']; ?>">
                                                    <input type="hidden" name="status" value="cancelled">
                                                    <input type="hidden" name="assigned_to" value="<?php echo (int)($request['assigned_to'] ?? $admin_user_id); ?>">
                                                    <input type="hidden" name="completion_notes" value="<?php echo htmlspecialchars((string)($request['completion_notes'] ?? '')); ?>">
                                                    <input type="hidden" name="tokens_charged" value="0">
                                                    <button type="submit" class="admin-btn admin-btn-secondary admin-btn-sm" onclick="return confirm('Cancel this request?')">
                                                        Cancel
                                                    </button>
                                                </form>
                                            <?php endif; ?>

                                            <button class="admin-btn admin-btn-danger admin-btn-sm" onclick="deleteRequest(<?php echo (int)$request['id']; ?>)">
                                                Delete
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Statistics Dashboard -->
        <div class="admin-section mgmt-section" id="statistics">
            <h2 class="admin-section-title"><i class="fa-solid fa-chart-column"></i> System Statistics</h2>
            
            <div class="grid grid-1">
                <!-- Service Request Stats -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Service Request Statistics</h3>
                    </div>
                    <div class="card-body">
                        <div class="admin-table-container">
                            <table class="admin-table">
                                <thead>
                                    <tr>
                                        <th>Service Type</th>
                                        <th>Total Requests</th>
                                        <th>Completed</th>
                                        <th>Pending</th>
                                        <th>Avg Completion Time</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($service_stats)): ?>
                                        <tr>
                                            <td colspan="5" style="text-align: center; padding: var(--spacing-lg); color: var(--medium-gray);">
                                                No service statistics available
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($service_stats as $stat): ?>
                                            <tr>
                                                <td>
                                                    <span class="badge badge-info"><?php echo ucfirst($stat['service_type']); ?></span>
                                                </td>
                                                <td><?php echo $stat['total_requests']; ?></td>
                                                <td><?php echo $stat['completed_requests']; ?></td>
                                                <td><?php echo $stat['pending_requests']; ?></td>
                                                <td><?php echo number_format((float)($stat['avg_completion_hours'] ?? 0), 1); ?> hrs</td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
            </div>
        </div>
    </div>
</div>

<script>
function deleteRequest(id) {
    // Delete service request
    if (confirm('Are you sure you want to delete this service request? This action cannot be undone.')) {
        // Create form for deletion
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '';
        
        // Add CSRF token
        const csrfToken = document.createElement('input');
        csrfToken.type = 'hidden';
        csrfToken.name = 'csrf_token';
        csrfToken.value = '<?php echo generate_csrf_token(); ?>';
        form.appendChild(csrfToken);
        
        // Add delete action
        const deleteAction = document.createElement('input');
        deleteAction.type = 'hidden';
        deleteAction.name = 'delete_service_request';
        deleteAction.value = '1';
        form.appendChild(deleteAction);
        
        // Add request ID
        const requestId = document.createElement('input');
        requestId.type = 'hidden';
        requestId.name = 'request_id';
        requestId.value = id;
        form.appendChild(requestId);
        
        // Submit form
        document.body.appendChild(form);
        form.submit();
    }
}

;(function(){
    const sections = Array.from(document.querySelectorAll('.mgmt-section'));
    const subnav = Array.from(document.querySelectorAll('.admin-subnav .subnav-btn'));
    function activate(id) {
        sections.forEach(s => s.classList.toggle('active', s.id === id));
        subnav.forEach(a => {
            const tgt = a.getAttribute('data-target');
            if (tgt === id) {
                a.classList.remove('admin-btn-secondary');
                a.classList.add('admin-btn-primary');
            } else {
                a.classList.remove('admin-btn-primary');
                a.classList.add('admin-btn-secondary');
            }
        });
    }
    function fromHash(h) {
        const v = (h || '').replace('#','');
        const known = ['services','equipment','room-reservations','service-requests','statistics'];
        return known.includes(v) ? v : 'services';
    }
    subnav.forEach(a => {
        a.addEventListener('click', function(e) {
            e.preventDefault();
            const tgt = a.getAttribute('data-target');
            if (!tgt) return;
            if (location.hash !== '#' + tgt) {
                location.hash = '#' + tgt;
            }
            activate(tgt);
        });
    });
    window.addEventListener('hashchange', () => activate(fromHash(location.hash)));
    activate(fromHash(location.hash));
})();
</script>

<?php include '../includes/admin_footer.php'; ?>
