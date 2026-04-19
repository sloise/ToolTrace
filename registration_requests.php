<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/require_admin_session.php';
require_once __DIR__ . '/includes/registration_requests_store.php';

$error = '';
$notice = '';

if (empty($_SESSION['csrf_reg_req']) || !is_string($_SESSION['csrf_reg_req'])) {
    $_SESSION['csrf_reg_req'] = bin2hex(random_bytes(16));
}
$csrf = $_SESSION['csrf_reg_req'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf'] ?? '';
    if (!is_string($token) || !hash_equals($csrf, $token)) {
        $error = 'Invalid session. Please refresh the page and try again.';
    } else {
        $action = $_POST['reg_action'] ?? '';
        $reason = isset($_POST['rejection_reason']) ? trim((string) $_POST['rejection_reason']) : '';

        $ids = [];
        if (isset($_POST['request_ids']) && is_array($_POST['request_ids'])) {
            foreach ($_POST['request_ids'] as $rid) {
                if (is_string($rid) && $rid !== '') {
                    $ids[] = $rid;
                }
            }
        } else {
            $id = $_POST['request_id'] ?? '';
            if (is_string($id) && $id !== '') {
                $ids[] = $id;
            }
        }

        $ids = array_values(array_unique($ids));
        if (count($ids) === 0) {
            $error = 'No requests selected.';
        } elseif ($action === 'approve') {
            $errs = [];
            foreach ($ids as $id) {
                $err = tooltrace_approve_registration_request($id);
                if ($err !== null) {
                    $errs[] = $err;
                }
            }
            if (count($errs) > 0) {
                $error = implode(' ', array_slice($errs, 0, 3));
            } else {
                $notice = count($ids) === 1
                    ? 'Registration approved. The user can sign in with their email and password.'
                    : 'Registrations approved.';
            }
        } elseif ($action === 'reject') {
            $errs = [];
            foreach ($ids as $id) {
                $err = tooltrace_reject_registration_request($id, $reason);
                if ($err !== null) {
                    $errs[] = $err;
                }
            }
            if (count($errs) > 0) {
                $error = implode(' ', array_slice($errs, 0, 3));
            } else {
                $notice = count($ids) === 1
                    ? 'Registration request rejected and removed.'
                    : 'Registration requests rejected.';
            }
        } else {
            $error = 'Unknown action.';
        }
    }
}

$pending = tooltrace_registration_requests_pending();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ToolTrace | Registration Requests</title>
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
        .tt-table-wrap th:last-child,
        .tt-table-wrap td:last-child { text-align: right; }
        .tt-table-wrap td:last-child .tt-btn-reject { margin-left: 8px; }
        .tt-title-row { display: flex; align-items: baseline; gap: 10px; flex-wrap: wrap; }
        .tt-badge { display: inline-flex; align-items: center; padding: 3px 10px; border-radius: 999px; font-weight: 800; font-size: 11px; letter-spacing: 0.04em; text-transform: uppercase; }
        .tt-badge-warn { background: rgba(241, 196, 15, 0.25); color: #8a6d00; border: 1px solid rgba(241, 196, 15, 0.35); }
        .tt-toolbar { display: flex; gap: 12px; align-items: center; justify-content: space-between; flex-wrap: wrap; padding: 16px 22px; border-bottom: 1px solid #f0f0f0; }
        .tt-search { flex: 1; min-width: 220px; display: flex; }
        .tt-search input { width: 100%; height: 40px; line-height: 40px; padding: 0 14px; border: 1px solid #ddd; border-radius: 999px; font: inherit; box-sizing: border-box; }
        .tt-actions { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
        .tt-actions select, .tt-actions input[type="text"] { height: 40px; line-height: 40px; padding: 0 12px; border: 1px solid #ddd; border-radius: 10px; font: inherit; box-sizing: border-box; }
        .tt-actions button { height: 40px; line-height: 40px; padding: 0 18px; }
        .tt-sortable { cursor: pointer; user-select: none; }
        .tt-sortable[aria-sort="ascending"]::after { content: " ▲"; font-size: 10px; }
        .tt-sortable[aria-sort="descending"]::after { content: " ▼"; font-size: 10px; }

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
            <div class="tt-title-row">
                <h1 class="tt-page-title">Registration <span class="tt-accent">Requests</span></h1>
                <span class="tt-badge tt-badge-warn" title="Pending requests"><?php echo (int) count($pending); ?> pending</span>
            </div>
            <p class="tt-page-lead">Review new account sign-ups from the landing page. Approving creates the account and allows the user to sign in; rejecting removes the request.</p>
        </header>

        <?php if ($error !== ''): ?>
            <div class="tt-alert tt-alert-error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>
        <?php if ($notice !== ''): ?>
            <div class="tt-alert tt-alert-ok"><?php echo htmlspecialchars($notice, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>

        <div class="tt-panel tt-panel-flush">
            <?php if (count($pending) === 0): ?>
                <div class="tt-empty">
                    <strong>No pending registrations</strong>
                    New sign-ups will appear here for you to approve or reject.
                </div>
            <?php else: ?>
                <div class="tt-toolbar">
                    <div class="tt-search">
                        <input id="ttRegSearch" type="text" placeholder="Search name, email, role…" autocomplete="off">
                    </div>
                    <div class="tt-actions">
                        <select id="ttBulkAction">
                            <option value="approve">Approve selected</option>
                            <option value="reject">Reject selected</option>
                        </select>
                        <input id="ttRejectReason" type="text" placeholder="Rejection reason (optional)">
                        <button id="ttApplyBulk" type="button" class="tt-btn tt-btn-dark">Apply</button>
                    </div>
                </div>
                <div class="tt-table-wrap tt-table-padded">
                    <table>
                        <thead>
                            <tr>
                                <th style="width:34px;"><input id="ttCheckAll" type="checkbox" aria-label="Select all"></th>
                                <th class="tt-sortable" data-sort="requested" aria-sort="none">Requested</th>
                                <th class="tt-sortable" data-sort="name" aria-sort="none">Name</th>
                                <th class="tt-sortable" data-sort="email" aria-sort="none">Email</th>
                                <th class="tt-sortable" data-sort="role" aria-sort="none">Account type</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="ttRegBody">
                            <?php foreach ($pending as $row): ?>
                                <?php
                                $rid = htmlspecialchars((string) ($row['request_id'] ?? ($row['id'] ?? '')), ENT_QUOTES, 'UTF-8');
                                $nameRaw = (string) ($row['org_name'] ?? '');
                                $emailRaw = (string) ($row['org_email'] ?? '');
                                $name = htmlspecialchars($nameRaw, ENT_QUOTES, 'UTF-8');
                                $email = htmlspecialchars($emailRaw, ENT_QUOTES, 'UTF-8');
                                $roleKey = (string) ($row['account_role'] ?? 'organization');
                                $roleLabel = htmlspecialchars(tooltrace_account_role_label($roleKey), ENT_QUOTES, 'UTF-8');
                                $at = $row['requested_at'] ?? '';
                                $ts = $at !== '' ? strtotime((string) $at) : 0;
                                $iso = $ts > 0 ? date('c', $ts) : '';
                                $dateStr = $ts > 0 ? date('M j, Y g:i A', $ts) : '—';
                                ?>
                                <tr class="tt-reg-row" data-requested="<?php echo (int) $ts; ?>" data-name="<?php echo htmlspecialchars(strtolower($nameRaw), ENT_QUOTES, 'UTF-8'); ?>" data-email="<?php echo htmlspecialchars(strtolower($emailRaw), ENT_QUOTES, 'UTF-8'); ?>" data-role="<?php echo htmlspecialchars(strtolower($roleKey), ENT_QUOTES, 'UTF-8'); ?>">
                                    <td><input class="tt-row-check" type="checkbox" value="<?php echo $rid; ?>" aria-label="Select"></td>
                                    <td>
                                        <span class="tt-mono tt-rel-time" data-iso="<?php echo htmlspecialchars($iso, ENT_QUOTES, 'UTF-8'); ?>" title="<?php echo htmlspecialchars($dateStr, ENT_QUOTES, 'UTF-8'); ?>">
                                            <?php echo htmlspecialchars($dateStr, ENT_QUOTES, 'UTF-8'); ?>
                                        </span>
                                    </td>
                                    <td><strong><?php echo $name; ?></strong></td>
                                    <td><?php echo $email; ?></td>
                                    <td><span class="tt-pill"><?php echo $roleLabel; ?></span></td>
                                    <td>
                                        <form class="tt-inline-form" method="post" data-confirm="approve">
                                            <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8'); ?>">
                                            <input type="hidden" name="reg_action" value="approve">
                                            <input type="hidden" name="request_id" value="<?php echo $rid; ?>">
                                            <button type="submit" class="tt-btn tt-btn-approve">Approve</button>
                                        </form>
                                        <form class="tt-inline-form" method="post" data-confirm="reject">
                                            <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8'); ?>">
                                            <input type="hidden" name="reg_action" value="reject">
                                            <input type="hidden" name="request_id" value="<?php echo $rid; ?>">
                                            <input type="hidden" class="tt-reason-hidden" name="rejection_reason" value="">
                                            <button type="submit" class="tt-btn tt-btn-reject">Reject</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <form id="ttBulkForm" method="post" style="display:none;">
        <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8'); ?>">
        <input type="hidden" name="reg_action" value="">
        <input type="hidden" name="rejection_reason" value="">
        <div id="ttBulkIds"></div>
    </form>

    <script>
        (function () {
            const body = document.getElementById('ttRegBody');
            if (!body) return;

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

            const search = document.getElementById('ttRegSearch');
            const checkAll = document.getElementById('ttCheckAll');
            const bulkAction = document.getElementById('ttBulkAction');
            const rejectReason = document.getElementById('ttRejectReason');
            const applyBulk = document.getElementById('ttApplyBulk');
            const bulkForm = document.getElementById('ttBulkForm');
            const bulkIds = document.getElementById('ttBulkIds');
            const bulkActionHidden = bulkForm.querySelector('input[name="reg_action"]');
            const bulkReasonHidden = bulkForm.querySelector('input[name="rejection_reason"]');

            function relativeTime(iso) {
                if (!iso) return '—';
                const then = new Date(iso);
                if (Number.isNaN(then.getTime())) return '—';
                const now = new Date();
                const diff = Math.floor((now.getTime() - then.getTime()) / 1000);
                const abs = Math.abs(diff);
                const units = [
                    ['year', 31536000],
                    ['month', 2592000],
                    ['day', 86400],
                    ['hour', 3600],
                    ['minute', 60],
                ];
                for (const [name, seconds] of units) {
                    const v = Math.floor(abs / seconds);
                    if (v >= 1) {
                        return diff >= 0 ? `${v} ${name}${v === 1 ? '' : 's'} ago` : `in ${v} ${name}${v === 1 ? '' : 's'}`;
                    }
                }
                return diff >= 0 ? 'just now' : 'soon';
            }

            function updateTimes() {
                document.querySelectorAll('.tt-rel-time').forEach(el => {
                    const iso = el.getAttribute('data-iso') || '';
                    const full = el.getAttribute('title') || '';
                    el.textContent = iso ? relativeTime(iso) : (full || '—');
                });
            }

            function matches(row, q) {
                if (!q) return true;
                const hay = `${row.dataset.name || ''} ${row.dataset.email || ''} ${row.dataset.role || ''}`;
                return hay.includes(q);
            }

            function applyFilter() {
                const q = (search.value || '').trim().toLowerCase();
                body.querySelectorAll('tr.tt-reg-row').forEach(tr => {
                    tr.style.display = matches(tr, q) ? '' : 'none';
                });
            }

            function selectedIds() {
                return Array.from(document.querySelectorAll('.tt-row-check:checked')).map(cb => cb.value);
            }

            function setAllChecked(v) {
                document.querySelectorAll('.tt-row-check').forEach(cb => { cb.checked = v; });
            }

            function clearSortIndicators(except) {
                document.querySelectorAll('.tt-sortable').forEach(th => {
                    if (th !== except) th.setAttribute('aria-sort', 'none');
                });
            }

            function sortRows(key, dir) {
                const rows = Array.from(body.querySelectorAll('tr.tt-reg-row'));
                rows.sort((a, b) => {
                    let av = a.dataset[key] || '';
                    let bv = b.dataset[key] || '';
                    if (key === 'requested') {
                        av = parseInt(av || '0', 10);
                        bv = parseInt(bv || '0', 10);
                        return dir === 'asc' ? av - bv : bv - av;
                    }
                    return dir === 'asc'
                        ? String(av).localeCompare(String(bv))
                        : String(bv).localeCompare(String(av));
                });
                rows.forEach(r => body.appendChild(r));
            }

            if (search) {
                search.addEventListener('input', applyFilter);
            }
            if (checkAll) {
                checkAll.addEventListener('change', () => setAllChecked(checkAll.checked));
            }

            document.querySelectorAll('form.tt-inline-form').forEach(form => {
                const reasonHidden = form.querySelector('.tt-reason-hidden');
                const action = form.querySelector('input[name="reg_action"]');
                form.addEventListener('submit', async (e) => {
                    e.preventDefault();
                    const act = action ? String(action.value || '') : '';
                    if (reasonHidden && act === 'reject') {
                        reasonHidden.value = (rejectReason?.value || '').trim();
                    }
                    const msg = act === 'approve'
                        ? 'Approve this account? The user can sign in immediately.'
                        : 'Reject this request? This cannot be undone.';
                    const ok = await showConfirm(msg, {
                        title: act === 'approve' ? 'Approve Registration' : 'Reject Registration',
                        okText: act === 'approve' ? 'Approve' : 'Reject',
                        cancelText: 'Cancel',
                        danger: act !== 'approve'
                    });
                    if (!ok) return;
                    form.submit();
                }, { capture: true });
            });

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

            if (applyBulk) {
                applyBulk.addEventListener('click', () => {
                    const action = bulkAction.value;
                    if (!action) {
                        showToast('Choose a bulk action.', 'warning');
                        return;
                    }
                    const ids = selectedIds();
                    if (ids.length === 0) {
                        showToast('Select at least one request.', 'warning');
                        return;
                    }
                    const msg = action === 'approve'
                        ? `Approve ${ids.length} request(s)?` 
                        : `Reject ${ids.length} request(s)? This cannot be undone.`;

                    (async () => {
                        const ok = await showConfirm(msg, {
                            title: action === 'approve' ? 'Approve Selected' : 'Reject Selected',
                            okText: action === 'approve' ? 'Approve' : 'Reject',
                            cancelText: 'Cancel',
                            danger: action !== 'approve'
                        });
                        if (!ok) return;

                        bulkIds.innerHTML = '';
                        ids.forEach(id => {
                            const input = document.createElement('input');
                            input.type = 'hidden';
                            input.name = 'request_ids[]';
                            input.value = id;
                            bulkIds.appendChild(input);
                        });
                        bulkActionHidden.value = action;
                        bulkReasonHidden.value = (rejectReason.value || '').trim();
                        bulkForm.submit();
                    })();
                });
            }

            updateTimes();
        })();
    </script>
</body>
</html>
