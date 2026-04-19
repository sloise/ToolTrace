<?php
/**
 * Registration requests — PDO-backed (MySQL via XAMPP).
 * Replaces the old JSON-based registration_requests_store.php.
 */
declare(strict_types=1);

require_once __DIR__ . '/auth_accounts.php';
require_once __DIR__ . '/../mailer.php';

function tooltrace_registration_mail_log(string $line): void
{
    $logPath = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'registration_mail_log.txt';
    $dir = dirname($logPath);
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
    @file_put_contents($logPath, $line . PHP_EOL, FILE_APPEND);
}

function tooltrace_registration_mail_send_best_effort(string $to, string $subject, string $textBody, string $htmlBody): void
{
    [$sent, $err] = tooltrace_send_mail($to, $subject, $textBody, $htmlBody);
    $log = '[' . date('Y-m-d H:i:s') . '] ' . $to . ' | ' . $subject . ' | ' . ($sent ? 'SENT' : 'FAILED');
    if (!$sent && $err) {
        $log .= ' | ' . $err;
    }
    tooltrace_registration_mail_log($log);
}

// ── Read ──────────────────────────────────────────────────────────────────────

function tooltrace_registration_requests_pending(): array
{
    $stmt = db()->query("SELECT * FROM registration_requests WHERE status = 'pending' ORDER BY requested_at DESC");
    return $stmt->fetchAll();
}

function tooltrace_find_pending_registration_by_email(string $email): ?array
{
    $email = strtolower(trim($email));
    $stmt  = db()->prepare("SELECT * FROM registration_requests WHERE LOWER(org_email) = ? AND status = 'pending'");
    $stmt->execute([$email]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function tooltrace_registration_request_by_id(string $id): ?array
{
    $stmt = db()->prepare("SELECT * FROM registration_requests WHERE request_id = ? AND status = 'pending'");
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function tooltrace_count_pending_registration_requests(): int
{
    $stmt = db()->query("SELECT COUNT(*) FROM registration_requests WHERE status = 'pending'");
    return (int) $stmt->fetchColumn();
}

// ── Role label helper ─────────────────────────────────────────────────────────

function tooltrace_account_role_label(string $role): string
{
    return match ($role) {
        'admin'  => 'Administrator',
        'staff'  => 'Maintenance Staff',
        default  => 'Organization',
    };
}

// ── Submit a new registration request ────────────────────────────────────────

/** @return string|null Error message or null on success */
function tooltrace_submit_registration_request(string $name, string $email, string $password, string $accountRole): ?string
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
    if (
        strlen($password) < 8
        || preg_match('/[A-Z]/', $password) !== 1
        || preg_match('/[a-z]/', $password) !== 1
        || preg_match('/[0-9]/', $password) !== 1
        || preg_match('/[^A-Za-z0-9]/', $password) !== 1
    ) {
        return 'Password must be at least 8 characters and include uppercase, lowercase, number, and special character.';
    }
    if (tooltrace_find_account_by_email($email) !== null) {
        return 'An account with this email already exists. Sign in instead.';
    }
    // Enforce unique org email + org name for organization accounts
    if ($accountRole === 'organization') {
        $pdo = db();
        $chkOrgEmail = $pdo->prepare("SELECT org_id FROM organizations WHERE LOWER(org_email) = ? LIMIT 1");
        $chkOrgEmail->execute([strtolower($email)]);
        if ($chkOrgEmail->fetch()) {
            return 'An organization with this email already exists.';
        }
        $chkOrgName = $pdo->prepare("SELECT org_id FROM organizations WHERE LOWER(org_name) = ? LIMIT 1");
        $chkOrgName->execute([strtolower($name)]);
        if ($chkOrgName->fetch()) {
            return 'An organization with this name already exists.';
        }
    }
    if (tooltrace_find_pending_registration_by_email($email) !== null) {
        return 'A registration request for this email is already pending. Please wait for admin review.';
    }

    if ($accountRole === 'organization') {
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

        // Backward compatible migration if the table was created without these columns.
        try {
            $pdo->exec("ALTER TABLE recognized_organizations ADD COLUMN org_email VARCHAR(100) NULL");
        } catch (Throwable $e) {
        }

        $matchStmt = $pdo->prepare(
            "SELECT 1 FROM recognized_organizations "
            . "WHERE LOWER(org_name) = ? OR (acronym IS NOT NULL AND acronym <> '' AND LOWER(acronym) = ?) "
            . "LIMIT 1"
        );
        $lowerName = strtolower($name);
        $matchStmt->execute([$lowerName, $lowerName]);
        if (!$matchStmt->fetchColumn()) {
            return 'Your organization is not found in our records. Please contact the administrator.';
        }
    }

    if ($accountRole === 'organization') {
        $pdo = db();
        $chkPendingName = $pdo->prepare("SELECT request_id FROM registration_requests WHERE status = 'pending' AND LOWER(org_name) = ? LIMIT 1");
        $chkPendingName->execute([strtolower($name)]);
        if ($chkPendingName->fetch()) {
            return 'A registration request for this organization name is already pending. Please wait for admin review.';
        }
    }

    $stmt = db()->prepare("INSERT INTO registration_requests (request_id, org_name, org_email, password_hash, account_role, status)
                           VALUES (?, ?, ?, ?, ?, 'pending')");
    $ok = $stmt->execute([
        bin2hex(random_bytes(8)),
        $name,
        strtolower($email),
        password_hash($password, PASSWORD_DEFAULT),
        $accountRole,
    ]);

    return $ok ? null : 'Could not save your request. Try again.';
}

// ── Approve ───────────────────────────────────────────────────────────────────

/** @return string|null Error or null on success */
function tooltrace_approve_registration_request(string $id): ?string
{
    $pending = tooltrace_registration_request_by_id($id);
    if ($pending === null) {
        return 'Request not found or already processed.';
    }

    $email = strtolower(trim((string) ($pending['org_email'] ?? '')));
    if (tooltrace_find_account_by_email($email) !== null) {
        return 'An account with this email already exists.';
    }

    $pdo = db();

    // Determine role — registration_requests table stores it if present
    $role = $pending['account_role'] ?? 'organization';
    if (!in_array($role, TOOLTRACE_ACCOUNT_ROLES, true)) {
        $role = 'organization';
    }

    // Create the account
    $publicAccountId = bin2hex(random_bytes(8));
    $insert = $pdo->prepare("INSERT INTO accounts (account_id, username, email, account_role, password_hash)
                              VALUES (?, ?, ?, ?, ?)");
    $ok = $insert->execute([
        $publicAccountId,
        $pending['org_name'],
        $email,
        $role,
        $pending['password_hash'],
    ]);

    if (!$ok) {
        return 'Could not create account. Try again.';
    }

    $accountNumId = (int) $pdo->lastInsertId();

    // If it's an organization, also add to organizations table
    if ($role === 'organization') {
        $orgName = trim((string) ($pending['org_name'] ?? ''));
        if ($orgName === '') {
            return 'Organization name is missing.';
        }

        $chkOrgEmail = $pdo->prepare("SELECT org_id FROM organizations WHERE LOWER(org_email) = ? LIMIT 1");
        $chkOrgEmail->execute([strtolower($email)]);
        if ($chkOrgEmail->fetch()) {
            return 'An organization with this email already exists.';
        }
        $chkOrgName = $pdo->prepare("SELECT org_id FROM organizations WHERE LOWER(org_name) = ? LIMIT 1");
        $chkOrgName->execute([strtolower($orgName)]);
        if ($chkOrgName->fetch()) {
            return 'An organization with this name already exists.';
        }

        $orgId = 'ORG-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
        $org   = $pdo->prepare("INSERT INTO organizations (org_id, org_name, org_email, members, status, account_id, account_num_id)
                                 VALUES (?, ?, ?, 0, 'Active', ?, ?)");
        $org->execute([$orgId, $orgName, $email, $publicAccountId, $accountNumId]);
    }

    // Mark request as approved
    $upd = $pdo->prepare("UPDATE registration_requests SET status = 'approved', account_id = ?, account_num_id = ? WHERE request_id = ?");
    $upd->execute([$publicAccountId, $accountNumId, $id]);

    $to = $email;
    if (filter_var($to, FILTER_VALIDATE_EMAIL)) {
        $name = (string) ($pending['org_name'] ?? '');
        $roleLabel = tooltrace_account_role_label((string) $role);
        $subject = 'ToolTrace: Registration Approved';
        $text = "Hello {$name},\n\nYour ToolTrace registration has been approved.\nAccount type: {$roleLabel}\n\nYou can now sign in using your email and password.\n\nThank you,\nToolTrace";
        $html = '<!doctype html><html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"></head>'
            . '<body style="margin:0;padding:0;background:#ffffff;font-family:Segoe UI,Arial,sans-serif;color:#111111;">'
            . '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#ffffff;padding:24px 12px;">'
            . '<tr><td align="center"><table role="presentation" width="640" cellspacing="0" cellpadding="0" style="max-width:640px;border:1px solid #111111;background:#ffffff;">'
            . '<tr><td style="background:#f1c40f;color:#111111;padding:18px 22px;text-align:center;">' . tooltrace_mail_brand_header_html() . '</td></tr>'
            . '<tr><td style="padding:20px 22px 10px 22px;">'
            . '<h2 style="margin:0 0 10px 0;color:#111111;font-size:22px;line-height:1.25;">Registration Approved</h2>'
            . '<p style="margin:0;color:#111111;font-size:14px;line-height:1.6;">Hello ' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . ', your registration has been approved.</p>'
            . '<p style="margin:10px 0 0 0;color:#111111;font-size:14px;line-height:1.6;">Account type: <strong>' . htmlspecialchars($roleLabel, ENT_QUOTES, 'UTF-8') . '</strong></p>'
            . '<p style="margin:10px 0 0 0;color:#111111;font-size:14px;line-height:1.6;">You can now sign in using your email and password.</p>'
            . '</td></tr>'
            . '<tr><td style="background:#111111;color:#ffffff;padding:12px 22px;font-size:12px;line-height:1.5;">ToolTrace Notification</td></tr>'
            . '</table></td></tr></table></body></html>';

        tooltrace_registration_mail_send_best_effort($to, $subject, $text, $html);
    }

    return null;
}

// ── Reject ────────────────────────────────────────────────────────────────────

/** @return string|null Error or null on success */
function tooltrace_reject_registration_request(string $id, string $reason = ''): ?string
{
    if (tooltrace_registration_request_by_id($id) === null) {
        return 'Request not found or already processed.';
    }

    $reason = trim($reason);

    $pending = tooltrace_registration_request_by_id($id);

    $pdo = db();
    if ($reason !== '') {
        try {
            $stmt = $pdo->prepare("UPDATE registration_requests SET status = 'rejected', rejection_reason = ? WHERE request_id = ?");
            return $stmt->execute([$reason, $id]) ? null : 'Could not update request.';
        } catch (Throwable $e) {
            // Column may not exist; fall back to status-only update.
        }
    }

    $stmt = $pdo->prepare("UPDATE registration_requests SET status = 'rejected' WHERE request_id = ?");
    $ok = $stmt->execute([$id]);
    if (!$ok) {
        return 'Could not update request.';
    }

    if (is_array($pending)) {
        $to = (string) ($pending['org_email'] ?? '');
        $name = (string) ($pending['org_name'] ?? '');
        $roleLabel = tooltrace_account_role_label((string) ($pending['account_role'] ?? 'organization'));
        if (filter_var($to, FILTER_VALIDATE_EMAIL)) {
            $subject = 'ToolTrace: Registration Rejected';
            $reasonLine = $reason !== '' ? "Reason: {$reason}\n\n" : '';
            $text = "Hello {$name},\n\nYour ToolTrace registration was rejected.\nAccount type: {$roleLabel}\n\n" . $reasonLine . "If you believe this is a mistake, please contact the administrator.\n\nThank you,\nToolTrace";
            $htmlReason = $reason !== ''
                ? '<p style="margin:10px 0 0 0;color:#111111;font-size:14px;line-height:1.6;"><strong>Reason:</strong> ' . htmlspecialchars($reason, ENT_QUOTES, 'UTF-8') . '</p>'
                : '';
            $html = '<!doctype html><html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"></head>'
                . '<body style="margin:0;padding:0;background:#ffffff;font-family:Segoe UI,Arial,sans-serif;color:#111111;">'
                . '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#ffffff;padding:24px 12px;">'
                . '<tr><td align="center"><table role="presentation" width="640" cellspacing="0" cellpadding="0" style="max-width:640px;border:1px solid #111111;background:#ffffff;">'
                . '<tr><td style="background:#f1c40f;color:#111111;padding:18px 22px;text-align:center;">' . tooltrace_mail_brand_header_html() . '</td></tr>'
                . '<tr><td style="padding:20px 22px 10px 22px;">'
                . '<h2 style="margin:0 0 10px 0;color:#111111;font-size:22px;line-height:1.25;">Registration Rejected</h2>'
                . '<p style="margin:0;color:#111111;font-size:14px;line-height:1.6;">Hello ' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . ', your registration was rejected.</p>'
                . '<p style="margin:10px 0 0 0;color:#111111;font-size:14px;line-height:1.6;">Account type: <strong>' . htmlspecialchars($roleLabel, ENT_QUOTES, 'UTF-8') . '</strong></p>'
                . $htmlReason
                . '<p style="margin:10px 0 0 0;color:#111111;font-size:14px;line-height:1.6;">If you believe this is a mistake, please contact the administrator.</p>'
                . '</td></tr>'
                . '<tr><td style="background:#111111;color:#ffffff;padding:12px 22px;font-size:12px;line-height:1.5;">ToolTrace Notification</td></tr>'
                . '</table></td></tr></table></body></html>';

            tooltrace_registration_mail_send_best_effort($to, $subject, $text, $html);
        }
    }

    return null;
}

// ── Legacy shim ───────────────────────────────────────────────────────────────

function tooltrace_registration_requests_remove_id(string $id, string $reason = ''): bool
{
    // Old code "removed" the row; we now just reject it instead.
    return tooltrace_reject_registration_request($id, $reason) === null;
}