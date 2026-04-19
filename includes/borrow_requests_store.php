<?php
/**
 * Borrow transactions — PDO-backed (MySQL via XAMPP).
 * Replaces the old JSON-based borrow_requests_store.php.
 */
declare(strict_types=1);

require_once __DIR__ . '/config.php';

// ── Read: all transactions ────────────────────────────────────────────────────

function tooltrace_borrow_requests_all(): array
{
    tooltrace_borrow_sync_overdue_statuses();
    $stmt = db()->query("
        SELECT
            bt.*,
            e.name        AS equipment_name,
            e.image       AS equipment_image,
            e.category    AS equipment_category,
            eu.unit_number,
            eu.condition_tag,
            o.org_name    AS organization_name,
            o.org_email   AS organization_email,
            s.name        AS staff_name,
            sa.name       AS approved_by_name
        FROM borrow_transactions bt
        LEFT JOIN equipment       e  ON bt.equipment_id = e.equipment_id
        LEFT JOIN equipment_units eu ON bt.unit_id      = eu.unit_id
        LEFT JOIN organizations   o  ON bt.org_id       = o.org_id
        LEFT JOIN staff           s  ON bt.staff_id     = s.staff_id
        LEFT JOIN staff           sa ON bt.approved_by_staff_id = sa.staff_id
        ORDER BY bt.date_borrowed DESC
    ");
    $rows = $stmt->fetchAll();
    return array_map('tooltrace_borrow_normalize_row', $rows);
}

// ── Read: filtered by org email ───────────────────────────────────────────────

function tooltrace_borrow_requests_for_org(string $orgEmail): array
{
    $orgEmail = strtolower(trim($orgEmail));
    tooltrace_borrow_sync_overdue_statuses();
    $stmt = db()->prepare("
        SELECT
            bt.*,
            e.name        AS equipment_name,
            e.image       AS equipment_image,
            e.category    AS equipment_category,
            eu.unit_number,
            eu.condition_tag,
            o.org_name    AS organization_name,
            o.org_email   AS organization_email,
            s.name        AS staff_name,
            sa.name       AS approved_by_name
        FROM borrow_transactions bt
        LEFT JOIN equipment       e  ON bt.equipment_id = e.equipment_id
        LEFT JOIN equipment_units eu ON bt.unit_id      = eu.unit_id
        LEFT JOIN organizations   o  ON bt.org_id       = o.org_id
        LEFT JOIN staff           s  ON bt.staff_id     = s.staff_id
        LEFT JOIN staff           sa ON bt.approved_by_staff_id = sa.staff_id
        WHERE LOWER(o.org_email) = ?
        ORDER BY bt.date_borrowed DESC
    ");
    $stmt->execute([$orgEmail]);
    $rows = $stmt->fetchAll();
    return array_map('tooltrace_borrow_normalize_row', $rows);
}

// ── Read: pending admin approval ─────────────────────────────────────────────

function tooltrace_borrow_requests_pending_admin_approval(): array
{
    tooltrace_borrow_sync_overdue_statuses();
    $stmt = db()->query("
        SELECT
            bt.*,
            e.name        AS equipment_name,
            e.image       AS equipment_image,
            e.category    AS equipment_category,
            eu.unit_number,
            eu.condition_tag,
            o.org_name    AS organization_name,
            o.org_email   AS organization_email,
            s.name        AS staff_name,
            sa.name       AS approved_by_name
        FROM borrow_transactions bt
        LEFT JOIN equipment       e  ON bt.equipment_id = e.equipment_id
        LEFT JOIN equipment_units eu ON bt.unit_id      = eu.unit_id
        LEFT JOIN organizations   o  ON bt.org_id       = o.org_id
        LEFT JOIN staff           s  ON bt.staff_id     = s.staff_id
        LEFT JOIN staff           sa ON bt.approved_by_staff_id = sa.staff_id
        WHERE bt.approval_status = 'Pending'
        ORDER BY bt.date_borrowed DESC
    ");
    $rows = $stmt->fetchAll();
    return array_map('tooltrace_borrow_normalize_row', $rows);
}

// ── Read: single transaction by ID ───────────────────────────────────────────

function tooltrace_borrow_request_by_id(string $transactionId): ?array
{
    tooltrace_borrow_sync_overdue_statuses();
    $stmt = db()->prepare("
        SELECT
            bt.*,
            e.name        AS equipment_name,
            e.image       AS equipment_image,
            e.category    AS equipment_category,
            eu.unit_number,
            eu.condition_tag,
            o.org_name    AS organization_name,
            o.org_email   AS organization_email,
            s.name        AS staff_name,
            sa.name       AS approved_by_name
        FROM borrow_transactions bt
        LEFT JOIN equipment       e  ON bt.equipment_id = e.equipment_id
        LEFT JOIN equipment_units eu ON bt.unit_id      = eu.unit_id
        LEFT JOIN organizations   o  ON bt.org_id       = o.org_id
        LEFT JOIN staff           s  ON bt.staff_id     = s.staff_id
        LEFT JOIN staff           sa ON bt.approved_by_staff_id = sa.staff_id
        WHERE bt.transaction_id = ? OR bt.request_group_id = ?
    ");
    $stmt->execute([$transactionId, $transactionId]);
    $row = $stmt->fetch();
    return $row ? tooltrace_borrow_normalize_row($row) : null;
}

// ── Normalize: map DB row to shape the frontend expects ───────────────────────

function tooltrace_borrow_sync_overdue_statuses(): void
{
    db()->exec(" 
        UPDATE borrow_transactions
        SET status = 'Overdue'
        WHERE approval_status = 'Approved'
          AND (status IS NULL OR status NOT IN ('Returned', 'Rejected', 'Overdue'))
          AND date_returned IS NULL
          AND due_date IS NOT NULL
          AND DATE(due_date) < CURDATE()
    ");
}

function tooltrace_borrow_normalize_row(array $row): array
{
    $dueDateRaw      = $row['due_date']      ?? '';
    $dateBorrowedRaw = $row['date_borrowed'] ?? '';
    $requestGroupId  = $row['request_group_id'] ?? null;
    $requestId       = $requestGroupId ?: ($row['transaction_id'] ?? '');

    return [
        // IDs
        'request_id'         => $requestId,
        'transaction_id'     => $row['transaction_id']    ?? '',
        'request_group_id'   => $requestGroupId,
        'org_id'             => $row['org_id']            ?? '',
        'equipment_id'       => $row['equipment_id']      ?? '',
        'unit_id'            => $row['unit_id']           ?? null,
        'unit_number'        => $row['unit_number']       ?? null,
        'staff_id'           => $row['staff_id']          ?? null,

        // Equipment info
        'item'               => $row['equipment_name']    ?? '',
        'equipment_name'     => $row['equipment_name']    ?? '',
        'equipment_image'    => $row['equipment_image']   ?? '',
        'equipment_category' => $row['equipment_category'] ?? '',
        'condition_tag'      => $row['condition_tag']     ?? '',

        // Org info
        'organization_name'  => $row['organization_name']  ?? '',
        'organization_email' => $row['organization_email'] ?? '',

        // Staff info
        'staff_name'         => $row['staff_name']        ?? '',
        'approved_by'        => $row['approved_by_name']  ?? '',
        'approved_by_staff_id' => $row['approved_by_staff_id'] ?? null,

        // Transaction details
        'purpose'            => $row['purpose']           ?? '',
        'oic_id_path'        => $row['oic_id_path']       ?? null,
        'oic_id_mime'        => $row['oic_id_mime']       ?? null,
        'oic_id_original_name' => $row['oic_id_original_name'] ?? null,
        'date_requested'     => $dateBorrowedRaw,   // alias: pages use date_requested
        'date_needed'        => $dateBorrowedRaw,   // alias: pages use date_needed
        'date_borrowed'      => $dateBorrowedRaw,
        'due_date'           => $dueDateRaw,
        'return_date'        => $dueDateRaw,         // alias: pages use return_date for due_date
        'date_returned'      => $row['date_returned'] ?? '',

        // Status
        'status'             => $row['status']            ?? 'Borrowed',
        'approval_status'    => $row['approval_status']   ?? 'Pending',
        'returned'           => !empty($row['date_returned']), // legacy compat flag
    ];
}

// ── Approval status helper (legacy compat) ────────────────────────────────────

function tooltrace_borrow_request_approval_status(array $row): string
{
    $raw = strtolower(trim((string) ($row['approval_status'] ?? 'pending')));
    if ($raw === 'approved') return 'approved';
    if ($raw === 'rejected') return 'rejected';
    return 'pending';
}

function tooltrace_borrow_request_display_status(array $row): string
{
    $approval = tooltrace_borrow_request_approval_status($row);
    if ($approval === 'rejected') {
        return 'Rejected';
    }
    if (tooltrace_borrow_is_overdue($row)) {
        return 'Overdue';
    }
    if ($approval === 'pending') {
        return 'Pending';
    }

    $txStatus = strtolower(trim((string) ($row['status'] ?? '')));
    if ($txStatus === 'returned') {
        return 'Returned';
    }
    if ($txStatus === 'borrowed') {
        return 'Picked Up';
    }

    return 'Approved';
}

function tooltrace_borrow_is_active(array $row): bool
{
    if (tooltrace_borrow_request_approval_status($row) === 'rejected') {
        return false;
    }

    if (!empty($row['returned']) || !empty($row['date_returned'])) {
        return false;
    }

    $txStatus = strtolower(trim((string) ($row['status'] ?? '')));
    if (in_array($txStatus, ['returned', 'rejected'], true)) {
        return false;
    }

    return in_array($txStatus, ['borrowed', 'overdue', 'approved'], true)
        || tooltrace_borrow_request_approval_status($row) === 'approved';
}

function tooltrace_borrow_is_overdue(array $row, ?DateTimeImmutable $today = null): bool
{
    if (!tooltrace_borrow_is_active($row)) {
        return false;
    }

    $txStatus = strtolower(trim((string) ($row['status'] ?? '')));
    if ($txStatus === 'overdue') {
        return true;
    }

    $dueDate = isset($row['due_date']) ? trim((string) $row['due_date']) : '';
    if ($dueDate === '') {
        $dueDate = isset($row['return_date']) ? trim((string) $row['return_date']) : '';
    }
    if ($dueDate === '') {
        return false;
    }

    try {
        $today = $today ?? new DateTimeImmutable('today');
        return new DateTimeImmutable($dueDate) < $today;
    } catch (Exception $e) {
        return false;
    }
}

// ── Item label helper ─────────────────────────────────────────────────────────

function tooltrace_borrow_item_label(array $row): string
{
    return $row['equipment_name'] ?? $row['item'] ?? 'Equipment';
}

// ── Write: submit a new borrow request ───────────────────────────────────────

/**
 * @return string|null Error message or null on success
 */
function tooltrace_borrow_submit_request(
    string $orgId,
    string $equipmentId,
    int    $unitId,
    int    $staffId,
    string $purpose,
    string $dateBorrowed,
    string $dueDate
): ?string {
    $pdo = db();

    $year   = date('Y');
    $prefix = 'REQ-' . $year . '-';
    $last   = $pdo->query("
        SELECT transaction_id FROM borrow_transactions
        WHERE transaction_id LIKE 'REQ-{$year}-%'
        ORDER BY transaction_id DESC LIMIT 1
    ")->fetchColumn();

    $maxSeq = 0;
    if ($last && preg_match('/(\d+)$/', $last, $m)) {
        $maxSeq = (int) $m[1];
    }
    $transactionId = $prefix . str_pad((string) ($maxSeq + 1), 4, '0', STR_PAD_LEFT);

    $stmt = $pdo->prepare("
        INSERT INTO borrow_transactions
            (transaction_id, org_id, equipment_id, unit_id, staff_id, purpose, date_borrowed, due_date, status, approval_status)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Borrowed', 'Pending')
    ");

    $ok = $stmt->execute([
        $transactionId,
        $orgId,
        $equipmentId,
        $unitId,
        $staffId,
        $purpose,
        $dateBorrowed,
        $dueDate,
    ]);

    return $ok ? null : 'Could not save borrow request.';
}

// ── Write: approve / reject (legacy compat) ───────────────────────────────────

/** @return string|null Error or null on success */
function tooltrace_borrow_set_request_approval(string $requestId, string $status, int $approvedByStaffId = 0): ?string
{
    $status = strtolower(trim($status));
    if (!in_array($status, ['approved', 'rejected'], true)) {
        return 'Invalid approval status.';
    }

    $dbStatus = ucfirst($status);

    if ($status === 'approved') {
        $stmt = db()->prepare("UPDATE borrow_transactions SET approval_status = ?, approved_by_staff_id = ? WHERE transaction_id = ? OR request_group_id = ?");
        $ok = $stmt->execute([$dbStatus, ($approvedByStaffId > 0 ? $approvedByStaffId : null), $requestId, $requestId]);
        return $ok ? null : 'Could not approve request.';
    }

    $stmt = db()->prepare("UPDATE borrow_transactions SET approval_status = ? WHERE transaction_id = ? OR request_group_id = ?");
    $ok = $stmt->execute([$dbStatus, $requestId, $requestId]);
    return $ok ? null : 'Could not reject request.';
}

// ── Write: mark as returned ───────────────────────────────────────────────────

/** @return string|null Error or null on success */
function tooltrace_borrow_mark_returned(string $transactionId, string $dateReturned = ''): ?string
{
    if ($dateReturned === '') {
        $dateReturned = date('Y-m-d');
    }
    $stmt = db()->prepare("
        UPDATE borrow_transactions
        SET status = 'Returned', date_returned = ?
        WHERE transaction_id = ?
    ");
    return $stmt->execute([$dateReturned, $transactionId]) ? null : 'Could not update transaction.';
}

// ── Write: mark as overdue ────────────────────────────────────────────────────

function tooltrace_borrow_mark_overdue(string $transactionId): ?string
{
    $stmt = db()->prepare("UPDATE borrow_transactions SET status = 'Overdue' WHERE transaction_id = ?");
    return $stmt->execute([$transactionId]) ? null : 'Could not update transaction.';
}

// ── Write: update due date ────────────────────────────────────────────────────

function tooltrace_borrow_update_due_date(string $transactionId, string $newDueDate): ?string
{
    $stmt = db()->prepare("UPDATE borrow_transactions SET due_date = ? WHERE transaction_id = ?");
    return $stmt->execute([$newDueDate, $transactionId]) ? null : 'Could not update due date.';
}

// ── Stats helpers ─────────────────────────────────────────────────────────────

function tooltrace_borrow_count_by_status(string $status): int
{
    $stmt = db()->prepare("SELECT COUNT(*) FROM borrow_transactions WHERE status = ?");
    $stmt->execute([$status]);
    return (int) $stmt->fetchColumn();
}

function tooltrace_borrow_overdue_list(): array
{
    $stmt = db()->query("
        SELECT bt.*, e.name AS equipment_name, o.org_name AS organization_name
        FROM borrow_transactions bt
        LEFT JOIN equipment     e ON bt.equipment_id = e.equipment_id
        LEFT JOIN organizations o ON bt.org_id       = o.org_id
        WHERE bt.status = 'Overdue'
        ORDER BY bt.due_date ASC
    ");
    return $stmt->fetchAll();
}

// ── Legacy shim ───────────────────────────────────────────────────────────────

function tooltrace_borrow_requests_save_all(array $rows): bool
{
    // No-op: DB is source of truth. Individual write functions handle updates.
    return true;
}