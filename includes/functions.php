<?php
declare(strict_types=1);

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/bank_store.php';
require_once __DIR__ . '/cms.php';
require_once __DIR__ . '/ui.php';
require_once __DIR__ . '/llms.php';
require_once __DIR__ . '/seo_audit.php';

function detect_lang(): string
{
    // Set from GET once, then rely on cookie (URLs stay clean)
    if (!empty($_GET['lang']) && in_array($_GET['lang'], SUPPORTED_LANGS, true)) {
        setcookie('lang', $_GET['lang'], [
            'expires' => time() + 86400 * 365,
            'path' => '/',
            'samesite' => 'Lax',
        ]);
        $_COOKIE['lang'] = $_GET['lang'];
        return $_GET['lang'];
    }
    if (!empty($_COOKIE['lang']) && in_array($_COOKIE['lang'], SUPPORTED_LANGS, true)) {
        return $_COOKIE['lang'];
    }
    return DEFAULT_LANG;
}

/** Merge rates for multiple currencies keyed by bank code */
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
    // Find best buy per currency for highlight
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
    // Clean existing lang query
    $query = [];
    parse_str(parse_url($uri, PHP_URL_QUERY) ?? '', $query);
    unset($query['lang']);
    $query['lang'] = $lang; // one-time set via GET, cookie stores it
    return $path . '?' . http_build_query($query);
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

function current_path_with_lang(string $path, string $lang): string
{
    $sep = str_contains($path, '?') ? '&' : '?';
    return $path . $sep . 'lang=' . $lang;
}

function api_get(string $endpoint, array $params = [], int $ttl = CACHE_TTL): ?array
{
    $url = API_BASE . '/' . ltrim($endpoint, '/');
    if ($params) {
        $url .= '?' . http_build_query($params);
    }
    $cacheKey = md5($url);
    $cacheFile = CACHE_DIR . '/' . $cacheKey . '.json';

    if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < $ttl) {
        $data = json_decode((string)file_get_contents($cacheFile), true);
        if (is_array($data)) return $data;
    }

    $ctx = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => "Accept: application/json\r\nUser-Agent: BestRate.am/2.0\r\n",
            'timeout' => 12,
            'ignore_errors' => true,
        ],
        'ssl' => ['verify_peer' => true, 'verify_peer_name' => true],
    ]);

    $raw = @file_get_contents($url, false, $ctx);
    if ($raw === false) {
        if (file_exists($cacheFile)) {
            $data = json_decode((string)file_get_contents($cacheFile), true);
            if (is_array($data)) return $data;
        }
        return null;
    }
    $data = json_decode($raw, true);
    if (!is_array($data)) return null;
    @file_put_contents($cacheFile, $raw, LOCK_EX);
    return $data;
}

function get_rates_summary(): ?array { return api_get('rates.php'); }
function get_banks_for_currency(string $cur = 'USD'): ?array { return api_get('banks.php', ['cur' => strtoupper($cur)]); }
function get_bank(string $code): ?array { return api_get('bank.php', ['code' => $code]); }
function get_banks_list(): ?array { return api_get('list.php'); }
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
        'AUD' => ['hy' => 'Ավստրալիական դոլար', 'ru' => 'Австралийский доллар', 'en' => 'Australian Dollar'],
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


/** Currency flag PNG path — upload files to /assets/flags/{CODE}.png */
function currency_flag(string $code): string
{
    $code = strtoupper(preg_replace('/[^A-Za-z]/', '', $code));
    if ($code !== '' && is_file(__DIR__ . '/../assets/flags/' . $code . '.png')) {
        return '/assets/flags/' . $code . '.png';
    }
    return '/assets/flags/GLOBE.png';
}

/** Language flag PNG — upload to /assets/lang/{hy|ru|en}.png */
function lang_flag(string $lang): string
{
    $lang = strtolower($lang);
    if (!in_array($lang, ['hy','ru','en'], true)) $lang = 'hy';
    return '/assets/lang/' . $lang . '.png';
}

/** All currency flag map for JS */
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
    if ($db !== null) {                       // филиалы ведутся в админке
        if (!empty($db[$code])) $meta['branches'] = $db[$code];
        return $meta;
    }
    static $branches = null;                  // запасной вариант: статичный файл
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

/** All currency codes from API */
function all_currency_codes(): array
{
    return ['AED','AUD','CAD','CHF','CNY','EUR','GBP','GEL','JPY','RUR','USD'];
}


/**
 * Валюты для переключателей в таблице на главной.
 * Берём живой список из API (то, что реально торгуется у банков), а при его
 * недоступности — статический список. URL-ы страниц валют (currency.php) и
 * sitemap по-прежнему строятся от all_currency_codes(), здесь только таблица.
 */
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

/**
 * Параллельная загрузка нескольких эндпоинтов API с тем же файловым кэшем,
 * что и api_get(). Холодный кэш = один круг запросов, а не N последовательных.
 * $reqs: ключ => [эндпоинт, параметры]. Возвращает ключ => массив|null.
 */
function api_get_many(array $reqs, int $ttl = CACHE_TTL): array
{
    $out = [];
    $pending = [];
    foreach ($reqs as $key => $r) {
        [$endpoint, $params] = $r;
        $url = API_BASE . '/' . ltrim($endpoint, '/') . ($params ? '?' . http_build_query($params) : '');
        $cacheFile = CACHE_DIR . '/' . md5($url) . '.json';
        if (is_file($cacheFile) && (time() - filemtime($cacheFile)) < $ttl) {
            $d = json_decode((string)file_get_contents($cacheFile), true);
            if (is_array($d)) { $out[$key] = $d; continue; }
        }
        $pending[$key] = [$url, $cacheFile, $endpoint, $params];
    }
    if (!$pending) return $out;

    if (!function_exists('curl_multi_init')) {
        foreach ($pending as $key => [$url, $cacheFile, $endpoint, $params]) {
            $out[$key] = api_get($endpoint, $params, $ttl);
        }
        return $out;
    }

    $mh = curl_multi_init();
    $handles = [];
    foreach ($pending as $key => [$url]) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_CONNECTTIMEOUT => 4,
            CURLOPT_TIMEOUT => 8,
            CURLOPT_USERAGENT => 'Poxarjeq.am/2.0',
            CURLOPT_HTTPHEADER => ['Accept: application/json'],
        ]);
        curl_multi_add_handle($mh, $ch);
        $handles[$key] = $ch;
    }
    do {
        $status = curl_multi_exec($mh, $running);
        if ($running) curl_multi_select($mh, 1.0);
    } while ($running && $status === CURLM_OK);

    foreach ($handles as $key => $ch) {
        [$url, $cacheFile] = $pending[$key];
        $raw = curl_multi_getcontent($ch);
        $http = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $data = ($http === 200 && is_string($raw)) ? json_decode($raw, true) : null;
        if (is_array($data)) {
            @file_put_contents($cacheFile, $raw, LOCK_EX);
        } elseif (is_file($cacheFile)) {
            $data = json_decode((string)file_get_contents($cacheFile), true); // устаревший кэш лучше, чем пустота
        }
        $out[$key] = is_array($data) ? $data : null;
        curl_multi_remove_handle($mh, $ch);
        curl_close($ch);
    }
    curl_multi_close($mh);
    return $out;
}

/**
 * Матрица «банк × валюта» для главной: курсы по ВСЕМ валютам из API.
 * @return array{banks: array<string,array>, updated_at: ?string, currencies: string[]}
 */
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

/** Лучший курс покупки (max) и продажи (min) по валюте среди банков матрицы */
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

/** Курс без хвостовых нулей, но не меньше 2 знаков: 388.5 → 388.50, 4.2813 → 4.2813 */
function fmt_rate(?float $n): string
{
    if ($n === null || $n <= 0) return '—';
    $s = rtrim(rtrim(number_format($n, 4, '.', ''), '0'), '.');
    $dec = strpos($s, '.') === false ? 0 : strlen($s) - strpos($s, '.') - 1;
    return $dec < 2 ? number_format($n, 2, '.', '') : $s;
}
