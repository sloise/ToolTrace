<?php
/**
 * Organization accounts — PDO-backed (MySQL via XAMPP).
 * Replaces the old JSON-based org_portal_accounts_store.php.
 */
declare(strict_types=1);

require_once __DIR__ . '/config.php';

// ── Read ──────────────────────────────────────────────────────────────────────

function tooltrace_org_portal_accounts_list(): array
{
    $stmt = db()->query("
        SELECT
            o.org_id,
            o.org_name,
            o.org_email,
            o.members,
            o.status,
            a.created_at
        FROM organizations o
        LEFT JOIN accounts a ON LOWER(a.email) = LOWER(o.org_email)
            AND a.account_role = 'organization'
        ORDER BY o.org_name ASC
    ");
    return $stmt->fetchAll();
}

function tooltrace_org_portal_find_by_email(string $email): ?array
{
    $email = strtolower(trim($email));
    $stmt  = db()->prepare("
        SELECT o.*, a.created_at
        FROM organizations o
        LEFT JOIN accounts a ON LOWER(a.email) = LOWER(o.org_email)
            AND a.account_role = 'organization'
        WHERE LOWER(o.org_email) = ?
    ");
    $stmt->execute([$email]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function tooltrace_org_portal_find_by_id(string $orgId): ?array
{
    $stmt = db()->prepare("
        SELECT o.*, a.created_at
        FROM organizations o
        LEFT JOIN accounts a ON LOWER(a.email) = LOWER(o.org_email)
            AND a.account_role = 'organization'
        WHERE o.org_id = ?
    ");
    $stmt->execute([$orgId]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function tooltrace_count_active_orgs(): int
{
    $stmt = db()->query("SELECT COUNT(*) FROM organizations WHERE status = 'Active'");
    return (int) $stmt->fetchColumn();
}

// ── Write ─────────────────────────────────────────────────────────────────────

function tooltrace_org_portal_update_status(string $orgId, string $status): bool
{
    $stmt = db()->prepare("UPDATE organizations SET status = ? WHERE org_id = ?");
    return $stmt->execute([$status, $orgId]);
}

function tooltrace_org_portal_update_members(string $orgId, int $members): bool
{
    $stmt = db()->prepare("UPDATE organizations SET members = ? WHERE org_id = ?");
    return $stmt->execute([$members, $orgId]);
}

function tooltrace_org_portal_delete(string $orgId): bool
{
    $pdo = db();
    // Remove account too
    $org = tooltrace_org_portal_find_by_id($orgId);
    if ($org && !empty($org['org_email'])) {
        $pdo->prepare("DELETE FROM accounts WHERE LOWER(email) = LOWER(?) AND account_role = 'organization'")
            ->execute([$org['org_email']]);
    }
    $stmt = $pdo->prepare("DELETE FROM organizations WHERE org_id = ?");
    return $stmt->execute([$orgId]);
}

// ── Legacy shim ───────────────────────────────────────────────────────────────

function tooltrace_org_portal_accounts_save(array $rows): bool
{
    return true; // DB is source of truth
}

// ── Legacy function name aliases (used in organizations.php) ──────────────────

function tooltrace_org_portal_accounts_all(): array
{
    return tooltrace_org_portal_accounts_list();
}

function tooltrace_org_portal_set_status(string $orgId, string $status): ?string
{
    return tooltrace_org_portal_update_status($orgId, $status) ? null : 'Could not update status.';
}

function tooltrace_org_portal_update_name(string $orgId, string $name): ?string
{
    $name = trim($name);
    if ($name === '') {
        return 'Organization name is required.';
    }

    $pdo = db();
    $chk = $pdo->prepare("SELECT org_id FROM organizations WHERE LOWER(org_name) = ? AND org_id <> ? LIMIT 1");
    $chk->execute([strtolower($name), $orgId]);
    if ($chk->fetch()) {
        return 'An organization with this name already exists.';
    }

    $stmt = $pdo->prepare("UPDATE organizations SET org_name = ? WHERE org_id = ?");
    return $stmt->execute([$name, $orgId]) ? null : 'Could not update name.';
}