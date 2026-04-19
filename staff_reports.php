<?php
/**
 * ToolTrace - Reports Page
 */
declare(strict_types=1);

require_once __DIR__ . '/includes/require_staff_session.php';
require_once __DIR__ . '/includes/inventory_store.php';
require_once __DIR__ . '/includes/borrow_requests_store.php';

$inventory_rows = tooltrace_inventory_load();
$inventory = [];
foreach ($inventory_rows as $row) {
    $inventory[] = [
        'id'          => 'IT' . str_pad((string)(int)($row['id'] ?? 0), 3, '0', STR_PAD_LEFT),
        'raw_id'      => (int)($row['id'] ?? 0),
        'name'        => (string)($row['name'] ?? ''),
        'description' => (string)($row['description'] ?? ''),
        'quantity'    => (int)($row['quantity'] ?? 0),
        'conditions'  => $row['conditions'] ?? [],
        'image'       => (string)($row['image'] ?? ''),
    ];
}

$all_requests = tooltrace_borrow_requests_all();
$today        = new DateTimeImmutable('today');

$total_transactions = count($all_requests);
$items_borrowed     = 0;
$items_returned     = 0;
$overdue_count      = 0;
$grouped_items      = [];

foreach ($all_requests as $req) {
    if (!empty($req['returned'])) {
        $items_returned++;
    }
    if (tooltrace_borrow_is_active($req)) {
        $items_borrowed++;
    }
    if (tooltrace_borrow_is_overdue($req, $today)) {
        $overdue_count++;
    }

    $requestId = (string) ($req['request_id'] ?? $req['transaction_id'] ?? '');
    if ($requestId !== '') {
        $label = tooltrace_borrow_item_label($req);
        if ($label !== '' && $label !== '—') {
            $grouped_items[$requestId][$label] = true;
        }
    }
}

$return_rate = $total_transactions > 0
    ? round(($items_returned / $total_transactions) * 100)
    : 0;

// Most borrowed items — tally from all approved/returned requests
$borrow_tally = [];
foreach ($all_requests as $req) {
    $ap = tooltrace_borrow_request_approval_status($req);
    if ($ap === 'rejected') continue;
    $label = tooltrace_borrow_item_label($req);
    if ($label !== '' && $label !== '—') {
        $borrow_tally[$label] = ($borrow_tally[$label] ?? 0) + 1;
    }
}
arsort($borrow_tally);
$chart_items  = array_keys(array_slice($borrow_tally, 0, 8, true));
$chart_counts = array_values(array_slice($borrow_tally, 0, 8, true));

$user = [
    'name'     => $_SESSION['user_name'] ?? 'Staff',
    'role'     => $_SESSION['role']      ?? 'Maintenance Staff',
    'initials' => strtoupper(substr((string)($_SESSION['user_name'] ?? 'S'), 0, 2)),
];

$stats = [
    'total'       => ['label' => 'Total Transactions', 'val' => $total_transactions, 'sub' => 'All time',           'color' => '#666'],
    'borrowed'    => ['label' => 'Currently Out',      'val' => $items_borrowed,     'sub' => 'Active loans',       'color' => '#2980b9'],
    'overdue'     => ['label' => 'Overdue',             'val' => $overdue_count,      'sub' => 'Past return date',   'color' => '#e74c3c'],
    'return_rate' => ['label' => 'Return Rate',         'val' => $return_rate . '%',  'sub' => $items_returned . ' items returned', 'color' => '#27ae60'],
];

$all_requests_js = [];
foreach ($all_requests as $req) {
    $requestId = (string) ($req['request_id'] ?? $req['transaction_id'] ?? '');
    $itemsList = isset($grouped_items[$requestId]) ? array_keys($grouped_items[$requestId]) : [];
    $req['item_label'] = $itemsList ? implode(', ', $itemsList) : tooltrace_borrow_item_label($req);
    $req['display_status'] = tooltrace_borrow_request_display_status($req);
    $all_requests_js[] = $req;
}

// PHP vars needed by printReport() JS — encode once for injection
$print_total        = (int)$total_transactions;
$print_borrowed     = (int)$items_borrowed;
$print_overdue      = (int)$overdue_count;
$print_return_rate  = $return_rate . '%';
$print_returned_sub = $items_returned . ' items returned';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ToolTrace | Reports</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
    <style>
        :root { --primary-blue: #2c3e50; --accent-yellow: #f1c40f; --bg-gray: #f4f7f6; }
        body { font-family: 'Segoe UI', Arial, sans-serif; margin: 0; background-color: var(--bg-gray); }
        .welcome-section { margin-bottom: 30px; }
        .welcome-section h1 { margin: 0; font-size: 28px; font-weight: 700; color: #000; }
        .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: white; padding: 20px; border-radius: 10px; text-align: center; box-shadow: 0 4px 6px rgba(0,0,0,0.02); border: 1px solid #eee; }
        .stat-card h3 { font-size: 11px; color: #7f8c8d; text-transform: uppercase; margin-bottom: 10px; margin-top: 0; letter-spacing: 0.5px; }
        .stat-card .val { font-size: 32px; font-weight: 700; color: var(--primary-blue); margin-bottom: 5px; }
        .stat-card .sub { font-size: 11px; font-weight: 600; }

        .chart-card { background: white; padding: 25px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.02); border: 1px solid #eee; margin-bottom: 30px; }
        .chart-card h2 { font-size: 16px; margin: 0 0 20px; }
        .chart-wrap { position: relative; height: 240px; }

        .table-card { background: white; padding: 25px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.02); border: 1px solid #eee; }
        .table-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 12px; }
        .table-header h2 { font-size: 18px; margin: 0; }
        .table-header-right { display: flex; align-items: center; gap: 16px; }
        .tab-nav { display: flex; gap: 20px; }
        .tab-nav a { text-decoration: none; font-size: 13px; font-weight: 600; color: #95a5a6; padding-bottom: 5px; border-bottom: 2px solid transparent; transition: 0.2s; cursor: pointer; }
        .tab-nav a.active { color: var(--primary-blue); border-bottom: 2px solid var(--primary-blue); }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th { text-align: left; color: #95a5a6; font-size: 11px; text-transform: uppercase; padding-bottom: 15px; border-bottom: 1px solid #eee; }
        td { padding: 15px 0; border-bottom: 1px solid #f9f9f9; font-size: 13px; color: #333; }
        #activityTable th:nth-child(2), #activityTable td:nth-child(2) { padding-right: 22px; }
        #activityTable th:nth-child(3), #activityTable td:nth-child(3) { padding-left: 22px; }
        .badge { padding: 4px 10px; border-radius: 5px; font-size: 11px; font-weight: 700; text-transform: uppercase; }
        .status-returned  { background: #ecfaf0; color: #2ecc71; }
        .status-borrowed  { background: #f4eafc; color: #9b59b6; }
        .status-overdue   { background: #fff0f0; color: #e74c3c; }
        .status-damaged   { background: #fff4e5; color: #e67e22; }
        .activity-row { transition: opacity 0.3s ease; }
        .action-group { display: flex; gap: 10px; }
        .btn { padding: 10px 18px; border-radius: 6px; font-size: 13px; font-weight: 600; cursor: pointer; border: none; display: flex; align-items: center; gap: 8px; transition: 0.2s; }
        .btn-outline { background: white; color: var(--primary-blue); border: 1px solid #ddd; }
        .btn-outline:hover { background: #f9f9f9; }
        .btn-export { background: white; color: var(--primary-blue); border: 1px solid #ddd; padding: 7px 16px; border-radius: 6px; font-size: 13px; font-weight: 600; cursor: pointer; font-family: inherit; }
        .btn-export:hover { background: #f4f7f6; }
        .btn-view { cursor: pointer; background: none; border: 1px solid #ddd; border-radius: 6px; padding: 5px 9px; font-size: 15px; color: var(--primary-blue); transition: background 0.2s, border-color 0.2s; line-height: 1; }
        .btn-view:hover { background: #f0f4f8; border-color: var(--primary-blue); }

        /* View modal (read-only on Reports) */
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); overflow-y: auto; }
        .modal-content { background: white; margin: 5% auto; padding: 25px; width: 90%; max-width: 600px; border-radius: 10px; max-height: 85vh; overflow-y: auto; box-shadow: 0 4px 20px rgba(0,0,0,0.2); }
        .form-label { font-size: 12px; font-weight: bold; display: block; margin-bottom: 5px; }
        .form-input { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px; box-sizing: border-box; background: #f9f9f9; color: #555; }
        .form-group { margin-bottom: 15px; }
    </style>
</head>
<body>

    <?php include 'navbar.php'; ?>

    <main class="main-wrapper">

        <section class="welcome-section" style="display:flex; justify-content:space-between; align-items:center;">
            <h1>Reports</h1>
            <div class="action-group">
                <button class="btn btn-outline" onclick="printReport()">
                    <span>&#x1F5A8;</span> Print Report
                </button>
            </div>
        </section>

        <!-- VIEW MODAL (read-only on Reports page) -->
        <div id="viewModal" class="modal">
            <div class="modal-content">
                <h2 style="margin-top:0">Request Details</h2>
                <p style="font-size:13px; color:#666;">View-only summary. Edit requests from the <a href="staff_requests.php" style="color:var(--primary-blue); font-weight:600;">Requests page</a>.</p>
                <hr style="border:0; border-top:1px solid #eee; margin:15px 0;">
                <div class="form-group">
                    <label class="form-label">Request ID</label>
                    <input type="text" id="viewRequestIdDisplay" class="form-input" readonly>
                </div>
                <div class="form-group">
                    <label class="form-label">Organization Name</label>
                    <input type="text" id="viewOrganization" class="form-input" readonly>
                </div>
                <div class="form-group">
                    <label class="form-label">Officer in Charge</label>
                    <input type="text" id="viewOfficer" class="form-input" readonly>
                </div>
                <div class="form-group">
                    <label class="form-label">Item</label>
                    <textarea id="viewItem" class="form-input" readonly style="resize:none; min-height:60px;"></textarea>
                </div>
                <div class="form-group">
                    <label class="form-label">Purpose / Notes</label>
                    <textarea id="viewPurpose" class="form-input" readonly style="resize:none; min-height:70px;"></textarea>
                </div>
                <div class="form-group" style="margin-bottom:20px;">
                    <label class="form-label">Status</label>
                    <input type="text" id="viewStatus" class="form-input" readonly>
                </div>
                <div style="display:flex; gap:10px; justify-content:flex-end;">
                    <button type="button" class="btn btn-outline" onclick="closeViewModal()">Close</button>
                </div>
            </div>
        </div>

        <!-- STATS -->
        <div class="stats-grid">
            <?php foreach ($stats as $s): ?>
            <div class="stat-card">
                <h3><?php echo htmlspecialchars($s['label'], ENT_QUOTES, 'UTF-8'); ?></h3>
                <div class="val"><?php echo is_int($s['val']) ? (int)$s['val'] : htmlspecialchars((string)$s['val'], ENT_QUOTES, 'UTF-8'); ?></div>
                <div class="sub" style="color: <?php echo $s['color']; ?>"><?php echo htmlspecialchars($s['sub'], ENT_QUOTES, 'UTF-8'); ?></div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- MOST BORROWED CHART -->
        <?php if (!empty($chart_items)): ?>
        <div class="chart-card">
            <h2>Most Borrowed Equipment</h2>
            <div class="chart-wrap">
                <canvas id="borrowChart"></canvas>
            </div>
        </div>
        <?php endif; ?>

        <!-- ACTIVITY LOG TABLE -->
        <div class="table-card">
            <div class="table-header">
                <h2>Item Activity Log</h2>
                <div class="table-header-right">
                    <button class="btn-export" onclick="exportCSV()">⬇ Export CSV</button>
                    <div class="tab-nav" id="filterNav">
                        <a class="filter-btn active" data-filter="all">All</a>
                        <a class="filter-btn" data-filter="borrowed">Borrowed</a>
                        <a class="filter-btn" data-filter="returned">Returned</a>
                        <a class="filter-btn" data-filter="overdue">Overdue</a>
                    </div>
                </div>
            </div>

            <table id="activityTable">
                <thead>
                    <tr>
                        <th style="width:15%;">Date</th>
                        <th style="width:15%;">Organization</th>
                        <th style="width:15%;">Item</th>
                        <th style="width:12%;">Officer</th>
                        <th style="width:10%;">Period</th>
                        <th style="width:10%;">Status</th>
                        <th style="width:15%;">Notes</th>
                        <th style="width:8%;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (array_reverse($all_requests) as $req):
                        $statusLabel = tooltrace_borrow_request_display_status($req);
                        $reqStatus = match ($statusLabel) {
                            'Returned' => 'returned',
                            'Overdue' => 'overdue',
                            default => 'borrowed',
                        };
                        $dateStr = !empty($req['date_requested']) ? date('M d, Y', strtotime($req['date_requested'])) : '—';
                        $period  = (!empty($req['date_borrowed']) && !empty($req['due_date']))
                            ? date('M d', strtotime($req['date_borrowed'])) . ' – ' . date('M d', strtotime($req['due_date']))
                            : '—';
                        $rowRequestId = (string) ($req['request_id'] ?? '');
                        $requestId = htmlspecialchars($rowRequestId, ENT_QUOTES, 'UTF-8');
                        $itemsList = isset($grouped_items[$rowRequestId]) ? array_keys($grouped_items[$rowRequestId]) : [];
                        $itemLabel = $itemsList ? implode(', ', $itemsList) : tooltrace_borrow_item_label($req);
                    ?>
                    <tr class="activity-row"
                        data-status="<?php echo htmlspecialchars($reqStatus, ENT_QUOTES, 'UTF-8'); ?>"
                        data-request-id="<?php echo $requestId; ?>">
                        <td>
                            <strong><?php echo htmlspecialchars($dateStr, ENT_QUOTES, 'UTF-8'); ?></strong><br>
                            <small style="color:#95a5a6;"><?php echo $requestId; ?></small>
                        </td>
                        <td><strong><?php echo htmlspecialchars($req['organization_name'] ?? '—', ENT_QUOTES, 'UTF-8'); ?></strong></td>
                        <td><?php echo htmlspecialchars($itemLabel, ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars($req['staff_name'] ?? '—', ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars($period, ENT_QUOTES, 'UTF-8'); ?></td>
                        <td>
                            <span class="badge status-<?php echo htmlspecialchars($reqStatus, ENT_QUOTES, 'UTF-8'); ?>">
                                <?php echo htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8'); ?>
                            </span>
                        </td>
                        <td style="color:#666; font-size:12px;">
                            <?php echo htmlspecialchars($req['purpose'] ?? 'None.', ENT_QUOTES, 'UTF-8'); ?>
                        </td>
                        <td>
                            <button type="button"
                                class="btn-view"
                                onclick="openViewModal('<?php echo $requestId; ?>')"
                                title="View Details">
                                &#x1F441;
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

    </main>

    <script>
        const STAFF_USER_NAME = <?php echo json_encode($user['name'], JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP); ?>;
        const all_requests    = <?php echo json_encode($all_requests_js, JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT); ?>;

        const chartLabels = <?php echo json_encode($chart_items, JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT); ?>;
        const chartCounts = <?php echo json_encode($chart_counts, JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT); ?>;

        // PHP stat values passed to JS for the print window
        const PRINT_STATS = {
            total:       { label: 'Total Transactions', val: <?php echo json_encode($print_total); ?>,        sub: 'All time',                                       color: '#555'    },
            borrowed:    { label: 'Currently Out',      val: <?php echo json_encode($print_borrowed); ?>,     sub: 'Active loans',                                   color: '#2980b9' },
            overdue:     { label: 'Overdue',            val: <?php echo json_encode($print_overdue); ?>,      sub: 'Past return date',                               color: '#e74c3c' },
            return_rate: { label: 'Return Rate',        val: <?php echo json_encode($print_return_rate); ?>,  sub: <?php echo json_encode($print_returned_sub); ?>,  color: '#27ae60' },
        };

        // ── Most Borrowed Chart ───────────────────────────────────────────────
        if (chartLabels.length > 0) {
            const ctx = document.getElementById('borrowChart').getContext('2d');
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: chartLabels,
                    datasets: [{
                        label: 'Times Borrowed',
                        data: chartCounts,
                        backgroundColor: 'rgba(44, 62, 80, 0.75)',
                        borderRadius: 5,
                        borderSkipped: false,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: ctx => ' ' + ctx.parsed.y + ' borrow' + (ctx.parsed.y !== 1 ? 's' : '')
                            }
                        }
                    },
                    scales: {
                        x: { grid: { display: false }, ticks: { font: { size: 12 } } },
                        y: { beginAtZero: true, ticks: { stepSize: 1, font: { size: 12 } }, grid: { color: '#f0f0f0' } }
                    }
                }
            });
        }

        // ── Export CSV ────────────────────────────────────────────────────────
        function exportCSV() {
            const rows = [['Date', 'Request ID', 'Organization', 'Item', 'Officer', 'Period', 'Status', 'Notes']];
            document.querySelectorAll('#activityTable tbody tr.activity-row').forEach(tr => {
                if (tr.style.display === 'none') return;
                const cells = tr.querySelectorAll('td');
                if (cells.length < 7) return;
                const dateCell = cells[0].innerText.trim().split('\n');
                rows.push([
                    dateCell[0] || '',
                    dateCell[1] || '',
                    cells[1].innerText.trim(),
                    cells[2].innerText.trim(),
                    cells[3].innerText.trim(),
                    cells[4].innerText.trim(),
                    cells[5].innerText.trim(),
                    cells[6].innerText.trim(),
                ]);
            });
            const csv = rows.map(r => r.map(c => '"' + String(c).replace(/"/g, '""') + '"').join(',')).join('\n');
            const a   = document.createElement('a');
            a.href    = 'data:text/csv;charset=utf-8,' + encodeURIComponent(csv);
            a.download = 'tooltrace_activity_' + new Date().toISOString().slice(0,10) + '.csv';
            a.click();
        }

        function escapeHtml(s) {
            return String(s)
                .replace(/&/g, '&amp;').replace(/</g, '&lt;')
                .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
        }

        // ── Print Report ─────────────────────────────────────────────────────
        function printReport() {
            const today = new Date().toLocaleDateString('en-US', { weekday:'long', year:'numeric', month:'long', day:'numeric' });

            const statsHtml = Object.values(PRINT_STATS).map(s => `
                <div style="border:1px solid #e0e0e0; border-radius:8px; padding:18px; text-align:center; background:#fafafa;">
                    <div style="font-size:10px; text-transform:uppercase; letter-spacing:0.5px; color:#888; margin-bottom:8px;">${escapeHtml(s.label)}</div>
                    <div style="font-size:28px; font-weight:700; color:#2c3e50; margin-bottom:4px;">${escapeHtml(String(s.val))}</div>
                    <div style="font-size:11px; font-weight:600; color:${escapeHtml(s.color)};">${escapeHtml(s.sub)}</div>
                </div>`).join('');

            const topItems = chartLabels.map((label, i) => ({ label, count: chartCounts[i] }));
            const maxCount = topItems.length > 0 ? topItems[0].count : 1;
            const topItemsHtml = topItems.length ? `
                <div style="margin-bottom:28px;">
                    <h3 style="font-size:12px; font-weight:700; color:#2c3e50; margin:0 0 12px; text-transform:uppercase; letter-spacing:0.6px; padding-bottom:8px; border-bottom:2px solid #2c3e50;">Most Borrowed Equipment</h3>
                    <table style="width:100%; border-collapse:collapse;">
                        <thead>
                            <tr style="background:#f4f7f6;">
                                <th style="text-align:left; padding:8px 12px; font-size:10px; color:#666; font-weight:600; border-bottom:1px solid #ddd; text-transform:uppercase; letter-spacing:0.4px;">Item</th>
                                <th style="text-align:right; padding:8px 12px; font-size:10px; color:#666; font-weight:600; border-bottom:1px solid #ddd; text-transform:uppercase; letter-spacing:0.4px; width:80px;">Borrows</th>
                                <th style="text-align:left; padding:8px 12px; font-size:10px; color:#666; font-weight:600; border-bottom:1px solid #ddd; text-transform:uppercase; letter-spacing:0.4px; width:42%;">Usage</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${topItems.map(it => {
                                const pct = maxCount > 0 ? Math.round((it.count / maxCount) * 100) : 0;
                                return `<tr style="border-bottom:1px solid #f2f2f2;">
                                    <td style="padding:9px 12px; font-size:12px; color:#333;">${escapeHtml(it.label)}</td>
                                    <td style="padding:9px 12px; font-size:13px; font-weight:700; color:#2c3e50; text-align:right;">${it.count}</td>
                                    <td style="padding:9px 12px;">
                                        <div style="background:#e8edf2; border-radius:3px; height:8px; overflow:hidden;">
                                            <div style="background:#2c3e50; height:100%; width:${pct}%; border-radius:3px;"></div>
                                        </div>
                                    </td>
                                </tr>`;
                            }).join('')}
                        </tbody>
                    </table>
                </div>` : '';

            const visibleRows = [...document.querySelectorAll('#activityTable tbody tr.activity-row')]
                .filter(r => r.style.display !== 'none');

            const statusColors = { returned: '#27ae60', borrowed: '#9b59b6', overdue: '#e74c3c', damaged: '#e67e22' };

            const tableRowsHtml = visibleRows.map(tr => {
                const cells  = tr.querySelectorAll('td');
                const status = tr.getAttribute('data-status') || 'borrowed';
                const col    = statusColors[status] || '#555';
                const dateLines = (cells[0]?.innerText || '').trim().split('\n');
                return `<tr style="border-bottom:1px solid #f5f5f5;">
                    <td style="padding:7px 10px; font-size:11px; color:#333; white-space:nowrap;">
                        ${escapeHtml(dateLines[0] || '—')}
                        <br><span style="color:#bbb; font-size:10px;">${escapeHtml(dateLines[1] || '')}</span>
                    </td>
                    <td style="padding:7px 10px; font-size:11px; color:#333;">${escapeHtml((cells[1]?.innerText || '—').trim())}</td>
                    <td style="padding:7px 10px; font-size:11px; color:#333;">${escapeHtml((cells[2]?.innerText || '—').trim())}</td>
                    <td style="padding:7px 10px; font-size:11px; color:#333;">${escapeHtml((cells[3]?.innerText || '—').trim())}</td>
                    <td style="padding:7px 10px; font-size:11px; color:#333; white-space:nowrap;">${escapeHtml((cells[4]?.innerText || '—').trim())}</td>
                    <td style="padding:7px 10px; font-size:10px; font-weight:700; color:${col}; text-transform:uppercase; letter-spacing:0.4px; white-space:nowrap;">${escapeHtml(status)}</td>
                    <td style="padding:7px 10px; font-size:11px; color:#666;">${escapeHtml((cells[6]?.innerText || '—').trim())}</td>
                </tr>`;
            }).join('');

            const activeFilter = document.querySelector('.filter-btn.active')?.getAttribute('data-filter') || 'all';
            const filterLabel  = activeFilter === 'all' ? 'All Entries' : activeFilter.charAt(0).toUpperCase() + activeFilter.slice(1) + ' Entries';

            const printWin = window.open('', '_blank', 'width=960,height=750');
            printWin.document.write(`<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>ToolTrace Report</title>
<style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: 'Segoe UI', Arial, sans-serif; background: #fff; color: #222; padding: 40px 44px; }
    .no-print { display: block; }
    @media print {
        body { padding: 0; }
        .no-print { display: none !important; }
        @page { margin: 16mm 14mm; size: A4 portrait; }
    }
</style>
</head>
<body>

<div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:28px; padding-bottom:16px; border-bottom:3px solid #2c3e50;">
    <div>
        <div style="font-size:24px; font-weight:800; color:#2c3e50; letter-spacing:-0.5px;">ToolTrace</div>
        <div style="font-size:12px; color:#999; margin-top:3px; letter-spacing:0.3px;">Equipment &amp; Borrow Management System</div>
    </div>
    <div style="text-align:right;">
        <div style="font-size:16px; font-weight:700; color:#2c3e50;">Activity Report</div>
        <div style="font-size:12px; color:#888; margin-top:4px;">${escapeHtml(today)}</div>
        <div style="font-size:11px; color:#bbb; margin-top:2px;">Generated by: ${escapeHtml(STAFF_USER_NAME)}</div>
    </div>
</div>

<div style="margin-bottom:10px;">
    <h3 style="font-size:12px; font-weight:700; color:#2c3e50; margin:0 0 12px; text-transform:uppercase; letter-spacing:0.6px; padding-bottom:8px; border-bottom:2px solid #2c3e50;">Summary Statistics</h3>
</div>
<div style="display:grid; grid-template-columns:repeat(4,1fr); gap:14px; margin-bottom:28px;">
    ${statsHtml}
</div>

${topItemsHtml}

<div style="margin-bottom:12px;">
    <h3 style="font-size:12px; font-weight:700; color:#2c3e50; margin:0 0 12px; text-transform:uppercase; letter-spacing:0.6px; padding-bottom:8px; border-bottom:2px solid #2c3e50;">
        Item Activity Log &mdash; ${escapeHtml(filterLabel)} (${visibleRows.length} record${visibleRows.length !== 1 ? 's' : ''})
    </h3>
</div>
<table style="width:100%; border-collapse:collapse; font-size:11px;">
    <thead>
        <tr style="background:#f4f7f6;">
            <th style="text-align:left; padding:8px 10px; font-size:10px; color:#666; font-weight:600; border-bottom:1px solid #ddd; text-transform:uppercase; letter-spacing:0.4px;">Date</th>
            <th style="text-align:left; padding:8px 10px; font-size:10px; color:#666; font-weight:600; border-bottom:1px solid #ddd; text-transform:uppercase; letter-spacing:0.4px;">Organization</th>
            <th style="text-align:left; padding:8px 10px; font-size:10px; color:#666; font-weight:600; border-bottom:1px solid #ddd; text-transform:uppercase; letter-spacing:0.4px;">Item</th>
            <th style="text-align:left; padding:8px 10px; font-size:10px; color:#666; font-weight:600; border-bottom:1px solid #ddd; text-transform:uppercase; letter-spacing:0.4px;">Officer</th>
            <th style="text-align:left; padding:8px 10px; font-size:10px; color:#666; font-weight:600; border-bottom:1px solid #ddd; text-transform:uppercase; letter-spacing:0.4px;">Period</th>
            <th style="text-align:left; padding:8px 10px; font-size:10px; color:#666; font-weight:600; border-bottom:1px solid #ddd; text-transform:uppercase; letter-spacing:0.4px;">Status</th>
            <th style="text-align:left; padding:8px 10px; font-size:10px; color:#666; font-weight:600; border-bottom:1px solid #ddd; text-transform:uppercase; letter-spacing:0.4px;">Notes</th>
        </tr>
    </thead>
    <tbody>
        ${tableRowsHtml || '<tr><td colspan="7" style="padding:20px; text-align:center; color:#bbb; font-size:12px;">No records to display.</td></tr>'}
    </tbody>
</table>

<div style="margin-top:32px; padding-top:12px; border-top:1px solid #eee; display:flex; justify-content:space-between; font-size:10px; color:#ccc;">
    <span>ToolTrace &mdash; Confidential Internal Report</span>
    <span>Printed on ${escapeHtml(today)}</span>
</div>

<div class="no-print" style="text-align:center; margin-top:36px; padding:20px; border-top:1px solid #f0f0f0;">
    <button onclick="window.print()"
        style="background:#2c3e50; color:white; border:none; padding:12px 32px; border-radius:6px; font-size:14px; font-weight:600; cursor:pointer; margin-right:10px; font-family:inherit;">
        🖨&nbsp; Print / Save as PDF
    </button>
    <button onclick="window.close()"
        style="background:white; color:#2c3e50; border:1px solid #ddd; padding:12px 24px; border-radius:6px; font-size:14px; font-weight:600; cursor:pointer; font-family:inherit;">
        Close
    </button>
</div>

</body>
</html>`);
            printWin.document.close();
        }

        // ── Filter tabs ──────────────────────────────────────────────────────
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('.filter-btn').forEach(btn => {
                btn.addEventListener('click', function () {
                    document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
                    this.classList.add('active');
                    const f = this.getAttribute('data-filter');
                    document.querySelectorAll('.activity-row').forEach(row => {
                        const s = row.getAttribute('data-status');
                        row.style.display = (f === 'all' || s === f) ? '' : 'none';
                    });
                });
            });
            refreshFilterCounts();
        });

        function refreshFilterCounts() {
            const rows  = document.querySelectorAll('.activity-row');
            const total = rows.length;
            const counts = { borrowed: 0, returned: 0, overdue: 0 };
            rows.forEach(r => {
                const s = r.getAttribute('data-status');
                if (counts[s] !== undefined) counts[s]++;
            });
            const nav = document.getElementById('filterNav');
            nav.querySelector('[data-filter="all"]').textContent      = `All (${total})`;
            nav.querySelector('[data-filter="borrowed"]').textContent  = `Borrowed (${counts.borrowed})`;
            nav.querySelector('[data-filter="returned"]').textContent  = `Returned (${counts.returned})`;
            nav.querySelector('[data-filter="overdue"]').textContent   = `Overdue (${counts.overdue})`;
        }

        // ── View modal (read-only) ────────────────────────────────────────────
        function openViewModal(requestId) {
            const request = all_requests.find(r => (r.request_id || '') === requestId);
            if (!request) return;
            document.getElementById('viewRequestIdDisplay').value = requestId;
            document.getElementById('viewOrganization').value     = request.organization_name || '—';
            document.getElementById('viewOfficer').value          = request.staff_name || '—';
            document.getElementById('viewItem').value             = request.item_label || '—';
            document.getElementById('viewPurpose').value          = request.purpose || 'None.';
            document.getElementById('viewStatus').value           = request.display_status || 'Borrowed';
            document.getElementById('viewModal').style.display    = 'block';
        }

        function closeViewModal() { document.getElementById('viewModal').style.display = 'none'; }

        window.onclick = function (event) {
            if (event.target === document.getElementById('viewModal')) closeViewModal();
        };
    </script>

</body>
</html>