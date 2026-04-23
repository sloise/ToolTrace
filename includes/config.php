<?php
declare(strict_types=1);
define('TOOLTRACE_ROOT',     dirname(__DIR__));
define('TOOLTRACE_DATA_DIR', TOOLTRACE_ROOT . DIRECTORY_SEPARATOR . 'data');
define('TOOLTRACE_INCLUDES', __DIR__);

function db(): PDO {
    static $pdo = null;
    if ($pdo !== null) return $pdo;

    try {
        $mysqlUrl = getenv('MYSQL_URL') ?: getenv('DATABASE_URL') ?: '';

        if ($mysqlUrl !== '') {
            // Railway gives mysql://user:pass@host:port/dbname — parse it for PDO
            $p = parse_url($mysqlUrl);
            $host    = $p['host']                        ?? 'localhost';
            $port    = isset($p['port']) ? (int)$p['port'] : 3306;
            $dbname  = ltrim($p['path'] ?? '', '/');
            $user    = urldecode($p['user']              ?? '');
            $pass    = urldecode($p['pass']              ?? '');
            $dsn     = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";
        } else {
            // Local fallback
            $host   = getenv('DB_HOST')    ?: 'localhost';
            $dbname = getenv('DB_NAME')    ?: 'tooltrace';
            $user   = getenv('DB_USER')    ?: 'root';
            $pass   = getenv('DB_PASS')    ?: '';
            $dsn    = "mysql:host={$host};dbname={$dbname};charset=utf8mb4";
        }

        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);

    } catch (PDOException $e) {
        // Return JSON instead of dying so save_request.php can catch it
        header('Content-Type: application/json');
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'DB connection failed: ' . $e->getMessage()]);
        exit;
    }

    return $pdo;
}
?>
