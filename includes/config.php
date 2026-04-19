<?php
declare(strict_types=1);

define('TOOLTRACE_ROOT',     dirname(__DIR__));
define('TOOLTRACE_DATA_DIR', TOOLTRACE_ROOT . DIRECTORY_SEPARATOR . 'data');
define('TOOLTRACE_INCLUDES', __DIR__);

// Database connection settings with environment variable fallbacks
define('DB_HOST',    getenv('DB_HOST')     ?: 'localhost');
define('DB_NAME',    getenv('DB_NAME')     ?: 'tooltrace');
define('DB_USER',    getenv('DB_USER')     ?: 'root');
define('DB_PASS',    getenv('DB_PASS')     ?: '');
define('DB_CHARSET', getenv('DB_CHARSET')  ?: 'utf8mb4');

function db(): PDO {
    static $pdo = null;
    
    if ($pdo === null) {
        try {
            // Check if Railway's MYSQL_URL is set
            $mysqlUrl = getenv('MYSQL_URL');
            
            if ($mysqlUrl) {
                $pdo = new PDO($mysqlUrl, [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]);
            } else {
                $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
                $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]);
            }
        } catch (PDOException $e) {
            die('Database connection failed: ' . $e->getMessage());
        }
    }
    
    return $pdo;
}
?>