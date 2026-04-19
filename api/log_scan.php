<?php
/**
 * ToolTrace - Log QR Scan (PDO-backed)
 * Supports pickup/return scans for a borrow request.
 *
 * GET  api/log_scan.php?query=1&request_id=REQ-YYYY-NNNN
 * POST api/log_scan.php  { request_id, scan_type: 'pickup'|'return', timestamp }
 */

declare(strict_types=1);

header('Content-Type: application/json');

require_once __DIR__ . '/../includes/borrow_requests_store.php';

$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $requestId = isset($_GET['request_id']) ? trim((string) $_GET['request_id']) : '';
    if ($requestId === '') {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Missing request_id']);
        exit;
    }

    $stmt = $pdo->prepare("SELECT status, date_returned FROM borrow_transactions WHERE transaction_id = ? OR request_group_id = ?");
    $stmt->execute([$requestId, $requestId]);
    $rows = $stmt->fetchAll();

    if (!$rows) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Request not found']);
        exit;
    }

    $hasReturned = false;
    $hasBorrowed = false;
    foreach ($rows as $r) {
        $st = strtolower(trim((string) ($r['status'] ?? '')));
        if ($st === 'returned' || !empty($r['date_returned'])) $hasReturned = true;
        if ($st === 'borrowed') $hasBorrowed = true;
    }

    $last = null;
    if ($hasReturned) $last = 'return';
    elseif ($hasBorrowed) $last = 'pickup';

    echo json_encode([
        'success' => true,
        'last_scan_type' => $last,
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $requestId = isset($input['request_id']) ? trim((string) $input['request_id']) : '';
    $scanType  = isset($input['scan_type']) ? strtolower(trim((string) $input['scan_type'])) : '';

    if ($requestId === '' || !in_array($scanType, ['pickup', 'return'], true)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid payload']);
        exit;
    }

    $existing = tooltrace_borrow_request_by_id($requestId);
    if (!$existing) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Request not found']);
        exit;
    }

    if ($scanType === 'pickup') {
        $stmt = $pdo->prepare("UPDATE borrow_transactions SET status = 'Borrowed' WHERE transaction_id = ? OR request_group_id = ?");
        $stmt->execute([$requestId, $requestId]);
    } else {
        $stmt = $pdo->prepare("UPDATE borrow_transactions SET status = 'Returned', date_returned = ? WHERE transaction_id = ? OR request_group_id = ?");
        $stmt->execute([date('Y-m-d'), $requestId, $requestId]);
    }

    echo json_encode([
        'success' => true,
        'request_id' => $requestId,
    ]);
    exit;
}

http_response_code(405);
echo json_encode(['success' => false, 'error' => 'Method not allowed']);
