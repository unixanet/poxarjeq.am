<?php
declare(strict_types=1);

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/bank_store.php';
require_once __DIR__ . '/cms.php';
require_once __DIR__ . '/ui.php';
require_once __DIR__ . '/currency_seo.php';
require_once __DIR__ . '/bank_seo.php';
require_once __DIR__ . '/news_ui.php';
require_once __DIR__ . '/cities.php';
require_once __DIR__ . '/llms.php';
require_once __DIR__ . '/seo_audit.php';

function detect_lang(): string
{
    if (!array_key_exists('lang', $_GET)) return DEFAULT_LANG;
    $q = $_GET['lang'];
    if (is_string($q) && in_array($q, SUPPORTED_LANGS, true) && $q !== DEFAULT_LANG) return $q;
    if (in_array($_SERVER['REQUEST_METHOD'] ?? 'GET', ['GET', 'HEAD'], true) && !headers_sent()) {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';
        $query = [];
        parse_str(parse_url($uri, PHP_URL_QUERY) ?? '', $query);
        unset($query['lang']);
        header('Location: ' . $path . ($query ? '?' . http_build_query($query) : ''), true, 301);
        exit;
    }
    return DEFAULT_LANG;
}

function lang_suffix(string $lang): string
{
    return $lang === DEFAULT_LANG ? '' : $lang;
}

function with_lang(string $path, string $lang): string
{
    if ($lang === DEFAULT_LANG) return $path;
    $frag = '';
    if (($h = strpos($path, '#')) !== false) { $frag = substr($path, $h); $path = substr($path, 0, $h); }
    return $path . (str_contains($path, '?') ? '&' : '?') . 'lang=' . $lang . $frag;
}

function lurl(string $path): string
{
    global $lang;
    return with_lang($path, $lang ?? DEFAULT_LANG);
}

function page_urls(string $path): array
{
    $base = rtrim(SITE_URL, '/');
    $out = [];
    foreach (SUPPORTED_LANGS as $l) $out[$l] = $base . with_lang($path, $l);
    return $out;
}

function get_multi_currency_table(array $currencies = ['USD','EUR','RUR','GBP','GEL']): array
{
    $byBank = [];
    $updated = null;
    foreach ($currencies as $cur) {
        $data = get_banks_for_currency($cur);
        if (!$data || empty($data['banks'])) continue;
        if (!$updated && !empty($data['updated_at'])) $updated = $data['updated_at'];
        foreach ($data['banks'] as $b) {
            $code = $b['code'];
            if (!isset($byBank[$code])) {
                $byBank[$code] = [
                    'code' => $code,
                    'name' => $b['name'] ?? $code,
                    'logo' => $b['logo'] ?? '',
                    'rates' => [],
                ];
            }
            $byBank[$code]['rates'][$cur] = [
                'buy' => $b['buy'] ?? null,
                'sell' => $b['sell'] ?? null,
            ];
        }
    }

    $bestBuy = [];
    foreach ($currencies as $cur) {
        $best = null;
        foreach ($byBank as $b) {
            $v = $b['rates'][$cur]['buy'] ?? null;
            if ($v !== null && ($best === null || $v > $best)) $best = $v;
        }
        $bestBuy[$cur] = $best;
    }
    return ['banks' => array_values($byBank), 'updated_at' => $updated, 'best_buy' => $bestBuy, 'currencies' => $currencies];
}

function lang_switch_url(string $lang): string
{
    $uri = $_SERVER['REQUEST_URI'] ?? '/';
    $path = parse_url($uri, PHP_URL_PATH) ?: '/';
    $query = [];
    parse_str(parse_url($uri, PHP_URL_QUERY) ?? '', $query);
    unset($query['lang']);
    if ($lang !== DEFAULT_LANG) $query['lang'] = $lang;
    return $path . ($query ? '?' . http_build_query($query) : '');
}

function load_lang(string $lang): array
{
    $file = __DIR__ . '/../lang/' . $lang . '.php';
    if (file_exists($file)) {
        return require $file;
    }
    return require __DIR__ . '/../lang/hy.php';
}

function lang_url(string $lang): string
{
    return lang_switch_url($lang);
}

function api_cache_file(string $url): string
{
    return CACHE_DIR . '/' . md5($url) . '.json';
}

function api_url(string $endpoint, array $params = []): string
{
    return API_BASE . '/' . ltrim($endpoint, '/') . ($params ? '?' . http_build_query($params) : '');
}

function api_fetch_raw(string $url, int $timeout = 8): ?string
{
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => true, CURLOPT_CONNECTTIMEOUT => 4, CURLOPT_TIMEOUT => $timeout,
            CURLOPT_USERAGENT => 'Poxarjeq.am/2.0', CURLOPT_HTTPHEADER => ['Accept: application/json'], CURLOPT_ENCODING => '',
        ]);
        $raw = curl_exec($ch);
        $http = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return ($http === 200 && is_string($raw)) ? $raw : null;
    }
    $ctx = stream_context_create(['http' => ['method' => 'GET', 'header' => "Accept: application/json\r\nUser-Agent: Poxarjeq.am/2.0\r\n", 'timeout' => $timeout, 'ignore_errors' => true]]);
    $raw = @file_get_contents($url, false, $ctx);
    return $raw === false ? null : $raw;
}

function api_store(string $url, ?string $raw): ?array
{
    $data = $raw !== null ? json_decode($raw, true) : null;
    if (!is_array($data) || isset($data['error'])) return null;
    @file_put_contents(api_cache_file($url), $raw, LOCK_EX);
    return $data;
}

function api_schedule_refresh(string $url): void
{
    static $queue = [], $registered = false;
    $queue[$url] = true;
    if ($registered) return;
    $registered = true;
    register_shutdown_function(function () use (&$queue) {
        if (function_exists('fastcgi_finish_request')) fastcgi_finish_request();
        elseif (function_exists('litespeed_finish_request')) litespeed_finish_request();
        foreach (array_keys($queue) as $u) {
            $lock = api_cache_file($u) . '.lock';
            if (is_file($lock) && time() - filemtime($lock) < 60) continue;
            @touch($lock);
            api_store($u, api_fetch_raw($u, 10));
            @unlink($lock);
        }
    });
}

function api_can_defer(): bool
{
    return function_exists('fastcgi_finish_request') || function_exists('litespeed_finish_request');
}

function api_get(string $endpoint, array $params = [], int $ttl = CACHE_TTL): ?array
{
    $url = api_url($endpoint, $params);
    $f = api_cache_file($url);
    $age = is_file($f) ? time() - (int)filemtime($f) : null;
    if ($age !== null && $age < $ttl) {
        $d = json_decode((string)file_get_contents($f), true);
        if (is_array($d)) return $d;
    }
    if ($age !== null && $age < 86400 && api_can_defer()) {
        $d = json_decode((string)file_get_contents($f), true);
        if (is_array($d)) { api_schedule_refresh($url); return $d; }
    }
    $d = api_store($url, api_fetch_raw($url, 8));
    if ($d !== null) return $d;
    if ($age !== null) {
        $d = json_decode((string)file_get_contents($f), true);
        if (is_array($d)) return $d;
    }
    return null;
}

function get_rates_summary(): ?array { return api_get('rates.php'); }
function get_banks_for_currency(string $cur = 'USD'): ?array { return api_get('banks.php', ['cur' => strtoupper($cur)]); }
function get_bank(string $code): ?array { return api_get('bank.php', ['code' => $code]); }
function get_banks_list(): ?array { return api_get('list.php'); }
function api_v2(string $endpoint, array $params = [], int $ttl = CACHE_TTL): ?array { return api_get('v2/' . $endpoint, $params, $ttl); }
function get_history(string $cur = 'USD', int $days = 30): ?array { return api_get('history.php', ['cur' => strtoupper($cur), 'days' => $days]); }
function get_currencies(): ?array { return api_get('currencies.php'); }

function currency_name(string $code, string $lang = 'hy'): string
{
    $map = [
        'USD' => ['hy' => 'ԱՄՆ դոլար', 'ru' => 'Доллар США', 'en' => 'US Dollar'],
        'EUR' => ['hy' => 'Եվրո', 'ru' => 'Евро', 'en' => 'Euro'],
        'RUR' => ['hy' => 'Ռուսական ռուբլի', 'ru' => 'Российский рубль', 'en' => 'Russian Ruble'],
        'GBP' => ['hy' => 'Բրիտանական ֆունտ', 'ru' => 'Фунт стерлингов', 'en' => 'British Pound'],
        'CHF' => ['hy' => 'Շվեյցարական ֆրանկ', 'ru' => 'Швейцарский франк', 'en' => 'Swiss Franc'],
        'GEL' => ['hy' => 'Վրացական լարի', 'ru' => 'Грузинский лари', 'en' => 'Georgian Lari'],
        'AED' => ['hy' => 'ԱՄԷ դիրհամ', 'ru' => 'Дирхам ОАЭ', 'en' => 'UAE Dirham'],
        'AUD' => ['hy' => 'Ավստրալական դոլար', 'ru' => 'Австралийский доллар', 'en' => 'Australian Dollar'],
        'CAD' => ['hy' => 'Կանադական դոլար', 'ru' => 'Канадский доллар', 'en' => 'Canadian Dollar'],
        'CNY' => ['hy' => 'Չինական յուան', 'ru' => 'Китайский юань', 'en' => 'Chinese Yuan'],
        'JPY' => ['hy' => 'Ճապոնական իեն', 'ru' => 'Японская иена', 'en' => 'Japanese Yen'],
        'KZT' => ['hy' => 'Ղազախական տենգե', 'ru' => 'Казахстанский тенге', 'en' => 'Kazakhstani Tenge'],
        'XAU' => ['hy' => 'Ոսկի', 'ru' => 'Золото', 'en' => 'Gold'],
    ];
    $code = strtoupper($code);
    return $map[$code][$lang] ?? $code;
}

function format_rate(?float $n, int $d = 2): string
{
    if ($n === null) return '—';
    return number_format($n, $d, '.', ' ');
}

function format_datetime(?string $iso): string
{
    if (!$iso) return '';
    try {
        $dt = new DateTime($iso);
        $dt->setTimezone(new DateTimeZone('Asia/Yerevan'));
        return $dt->format('d.m.Y H:i');
    } catch (Exception $e) {
        return $iso;
    }
}

function e(?string $s): string
{
    return htmlspecialchars((string)$s, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

function calc_spread(float $buy, float $sell): float
{
    if ($buy <= 0) return 0.0;
    return round((($sell - $buy) / $buy) * 100, 2);
}

function currency_flag(string $code): string
{
    $code = strtoupper(preg_replace('/[^A-Za-z]/', '', $code));
    if ($code !== '' && is_file(__DIR__ . '/../assets/flags/' . $code . '.png')) {
        return '/assets/flags/' . $code . '.png';
    }
    return '/assets/flags/GLOBE.png';
}

function lang_flag(string $lang): string
{
    $lang = strtolower($lang);
    if (!in_array($lang, ['hy','ru','en'], true)) $lang = 'hy';
    return '/assets/lang/' . $lang . '.png';
}

function currency_flags_map(): array
{
    $map = [];
    foreach (array_unique(array_merge(all_currency_codes(), table_currency_codes())) as $c) {
        $map[$c] = currency_flag($c);
    }
    return $map;
}

function bank_page_url(string $code): string
{
    return '/' . preg_replace('/[^a-z0-9\-]/', '', strtolower($code));
}

function branch_page_url(string $code, string $citySlug): string
{
    return '/' . preg_replace('/[^a-z0-9\-]/', '', strtolower($code)) . '/' . preg_replace('/[^a-z0-9\-]/', '', strtolower($citySlug));
}

function get_bank_meta(string $code): ?array
{
    static $data = null;
    if ($data === null) {
        $data = require __DIR__ . '/bank_data.php';
    }
    if (!isset($data[$code]) && !bank_override($code)) return null;
    $meta = bank_apply_overrides($code, $data[$code] ?? null);
    $db = bank_branches_from_db();
    if ($db !== null) {
        if (!empty($db[$code])) $meta['branches'] = $db[$code];
        return $meta;
    }
    static $branches = null;
    if ($branches === null) {
        $bf = __DIR__ . "/branches_data.php";
        $branches = file_exists($bf) ? require $bf : [];
    }
    if (!empty($branches[$code])) $meta["branches"] = $branches[$code];
    return $meta;
}

function bank_display_name(string $code, string $lang, ?string $apiName = null): string
{
    $meta = get_bank_meta($code);
    if ($meta && !empty($meta['name'][$lang])) {
        return $meta['name'][$lang];
    }
    return $apiName ?? $code;
}

function all_currency_codes(): array
{
    return ['AED','AUD','CAD','CHF','CNY','EUR','GBP','GEL','JPY','RUR','USD'];
}

function table_currency_codes(): array
{
    static $codes = null;
    if ($codes !== null) return $codes;
    $priority = ['USD','EUR','RUR','GBP','GEL','CHF','CNY','AED','KZT','CAD','AUD','JPY','XAU'];
    $found = [];
    $data = api_get('currencies.php', [], 3600);
    if ($data && !empty($data['currencies']) && is_array($data['currencies'])) {
        foreach ($data['currencies'] as $c) {
            $c = strtoupper(trim((string)$c));
            if (preg_match('/^[A-Z]{3}$/', $c) && $c !== 'AMD') $found[] = $c;
        }
    }
    if (!$found) $found = all_currency_codes();
    usort($found, function ($a, $b) use ($priority) {
        $ia = array_search($a, $priority, true); $ib = array_search($b, $priority, true);
        $ia = $ia === false ? 999 : $ia; $ib = $ib === false ? 999 : $ib;
        return $ia <=> $ib ?: strcmp($a, $b);
    });
    return $codes = array_values(array_unique($found));
}

function api_get_many(array $reqs, int $ttl = CACHE_TTL): array
{
    $out = [];
    $pending = [];
    foreach ($reqs as $key => [$endpoint, $params]) {
        $url = api_url($endpoint, $params);
        $f = api_cache_file($url);
        $age = is_file($f) ? time() - (int)filemtime($f) : null;
        $d = $age !== null ? json_decode((string)file_get_contents($f), true) : null;
        if (is_array($d) && $age < $ttl) { $out[$key] = $d; continue; }
        if (is_array($d) && $age < 86400 && api_can_defer()) { $out[$key] = $d; api_schedule_refresh($url); continue; }
        $pending[$key] = [$url, $f, is_array($d) ? $d : null];
    }
    if (!$pending) return $out;
    if (!function_exists('curl_multi_init')) {
        foreach ($pending as $key => [$url, $f, $old]) $out[$key] = api_store($url, api_fetch_raw($url, 8)) ?? $old;
        return $out;
    }
    $mh = curl_multi_init();
    $handles = [];
    foreach ($pending as $key => [$url]) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => true, CURLOPT_CONNECTTIMEOUT => 4, CURLOPT_TIMEOUT => 8,
            CURLOPT_USERAGENT => 'Poxarjeq.am/2.0', CURLOPT_HTTPHEADER => ['Accept: application/json'], CURLOPT_ENCODING => '',
        ]);
        curl_multi_add_handle($mh, $ch);
        $handles[$key] = $ch;
    }
    do {
        $status = curl_multi_exec($mh, $running);
        if ($running) curl_multi_select($mh, 1.0);
    } while ($running && $status === CURLM_OK);
    foreach ($handles as $key => $ch) {
        [$url, $f, $old] = $pending[$key];
        $raw = curl_multi_getcontent($ch);
        $http = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $out[$key] = api_store($url, ($http === 200 && is_string($raw)) ? $raw : null) ?? $old;
        curl_multi_remove_handle($mh, $ch);
        curl_close($ch);
    }
    curl_multi_close($mh);
    return $out;
}

function get_rates_matrix(array $currencies): array
{
    $reqs = [];
    foreach ($currencies as $c) $reqs[$c] = ['banks.php', ['cur' => $c]];
    $res = api_get_many($reqs);

    $banks = [];
    $updated = null;
    $have = [];
    foreach ($currencies as $cur) {
        $d = $res[$cur] ?? null;
        if (!$d || empty($d['banks'])) continue;
        $have[] = $cur;
        if (!$updated && !empty($d['updated_at'])) $updated = $d['updated_at'];
        foreach ($d['banks'] as $b) {
            $code = (string)($b['code'] ?? '');
            if ($code === '') continue;
            if (!isset($banks[$code])) {
                $banks[$code] = ['code' => $code, 'name' => $b['name'] ?? $code, 'logo' => $b['logo'] ?? '', 'rates' => []];
            }
            $buy = isset($b['buy']) ? (float)$b['buy'] : 0.0;
            $sell = isset($b['sell']) ? (float)$b['sell'] : 0.0;
            if ($buy > 0 && $sell > 0) $banks[$code]['rates'][$cur] = ['buy' => $buy, 'sell' => $sell];
        }
    }
    return ['banks' => $banks, 'updated_at' => $updated, 'currencies' => $have];
}

function matrix_best(array $banks, string $cur): array
{
    $bb = null; $bs = null;
    foreach ($banks as $code => $b) {
        $r = $b['rates'][$cur] ?? null;
        if (!$r) continue;
        if ($bb === null || $r['buy'] > $bb['rate']) $bb = ['rate' => $r['buy'], 'code' => $code];
        if ($bs === null || $r['sell'] < $bs['rate']) $bs = ['rate' => $r['sell'], 'code' => $code];
    }
    return ['buy' => $bb, 'sell' => $bs];
}

function fmt_rate(?float $n): string
{
    if ($n === null || $n <= 0) return '—';
    $s = rtrim(rtrim(number_format($n, 4, '.', ''), '0'), '.');
    $dec = strpos($s, '.') === false ? 0 : strlen($s) - strpos($s, '.') - 1;
    return $dec < 2 ? number_format($n, 2, '.', '') : $s;
}

function pxq_not_found(): void
{
    global $lang, $L;
    if (!isset($lang)) { $lang = detect_lang(); $L = load_lang($lang); }
    require __DIR__ . '/../404.php';
    exit;
}

function pxq_redirect(string $url, int $code = 301): void
{
    header('Location: ' . $url, true, $code);
    exit;
}

function top_bank_codes(int $limit = 10): array
{
    $priority = ['acba-bank', 'hayekonombank', 'araratbank', 'ardshinbank', 'inekobank', 'yunibank', 'evocabank', 'ameriabank', 'konvers-bank', 'fast-bank', 'vtbhayastan-bank', 'aydi-bank', 'haybiznesbank', 'armsvisbank', 'arcakhbank', 'mellat-bank', 'biblos-bank-armenia'];
    $out = [];
    foreach ($priority as $c) {
        if (get_bank_meta($c) && bank_is_visible($c)) $out[] = $c;
        if (count($out) >= $limit) break;
    }
    return $out;
}

function sm_bank_codes_safe(): array
{
    $codes = array_keys(require __DIR__ . '/bank_data.php');
    foreach (bank_overrides() as $c => $o) if (!in_array($c, $codes, true) && (int)$o['is_active'] === 1) $codes[] = $c;
    return $codes;
}
