<?php
/**
 * ToolTrace — Save Borrow Request
 * Writes one row per item into borrow_transactions (PDO/MySQL).
 */
declare(strict_types=1);

header('Content-Type: application/json');

try {

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        exit;
    }

    require_once __DIR__ . '/includes/config.php';

    $data = null;
    if (isset($_POST['payload']) && is_string($_POST['payload'])) {
        $data = json_decode($_POST['payload'], true);
    } else {
        $raw  = file_get_contents('php://input');
        $data = json_decode($raw, true);
    }

    if (!is_array($data) || empty($data['organization_email'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid payload']);
        exit;
    }

    $pdo = db();

    try { $pdo->exec("ALTER TABLE borrow_transactions ADD COLUMN oic_id_data LONGTEXT NULL"); } catch (Throwable $e) {}
    try { $pdo->exec("ALTER TABLE borrow_transactions ADD COLUMN oic_id_mime VARCHAR(100) NULL"); } catch (Throwable $e) {}
    try { $pdo->exec("ALTER TABLE borrow_transactions ADD COLUMN oic_id_original_name VARCHAR(255) NULL"); } catch (Throwable $e) {}

    $orgEmail   = strtolower(trim((string) $data['organization_email']));
    $purpose    = trim((string) ($data['purpose']    ?? ''));
    $dateBorrow = trim((string) ($data['date_needed'] ?? date('Y-m-d')));
    $dueDate    = trim((string) ($data['return_date'] ?? $dateBorrow));
    $location   = trim((string) ($data['location'] ?? ''));
    $officer    = trim((string) ($data['officer_in_charge'] ?? $data['officer'] ?? ''));
    $items      = isset($data['items']) && is_array($data['items']) ? $data['items'] : [];

    // ── OIC ID file — stored as base64 data URL in MySQL (Railway-compatible) ──
    $oicIdData     = null;
    $oicIdMime     = null;
    $oicIdOriginal = null;

    if (
        isset($_FILES['oic_id_file']) &&
        is_array($_FILES['oic_id_file']) &&
        ((int)($_FILES['oic_id_file']['error'] ?? UPLOAD_ERR_NO_FILE)) === UPLOAD_ERR_OK
    ) {
        $tmp  = (string)($_FILES['oic_id_file']['tmp_name'] ?? '');
        $size = (int)($_FILES['oic_id_file']['size'] ?? 0);

        if ($tmp === '' || !is_uploaded_file($tmp) || $size <= 0) {
            http_response_code(422);
            echo json_encode(['success' => false, 'error' => 'Invalid upload.']);
            exit;
        }

        $finfo   = new finfo(FILEINFO_MIME_TYPE);
        $mime    = (string)($finfo->file($tmp) ?: '');
        $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'application/pdf' => 'pdf'];

        if (!isset($allowed[$mime])) {
            http_response_code(422);
            echo json_encode(['success' => false, 'error' => 'Invalid file type. JPG, PNG, or PDF only.']);
            exit;
        }

        $rawBytes = @file_get_contents($tmp);
        if ($rawBytes === false || strlen($rawBytes) === 0) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Could not read uploaded file.']);
            exit;
        }

        $oicIdData     = 'data:' . $mime . ';base64,' . base64_encode($rawBytes);
        $oicIdMime     = $mime;
        $oicIdOriginal = (string)($_FILES['oic_id_file']['name'] ?? '');
    }

    if ($oicIdData === null) {
        http_response_code(422);
        echo json_encode(['success' => false, 'error' => 'Officer-in-Charge ID upload is required.']);
        exit;
    }

    // ── Resolve org_id from email ─────────────────────────────────────────────
    $orgStmt = $pdo->prepare("SELECT org_id FROM organizations WHERE LOWER(org_email) = ?");
    $orgStmt->execute([$orgEmail]);
    $org = $orgStmt->fetch();

    if (!$org) {
        http_response_code(422);
        echo json_encode(['error' => 'Organization not found for email: ' . $orgEmail]);
        exit;
    }
    $orgId = $org['org_id'];

    // ── Pick a staff_id ───────────────────────────────────────────────────────
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $staffEmail = strtolower(trim((string) ($_SESSION['organization_email'] ?? '')));
    $staffStmt  = $pdo->prepare("SELECT staff_id FROM staff WHERE LOWER(email) = ?");
    $staffStmt->execute([$staffEmail]);
    $staffRow   = $staffStmt->fetch();
    $staffId    = $staffRow ? (int) $staffRow['staff_id'] : 1;

    // ── Generate next request group ID ────────────────────────────────────────
    $year   = date('Y');
    $prefix = 'REQ-' . $year . '-';

    $lastGroup = $pdo->query("
        SELECT request_group_id
        FROM borrow_transactions
        WHERE request_group_id LIKE 'REQ-{$year}-%'
          AND request_group_id IS NOT NULL
          AND request_group_id <> ''
        ORDER BY request_group_id DESC
        LIMIT 1
    ")->fetchColumn();

    $maxSeq = 0;
    if (is_string($lastGroup) && preg_match('/(\d{4})$/', $lastGroup, $m)) {
        $maxSeq = (int) $m[1];
    }

    $groupSeq = $maxSeq + 1;
    while (true) {
        $candidate  = $prefix . str_pad((string) $groupSeq, 4, '0', STR_PAD_LEFT);
        $existsStmt = $pdo->prepare("SELECT 1 FROM borrow_transactions WHERE request_group_id = ? OR transaction_id = ? LIMIT 1");
        $existsStmt->execute([$candidate, $candidate . '-01']);
        if (!$existsStmt->fetchColumn()) {
            $requestGroupId = $candidate;
            break;
        }
        $groupSeq++;
    }

    // ── Insert rows ───────────────────────────────────────────────────────────
    $insertStmt = $pdo->prepare("
        INSERT INTO borrow_transactions
            (transaction_id, request_group_id, org_id, equipment_id, unit_id,
             staff_id, purpose, location, officer_in_charge, date_borrowed,
             due_date, status, approval_status,
             oic_id_data, oic_id_mime, oic_id_original_name)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending', 'Pending', ?, ?, ?)
    ");

    $firstRequestId = null;
    $errors         = [];
    $seenUnits      = [];
    $itemSeq        = 0;

    foreach ($items as $item) {
        $equipmentId = trim((string) ($item['id'] ?? ''));
        if ($equipmentId === '') continue;

        $qty = max(1, (int) ($item['qty'] ?? 1));

        $unitIds = [];
        if (isset($item['unit_ids']) && is_array($item['unit_ids'])) {
            foreach ($item['unit_ids'] as $uid) {
                if (is_numeric($uid)) $unitIds[] = (int) $uid;
            }
        }
        $unitIds = array_values(array_unique($unitIds));

        if (count($unitIds) !== $qty) {
            $errors[] = "Unit selection mismatch for equipment: $equipmentId";
            continue;
        }

        $availStmt = $pdo->prepare("
            SELECT COUNT(*)
            FROM equipment_units eu
            WHERE eu.equipment_id = ?
              AND eu.unit_id NOT IN (
                SELECT DISTINCT unit_id
                FROM borrow_transactions
                WHERE equipment_id = ?
                  AND unit_id IS NOT NULL
                  AND status <> 'Returned'
                  AND approval_status <> 'Rejected'
              )
        ");
        $availStmt->execute([$equipmentId, $equipmentId]);
        $availableCount = (int) $availStmt->fetchColumn();

        if ($qty > $availableCount) {
            $errors[] = "Only {$availableCount} unit(s) available for equipment: $equipmentId";
            continue;
        }

        foreach ($unitIds as $unitId) {
            if (isset($seenUnits[$unitId])) {
                $errors[] = "Duplicate unit selected: {$unitId}";
                continue 2;
            }
            $seenUnits[$unitId] = true;

            $belongsStmt = $pdo->prepare("SELECT 1 FROM equipment_units WHERE unit_id = ? AND equipment_id = ? LIMIT 1");
            $belongsStmt->execute([$unitId, $equipmentId]);
            if (!$belongsStmt->fetchColumn()) {
                $errors[] = "Invalid unit selection for equipment: $equipmentId";
                continue 2;
            }

            $busyStmt = $pdo->prepare("
                SELECT 1 FROM borrow_transactions
                WHERE unit_id = ?
                  AND status <> 'Returned'
                  AND approval_status <> 'Rejected'
                LIMIT 1
            ");
            $busyStmt->execute([$unitId]);
            if ($busyStmt->fetchColumn()) {
                $errors[] = "Selected unit is no longer available.";
                continue 2;
            }
        }

        foreach ($unitIds as $unitId) {
            $itemSeq++;
            $transactionId = $requestGroupId . '-' . str_pad((string) $itemSeq, 2, '0', STR_PAD_LEFT);

            if ($firstRequestId === null) {
                $firstRequestId = $transactionId;
            }

            $insertStmt->execute([
                $transactionId,
                $requestGroupId,
                $orgId,
                $equipmentId,
                $unitId,
                $staffId,
                $purpose,
                ($location !== '' ? $location : null),
                ($officer  !== '' ? $officer  : null),
                $dateBorrow,
                $dueDate,
                $oicIdData,
                $oicIdMime,
                ($oicIdOriginal !== '' ? $oicIdOriginal : null),
            ]);
        }
    }

    if ($firstRequestId === null) {
        http_response_code(422);
        echo json_encode(['error' => 'No items could be saved. ' . implode(' ', $errors)]);
        exit;
    }

    echo json_encode([
        'success'    => true,
        'request_id' => $requestGroupId,
        'warnings'   => $errors,
    ]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error'   => 'Server error: ' . $e->getMessage(),
    ]);
}
