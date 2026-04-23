<?php
declare(strict_types=1);
header('Content-Type: application/json');

$result = [];

// 1. Check env vars
$result['mysql_url_set'] = !empty(getenv('MYSQL_URL'));
$result['mysql_url_preview'] = substr((string)(getenv('MYSQL_URL') ?: ''), 0, 40) . '...';

// 2. Check DB connection
try {
    require_once __DIR__ . '/includes/config.php';
    $pdo = db();
    $result['db_connected'] = true;
    $result['db_test'] = $pdo->query("SELECT 1")->fetchColumn();
} catch (Throwable $e) {
    $result['db_connected'] = false;
    $result['db_error'] = $e->getMessage();
}

// 3. Check TOOLTRACE_DATA_DIR
$result['data_dir'] = TOOLTRACE_DATA_DIR;
$result['data_dir_exists']   = is_dir(TOOLTRACE_DATA_DIR);
$result['data_dir_writable'] = is_writable(TOOLTRACE_DATA_DIR);

// 4. Check uploads dir
$uploadsDir = TOOLTRACE_DATA_DIR . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'oic_ids';
$result['uploads_dir'] = $uploadsDir;
$result['uploads_dir_exists']   = is_dir($uploadsDir);
$result['uploads_dir_writable'] = is_dir($uploadsDir) && is_writable($uploadsDir);

// Try to create it
if (!is_dir($uploadsDir)) {
    $made = @mkdir($uploadsDir, 0755, true);
    $result['uploads_dir_mkdir_attempted'] = true;
    $result['uploads_dir_mkdir_success']   = $made;
    $result['uploads_dir_mkdir_error']     = $made ? null : error_get_last();
}

// 5. Try writing a test file
$testFile = $uploadsDir . DIRECTORY_SEPARATOR . 'write_test_' . time() . '.txt';
$wrote = @file_put_contents($testFile, 'test');
$result['test_file_write_success'] = ($wrote !== false);
$result['test_file_write_error']   = ($wrote === false) ? error_get_last() : null;
if ($wrote !== false) @unlink($testFile);

// 6. Check borrow_transactions columns
if (!empty($result['db_connected'])) {
    try {
        $cols = $pdo->query("SHOW COLUMNS FROM borrow_transactions")->fetchAll();
        $result['table_columns'] = array_column($cols, 'Field');
    } catch (Throwable $e) {
        $result['table_columns_error'] = $e->getMessage();
    }
}

// 7. PHP upload settings
$result['php_upload_max_filesize'] = ini_get('upload_max_filesize');
$result['php_post_max_size']       = ini_get('post_max_size');
$result['php_file_uploads']        = ini_get('file_uploads');

echo json_encode($result, JSON_PRETTY_PRINT);
