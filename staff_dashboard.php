<?php
/**
 * ToolTrace - Maintenance Staff Dashboard
 */
declare(strict_types=1);

require_once __DIR__ . '/includes/require_staff_session.php';
require_once __DIR__ . '/includes/borrow_requests_store.php';
require_once __DIR__ . '/includes/inventory_store.php';

$user = [
    'name' => $_SESSION['user_name'] ?? 'Staff',
    'role' => $_SESSION['role']      ?? 'Maintenance Staff',
];

$notice = '';
$error  = '';

$all_requests  = tooltrace_borrow_requests_all();
$all_inventory = tooltrace_inventory_load();
$today         = new DateTimeImmutable('today');

$total_equipment = count($all_inventory);
$pending_count   = 0;
$active_loans    = 0;
$overdue_today   = 0;
$pending_grouped = [];

foreach ($all_requests as $req) {
    $status = tooltrace_borrow_request_approval_status($req);
    if ($status === 'pending') {
        $requestId = (string) ($req['request_id'] ?? $req['transaction_id'] ?? '');
        if ($requestId !== '') {
            $pending_grouped[$requestId]['request_id'] = $requestId;
            $pending_grouped[$requestId]['organization_name'] = (string) ($req['organization_name'] ?? '—');
            $pending_grouped[$requestId]['date_requested'] = (string) ($req['date_requested'] ?? '');
            $label = tooltrace_borrow_item_label($req);
            if ($label !== '') {
                $pending_grouped[$requestId]['items'][$label] = true;
            }
        }
    }
    if (tooltrace_borrow_is_active($req)) {
        $active_loans++;
    }
    if (tooltrace_borrow_is_overdue($req, $today)) {
        $overdue_today++;
    }
}

$pending_count = count($pending_grouped);

$stats = [
    'total'   => $total_equipment,
    'pending' => $pending_count,
    'active'  => $active_loans,
    'overdue' => $overdue_today,
];

// Latest 5 PENDING requests only (newest first)
$pending_requests = array_values(array_map(static function (array $req): array {
    $req['items_list'] = array_keys($req['items'] ?? []);
    $req['item_label'] = $req['items_list'] ? implode(', ', $req['items_list']) : '—';
    unset($req['items']);
    return $req;
}, $pending_grouped));

foreach ($pending_requests as &$pending_request) {
    if (empty($pending_request['organization_name'])) {
        $pending_request['organization_name'] = '—';
    }
}
unset($pending_request);

usort($pending_requests, static function (array $a, array $b): int {
    $da = isset($a['date_requested']) ? strtotime((string)$a['date_requested']) : 0;
    $db = isset($b['date_requested']) ? strtotime((string)$b['date_requested']) : 0;
    return $db <=> $da;
});
$latest_requests = array_slice($pending_requests, 0, 5);

// Build TTS summary for staff dashboard
$firstName  = explode(' ', $user['name'])[0];
$ttsSummary = 'Welcome back ' . $firstName . '. '
    . 'Total equipment: ' . $total_equipment . '. '
    . 'Pending requests: ' . $pending_count . '. '
    . 'Active loans: ' . $active_loans . '. '
    . 'Overdue today: ' . $overdue_today . '.';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ToolTrace | Staff Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-blue: #2c3e50;
            --accent-yellow: #f1c40f;
            --bg-gray: #f4f7f6;
        }
        body { font-family: 'Segoe UI', Arial, sans-serif; margin: 0; background-color: var(--bg-gray); }

        .welcome-section { margin-bottom: 30px; display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 12px; }
        .welcome-text h1 { margin: 0; font-size: 28px; }
        .welcome-text p  { color: #666; margin-top: 5px; margin-bottom: 0; }

        .tts-summary-btn {
            background: var(--accent-yellow);
            border: none;
            padding: 9px 16px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 7px;
            color: var(--primary-blue);
            white-space: nowrap;
            transition: 0.2s;
        }
        .tts-summary-btn:hover { background: #d4ac0d; }

        .dashboard-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 40px; }
        .stat-card { background: white; padding: 20px; border-radius: 10px; text-align: center; box-shadow: 0 4px 6px rgba(0,0,0,0.02); position: relative; }
        .stat-card h3 { font-size: 13px; color: #7f8c8d; text-transform: uppercase; margin-bottom: 10px; margin-top: 0; }
        .stat-card .val { font-size: 32px; font-weight: 700; color: var(--primary-blue); }

        .stat-speak-btn {
            position: absolute;
            top: 10px;
            right: 10px;
            background: none;
            border: none;
            cursor: pointer;
            font-size: 13px;
            color: #bdc3c7;
            padding: 0;
            transition: color 0.2s;
        }
        .stat-speak-btn:hover { color: #3498db; }

        .table-card { background: white; padding: 25px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.02); }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th { text-align: left; color: #95a5a6; font-size: 12px; text-transform: uppercase; padding-bottom: 15px; border-bottom: 1px solid #eee; }
        td { padding: 15px 0; border-bottom: 1px solid #f9f9f9; font-size: 14px; }
        .badge { padding: 4px 10px; border-radius: 5px; font-size: 12px; font-weight: 600; }
        .status-approved { background: #ecfaf0; color: #2ecc71; }
        .status-pending  { background: #fef9e7; color: #d4ac0d; }
        .status-rejected { background: #fdedec; color: #e74c3c; }
        .notice { background: #ecfaf0; border: 1px solid #2ecc71; color: #1e8449; padding: 12px 18px; border-radius: 8px; margin-bottom: 18px; font-size: 14px; font-weight: 600; }
        .view-all-link { font-size: 13px; color: var(--primary-blue); text-decoration: none; font-weight: 600; float: right; margin-top: -36px; }
        .view-all-link:hover { text-decoration: underline; }
        .item-label { font-size: 12px; color: #7f8c8d; }

        /* Custom confirmation modal */
        .modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.45);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        .modal-overlay.active { display: flex; }
        .modal-box {
            background: white;
            border-radius: 14px;
            padding: 32px 36px;
            width: 100%;
            max-width: 420px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.15);
            text-align: center;
        }
        .modal-icon {
            width: 52px; height: 52px;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 22px;
            margin: 0 auto 16px;
        }
        .modal-icon.approve { background: #ecfaf0; color: #1e8449; }
        .modal-icon.reject  { background: #fdedec; color: #c0392b; }
        .modal-box h3 { font-size: 18px; font-weight: 700; margin: 0 0 8px; color: #2c3e50; }
        .modal-box p  { font-size: 14px; color: #7f8c8d; margin: 0 0 24px; line-height: 1.5; }
        .modal-actions { display: flex; gap: 12px; justify-content: center; }
        .modal-btn {
            padding: 10px 28px; border-radius: 8px; font-size: 14px;
            font-weight: 600; cursor: pointer; border: none; font-family: inherit;
            transition: opacity 0.15s;
        }
        .modal-btn:hover { opacity: 0.85; }
        .modal-btn-cancel  { background: #f4f7f6; color: #555; border: 1px solid #ddd; }
        .modal-btn-approve { background: #2ecc71; color: white; }
        .modal-btn-reject  { background: #e74c3c; color: white; }

        @media (max-width: 900px) {
            .dashboard-grid { grid-template-columns: repeat(2, 1fr); }
        }
        @media (max-width: 500px) {
            .dashboard-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

    <?php include 'navbar.php'; ?>

    <main class="main-wrapper">
        <section class="welcome-section">
            <div class="welcome-text">
                <h1>Welcome back, <?php echo htmlspecialchars($firstName, ENT_QUOTES, 'UTF-8'); ?>!</h1>
                <p>Here is the current overview of the laboratory equipment and requests.</p>
            </div>
            <!-- TTS: Read entire dashboard summary aloud -->
            <button class="tts-summary-btn" type="button"
                onclick="tooltraceSpeak('<?php echo htmlspecialchars($ttsSummary, ENT_QUOTES, 'UTF-8'); ?>')">
                <i class="fa-solid fa-volume-high"></i> Read Summary
            </button>
        </section>

        <?php if ($notice): ?>
            <div class="notice"><?php echo htmlspecialchars($notice, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>

        <div class="dashboard-grid">
            <div class="stat-card">
                <h3>Total Equipment</h3>
                <div class="val"><?php echo $stats['total']; ?></div>
                <button class="stat-speak-btn" type="button" title="Read aloud"
                    onclick="tooltraceSpeak('Total equipment: <?php echo $stats['total']; ?> items.')">🔊</button>
            </div>
            <div class="stat-card">
                <h3>Pending Requests</h3>
                <div class="val"><?php echo $stats['pending']; ?></div>
                <button class="stat-speak-btn" type="button" title="Read aloud"
                    onclick="tooltraceSpeak('Pending requests: <?php echo $stats['pending']; ?> requests.')">🔊</button>
            </div>
            <div class="stat-card">
                <h3>Active Loans</h3>
                <div class="val"><?php echo $stats['active']; ?></div>
                <button class="stat-speak-btn" type="button" title="Read aloud"
                    onclick="tooltraceSpeak('Active loans: <?php echo $stats['active']; ?> loans.')">🔊</button>
            </div>
            <div class="stat-card">
                <h3>Overdue Today</h3>
                <div class="val" style="color:#e74c3c;"><?php echo $stats['overdue']; ?></div>
                <button class="stat-speak-btn" type="button" title="Read aloud"
                    onclick="tooltraceSpeak('Overdue today: <?php echo $stats['overdue']; ?> items.')">🔊</button>
            </div>
        </div>

        <div class="table-card">
            <h2 style="font-size:18px; margin-top:0;">Pending Requests</h2>
            <a href="staff_requests.php" class="view-all-link">View all →</a>
            <table>
                <thead>
                    <tr>
                        <th>Organization</th>
                        <th>Item(s)</th>
                        <th>Date Requested</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($latest_requests)): ?>
                    <tr><td colspan="3" style="color:#95a5a6; padding:20px 0;">No pending requests.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($latest_requests as $req):
                        $org        = htmlspecialchars($req['organization_name'] ?? '—', ENT_QUOTES, 'UTF-8');
                        $item_label = htmlspecialchars((string) ($req['item_label'] ?? '—'), ENT_QUOTES, 'UTF-8');
                        $date_req   = !empty($req['date_requested']) ? date('M d, Y', strtotime($req['date_requested'])) : '—';
                        $safe_rid   = htmlspecialchars($req['request_id'] ?? '', ENT_QUOTES, 'UTF-8');
                    ?>
                    <tr>
                        <td>
                            <strong><?php echo $org; ?></strong><br>
                            <span style="font-size:11px; color:#95a5a6;"><?php echo $safe_rid; ?></span>
                        </td>
                        <td><span class="item-label"><?php echo $item_label; ?></span></td>
                        <td><?php echo htmlspecialchars($date_req, ENT_QUOTES, 'UTF-8'); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

    </main>

    <div id="uiToast" class="success" role="status" aria-live="polite" style="display:none; position: fixed; bottom: 26px; left: 50%; transform: translateX(-50%); background: #2c3e50; color: #fff; padding: 14px 20px; border-radius: 12px; font-size: 14px; font-weight: 800; z-index: 10000; box-shadow: 0 10px 30px rgba(0,0,0,0.25); max-width: 520px; text-align: center;"></div>

    <script>
        function showToast(msg, type = 'success') {
            const t = document.getElementById('uiToast');
            if (!t) return;
            t.textContent = String(msg || '');
            t.className = type;
            t.style.display = 'block';
            clearTimeout(t._hideTimer);
            t._hideTimer = setTimeout(() => { t.style.display = 'none'; }, 3500);
        }

        // ===== TEXT TO SPEECH FUNCTION =====
        function tooltraceSpeak(text) {
            if (!window.speechSynthesis) {
                showToast('Text-to-speech is not supported in your browser.', 'warning');
                return;
            }
            
            // Cancel any ongoing speech
            window.speechSynthesis.cancel();
            
            // Ensure text is a string and decode HTML entities
            if (typeof text !== 'string') {
                text = String(text);
            }
            
            // Create a temporary element to decode HTML entities
            const temp = document.createElement('div');
            temp.innerHTML = text;
            const decodedText = temp.textContent || temp.innerText || text;
            
            const utterance = new SpeechSynthesisUtterance(decodedText);
            utterance.rate = 1;
            utterance.pitch = 1;
            utterance.volume = 1;
            
            console.log('Speaking:', decodedText);
            
            window.speechSynthesis.speak(utterance);
        }
    </script>

</body>
</html>