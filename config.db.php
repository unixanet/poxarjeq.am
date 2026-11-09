<?php
declare(strict_types=1);

// Подключение к MySQL (используется новой админкой и модулями на БД).
define('DB_HOST', 'localhost');
define('DB_NAME', 'poxarjeq');
define('DB_USER', 'poxarjeq');
define('DB_PASS', '^,]8dsr}JVTdi@8=');
define('DB_CHARSET', 'utf8mb4');
define('DB_HOST_OVERRIDE', getenv('POXARJEQ_DB_HOST') ?: null);

// Ключ первого запуска админки: нужен один раз, чтобы создать первого администратора на /admin/.
define('ADMIN_SETUP_KEY', 'pxq-setup-7f3a91c2e8b4d605');

function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $pdo = new PDO(
            'mysql:host=' . (DB_HOST_OVERRIDE ?: DB_HOST) . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET,
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_TIMEOUT => 3,
            ]
        );
    }
    return $pdo;
}
