<?php
/**
 * Super Admin — equipment borrow requests pending approval (data/borrow_requests.json).
 */
declare(strict_types=1);

require_once __DIR__ . '/includes/require_admin_session.php';
require_once __DIR__ . '/includes/borrow_requests_store.php';

$error = '';
$notice = '';

if (empty($_SESSION['csrf_req_mgmt']) || !is_string($_SESSION['csrf_req_mgmt'])) {
    $_SESSION['csrf_req_mgmt'] = bin2hex(random_bytes(16));
}
$csrf = $_SESSION['csrf_req_mgmt'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf'] ?? '';
    if (!is_string($token) || !hash_equals($csrf, $token)) {
        $error = 'Invalid session. Please refresh the page and try again.';
    } else {
        $action = $_POST['borrow_action'] ?? '';
        $id = $_POST['request_id'] ?? '';
        if (!is_string($id) || $id === '') {
            $error = 'Missing request.';
        } elseif ($action === 'approve') {
            $err = tooltrace_borrow_set_request_approval($id, 'approved');
            if ($err !== null) {
                $error = $err;
            } else {
                $notice = 'Request approved. The organization can track this borrow when it is active.';
            }
        } elseif ($action === 'reject') {
            $err = tooltrace_borrow_set_request_approval($id, 'rejected');
            if ($err !== null) {
                $error = $err;
            } else {
                $notice = 'Request rejected.';
            }
        } else {
            $error = 'Unknown action.';
        }
    }
    $_SESSION['csrf_req_mgmt'] = bin2hex(random_bytes(16));
    $csrf = $_SESSION['csrf_req_mgmt'];
}

$pending = tooltrace_borrow_requests_pending_admin_approval();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>ToolTrace | Equipment Requests</title>
    <style>
        :root { --primary: #2c3e50; --accent: #f1c40f; --bg: #f4f7f6; --header-h: 70px; --sidebar-w: 240px; }
        body { font-family: 'Segoe UI', sans-serif; margin: 0; background: var(--bg); }
        .msg { padding: 12px 16px; border-radius: 8px; margin-bottom: 16px; max-width: 960px; }
        .msg-err { background: #ffebee; color: #b71c1c; border: 1px solid #ffcdd2; }
        .msg-ok { background: #e8f5e9; color: #1b5e20; border: 1px solid #c8e6c9; }
        .request-card { background: white; padding: 20px; border-radius: 12px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; border-left: 5px solid var(--accent); flex-wrap: wrap; gap: 12px; }
        .btn-approve { background: #2ecc71; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; font-weight: bold; }
        .btn-reject { background: #e74c3c; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; font-weight: bold; margin-left: 10px; }
        .empty { background: white; padding: 32px; border-radius: 12px; color: #7f8c8d; text-align: center; }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="main-wrapper">
        <h1>Pending borrow requests</h1>
        <p style="color:#7f8c8d; max-width:720px;">New submissions from the equipment catalog are <strong>pending</strong> until you approve or reject them here.</p>

        <?php if ($error !== ''): ?>
            <div class="msg msg-err"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>
        <?php if ($notice !== ''): ?>
            <div class="msg msg-ok"><?php echo htmlspecialchars($notice, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>

        <?php if (count($pending) === 0): ?>
            <div class="empty">
                <strong>No pending requests</strong><br>
                When students submit a borrow from the catalog, it will appear here for approval.
            </div>
        <?php else: ?>
            <?php foreach ($pending as $r): ?>
                <?php
                $rid = (string) ($r['request_id'] ?? '');
                $user = (string) ($r['student_name'] ?? 'Borrower');
                $item = tooltrace_borrow_item_label($r);
                $qty = isset($r['qty']) ? (int) $r['qty'] : 1;
                $date = isset($r['date_requested']) ? (string) $r['date_requested'] : '';
                ?>
                <div class="request-card">
                    <div>
                        <small style="color:#7f8c8d;"><?php echo htmlspecialchars($rid, ENT_QUOTES, 'UTF-8'); ?> | <?php echo htmlspecialchars($date, ENT_QUOTES, 'UTF-8'); ?></small>
                        <h3 style="margin:5px 0;"><?php echo htmlspecialchars($user, ENT_QUOTES, 'UTF-8'); ?></h3>
                        <p style="margin:0; font-size:14px; color:#34495e;">Requesting: <strong><?php echo htmlspecialchars($item, ENT_QUOTES, 'UTF-8'); ?></strong> (Qty: <?php echo $qty; ?>)</p>
                    </div>
                    <div>
                        <form method="post" style="display:inline;">
                            <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8'); ?>">
                            <input type="hidden" name="borrow_action" value="approve">
                            <input type="hidden" name="request_id" value="<?php echo htmlspecialchars($rid, ENT_QUOTES, 'UTF-8'); ?>">
                            <button type="submit" class="btn-approve">Approve</button>
                        </form>
                        <form method="post" style="display:inline;">
                            <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8'); ?>">
                            <input type="hidden" name="borrow_action" value="reject">
                            <input type="hidden" name="request_id" value="<?php echo htmlspecialchars($rid, ENT_QUOTES, 'UTF-8'); ?>">
                            <button type="submit" class="btn-reject">Reject</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

</body>
</html>
