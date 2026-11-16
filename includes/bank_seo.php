<?php
declare(strict_types=1);

function lc_first(string $s): string
{
    $two = mb_substr($s, 0, 2);
    if ($two !== '' && mb_strtoupper($two) === $two && mb_strtolower($two) !== $two) return $s;
    return mb_strtolower(mb_substr($s, 0, 1)) . mb_substr($s, 1);
}

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
        $label = $lang === 'en' ? $c : lc_first(cur_name($c, $lang));
        $out[] = $label . ' ' . fmt_rate((float)$rates[$c]['buy']) . '/' . fmt_rate((float)$rates[$c]['sell']);
        if (count($out) >= $max) break;
    }
    return implode(', ', $out);
}

function bank_title(string $bank, string $lang, string $alt = ''): string
{
    $v = bank_vars($bank, $lang) + ['alt' => $alt];
    return cms_text_in($alt !== '' && mb_strtolower($alt) !== mb_strtolower($bank) ? 'seo.bank.title' : 'seo.bank.title_plain', $lang, $v);
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

function bank_aliases(string $code): array
{
    static $a = [
        'acba-bank' => ['ACBA', 'АКБА', 'Акба банк', 'ակբա բանկ'],
        'hayekonombank' => ['Armeconombank', 'AEB', 'Армэкономбанк', 'Hayekonombank'],
        'haybiznesbank' => ['Hay Biznes Bank', 'Haybiznesbank', 'Хай Бизнес Банк', 'AMIO Bank', 'Амио банк'],
        'armsvisbank' => ['Armswissbank', 'Armsvisbank', 'Армсвисбанк'],
        'biblos-bank-armenia' => ['Biblos Bank Armenia', 'Biblosbank', 'Библос банк'],
        'mellat-bank' => ['Mellat Bank', 'Меллат банк', 'Մելաթ բանկ'],
        'konvers-bank' => ['Converse Bank', 'Konvers Bank', 'Конверс банк'],
        'ardshinbank' => ['Ardshinbank', 'Ардшинбанк'],
        'inekobank' => ['Inecobank', 'Инекобанк'],
        'ameriabank' => ['Ameriabank', 'Америабанк'],
        'araratbank' => ['Ararat Bank', 'Араратбанк', 'Арарат банк'],
        'yunibank' => ['Unibank', 'Юнибанк'],
        'evocabank' => ['Evocabank', 'Эвокабанк'],
        'fast-bank' => ['Fast Bank', 'Фаст банк'],
        'aydi-bank' => ['ID Bank', 'Idbank', 'АйДи Банк'],
        'vtbhayastan-bank' => ['VTB Armenia', 'ВТБ Армения'],
        'arcakhbank' => ['Artsakhbank', 'Арцахбанк'],
    ];
    return $a[$code] ?? [];
}

function bank_names_line(array $meta, string $lang, string $code = ''): string
{
    $names = [];
    $add = function (string $n) use (&$names) {
        $n = trim($n);
        if ($n === '') return;
        foreach ($names as $e) if (mb_strtolower($e) === mb_strtolower($n)) return;
        $names[] = $n;
    };
    foreach (['hy', 'ru', 'en'] as $l) $add((string)($meta['name'][$l] ?? ''));
    foreach (bank_aliases($code) as $al) $add($al);
    return count($names) > 1 ? implode(' · ', $names) : '';
}

function bank_alt_name(array $meta, string $lang): string
{
    $pick = $lang === 'en' ? 'ru' : 'en';
    return trim((string)($meta['name'][$pick] ?? ''));
}

function branch_label(string $bank, string $branch, string $city): string
{
    $same = $branch === '' || mb_stripos($branch, $city) !== false || mb_stripos($city, $branch) !== false;
    return $same ? $bank . ' ' . $city : $bank . ' ' . $branch . ', ' . $city;
}

function branch_title(string $bank, string $branch, string $city, string $lang, string $altLabel = ''): string
{
    $label = branch_label($bank, $branch, $city);
    $alt = ($altLabel !== '' && mb_strtolower($altLabel) !== mb_strtolower($label)) ? ' (' . $altLabel . ')' : '';
    $full = cms_text_in('seo.branch.title', $lang, ['label' => $label, 'alt_part' => $alt] + bank_vars($bank, $lang));
    if (mb_strlen($full) <= 78) return $full;
    $noAlt = cms_text_in('seo.branch.title', $lang, ['label' => $label, 'alt_part' => ''] + bank_vars($bank, $lang));
    if (mb_strlen($noAlt) <= 78) return $noAlt;
    return cms_text_in('seo.branch.title_short', $lang, ['label' => $label]);
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

function bank_origin(string $code, string $lang): string
{
    static $o = [
        'aydi-bank' => ['hy' => 'IDBank-ը մտնում է ID Group-ի մեջ՝ Idram վճարային համակարգի հետ միասին։', 'ru' => 'IDBank входит в ID Group вместе с платёжной системой Idram.', 'en' => 'IDBank is part of ID Group, together with the Idram payment system.'],
        'vtbhayastan-bank' => ['hy' => 'ՎՏԲ Հայաստանը ՎՏԲ միջազգային խմբի մաս է։', 'ru' => 'ВТБ Армения входит в международную группу ВТБ.', 'en' => 'VTB Armenia is part of the international VTB group.'],
        'evocabank' => ['hy' => 'Էվոկաբանկը նախկինում կոչվել է Պրոմեթեյ Բանկ։', 'ru' => 'Эвокабанк ранее назывался Прометей Банк.', 'en' => 'Evocabank was formerly known as Prometey Bank.'],
        'haybiznesbank' => ['hy' => 'ԱՄԻՈ Բանկը նախկինում կոչվել է Արմբիզնեսբանկ։', 'ru' => 'АМИО Банк ранее назывался Армбизнесбанк.', 'en' => 'AMIO Bank was formerly known as Armbusinessbank.'],
        'biblos-bank-armenia' => ['hy' => 'Բիբլոս Բանկ Հայաստանը լիբանանյան Byblos Bank-ի դուստր կառույցն է։', 'ru' => 'Библос Банк Армения — дочерняя структура ливанского Byblos Bank.', 'en' => 'Byblos Bank Armenia is a subsidiary of the Lebanese Byblos Bank.'],
        'mellat-bank' => ['hy' => 'Մելլաթ Բանկը իրանական Bank Mellat-ի դուստր բանկն է Հայաստանում։', 'ru' => 'Меллат Банк — дочерний банк иранского Bank Mellat в Армении.', 'en' => 'Mellat Bank is the Armenian subsidiary of Iran\'s Bank Mellat.'],
    ];
    return $o[$code][$lang] ?? '';
}

function bank_facts_text(string $code, string $lang, array $meta, array $rates, string $bankName): string
{
    $parts = [];
    $origin = bank_origin($code, $lang);
    if ($origin !== '') $parts[] = $origin;
    $legal = trim((string)($meta['legal'] ?? ''));
    $swift = trim((string)($meta['swift'] ?? ''));
    if ($legal !== '') $parts[] = cms_text_in($swift !== '' ? 'bank.facts_legal' : 'bank.facts_legal_only', $lang, ['legal' => $legal, 'swift' => $swift]);
    if ($rates) {
        $list = implode(', ', array_map(fn($c) => $lang === 'en' ? $c : lc_first(currency_name($c, $lang)), array_keys($rates)));
        $parts[] = cms_text_in('bank.facts_cur', $lang, ['n' => count($rates), 'list' => $list]);
    }
    return implode(' ', $parts);
}

function addr_clean(string $addr, string $city): string
{
    $a = trim(preg_replace('/^(ք\.|г\.|city of)\s*/iu', '', trim($addr)));
    $a = trim($a, " ,.");
    if ($a === '' || mb_strtolower($a) === mb_strtolower($city)) return '';
    return trim((string)preg_replace('/^' . preg_quote($city, '/') . '[,\s]+/iu', '', $a));
}
