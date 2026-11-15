<?php
declare(strict_types=1);

function ord_num(int $n, string $lang): string
{
    if ($lang !== 'hy') return (string)$n;
    return $n === 1 ? '1-ին' : $n . '-րդ';
}

function bank_vars(string $bank, string $lang): array
{
    return ['bank' => $bank, 'bank_loc' => $lang === 'hy' ? $bank . 'ում' : $bank, 'bank_gen' => $lang === 'hy' ? $bank . 'ի' : $bank];
}

function bank_rates_brief(array $rates, string $lang, int $max = 3): string
{
    $out = [];
    foreach (['USD', 'EUR', 'RUR', 'GEL', 'GBP'] as $c) {
        if (!isset($rates[$c]['buy'], $rates[$c]['sell'])) continue;
        $label = $lang === 'en' ? $c : mb_strtolower(cur_name($c, $lang));
        $out[] = $label . ' ' . fmt_rate((float)$rates[$c]['buy']) . '/' . fmt_rate((float)$rates[$c]['sell']);
        if (count($out) >= $max) break;
    }
    return implode(', ', $out);
}

function bank_title(string $bank, string $lang): string
{
    return cms_text_in('seo.bank.title', $lang, bank_vars($bank, $lang));
}

function bank_h1(string $bank, string $lang): string
{
    return cms_text_in('bank.h1', $lang, bank_vars($bank, $lang));
}

function bank_description(string $bank, string $lang, array $rates, int $branches): string
{
    $brief = bank_rates_brief($rates, $lang);
    $v = bank_vars($bank, $lang) + ['rates' => $brief, 'branches' => $branches];
    if ($brief === '') return cms_text_in('seo.bank.description_plain', $lang, $v);
    return cms_text_in($branches > 0 ? 'seo.bank.description' : 'seo.bank.description_nb', $lang, $v);
}

function bank_summary(string $bank, string $lang, ?array $usd): string
{
    if (!$usd || !isset($usd['buy'], $usd['sell'])) return '';
    $place = (int)($usd['place_buy'] ?? 0);
    $of = (int)($usd['of'] ?? 0);
    $v = bank_vars($bank, $lang) + ['buy' => fmt_rate((float)$usd['buy']), 'sell' => fmt_rate((float)$usd['sell']), 'place' => $place, 'of' => $of, 'place_ord' => ord_num($place, $lang)];
    return cms_text_in($place > 0 && $of > 0 ? 'bank.summary_rank' : 'bank.summary', $lang, $v);
}

function bank_names_line(array $meta, string $lang): string
{
    $names = [];
    foreach (['hy', 'ru', 'en'] as $l) {
        $n = trim((string)($meta['name'][$l] ?? ''));
        if ($n !== '' && !in_array($n, $names, true)) $names[] = $n;
    }
    return count($names) > 1 ? implode(' · ', $names) : '';
}

function branch_label(string $bank, string $branch, string $city): string
{
    $same = $branch === '' || mb_stripos($branch, $city) !== false || mb_stripos($city, $branch) !== false;
    return $same ? $bank . ' ' . $city : $bank . ' ' . $branch . ', ' . $city;
}

function branch_title(string $bank, string $branch, string $city, string $lang): string
{
    return cms_text_in('seo.branch.title', $lang, ['label' => branch_label($bank, $branch, $city)] + bank_vars($bank, $lang));
}

function branch_description(string $bank, string $branch, string $city, string $addr, string $phone, string $hours, string $lang): string
{
    $details = implode(' · ', array_filter([$addr, $phone, $hours]));
    $v = ['label' => branch_label($bank, $branch, $city), 'details' => $details] + bank_vars($bank, $lang);
    return cms_text_in($details !== '' ? 'seo.branch.description' : 'seo.branch.description_plain', $lang, $v);
}

function match_api_branch(array $apiBranches, array $branch): ?array
{
    $city = mb_strtolower((string)($branch['city']['hy'] ?? ''));
    $addr = mb_strtolower((string)($branch['address']['hy'] ?? ''));
    $best = null;
    $bestScore = -1;
    foreach ($apiBranches as $ab) {
        $c = mb_strtolower((string)($ab['city'] ?? ''));
        if ($city === '' || $c === '' || mb_stripos($c, $city) === false) continue;
        $score = 0;
        foreach (preg_split('/[\s,.]+/u', $addr) as $tok) {
            if (mb_strlen($tok) >= 4 && mb_stripos((string)($ab['address'] ?? ''), $tok) !== false) $score += mb_strlen($tok);
        }
        if ($score > $bestScore) { $bestScore = $score; $best = $ab; }
    }
    return $best;
}
