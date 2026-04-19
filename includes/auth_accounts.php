<?php
/**
 * Account authentication — PDO-backed (MySQL via XAMPP).
 * Replaces the old JSON-based auth_accounts.php.
 */
declare(strict_types=1);

require_once __DIR__ . '/config.php';

/** Internal role keys stored in DB */
const TOOLTRACE_ACCOUNT_ROLES = ['organization', 'staff', 'admin'];

// ── Lookup ────────────────────────────────────────────────────────────────────

function tooltrace_accounts_list(): array
{
    $stmt = db()->query("SELECT * FROM accounts ORDER BY created_at DESC");
    return $stmt->fetchAll();
}

function tooltrace_find_account_by_email(string $email): ?array
{
    $email = strtolower(trim($email));
    $stmt  = db()->prepare("SELECT * FROM accounts WHERE LOWER(email) = ?");
    $stmt->execute([$email]);
    $row = $stmt->fetch();
    return $row ?: null;
}

// ── Role helpers ──────────────────────────────────────────────────────────────

function tooltrace_account_stored_role(array $row): string
{
    $r = $row['account_role'] ?? $row['role'] ?? 'organization';
    return in_array($r, TOOLTRACE_ACCOUNT_ROLES, true) ? $r : 'organization';
}

function tooltrace_session_role_for_account(string $stored): string
{
    return match ($stored) {
        'admin' => 'Super Admin',
        'staff' => 'Maintenance Staff',
        default => 'Student',
    };
}

function tooltrace_redirect_for_account_role(string $stored): string
{
    return match ($stored) {
        'admin' => 'admin.php',
        'staff' => 'staff_dashboard.php',
        default => 'org_dashboard.php',
    };
}

// ── Login ─────────────────────────────────────────────────────────────────────

/** @return array|null Account row without password_hash, or null if login fails */
function tooltrace_login_account(string $email, string $password): ?array
{
    $acc = tooltrace_find_account_by_email($email);
    if ($acc === null || empty($acc['password_hash'])) {
        return null;
    }
    if (!password_verify($password, (string) $acc['password_hash'])) {
        return null;
    }
    unset($acc['password_hash']);
$acc['account_role'] = tooltrace_account_stored_role($acc);

// Map DB column 'email' → 'organization_email' so index.php session works
$acc['organization_email'] = $acc['email'] ?? '';

if ($acc['account_role'] === 'organization') {
    $stmt = db()->prepare("SELECT org_name FROM organizations WHERE LOWER(org_email) = ? AND status = 'Active'");
    $stmt->execute([strtolower(trim($email))]);
    $org = $stmt->fetch();
    if ($org) {
        $acc['organization_name'] = $org['org_name'];
    } else {
        return null; // Organization not found or inactive
    }
} else {
    $acc['organization_name'] = $acc['username'] ?? '';
}

return $acc;
}

/** Backward-compatible alias */
function tooltrace_login_org(string $email, string $password): ?array
{
    $acc = tooltrace_login_account($email, $password);
    if ($acc !== null && ($acc['account_role'] ?? 'organization') !== 'organization') {
        return null;
    }
    return $acc;
}

// ── Register ──────────────────────────────────────────────────────────────────

/** @return string|null Error message or null on success */
function tooltrace_register_account(string $name, string $email, string $password, string $accountRole): ?string
{
    $name  = trim($name);
    $email = trim($email);

    if ($name === '' || $email === '' || $password === '') {
        return 'All fields are required.';
    }
    if (!in_array($accountRole, TOOLTRACE_ACCOUNT_ROLES, true)) {
        return 'Invalid account type.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return 'Invalid email address.';
    }
    if (tooltrace_find_account_by_email($email) !== null) {
        return 'An account with this email already exists. Sign in instead.';
    }

    $pdo  = db();
    $publicAccountId = bin2hex(random_bytes(8));
    $stmt = $pdo->prepare("INSERT INTO accounts (account_id, username, email, account_role, password_hash)
                           VALUES (?, ?, ?, ?, ?)");
    $ok = $stmt->execute([
        $publicAccountId,
        $name,
        strtolower($email),
        $accountRole,
        password_hash($password, PASSWORD_DEFAULT),
    ]);

    return $ok ? null : 'Could not create account. Try again.';
}

/** Backward-compatible alias */
function tooltrace_register_org(string $name, string $email, string $password): ?string
{
    return tooltrace_register_account($name, $email, $password, 'organization');
}

// ── Save (legacy shim — no longer needed but keeps old callers happy) ─────────

function tooltrace_accounts_save(array $rows): bool
{
    // DB is source of truth; no-op shim for any legacy caller.
    return true;
}