<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/require_staff_session.php';
require_once __DIR__ . '/includes/config.php';

$requestId = isset($_GET['request_id']) ? trim((string) $_GET['request_id']) : '';
if ($requestId === '') {
    http_response_code(400);
    echo 'Missing request_id';
    exit;
}

$pdo = db();

$stmt = $pdo->prepare("SELECT oic_id_path, oic_id_mime, oic_id_original_name FROM borrow_transactions WHERE request_group_id = ? OR transaction_id = ? LIMIT 1");
$stmt->execute([$requestId, $requestId]);
$row = $stmt->fetch();

if (!$row) {
    http_response_code(404);
    echo 'Request not found';
    exit;
}

$relPath = (string) ($row['oic_id_path'] ?? '');
$mime    = (string) ($row['oic_id_mime'] ?? 'application/octet-stream');
$name    = (string) ($row['oic_id_original_name'] ?? 'oic_id');

if ($relPath === '' || !str_starts_with(str_replace('\\', '/', $relPath), 'data/uploads/oic_ids/')) {
    http_response_code(404);
    echo 'ID file not found';
    exit;
}

$full = TOOLTRACE_ROOT . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $relPath);
$realBase = realpath(TOOLTRACE_ROOT . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'oic_ids');
$realFile = realpath($full);

if ($realBase) {
    $realBase = str_replace('\\', '/', $realBase);
}
if ($realFile) {
    $realFile = str_replace('\\', '/', $realFile);
}

$baseCmp = $realBase ? strtolower(rtrim($realBase, '/')) : '';
$fileCmp = $realFile ? strtolower($realFile) : '';

if (!$realBase || !$realFile || !str_starts_with($fileCmp, $baseCmp) || !is_file($realFile)) {
    http_response_code(404);
    echo 'ID file not found';
    exit;
}

$disposition = 'inline';
header('Content-Type: ' . $mime);
header('Content-Disposition: ' . $disposition . '; filename="' . str_replace('"', '', basename($name)) . '"');
header('X-Content-Type-Options: nosniff');

readfile($realFile);
