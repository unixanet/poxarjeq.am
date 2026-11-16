<?php
declare(strict_types=1);

function cur_forms(string $code): array
{
    static $m = [
        'USD' => ['hy' => ['ԱՄՆ դոլար', 'ԱՄՆ դոլարի'], 'ru' => ['Доллар США', 'доллара'], 'en' => ['US Dollar', 'US dollar']],
        'EUR' => ['hy' => ['Եվրո', 'եվրոյի'], 'ru' => ['Евро', 'евро'], 'en' => ['Euro', 'euro']],
        'RUR' => ['hy' => ['Ռուսական ռուբլի', 'ռուբլու'], 'ru' => ['Российский рубль', 'рубля'], 'en' => ['Russian Ruble', 'Russian ruble']],
        'GBP' => ['hy' => ['Բրիտանական ֆունտ', 'ֆունտի'], 'ru' => ['Фунт стерлингов', 'фунта'], 'en' => ['British Pound', 'British pound']],
        'CHF' => ['hy' => ['Շվեյցարական ֆրանկ', 'շվեյցարական ֆրանկի'], 'ru' => ['Швейцарский франк', 'швейцарского франка'], 'en' => ['Swiss Franc', 'Swiss franc']],
        'GEL' => ['hy' => ['Վրացական լարի', 'լարիի'], 'ru' => ['Грузинский лари', 'лари'], 'en' => ['Georgian Lari', 'Georgian lari']],
        'AED' => ['hy' => ['ԱՄԷ դիրհամ', 'դիրհամի'], 'ru' => ['Дирхам ОАЭ', 'дирхама'], 'en' => ['UAE Dirham', 'UAE dirham']],
        'AUD' => ['hy' => ['Ավստրալական դոլար', 'ավստրալական դոլարի'], 'ru' => ['Австралийский доллар', 'австралийского доллара'], 'en' => ['Australian Dollar', 'Australian dollar']],
        'CAD' => ['hy' => ['Կանադական դոլար', 'կանադական դոլարի'], 'ru' => ['Канадский доллар', 'канадского доллара'], 'en' => ['Canadian Dollar', 'Canadian dollar']],
        'CNY' => ['hy' => ['Չինական յուան', 'յուանի'], 'ru' => ['Китайский юань', 'юаня'], 'en' => ['Chinese Yuan', 'Chinese yuan']],
        'JPY' => ['hy' => ['Ճապոնական իեն', 'իենի'], 'ru' => ['Японская иена', 'иены'], 'en' => ['Japanese Yen', 'Japanese yen']],
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

function cur_meta(string $code, string $lang): ?array
{
    static $m = [
        'USD' => [
            'hy' => ['title' => 'ԱՄՆ դոլար փոխարժեք այսօր Հայաստանում՝ USD/AMD | Poxarjeq.am', 'h1' => 'ԱՄՆ դոլարի փոխարժեքը այսօր', 'alias' => 'ԱՄՆ դոլար'],
            'ru' => ['title' => 'Курс доллара в Армении сегодня: USD/AMD в банках | Poxarjeq.am', 'h1' => 'Курс доллара в Армении сегодня', 'alias' => 'доллар США'],
            'en' => ['title' => 'US dollar rate in Armenia today: USD to AMD | Poxarjeq.am', 'h1' => 'US dollar rate in Armenia today', 'alias' => 'US dollar'],
        ],
        'EUR' => [
            'hy' => ['title' => 'Եվրո փոխարժեք այսօր Հայաստանում՝ EUR/AMD | Poxarjeq.am', 'h1' => 'Եվրոյի փոխարժեքը այսօր', 'alias' => 'եվրո'],
            'ru' => ['title' => 'Курс евро в Армении сегодня: EUR/AMD в банках | Poxarjeq.am', 'h1' => 'Курс евро в Армении сегодня', 'alias' => 'евро'],
            'en' => ['title' => 'Euro rate in Armenia today: EUR to AMD | Poxarjeq.am', 'h1' => 'Euro rate in Armenia today', 'alias' => 'euro'],
        ],
        'RUR' => [
            'hy' => ['title' => 'Ռուսական ռուբլու փոխարժեքը այսօր՝ RUR/AMD | Poxarjeq.am', 'h1' => 'Ռուսական ռուբլու փոխարժեքը այսօր', 'alias' => 'ռուսական ռուբլի'],
            'ru' => ['title' => 'Курс рубля в Армении сегодня: RUR/AMD, ВТБ, Акба | Poxarjeq.am', 'h1' => 'Курс рубля к драму в Армении сегодня', 'alias' => 'российский рубль'],
            'en' => ['title' => 'Russian ruble rate in Armenia today: RUR to AMD | Poxarjeq.am', 'h1' => 'Russian ruble rate in Armenia today', 'alias' => 'Russian ruble'],
        ],
        'GEL' => [
            'hy' => ['title' => 'Լարիի փոխարժեքը այսօր՝ վրացական լարի GEL/AMD | Poxarjeq.am', 'h1' => 'Լարիի փոխարժեքը այսօր', 'alias' => 'վրացական լարի'],
            'ru' => ['title' => 'Курс лари в Армении сегодня: грузинский лари GEL/AMD | Poxarjeq.am', 'h1' => 'Курс грузинского лари к драму сегодня', 'alias' => 'грузинский лари'],
            'en' => ['title' => 'Georgian lari rate in Armenia today: GEL to AMD | Poxarjeq.am', 'h1' => 'Georgian lari rate in Armenia today', 'alias' => 'Georgian lari'],
        ],
        'GBP' => [
            'hy' => ['title' => 'Բրիտանական ֆունտի փոխարժեքը այսօր՝ GBP/AMD | Poxarjeq.am', 'h1' => 'Բրիտանական ֆունտի փոխարժեքը այսօր', 'alias' => 'բրիտանական ֆունտ'],
            'ru' => ['title' => 'Курс фунта стерлингов в Армении сегодня: GBP/AMD | Poxarjeq.am', 'h1' => 'Курс фунта стерлингов к драму сегодня', 'alias' => 'фунт стерлингов'],
            'en' => ['title' => 'British pound rate in Armenia today: GBP to AMD | Poxarjeq.am', 'h1' => 'British pound rate in Armenia today', 'alias' => 'British pound'],
        ],
        'CHF' => [
            'hy' => ['title' => 'Շվեյցարական ֆրանկի փոխարժեքը այսօր՝ CHF/AMD | Poxarjeq.am', 'h1' => 'Շվեյցարական ֆրանկի փոխարժեքը այսօր', 'alias' => 'շվեյցարական ֆրանկ'],
            'ru' => ['title' => 'Курс швейцарского франка в Армении сегодня: CHF/AMD | Poxarjeq.am', 'h1' => 'Курс швейцарского франка к драму сегодня', 'alias' => 'швейцарский франк'],
            'en' => ['title' => 'Swiss franc rate in Armenia today: CHF to AMD | Poxarjeq.am', 'h1' => 'Swiss franc rate in Armenia today', 'alias' => 'Swiss franc'],
        ],
        'CNY' => [
            'hy' => ['title' => 'Յուանի փոխարժեքը այսօր՝ չինական յուան CNY/AMD | Poxarjeq.am', 'h1' => 'Յուանի փոխարժեքը այսօր', 'alias' => 'չինական յուան'],
            'ru' => ['title' => 'Курс юаня в Армении сегодня: китайский юань CNY/AMD | Poxarjeq.am', 'h1' => 'Курс китайского юаня к драму сегодня', 'alias' => 'китайский юань'],
            'en' => ['title' => 'Chinese yuan rate in Armenia today: CNY to AMD | Poxarjeq.am', 'h1' => 'Chinese yuan rate in Armenia today', 'alias' => 'Chinese yuan'],
        ],
        'AED' => [
            'hy' => ['title' => 'ԱՄԷ դիրհամի փոխարժեքը այսօր՝ AED/AMD | Poxarjeq.am', 'h1' => 'ԱՄԷ դիրհամի փոխարժեքը այսօր', 'alias' => 'ԱՄԷ դիրհամ'],
            'ru' => ['title' => 'Курс дирхама ОАЭ в Армении сегодня: AED/AMD | Poxarjeq.am', 'h1' => 'Курс дирхама ОАЭ к драму сегодня', 'alias' => 'дирхам ОАЭ'],
            'en' => ['title' => 'UAE dirham rate in Armenia today: AED to AMD | Poxarjeq.am', 'h1' => 'UAE dirham rate in Armenia today', 'alias' => 'UAE dirham'],
        ],
        'JPY' => [
            'hy' => ['title' => 'Ճապոնական իենի փոխարժեքը այսօր՝ JPY/AMD | Poxarjeq.am', 'h1' => 'Ճապոնական իենի փոխարժեքը այսօր', 'alias' => 'ճապոնական իեն (ճապոնական դրամ)'],
            'ru' => ['title' => 'Курс иены в Армении сегодня: японская валюта JPY/AMD | Poxarjeq.am', 'h1' => 'Курс японской иены к драму сегодня', 'alias' => 'японская иена'],
            'en' => ['title' => 'Japanese yen rate in Armenia today: JPY to AMD | Poxarjeq.am', 'h1' => 'Japanese yen rate in Armenia today', 'alias' => 'Japanese yen'],
        ],
        'CAD' => [
            'hy' => ['title' => 'Կանադական դոլարի փոխարժեքը այսօր՝ CAD/AMD | Poxarjeq.am', 'h1' => 'Կանադական դոլարի փոխարժեքը այսօր', 'alias' => 'կանադական դոլար'],
            'ru' => ['title' => 'Курс канадского доллара в Армении сегодня: CAD/AMD | Poxarjeq.am', 'h1' => 'Курс канадского доллара к драму сегодня', 'alias' => 'канадский доллар'],
            'en' => ['title' => 'Canadian dollar rate in Armenia today: CAD to AMD | Poxarjeq.am', 'h1' => 'Canadian dollar rate in Armenia today', 'alias' => 'Canadian dollar'],
        ],
        'AUD' => [
            'hy' => ['title' => 'Ավստրալական դոլարի փոխարժեքը այսօր՝ AUD/AMD | Poxarjeq.am', 'h1' => 'Ավստրալական դոլարի փոխարժեքը այսօր', 'alias' => 'ավստրալական դոլար'],
            'ru' => ['title' => 'Курс австралийского доллара в Армении сегодня: AUD/AMD | Poxarjeq.am', 'h1' => 'Курс австралийского доллара к драму сегодня', 'alias' => 'австралийский доллар'],
            'en' => ['title' => 'Australian dollar rate in Armenia today: AUD to AMD | Poxarjeq.am', 'h1' => 'Australian dollar rate in Armenia today', 'alias' => 'Australian dollar'],
        ],
    ];
    return $m[strtoupper($code)][$lang] ?? null;
}

function cur_vars(string $code, string $lang): array
{
    $meta = cur_meta($code, $lang);
    return ['code' => strtoupper($code), 'name' => cur_name($code, $lang), 'gen' => cur_gen($code, $lang), 'gen_uc' => cur_ucfirst(cur_gen($code, $lang)), 'alias' => $meta['alias'] ?? cur_name($code, $lang), 'alias_acc' => ($lang === 'ru' && strtoupper($code) === 'JPY') ? 'японскую иену' : ($meta['alias'] ?? cur_name($code, $lang))];
}

function cur_menu_label(string $code, string $lang): string
{
    return cms_text_in('cur.menu_label', $lang, cur_vars($code, $lang));
}

function cur_h1(string $code, string $lang): string
{
    if ($m = cur_meta($code, $lang)) return $m['h1'];
    return cms_text_in('cur.h1', $lang, cur_vars($code, $lang));
}

function cur_title(string $code, string $lang): string
{
    if ($m = cur_meta($code, $lang)) return $m['title'];
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
