<?php
declare(strict_types=1);

function sm_currency_codes(): array
{
    return array_values(array_unique(array_merge(all_currency_codes(), table_currency_codes())));
}

function sm_bank_codes(): array
{
    return array_values(array_filter(sm_bank_codes_safe(), 'bank_is_visible'));
}

function sm_bank_rates(): array
{
    $codes = sm_bank_codes();
    $reqs = [];
    foreach ($codes as $c) $reqs[$c] = ['bank.php', ['code' => $c]];
    $res = api_get_many($reqs);
    $out = [];
    foreach ($codes as $c) {
        $out[$c] = ['cur' => array_keys($res[$c]['rates'] ?? []), 'updated' => $res[$c]['updated_at'] ?? null];
    }
    return $out;
}

function sm_date(?string $iso, ?int $fallback = null): string
{
    $ts = $iso ? strtotime($iso) : false;
    if (!$ts) $ts = $fallback ?? time();
    return date('Y-m-d', $ts);
}

function sm_entries(string $part): array
{
    $e = [];
    $addPage = function (string $path, string $lastmod, string $freq, string $prio) use (&$e) { $e[] = ['path' => $path, 'lastmod' => $lastmod, 'freq' => $freq, 'prio' => $prio]; };
    $root = dirname(__DIR__);
    $mt = fn(string $f) => (int)(@filemtime($root . '/' . $f) ?: time());
    $apiTs = api_get('currencies.php', [], 900)['updated_at'] ?? null;
    $today = sm_date($apiTs);

    if ($part === 'pages') {
        $addPage('/', $today, 'hourly', '1.0');
        $addPage('/banks', $today, 'daily', '0.8');
        $addPage('/poxanakman-keter', $today, 'hourly', '0.8');
        $addPage('/voski', $today, 'daily', '0.7');
        $addPage('/news', $today, 'daily', '0.6');
        foreach (['methodology.php' => '0.5', 'about.php' => '0.5', 'contact.php' => '0.4', 'privacy.php' => '0.2', 'disclaimer.php' => '0.2'] as $f => $p) $addPage('/' . $f, sm_date(null, $mt($f)), 'monthly', $p);
    } elseif ($part === 'currencies') {
        foreach (sm_currency_codes() as $c) $addPage('/currency.php?cur=' . $c, $today, 'hourly', in_array($c, ['USD', 'EUR', 'RUR', 'GEL'], true) ? '0.9' : '0.7');
    } elseif ($part === 'banks') {
        foreach (sm_bank_rates() as $code => $b) {
            $d = sm_date($b['updated']);
            $addPage(bank_page_url($code), $d, 'daily', '0.7');
            foreach ($b['cur'] as $c) $addPage(bank_page_url($code) . '/' . strtolower($c), $d, 'daily', '0.5');
        }
    } elseif ($part === 'branches') {
        $dc = sm_date(null, max($mt('includes/branches_data.php'), $mt('includes/bank_data.php')));
        foreach (array_keys(cities_index()) as $cs) $addPage(city_page_url($cs), $dc, 'weekly', '0.6');
        $d = sm_date(null, max($mt('includes/branches_data.php'), $mt('includes/bank_data.php')));
        foreach (sm_bank_codes() as $code) {
            foreach (get_bank_meta($code)['branches'] ?? [] as $br) {
                if (!empty($br['slug'])) $addPage(branch_page_url($code, $br['slug']), $d, 'monthly', '0.5');
            }
        }
    }
    return $e;
}
