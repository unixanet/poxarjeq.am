<?php
declare(strict_types=1);

/**
 * Данные банков из админки (MySQL): названия, логотипы, партнёры, контакты.
 * Публичный сайт читает их через маленький файловый кэш (60 с) и при любой
 * проблеме с БД спокойно работает на данных из bank_data.php и API.
 */

const BANK_STORE_TTL = 60;

function bank_store_cache_file(): string
{
    return CACHE_DIR . '/bank_overrides.json';
}

/** @return array<string,array> code => строка таблицы banks */
function bank_overrides(): array
{
    static $mem = null;
    if ($mem !== null) return $mem;

    $f = bank_store_cache_file();
    if (is_file($f) && (time() - filemtime($f)) < BANK_STORE_TTL) {
        $d = json_decode((string)file_get_contents($f), true);
        if (is_array($d)) return $mem = $d;
    }
    try {
        require_once __DIR__ . '/../config.db.php';
        $rows = db()->query('SELECT * FROM banks')->fetchAll();
        $out = [];
        foreach ($rows as $r) $out[$r['code']] = $r;
        @file_put_contents($f, json_encode($out, JSON_UNESCAPED_UNICODE), LOCK_EX);
        return $mem = $out;
    } catch (Throwable $e) {
        if (is_file($f)) { // база недоступна: берём последнюю удачную копию
            $d = json_decode((string)file_get_contents($f), true);
            if (is_array($d)) return $mem = $d;
        }
        return $mem = [];
    }
}

function bank_store_clear_cache(): void
{
    @unlink(bank_store_cache_file());
}

function bank_override(string $code): ?array
{
    return bank_overrides()[$code] ?? null;
}

/** Адрес логотипа: загруженный в админке, иначе из API */
function bank_logo_url(string $code, ?string $apiLogo = null): string
{
    $o = bank_override($code);
    if ($o && !empty($o['logo_path']) && is_file(__DIR__ . '/../' . ltrim($o['logo_path'], '/'))) {
        return '/' . ltrim($o['logo_path'], '/');
    }
    return (string)$apiLogo;
}

function bank_is_partner(string $code): bool
{
    $o = bank_override($code);
    return $o && (int)$o['is_partner'] === 1;
}

function bank_partner_order(string $code): int
{
    $o = bank_override($code);
    return $o ? (int)$o['partner_order'] : 100;
}

/** Скрытые в админке банки не показываются в таблицах курсов */
function bank_is_visible(string $code): bool
{
    $o = bank_override($code);
    return !$o || (int)$o['is_active'] === 1;
}

/** Поверх статичных данных из bank_data.php кладём непустые поля из БД */
function bank_apply_overrides(string $code, ?array $meta): ?array
{
    $o = bank_override($code);
    if (!$o) return $meta;
    $meta = $meta ?? ['name' => [], 'address' => [], 'about' => []];
    foreach (['hy', 'ru', 'en'] as $l) {
        if (($o['name_' . $l] ?? '') !== '')    $meta['name'][$l]    = $o['name_' . $l];
        if (($o['address_' . $l] ?? '') !== '') $meta['address'][$l] = $o['address_' . $l];
        if (($o['about_' . $l] ?? '') !== '')   $meta['about'][$l]   = $o['about_' . $l];
    }
    foreach (['legal', 'phone', 'phone2', 'email', 'website', 'swift'] as $k) {
        if (($o[$k] ?? '') !== '') $meta[$k] = $o[$k];
    }
    return $meta;
}
