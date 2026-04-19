<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/require_admin_session.php';
require_once __DIR__ . '/includes/org_portal_accounts_store.php';

$error = '';
$notice = '';

if (empty($_SESSION['csrf_org_mgmt']) || !is_string($_SESSION['csrf_org_mgmt'])) {
    $_SESSION['csrf_org_mgmt'] = bin2hex(random_bytes(16));
}
$csrf = $_SESSION['csrf_org_mgmt'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf'] ?? '';
    if (!is_string($token) || !hash_equals($csrf, $token)) {
        $error = 'Invalid session. Please refresh the page and try again.';
    } else {
        $action = $_POST['org_action'] ?? '';
        $oid = $_POST['org_id'] ?? '';
        if (!is_string($oid) || $oid === '') {
            $error = 'Missing organization.';
        } elseif ($action === 'toggle_restrict') {
            $rows = tooltrace_org_portal_accounts_all();
            $cur = null;
            foreach ($rows as $row) {
                if (is_array($row) && ($row['org_id'] ?? '') === $oid) {
                    $cur = $row['status'] ?? 'Active';
                    break;
                }
            }
            if ($cur === null) {
                $error = 'Organization not found.';
            } else {
                $next = ($cur === 'Active') ? 'Restricted' : 'Active';
                $err = tooltrace_org_portal_set_status($oid, $next);
                if ($err !== null) {
                    $error = $err;
                } else {
                    $notice = $next === 'Restricted' ? 'Organization restricted.' : 'Organization access restored.';
                }
            }
        } elseif ($action === 'save_name') {
            $name = isset($_POST['new_name']) ? trim((string) $_POST['new_name']) : '';
            $err = tooltrace_org_portal_update_name($oid, $name);
            if ($err !== null) {
                $error = $err;
            } else {
                $notice = 'Organization name updated.';
            }
        } else {
            $error = 'Unknown action.';
        }
    }
}

$orgs = tooltrace_org_portal_accounts_all();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ToolTrace | Account Management</title>
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Barlow:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/tooltrace-org-theme.css">
    <style>
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
        .tt-toolbar { display: flex; gap: 12px; align-items: center; justify-content: space-between; flex-wrap: wrap; padding: 16px 22px; border-bottom: 1px solid #f0f0f0; }
        .tt-search { flex: 1; min-width: 220px; display: flex; }
        .tt-search input { width: 100%; height: 40px; line-height: 40px; padding: 0 14px; border: 1px solid #ddd; border-radius: 999px; font: inherit; box-sizing: border-box; }
        .tt-actions { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
        .tt-actions button { height: 40px; padding: 0 14px; }
        .tt-filter { display: inline-flex; gap: 8px; align-items: center; }
        .tt-filter button { border: 1px solid #ddd; background: #fff; color: var(--tt-primary); border-radius: 999px; height: 40px; padding: 0 14px; font: inherit; font-weight: 800; font-size: 11px; text-transform: uppercase; letter-spacing: 0.05em; cursor: pointer; }
        .tt-filter button[aria-pressed="true"] { background: rgba(241, 196, 15, 0.25); border-color: rgba(241, 196, 15, 0.35); }
        .tt-sortable { cursor: pointer; user-select: none; }
        .tt-sortable[aria-sort="ascending"]::after { content: " ▲"; font-size: 10px; }
        .tt-sortable[aria-sort="descending"]::after { content: " ▼"; font-size: 10px; }
        .tt-name-view { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
        .tt-muted { color: var(--tt-muted); }
        .tt-members-zero { color: #8a6d00; background: rgba(241, 196, 15, 0.15); border: 1px solid rgba(241, 196, 15, 0.25); padding: 2px 8px; border-radius: 999px; font-weight: 800; font-size: 12px; }

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
            <h1 class="tt-page-title">Account <span class="tt-accent">Management</span></h1>
            <p class="tt-page-lead">Organizations and access — approve-style actions persist in the database.</p>
        </header>

        <?php if ($error !== ''): ?>
            <div class="tt-alert tt-alert-error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>
        <?php if ($notice !== ''): ?>
            <div class="tt-alert tt-alert-ok"><?php echo htmlspecialchars($notice, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>

        <div class="tt-panel tt-panel-flush">
            <div class="tt-toolbar">
                <div class="tt-search">
                    <input id="ttOrgSearch" type="text" placeholder="Search account name, email, org id…" autocomplete="off">
                </div>
                <div class="tt-actions">
                    <div class="tt-filter" role="group" aria-label="Status filter">
                        <button type="button" class="tt-filter-btn" data-filter="all" aria-pressed="true">All</button>
                        <button type="button" class="tt-filter-btn" data-filter="active" aria-pressed="false">Active</button>
                        <button type="button" class="tt-filter-btn" data-filter="restricted" aria-pressed="false">Restricted</button>
                    </div>
                </div>
            </div>
            <div class="tt-table-wrap tt-table-padded">
                <table>
                    <thead>
                        <tr>
                            <th>Org ID</th>
                            <th class="tt-sortable" data-sort="name" aria-sort="none">Org Name</th>
                            <th class="tt-sortable" data-sort="email" aria-sort="none">Org Email</th>
                            <th class="tt-sortable" data-sort="status" aria-sort="none">Status</th>
                            <th style="text-align:right;">Action</th>
                        </tr>
                    </thead>
                    <tbody id="ttOrgBody">
                        <?php foreach ($orgs as $org): ?>
                            <?php
                            if (!is_array($org)) {
                                continue;
                            }
                            $oid = (string) ($org['org_id'] ?? '');
                            $name = (string) ($org['org_name'] ?? '');
                            $email = (string) ($org['org_email'] ?? '');
                            $members = (int) ($org['members'] ?? 0);
                            $status = (string) ($org['status'] ?? 'Active');
                            $isActive = strtolower($status) === 'active';
                            $toggleLabel = $isActive ? 'Restrict' : 'Restore';
                            $filterLink = 'organizations.php#org=' . rawurlencode($oid);
                            ?>
                            <tr class="tt-org-row" data-oid="<?php echo htmlspecialchars($oid, ENT_QUOTES, 'UTF-8'); ?>" data-name="<?php echo htmlspecialchars(strtolower($name), ENT_QUOTES, 'UTF-8'); ?>" data-email="<?php echo htmlspecialchars(strtolower($email), ENT_QUOTES, 'UTF-8'); ?>" data-members="<?php echo (int) $members; ?>" data-status="<?php echo htmlspecialchars(strtolower($status), ENT_QUOTES, 'UTF-8'); ?>">
                                <td><span class="tt-mono"><?php echo htmlspecialchars($oid, ENT_QUOTES, 'UTF-8'); ?></span></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?></strong>
                                </td>
                                <td class="tt-muted"><?php echo htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?></td>
                                <td>
                                    <?php if ($isActive): ?>
                                        <strong style="color:#2ecc71;"><?php echo htmlspecialchars($status, ENT_QUOTES, 'UTF-8'); ?></strong>
                                    <?php else: ?>
                                        <strong style="color:#e74c3c;"><?php echo htmlspecialchars($status, ENT_QUOTES, 'UTF-8'); ?></strong>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align:right;">
                                    <form method="post" style="display:inline;" data-confirm-title="<?php echo $isActive ? 'Restrict organization' : 'Restore organization'; ?>" data-confirm-message="<?php echo $isActive ? 'Restrict this organization? They will not be able to borrow until restored.' : 'Restore this organization? They will regain borrowing access.'; ?>" data-confirm-ok="<?php echo $isActive ? 'Restrict' : 'Restore'; ?>" data-confirm-danger="<?php echo $isActive ? '1' : '0'; ?>">
                                        <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8'); ?>">
                                        <input type="hidden" name="org_action" value="toggle_restrict">
                                        <input type="hidden" name="org_id" value="<?php echo htmlspecialchars($oid, ENT_QUOTES, 'UTF-8'); ?>">
                                        <button type="submit" class="tt-btn tt-btn-dark" style="margin-left:8px;"><?php echo htmlspecialchars($toggleLabel, ENT_QUOTES, 'UTF-8'); ?></button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        (function () {
            const body = document.getElementById('ttOrgBody');
            if (!body) return;

            const search = document.getElementById('ttOrgSearch');
            const filterBtns = Array.from(document.querySelectorAll('.tt-filter-btn'));
            let activeFilter = 'all';

            function setFilter(next) {
                activeFilter = next;
                filterBtns.forEach(btn => {
                    btn.setAttribute('aria-pressed', btn.dataset.filter === next ? 'true' : 'false');
                });
                applyFilter();
            }

            function matches(tr, q) {
                if (activeFilter !== 'all') {
                    const st = (tr.dataset.status || '').toLowerCase();
                    if (activeFilter === 'active' && st !== 'active') return false;
                    if (activeFilter === 'restricted' && st !== 'restricted') return false;
                }
                if (!q) return true;
                const hay = `${tr.dataset.name || ''} ${tr.dataset.email || ''} ${tr.dataset.oid || ''}`;
                return hay.includes(q);
            }

            function applyFilter() {
                const q = (search?.value || '').trim().toLowerCase();
                body.querySelectorAll('tr.tt-org-row').forEach(tr => {
                    tr.style.display = matches(tr, q) ? '' : 'none';
                });
            }

            function clearSortIndicators(except) {
                document.querySelectorAll('.tt-sortable').forEach(th => {
                    if (th !== except) th.setAttribute('aria-sort', 'none');
                });
            }

            function sortRows(key, dir) {
                const rows = Array.from(body.querySelectorAll('tr.tt-org-row'));
                rows.sort((a, b) => {
                    const av = String(a.dataset[key] || '');
                    const bv = String(b.dataset[key] || '');
                    return dir === 'asc' ? av.localeCompare(bv) : bv.localeCompare(av);
                });
                rows.forEach(r => body.appendChild(r));
            }

            document.querySelectorAll('.tt-sortable').forEach(th => {
                th.addEventListener('click', () => {
                    const key = th.getAttribute('data-sort');
                    const cur = th.getAttribute('aria-sort') || 'none';
                    const next = cur === 'ascending' ? 'descending' : 'ascending';
                    clearSortIndicators(th);
                    th.setAttribute('aria-sort', next);
                    sortRows(key, next === 'ascending' ? 'asc' : 'desc');
                    applyFilter();
                });
            });

            filterBtns.forEach(btn => {
                btn.addEventListener('click', () => setFilter(btn.dataset.filter));
            });
            search?.addEventListener('input', applyFilter);

            applyFilter();
        })();
    </script>

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
