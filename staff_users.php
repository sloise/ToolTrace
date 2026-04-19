<?php
/**
 * ToolTrace - Users (Active Organizations) Page
 * Theme: Consistent Blue/Navy Sidebar & Accents
 */
declare(strict_types=1);

require_once __DIR__ . '/includes/require_staff_session.php';
require_once __DIR__ . '/includes/config.php';

$pdo = db();

// ===== HANDLE POST FIRST (BEFORE ANY OUTPUT) =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'edit_org') {
    $orgId   = trim($_POST['org_id']   ?? '');
    $orgName = trim($_POST['org_name'] ?? '');
    $members = (int) ($_POST['members'] ?? 0);
    $status  = in_array($_POST['status'] ?? '', ['Active', 'Inactive']) ? $_POST['status'] : 'Active';

    if ($orgId !== '' && $orgName !== '') {
        $upd = $pdo->prepare("UPDATE organizations SET org_name = ?, members = ?, status = ? WHERE org_id = ?");
        if ($upd->execute([$orgName, $members, $status, $orgId])) {
            $_SESSION['flash_success'] = 'Organization updated successfully.';
        } else {
            $_SESSION['flash_error'] = 'Could not update organization.';
        }
    } else {
        $_SESSION['flash_error'] = 'Invalid data submitted.';
    }
    header('Location: staff_users.php');
    exit;
}
// ===== END POST HANDLING =====

// Fetch organizations (this was originally at the top)
$stmt = $pdo->query("
    SELECT
        o.org_id,
        o.org_name,
        o.members,
        o.status,
        (
            SELECT COUNT(*) FROM borrow_transactions bt
            WHERE bt.org_id = o.org_id AND bt.status = 'Borrowed'
        ) AS borrowing,
        (
            SELECT COUNT(*) FROM borrow_transactions bt2
            WHERE bt2.org_id = o.org_id
        ) AS total
    FROM organizations o
    ORDER BY o.org_name ASC
");
$organizations = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ToolTrace | Users</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-blue: #2c3e50;
            --bg-gray: #f4f7f6;
            --text-main: #333;
            --success-green: #2ecc71;
            --danger-red: #e74c3c;
        }

        body {
            font-family: 'Segoe UI', sans-serif;
            margin: 0;
            background-color: var(--bg-gray);
        }

        .page-header h1 {
            margin: 0 0 25px 0;
            font-size: 28px;
            color: #000;
        }

        .controls-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 25px;
        }

        .search-wrap {
            display: flex;
            background: white;
            padding: 5px 15px;
            border-radius: 25px;
            border: 1px solid #ddd;
            width: 300px;
        }
        .search-wrap input {
            border: none;
            padding: 10px;
            flex: 1;
            outline: none;
        }

        .mic-btn {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            background: white;
            border: none;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 18px;
            transition: 0.2s;
        }
        .mic-btn.listening {
            background: #e74c3c;
            color: white;
            animation: pulse 1s infinite;
        }

        @keyframes pulse {
            0%, 100% { box-shadow: 0 0 0 0 rgba(231, 76, 60, 0.7); }
            50% { box-shadow: 0 0 0 8px rgba(231, 76, 60, 0.2); }
        }

        .speech-status {
            font-size: 12px;
            margin-top: 8px;
            color: #e74c3c;
            display: none;
        }
        .speech-status.active { display: block; }

        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        .btn-primary {
            background-color: var(--primary-blue);
            color: white;
            padding: 10px 25px;
            border-radius: 12px;
            border: none;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            transition: 0.3s;
        }
        .btn-primary:hover { opacity: 0.9; }

        .content-card {
            background: white;
            padding: 30px;
            border-radius: 20px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        }

        .content-card h2 {
            margin-top: 0;
            margin-bottom: 20px;
            font-size: 22px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            background-color: #f8f9fa;
            padding: 15px;
            text-align: center;
            font-size: 13px;
            color: #555;
            border-top: 1px solid #eee;
            border-bottom: 1px solid #eee;
        }
        th:first-child { text-align: left; }

        td {
            padding: 20px 15px;
            border-bottom: 1px solid #f9f9f9;
            font-size: 14px;
            text-align: center;
        }
        td:first-child {
            text-align: left;
            font-weight: 500;
            line-height: 1.4;
            color: #2c3e50;
        }

        .status-active   { color: var(--success-green); font-weight: bold; }
        .status-inactive { color: var(--danger-red); font-weight: bold; }

        .btn-edit {
            background-color: var(--primary-blue);
            color: white;
            padding: 6px 20px;
            border-radius: 8px;
            border: none;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
        }
        .btn-edit:hover { opacity: 0.92; }

        .modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            z-index: 5000;
            background: rgba(0,0,0,0.45);
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .modal-overlay.is-open { display: flex; }
        .modal-panel {
            background: #fff;
            border-radius: 16px;
            padding: 28px;
            width: 100%;
            max-width: 440px;
            box-shadow: 0 12px 40px rgba(0,0,0,0.12);
        }
        .modal-panel h2 { margin: 0 0 20px; font-size: 20px; color: #2c3e50; }
        .modal-panel label { display: block; font-size: 13px; font-weight: 600; color: #555; margin-bottom: 6px; }
        .modal-panel select,
        .modal-panel input {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
            margin-bottom: 16px;
            box-sizing: border-box;
        }
        .modal-actions { display: flex; gap: 10px; justify-content: flex-end; margin-top: 8px; }
        .btn-modal-secondary {
            background: #f4f7f6;
            border: 1px solid #ddd;
            padding: 10px 18px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
        }
        .btn-modal-primary {
            background: var(--primary-blue);
            color: #fff;
            border: none;
            padding: 10px 18px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
        }

        .flash-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
            padding: 12px 18px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        .flash-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
            padding: 12px 18px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 14px;
        }
    </style>
</head>
<body>

    <?php include 'navbar.php'; ?>

    <main class="main-wrapper">
        <header class="page-header">
            <h1>Users</h1>
        </header>

        <?php if (!empty($_SESSION['flash_success'])): ?>
            <div class="flash-success"><?php echo htmlspecialchars($_SESSION['flash_success']); unset($_SESSION['flash_success']); ?></div>
        <?php endif; ?>
        <?php if (!empty($_SESSION['flash_error'])): ?>
            <div class="flash-error"><?php echo htmlspecialchars($_SESSION['flash_error']); unset($_SESSION['flash_error']); ?></div>
        <?php endif; ?>

        <div class="controls-row">
            <div>
                <div class="search-wrap">
                    <input type="text" id="searchInput" placeholder="Search organizations..." onkeyup="filterUsers()">
                    <button class="mic-btn" id="micBtn" type="button" title="Search by voice">
                        <i class="fa-solid fa-microphone"></i>
                    </button>
                </div>
                <div class="speech-status" id="speechStatus">
                    <i class="fa-solid fa-circle-notch" style="animation: spin 1s linear infinite;"></i> Listening...
                </div>
            </div>
        </div>

        <div class="content-card">
            <h2>Active Organization</h2>
            <table>
                <thead>
                    <tr>
                        <th style="width: 35%;">Organization Name</th>
                        <th style="width: 10%;">Members</th>
                        <th style="width: 15%;">Currently Borrowing</th>
                        <th style="width: 15%;">Total Borrowed</th>
                        <th style="width: 10%;">Status</th>
                        <th style="width: 10%;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($organizations as $org): ?>
                    <tr data-org-id="<?php echo htmlspecialchars($org['org_id']); ?>"
                        data-name="<?php echo htmlspecialchars(strtolower($org['org_name'])); ?>">
                        <td class="col-org-name"><?php echo htmlspecialchars($org['org_name']); ?></td>
                        <td class="col-members"><?php echo (int) $org['members']; ?></td>
                        <td><?php echo (int) $org['borrowing']; ?></td>
                        <td><?php echo (int) $org['total']; ?></td>
                        <td class="col-status status-<?php echo strtolower(htmlspecialchars($org['status'])); ?>">
                            <?php echo htmlspecialchars($org['status']); ?>
                        </td>
                        <td>
                            <button type="button" class="btn-edit" onclick="openEditOrg(this)">Edit</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>

    <!-- Edit Modal -->
    <div class="modal-overlay" id="editOrgModal" role="dialog" aria-modal="true" aria-labelledby="editOrgTitle">
        <div class="modal-panel">
            <h2 id="editOrgTitle">Edit Organization</h2>
            <form method="POST" action="staff_users.php">
                <input type="hidden" name="action" value="edit_org">
                <input type="hidden" name="org_id" id="editOrgId">

                <label for="editOrgName">Organization Name</label>
                <input type="text" id="editOrgName" name="org_name" autocomplete="organization" required>

                <label for="editOrgMembers">Members</label>
                <input type="number" id="editOrgMembers" name="members" min="0" step="1" required>

                <label for="editOrgStatus">Status</label>
                <select id="editOrgStatus" name="status">
                    <option value="Active">Active</option>
                    <option value="Inactive">Inactive</option>
                </select>

                <div class="modal-actions">
                    <button type="button" class="btn-modal-secondary" onclick="closeEditOrg()">Cancel</button>
                    <button type="submit" class="btn-modal-primary">Save</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        let editOrgRow = null;

        function openEditOrg(btn) {
            editOrgRow = btn.closest('tr');
            if (!editOrgRow) return;
            document.getElementById('editOrgId').value      = editOrgRow.dataset.orgId;
            document.getElementById('editOrgName').value    = editOrgRow.querySelector('.col-org-name').textContent.trim();
            document.getElementById('editOrgMembers').value = editOrgRow.querySelector('.col-members').textContent.trim();
            const status = editOrgRow.querySelector('.col-status').textContent.trim();
            document.getElementById('editOrgStatus').value  = status;
            document.getElementById('editOrgModal').classList.add('is-open');
        }

        function closeEditOrg() {
            document.getElementById('editOrgModal').classList.remove('is-open');
            editOrgRow = null;
        }

        document.getElementById('editOrgModal').addEventListener('click', function (e) {
            if (e.target === this) closeEditOrg();
        });

        function filterUsers() {
            const query = document.getElementById('searchInput').value.toLowerCase();
            document.querySelectorAll('tbody tr').forEach(row => {
                row.style.display = row.getAttribute('data-name').includes(query) ? '' : 'none';
            });
        }
    </script>
    <script src="assets/js/tooltrace-speech.js"></script>
    <script>
        tooltraceInitVoiceSearch({
            micId: 'micBtn',
            inputId: 'searchInput',
            statusId: 'speechStatus',
            onText: function () { filterUsers(); }
        });
    </script>

</body>
</html>