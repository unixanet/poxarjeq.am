<?php
declare(strict_types=1);

/**
 * Управляемое содержимое сайта: тексты, меню, подвал, филиалы — всё редактируется в /admin/.
 * Любая проблема с БД молча сводится к значениям по умолчанию (content_defaults.php, lang/*.php).
 */

/* ---------- Файловый кэш чтения из БД (60 секунд, сбрасывается при сохранении в админке) ---------- */
function pxq_cache(string $name, callable $loader, int $ttl = 60): mixed
{
    static $mem = [];
    if (array_key_exists($name, $mem)) return $mem[$name];
    $f = CACHE_DIR . '/cms_' . $name . '.json';
    if (is_file($f) && (time() - filemtime($f)) < $ttl) {
        $d = json_decode((string)file_get_contents($f), true);
        if ($d !== null || trim((string)file_get_contents($f)) === 'null') return $mem[$name] = $d;
    }
    try {
        require_once __DIR__ . '/../config.db.php';
        $d = $loader(db());
        @file_put_contents($f, json_encode($d, JSON_UNESCAPED_UNICODE), LOCK_EX);
        return $mem[$name] = $d;
    } catch (Throwable $e) {
        if (is_file($f)) {
            $d = json_decode((string)file_get_contents($f), true);
            if ($d !== null) return $mem[$name] = $d;
        }
        return $mem[$name] = null;
    }
}
function pxq_cache_clear(): void
{
    foreach (glob(CACHE_DIR . '/cms_*.json') ?: [] as $f) @unlink($f);
}

/* ---------- Тексты ---------- */
function cms_defs(): array
{
    static $d = null;
    return $d ??= require __DIR__ . '/content_defaults.php';
}

/** Правки из админки: [lang][key] => value */
function cms_overrides(): array
{
    $r = pxq_cache('texts', function (PDO $pdo) {
        $out = [];
        foreach ($pdo->query('SELECT k, lang, value FROM site_texts') as $row) $out[$row['lang']][$row['k']] = $row['value'];
        return $out;
    });
    return is_array($r) ? $r : [];
}

/** Значение по умолчанию (то, что на сайте, пока в админке ничего не изменено) */
function cms_default(string $key, string $lang): ?string
{
    $defs = cms_defs();
    if (isset($defs['keys'][$key])) return $defs['keys'][$key][$lang] ?? $defs['keys'][$key]['hy'];
    if (isset($defs['legacy'][$key])) {
        static $files = [];
        $files[$lang] ??= is_file(__DIR__ . "/../lang/$lang.php") ? require __DIR__ . "/../lang/$lang.php" : [];
        return $files[$lang][$key] ?? null;
    }
    return null;
}

function t(string $key, ?array $dict = null): string
{
    global $L, $lang;
    $l = isset($lang) && in_array($lang, SUPPORTED_LANGS, true) ? $lang : DEFAULT_LANG;
    if ($dict === null) {
        $o = cms_overrides()[$l][$key] ?? null;
        if ($o !== null && $o !== '') return $o;
        if (isset($L[$key])) return $L[$key];
        return cms_default($key, $l) ?? $key;
    }
    return $dict[$key] ?? $key;
}

/** Подстановка {плейсхолдеров}; неизвестные убираются, любой обычный ключ текстов тоже доступен: {site_name} */
function cms_fill(string $s, array $vars = []): string
{
    return preg_replace_callback('/\{([a-z_][a-z0-9_]*)\}/i', function ($m) use ($vars) {
        $k = $m[1];
        if (array_key_exists($k, $vars)) return (string)$vars[$k];
        $v = t($k);
        return $v === $k ? '' : $v;
    }, $s);
}
function tv(string $key, array $vars = []): string
{
    return trim(cms_fill(t($key), $vars));
}

/** Простая разметка: **жирный**, пустая строка = новый абзац */
function md_inline(string $s): string
{
    $s = e($s);
    return preg_replace('/\*\*(.+?)\*\*/su', '<strong>$1</strong>', $s);
}
function md_paragraphs(string $s, string $pClass = ''): string
{
    $out = '';
    foreach (preg_split('/\R{2,}/u', trim($s)) as $p) {
        $p = trim($p);
        if ($p !== '') $out .= '<p' . ($pClass ? ' class="' . e($pClass) . '"' : '') . '>' . nl2br(md_inline($p), false) . '</p>';
    }
    return $out;
}
function tp(string $key, array $vars = [], string $pClass = ''): string
{
    return md_paragraphs(cms_fill(t($key), $vars), $pClass);
}

/** Заголовок и описание страницы из админки (SEO) */
function seo_meta(string $page, array $vars = []): array
{
    $clean = fn(string $s) => trim(preg_replace('/\s+/u', ' ', $s));
    return [$clean(cms_fill(t("seo.$page.title"), $vars)), $clean(cms_fill(t("seo.$page.description"), $vars))];
}

/* ---------- Данные валют (отдельный текст/SEO для каждой валюты) ---------- */
function currency_override(string $code, string $lang): array
{
    $all = pxq_cache('currency_pages', function (PDO $pdo) {
        $out = [];
        foreach ($pdo->query('SELECT * FROM currency_pages') as $r) $out[$r['code']][$r['lang']] = $r;
        return $out;
    });
    return is_array($all) ? ($all[strtoupper($code)][$lang] ?? []) : [];
}

/* ---------- Меню шапки и подвала ---------- */
function nav_defaults(): array
{
    $items = [];
    $mk = function (string $area, int $grp, string $url, callable $label, callable $title) use (&$items) {
        $row = ['area' => $area, 'grp' => $grp, 'url' => $url, 'new_tab' => 0, 'nofollow' => 0, 'is_active' => 1];
        foreach (SUPPORTED_LANGS as $l) { $row['label_' . $l] = $label($l); $row['title_' . $l] = $title($l); }
        $items[] = $row;
    };
    $tx = fn(string $k, string $l) => cms_default($k, $l) ?? $k;
    $cur = fn(string $c, string $l) => str_replace(['{name}', '{code}', '{rates_of}'], [currency_name($c, $l), $c, $tx('rates_of', $l)], cms_default('common.link_currency', $l) ?? '');
    $mk('header', 0, '/', fn($l) => $tx('home', $l), fn($l) => $tx('site_name', $l) . ' — ' . $tx('today_rates', $l));
    foreach (['USD', 'EUR', 'RUR'] as $c) $mk('header', 0, "/currency.php?cur=$c", fn($l) => $c, fn($l) => $cur($c, $l));
    $mk('header', 0, '/about.php', fn($l) => $tx('about', $l), fn($l) => $tx('about', $l) . ' — ' . $tx('site_name', $l));
    $mk('header', 0, '/contact.php', fn($l) => $tx('contact', $l), fn($l) => $tx('contact', $l) . ' — ' . $tx('site_name', $l));
    foreach (['USD', 'EUR', 'RUR', 'GBP'] as $c) $mk('footer', 1, "/currency.php?cur=$c", fn($l) => $c, fn($l) => $cur($c, $l));
    $mk('footer', 2, '/about.php', fn($l) => $tx('about', $l), fn($l) => $tx('about', $l) . ' — ' . $tx('site_name', $l));
    $mk('footer', 2, '/contact.php', fn($l) => $tx('contact', $l), fn($l) => $tx('contact', $l) . ' — ' . $tx('site_name', $l));
    $mk('footer', 2, '/privacy.php', fn($l) => $tx('privacy', $l), fn($l) => $tx('privacy', $l) . ' — ' . $tx('site_name', $l));
    $mk('footer', 2, '/disclaimer.php', fn($l) => $tx('disclaimer', $l), fn($l) => $tx('disclaimer', $l) . ' — ' . $tx('site_name', $l));
    $bk = require __DIR__ . '/bank_data.php';
    foreach (array_slice(array_keys($bk), 0, 6) as $code) {
        $mk('footer', 3, '/' . $code, fn($l) => $bk[$code]['name'][$l] ?? $code, fn($l) => str_replace('{bank}', $bk[$code]['name'][$l] ?? $code, cms_default('common.link_bank', $l) ?? ''));
    }
    $mk('footer', 4, 'mailto:' . CONTACT_EMAIL, fn($l) => CONTACT_EMAIL, fn($l) => $tx('email', $l) . ': ' . CONTACT_EMAIL);
    return $items;
}

/** @return array<int,array> активные пункты области ('header' | 'footer') в порядке из админки */
function cms_nav(string $area): array
{
    $rows = pxq_cache('nav', function (PDO $pdo) {
        return $pdo->query('SELECT * FROM nav_links WHERE is_active = 1 ORDER BY grp, sort, id')->fetchAll();
    });
    if (!is_array($rows) || !$rows) $rows = nav_defaults();   // пока в админке меню не создано (или БД недоступна)
    return array_values(array_filter($rows, fn($r) => $r['area'] === $area));
}
function nav_label(array $r, string $lang): string
{
    return $r['label_' . $lang] !== '' ? $r['label_' . $lang] : ($r['label_hy'] ?: $r['url']);
}
function nav_title(array $r, string $lang): string
{
    $t = $r['title_' . $lang] ?? '';
    return $t !== '' ? $t : nav_label($r, $lang);
}
/** Совпадает ли ссылка меню с текущей страницей */
function nav_is_active(string $url): bool
{
    $u = parse_url($url);
    $cur = parse_url($_SERVER['REQUEST_URI'] ?? '/');
    $path = rtrim($u['path'] ?? '/', '/') ?: '/';
    $curPath = rtrim($cur['path'] ?? '/', '/') ?: '/';
    if ($curPath === '/index.php') $curPath = '/';
    if ($path !== $curPath) return false;
    parse_str($u['query'] ?? '', $q);
    if (isset($q['cur'])) return strtoupper((string)($_GET['cur'] ?? '')) === strtoupper((string)$q['cur']);
    return true;
}
