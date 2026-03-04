<?php
require_once '../config/config.php';
require_admin();

$page_title = 'System Reports';
$message = '';
$message_type = '';

$db = Database::getInstance()->getConnection();

// Include the PDF generator
require_once '../includes/pdf_generator.php';

// Handle PDF download
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['download_pdf'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        die('Invalid security token.');
    }
    
    $report_type = sanitize_input($_POST['report_type'] ?? '');
    $date_from = $_POST['date_from'] ?? '';
    $date_to = $_POST['date_to'] ?? '';
    
    if (empty($report_type) || empty($date_from) || empty($date_to)) {
        die('Missing required parameters.');
    }
    
    try {
        $report_data = [];
        
        switch ($report_type) {
            case 'users':
                $stmt = $db->prepare("
                    SELECT u.id, u.full_name, u.student_id, u.email, u.eco_tokens, u.is_active,
                           COUNT(ra.id) as recycling_activities,
                           SUM(ra.tokens_earned) as total_tokens_earned,
                           COUNT(DISTINCT r.id) as redemptions_made
                    FROM users u
                    LEFT JOIN recycling_activities ra ON u.id = ra.user_id AND ra.created_at BETWEEN ? AND ?
                    LEFT JOIN redemptions r ON u.id = r.user_id AND r.created_at BETWEEN ? AND ?
                    GROUP BY u.id, u.full_name, u.student_id, u.email, u.eco_tokens, u.is_active
                    ORDER BY u.full_name
                ");
                $stmt->execute([$date_from, $date_to, $date_from, $date_to]);
                $report_data = $stmt->fetchAll();
                break;
                
            case 'recycling':
                $stmt = $db->prepare("
                    SELECT ra.*, u.full_name, u.student_id
                    FROM recycling_activities ra
                    JOIN users u ON ra.user_id = u.id
                    WHERE ra.created_at BETWEEN ? AND ?
                    ORDER BY ra.created_at DESC
                ");
                $stmt->execute([$date_from, $date_to]);
                $report_data = $stmt->fetchAll();
                break;
                
            case 'redemptions':
                $stmt = $db->prepare("
                    SELECT r.*, u.full_name, u.student_id, rw.name as reward_name
                    FROM redemptions r
                    JOIN users u ON r.user_id = u.id
                    JOIN rewards rw ON r.reward_id = rw.id
                    WHERE r.created_at BETWEEN ? AND ?
                    ORDER BY r.created_at DESC
                ");
                $stmt->execute([$date_from, $date_to]);
                $report_data = $stmt->fetchAll();
                break;
                
            case 'events':
                $stmt = $db->prepare("
                    SELECT e.*, COUNT(ea.id) as total_attendance, 
                           COUNT(CASE WHEN ea.attendance_status = 'approved' THEN 1 END) as approved_attendance
                    FROM events e
                    LEFT JOIN event_attendance ea ON e.id = ea.event_id
                    WHERE e.event_date BETWEEN ? AND ?
                    GROUP BY e.id, e.title, e.event_date, e.location, e.is_published
                    ORDER BY e.event_date DESC
                ");
                $stmt->execute([$date_from, $date_to]);
                $report_data = $stmt->fetchAll();
                break;
                
            case 'transactions':
                $stmt = $db->prepare("
                    SELECT t.*, u.full_name, u.student_id
                    FROM transactions t
                    JOIN users u ON t.user_id = u.id
                    WHERE t.created_at BETWEEN ? AND ?
                    ORDER BY t.created_at DESC
                ");
                $stmt->execute([$date_from, $date_to]);
                $report_data = $stmt->fetchAll();
                break;
             
         case 'top_recyclers':
             $stmt = $db->prepare("
                 SELECT u.full_name, u.student_id, COUNT(ra.id) as bottles_recycled, SUM(ra.tokens_earned) as total_tokens
                 FROM users u
                 LEFT JOIN recycling_activities ra ON u.id = ra.user_id AND ra.created_at BETWEEN ? AND ?
                 GROUP BY u.id, u.full_name, u.student_id
                 HAVING bottles_recycled > 0
                 ORDER BY bottles_recycled DESC, total_tokens DESC
                 LIMIT 50
             ");
             $stmt->execute([$date_from, $date_to]);
             $report_data = $stmt->fetchAll();
             break;
                
            default:
                throw new Exception('Invalid report type.');
        }
        generate_pdf_report($report_type, $report_data, $date_from, $date_to);
        
    } catch (Exception $e) {
        die('Error generating PDF: ' . $e->getMessage());
    }
}

// Handle report generation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate_report'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $message = 'Invalid security token.';
        $message_type = 'error';
        
        // Return AJAX error response
        echo '<div class="alert alert-' . $message_type . '">' . htmlspecialchars($message) . '</div>';
        exit;
    } else {
        $report_type = sanitize_input($_POST['report_type'] ?? '');
        $date_from = $_POST['date_from'] ?? '';
        $date_to = $_POST['date_to'] ?? '';
        
        if (empty($report_type) || empty($date_from) || empty($date_to)) {
            $message = 'Please fill in all required fields.';
            $message_type = 'error';
            
            // Return AJAX error response
            echo '<div class="alert alert-' . $message_type . '">' . htmlspecialchars($message) . '</div>';
            exit;
        } else {
            try {
                $report_data = [];
                
                switch ($report_type) {
                    case 'users':
                        $stmt = $db->prepare("
                            SELECT u.id, u.full_name, u.student_id, u.email, u.eco_tokens, u.is_active,
                                   COUNT(ra.id) as recycling_activities,
                                   SUM(ra.tokens_earned) as total_tokens_earned,
                                   COUNT(DISTINCT r.id) as redemptions_made
                            FROM users u
                            LEFT JOIN recycling_activities ra ON u.id = ra.user_id AND ra.created_at BETWEEN ? AND ?
                            LEFT JOIN redemptions r ON u.id = r.user_id AND r.created_at BETWEEN ? AND ?
                            GROUP BY u.id, u.full_name, u.student_id, u.email, u.eco_tokens, u.is_active
                            ORDER BY u.full_name
                        ");
                        $stmt->execute([$date_from, $date_to, $date_from, $date_to]);
                        $report_data = $stmt->fetchAll();
                        break;
                        
                    case 'recycling':
                        $stmt = $db->prepare("
                            SELECT ra.*, u.full_name, u.student_id
                            FROM recycling_activities ra
                            JOIN users u ON ra.user_id = u.id
                            WHERE ra.created_at BETWEEN ? AND ?
                            ORDER BY ra.created_at DESC
                        ");
                        $stmt->execute([$date_from, $date_to]);
                        $report_data = $stmt->fetchAll();
                        break;
                        
                    case 'redemptions':
                        $stmt = $db->prepare("
                            SELECT r.*, u.full_name, u.student_id, rw.name as reward_name
                            FROM redemptions r
                            JOIN users u ON r.user_id = u.id
                            JOIN rewards rw ON r.reward_id = rw.id
                            WHERE r.created_at BETWEEN ? AND ?
                            ORDER BY r.created_at DESC
                        ");
                        $stmt->execute([$date_from, $date_to]);
                        $report_data = $stmt->fetchAll();
                        break;
                        
                    case 'events':
                        $stmt = $db->prepare("
                            SELECT e.*, 
                                   COUNT(DISTINCT ea.id) as total_attendance,
                                   COUNT(DISTINCT CASE WHEN ea.attendance_status = 'approved' THEN ea.id END) as approved_attendance
                            FROM events e
                            LEFT JOIN event_attendance ea ON e.id = ea.event_id
                            WHERE e.event_date BETWEEN ? AND ?
                            GROUP BY e.id, e.title, e.description, e.event_date, e.location, e.is_published
                            ORDER BY e.event_date DESC
                        ");
                        $stmt->execute([$date_from, $date_to]);
                        $report_data = $stmt->fetchAll();
                        break;
                        
                    case 'transactions':
                        $stmt = $db->prepare("
                            SELECT t.*, u.full_name, u.student_id
                            FROM transactions t
                            JOIN users u ON t.user_id = u.id
                            WHERE t.created_at BETWEEN ? AND ?
                            ORDER BY t.created_at DESC
                        ");
                        $stmt->execute([$date_from, $date_to]);
                        $report_data = $stmt->fetchAll();
                        break;
                        
                    case 'top_recyclers':
                        $stmt = $db->prepare("
                            SELECT u.full_name, u.student_id, COUNT(ra.id) as bottles_recycled, SUM(ra.tokens_earned) as total_tokens
                            FROM users u
                            LEFT JOIN recycling_activities ra ON u.id = ra.user_id AND ra.created_at BETWEEN ? AND ?
                            GROUP BY u.id, u.full_name, u.student_id
                            HAVING bottles_recycled > 0
                            ORDER BY bottles_recycled DESC, total_tokens DESC
                            LIMIT 50
                        ");
                        $stmt->execute([$date_from, $date_to]);
                        $report_data = $stmt->fetchAll();
                        break;
                        
                    default:
                        throw new Exception('Invalid report type.');
                }
                
                // Generate report data for download (no redirect)
                $html_report = generate_html_report($report_type, $report_data, $date_from, $date_to);
                $message = 'Report generated successfully! Click "Download PDF" to open a printable report.';
                $message_type = 'success';
                
                // Store report data in session for download
                $_SESSION['generated_report'] = [
                    'type' => $report_type,
                    'date_from' => $date_from,
                    'date_to' => $date_to,
                    'data' => $report_data,
                    'html' => $html_report
                ];
                
                // Return AJAX response
                echo '<div class="alert alert-' . $message_type . '">' . htmlspecialchars($message) . '</div>';
                echo '<script>document.getElementById("downloadPdfBtn").disabled = false;</script>';
                exit;
                
            } catch (Exception $e) {
                $message = 'Error generating report: ' . $e->getMessage();
                $message_type = 'error';
                
                // Return AJAX error response
                echo '<div class="alert alert-' . $message_type . '">' . htmlspecialchars($message) . '</div>';
                exit;
            }
        }
    }
}

function generate_pdf_report($report_type, $data, $date_from, $date_to) {
    $title = ucfirst($report_type) . ' Report';
    $date_range = date('F j, Y', strtotime($date_from)) . ' - ' . date('F j, Y', strtotime($date_to));
    $generated_on = date('F j, Y g:i A');
    
    $html_content = generate_html_report($report_type, $data, $date_from, $date_to, true);
    
    $pdf_html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>' . $title . '</title>
    <style>
        @page { size: A4; margin: 18mm; }
        :root { --primary:#1976d2; --primary-600:#1565c0; --ink:#1f2937; --muted:#6b7280; --line:#e5effa; --row:#f7fbff; }
        * { box-sizing: border-box; }
        body { font-family: Arial, sans-serif; color: var(--ink); }
        .screen-toolbar { display: flex; gap: 8px; justify-content: flex-end; margin-bottom: 12px; }
        .screen-toolbar button { background: var(--primary); color: #fff; border: 0; padding: 8px 12px; border-radius: 6px; cursor: pointer; }
        .screen-toolbar button:hover { background: var(--primary-600); }
        .report { border: 1px solid var(--line); border-radius: 10px; overflow: hidden; }
        .report-header { background: var(--primary); color: #fff; padding: 16px 20px; }
        .report-title { margin: 0; font-size: 18px; letter-spacing: 0.3px; }
        .report-meta { margin: 6px 0 0 0; font-size: 12px; opacity: 0.95; }
        .report-body { padding: 16px 20px; }
        .summary { display: grid; grid-template-columns: repeat(3, minmax(0,1fr)); gap: 12px; margin-bottom: 16px; }
        .summary .card { border: 1px solid var(--line); border-radius: 8px; padding: 10px 12px; background: #fff; }
        .summary .label { font-size: 11px; color: var(--muted); text-transform: uppercase; letter-spacing: .04em; }
        .summary .value { font-size: 16px; margin-top: 4px; color: var(--ink); }
        .section-title { font-size: 13px; color: var(--muted); margin: 6px 0 8px; }
        table { width: 100%; border-collapse: collapse; font-size: 12px; border: 1px solid var(--line); }
        thead th { background: var(--primary); color: #fff; padding: 8px; text-align: left; }
        tbody td { padding: 8px; border-top: 1px solid var(--line); }
        tbody tr:nth-child(even) { background: var(--row); }
        .report-footer { padding: 12px 20px; font-size: 11px; color: var(--muted); border-top: 1px solid var(--line); text-align: right; }
        @media print { .screen-toolbar { display: none; } body { background: #fff; } .report { border: 0; } }
    </style>
</head>
<body>
    <div class="screen-toolbar">
        <button onclick="triggerDownload()">Download PDF</button>
    </div>
    <div class="report">
        <div class="report-header">
            <h1 class="report-title">' . $title . '</h1>
            <p class="report-meta">Period: ' . $date_range . ' • Generated: ' . $generated_on . '</p>
        </div>
        <div class="report-body">
            ' . $html_content . '
        </div>
        <div class="report-footer">Generated by MTICS Admin System</div>
    </div>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <script>
        (function() {
            function buildOptions() {
                return {
                    margin: 12,
                    filename: "' . addslashes(ucfirst($report_type) . '_Report_' . date('Y-m-d')) . '.pdf",
                    image: { type: "jpeg", quality: 0.98 },
                    html2canvas: { scale: 2, useCORS: true, backgroundColor: null },
                    jsPDF: { unit: "mm", format: "a4", orientation: "portrait" }
                };
            }
            window.triggerDownload = function() {
                try {
                    const el = document.querySelector(".report");
                    if (!el) return;
                    const opt = buildOptions();
                    html2pdf().set(opt).from(el).save();
                } catch (e) {
                    try { window.print(); } catch(_) {}
                }
            };
            window.addEventListener("load", function() {
                setTimeout(triggerDownload, 300);
            });
        })();
    </script>
</body>
</html>';
    
    header('Content-Type: text/html; charset=UTF-8');
    header('Content-Disposition: inline; filename="report_' . $report_type . '_' . date('Y-m-d') . '.html"');
    header('Cache-Control: no-cache, must-revalidate');
    header('Expires: 0');
    
    echo $pdf_html;
    exit;
}

function generate_html_report($report_type, $data, $date_from, $date_to, $pdf_mode = false) {
    $title = ucfirst($report_type) . ' Report';
    $period = date('M j, Y', strtotime($date_from)) . ' - ' . date('M j, Y', strtotime($date_to));
    $generated = date('M j, Y g:i A');
    $summary_blocks = [];
    $summary_blocks[] = ['label' => 'Total Records', 'value' => count($data)];
    
    switch ($report_type) {
        case 'users':
            $active_users = array_filter($data, fn($u) => $u['is_active']);
            $total_tokens = array_sum(array_column($data, 'eco_tokens'));
            $summary_blocks[] = ['label' => 'Active Users', 'value' => count($active_users)];
            $summary_blocks[] = ['label' => 'Total Tokens', 'value' => number_format($total_tokens ?? 0, 2)];
            break;
            
        case 'recycling':
            $total_bottles = count($data);
            $total_tokens = array_sum(array_column($data, 'tokens_earned'));
            $summary_blocks[] = ['label' => 'Total Bottles', 'value' => $total_bottles];
            $summary_blocks[] = ['label' => 'Tokens Earned', 'value' => number_format($total_tokens ?? 0, 2)];
            break;
            
        case 'redemptions':
            $total_spent = array_sum(array_column($data, 'tokens_spent'));
            $status_counts = array_count_values(array_column($data, 'status'));
            $summary_blocks[] = ['label' => 'Tokens Spent', 'value' => number_format($total_spent ?? 0, 2)];
            $summary_blocks[] = ['label' => 'Pending', 'value' => ($status_counts['pending'] ?? 0)];
            $summary_blocks[] = ['label' => 'Approved', 'value' => ($status_counts['approved'] ?? 0)];
            break;
    }

    $table_head = '';
    $rows_html = '';
    
    switch ($report_type) {
        case 'users':
            $table_head = '
                <thead><tr>
                    <th>Name</th>
                    <th>Student ID</th>
                    <th>Email</th>
                    <th>Token Balance</th>
                    <th>Status</th>
                    <th>Activities</th>
                    <th>Tokens Earned</th>
                    <th>Redemptions</th>
                </tr></thead><tbody>';
            foreach ($data as $row) {
                $rows_html .= '<tr>
                    <td>' . htmlspecialchars($row['full_name'] ?? '') . '</td>
                    <td>' . htmlspecialchars($row['student_id'] ?? '') . '</td>
                    <td>' . htmlspecialchars($row['email'] ?? '') . '</td>
                    <td>' . number_format($row['eco_tokens'] ?? 0, 2) . '</td>
                    <td>' . ($row['is_active'] ? 'Active' : 'Inactive') . '</td>
                    <td>' . ($row['recycling_activities'] ?? 0) . '</td>
                    <td>' . number_format($row['total_tokens_earned'] ?? 0, 2) . '</td>
                    <td>' . ($row['redemptions_made'] ?? 0) . '</td>
                </tr>';
            }
            break;
            
        case 'top_recyclers':
            $table_head = '
                <thead><tr>
                    <th>Rank</th>
                    <th>Student</th>
                    <th>Student ID</th>
                    <th>Bottles Recycled</th>
                    <th>Tokens Earned</th>
                </tr></thead><tbody>';
            $rank = 1;
            foreach ($data as $row) {
                $rows_html .= '<tr>
                    <td>' . $rank++ . '</td>
                    <td>' . htmlspecialchars($row['full_name'] ?? '') . '</td>
                    <td>' . htmlspecialchars($row['student_id'] ?? '') . '</td>
                    <td>' . (int)($row['bottles_recycled'] ?? 0) . '</td>
                    <td>' . number_format($row['total_tokens'] ?? 0, 2) . '</td>
                </tr>';
            }
            break;
            
        case 'recycling':
            $table_head = '
                <thead><tr>
                    <th>Date</th>
                    <th>Student</th>
                    <th>Student ID</th>
                    <th>Bottle Type</th>
                    <th>Sensor ID</th>
                    <th>Tokens Earned</th>
                </tr></thead><tbody>';
            foreach ($data as $row) {
                $rows_html .= '<tr>
                    <td>' . date('M j, Y g:i A', strtotime($row['created_at'])) . '</td>
                    <td>' . htmlspecialchars($row['full_name'] ?? '') . '</td>
                    <td>' . htmlspecialchars($row['student_id'] ?? '') . '</td>
                    <td>' . ucfirst(htmlspecialchars($row['bottle_type'] ?? '')) . '</td>
                    <td>' . htmlspecialchars($row['sensor_id'] ?? '') . '</td>
                    <td>' . number_format($row['tokens_earned'] ?? 0, 2) . '</td>
                </tr>';
            }
            break;
            
        case 'redemptions':
            $table_head = '
                <thead><tr>
                    <th>Date</th>
                    <th>Student</th>
                    <th>Student ID</th>
                    <th>Reward</th>
                    <th>Tokens Spent</th>
                    <th>Status</th>
                    <th>Redemption Code</th>
                </tr></thead><tbody>';
            foreach ($data as $row) {
                $rows_html .= '<tr>
                    <td>' . date('M j, Y', strtotime($row['created_at'])) . '</td>
                    <td>' . htmlspecialchars($row['full_name'] ?? '') . '</td>
                    <td>' . htmlspecialchars($row['student_id'] ?? '') . '</td>
                    <td>' . htmlspecialchars($row['reward_name'] ?? '') . '</td>
                    <td>' . number_format($row['tokens_spent'] ?? 0, 2) . '</td>
                    <td>' . ucfirst($row['status'] ?? '') . '</td>
                    <td>' . htmlspecialchars($row['redemption_code'] ?? '') . '</td>
                </tr>';
            }
            break;
            
        case 'events':
            $table_head = '
                <thead><tr>
                    <th>Event Name</th>
                    <th>Date</th>
                    <th>Location</th>
                    <th>Status</th>
                    <th>Total Attendance</th>
                    <th>Approved Attendance</th>
                </tr></thead><tbody>';
            foreach ($data as $row) {
                $rows_html .= '<tr>
                    <td>' . htmlspecialchars($row['title'] ?? '') . '</td>
                    <td>' . date('M j, Y g:i A', strtotime($row['event_date'])) . '</td>
                    <td>' . htmlspecialchars($row['location'] ?? 'N/A') . '</td>
                    <td>' . ($row['is_published'] ? 'Published' : 'Draft') . '</td>
                    <td>' . ($row['total_attendance'] ?? 0) . '</td>
                    <td>' . ($row['approved_attendance'] ?? 0) . '</td>
                </tr>';
            }
            break;
            
        case 'transactions':
            $table_head = '
                <thead><tr>
                    <th>Date</th>
                    <th>Student</th>
                    <th>Student ID</th>
                    <th>Type</th>
                    <th>Description</th>
                    <th>Amount</th>
                </tr></thead><tbody>';
            foreach ($data as $row) {
                $amount_sign = $row['transaction_type'] === 'earned' ? '+' : '-';
                $rows_html .= '<tr>
                    <td>' . date('M j, Y g:i A', strtotime($row['created_at'])) . '</td>
                    <td>' . htmlspecialchars($row['full_name'] ?? '') . '</td>
                    <td>' . htmlspecialchars($row['student_id'] ?? '') . '</td>
                    <td>' . ucfirst($row['transaction_type'] ?? '') . '</td>
                    <td>' . htmlspecialchars($row['description'] ?? '') . '</td>
                    <td>' . $amount_sign . number_format($row['amount'] ?? 0, 2) . '</td>
                </tr>';
            }
            break;
    }

    $summary_html = '<div class="summary">';
    foreach ($summary_blocks as $blk) {
        $summary_html .= '<div class="card"><div class="label">' . htmlspecialchars($blk['label']) . '</div><div class="value">' . htmlspecialchars((string)$blk['value']) . '</div></div>';
    }
    $summary_html .= '</div>';

    $table_html = '<div class="section-title">Report Data</div><table class="data-table">' . $table_head . $rows_html . '</tbody></table>';

    if ($pdf_mode) {
        return $summary_html . $table_html;
    }

    $css = '<style>
        :root { --primary:#1976d2; --primary-600:#1565c0; --ink:#1f2937; --muted:#6b7280; --line:#e5effa; --row:#f7fbff; }
        body { font-family: Arial, sans-serif; color: var(--ink); margin: 20px; }
        .report { border: 1px solid var(--line); border-radius: 10px; overflow: hidden; }
        .report-header { background: var(--primary); color: #fff; padding: 16px 20px; }
        .report-title { margin: 0; font-size: 20px; }
        .report-meta { margin: 6px 0 0 0; font-size: 12px; opacity: .95; }
        .report-body { padding: 16px 20px; }
        .summary { display: grid; grid-template-columns: repeat(3, minmax(0,1fr)); gap: 12px; margin-bottom: 16px; }
        .summary .card { border: 1px solid var(--line); border-radius: 8px; padding: 10px 12px; background: #fff; }
        .summary .label { font-size: 11px; color: var(--muted); text-transform: uppercase; letter-spacing: .04em; }
        .summary .value { font-size: 16px; margin-top: 4px; color: var(--ink); }
        .section-title { font-size: 13px; color: var(--muted); margin: 6px 0 8px; }
        table { width: 100%; border-collapse: collapse; font-size: 12px; border: 1px solid var(--line); }
        thead th { background: var(--primary); color: #fff; padding: 8px; text-align: left; }
        tbody td { padding: 8px; border-top: 1px solid var(--line); }
        tbody tr:nth-child(even) { background: var(--row); }
        .report-footer { padding: 12px 20px; font-size: 11px; color: var(--muted); border-top: 1px solid var(--line); text-align: right; }
    </style>';

    $html = '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>' . htmlspecialchars($title) . '</title>' . $css . '</head><body>
        <div class="report">
            <div class="report-header">
                <h1 class="report-title">' . htmlspecialchars($title) . '</h1>
                <p class="report-meta">Period: ' . htmlspecialchars($period) . ' • Generated: ' . htmlspecialchars($generated) . '</p>
            </div>
            <div class="report-body">' . $summary_html . $table_html . '</div>
            <div class="report-footer">Generated by MTICS Admin System</div>
        </div>
    </body></html>';

    return $html;
}

include '../includes/admin_header.php';
?>

<div class="admin-content">
    <div class="admin-container">
        <div class="admin-page-header">
            <h1 class="admin-page-title">System Reports</h1>
            <p class="admin-page-subtitle">Generate comprehensive reports of system data</p>
            <div class="admin-breadcrumb">
                <a href="<?php echo SITE_URL; ?>/admin/index.php">Home</a> / Reports
            </div>
        </div>
        
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?>" style="margin-bottom: var(--spacing-md);">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['generated_report'])): ?>
            <div id="reportContent" style="display: none;">
                <?php echo $_SESSION['generated_report']['html'] ?? ''; ?>
            </div>
        <?php endif; ?>
            <div class="admin-section">
                <h2 class="admin-section-title">Generate Report</h2>
                
                <div class="card">
                    <div class="card-body">
                        <form method="POST" class="admin-form" id="reportForm">
                            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                            <input type="hidden" name="generate_report" value="1">
                            
                            <div class="grid grid-2">
                                <div class="admin-form-group">
                                    <label class="admin-form-label">Report Type *</label>
                                    <select name="report_type" class="admin-form-select" required>
                                        <option value="">Select report type...</option>
                                        <option value="users">Users Report</option>
                                        <option value="recycling">Recycling Activities Report</option>
                                        <option value="redemptions">Redemptions Report</option>
                                        <option value="events">Events Report</option>
                                        <option value="transactions">Transactions Report</option>
                                        <option value="top_recyclers">Top Recyclers</option>
                                    </select>
                                </div>
                                
                                <div class="admin-form-group">
                                    <label class="admin-form-label">Format *</label>
                                    <select name="format" class="admin-form-select" required>
                                        <option value="html">PDF File</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="grid grid-2">
                                <div class="admin-form-group">
                                    <label class="admin-form-label">From Date *</label>
                                    <input type="date" name="date_from" class="admin-form-input" required
                                           value="<?php echo date('Y-m-01'); // First day of current month ?>">
                                </div>
                                
                                <div class="admin-form-group">
                                    <label class="admin-form-label">To Date *</label>
                                    <input type="date" name="date_to" class="admin-form-input" required
                                           value="<?php echo date('Y-m-d'); // Today ?>">
                                </div>
                            </div>
                            
                            <div style="display: flex; gap: 1rem; margin-top: 2rem;">
                                <button type="button" class="admin-btn admin-btn-primary" onclick="generateReport()">
                                    <i class="fa-solid fa-file-alt"></i> Generate Report
                                </button>
                                <button type="button" class="admin-btn admin-btn-success" id="downloadPdfBtn" onclick="downloadPDF()" disabled>
                                    <i class="fa-solid fa-download"></i> Download PDF
                                </button>
                                <a href="index.php" class="admin-btn admin-btn-secondary">
                                    ← Back to Dashboard
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
    </div>
</div>

<?php include '../includes/admin_footer.php'; ?>

<script>
function generateReport() {
    const form = document.getElementById('reportForm');
    const formData = new FormData(form);
    
    // Show loading state
    const generateBtn = document.querySelector('button[onclick="generateReport()"]');
    const originalText = generateBtn.innerHTML;
    generateBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Generating...';
    generateBtn.disabled = true;
    
    fetch('reports.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.text())
    .then(html => {
        // Parse the response to check for success message
        if (html.includes('alert-success')) {
            // Enable download button
            const downloadBtn = document.getElementById("downloadPdfBtn");
            if (downloadBtn) {
                downloadBtn.disabled = false;
            }
            
            // Show success message
            showMessage('Report generated successfully! Click "Download PDF" to get your file.', 'success');
        } else {
            // Show error message
            showMessage('Error generating report. Please try again.', 'error');
        }
        
        // Reset button
        generateBtn.innerHTML = originalText;
        generateBtn.disabled = false;
    })
    .catch(error => {
        console.error('Error:', error);
        showMessage('Error generating report. Please try again.', 'error');
        generateBtn.innerHTML = originalText;
        generateBtn.disabled = false;
    });
}

function showMessage(message, type) {
    // Remove existing alerts
    const existingAlerts = document.querySelectorAll('.alert');
    existingAlerts.forEach(alert => alert.remove());
    
    // Create new alert
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type}`;
    alertDiv.style.marginBottom = 'var(--spacing-md)';
    alertDiv.textContent = message;
    
    // Insert after the page header
    const pageHeader = document.querySelector('.admin-page-header');
    if (pageHeader) {
        pageHeader.parentNode.insertBefore(alertDiv, pageHeader.nextSibling);
    }
    
    // Auto-remove after 5 seconds
    setTimeout(() => {
        alertDiv.remove();
    }, 5000);
}

function downloadPDF() {
    const formRef = document.getElementById('reportForm');
    const reportType = formRef.querySelector('[name="report_type"]')?.value || '';
    const dateFrom = formRef.querySelector('[name="date_from"]')?.value || '';
    const dateTo = formRef.querySelector('[name="date_to"]')?.value || '';
    const csrf = formRef.querySelector('[name="csrf_token"]')?.value || '';
    
    if (!reportType || !dateFrom || !dateTo) {
        showMessage('Please generate a report first.', 'error');
        return;
    }
    
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = 'reports.php';
    form.style.display = 'none';
    form.target = '_blank';
    
    const csrfToken = document.createElement('input');
    csrfToken.type = 'hidden';
    csrfToken.name = 'csrf_token';
    csrfToken.value = csrf;
    form.appendChild(csrfToken);
    
    const downloadPdf = document.createElement('input');
    downloadPdf.type = 'hidden';
    downloadPdf.name = 'download_pdf';
    downloadPdf.value = '1';
    form.appendChild(downloadPdf);
    
    const reportTypeField = document.createElement('input');
    reportTypeField.type = 'hidden';
    reportTypeField.name = 'report_type';
    reportTypeField.value = reportType;
    form.appendChild(reportTypeField);
    
    const dateFromField = document.createElement('input');
    dateFromField.type = 'hidden';
    dateFromField.name = 'date_from';
    dateFromField.value = dateFrom;
    form.appendChild(dateFromField);
    
    const dateToField = document.createElement('input');
    dateToField.type = 'hidden';
    dateToField.name = 'date_to';
    dateToField.value = dateTo;
    form.appendChild(dateToField);
    
    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);
    
    showMessage('Preparing PDF...', 'success');
}

document.addEventListener('DOMContentLoaded', function() {
    const formRef = document.getElementById('reportForm');
    const downloadBtn = document.getElementById('downloadPdfBtn');
    if (formRef && downloadBtn) {
        formRef.addEventListener('change', function() {
            downloadBtn.disabled = true;
        });
    }
});
</script>
