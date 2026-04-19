<?php
/**
 * ToolTrace - Item Requests Page
 * Fixed: loads real requests from borrow_requests.json, approve/reject saves back to JSON
 */
declare(strict_types=1);

require_once __DIR__ . '/includes/require_staff_session.php';
require_once __DIR__ . '/includes/borrow_requests_store.php';
require_once __DIR__ . '/includes/inventory_store.php';

$user = [
    'name'     => $_SESSION['user_name'] ?? 'Staff',
    'role'     => $_SESSION['role']      ?? 'Maintenance Staff',
    'initials' => strtoupper(substr((string)($_SESSION['user_name'] ?? 'S'), 0, 2)),
];

$staffEmail = strtolower(trim((string) ($_SESSION['organization_email'] ?? '')));
$staffStmt  = db()->prepare("SELECT staff_id FROM staff WHERE LOWER(email) = ?");
$staffStmt->execute([$staffEmail]);
$staffRow = $staffStmt->fetch();
$staffId  = $staffRow ? (int) $staffRow['staff_id'] : 0;

// --- Handle approve/reject POST ---
$notice = '';
$error  = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['request_id'])) {
    $action     = $_POST['action'];
    $request_id = trim($_POST['request_id']);
    if (in_array($action, ['approved', 'rejected'], true)) {
        $err = tooltrace_borrow_set_request_approval($request_id, $action, $staffId);
        if ($err) {
            $error = $err;
        } else {
            $notice = 'Request ' . htmlspecialchars($request_id, ENT_QUOTES, 'UTF-8') . ' has been ' . $action . '.';
        }
    }
}

// --- Load all requests ---
$all_requests = tooltrace_borrow_requests_all();

// Group multiple item rows under one Request ID
$grouped = [];
foreach ($all_requests as $req) {
    $rid = (string) ($req['request_id'] ?? '');
    if ($rid === '') {
        continue;
    }
    if (!isset($grouped[$rid])) {
        $req['items_list'] = [];
        $req['_item_count'] = 0;
        $req['_any_borrowed'] = false;
        $req['_all_returned'] = true;
        $grouped[$rid] = $req;
    }

    $grouped[$rid]['_item_count']++;

    $rowStatus = strtolower(trim((string) ($req['status'] ?? '')));
    if ($rowStatus === 'borrowed') {
        $grouped[$rid]['_any_borrowed'] = true;
    }
    if ($rowStatus !== 'returned') {
        $grouped[$rid]['_all_returned'] = false;
    }

    $label = tooltrace_borrow_item_label($req);
    if ($label !== '' && !in_array($label, $grouped[$rid]['items_list'], true)) {
        $grouped[$rid]['items_list'][] = $label;
    }
}
foreach ($grouped as &$g) {
    if (!empty($g['_any_borrowed'])) {
        $g['status'] = 'Borrowed';
    } elseif (!empty($g['_all_returned'])) {
        $g['status'] = 'Returned';
    }
    $g['item_label'] = !empty($g['items_list']) ? implode(', ', $g['items_list']) : tooltrace_borrow_item_label($g);
    $g['request_quantity'] = (int) ($g['_item_count'] ?? 0);
    unset($g['_item_count'], $g['_any_borrowed'], $g['_all_returned']);
}
unset($g);

$all_requests = array_values($grouped);

// Sort newest first
usort($all_requests, static function (array $a, array $b): int {
    $da = isset($a['date_requested']) ? strtotime((string)$a['date_requested']) : 0;
    $db = isset($b['date_requested']) ? strtotime((string)$b['date_requested']) : 0;
    return $db <=> $da;
});

// Count by status
$count_all      = count($all_requests);
$count_pending  = 0;
$count_approved = 0;
$count_denied   = 0;
foreach ($all_requests as $req) {
    $s = tooltrace_borrow_request_approval_status($req);
    if ($s === 'pending')        $count_pending++;
    elseif ($s === 'approved')   $count_approved++;
    elseif ($s === 'rejected')   $count_denied++;
}

// Load inventory for the Log New Return modal
$inventory_rows = tooltrace_inventory_load();
$inventory = [];
foreach ($inventory_rows as $row) {
    $inventory[] = [
        'id'          => 'IT' . str_pad((string)(int)($row['id'] ?? 0), 3, '0', STR_PAD_LEFT),
        'name'        => (string)($row['name'] ?? ''),
        'description' => (string)($row['description'] ?? ''),
    ];
}

$all_requests_js = [];
foreach ($all_requests as $req) {
    $items = $req['items_list'] ?? [];
    $req['item_label'] = $items ? implode(', ', $items) : tooltrace_borrow_item_label($req);
    $all_requests_js[] = $req;
}

$request_details_rows_js = [];
foreach (tooltrace_borrow_requests_all() as $req) {
    $request_details_rows_js[] = $req;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ToolTrace | Item Requests</title>
    <!-- FIX: pinned version to avoid silent breaking changes -->
    <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-blue: #2c3e50;
            --accent-yellow: #f1c40f;
            --bg-gray: #f4f7f6;
        }
        body { font-family: 'Segoe UI', Arial, sans-serif; margin: 0; background-color: var(--bg-gray); }
        .welcome-section { margin-bottom: 25px; }
        .welcome-section h1 { margin: 0; font-size: 28px; font-weight: 700; color: #000; }
        .search-container { margin-bottom: 25px; display: flex; align-items: center; }
        .search-input { width: 300px; padding: 10px 15px; border-radius: 20px; border: 1px solid #ddd; background: #fff; outline: none; font-size: 14px; }
        .mic-btn { width: 44px; height: 44px; border-radius: 50%; background: white; border: 1px solid #ddd; padding: 0; display: flex; align-items: center; justify-content: center; cursor: pointer; margin-left: 10px; font-size: 18px; transition: 0.2s; }
        .mic-btn.listening { background: #e74c3c; color: white; animation: pulse 1s infinite; }
        @keyframes pulse { 0%, 100% { box-shadow: 0 0 0 0 rgba(231,76,60,0.7); } 50% { box-shadow: 0 0 0 8px rgba(231,76,60,0.2); } }
        @keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
        .speech-status { font-size: 12px; margin-top: 8px; color: #e74c3c; display: none; }
        .speech-status.active { display: block; }
        .table-card { background: white; padding: 25px; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); border: 1px solid #eee; }
        .table-header { margin-bottom: 20px; }
        .table-header h2 { font-size: 22px; margin: 0 0 15px 0; }
        .tab-nav { display: flex; gap: 20px; border-bottom: 1px solid #eee; }
        .tab-nav a { text-decoration: none; font-size: 14px; font-weight: 600; color: #95a5a6; padding-bottom: 10px; transition: 0.2s; cursor: pointer; }
        .tab-nav a.active { color: var(--primary-blue); border-bottom: 3px solid var(--primary-blue); }
        .tab-count { color: var(--accent-yellow); }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th { text-align: center; color: #333; font-size: 12px; font-weight: 800; text-transform: capitalize; padding: 15px 10px; background-color: #f8f9fa; }
        th:first-child { border-top-left-radius: 8px; text-align: left; }
        th:last-child { border-top-right-radius: 8px; }
        td { padding: 20px 10px; border-bottom: 1px solid #f2f2f2; font-size: 13px; color: #444; text-align: center; }
        td:first-child { text-align: left; font-weight: 600; }
        .org-cell { text-align: center; line-height: 1.4; max-width: 250px; margin: 0 auto; }
        td:nth-child(2) { padding-right: 26px; }
        td:nth-child(3) { padding-left: 26px; }
        .item-cell { text-align: center; line-height: 1.4; max-width: 200px; margin: 0 auto; font-size: 12px; padding-left: 8px; }
        .status-approved { color: #2ecc71; font-weight: 700; }
        .status-pending  { color: #f1c40f;  font-weight: 700; }
        .status-rejected { color: #e74c3c;  font-weight: 700; }
        .action-btns { display: inline-flex; flex-direction: column; gap: 8px; align-items: stretch; }
        .action-btns .btn-req { width: 100%; }
        .btn-req { padding: 6px 12px; border-radius: 6px; font-size: 11px; font-weight: 700; cursor: pointer; border: 1px solid #ddd; background: #f8f9fa; font-family: inherit; }
        .btn-req-approve { background: #ecfaf0; border-color: #2ecc71; color: #1e8449; }
        .btn-req-approve:hover { background: #d5f5e3; }
        .btn-req-reject  { background: #fdedec; border-color: #e74c3c; color: #c0392b; }
        .btn-req-reject:hover  { background: #fadbd8; }
        .btn-req:disabled { opacity: 0.5; cursor: not-allowed; }
        .notice { background: #ecfaf0; border: 1px solid #2ecc71; color: #1e8449; padding: 12px 18px; border-radius: 8px; margin-bottom: 18px; font-size: 14px; font-weight: 600; }
        .error  { background: #fdedec; border: 1px solid #e74c3c; color: #c0392b; padding: 12px 18px; border-radius: 8px; margin-bottom: 18px; font-size: 14px; font-weight: 600; }

        /* Action buttons in header */
        .page-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 25px; }
        .action-group { display: flex; gap: 10px; }
        .btn { padding: 10px 18px; border-radius: 6px; font-size: 13px; font-weight: 600; cursor: pointer; border: none; display: flex; align-items: center; gap: 8px; transition: 0.2s; }
        .btn-primary { background: var(--primary-blue); color: white; }
        .btn-primary:hover { background: #34495e; }
        .btn-outline { background: white; color: var(--primary-blue); border: 1px solid #ddd; }
        .btn-outline:hover { background: #f9f9f9; }

        /* Modals */
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); overflow-y: auto; }
        .modal-content { background: white; margin: 5% auto; padding: 25px; width: 90%; max-width: 450px; border-radius: 10px; max-height: 85vh; overflow-y: auto; box-shadow: 0 4px 20px rgba(0,0,0,0.2); }
        .form-label { font-size: 12px; font-weight: bold; display: block; margin-bottom: 5px; }
        .form-input { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px; box-sizing: border-box; }
        .form-input:read-only { background: #f9f9f9; color: #666; }
        .form-group { margin-bottom: 15px; }

        /* Toast */
        #scanToast { display: none; position: fixed; bottom: 30px; left: 50%; transform: translateX(-50%); background: #2c3e50; color: white; padding: 14px 24px; border-radius: 10px; font-size: 14px; font-weight: 600; z-index: 9999; box-shadow: 0 4px 16px rgba(0,0,0,0.3); max-width: 360px; text-align: center; }
        #scanToast.success { background: #27ae60; }
        #scanToast.warning { background: #e67e22; }

        /* ── Custom confirmation modal ── */
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
    </style>
</head>
<body>

    <?php include 'navbar.php'; ?>

    <div id="scanToast"></div>

    <main class="main-wrapper">

        <section class="page-header">
            <h1>Item Requests</h1>
            <div class="action-group">
                <button class="btn btn-outline" onclick="triggerScan()">
                    <span>&#x1F4F7;</span> Scan QR Code
                </button>
                <button class="btn btn-outline" onclick="triggerItemScan()">
                    <span>&#x1F4F7;</span> Scan Item QR
                </button>
                <button class="btn btn-primary" onclick="openReturnModal()">
                    <span>+</span> Log New Return
                </button>
            </div>
        </section>

        <?php if ($notice): ?>
            <div class="notice"><?php echo htmlspecialchars($notice, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>

        <!-- LOG NEW RETURN MODAL -->
        <div id="returnModal" class="modal">
            <div class="modal-content">
                <h2 style="margin-top:0">Log New Return</h2>
                <p style="font-size:13px; color:#666;">Enter the Request ID to record a return log (alternative to QR scan).</p>
                <hr style="border:0; border-top:1px solid #eee; margin:15px 0;">
                <form id="logForm" onsubmit="handleLogSubmit(event)">
                    <div class="form-group">
                        <label class="form-label">Request ID <span style="color:#e74c3c;">*</span></label>
                        <input type="text" id="requestId" class="form-input" placeholder="e.g. REQ-2026-0003" required>
                    </div>
                    <div class="form-group" style="margin-bottom:20px;">
                        <label class="form-label">Notes</label>
                        <textarea id="notes" class="form-input" style="resize:vertical; min-height:70px;" placeholder="None."></textarea>
                    </div>
                    <div style="display:flex; gap:10px; justify-content:flex-end;">
                        <button type="button" class="btn btn-outline" onclick="closeReturnModal()">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Log Entry</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- QR SCANNER MODAL -->
        <div id="qrModal" class="modal">
            <div class="modal-content" style="max-width:500px;">
                <h2 style="margin-top:0">Scan QR Code</h2>
                <p style="font-size:13px; color:#666;">Scan <strong>pickup ticket QRs</strong> (request pickup/return). For equipment/unit codes, use <strong>Scan Item QR</strong>.</p>
                <div id="qr-reader" style="width:100%;"></div>
                <p id="qr-result" style="font-size:13px; color:#27ae60; margin-top:10px;"></p>
                <div id="qrItemDetails" style="display:none; margin-top:12px; padding:14px; border:1px solid #eee; border-radius:10px; background:#fafafa;"></div>
                <div style="text-align:right; margin-top:15px;">
                    <button class="btn btn-outline" onclick="closeQrModal()">Close</button>
                </div>
            </div>
        </div>

        <!-- ITEM QR SCANNER MODAL (Dedicated to inventory/unit QRs) -->
        <div id="itemQrModal" class="modal">
            <div class="modal-content" style="max-width:500px;">
                <h2 style="margin-top:0">Scan Item QR</h2>
                <p style="font-size:13px; color:#666;">Scan <strong>equipment/unit QRs</strong> (e.g. <code>EQ-PJ-001-U3</code>). Pickup ticket QRs are not handled here.</p>
                <div id="qr-item-reader" style="width:100%;"></div>
                <p id="qr-item-result" style="font-size:13px; color:#27ae60; margin-top:10px;"></p>
                <div id="qrItemDetails2" style="display:none; margin-top:12px; padding:14px; border:1px solid #eee; border-radius:10px; background:#fafafa;"></div>
                <div style="text-align:right; margin-top:15px;">
                    <button class="btn btn-outline" onclick="closeItemQrModal()">Close</button>
                </div>
            </div>
        </div>

        <div id="itemQrDetailsModal" class="modal">
            <div class="modal-content" style="max-width:620px;">
                <div style="display:flex; align-items:flex-start; justify-content:space-between; gap:12px;">
                    <div>
                        <h2 style="margin:0">Equipment Unit Details</h2>
                        <p style="margin:8px 0 0 0; font-size:13px; color:#666;">Scanned equipment unit information.</p>
                    </div>
                    <button class="btn btn-outline" type="button" onclick="closeItemQrDetailsModal()">Close</button>
                </div>
                <div id="itemQrDetailsContent" style="display:none; margin-top:16px; padding:14px; border:1px solid #eee; border-radius:10px; background:#fafafa;"></div>
            </div>
        </div>

        <!-- REQUEST DETAILS MODAL -->
        <div id="requestDetailsModal" class="modal">
            <div class="modal-content" style="max-width:860px;">
                <div style="display:flex; align-items:flex-start; justify-content:space-between; gap:12px;">
                    <div>
                        <h2 style="margin:0">Request Details</h2>
                        <div style="margin-top:6px; font-size:13px; color:#7f8c8d;">Review units and Officer-in-Charge ID before approving.</div>
                    </div>
                    <button class="btn btn-outline" type="button" onclick="closeRequestDetails()">Close</button>
                </div>
                <hr style="border:0; border-top:1px solid #eee; margin:15px 0;">

                <div id="reqDetailsSummary" style="display:grid; grid-template-columns: 1fr 1fr; gap:12px;"></div>

                <div style="margin-top:16px; padding:14px; border:1px solid #eee; border-radius:10px; background:#fafafa;">
                    <div style="display:flex; align-items:center; justify-content:space-between; gap:10px;">
                        <div style="font-weight:800; font-size:12px; color:#2c3e50; text-transform:uppercase; letter-spacing:0.05em;">Officer-in-Charge ID</div>
                        <a id="reqDetailsOicLink" href="#" target="_blank" rel="noopener" style="font-size:13px; font-weight:700; color:#2c3e50; text-decoration:underline;">Open / Preview</a>
                    </div>
                    <div id="reqDetailsOicPreview" style="margin-top:12px;"></div>
                </div>

                <div style="margin-top:18px;">
                    <div style="font-weight:800; font-size:12px; color:#2c3e50; text-transform:uppercase; letter-spacing:0.05em; margin-bottom:10px;">Units</div>
                    <div id="reqDetailsUnits"></div>
                </div>
            </div>
        </div>

        <div class="search-container">
            <input type="text" id="searchInput" class="search-input" placeholder="Search..." onkeyup="filterRequests()">
            <button type="button" class="mic-btn" id="micBtn" title="Search by voice" aria-label="Search by voice">
                <i class="fa-solid fa-microphone"></i>
            </button>
        </div>
        <div class="speech-status" id="speechStatus">
            <i class="fa-solid fa-circle-notch" style="animation: spin 1s linear infinite;"></i> Listening...
        </div>

        <div class="table-card">
            <div class="table-header">
                <h2>Requests</h2>
                <div class="tab-nav">
                    <a href="javascript:void(0)" class="tab-link active" onclick="setStatusFilter('all', this)">All <span class="tab-count">(<?php echo $count_all; ?>)</span></a>
                    <a href="javascript:void(0)" class="tab-link" onclick="setStatusFilter('pending', this)">Pending <span class="tab-count">(<?php echo $count_pending; ?>)</span></a>
                    <a href="javascript:void(0)" class="tab-link" onclick="setStatusFilter('approved', this)">Approved <span class="tab-count">(<?php echo $count_approved; ?>)</span></a>
                    <a href="javascript:void(0)" class="tab-link" onclick="setStatusFilter('rejected', this)">Denied <span class="tab-count">(<?php echo $count_denied; ?>)</span></a>
                </div>
            </div>

            <table>
                <thead>
                    <tr>
                        <th style="width:12%;">Request ID</th>
                        <th style="width:25%;">Organization</th>
                        <th style="width:18%;">Item(s)</th>
                        <th style="width:7%;">Quantity</th>
                        <th style="width:12%;">Date Requested</th>
                        <th style="width:12%;">Borrow Period</th>
                        <th style="width:10%;">Due Date</th>
                        <th style="width:12%;">Approved By</th>
                        <th style="width:9%;">Status</th>
                        <th style="width:12%;">Action</th>
                    </tr>
                </thead>
                <tbody id="requestTableBody">
                    <?php foreach ($all_requests as $req):
                        $status     = tooltrace_borrow_request_approval_status($req);
                        $statusLabel = tooltrace_borrow_request_display_status($req);
                        $request_id = htmlspecialchars($req['request_id'] ?? '—', ENT_QUOTES, 'UTF-8');
                        $org        = htmlspecialchars($req['organization_name'] ?? '—', ENT_QUOTES, 'UTF-8');
                        $item_label = htmlspecialchars((string) ($req['item_label'] ?? tooltrace_borrow_item_label($req)), ENT_QUOTES, 'UTF-8');
                        $requestQty = (int) ($req['request_quantity'] ?? 1);
                        $date_req   = !empty($req['date_requested']) ? date('m/d/Y', strtotime($req['date_requested'])) : '—';
                        $period     = (!empty($req['date_needed']) && !empty($req['return_date']))
                            ? date('m/d', strtotime($req['date_needed'])) . ' - ' . date('m/d', strtotime($req['return_date']))
                            : '—';
                        $dueDate    = !empty($req['due_date']) ? date('m/d/Y', strtotime($req['due_date'])) : '—';
                        $approvedBy = !empty($req['approved_by']) ? (string) $req['approved_by'] : '—';
                        $safe_rid   = htmlspecialchars($req['request_id'] ?? '', ENT_QUOTES, 'UTF-8');
                    ?>
                    <tr data-status="<?php echo $status; ?>">
                        <td><?php echo $request_id; ?></td>
                        <td><div class="org-cell"><?php echo $org; ?></div></td>
                        <td><div class="item-cell"><?php echo $item_label; ?></div></td>
                        <td><?php echo htmlspecialchars((string) $requestQty, ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars($date_req, ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars($period, ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars($dueDate, ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars($approvedBy, ENT_QUOTES, 'UTF-8'); ?></td>
                        <td class="status-<?php echo $status; ?>">
                            <?php echo htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8'); ?>
                        </td>
                        <td>
                            <?php if ($status === 'pending'): ?>
                            <form method="POST" style="display:inline;" id="form-<?php echo $safe_rid; ?>">
                                <input type="hidden" name="request_id" value="<?php echo $safe_rid; ?>">
                                <input type="hidden" name="action" id="action-<?php echo $safe_rid; ?>" value="">
                                <div class="action-btns">
                                    <button type="button" class="btn-req" onclick="openRequestDetails('<?php echo $safe_rid; ?>')">View</button>
                                    <button type="button" class="btn-req btn-req-approve"
                                        onclick="openModal('approve', '<?php echo $safe_rid; ?>')">
                                        Approve
                                    </button>
                                    <button type="button" class="btn-req btn-req-reject"
                                        onclick="openModal('reject', '<?php echo $safe_rid; ?>')">
                                        Reject
                                    </button>
                                </div>
                            </form>
                            <?php else: ?>
                            <span style="color:#95a5a6; font-size:12px;">—</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($all_requests)): ?>
                    <tr><td colspan="10" style="text-align:center; color:#95a5a6; padding:30px;">No requests yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- ── Confirmation modal ── -->
        <div class="modal-overlay" id="confirmModal">
            <div class="modal-box">
                <div class="modal-icon" id="modalIcon"></div>
                <h3 id="modalTitle">Confirm Action</h3>
                <p id="modalMessage">Are you sure?</p>
                <div class="modal-actions">
                    <button class="modal-btn modal-btn-cancel" onclick="closeModal()">Cancel</button>
                    <button class="modal-btn" id="modalConfirmBtn" onclick="submitConfirmed()">Confirm</button>
                </div>
            </div>
        </div>

    </main>

    <script>
        const inventoryData   = <?php echo json_encode($inventory, JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT); ?>;
        const STAFF_USER_NAME = <?php echo json_encode($user['name'], JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP); ?>;
        const all_requests    = <?php echo json_encode($all_requests_js, JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT); ?>;
        const request_details_rows = <?php echo json_encode($request_details_rows_js, JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT); ?>;

        let html5QrCode = null;
        let itemQrCode  = null;
        let currentStatusFilter = 'all';
        let pendingFormId       = null;
        let pendingActionId     = null;

        function getScannerQrBoxSize() {
            const viewport = Math.min(window.innerWidth || 360, window.innerHeight || 640);
            return Math.max(180, Math.min(260, Math.floor(viewport * 0.42)));
        }

        function getItemQrScannerConfig() {
            const size = getScannerQrBoxSize();
            return {
                fps: 24,
                qrbox: { width: size, height: size },
                aspectRatio: 1.3333333,
                disableFlip: true,
                formatsToSupport: [ Html5QrcodeSupportedFormats.QR_CODE ],
                experimentalFeatures: {
                    useBarCodeDetectorIfSupported: true
                },
                videoConstraints: {
                    width: { ideal: 960 },
                    height: { ideal: 540 },
                    focusMode: 'continuous'
                }
            };
        }
        let pendingActionValue  = null;

        function closeRequestDetails() {
            document.getElementById('requestDetailsModal').style.display = 'none';
        }

        function openRequestDetails(requestId) {
            const rows = Array.isArray(request_details_rows) ? request_details_rows.filter(r => String(r.request_id) === String(requestId)) : [];
            if (!rows.length) {
                showToast('Request not found.', 'warning');
                return;
            }

            const first = rows[0];
            const summary = document.getElementById('reqDetailsSummary');
            const safe = (v) => String(v == null ? '' : v);
            const dateFmt = (d) => {
                if (!d) return '—';
                try { return new Date(d).toLocaleDateString(); } catch(e) { return safe(d); }
            };

            summary.innerHTML = `
                <div style="padding:12px; border:1px solid #eee; border-radius:10px; background:#fff;">
                    <div style="font-size:11px; color:#95a5a6; font-weight:800; text-transform:uppercase; letter-spacing:0.05em;">Request ID</div>
                    <div style="font-size:14px; font-weight:800; color:#2c3e50; margin-top:6px;">${escapeHtml(safe(first.request_id))}</div>
                </div>
                <div style="padding:12px; border:1px solid #eee; border-radius:10px; background:#fff;">
                    <div style="font-size:11px; color:#95a5a6; font-weight:800; text-transform:uppercase; letter-spacing:0.05em;">Organization</div>
                    <div style="font-size:14px; font-weight:800; color:#2c3e50; margin-top:6px;">${escapeHtml(safe(first.organization_name || '—'))}</div>
                    <div style="font-size:12px; color:#7f8c8d; margin-top:4px;">${escapeHtml(safe(first.organization_email || ''))}</div>
                </div>
                <div style="padding:12px; border:1px solid #eee; border-radius:10px; background:#fff;">
                    <div style="font-size:11px; color:#95a5a6; font-weight:800; text-transform:uppercase; letter-spacing:0.05em;">Schedule</div>
                    <div style="font-size:13px; font-weight:700; color:#2c3e50; margin-top:6px;">${escapeHtml(dateFmt(first.date_needed))} - ${escapeHtml(dateFmt(first.return_date))}</div>
                </div>
                <div style="padding:12px; border:1px solid #eee; border-radius:10px; background:#fff;">
                    <div style="font-size:11px; color:#95a5a6; font-weight:800; text-transform:uppercase; letter-spacing:0.05em;">Purpose</div>
                    <div style="font-size:13px; font-weight:700; color:#2c3e50; margin-top:6px;">${escapeHtml(safe(first.purpose || '—'))}</div>
                </div>
            `;

            const link = document.getElementById('reqDetailsOicLink');
            const href = 'view_oic_id.php?request_id=' + encodeURIComponent(String(requestId));
            link.setAttribute('href', href);

            const previewWrap = document.getElementById('reqDetailsOicPreview');
            const mime = String(first.oic_id_mime || '');
            if (mime.startsWith('image/')) {
                previewWrap.innerHTML = `<img src="${escapeHtml(href)}" alt="OIC ID" style="max-width:100%; border:1px solid #eee; border-radius:10px;">`;
            } else {
                previewWrap.innerHTML = `<div style="font-size:13px; color:#7f8c8d;">PDF uploaded. Use “Open / Preview”.</div>`;
            }

            const unitsWrap = document.getElementById('reqDetailsUnits');
            unitsWrap.innerHTML = `
                <table style="width:100%; border-collapse:collapse; background:#fff; border:1px solid #eee; border-radius:10px; overflow:hidden; table-layout:fixed;">
                    <thead>
                        <tr>
                            <th style="text-align:left; padding:12px 14px; font-size:12px; background:#f8f9fa; width:36%;">Equipment</th>
                            <th style="text-align:center; padding:12px 14px; font-size:12px; background:#f8f9fa; width:24%;">Equipment ID</th>
                            <th style="text-align:center; padding:12px 14px; font-size:12px; background:#f8f9fa; width:16%;">Unit #</th>
                            <th style="text-align:center; padding:12px 14px; font-size:12px; background:#f8f9fa; width:24%;">Condition</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${rows.map(r => `
                            <tr>
                                <td style="padding:11px 14px; border-top:1px solid #f2f2f2; font-size:13px; text-align:left; vertical-align:middle; word-break:break-word;">${escapeHtml(safe(r.equipment_name || r.item || '—'))}</td>
                                <td style="padding:11px 14px; border-top:1px solid #f2f2f2; font-size:13px; text-align:center; vertical-align:middle; color:#2c3e50;">${escapeHtml(safe(r.equipment_id || '—'))}</td>
                                <td style="padding:11px 14px; border-top:1px solid #f2f2f2; font-size:13px; font-weight:800; color:#2c3e50; text-align:center; vertical-align:middle;">${escapeHtml(safe(r.unit_number || '—'))}</td>
                                <td style="padding:11px 14px; border-top:1px solid #f2f2f2; font-size:13px; text-align:center; vertical-align:middle;">${escapeHtml(safe(r.condition_tag || ''))}</td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
            `;

            document.getElementById('requestDetailsModal').style.display = 'block';
        }

        // ── Approve/Reject confirmation modal ────────────────────────────────
        function openModal(action, requestId) {
            const isApprove    = action === 'approve';
            pendingFormId      = 'form-'   + requestId;
            pendingActionId    = 'action-' + requestId;
            pendingActionValue = isApprove ? 'approved' : 'rejected';

            const icon    = document.getElementById('modalIcon');
            const title   = document.getElementById('modalTitle');
            const message = document.getElementById('modalMessage');
            const btn     = document.getElementById('modalConfirmBtn');

            if (isApprove) {
                icon.className    = 'modal-icon approve';
                icon.textContent  = '✓';
                title.textContent = 'Approve Request';
                message.textContent = 'Are you sure you want to approve request ' + requestId + '? This will allow the organization to proceed with borrowing.';
                btn.className     = 'modal-btn modal-btn-approve';
                btn.textContent   = 'Yes, Approve';
            } else {
                icon.className    = 'modal-icon reject';
                icon.textContent  = '✕';
                title.textContent = 'Reject Request';
                message.textContent = 'Are you sure you want to reject request ' + requestId + '? This action cannot be undone.';
                btn.className     = 'modal-btn modal-btn-reject';
                btn.textContent   = 'Yes, Reject';
            }

            document.getElementById('confirmModal').classList.add('active');
        }

        function closeModal() {
            document.getElementById('confirmModal').classList.remove('active');
            pendingFormId = pendingActionId = pendingActionValue = null;
        }

        function submitConfirmed() {
            if (!pendingFormId) return;
            document.getElementById(pendingActionId).value = pendingActionValue;
            document.getElementById(pendingFormId).submit();
        }

        document.getElementById('confirmModal').addEventListener('click', function(e) {
            if (e.target === this) closeModal();
        });
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') closeModal();
        });

        // ── Tab filter ───────────────────────────────────────────────────────
        function setStatusFilter(status, element) {
            document.querySelectorAll('.tab-link').forEach(tab => tab.classList.remove('active'));
            element.classList.add('active');
            currentStatusFilter = status;
            filterRequests();
        }

        function filterRequests() {
            const query = document.getElementById('searchInput').value.toLowerCase();
            document.querySelectorAll('#requestTableBody tr').forEach(row => {
                const rowText   = row.innerText.toLowerCase();
                const rowStatus = row.getAttribute('data-status');
                const matchesSearch = rowText.includes(query);
                const matchesStatus = (currentStatusFilter === 'all' || rowStatus === currentStatusFilter);
                row.style.display = (matchesSearch && matchesStatus) ? '' : 'none';
            });
        }

        // ── Log New Return modal ─────────────────────────────────────────────
        function openReturnModal()  { document.getElementById('returnModal').style.display = 'block'; }
        function closeReturnModal() { document.getElementById('returnModal').style.display = 'none';  }

        async function handleLogSubmit(event) {
            event.preventDefault();
            const requestId = String(document.getElementById('requestId').value || '').trim();
            const notes     = document.getElementById('notes').value.trim() || 'None.';

            if (!requestId) {
                showToast('Please enter a Request ID.', 'warning');
                return;
            }

            let lastScanType = null;
            try {
                const checkRes = await fetch('api/log_scan.php?query=1&request_id=' + encodeURIComponent(requestId));
                const checkData = await checkRes.json();
                lastScanType = checkData.last_scan_type || null;
            } catch (e) {
                showToast('⚠️ Could not verify scan state. Please try again.', 'warning');
                return;
            }

            if (lastScanType === 'return') {
                showToast('⚠️ ' + requestId + ' was already returned. No further action needed.', 'warning');
                return;
            }

            const now = new Date();
            try {
                const res = await fetch('api/log_scan.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ request_id: requestId, scan_type: 'return', timestamp: now.toISOString(), notes: notes })
                });
                const data = await res.json();
                if (!res.ok || !data || !data.success) {
                    showToast((data && data.error) ? data.error : ('❌ Failed to log return for ' + requestId), 'warning');
                    return;
                }

                if (data.organization_name || data.staff_name) {
                    try {
                        const r2 = await fetch('update_request_details.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ request_id: requestId, organization_name: data.organization_name, staff_name: data.staff_name, updated_by: STAFF_USER_NAME, update_timestamp: now.toISOString() })
                        });
                        const upd = await r2.json();
                        if (!upd || !upd.success) {
                            showToast('⚠️ Return logged, but details update failed for ' + requestId, 'warning');
                        }
                    } catch (e) {
                        showToast('⚠️ Return logged, but details update failed for ' + requestId, 'warning');
                    }
                }

                showToast('✅ Return logged for ' + requestId, 'success');
                document.getElementById('logForm').reset();
                closeReturnModal();
            } catch (e) {
                showToast('❌ Network error while logging return. Please try again.', 'warning');
            }
        }

        // ── QR Scanner ───────────────────────────────────────────────────────
        function triggerScan() {
            document.getElementById('qr-result').textContent = '⏳ Requesting camera access...';
            clearQrItemDetails();
            document.getElementById('qrModal').style.display = 'block';
            html5QrCode = new Html5Qrcode("qr-reader");

            Html5Qrcode.getCameras().then((cameras) => {
                if (!cameras || cameras.length === 0) {
                    throw new Error('No cameras found');
                }

                const pickRearCamera = (cams) => {
                    const scored = cams.map((c, idx) => {
                        const label = String(c.label || '').toLowerCase();
                        let score = 0;
                        if (label.includes('back') || label.includes('rear') || label.includes('environment')) score += 100;
                        if (label.includes('wide')) score += 5;
                        return { cam: c, idx, score };
                    });
                    scored.sort((a, b) => (b.score - a.score) || (b.idx - a.idx));
                    return scored[0].cam;
                };

                const chosen = pickRearCamera(cameras);
                const cameraId = chosen.id;

                return html5QrCode.start(
                    {
                        deviceId: { exact: cameraId }
                    },
                    {
                        fps: 15,
                        qrbox: { width: 320, height: 320 },
                        videoConstraints: {
                            width: { ideal: 1280 },
                            height: { ideal: 720 }
                        }
                    },
                    (decodedText) => {
                        document.getElementById('qr-result').textContent = "Scanned: " + decodedText;
                        clearQrItemDetails();

                        const text = String(decodedText || '');
                        // Always stop the camera after a successful scan.
                        try {
                            html5QrCode.stop().then(() => { html5QrCode = null; }).catch(() => { html5QrCode = null; });
                        } catch (e) { html5QrCode = null; }

                        if (text.startsWith('{')) {
                            // Pickup ticket flow keeps existing behavior: handle and close modal.
                            try {
                                const obj = JSON.parse(text);
                                if (obj.type === 'PICKUP') {
                                    closeQrModal();
                                    handlePickupTicketScan(obj);
                                } else {
                                    showToast('Unknown QR type.', 'warning');
                                }
                            } catch(e) {
                                showToast('Invalid QR data.', 'warning');
                            }
                        } else {
                            showToast('This scanner is for pickup ticket QRs. Use “Scan Item QR” for equipment/unit codes.', 'warning');
                        }
                    },
                    () => {}
                );
            }).catch(err => {
                document.getElementById('qr-result').textContent = '⚠️ Camera unavailable: ' + err;
                setTimeout(() => closeQrModal(), 3000);
            });
        }

        // Dedicated item scanner (inventory/unit QR only)
        function triggerItemScan() {
            document.getElementById('qr-item-result').textContent = '⏳ Requesting camera access...';
            clearQrItemDetails2();
            document.getElementById('itemQrModal').style.display = 'block';
            itemQrCode = new Html5Qrcode("qr-item-reader");

            Html5Qrcode.getCameras().then((cameras) => {
                if (!cameras || cameras.length === 0) {
                    throw new Error('No cameras found');
                }

                const pickRearCamera = (cams) => {
                    const scored = cams.map((c, idx) => {
                        const label = String(c.label || '').toLowerCase();
                        let score = 0;
                        if (label.includes('back') || label.includes('rear') || label.includes('environment')) score += 100;
                        if (label.includes('wide')) score += 5;
                        return { cam: c, idx, score };
                    });
                    scored.sort((a, b) => (b.score - a.score) || (b.idx - a.idx));
                    return scored[0].cam;
                };

                const chosen = pickRearCamera(cameras);
                const cameraId = chosen.id;

                return itemQrCode.start(
                    {
                        deviceId: { exact: cameraId }
                    },
                    getItemQrScannerConfig(),
                    (decodedText) => {
                        document.getElementById('qr-item-result').textContent = "Scanned: " + decodedText;
                        clearQrItemDetails2();

                        const text = String(decodedText || '');
                        // Stop camera after success.
                        try {
                            itemQrCode.stop().then(() => { itemQrCode = null; }).catch(() => { itemQrCode = null; });
                        } catch (e) { itemQrCode = null; }

                        if (text.startsWith('{')) {
                            showToast('This scanner is for equipment/unit QRs. Use “Scan QR Code” for pickup tickets.', 'warning');
                            return;
                        }
                        handleInventoryQrScan2(text);
                    },
                    () => {}
                );
            }).catch(err => {
                document.getElementById('qr-item-result').textContent = '⚠️ Camera unavailable: ' + err;
                setTimeout(() => closeItemQrModal(), 3000);
            });
        }

        async function handleInventoryQrScan(qrValue) {
            try {
                const res = await fetch('api/inventory_lookup.php?q=' + encodeURIComponent(String(qrValue || '')));
                const json = await res.json();
                if (!res.ok || !json || !json.success) {
                    showToast((json && json.error) ? json.error : 'Could not lookup inventory item.', 'warning');
                    return;
                }

                const d = json.data || {};
                d.raw = String(qrValue || '');
                const parts = [];
                parts.push('Item: ' + (d.name || d.equipment_id || '—'));
                if (d.category) parts.push('Category: ' + d.category);
                if (d.unit_number) parts.push('Unit: ' + d.unit_number);
                if (d.condition_tag) parts.push('Condition: ' + d.condition_tag);
                parts.push('Available: ' + String(d.available_count) + '/' + String(d.quantity));

                showToast('✅ ' + parts.join(' | '), 'success');
                renderQrItemDetails(d);
            } catch (e) {
                showToast('❌ Network error while looking up inventory.', 'warning');
            }
        }

        async function handleInventoryQrScan2(qrValue) {
            try {
                const res = await fetch('api/inventory_lookup.php?q=' + encodeURIComponent(String(qrValue || '')));
                const json = await res.json();
                if (!res.ok || !json || !json.success) {
                    showToast((json && json.error) ? json.error : 'Could not lookup inventory item.', 'warning');
                    return;
                }

                const d = json.data || {};
                d.raw = String(qrValue || '');
                renderQrItemDetailsInto(d, 'qrItemDetails2');
                closeItemQrModal();
                openItemQrDetailsModal(d);
            } catch (e) {
                showToast('❌ Network error while looking up inventory.', 'warning');
            }
        }

        function clearQrItemDetails() {
            clearQrItemDetailsInto('qrItemDetails');
        }

        function clearQrItemDetails2() {
            clearQrItemDetailsInto('qrItemDetails2');
        }

        function openItemQrDetailsModal(d) {
            renderQrItemDetailsInto(d, 'itemQrDetailsContent');
            const modal = document.getElementById('itemQrDetailsModal');
            if (modal) modal.style.display = 'block';
        }

        function closeItemQrDetailsModal() {
            const modal = document.getElementById('itemQrDetailsModal');
            if (modal) modal.style.display = 'none';
            clearQrItemDetailsInto('itemQrDetailsContent');
        }

        function renderQrItemDetails(d) {
            renderQrItemDetailsInto(d, 'qrItemDetails');
        }

        function clearQrItemDetailsInto(elementId) {
            const wrap = document.getElementById(elementId);
            if (!wrap) return;
            wrap.style.display = 'none';
            wrap.innerHTML = '';
        }

        function renderQrItemDetailsInto(d, elementId) {
            const wrap = document.getElementById(elementId);
            if (!wrap) return;

            const safe = (v) => String(v == null ? '' : v);
            const equipmentId = safe(d.equipment_id || '—');
            const name = safe(d.name || '—');
            const category = safe(d.category || '—');
            const unitId = d.unit_id != null ? safe(d.unit_id) : '—';
            const unitNumber = d.unit_number != null ? safe(d.unit_number) : '—';
            const condition = safe(d.condition_tag || '—');
            const availableCount = d.available_count != null ? Number(d.available_count) : null;
            const quantity = d.quantity != null ? Number(d.quantity) : null;

            let availabilityLabel = '—';
            let availabilityColor = '#7f8c8d';
            if (Number.isFinite(availableCount) && Number.isFinite(quantity)) {
                availabilityLabel = String(availableCount) + '/' + String(quantity) + ' available';
                if (availableCount <= 0) availabilityColor = '#e74c3c';
                else availabilityColor = '#27ae60';
            }

            wrap.innerHTML = `
                <div style="font-weight:800; font-size:12px; color:#2c3e50; text-transform:uppercase; letter-spacing:0.05em;">Scanned Item Details</div>
                <div style="margin-top:10px; display:grid; grid-template-columns: 1fr 1fr; gap:10px;">
                    <div style="padding:10px; background:#fff; border:1px solid #eee; border-radius:10px;">
                        <div style="font-size:11px; color:#95a5a6; font-weight:800; text-transform:uppercase; letter-spacing:0.05em;">Equipment</div>
                        <div style="font-size:13px; font-weight:800; color:#2c3e50; margin-top:6px;">${escapeHtml(name)}</div>
                        <div style="font-size:12px; color:#7f8c8d; margin-top:4px;">${escapeHtml(equipmentId)}</div>
                    </div>
                    <div style="padding:10px; background:#fff; border:1px solid #eee; border-radius:10px;">
                        <div style="font-size:11px; color:#95a5a6; font-weight:800; text-transform:uppercase; letter-spacing:0.05em;">Availability</div>
                        <div style="font-size:13px; font-weight:900; margin-top:6px; color:${availabilityColor};">${escapeHtml(availabilityLabel)}</div>
                        <div style="font-size:12px; color:#7f8c8d; margin-top:4px;">${escapeHtml(category)}</div>
                    </div>
                    <div style="padding:10px; background:#fff; border:1px solid #eee; border-radius:10px;">
                        <div style="font-size:11px; color:#95a5a6; font-weight:800; text-transform:uppercase; letter-spacing:0.05em;">Unit</div>
                        <div style="font-size:13px; font-weight:900; color:#2c3e50; margin-top:6px;">#${escapeHtml(unitNumber)}</div>
                        <div style="font-size:12px; color:#7f8c8d; margin-top:4px;">Unit ID: ${escapeHtml(unitId)}</div>
                    </div>
                    <div style="padding:10px; background:#fff; border:1px solid #eee; border-radius:10px;">
                        <div style="font-size:11px; color:#95a5a6; font-weight:800; text-transform:uppercase; letter-spacing:0.05em;">Condition</div>
                        <div style="font-size:13px; font-weight:900; color:#2c3e50; margin-top:6px;">${escapeHtml(condition)}</div>
                        <div style="font-size:12px; color:#7f8c8d; margin-top:4px;">Scanned QR: ${escapeHtml(safe(d.raw || ''))}</div>
                    </div>
                </div>
            `;
            wrap.style.display = 'block';
        }

        function escapeHtml(s) {
            return String(s)
                .replace(/&/g, '&amp;').replace(/</g, '&lt;')
                .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
        }

        async function handlePickupTicketScan(data) {
            const requestId  = data.requestId;
            const orgName    = data.orgName;
            const dateNeeded = data.dateNeeded;
            const returnDate = data.returnDate;

            // ✅ FIX: Corrected fetch path (removed 'project-root/' prefix)
            let lastScanType = null;
            try {
                const checkRes = await fetch('api/log_scan.php?query=1&request_id=' + encodeURIComponent(requestId));
                const checkData = await checkRes.json();
                lastScanType = checkData.last_scan_type || null;
            } catch(e) {
                showToast('⚠️ Could not verify scan state. Please try again.', 'warning');
                return;
            }

            if (lastScanType === 'return') {
                showToast('⚠️ ' + requestId + ' was already returned. No further action needed.', 'warning');
                return;
            }

            const now      = new Date();
            const isReturn = lastScanType === 'pickup';
            const scanType = isReturn ? 'return' : 'pickup';

            // ✅ FIX: Corrected fetch path (removed 'project-root/' prefix)
            fetch('api/log_scan.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ request_id: requestId, scan_type: scanType, timestamp: now.toISOString() })
            }).then(r => r.json())
            .then(data => {
                if (data.success && (data.organization_name || data.staff_name)) {
                    fetch('update_request_details.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ request_id: requestId, organization_name: data.organization_name, staff_name: data.staff_name, updated_by: STAFF_USER_NAME, update_timestamp: now.toISOString() })
                    }).then(r2 => r2.json())
                    .then(updateResult => {
                        if (updateResult.success) showToast('✅ Updated info for ' + requestId, 'success');
                        else showToast('❌ Failed to update ' + requestId, 'warning');
                    }).catch(() => showToast('❌ Network error updating ' + requestId, 'warning'));
                }
            }).catch(() => {});

            showToast(
                isReturn
                    ? '✅ Return logged for ' + requestId + ' (' + orgName + ')'
                    : '✅ Pickup logged for '  + requestId + ' (' + orgName + '). Scan again when returned.',
                'success'
            );
        }

        function closeQrModal() {
            clearQrItemDetails();
            try {
                if (html5QrCode) {
                    html5QrCode.stop().then(() => { html5QrCode = null; document.getElementById('qrModal').style.display = 'none'; })
                    .catch(() => { html5QrCode = null; document.getElementById('qrModal').style.display = 'none'; });
                } else { document.getElementById('qrModal').style.display = 'none'; }
            } catch (e) { html5QrCode = null; document.getElementById('qrModal').style.display = 'none'; }
        }

        function closeItemQrModal() {
            clearQrItemDetails2();
            try {
                if (itemQrCode) {
                    itemQrCode.stop().then(() => { itemQrCode = null; document.getElementById('itemQrModal').style.display = 'none'; })
                    .catch(() => { itemQrCode = null; document.getElementById('itemQrModal').style.display = 'none'; });
                } else { document.getElementById('itemQrModal').style.display = 'none'; }
            } catch (e) { itemQrCode = null; document.getElementById('itemQrModal').style.display = 'none'; }
        }

        function showToast(msg, type = 'success') {
            const t = document.getElementById('scanToast');
            t.textContent   = msg;
            t.className     = type;
            t.style.display = 'block';
            setTimeout(() => { t.style.display = 'none'; }, 4000);
        }

        window.onclick = function (event) {
            if (event.target === document.getElementById('returnModal')) closeReturnModal();
            if (event.target === document.getElementById('qrModal'))     closeQrModal();
            if (event.target === document.getElementById('itemQrModal')) closeItemQrModal();
        };
    </script>

    <!-- ✅ FIX: Added missing TTS script (was completely absent — caused ReferenceError) -->
    <script src="assets/js/tooltrace-tts.js"></script>
    <script src="assets/js/tooltrace-speech.js"></script>
    <script>
        tooltraceInitVoiceSearch({
            micId:    'micBtn',
            inputId:  'searchInput',
            statusId: 'speechStatus',
            onText:   function () { filterRequests(); }
        });
    </script>

</body>
</html>