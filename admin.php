<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/require_admin_session.php';
require_once __DIR__ . '/includes/registration_requests_store.php';
require_once __DIR__ . '/includes/auth_accounts.php';
require_once __DIR__ . '/includes/org_portal_accounts_store.php';
require_once __DIR__ . '/includes/admin_demo_users_store.php';

$error = '';
$notice = '';

$last_updated = date('M j, Y g:i A');

if (empty($_SESSION['csrf_admin_dash']) || !is_string($_SESSION['csrf_admin_dash'])) {
    $_SESSION['csrf_admin_dash'] = bin2hex(random_bytes(16));
}
$csrf = $_SESSION['csrf_admin_dash'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf'] ?? '';
    if (!is_string($token) || !hash_equals($csrf, $token)) {
        $error = 'Invalid session. Please refresh the page and try again.';
    } else {
        $action = $_POST['dash_action'] ?? '';
        $id = $_POST['request_id'] ?? '';
        if (!is_string($id) || $id === '') {
            $error = 'Missing request.';
        } elseif ($action === 'approve') {
            $err = tooltrace_approve_registration_request($id);
            if ($err !== null) {
                $error = $err;
            } else {
                $notice = 'Registration approved.';
            }
        } elseif ($action === 'reject') {
            $reason = isset($_POST['rejection_reason']) ? trim((string) $_POST['rejection_reason']) : '';
            $err = tooltrace_reject_registration_request($id, $reason);
            if ($err !== null) {
                $error = $err;
            } else {
                $notice = 'Registration rejected.';
            }
        } else {
            $error = 'Unknown action.';
        }
    }
}

$pending_registrations = tooltrace_count_pending_registration_requests();
$recent_pending = array_slice(tooltrace_registration_requests_pending(), 0, 5);

$orgAccounts = tooltrace_org_portal_accounts_all();
$staffAdminAccounts = tooltrace_admin_users_list();
$count_org = count($orgAccounts);
$count_staff = 0;
$count_admin = 0;
$active_accounts = 0;

foreach ($orgAccounts as $org) {
    if (!is_array($org)) {
        continue;
    }
    if (strtolower((string) ($org['status'] ?? 'active')) === 'active') {
        $active_accounts++;
    }
}

foreach ($staffAdminAccounts as $acc) {
    if (!is_array($acc)) {
        continue;
    }
    $role = strtolower((string) ($acc['account_role'] ?? ''));
    if ($role === 'admin') {
        $count_admin++;
        $active_accounts++;
    } elseif ($role === 'staff') {
        $count_staff++;
        $active_accounts++;
    }
}

$total_users = $count_org + $count_staff + $count_admin;

$max_role = max(1, $count_org + $count_staff + $count_admin);
$p_org = (int) round(($count_org / $max_role) * 100);
$p_staff = (int) round(($count_staff / $max_role) * 100);
$p_admin = max(0, 100 - $p_org - $p_staff);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ToolTrace | Admin Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Barlow:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/tooltrace-org-theme.css">
    <style>
        .tt-page-title {
            font-family: 'Segoe UI', 'Barlow', system-ui, sans-serif;
            font-weight: 700;
            font-size: 2.2rem;
            margin: 0;
            line-height: 1.1;
            color: var(--tt-primary);
            letter-spacing: -0.02em;
        }
        .tt-page-lead {
            margin: 8px 0 0 0;
            color: var(--tt-muted);
            font-size: 14px;
            line-height: 1.4;
            max-width: 42rem;
        }
        .tt-stat-actions { margin-top: 12px; display: flex; gap: 8px; justify-content: center; flex-wrap: wrap; }
        .tt-stat-actions a { text-decoration: none; }
        .tt-stat-actions .tt-btn { padding: 8px 14px; }
        .tt-stat-card.tt-amber { border-color: rgba(241, 196, 15, 0.55); box-shadow: 0 10px 26px rgba(241, 196, 15, 0.12); }
        .tt-role-bar { height: 10px; background: #eef2f7; border-radius: 999px; overflow: hidden; display: flex; margin-top: 14px; }
        .tt-role-seg { height: 100%; }
        .tt-role-org { background: #3498db; }
        .tt-role-staff { background: #2ecc71; }
        .tt-role-admin { background: #9b59b6; }
        .tt-muted-line { margin-top: 12px; font-size: 12px; color: var(--tt-muted); text-align: right; }
        .tt-recent-row { display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap; }
        .tt-recent-meta { display: flex; flex-direction: column; gap: 2px; }
        .tt-recent-meta strong { color: var(--tt-primary); }
        .tt-recent-actions { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
        .tt-recent-actions input[type="text"] { padding: 10px 12px; border: 1px solid #ddd; border-radius: 10px; font: inherit; }
        .tt-stat-card-center { text-align: center; display: flex; flex-direction: column; justify-content: center; }

        /* Toast */
        #uiToast { display:none; position: fixed; bottom: 26px; left: 50%; transform: translateX(-50%); background: #2c3e50; color: #fff; padding: 14px 20px; border-radius: 12px; font-size: 14px; font-weight: 800; z-index: 10000; box-shadow: 0 10px 30px rgba(0,0,0,0.25); max-width: 520px; text-align: center; }
        #uiToast.success { background: #27ae60; }
        #uiToast.warning { background: #e67e22; }
        #uiToast.error { background: #e74c3c; }

        /* Confirm modal */
        #confirmModal { display:none; position: fixed; inset: 0; z-index: 10001; align-items: center; justify-content: center; background: rgba(0,0,0,0.55); backdrop-filter: blur(2px); padding: 16px; }
        #confirmModal.show { display:flex; }
        .confirm-box { width: 100%; max-width: 460px; background: #fff; border-radius: 14px; box-shadow: 0 15px 60px rgba(0,0,0,0.28); overflow: hidden; border: 1px solid rgba(0,0,0,0.06); }
        .confirm-head { padding: 16px 18px; background: var(--tt-primary); color: #fff; font-weight: 900; letter-spacing: 0.2px; }
        .confirm-body { padding: 16px 18px; color: #111827; font-size: 14px; line-height: 1.45; }
        .confirm-actions { display:flex; gap: 10px; justify-content: flex-end; padding: 0 18px 18px 18px; }
        .confirm-btn { border: none; border-radius: 10px; padding: 10px 14px; font-weight: 900; cursor: pointer; }
        .confirm-btn.cancel { background: #f1f5f9; color: #0f172a; }
        .confirm-btn.ok { background: #0f172a; color: #fff; }
        .confirm-btn.danger { background: #e74c3c; color: #fff; }
    </style>
</head>
<body class="tt-app-shell">
    <?php include 'navbar.php'; ?>
    <div id="uiToast" class="success" role="status" aria-live="polite"></div>
    <div id="confirmModal" role="dialog" aria-modal="true" aria-labelledby="confirmTitle">
        <div class="confirm-box">
            <div class="confirm-head" id="confirmTitle">Confirm</div>
            <div class="confirm-body" id="confirmMessage">Are you sure?</div>
            <div class="confirm-actions">
                <button type="button" class="confirm-btn cancel" id="confirmCancelBtn">Cancel</button>
                <button type="button" class="confirm-btn ok" id="confirmOkBtn">OK</button>
            </div>
        </div>
    </div>
    <div class="main-wrapper">
        <header class="tt-page-header">
            <h1 class="tt-page-title">Welcome, Admin!</h1>
            <p class="tt-page-lead">Here is your overview of registrations and accounts.</p>
        </header>

        <?php if ($error !== ''): ?>
            <div class="tt-alert tt-alert-error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>
        <?php if ($notice !== ''): ?>
            <div class="tt-alert tt-alert-ok"><?php echo htmlspecialchars($notice, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>

        <div class="tt-stat-grid">
            <div class="tt-stat-card">
                <h3>Total users</h3>
                <p class="tt-stat-val"><?php echo (int) $total_users; ?></p>
                <div class="tt-role-bar" aria-label="Role breakdown">
                    <span class="tt-role-seg tt-role-org" style="width: <?php echo (int) $p_org; ?>%;" title="Organizations: <?php echo (int) $count_org; ?>"></span>
                    <span class="tt-role-seg tt-role-staff" style="width: <?php echo (int) $p_staff; ?>%;" title="Staff: <?php echo (int) $count_staff; ?>"></span>
                    <span class="tt-role-seg tt-role-admin" style="width: <?php echo (int) $p_admin; ?>%;" title="Admin: <?php echo (int) $count_admin; ?>"></span>
                </div>
                <div class="tt-stat-actions">
                    <a class="tt-btn tt-btn-dark" href="organizations.php">Manage orgs →</a>
                    <a class="tt-btn tt-btn-accent" href="account_management.php">Manage staff &amp; admin →</a>
                </div>
            </div>
            <div class="tt-stat-card <?php echo $pending_registrations > 0 ? 'tt-amber' : ''; ?>">
                <h3>Pending registrations</h3>
                <p class="tt-stat-val"><?php echo (int) $pending_registrations; ?></p>
                <div class="tt-stat-actions">
                    <a class="tt-btn tt-btn-dark" href="registration_requests.php">Review requests →</a>
                </div>
            </div>
            <div class="tt-stat-card tt-stat-card-center">
                <h3>Active accounts</h3>
                <p class="tt-stat-val"><?php echo (int) $active_accounts; ?></p>
            </div>
        </div>

        <div class="tt-panel" style="margin-bottom: 18px;">
            <h2 class="tt-panel-title">Recent activity</h2>
            <p class="tt-page-lead" style="max-width:none;">Last 5 pending registration requests — approve/reject inline.</p>
            <div class="tt-table-wrap" style="margin-top: 10px;">
                <table>
                    <thead>
                        <tr>
                            <th>Requested</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th style="text-align:right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($recent_pending) === 0): ?>
                            <tr><td colspan="5" style="color: var(--tt-muted);">No pending requests.</td></tr>
                        <?php else: ?>
                            <?php foreach ($recent_pending as $row): ?>
                                <?php
                                $rid = (string) ($row['request_id'] ?? ($row['id'] ?? ''));
                                $name = (string) ($row['org_name'] ?? '');
                                $email = (string) ($row['org_email'] ?? '');
                                $roleKey = (string) ($row['account_role'] ?? 'organization');
                                $roleLabel = tooltrace_account_role_label($roleKey);
                                $at = (string) ($row['requested_at'] ?? '');
                                $ts = $at !== '' ? strtotime($at) : 0;
                                $dateStr = $ts > 0 ? date('M j, Y g:i A', $ts) : '—';
                                ?>
                                <tr>
                                    <td><span class="tt-mono" title="<?php echo htmlspecialchars($dateStr, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($dateStr, ENT_QUOTES, 'UTF-8'); ?></span></td>
                                    <td><strong><?php echo htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?></strong></td>
                                    <td><?php echo htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><span class="tt-pill"><?php echo htmlspecialchars($roleLabel, ENT_QUOTES, 'UTF-8'); ?></span></td>
                                    <td style="text-align:right;">
                                        <form class="tt-inline-form" method="post" data-confirm="approve">
                                            <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8'); ?>">
                                            <input type="hidden" name="dash_action" value="approve">
                                            <input type="hidden" name="request_id" value="<?php echo htmlspecialchars($rid, ENT_QUOTES, 'UTF-8'); ?>">
                                            <button type="submit" class="tt-btn tt-btn-approve">Approve</button>
                                        </form>
                                        <form class="tt-inline-form" method="post" data-confirm="reject" style="margin-left:8px;">
                                            <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8'); ?>">
                                            <input type="hidden" name="dash_action" value="reject">
                                            <input type="hidden" name="request_id" value="<?php echo htmlspecialchars($rid, ENT_QUOTES, 'UTF-8'); ?>">
                                            <input type="hidden" name="rejection_reason" value="">
                                            <button type="submit" class="tt-btn tt-btn-reject">Reject</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="tt-muted-line">Last updated: <?php echo htmlspecialchars($last_updated, ENT_QUOTES, 'UTF-8'); ?></div>
    </div>

    <script>
        (function () {
            function showToast(msg, type = 'success') {
                const t = document.getElementById('uiToast');
                if (!t) return;
                t.textContent = String(msg || '');
                t.className = type;
                t.style.display = 'block';
                clearTimeout(t._hideTimer);
                t._hideTimer = setTimeout(() => { t.style.display = 'none'; }, 3500);
            }

            function showConfirm(message, { title = 'Confirm', okText = 'OK', cancelText = 'Cancel', danger = false } = {}) {
                const modal = document.getElementById('confirmModal');
                const titleEl = document.getElementById('confirmTitle');
                const msgEl = document.getElementById('confirmMessage');
                const okBtn = document.getElementById('confirmOkBtn');
                const cancelBtn = document.getElementById('confirmCancelBtn');
                if (!modal || !titleEl || !msgEl || !okBtn || !cancelBtn) {
                    return Promise.resolve(window.confirm(String(message || 'Are you sure?')));
                }

                titleEl.textContent = String(title || 'Confirm');
                msgEl.textContent = String(message || 'Are you sure?');
                okBtn.textContent = String(okText || 'OK');
                cancelBtn.textContent = String(cancelText || 'Cancel');
                okBtn.classList.toggle('danger', Boolean(danger));
                okBtn.classList.toggle('ok', !danger);

                modal.classList.add('show');
                cancelBtn.focus();

                return new Promise((resolve) => {
                    const cleanup = (result) => {
                        modal.classList.remove('show');
                        okBtn.removeEventListener('click', onOk);
                        cancelBtn.removeEventListener('click', onCancel);
                        modal.removeEventListener('click', onBackdrop);
                        document.removeEventListener('keydown', onKey);
                        resolve(result);
                    };

                    const onOk = () => cleanup(true);
                    const onCancel = () => cleanup(false);
                    const onBackdrop = (e) => { if (e.target === modal) cleanup(false); };
                    const onKey = (e) => {
                        if (e.key === 'Escape') cleanup(false);
                        if (e.key === 'Enter') cleanup(true);
                    };

                    okBtn.addEventListener('click', onOk);
                    cancelBtn.addEventListener('click', onCancel);
                    modal.addEventListener('click', onBackdrop);
                    document.addEventListener('keydown', onKey);
                });
            }

            document.querySelectorAll('form.tt-inline-form').forEach(form => {
                form.addEventListener('submit', async (e) => {
                    e.preventDefault();
                    const act = String(form.getAttribute('data-confirm') || '');
                    const ok = await showConfirm(
                        act === 'approve' ? 'Approve this account?' : 'Reject this request?',
                        {
                            title: act === 'approve' ? 'Approve Registration' : 'Reject Registration',
                            okText: act === 'approve' ? 'Approve' : 'Reject',
                            cancelText: 'Cancel',
                            danger: act !== 'approve'
                        }
                    );
                    if (!ok) return;
                    showToast(act === 'approve' ? 'Processing approval…' : 'Processing rejection…', 'warning');
                    form.submit();
                }, { capture: true });
            });
        })();
    </script>
</body>
</html>
