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

    pxq_ensure_column($pdo, 'banks', 'seo_title_hy', "VARCHAR(255) NOT NULL DEFAULT ''");
    foreach (['seo_title_ru', 'seo_title_en', 'seo_description_hy', 'seo_description_ru', 'seo_description_en'] as $c) {
        pxq_ensure_column($pdo, 'banks', $c, "VARCHAR(500) NOT NULL DEFAULT ''");
    }

    $pdo->exec("CREATE TABLE IF NOT EXISTS site_texts (
        k VARCHAR(120) NOT NULL,
        lang CHAR(2) NOT NULL,
        value MEDIUMTEXT NOT NULL,
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (k, lang)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS currency_pages (
        code CHAR(3) NOT NULL,
        lang CHAR(2) NOT NULL,
        name VARCHAR(190) NOT NULL DEFAULT '',
        h1 VARCHAR(255) NOT NULL DEFAULT '',
        seo_title VARCHAR(255) NOT NULL DEFAULT '',
        seo_description VARCHAR(500) NOT NULL DEFAULT '',
        lead TEXT NULL,
        about_title VARCHAR(255) NOT NULL DEFAULT '',
        about_body MEDIUMTEXT NULL,
        PRIMARY KEY (code, lang)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS nav_links (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        area VARCHAR(10) NOT NULL,
        grp TINYINT NOT NULL DEFAULT 0,
        sort INT NOT NULL DEFAULT 100,
        url VARCHAR(255) NOT NULL,
        label_hy VARCHAR(190) NOT NULL DEFAULT '',
        label_ru VARCHAR(190) NOT NULL DEFAULT '',
        label_en VARCHAR(190) NOT NULL DEFAULT '',
        title_hy VARCHAR(255) NOT NULL DEFAULT '',
        title_ru VARCHAR(255) NOT NULL DEFAULT '',
        title_en VARCHAR(255) NOT NULL DEFAULT '',
        new_tab TINYINT(1) NOT NULL DEFAULT 0,
        nofollow TINYINT(1) NOT NULL DEFAULT 0,
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        KEY idx_nav_area (area, grp, sort)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS branches (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        bank_code VARCHAR(64) NOT NULL,
        slug VARCHAR(80) NOT NULL,
        city_hy VARCHAR(190) NOT NULL DEFAULT '', city_ru VARCHAR(190) NOT NULL DEFAULT '', city_en VARCHAR(190) NOT NULL DEFAULT '',
        name_hy VARCHAR(190) NOT NULL DEFAULT '', name_ru VARCHAR(190) NOT NULL DEFAULT '', name_en VARCHAR(190) NOT NULL DEFAULT '',
        address_hy VARCHAR(255) NOT NULL DEFAULT '', address_ru VARCHAR(255) NOT NULL DEFAULT '', address_en VARCHAR(255) NOT NULL DEFAULT '',
        phone VARCHAR(64) NOT NULL DEFAULT '',
        sort INT NOT NULL DEFAULT 100,
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        UNIQUE KEY uq_branch (bank_code, slug)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS contact_messages (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(190) NOT NULL,
        email VARCHAR(190) NOT NULL,
        message TEXT NOT NULL,
        lang CHAR(2) NOT NULL DEFAULT 'hy',
        ip VARCHAR(45) NOT NULL DEFAULT '',
        is_read TINYINT(1) NOT NULL DEFAULT 0,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        KEY idx_msg_read (is_read, created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    pxq_seed_banks($pdo);
    pxq_seed_nav($pdo);
    pxq_seed_branches($pdo);
}

function pxq_ensure_column(PDO $pdo, string $table, string $col, string $def): void
{
    $st = $pdo->prepare('SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?');
    $st->execute([$table, $col]);
    if ((int)$st->fetchColumn() === 0) $pdo->exec("ALTER TABLE `$table` ADD COLUMN `$col` $def");
}

/** Меню по умолчанию — только когда таблица пуста */
function pxq_seed_nav(PDO $pdo): void
{
    if ((int)$pdo->query('SELECT COUNT(*) FROM nav_links')->fetchColumn() > 0) return;
    $ins = $pdo->prepare('INSERT INTO nav_links (area, grp, sort, url, label_hy, label_ru, label_en, title_hy, title_ru, title_en, new_tab, nofollow, is_active)
        VALUES (:area,:grp,:sort,:url,:lh,:lr,:le,:th,:tr,:te,0,0,1)');
    $i = 0;
    foreach (nav_defaults() as $r) {
        $ins->execute(['area' => $r['area'], 'grp' => $r['grp'], 'sort' => (++$i) * 10, 'url' => $r['url'],
            'lh' => $r['label_hy'], 'lr' => $r['label_ru'], 'le' => $r['label_en'], 'th' => $r['title_hy'], 'tr' => $r['title_ru'], 'te' => $r['title_en']]);
    }
}

/** Филиалы из includes/branches_data.php — один раз, когда таблица пуста */
function pxq_seed_branches(PDO $pdo): void
{
    if ((int)$pdo->query('SELECT COUNT(*) FROM branches')->fetchColumn() > 0) return;
    $file = __DIR__ . '/branches_data.php';
    if (!is_file($file)) return;
    $data = require $file;
    $ins = $pdo->prepare('INSERT IGNORE INTO branches (bank_code, slug, city_hy, city_ru, city_en, name_hy, name_ru, name_en, address_hy, address_ru, address_en, phone, sort)
        VALUES (:b,:s,:ch,:cr,:ce,:nh,:nr,:ne,:ah,:ar,:ae,:p,:o)');
    foreach ($data as $bank => $list) {
        foreach ($list as $i => $br) {
            $ins->execute(['b' => $bank, 's' => $br['slug'], 'ch' => $br['city']['hy'] ?? '', 'cr' => $br['city']['ru'] ?? '', 'ce' => $br['city']['en'] ?? '',
                'nh' => $br['name']['hy'] ?? '', 'nr' => $br['name']['ru'] ?? '', 'ne' => $br['name']['en'] ?? '',
                'ah' => $br['address']['hy'] ?? '', 'ar' => $br['address']['ru'] ?? '', 'ae' => $br['address']['en'] ?? '',
                'p' => $br['phone'] ?? '', 'o' => ($i + 1) * 10]);
        }
    }
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
