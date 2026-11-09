<?php
declare(strict_types=1);

require_once __DIR__ . '/../config.php';

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

function t(string $key, ?array $dict = null): string
{
    global $L;
    $dict = $dict ?? $L;
    return $dict[$key] ?? $key;
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
function get_history(string $cur = 'USD', int $days = 30): ?array { return api_get('history.php', ['currency' => strtoupper($cur), 'days' => $days]); }
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
    $code = strtoupper($code);
    return '/assets/flags/' . $code . '.png';
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
    foreach (all_currency_codes() as $c) {
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
    if (!isset($data[$code])) return null;
    $meta = $data[$code];
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

/** All currency codes from API */
function all_currency_codes(): array
{
    return ['AED','AUD','CAD','CHF','CNY','EUR','GBP','GEL','JPY','RUR','USD'];
}
