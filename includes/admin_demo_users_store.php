<?php
/**
 * Staff and admin accounts — PDO-backed (MySQL via XAMPP).
 * Replaces the old JSON-based admin_demo_users_store.php.
 */
declare(strict_types=1);

require_once __DIR__ . '/config.php';

// ── Read ──────────────────────────────────────────────────────────────────────

function tooltrace_admin_users_list(): array
{
    $stmt = db()->query("
        SELECT
            a.*, 
            s.staff_id,
            s.position,
            COALESCE(s.status, 'Active') AS status
        FROM accounts a
        LEFT JOIN staff s ON LOWER(s.email) = LOWER(a.email)
        WHERE a.account_role IN ('staff', 'admin')
        ORDER BY a.account_role DESC, a.username ASC
    ");

    $rows = $stmt->fetchAll();
    foreach ($rows as &$row) {
        if (!is_array($row)) {
            continue;
        }
        if (!isset($row['id']) && isset($row['aid'])) {
            $row['id'] = $row['aid'];
        }
        if (!isset($row['name']) && isset($row['username'])) {
            $row['name'] = $row['username'];
        }
    }
    unset($row);
    return $rows;
}

function tooltrace_admin_find_user_by_id(string $id): ?array
{
    $stmt = db()->prepare("
        SELECT a.*, s.staff_id, s.position, COALESCE(s.status, 'Active') AS status
        FROM accounts a
        LEFT JOIN staff s ON LOWER(s.email) = LOWER(a.email)
        WHERE a.id = ?
    ");
    $stmt->execute([(int) $id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function tooltrace_count_staff(): int
{
    $stmt = db()->query("SELECT COUNT(*) FROM accounts WHERE account_role = 'staff'");
    return (int) $stmt->fetchColumn();
}

// ── Write ─────────────────────────────────────────────────────────────────────

/** @return string|null Error or null on success */
function tooltrace_admin_add_user(string $name, string $email, string $password, string $role, string $position = 'Staff'): ?string
{
    $name  = trim($name);
    $email = strtolower(trim($email));

    if ($name === '' || $email === '' || $password === '') {
        return 'All fields are required.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return 'Invalid email address.';
    }

    $pdo  = db();

    // Check duplicate
    $chk = $pdo->prepare("SELECT id FROM accounts WHERE LOWER(email) = ?");
    $chk->execute([$email]);
    if ($chk->fetch()) {
        return 'An account with this email already exists.';
    }

    $publicAccountId = bin2hex(random_bytes(8));

    $stmt = $pdo->prepare("INSERT INTO accounts (account_id, username, email, account_role, password_hash)
                           VALUES (?, ?, ?, ?, ?)");
    $ok = $stmt->execute([
        $publicAccountId,
        $name,
        $email,
        $role,
        password_hash($password, PASSWORD_DEFAULT),
    ]);

    if (!$ok) {
        return 'Could not create account.';
    }

    // Also insert into staff table if role is staff
    if ($role === 'staff') {
        $accountNumId = (int) $pdo->lastInsertId();
        $s = $pdo->prepare("INSERT IGNORE INTO staff (name, email, position, account_id, account_num_id) VALUES (?, ?, ?, ?, ?)");
        $s->execute([$name, $email, $position, $publicAccountId, $accountNumId]);
    }

    return null;
}

/** @return string|null Error or null on success */
function tooltrace_admin_delete_user(string $id): ?string
{
    $pdo  = db();
    $user = tooltrace_admin_find_user_by_id($id);
    if ($user === null) {
        return 'User not found.';
    }

    // Also remove from staff table if applicable
    if (($user['account_role'] ?? '') === 'staff') {
        $pdo->prepare("DELETE FROM staff WHERE LOWER(email) = LOWER(?)")
            ->execute([$user['email']]);
    }

    $stmt = $pdo->prepare("DELETE FROM accounts WHERE id = ?");
    return $stmt->execute([(int) $id]) ? null : 'Could not delete account.';
}

/** @return string|null Error or null on success */
function tooltrace_admin_update_user(string $id, array $fields): ?string
{
    $user = tooltrace_admin_find_user_by_id($id);
    if ($user === null) {
        return 'User not found.';
    }

    if (array_key_exists('status', $fields)) {
        $status = trim((string) $fields['status']);
        if (!in_array($status, ['Active', 'Restricted'], true)) {
            return 'Invalid status.';
        }
        if (($user['account_role'] ?? '') === 'staff') {
            $stmt = db()->prepare("UPDATE staff SET status = ? WHERE LOWER(email) = LOWER(?)");
            return $stmt->execute([$status, (string) ($user['email'] ?? '')]) ? null : 'Could not update user.';
        }
        return null;
    }

    $allowed = ['username', 'email', 'account_role'];
    $sets    = [];
    $vals    = [];
    foreach ($allowed as $col) {
        if (array_key_exists($col, $fields)) {
            $sets[] = "`$col` = ?";
            $vals[] = $fields[$col];
        }
    }
    if (empty($sets)) {
        return 'Nothing to update.';
    }
    $vals[] = $id;
    $stmt = db()->prepare("UPDATE accounts SET " . implode(', ', $sets) . " WHERE id = ?");
    $vals[count($vals) - 1] = (int) $vals[count($vals) - 1];
    return $stmt->execute($vals) ? null : 'Could not update user.';
}

// ── Legacy shim ───────────────────────────────────────────────────────────────

function tooltrace_admin_users_save(array $rows): bool
{
    return true; // DB is source of truth
}