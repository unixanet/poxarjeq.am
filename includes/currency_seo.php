<?php
declare(strict_types=1);

function cur_forms(string $code): array
{
    static $m = [
        'USD' => ['hy' => ['ԱՄՆ դոլար', 'դոլարի'], 'ru' => ['Доллар США', 'доллара США'], 'en' => ['US Dollar', 'US dollar']],
        'EUR' => ['hy' => ['Եվրո', 'եվրոյի'], 'ru' => ['Евро', 'евро'], 'en' => ['Euro', 'euro']],
        'RUR' => ['hy' => ['Ռուսական ռուբլի', 'ռուբլու'], 'ru' => ['Российский рубль', 'российского рубля'], 'en' => ['Russian Ruble', 'Russian ruble']],
        'GBP' => ['hy' => ['Բրիտանական ֆունտ', 'ֆունտի'], 'ru' => ['Фунт стерлингов', 'фунта стерлингов'], 'en' => ['British Pound', 'British pound']],
        'CHF' => ['hy' => ['Շվեյցարական ֆրանկ', 'շվեյցարական ֆրանկի'], 'ru' => ['Швейцарский франк', 'швейцарского франка'], 'en' => ['Swiss Franc', 'Swiss franc']],
        'GEL' => ['hy' => ['Վրացական լարի', 'լարիի'], 'ru' => ['Грузинский лари', 'грузинского лари'], 'en' => ['Georgian Lari', 'Georgian lari']],
        'AED' => ['hy' => ['ԱՄԷ դիրհամ', 'դիրհամի'], 'ru' => ['Дирхам ОАЭ', 'дирхама ОАЭ'], 'en' => ['UAE Dirham', 'UAE dirham']],
        'AUD' => ['hy' => ['Ավստրալական դոլար', 'ավստրալական դոլարի'], 'ru' => ['Австралийский доллар', 'австралийского доллара'], 'en' => ['Australian Dollar', 'Australian dollar']],
        'CAD' => ['hy' => ['Կանադական դոլար', 'կանադական դոլարի'], 'ru' => ['Канадский доллар', 'канадского доллара'], 'en' => ['Canadian Dollar', 'Canadian dollar']],
        'CNY' => ['hy' => ['Չինական յուան', 'յուանի'], 'ru' => ['Китайский юань', 'китайского юаня'], 'en' => ['Chinese Yuan', 'Chinese yuan']],
        'JPY' => ['hy' => ['Ճապոնական իեն', 'իենի'], 'ru' => ['Японская иена', 'японской иены'], 'en' => ['Japanese Yen', 'Japanese yen']],
        'KZT' => ['hy' => ['Ղազախական տենգե', 'տենգեի'], 'ru' => ['Казахстанский тенге', 'казахстанского тенге'], 'en' => ['Kazakhstani Tenge', 'Kazakhstani tenge']],
        'XAU' => ['hy' => ['Ոսկի', 'ոսկու'], 'ru' => ['Золото', 'золота'], 'en' => ['Gold', 'gold']],
    ];
    return $m[strtoupper($code)] ?? [];
}

function cur_name(string $code, string $lang): string
{
    $f = cur_forms($code)[$lang] ?? null;
    return $f ? $f[0] : strtoupper($code);
}

function cur_gen(string $code, string $lang): string
{
    $f = cur_forms($code)[$lang] ?? null;
    return $f ? $f[1] : strtoupper($code);
}

function cur_ucfirst(string $s): string
{
    return mb_strtoupper(mb_substr($s, 0, 1)) . mb_substr($s, 1);
}

function cur_vars(string $code, string $lang): array
{
    return ['code' => strtoupper($code), 'name' => cur_name($code, $lang), 'gen' => cur_gen($code, $lang), 'gen_uc' => cur_ucfirst(cur_gen($code, $lang))];
}

function cur_menu_label(string $code, string $lang): string
{
    return cms_text_in('cur.menu_label', $lang, cur_vars($code, $lang));
}

function cur_h1(string $code, string $lang): string
{
    return cms_text_in('cur.h1', $lang, cur_vars($code, $lang));
}

function cur_title(string $code, string $lang): string
{
    return cms_text_in('seo.currency.title', $lang, cur_vars($code, $lang));
}

function cur_description(string $code, string $lang, ?float $bestBuy, string $bestBuyBank, ?float $bestSell, string $bestSellBank, int $banks): string
{
    $v = cur_vars($code, $lang);
    if ($bestBuy !== null && $bestSell !== null && $banks > 0) {
        $v += ['best_buy' => fmt_rate($bestBuy), 'best_buy_bank' => $bestBuyBank, 'best_sell' => fmt_rate($bestSell), 'best_sell_bank' => $bestSellBank, 'banks' => $banks];
        return cms_text_in('seo.currency.description', $lang, $v);
    }
    return cms_text_in('seo.currency.description_plain', $lang, $v);
}
