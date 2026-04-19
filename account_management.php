<?php
/**
 * ToolTrace Admin - Staff & Admin Accounts Management
 * Features: Live Search (STT), Restrict/Restore Actions
 */
declare(strict_types=1);

require_once __DIR__ . '/includes/require_admin_session.php';
require_once __DIR__ . '/includes/admin_demo_users_store.php';

$error = '';
$notice = '';

if (empty($_SESSION['csrf_acct_mgmt']) || !is_string($_SESSION['csrf_acct_mgmt'])) {
    $_SESSION['csrf_acct_mgmt'] = bin2hex(random_bytes(16));
}
$csrf = $_SESSION['csrf_acct_mgmt'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf'] ?? '';
    if (!is_string($token) || !hash_equals($csrf, $token)) {
        $error = 'Invalid session. Please refresh the page and try again.';
    } else {
        $action = $_POST['acct_action'] ?? '';
        $uid = $_POST['user_id'] ?? '';
        if (!is_string($uid) || $uid === '') {
            $error = 'Missing user.';
        } elseif ($action === 'toggle_restrict') {
            $user = tooltrace_admin_find_user_by_id($uid);
            if ($user === null) {
                $error = 'User not found.';
            } else {
                $cur = ($user['status'] ?? 'Active');
                $next = ($cur === 'Active') ? 'Restricted' : 'Active';
                // Update user status in database
                $err = tooltrace_admin_update_user($uid, ['status' => $next]);
                if ($err !== null) {
                    $error = $err;
                } else {
                    $notice = $next === 'Restricted' ? 'Access restricted.' : 'Access restored to active.';
                }
            }
        } else {
            $error = 'Unknown action.';
        }
    }
}

$users = tooltrace_admin_users_list();
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ToolTrace | Staff & Admin Accounts</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Barlow:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/tooltrace-org-theme.css">
    <style>
        * { box-sizing: border-box; }

        .tt-page-title {
            font-family: 'Segoe UI', system-ui, sans-serif;
            font-weight: 700;
            font-size: 2.2rem;
            margin: 0;
            line-height: 1.1;
            color: var(--tt-primary);
            letter-spacing: -0.02em;
        }
        .tt-page-title .tt-accent { color: var(--tt-primary); }
        .tt-page-lead {
            margin: 8px 0 0 0;
            color: var(--tt-muted);
            font-size: 14px;
            line-height: 1.4;
            max-width: 42rem;
        }

        .tt-toolbar { display: flex; gap: 10px; align-items: center; justify-content: space-between; flex-wrap: wrap; padding: 16px 22px; border-bottom: 1px solid #f0f0f0; }
        .tt-search { flex: 1; min-width: 260px; display: flex; gap: 10px; align-items: center; }
        .tt-search input { flex: 1; height: 44px; padding: 12px 18px; border: 1px solid #ddd; border-radius: 999px; font-size: 14px; font: inherit; box-sizing: border-box; }
        .tt-search input:focus { outline: none; border-color: var(--tt-primary); box-shadow: 0 0 0 3px rgba(44,62,80,0.1); }
        .tt-mic-btn { width: 44px; height: 44px; border-radius: 50%; background: #fff; border: 1px solid #ddd; padding: 0; display: flex; align-items: center; justify-content: center; cursor: pointer; }
        .tt-mic-btn.listening { background: #e74c3c; color: #fff; animation: pulse 1s infinite; }

        .table-wrapper { background: var(--tt-white); border-radius: var(--tt-radius); overflow: hidden; box-shadow: var(--tt-shadow); border: 1px solid var(--tt-border); }

        table { 
            width: 100%; 
            border-collapse: collapse;
        }

        th { text-align: left; color: #95a5a6; font-size: 12px; text-transform: uppercase; letter-spacing: 0.04em; padding: 14px 16px; border-bottom: 1px solid #eee; font-weight: 700; background: #fff; }

        td { padding: 16px; border-bottom: 1px solid #f0f0f0; font-size: 14px; }

        tr:last-child td {
            border-bottom: none;
        }

        tr:hover {
            background: #fafafa;
        }

        .user-code {
            font-family: 'Monaco', 'Courier', monospace;
            font-size: 12px;
            color: #7f8c8d;
            background: #f8f9fa;
            padding: 2px 6px;
            border-radius: 3px;
        }

        .user-name { font-weight: 700; color: var(--tt-primary); }

        .badge { 
            padding: 4px 12px; 
            border-radius: 4px; 
            font-size: 11px; 
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .badge-admin { 
            background: #e3f2fd; 
            color: #1565c0;
        }

        .badge-staff { 
            background: #f3e5f5; 
            color: #6a1b9a;
        }

        .status-active { 
            color: #27ae60;
            font-weight: 600;
        }

        .status-restricted { 
            color: #e74c3c;
            font-weight: 600;
        }

        .action-btn { 
            padding: 7px 14px; 
            border-radius: 4px; 
            border: 1px solid #ddd; 
            cursor: pointer; 
            background: white; 
            font-size: 12px; 
            font-weight: 600;
            transition: all 0.2s;
            white-space: nowrap;
        }

        .btn-restrict { 
            border-color: #e74c3c;
            color: #e74c3c;
        }

        .btn-restrict:hover { 
            background: #fdedec; 
            border-color: #c0392b;
        }

        .btn-restore {
            border-color: #27ae60;
            color: #27ae60;
        }

        .btn-restore:hover {
            background: #ecfaf0;
            border-color: #1e8449;
        }

        .inline-form { 
            display: inline;
        }

        @media (max-width: 768px) {
            .tt-search { min-width: 100%; }

            table {
                font-size: 12px;
            }

            td, th {
                padding: 12px 10px;
            }

            .action-btn {
                padding: 6px 10px;
                font-size: 11px;
            }
        }

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
            <h1 class="tt-page-title">Staff &amp; Admin <span class="tt-accent">Accounts</span></h1>
            <p class="tt-page-lead">Manage administrator and maintenance staff accounts.</p>
        </header>

        <?php if ($error !== ''): ?>
            <div class="tt-alert tt-alert-error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>
        <?php if ($notice !== ''): ?>
            <div class="tt-alert tt-alert-ok"><?php echo htmlspecialchars($notice, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>

        <div class="tt-panel tt-panel-flush" style="margin-bottom: 18px;">
            <div class="tt-toolbar">
                <div class="tt-search">
                    <input type="text" id="searchInput" placeholder="Search by name, email or role..." value="<?php echo htmlspecialchars($search, ENT_QUOTES, 'UTF-8'); ?>">
                    <button type="button" id="micBtn" class="tt-mic-btn" title="Voice search" aria-label="Voice search">
                        <i class="fa-solid fa-microphone"></i>
                    </button>
                </div>
            </div>

            <div class="tt-table-wrap tt-table-padded">
                <table>
                    <thead>
                        <tr>
                            <th style="width: 12%">User ID</th>
                            <th style="width: 24%">Account Name</th>
                            <th style="width: 28%">Email</th>
                            <th style="width: 16%">Role</th>
                            <th style="width: 20%">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($users)): ?>
                            <tr>
                                <td colspan="5">
                                    <div class="tt-empty">
                                        <strong>No staff or admin accounts found</strong>
                                        Create an account to see it here.
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($users as $user):
                                if (!is_array($user)) continue;

                                $uid = htmlspecialchars((string) ($user['id'] ?? ''), ENT_QUOTES, 'UTF-8');
                                $userName = htmlspecialchars((string) ($user['name'] ?? ''), ENT_QUOTES, 'UTF-8');
                                $userEmail = htmlspecialchars((string) ($user['email'] ?? ''), ENT_QUOTES, 'UTF-8');
                                $userRole = htmlspecialchars((string) ($user['account_role'] ?? ''), ENT_QUOTES, 'UTF-8');
                                $isActive = strtolower((string) ($user['status'] ?? 'Active')) === 'active';
                                $toggleLabel = $isActive ? 'Restrict' : 'Restore';
                                $toggleClass = $isActive ? 'btn-restrict' : 'btn-restore';

                                $searchText = strtolower($userName . ' ' . $userEmail . ' ' . $userRole);
                                $roleBadgeClass = strtolower($userRole) === 'admin' ? 'badge-admin' : 'badge-staff';
                            ?>
                            <tr data-search="<?php echo htmlspecialchars($searchText); ?>">
                                <td><span class="user-code"><?php echo $uid; ?></span></td>
                                <td><span class="user-name"><?php echo $userName; ?></span></td>
                                <td><?php echo $userEmail; ?></td>
                                <td>
                                    <span class="badge <?php echo $roleBadgeClass; ?>"><?php echo ucfirst($userRole); ?></span>
                                </td>
                                <td>
                                    <form method="post" class="inline-form" data-confirm-title="<?php echo $isActive ? 'Restrict user' : 'Restore user'; ?>" data-confirm-message="<?php echo $isActive ? 'Restrict this user? They will be blocked from signing in until restored.' : 'Restore this user? They will regain access to sign in.'; ?>" data-confirm-ok="<?php echo $isActive ? 'Restrict' : 'Restore'; ?>" data-confirm-danger="<?php echo $isActive ? '1' : '0'; ?>">
                                        <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8'); ?>">
                                        <input type="hidden" name="acct_action" value="toggle_restrict">
                                        <input type="hidden" name="user_id" value="<?php echo $uid; ?>">
                                        <button type="submit" class="action-btn <?php echo $toggleClass; ?>"><?php echo htmlspecialchars($toggleLabel, ENT_QUOTES, 'UTF-8'); ?></button>
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

    <script src="assets/js/tooltrace-speech.js"></script>
    <script>
        tooltraceInitVoiceSearch({ micId: 'micBtn', inputId: 'searchInput' });

        // Live search - real-time filtering
        function filterUsers() {
            const search = document.getElementById('searchInput').value.toLowerCase();
            const rows = document.querySelectorAll('table tbody tr');
            
            rows.forEach(tr => {
                if (tr.querySelector('.empty-state')) return;
                
                const rowSearch = tr.dataset.search || '';
                const matches = search === '' || rowSearch.includes(search);
                tr.style.display = matches ? '' : 'none';
            });
        }

        // Live search on every keystroke
        document.getElementById('searchInput').addEventListener('input', function() {
            filterUsers();
        });

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

            document.querySelectorAll('form[method="post"][data-confirm-title]').forEach(form => {
                form.addEventListener('submit', async (e) => {
                    e.preventDefault();
                    const title = form.getAttribute('data-confirm-title') || 'Confirm';
                    const message = form.getAttribute('data-confirm-message') || 'Are you sure?';
                    const okText = form.getAttribute('data-confirm-ok') || 'OK';
                    const danger = (form.getAttribute('data-confirm-danger') || '0') === '1';
                    const ok = await showConfirm(message, { title, okText, cancelText: 'Cancel', danger });
                    if (!ok) return;
                    showToast('Saving…', 'warning');
                    form.submit();
                }, { capture: true });
            });
        })();
    </script>
</body>
</html>