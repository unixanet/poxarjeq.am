<?php
declare(strict_types=1);

/**
 * Таблицы админки poxarjeq.am (MySQL / MariaDB). Идемпотентно: CREATE TABLE IF NOT EXISTS.
 * Вызывается из админки; публичный сайт таблицы не создаёт.
 */
function pxq_migrate(PDO $pdo): void
{
    $pdo->exec("CREATE TABLE IF NOT EXISTS banks (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        code VARCHAR(64) NOT NULL,
        name_hy VARCHAR(190) NOT NULL DEFAULT '',
        name_ru VARCHAR(190) NOT NULL DEFAULT '',
        name_en VARCHAR(190) NOT NULL DEFAULT '',
        legal VARCHAR(190) NOT NULL DEFAULT '',
        phone VARCHAR(64) NOT NULL DEFAULT '',
        phone2 VARCHAR(64) NOT NULL DEFAULT '',
        email VARCHAR(190) NOT NULL DEFAULT '',
        website VARCHAR(255) NOT NULL DEFAULT '',
        swift VARCHAR(32) NOT NULL DEFAULT '',
        address_hy VARCHAR(255) NOT NULL DEFAULT '',
        address_ru VARCHAR(255) NOT NULL DEFAULT '',
        address_en VARCHAR(255) NOT NULL DEFAULT '',
        about_hy TEXT NULL,
        about_ru TEXT NULL,
        about_en TEXT NULL,
        logo_path VARCHAR(255) NULL,
        is_partner TINYINT(1) NOT NULL DEFAULT 0,
        partner_order INT NOT NULL DEFAULT 100,
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uq_banks_code (code),
        KEY idx_banks_partner (is_partner, partner_order)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS admin_users (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(64) NOT NULL,
        password_hash VARCHAR(255) NOT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        last_login TIMESTAMP NULL DEFAULT NULL,
        UNIQUE KEY uq_admin_username (username)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS admin_login_attempts (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        ip VARCHAR(45) NOT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        KEY idx_attempts_ip (ip, created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    pxq_seed_banks($pdo);
}

/** Первичное наполнение из includes/bank_data.php (только отсутствующие банки) */
function pxq_seed_banks(PDO $pdo): int
{
    $data = require __DIR__ . '/bank_data.php';
    $have = $pdo->query('SELECT code FROM banks')->fetchAll(PDO::FETCH_COLUMN);
    $have = array_flip($have);
    $ins = $pdo->prepare('INSERT INTO banks
        (code, name_hy, name_ru, name_en, legal, phone, phone2, email, website, swift,
         address_hy, address_ru, address_en, about_hy, about_ru, about_en)
        VALUES (:code,:nh,:nr,:ne,:legal,:phone,:phone2,:email,:website,:swift,:ah,:ar,:ae,:abh,:abr,:abe)');
    $n = 0;
    foreach ($data as $code => $m) {
        if (isset($have[$code])) continue;
        $ins->execute([
            'code' => $code,
            'nh' => $m['name']['hy'] ?? '', 'nr' => $m['name']['ru'] ?? '', 'ne' => $m['name']['en'] ?? '',
            'legal' => $m['legal'] ?? '', 'phone' => $m['phone'] ?? '', 'phone2' => $m['phone2'] ?? '',
            'email' => $m['email'] ?? '', 'website' => $m['website'] ?? '', 'swift' => $m['swift'] ?? '',
            'ah' => $m['address']['hy'] ?? '', 'ar' => $m['address']['ru'] ?? '', 'ae' => $m['address']['en'] ?? '',
            'abh' => $m['about']['hy'] ?? '', 'abr' => $m['about']['ru'] ?? '', 'abe' => $m['about']['en'] ?? '',
        ]);
        $n++;
    }
    return $n;
}
