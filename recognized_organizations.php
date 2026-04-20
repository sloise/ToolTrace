<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/require_admin_session.php';
require_once __DIR__ . '/includes/config.php';

$error = '';
$notice = '';

if (empty($_SESSION['csrf_rec_orgs']) || !is_string($_SESSION['csrf_rec_orgs'])) {
    $_SESSION['csrf_rec_orgs'] = bin2hex(random_bytes(16));
}
$csrf = $_SESSION['csrf_rec_orgs'];

$pdo = db();

try {
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS recognized_organizations ("
        . " org_id INT AUTO_INCREMENT PRIMARY KEY,"
        . " org_name VARCHAR(255) NOT NULL,"
        . " acronym VARCHAR(50) NULL,"
        . " org_email VARCHAR(100) NULL,"
        . " org_type VARCHAR(100) NULL,"
        . " UNIQUE KEY uq_recognized_org_name (org_name),"
        . " KEY idx_recognized_org_acronym (acronym)"
        . ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );
} catch (Throwable $e) {
}

try {
    $pdo->exec("ALTER TABLE recognized_organizations ADD COLUMN org_email VARCHAR(100) NULL");
} catch (Throwable $e) {
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf'] ?? '';
    if (!is_string($token) || !hash_equals($csrf, $token)) {
        $error = 'Invalid session. Please refresh the page and try again.';
    } else {
        $action = isset($_POST['action']) ? (string) $_POST['action'] : '';
        if ($action === 'add') {
            $name = isset($_POST['org_name']) ? trim((string) $_POST['org_name']) : '';
            $acronym = isset($_POST['acronym']) ? trim((string) $_POST['acronym']) : '';
            $email = isset($_POST['org_email']) ? trim((string) $_POST['org_email']) : '';

            if ($name === '') {
                $error = 'Organization name is required.';
            } else {
                $stmt = $pdo->prepare("INSERT INTO recognized_organizations (org_name, acronym, org_email) VALUES (?, ?, ?)");
                try {
                    $ok = $stmt->execute([
                        $name,
                        ($acronym !== '' ? $acronym : null),
                        ($email !== '' ? $email : null),
                    ]);
                    if ($ok) {
                        $notice = 'Organization record added.';
                    } else {
                        $error = 'Could not add organization record.';
                    }
                } catch (Throwable $e) {
                    $msg = $e->getMessage();
                    if (stripos($msg, 'Duplicate') !== false) {
                        $error = 'This organization is already in the records.';
                    } else {
                        $error = 'Could not add organization record.';
                    }
                }
            }
        } elseif ($action === 'delete') {
            $id = isset($_POST['org_id']) ? (int) $_POST['org_id'] : 0;
            if ($id <= 0) {
                $error = 'Invalid organization.';
            } else {
                $stmt = $pdo->prepare("DELETE FROM recognized_organizations WHERE org_id = ?");
                $ok = $stmt->execute([$id]);
                if ($ok) {
                    $notice = 'Organization record deleted.';
                } else {
                    $error = 'Could not delete organization record.';
                }
            }
        } elseif ($action === 'import_current') {
            try {
                $pdo->exec(
                    "INSERT IGNORE INTO recognized_organizations (org_name, acronym, org_email) "
                    . "SELECT org_name, NULL, org_email FROM organizations"
                );
                $notice = 'Imported current organizations into the records.';
            } catch (Throwable $e) {
                $error = 'Could not import organizations.';
            }
        } else {
            $error = 'Unknown action.';
        }
    }
}

$rows = [];
try {
$rows = $pdo->query("SELECT recognized_org_id as org_id, org_name, acronym, org_email FROM recognized_organizations ORDER BY org_name ASC")->fetchAll(PDO::FETCH_ASSOC);} catch (Throwable $e) {
    $rows = [];
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ToolTrace | Organization Records</title>
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Barlow:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/tooltrace-org-theme.css">
    <style>
        .tt-page-title { font-family: 'Segoe UI', system-ui, sans-serif; font-weight: 800; font-size: 2.2rem; margin: 0; line-height: 1.1; color: var(--tt-primary); letter-spacing: -0.02em; }
        .tt-page-lead { margin: 8px 0 0 0; color: var(--tt-muted); font-size: 14px; line-height: 1.4; max-width: 46rem; }
        .tt-panel { background: #fff; border: 1px solid #eee; border-radius: 16px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.06); }
        .tt-panel-head { padding: 16px 20px; border-bottom: 1px solid #f0f0f0; display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap; }
        .tt-panel-title { margin: 0; font-size: 15px; font-weight: 900; color: #0f172a; }
        .tt-form { display: grid; grid-template-columns: 1.3fr 0.7fr 1fr auto; gap: 10px; align-items: end; padding: 16px 20px; }
        .tt-form label { display: block; font-size: 12px; font-weight: 900; color: #334155; margin-bottom: 6px; }
        .tt-form input { width: 100%; height: 40px; line-height: 40px; padding: 0 12px; border: 1px solid #ddd; border-radius: 10px; box-sizing: border-box; font: inherit; }
        .tt-form .tt-btn { height: 40px; line-height: 40px; padding: 0 16px; border-radius: 10px; border: none; cursor: pointer; font-weight: 900; }
        .tt-btn-dark { background: #0f172a; color: #fff; }
        .tt-btn-dark:hover { opacity: 0.92; }
        .tt-btn-outline { background: #fff; border: 1px solid #ddd; color: #0f172a; }
        .tt-btn-outline:hover { background: #f8fafc; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px 14px; border-bottom: 1px solid #f1f5f9; text-align: left; font-size: 13px; }
        th { background: #f8fafc; color: #334155; font-size: 12px; text-transform: uppercase; letter-spacing: 0.04em; }
        td:last-child, th:last-child { text-align: right; }
        .tt-pill { display: inline-flex; align-items: center; padding: 4px 10px; border-radius: 999px; border: 1px solid rgba(0,0,0,0.08); background: rgba(241, 196, 15, 0.15); color: #6b4f00; font-weight: 900; font-size: 11px; letter-spacing: 0.04em; text-transform: uppercase; }
        .tt-actions { display: flex; gap: 10px; align-items: center; }
        .tt-actions form { margin: 0; }
        .tt-danger { background: #e74c3c; color: #fff; border: none; }
        .tt-danger:hover { opacity: 0.92; }
        @media (max-width: 900px) { .tt-form { grid-template-columns: 1fr; } }
    </style>
</head>
<body class="tt-app-shell">
    <?php include 'navbar.php'; ?>

    <div class="main-wrapper">
        <header class="tt-page-header">
            <h1 class="tt-page-title">Organization <span class="tt-accent">Records</span></h1>
            <p class="tt-page-lead">This list is used to validate organization registration requests. If an organization is not listed here, registration will be blocked until it is added.</p>
        </header>

        <?php if ($error !== ''): ?>
            <div class="tt-alert tt-alert-error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>
        <?php if ($notice !== ''): ?>
            <div class="tt-alert tt-alert-ok"><?php echo htmlspecialchars($notice, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>

        <section class="tt-panel" style="margin-bottom: 16px;">
            <div class="tt-panel-head">
                <h2 class="tt-panel-title">Add organization record</h2>
                <div class="tt-actions">
                    <form method="POST">
                        <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8'); ?>">
                        <input type="hidden" name="action" value="import_current">
                        <button type="submit" class="tt-form-btn tt-btn-outline" style="height:40px; padding:0 14px; border-radius:10px; font-weight:900;">Import current orgs</button>
                    </form>
                    <span class="tt-pill"><?php echo (int) count($rows); ?> total</span>
                </div>
            </div>

            <form method="POST" class="tt-form">
                <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="action" value="add">
                <div>
                    <label for="org_name">Organization name</label>
                    <input id="org_name" name="org_name" type="text" autocomplete="organization" required>
                </div>
                <div>
                    <label for="acronym">Acronym (optional)</label>
                    <input id="acronym" name="acronym" type="text" autocomplete="off">
                </div>
                <div>
                    <label for="org_email">Email (optional)</label>
                    <input id="org_email" name="org_email" type="email" autocomplete="email">
                </div>
                <div>
                    <button class="tt-btn tt-btn-dark" type="submit">Add</button>
                </div>
            </form>
        </section>

        <section class="tt-panel">
            <div class="tt-panel-head">
                <h2 class="tt-panel-title">Recognized organizations</h2>
            </div>
            <div style="overflow:auto;">
                <table>
                    <thead>
                        <tr>
                            <th style="width:50%">Organization</th>
                            <th style="width:15%">Acronym</th>
                            <th style="width:25%">Email</th>
                            <th style="width:10%">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($rows) === 0): ?>
                            <tr><td colspan="4" style="color:#64748b; padding:16px 14px;">No records yet. Add one above.</td></tr>
                        <?php else: ?>
                            <?php foreach ($rows as $r): ?>
                                <tr>
                                    <td style="font-weight:900; color:#0f172a;">
                                        <?php echo htmlspecialchars((string) ($r['org_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
                                    </td>
                                    <td><?php echo htmlspecialchars((string) ($r['acronym'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars((string) ($r['org_email'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td>
                                        <form method="POST" onsubmit="return confirm('Delete this record?');" style="display:inline;">
                                            <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8'); ?>">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="org_id" value="<?php echo (int) ($r['org_id'] ?? 0); ?>">
                                            <button type="submit" class="tt-btn tt-danger" style="height:34px; padding:0 12px; border-radius:10px; font-weight:900;">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</body>
</html>
