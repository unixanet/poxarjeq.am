<?php
declare(strict_types=1);

// Подключение к MySQL (используется новой админкой и модулями на БД).
define('DB_HOST', 'localhost');
define('DB_NAME', 'poxarjeq');
define('DB_USER', 'poxarjeq');
define('DB_PASS', '^,]8dsr}JVTdi@8=');
define('DB_CHARSET', 'utf8mb4');

function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $pdo = new PDO(
            'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET,
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_TIMEOUT => 5,
            ]
        );
    }
    return $pdo;
}
