<?php
require_once '../config/config.php';
require_admin();

$page_title = 'System Reports';
$message = '';
$message_type = '';

$db = Database::getInstance()->getConnection();

// Include the PDF generator
require_once '../includes/pdf_generator.php';

// Fetch all attendance-enabled events for the event filter dropdown
$all_events_list = [];
try {
    $evStmt = $db->query("SELECT id, title, event_date FROM events WHERE attendance_enabled = 1 ORDER BY event_date DESC");
    $all_events_list = $evStmt->fetchAll();
} catch (Exception $e) {
    $all_events_list = [];
}

// ─────────────────────────────────────────────
// Helper: build event attendance report data
// ─────────────────────────────────────────────
function build_event_attendance_data($db, $date_from, $date_to, $filter_event_id = 0) {

    // 1. Get relevant events
    if ($filter_event_id > 0) {
        $evStmt = $db->prepare("
            SELECT id, title, event_date, location
            FROM events
            WHERE id = ? AND attendance_enabled = 1
        ");
        $evStmt->execute([$filter_event_id]);
    } else {
        $evStmt = $db->prepare("
            SELECT id, title, event_date, location
            FROM events
            WHERE attendance_enabled = 1
              AND event_date BETWEEN ? AND ?
            ORDER BY event_date ASC
        ");
        $evStmt->execute([$date_from, $date_to]);
    }
    $events = $evStmt->fetchAll();

    if (empty($events)) {
        return [
            'events'        => [],
            'users'         => [],
            'matrix'        => [],
            'warning_list'  => [],
            'sanction_list' => [],
            'perfect_list'  => [],
        ];
    }

    $event_ids = array_column($events, 'id');

    // 2. Get all active users
    $userStmt = $db->query("SELECT id, full_name, student_id, email FROM users WHERE is_active = 1 ORDER BY full_name ASC");
    $users = $userStmt->fetchAll();

    // 3. Get approved attendance records for those events
    $placeholders = implode(',', array_fill(0, count($event_ids), '?'));
    $attStmt = $db->prepare("
        SELECT event_id, user_id
        FROM event_attendance
        WHERE event_id IN ($placeholders)
          AND attendance_status = 'approved'
    ");
    $attStmt->execute($event_ids);
    $att_rows = $attStmt->fetchAll();

    // Build a lookup: [event_id][user_id] = true
    $attended = [];
    foreach ($att_rows as $row) {
        $attended[$row['event_id']][$row['user_id']] = true;
    }

    // 4. Build the presence/absence matrix and count absences
    $matrix        = []; // [user_id] => [event_id => bool]
    $absence_count = []; // [user_id] => int

    foreach ($users as $user) {
        $uid = $user['id'];
        $absence_count[$uid] = 0;
        foreach ($events as $event) {
            $eid = $event['id'];
            $present = isset($attended[$eid][$uid]);
            $matrix[$uid][$eid] = $present;
            if (!$present) {
                $absence_count[$uid]++;
            }
        }
    }

    // 5. Categorise
    $warning_list  = []; // exactly 1 absence
    $sanction_list = []; // 2+ absences
    $perfect_list  = []; // 0 absences

    foreach ($users as $user) {
        $uid  = $user['id'];
        $cnt  = $absence_count[$uid];
        $user['absence_count'] = $cnt;

        // List which events they missed
        $missed = [];
        foreach ($events as $event) {
            if (!$matrix[$uid][$event['id']]) {
                $missed[] = $event['title'];
            }
        }
        $user['missed_events'] = $missed;

        if ($cnt === 0) {
            $perfect_list[] = $user;
        } elseif ($cnt === 1) {
            $warning_list[] = $user;
        } else {
            $sanction_list[] = $user;
        }
    }

    return [
        'events'        => $events,
        'users'         => $users,
        'matrix'        => $matrix,
        'warning_list'  => $warning_list,
        'sanction_list' => $sanction_list,
        'perfect_list'  => $perfect_list,
    ];
}

// ─────────────────────────────────────────────
// Helper: generate HTML for event attendance
// ─────────────────────────────────────────────
function generate_event_attendance_html($data, $date_from, $date_to) {
    $events        = $data['events'];
    $matrix        = $data['matrix'];
    $warning_list  = $data['warning_list'];
    $sanction_list = $data['sanction_list'];
    $perfect_list  = $data['perfect_list'];

    if (empty($events)) {
        return '<p style="color:#6b7280;">No attendance-enabled events found for the selected period.</p>';
    }

    $total_events  = count($events);
    $total_users   = count($data['users']);
    $warn_count    = count($warning_list);
    $sanc_count    = count($sanction_list);
    $perf_count    = count($perfect_list);

    $html = '';

    // ── Summary Cards ──────────────────────────────────────
    $html .= '<div class="summary" style="display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px;margin-bottom:20px;">';
    foreach ([
        ['Total Events',   $total_events,  '#1976d2'],
        ['Total Members',  $total_users,   '#1976d2'],
        ['⚠ Warning List', $warn_count,    '#f59e0b'],
        ['🚫 Sanction List',$sanc_count,   '#dc3545'],
    ] as [$label, $val, $color]) {
        $html .= '<div class="card" style="border:1px solid #e5effa;border-radius:8px;padding:10px 12px;background:#fff;">
            <div class="label" style="font-size:11px;color:#6b7280;text-transform:uppercase;letter-spacing:.04em;">' . htmlspecialchars($label) . '</div>
            <div class="value" style="font-size:20px;margin-top:4px;color:' . $color . ';font-weight:bold;">' . $val . '</div>
        </div>';
    }
    $html .= '</div>';

    // ── Per-Event Attendance Matrix ─────────────────────────
    $html .= '<div class="section-title" style="font-size:13px;color:#6b7280;margin:6px 0 8px;">Attendance Matrix — All Events</div>';
    $html .= '<div style="overflow-x:auto;margin-bottom:24px;">';
    $html .= '<table style="width:100%;border-collapse:collapse;font-size:11px;border:1px solid #e5effa;">';
    $html .= '<thead><tr style="background:#1976d2;color:#fff;">';
    $html .= '<th style="padding:8px;text-align:left;white-space:nowrap;">Student</th>';
    $html .= '<th style="padding:8px;text-align:left;white-space:nowrap;">Student ID</th>';
    foreach ($events as $event) {
        $html .= '<th style="padding:8px;text-align:center;white-space:nowrap;">' . htmlspecialchars($event['title']) . '<br><span style="font-weight:normal;font-size:10px;">' . date('M j, Y', strtotime($event['event_date'])) . '</span></th>';
    }
    $html .= '<th style="padding:8px;text-align:center;">Absences</th>';
    $html .= '<th style="padding:8px;text-align:center;">Status</th>';
    $html .= '</tr></thead><tbody>';

    $all_users = array_merge($sanction_list, $warning_list, $perfect_list);
    usort($all_users, fn($a, $b) => $b['absence_count'] - $a['absence_count']);

    foreach ($all_users as $i => $user) {
        $uid  = $user['id'];
        $cnt  = $user['absence_count'];
        $bg   = $i % 2 === 0 ? '#fff' : '#f7fbff';

        if ($cnt >= 2) {
            $status_badge = '<span style="background:#dc3545;color:#fff;padding:2px 7px;border-radius:4px;font-size:10px;font-weight:bold;">SANCTION</span>';
        } elseif ($cnt === 1) {
            $status_badge = '<span style="background:#f59e0b;color:#fff;padding:2px 7px;border-radius:4px;font-size:10px;font-weight:bold;">WARNING</span>';
        } else {
            $status_badge = '<span style="background:#16a34a;color:#fff;padding:2px 7px;border-radius:4px;font-size:10px;font-weight:bold;">GOOD</span>';
        }

        $html .= '<tr style="background:' . $bg . ';">';
        $html .= '<td style="padding:7px 8px;border-top:1px solid #e5effa;">' . htmlspecialchars($user['full_name']) . '</td>';
        $html .= '<td style="padding:7px 8px;border-top:1px solid #e5effa;">' . htmlspecialchars($user['student_id']) . '</td>';

        foreach ($events as $event) {
            $eid     = $event['id'];
            $present = isset($matrix[$uid][$eid]) ? $matrix[$uid][$eid] : false;
            $icon    = $present
                ? '<span style="color:#16a34a;font-size:14px;">✓</span>'
                : '<span style="color:#dc3545;font-size:14px;">✗</span>';
            $html .= '<td style="padding:7px 8px;border-top:1px solid #e5effa;text-align:center;">' . $icon . '</td>';
        }

        $absence_color = $cnt >= 2 ? '#dc3545' : ($cnt === 1 ? '#f59e0b' : '#16a34a');
        $html .= '<td style="padding:7px 8px;border-top:1px solid #e5effa;text-align:center;font-weight:bold;color:' . $absence_color . ';">' . $cnt . '</td>';
        $html .= '<td style="padding:7px 8px;border-top:1px solid #e5effa;text-align:center;">' . $status_badge . '</td>';
        $html .= '</tr>';
    }

    $html .= '</tbody></table></div>';

    // ── Sanction List ───────────────────────────────────────
    $html .= '<div class="section-title" style="font-size:14px;font-weight:bold;color:#dc3545;margin:20px 0 8px;padding:8px 12px;background:#fff5f5;border-left:4px solid #dc3545;border-radius:0 4px 4px 0;">
        🚫 Sanction List — 2 or More Absences (' . $sanc_count . ' student' . ($sanc_count !== 1 ? 's' : '') . ')
    </div>';

    if (empty($sanction_list)) {
        $html .= '<p style="color:#6b7280;font-style:italic;margin-bottom:16px;">No students require sanctions for this period.</p>';
    } else {
        $html .= '<table style="width:100%;border-collapse:collapse;font-size:12px;border:1px solid #fecaca;margin-bottom:20px;">';
        $html .= '<thead><tr style="background:#dc3545;color:#fff;">
            <th style="padding:8px;text-align:left;">#</th>
            <th style="padding:8px;text-align:left;">Student Name</th>
            <th style="padding:8px;text-align:left;">Student ID</th>
            <th style="padding:8px;text-align:center;">Total Absences</th>
            <th style="padding:8px;text-align:left;">Missed Events</th>
        </tr></thead><tbody>';
        foreach ($sanction_list as $i => $user) {
            $bg = $i % 2 === 0 ? '#fff5f5' : '#fff';
            $html .= '<tr style="background:' . $bg . ';">
                <td style="padding:8px;border-top:1px solid #fecaca;">' . ($i + 1) . '</td>
                <td style="padding:8px;border-top:1px solid #fecaca;font-weight:bold;">' . htmlspecialchars($user['full_name']) . '</td>
                <td style="padding:8px;border-top:1px solid #fecaca;">' . htmlspecialchars($user['student_id']) . '</td>
                <td style="padding:8px;border-top:1px solid #fecaca;text-align:center;font-weight:bold;color:#dc3545;">' . $user['absence_count'] . '</td>
                <td style="padding:8px;border-top:1px solid #fecaca;font-size:11px;color:#6b7280;">' . htmlspecialchars(implode(', ', $user['missed_events'])) . '</td>
            </tr>';
        }
        $html .= '</tbody></table>';
    }

    // ── Warning List ────────────────────────────────────────
    $html .= '<div class="section-title" style="font-size:14px;font-weight:bold;color:#92400e;margin:20px 0 8px;padding:8px 12px;background:#fffbeb;border-left:4px solid #f59e0b;border-radius:0 4px 4px 0;">
        ⚠ Warning List — 1 Absence (' . $warn_count . ' student' . ($warn_count !== 1 ? 's' : '') . ')
    </div>';

    if (empty($warning_list)) {
        $html .= '<p style="color:#6b7280;font-style:italic;margin-bottom:16px;">No students on the warning list for this period.</p>';
    } else {
        $html .= '<table style="width:100%;border-collapse:collapse;font-size:12px;border:1px solid #fde68a;margin-bottom:20px;">';
        $html .= '<thead><tr style="background:#f59e0b;color:#fff;">
            <th style="padding:8px;text-align:left;">#</th>
            <th style="padding:8px;text-align:left;">Student Name</th>
            <th style="padding:8px;text-align:left;">Student ID</th>
            <th style="padding:8px;text-align:center;">Total Absences</th>
            <th style="padding:8px;text-align:left;">Missed Event</th>
        </tr></thead><tbody>';
        foreach ($warning_list as $i => $user) {
            $bg = $i % 2 === 0 ? '#fffbeb' : '#fff';
            $html .= '<tr style="background:' . $bg . ';">
                <td style="padding:8px;border-top:1px solid #fde68a;">' . ($i + 1) . '</td>
                <td style="padding:8px;border-top:1px solid #fde68a;font-weight:bold;">' . htmlspecialchars($user['full_name']) . '</td>
                <td style="padding:8px;border-top:1px solid #fde68a;">' . htmlspecialchars($user['student_id']) . '</td>
                <td style="padding:8px;border-top:1px solid #fde68a;text-align:center;font-weight:bold;color:#92400e;">1</td>
                <td style="padding:8px;border-top:1px solid #fde68a;font-size:11px;color:#6b7280;">' . htmlspecialchars(implode(', ', $user['missed_events'])) . '</td>
            </tr>';
        }
        $html .= '</tbody></table>';
    }

    // ── Perfect Attendance ──────────────────────────────────
    $html .= '<div class="section-title" style="font-size:14px;font-weight:bold;color:#166534;margin:20px 0 8px;padding:8px 12px;background:#f0fdf4;border-left:4px solid #16a34a;border-radius:0 4px 4px 0;">
        ✅ Perfect Attendance — Attended All Events (' . $perf_count . ' student' . ($perf_count !== 1 ? 's' : '') . ')
    </div>';

    if (empty($perfect_list)) {
        $html .= '<p style="color:#6b7280;font-style:italic;margin-bottom:16px;">No students with perfect attendance for this period.</p>';
    } else {
        $html .= '<table style="width:100%;border-collapse:collapse;font-size:12px;border:1px solid #bbf7d0;margin-bottom:20px;">';
        $html .= '<thead><tr style="background:#16a34a;color:#fff;">
            <th style="padding:8px;text-align:left;">#</th>
            <th style="padding:8px;text-align:left;">Student Name</th>
            <th style="padding:8px;text-align:left;">Student ID</th>
            <th style="padding:8px;text-align:center;">Events Attended</th>
        </tr></thead><tbody>';
        foreach ($perfect_list as $i => $user) {
            $bg = $i % 2 === 0 ? '#f0fdf4' : '#fff';
            $html .= '<tr style="background:' . $bg . ';">
                <td style="padding:8px;border-top:1px solid #bbf7d0;">' . ($i + 1) . '</td>
                <td style="padding:8px;border-top:1px solid #bbf7d0;font-weight:bold;">' . htmlspecialchars($user['full_name']) . '</td>
                <td style="padding:8px;border-top:1px solid #bbf7d0;">' . htmlspecialchars($user['student_id']) . '</td>
                <td style="padding:8px;border-top:1px solid #bbf7d0;text-align:center;font-weight:bold;color:#16a34a;">' . $total_events . ' / ' . $total_events . '</td>
            </tr>';
        }
        $html .= '</tbody></table>';
    }

    return $html;
}

// ─────────────────────────────────────────────
// Handle PDF download
// ─────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['download_pdf'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        die('Invalid security token.');
    }

    $report_type     = sanitize_input($_POST['report_type'] ?? '');
    $date_from       = $_POST['date_from'] ?? '';
    $date_to         = $_POST['date_to'] ?? '';
    $filter_event_id = (int)($_POST['filter_event_id'] ?? 0);

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

            case 'event_attendance':
                $report_data = build_event_attendance_data($db, $date_from, $date_to, $filter_event_id);
                break;

            default:
                throw new Exception('Invalid report type.');
        }

        generate_pdf_report($report_type, $report_data, $date_from, $date_to);

    } catch (Exception $e) {
        die('Error generating PDF: ' . $e->getMessage());
    }
}

// ─────────────────────────────────────────────
// Handle report generation (AJAX)
// ─────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate_report'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        echo '<div class="alert alert-error">Invalid security token.</div>';
        exit;
    }

    $report_type     = sanitize_input($_POST['report_type'] ?? '');
    $date_from       = $_POST['date_from'] ?? '';
    $date_to         = $_POST['date_to'] ?? '';
    $filter_event_id = (int)($_POST['filter_event_id'] ?? 0);

    if (empty($report_type) || empty($date_from) || empty($date_to)) {
        echo '<div class="alert alert-error">Please fill in all required fields.</div>';
        exit;
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

            case 'event_attendance':
                $report_data = build_event_attendance_data($db, $date_from, $date_to, $filter_event_id);
                break;

            default:
                throw new Exception('Invalid report type.');
        }

        $html_report = generate_html_report($report_type, $report_data, $date_from, $date_to);

        $_SESSION['generated_report'] = [
            'type'            => $report_type,
            'date_from'       => $date_from,
            'date_to'         => $date_to,
            'filter_event_id' => $filter_event_id,
            'data'            => $report_data,
            'html'            => $html_report,
        ];

        echo '<div class="alert alert-success">Report generated successfully! Click "Download PDF" to open a printable report.</div>';
        echo '<script>document.getElementById("downloadPdfBtn").disabled = false;</script>';
        exit;

    } catch (Exception $e) {
        echo '<div class="alert alert-error">Error generating report: ' . htmlspecialchars($e->getMessage()) . '</div>';
        exit;
    }
}

// ─────────────────────────────────────────────
// PDF renderer
// ─────────────────────────────────────────────
function generate_pdf_report($report_type, $data, $date_from, $date_to) {
    $label_map = [
        'users'            => 'Users Report',
        'recycling'        => 'Recycling Activities Report',
        'redemptions'      => 'Redemptions Report',
        'events'           => 'Events Report',
        'transactions'     => 'Transactions Report',
        'top_recyclers'    => 'Top Recyclers Report',
        'event_attendance' => 'Event Attendance & Sanctions Report',
    ];
    $title       = $label_map[$report_type] ?? (ucfirst($report_type) . ' Report');
    $date_range  = date('F j, Y', strtotime($date_from)) . ' – ' . date('F j, Y', strtotime($date_to));
    $generated_on = date('F j, Y g:i A');

    $html_content = generate_html_report($report_type, $data, $date_from, $date_to, true);

    $orientation = ($report_type === 'event_attendance') ? 'landscape' : 'portrait';

    $pdf_html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>' . htmlspecialchars($title) . '</title>
    <style>
        @page { size: A4 ' . $orientation . '; margin: 15mm; }
        :root { --primary:#1976d2; --primary-600:#1565c0; --ink:#1f2937; --muted:#6b7280; --line:#e5effa; --row:#f7fbff; }
        * { box-sizing: border-box; }
        body { font-family: Arial, sans-serif; color: var(--ink); }
        .screen-toolbar { display: flex; gap: 8px; justify-content: flex-end; margin-bottom: 12px; }
        .screen-toolbar button { background: var(--primary); color: #fff; border: 0; padding: 8px 14px; border-radius: 6px; cursor: pointer; font-size: 13px; }
        .screen-toolbar button:hover { background: var(--primary-600); }
        .report { border: 1px solid var(--line); border-radius: 10px; overflow: hidden; }
        .report-header { background: var(--primary); color: #fff; padding: 16px 20px; }
        .report-title { margin: 0; font-size: 18px; }
        .report-meta { margin: 5px 0 0; font-size: 12px; opacity: .9; }
        .report-body { padding: 16px 20px; }
        .report-footer { padding: 10px 20px; font-size: 11px; color: var(--muted); border-top: 1px solid var(--line); text-align: right; }
        table { width: 100%; border-collapse: collapse; font-size: 12px; border: 1px solid var(--line); }
        thead th { background: var(--primary); color: #fff; padding: 8px; text-align: left; }
        tbody td { padding: 8px; border-top: 1px solid var(--line); }
        tbody tr:nth-child(even) { background: var(--row); }
        @media print { .screen-toolbar { display: none; } body { background: #fff; } .report { border: 0; } }
    </style>
</head>
<body>
    <div class="screen-toolbar">
        <button onclick="triggerDownload()">⬇ Download PDF</button>
    </div>
    <div class="report">
        <div class="report-header">
            <h1 class="report-title">' . htmlspecialchars($title) . '</h1>
            <p class="report-meta">Period: ' . $date_range . ' &nbsp;•&nbsp; Generated: ' . $generated_on . '</p>
        </div>
        <div class="report-body">' . $html_content . '</div>
        <div class="report-footer">Generated by MTICS Admin System</div>
    </div>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <script>
        (function() {
            function buildOptions() {
                return {
                    margin: 10,
                    filename: "' . addslashes(str_replace(' ', '_', $title) . '_' . date('Y-m-d')) . '.pdf",
                    image: { type: "jpeg", quality: 0.98 },
                    html2canvas: { scale: 2, useCORS: true, backgroundColor: "#ffffff" },
                    jsPDF: { unit: "mm", format: "a4", orientation: "' . $orientation . '" }
                };
            }
            window.triggerDownload = function() {
                try {
                    const el = document.querySelector(".report");
                    if (!el) return;
                    html2pdf().set(buildOptions()).from(el).save();
                } catch(e) { window.print(); }
            };
            window.addEventListener("load", function() { setTimeout(triggerDownload, 400); });
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

// ─────────────────────────────────────────────
// HTML report generator
// ─────────────────────────────────────────────
function generate_html_report($report_type, $data, $date_from, $date_to, $pdf_mode = false) {
    $label_map = [
        'users'            => 'Users Report',
        'recycling'        => 'Recycling Activities Report',
        'redemptions'      => 'Redemptions Report',
        'events'           => 'Events Report',
        'transactions'     => 'Transactions Report',
        'top_recyclers'    => 'Top Recyclers Report',
        'event_attendance' => 'Event Attendance & Sanctions Report',
    ];
    $title   = $label_map[$report_type] ?? (ucfirst($report_type) . ' Report');
    $period  = date('M j, Y', strtotime($date_from)) . ' – ' . date('M j, Y', strtotime($date_to));
    $generated = date('M j, Y g:i A');

    // ── Event Attendance: special renderer ──
    if ($report_type === 'event_attendance') {
        $body = generate_event_attendance_html($data, $date_from, $date_to);

        if ($pdf_mode) return $body;

        $css = '<style>
            :root{--primary:#1976d2;--ink:#1f2937;--muted:#6b7280;--line:#e5effa;--row:#f7fbff;}
            body{font-family:Arial,sans-serif;color:var(--ink);margin:20px;}
            .report{border:1px solid var(--line);border-radius:10px;overflow:hidden;}
            .report-header{background:var(--primary);color:#fff;padding:16px 20px;}
            .report-title{margin:0;font-size:20px;}
            .report-meta{margin:6px 0 0;font-size:12px;opacity:.95;}
            .report-body{padding:16px 20px;}
            .report-footer{padding:12px 20px;font-size:11px;color:var(--muted);border-top:1px solid var(--line);text-align:right;}
        </style>';
        return '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>' . htmlspecialchars($title) . '</title>' . $css . '</head><body>
            <div class="report">
                <div class="report-header"><h1 class="report-title">' . htmlspecialchars($title) . '</h1>
                <p class="report-meta">Period: ' . htmlspecialchars($period) . ' • Generated: ' . htmlspecialchars($generated) . '</p></div>
                <div class="report-body">' . $body . '</div>
                <div class="report-footer">Generated by MTICS Admin System</div>
            </div></body></html>';
    }

    // ── All other report types ───────────────
    $summary_blocks = [['label' => 'Total Records', 'value' => count($data)]];

    switch ($report_type) {
        case 'users':
            $active_users = array_filter($data, fn($u) => $u['is_active']);
            $total_tokens = array_sum(array_column($data, 'eco_tokens'));
            $summary_blocks[] = ['label' => 'Active Users',  'value' => count($active_users)];
            $summary_blocks[] = ['label' => 'Total Tokens',  'value' => number_format($total_tokens ?? 0, 2)];
            break;
        case 'recycling':
            $total_tokens = array_sum(array_column($data, 'tokens_earned'));
            $summary_blocks[] = ['label' => 'Total Bottles', 'value' => count($data)];
            $summary_blocks[] = ['label' => 'Tokens Earned', 'value' => number_format($total_tokens ?? 0, 2)];
            break;
        case 'redemptions':
            $total_spent   = array_sum(array_column($data, 'tokens_spent'));
            $status_counts = array_count_values(array_column($data, 'status'));
            $summary_blocks[] = ['label' => 'Tokens Spent', 'value' => number_format($total_spent ?? 0, 2)];
            $summary_blocks[] = ['label' => 'Pending',      'value' => ($status_counts['pending'] ?? 0)];
            $summary_blocks[] = ['label' => 'Approved',     'value' => ($status_counts['approved'] ?? 0)];
            break;
    }

    $table_head = '';
    $rows_html  = '';

    switch ($report_type) {
        case 'users':
            $table_head = '<thead><tr><th>Name</th><th>Student ID</th><th>Email</th><th>Token Balance</th><th>Status</th><th>Activities</th><th>Tokens Earned</th><th>Redemptions</th></tr></thead><tbody>';
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
            $table_head = '<thead><tr><th>Rank</th><th>Student</th><th>Student ID</th><th>Bottles Recycled</th><th>Tokens Earned</th></tr></thead><tbody>';
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
            $table_head = '<thead><tr><th>Date</th><th>Student</th><th>Student ID</th><th>Bottle Type</th><th>Sensor ID</th><th>Tokens Earned</th></tr></thead><tbody>';
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
            $table_head = '<thead><tr><th>Date</th><th>Student</th><th>Student ID</th><th>Reward</th><th>Tokens Spent</th><th>Status</th><th>Redemption Code</th></tr></thead><tbody>';
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
            $table_head = '<thead><tr><th>Event Name</th><th>Date</th><th>Location</th><th>Status</th><th>Total Attendance</th><th>Approved Attendance</th></tr></thead><tbody>';
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
            $table_head = '<thead><tr><th>Date</th><th>Student</th><th>Student ID</th><th>Type</th><th>Description</th><th>Amount</th></tr></thead><tbody>';
            foreach ($data as $row) {
                $sign = $row['transaction_type'] === 'earned' ? '+' : '-';
                $rows_html .= '<tr>
                    <td>' . date('M j, Y g:i A', strtotime($row['created_at'])) . '</td>
                    <td>' . htmlspecialchars($row['full_name'] ?? '') . '</td>
                    <td>' . htmlspecialchars($row['student_id'] ?? '') . '</td>
                    <td>' . ucfirst($row['transaction_type'] ?? '') . '</td>
                    <td>' . htmlspecialchars($row['description'] ?? '') . '</td>
                    <td>' . $sign . number_format($row['amount'] ?? 0, 2) . '</td>
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

    if ($pdf_mode) return $summary_html . $table_html;

    $css = '<style>
        :root{--primary:#1976d2;--primary-600:#1565c0;--ink:#1f2937;--muted:#6b7280;--line:#e5effa;--row:#f7fbff;}
        body{font-family:Arial,sans-serif;color:var(--ink);margin:20px;}
        .report{border:1px solid var(--line);border-radius:10px;overflow:hidden;}
        .report-header{background:var(--primary);color:#fff;padding:16px 20px;}
        .report-title{margin:0;font-size:20px;}
        .report-meta{margin:6px 0 0;font-size:12px;opacity:.95;}
        .report-body{padding:16px 20px;}
        .summary{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px;margin-bottom:16px;}
        .summary .card{border:1px solid var(--line);border-radius:8px;padding:10px 12px;background:#fff;}
        .summary .label{font-size:11px;color:var(--muted);text-transform:uppercase;letter-spacing:.04em;}
        .summary .value{font-size:16px;margin-top:4px;color:var(--ink);}
        .section-title{font-size:13px;color:var(--muted);margin:6px 0 8px;}
        table{width:100%;border-collapse:collapse;font-size:12px;border:1px solid var(--line);}
        thead th{background:var(--primary);color:#fff;padding:8px;text-align:left;}
        tbody td{padding:8px;border-top:1px solid var(--line);}
        tbody tr:nth-child(even){background:var(--row);}
        .report-footer{padding:12px 20px;font-size:11px;color:var(--muted);border-top:1px solid var(--line);text-align:right;}
    </style>';

    return '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>' . htmlspecialchars($title) . '</title>' . $css . '</head><body>
        <div class="report">
            <div class="report-header">
                <h1 class="report-title">' . htmlspecialchars($title) . '</h1>
                <p class="report-meta">Period: ' . htmlspecialchars($period) . ' • Generated: ' . htmlspecialchars($generated) . '</p>
            </div>
            <div class="report-body">' . $summary_html . $table_html . '</div>
            <div class="report-footer">Generated by MTICS Admin System</div>
        </div>
    </body></html>';
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
                                <select name="report_type" id="reportTypeSelect" class="admin-form-select" required onchange="handleReportTypeChange(this.value)">
                                    <option value="">Select report type...</option>
                                    <optgroup label="General Reports">
                                        <option value="users">Users Report</option>
                                        <option value="recycling">Recycling Activities Report</option>
                                        <option value="redemptions">Redemptions Report</option>
                                        <option value="events">Events Summary Report</option>
                                        <option value="transactions">Transactions Report</option>
                                        <option value="top_recyclers">Top Recyclers</option>
                                    </optgroup>
                                    <optgroup label="Attendance Reports">
                                        <option value="event_attendance">⚠ Event Attendance &amp; Sanctions Report</option>
                                    </optgroup>
                                </select>
                            </div>

                            <div class="admin-form-group">
                                <label class="admin-form-label">Format *</label>
                                <select name="format" class="admin-form-select" required>
                                    <option value="html">PDF File</option>
                                </select>
                            </div>
                        </div>

                        <!-- Event filter — shown only for event_attendance report -->
                        <div class="admin-form-group" id="eventFilterGroup" style="display:none;">
                            <label class="admin-form-label">Filter by Specific Event <span style="color:var(--medium-gray);font-weight:normal;">(optional — leave blank for all events in date range)</span></label>
                            <select name="filter_event_id" id="filterEventId" class="admin-form-select">
                                <option value="0">All events in date range</option>
                                <?php foreach ($all_events_list as $ev): ?>
                                    <option value="<?php echo $ev['id']; ?>">
                                        <?php echo htmlspecialchars($ev['title']); ?> — <?php echo date('M j, Y', strtotime($ev['event_date'])); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small style="color:var(--medium-gray);display:block;margin-top:0.4rem;">
                                Only events with attendance tracking enabled are listed. When a single event is selected,
                                the warning/sanction lists apply to that event only.
                            </small>
                        </div>

                        <!-- Attendance report legend -->
                        <div id="attendanceLegend" style="display:none;background:#f8fafc;border:1px solid #e5effa;border-radius:8px;padding:1rem;margin-bottom:1rem;">
                            <strong style="color:var(--primary-blue);font-size:0.95rem;">How sanctions &amp; warnings are determined:</strong>
                            <div style="display:flex;gap:1.5rem;flex-wrap:wrap;margin-top:0.6rem;font-size:0.875rem;">
                                <span style="display:flex;align-items:center;gap:0.4rem;">
                                    <span style="background:#16a34a;color:#fff;padding:2px 8px;border-radius:4px;font-size:11px;font-weight:bold;">GOOD</span>
                                    Zero absences — attended all events
                                </span>
                                <span style="display:flex;align-items:center;gap:0.4rem;">
                                    <span style="background:#f59e0b;color:#fff;padding:2px 8px;border-radius:4px;font-size:11px;font-weight:bold;">WARNING</span>
                                    Exactly 1 absence
                                </span>
                                <span style="display:flex;align-items:center;gap:0.4rem;">
                                    <span style="background:#dc3545;color:#fff;padding:2px 8px;border-radius:4px;font-size:11px;font-weight:bold;">SANCTION</span>
                                    2 or more absences
                                </span>
                            </div>
                            <p style="margin:0.6rem 0 0;font-size:0.8rem;color:var(--medium-gray);">
                                Only active members are included. A student is counted as absent if they do not have an <strong>approved</strong> attendance record for an event.
                            </p>
                        </div>

                        <div class="grid grid-2">
                            <div class="admin-form-group">
                                <label class="admin-form-label">From Date *</label>
                                <input type="date" name="date_from" class="admin-form-input" required
                                       value="<?php echo date('Y-m-01'); ?>">
                            </div>
                            <div class="admin-form-group">
                                <label class="admin-form-label">To Date *</label>
                                <input type="date" name="date_to" class="admin-form-input" required
                                       value="<?php echo date('Y-m-d'); ?>">
                            </div>
                        </div>

                        <div style="display: flex; gap: 1rem; margin-top: 2rem; flex-wrap: wrap;">
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
function handleReportTypeChange(val) {
    const eventGroup  = document.getElementById('eventFilterGroup');
    const legend      = document.getElementById('attendanceLegend');
    const isAttendance = val === 'event_attendance';
    eventGroup.style.display  = isAttendance ? 'block' : 'none';
    legend.style.display      = isAttendance ? 'block' : 'none';
    document.getElementById('downloadPdfBtn').disabled = true;
}

function generateReport() {
    const form        = document.getElementById('reportForm');
    const formData    = new FormData(form);
    const btn         = document.querySelector('button[onclick="generateReport()"]');
    const originalHTML = btn.innerHTML;

    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Generating...';
    btn.disabled  = true;

    fetch('reports.php', { method: 'POST', body: formData })
        .then(r => r.text())
        .then(html => {
            if (html.includes('alert-success')) {
                document.getElementById('downloadPdfBtn').disabled = false;
                showMessage('Report generated successfully! Click "Download PDF" to get your file.', 'success');
            } else {
                showMessage('Error generating report. Please try again.', 'error');
            }
            btn.innerHTML = originalHTML;
            btn.disabled  = false;
        })
        .catch(() => {
            showMessage('Error generating report. Please try again.', 'error');
            btn.innerHTML = originalHTML;
            btn.disabled  = false;
        });
}

function showMessage(message, type) {
    document.querySelectorAll('.alert').forEach(a => a.remove());
    const div = document.createElement('div');
    div.className = `alert alert-${type}`;
    div.style.marginBottom = 'var(--spacing-md)';
    div.textContent = message;
    const header = document.querySelector('.admin-page-header');
    if (header) header.parentNode.insertBefore(div, header.nextSibling);
    setTimeout(() => div.remove(), 6000);
}

function downloadPDF() {
    const form        = document.getElementById('reportForm');
    const reportType  = form.querySelector('[name="report_type"]')?.value || '';
    const dateFrom    = form.querySelector('[name="date_from"]')?.value  || '';
    const dateTo      = form.querySelector('[name="date_to"]')?.value    || '';
    const csrf        = form.querySelector('[name="csrf_token"]')?.value || '';
    const filterEv    = form.querySelector('[name="filter_event_id"]')?.value || '0';

    if (!reportType || !dateFrom || !dateTo) {
        showMessage('Please generate a report first.', 'error');
        return;
    }

    const f = document.createElement('form');
    f.method = 'POST';
    f.action = 'reports.php';
    f.target = '_blank';
    f.style.display = 'none';

    [
        ['csrf_token',       csrf],
        ['download_pdf',     '1'],
        ['report_type',      reportType],
        ['date_from',        dateFrom],
        ['date_to',          dateTo],
        ['filter_event_id',  filterEv],
    ].forEach(([name, value]) => {
        const inp = document.createElement('input');
        inp.type  = 'hidden';
        inp.name  = name;
        inp.value = value;
        f.appendChild(inp);
    });

    document.body.appendChild(f);
    f.submit();
    document.body.removeChild(f);
    showMessage('Preparing PDF…', 'success');
}

document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('reportForm');
    const btn  = document.getElementById('downloadPdfBtn');
    if (form && btn) {
        form.addEventListener('change', function(e) {
            if (e.target.name !== 'filter_event_id') {
                btn.disabled = true;
            }
        });
    }
});
</script>