<?php
/**
 * Require organization user session (student org role). Redirect to landing page if not logged in.
 */
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config.php';

$orgEmail = isset($_SESSION['organization_email']) ? trim((string) $_SESSION['organization_email']) : '';
$role = $_SESSION['role'] ?? '';

if ($orgEmail === '' || $role !== 'Student') {
    header('Location: index.php', true, 302);
    exit;
}

// Check if organization is still active
$pdo = db();
$stmt = $pdo->prepare("SELECT status FROM organizations WHERE LOWER(org_email) = ?");
$stmt->execute([strtolower($orgEmail)]);
$org = $stmt->fetch();
if (!$org || $org['status'] !== 'Active') {
    session_destroy();
    header('Location: index.php', true, 302);
    exit;
}
