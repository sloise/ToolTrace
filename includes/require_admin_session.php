<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (($_SESSION['role'] ?? '') !== 'Super Admin') {
    header('Location: index.php', true, 302);
    exit;
}
