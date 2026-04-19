<?php
/**
 * ToolTrace - Update Request Details (PDO-backed)
 * Called by the QR scan flow to update borrow transaction details.
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once __DIR__ . '/includes/borrow_requests_store.php';

try {
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input || !isset($input['request_id'])) {
        echo json_encode(['success' => false, 'error' => 'Missing required fields']);
        exit;
    }

    $requestId = trim((string) $input['request_id']);

    // Verify the transaction (or group) exists
    $existing = tooltrace_borrow_request_by_id($requestId);
    if (!$existing) {
        echo json_encode(['success' => false, 'error' => 'Request not found']);
        exit;
    }

    $pdo    = db();
    $sets   = [];
    $values = [];

    // purpose
    if (isset($input['purpose']) && trim((string) $input['purpose']) !== '') {
        $sets[]   = 'purpose = ?';
        $values[] = trim((string) $input['purpose']);
    }

    // status → map 'returned' to DB status
    if (isset($input['status'])) {
        $newStatus = strtolower(trim((string) $input['status']));
        if ($newStatus === 'returned') {
            $sets[]   = "status = 'Returned'";
            $sets[]   = 'date_returned = ?';
            $values[] = date('Y-m-d');
        } elseif (in_array($newStatus, ['borrowed', 'overdue', 'lost'], true)) {
            $sets[]   = 'status = ?';
            $values[] = ucfirst($newStatus);
        }
    }

    if (!empty($sets)) {
        $values[] = $requestId;
        $values[] = $requestId;
        $stmt = $pdo->prepare('UPDATE borrow_transactions SET ' . implode(', ', $sets) . ' WHERE transaction_id = ? OR request_group_id = ?');
        $stmt->execute($values);
    }

    echo json_encode([
        'success' => true,
        'message' => 'Request updated successfully',
        'updated_request' => [
            'request_id'        => $requestId,
            'organization_name' => $input['organization_name'] ?? null,
            'staff_name'        => $input['staff_name']        ?? null,
            'updated_by'        => $input['updated_by']        ?? null,
        ],
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error'   => 'Server error: ' . $e->getMessage(),
    ]);
}