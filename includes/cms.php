<?php
declare(strict_types=1);

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

function cms_defs(): array
{
    static $d = null;
    if ($d !== null) return $d;
    $d = require __DIR__ . '/content_defaults.php';
    $v2 = require __DIR__ . '/content_v2.php';
    $d['groups'] = array_replace($d['groups'], $v2['groups']);
    $d['keys'] = array_replace($d['keys'], $v2['keys']);
    foreach ($v2['keys'] as $k => $_) unset($d['legacy'][$k]);
    return $d;
}

const TEXTS_VERSION = 2;

function texts_migrate(PDO $pdo): void
{
    $v = $pdo->query("SELECT value FROM site_texts WHERE k = '_texts_version' AND lang = 'xx'")->fetchColumn();
    if ((int)$v >= TEXTS_VERSION) return;
    $v2 = require __DIR__ . '/content_v2.php';
    $keys = array_keys($v2['keys']);
    foreach (['site_name', 'site_tagline', 'today_rates', 'best_rates', 'hero_title', 'hero_sub', 'footer_text', 'disclaimer_short', 'disclaimer_full', 'buy', 'sell', 'best_buy', 'best_sell', 'avg_buy', 'updated', 'best_marked', 'tip2', 'about', 'calc_title', 'calc_btn', 'cta_banks', 'rates_of', 'faq'] as $k) $keys[] = $k;
    $del = $pdo->prepare("DELETE FROM site_texts WHERE k = ? AND lang IN ('hy','ru','en')");
    foreach ($keys as $k) $del->execute([$k]);
    $pdo->prepare("REPLACE INTO site_texts (k, lang, value) VALUES ('_texts_version', 'xx', ?)")->execute([(string)TEXTS_VERSION]);
}

function cms_overrides(): array
{
    $r = pxq_cache('texts', function (PDO $pdo) {
        texts_migrate($pdo);
        $out = [];
        foreach ($pdo->query("SELECT k, lang, value FROM site_texts WHERE lang <> 'xx'") as $row) $out[$row['lang']][$row['k']] = $row['value'];
        return $out;
    });
    return is_array($r) ? $r : [];
}

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

function cms_text_in(string $key, string $lang, array $vars = []): string
{
    $o = cms_overrides()[$lang][$key] ?? null;
    $tpl = ($o !== null && $o !== '') ? $o : (cms_default($key, $lang) ?? $key);
    return trim(preg_replace('/\s+/u', ' ', preg_replace_callback('/\{([a-z_][a-z0-9_]*)\}/i', function ($m) use ($vars, $lang) {
        if (array_key_exists($m[1], $vars)) return (string)$vars[$m[1]];
        $v = cms_text_in($m[1], $lang);
        return $v === $m[1] ? '' : $v;
    }, $tpl)));
}

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

function seo_meta(string $page, array $vars = []): array
{
    $clean = fn(string $s) => trim(preg_replace('/\s+/u', ' ', $s));
    return [$clean(cms_fill(t("seo.$page.title"), $vars)), $clean(cms_fill(t("seo.$page.description"), $vars))];
}

function currency_override(string $code, string $lang): array
{
    $all = pxq_cache('currency_pages', function (PDO $pdo) {
        $out = [];
        foreach ($pdo->query('SELECT * FROM currency_pages') as $r) $out[$r['code']][$r['lang']] = $r;
        return $out;
    });
    return is_array($all) ? ($all[strtoupper($code)][$lang] ?? []) : [];
}

const NAV_VERSION = 2;

function nav_defaults(): array
{
    $items = [];
    $mk = function (string $area, int $grp, string $url, array $label, array $title) use (&$items) {
        $row = ['area' => $area, 'grp' => $grp, 'url' => $url, 'new_tab' => 0, 'nofollow' => 0, 'is_active' => 1];
        foreach (SUPPORTED_LANGS as $l) { $row['label_' . $l] = $label[$l]; $row['title_' . $l] = $title[$l]; }
        $items[] = $row;
    };
    $tr = fn(string $k) => ['hy' => cms_default($k, 'hy') ?? $k, 'ru' => cms_default($k, 'ru') ?? $k, 'en' => cms_default($k, 'en') ?? $k];
    foreach (['#currencies' => 'menu.currencies', '#banks' => 'menu.banks', '/poxanakman-keter' => 'menu.exchange', '/voski' => 'menu.gold', '/news' => 'menu.news', '/about.php' => 'menu.about'] as $url => $k) {
        $mk('header', 0, $url, $tr($k), $tr($k . '_title'));
    }
    foreach (['USD', 'EUR', 'RUR', 'GEL', 'GBP', 'CHF', 'CNY', 'AED'] as $c) {
        $lab = []; $tit = [];
        foreach (SUPPORTED_LANGS as $l) { $lab[$l] = cur_menu_label($c, $l); $tit[$l] = cur_h1($c, $l) . ' — ' . cms_default('site_name', $l); }
        $mk('footer', 1, "/currency.php?cur=$c", $lab, $tit);
    }
    $bk = require __DIR__ . '/bank_data.php';
    foreach (['acba-bank', 'hayekonombank', 'araratbank', 'ardshinbank', 'inekobank', 'yunibank', 'evocabank', 'ameriabank', 'konvers-bank', 'fast-bank'] as $code) {
        if (!isset($bk[$code])) continue;
        $lab = []; $tit = [];
        foreach (SUPPORTED_LANGS as $l) {
            $n = $bk[$code]['name'][$l] ?? $code;
            $lab[$l] = $n;
            $tit[$l] = str_replace('{bank}', $n, cms_default('common.link_bank', $l) ?? '');
        }
        $mk('footer', 2, '/' . $code, $lab, $tit);
    }
    $mk('footer', 2, '/banks', $tr('menu.all_banks'), $tr('menu.all_banks_title'));
    $mk('footer', 3, '/poxanakman-keter', $tr('menu.exchange'), $tr('menu.exchange_title'));
    $mk('footer', 3, '/voski', $tr('menu.gold'), $tr('menu.gold_title'));
    $mk('footer', 3, '/news', $tr('menu.news'), $tr('menu.news_title'));
    $mk('footer', 3, '/#calc', $tr('menu.calc'), $tr('menu.calc_title'));
    $mk('footer', 4, '/about.php', $tr('menu.about'), $tr('menu.about_title'));
    $mk('footer', 4, '/contact.php', $tr('menu.contact'), $tr('menu.contact_title'));
    $mk('footer', 4, '/privacy.php', $tr('menu.privacy'), $tr('menu.privacy_title'));
    $mk('footer', 4, '/disclaimer.php', $tr('menu.disclaimer'), $tr('menu.disclaimer_title'));
    return $items;
}

function nav_reseed(PDO $pdo): void
{
    $pdo->exec('DELETE FROM nav_links');
    $ins = $pdo->prepare('INSERT INTO nav_links (area, grp, sort, url, label_hy, label_ru, label_en, title_hy, title_ru, title_en, new_tab, nofollow, is_active)
        VALUES (:area,:grp,:sort,:url,:lh,:lr,:le,:th,:tr,:te,0,0,1)');
    $i = 0;
    foreach (nav_defaults() as $r) {
        $ins->execute(['area' => $r['area'], 'grp' => $r['grp'], 'sort' => (++$i) * 10, 'url' => $r['url'],
            'lh' => $r['label_hy'], 'lr' => $r['label_ru'], 'le' => $r['label_en'], 'th' => $r['title_hy'], 'tr' => $r['title_ru'], 'te' => $r['title_en']]);
    }
    $pdo->prepare("REPLACE INTO site_texts (k, lang, value) VALUES ('_nav_version', 'xx', ?)")->execute([(string)NAV_VERSION]);
}

function cms_nav(string $area): array
{
    $rows = pxq_cache('nav', function (PDO $pdo) {
        $v = $pdo->query("SELECT value FROM site_texts WHERE k = '_nav_version' AND lang = 'xx'")->fetchColumn();
        if ((int)$v < NAV_VERSION) nav_reseed($pdo);
        return $pdo->query('SELECT * FROM nav_links WHERE is_active = 1 ORDER BY grp, sort, id')->fetchAll();
    });
    if (!is_array($rows) || !$rows) $rows = nav_defaults();
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

function nav_is_active(string $url): bool
{
    if ($url === '' || $url[0] === '#') return false;
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
