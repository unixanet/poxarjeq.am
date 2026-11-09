<?php
declare(strict_types=1);

/**
 * Общие элементы разметки. Каждая ссылка получает title, каждая картинка — alt (SEO-правило проекта).
 * Все классы — Tailwind; собственных CSS-классов в проекте нет.
 */

function ui_link(string $href, string $label, string $title = '', string $cls = '', array $attrs = [], bool $labelIsHtml = false): string
{
    $title = trim($title) !== '' ? $title : trim(strip_tags($label));
    $a = '';
    foreach ($attrs as $k => $v) $a .= ' ' . e($k) . ($v === true ? '' : '="' . e((string)$v) . '"');
    return '<a href="' . e($href) . '" title="' . e($title) . '"' . ($cls !== '' ? ' class="' . e($cls) . '"' : '') . $a . '>' . ($labelIsHtml ? $label : e($label)) . '</a>';
}

function ui_img(string $src, string $alt, int $w, int $h, string $cls = '', array $attrs = []): string
{
    $alt = trim($alt) !== '' ? $alt : basename(parse_url($src, PHP_URL_PATH) ?: 'image');
    $a = '';
    foreach ($attrs as $k => $v) $a .= ' ' . e($k) . ($v === true ? '' : '="' . e((string)$v) . '"');
    return '<img src="' . e($src) . '" alt="' . e($alt) . '" width="' . $w . '" height="' . $h . '"' . ($cls !== '' ? ' class="' . e($cls) . '"' : '') . $a . '>';
}

function ui_flag(string $code, int $size = 28, string $cls = 'rounded-full object-cover shadow'): string
{
    return ui_img(currency_flag($code), tv('common.flag_alt', ['code' => $code]), $size, $size, $cls);
}

function ui_bank_logo(string $code, string $name, ?string $apiLogo, int $size = 44, string $cls = 'rounded-[10px] object-contain'): string
{
    $src = bank_logo_url($code, $apiLogo);
    if ($src === '') {
        return '<span class="grid shrink-0 place-items-center rounded-[10px] bg-blue-50 font-extrabold text-blue-700" style="width:' . $size . 'px;height:' . $size . 'px">' . e(mb_strtoupper(mb_substr($name, 0, 1))) . '</span>';
    }
    return ui_img($src, tv('common.logo_alt', ['bank' => $name]), $size, $size, $cls, ['loading' => 'lazy']);
}

function ui_partner_badge(string $extra = ''): string
{
    return '<span title="' . e(t('common.partner_title')) . '" class="inline-block w-fit rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-extrabold uppercase tracking-wide text-amber-800 ' . e($extra) . '">★ ' . e(t('partner')) . '</span>';
}

function ui_live(?string $updatedAt): string
{
    if (!$updatedAt) return '';
    return '<span class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white/80 px-3 py-1.5 text-xs"><span class="relative flex h-2 w-2"><span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-green-400 opacity-75"></span><span class="relative inline-flex h-2 w-2 rounded-full bg-green-500"></span></span>'
         . e(tv('common.update_label', ['time' => format_datetime($updatedAt)])) . '</span>';
}

/** Хлебные крошки: [[подпись, ссылка|null], ...]; последний пункт — текущая страница */
function ui_breadcrumbs(array $items): string
{
    $h = '<nav class="mb-5 text-sm" aria-label="' . e(t('common.breadcrumb')) . '"><ol class="flex flex-wrap items-center gap-x-2 gap-y-1">';
    $n = count($items);
    foreach ($items as $i => [$label, $url]) {
        if ($i < $n - 1 && $url) $h .= '<li>' . ui_link($url, $label, $label, 'no-underline hover:underline') . '</li><li aria-hidden="true">/</li>';
        else $h .= '<li class="font-semibold" aria-current="page">' . e($label) . '</li>';
    }
    return $h . '</ol></nav>';
}

/** Разметка хлебных крошек для поисковиков */
function ld_breadcrumbs(array $items): array
{
    $list = [];
    foreach ($items as $i => [$label, $url]) {
        $el = ['@type' => 'ListItem', 'position' => $i + 1, 'name' => $label];
        if ($url) $el['item'] = rtrim(SITE_URL, '/') . $url;
        $list[] = $el;
    }
    return ['@type' => 'BreadcrumbList', 'itemListElement' => $list];
}

function ld_script(array $graph): string
{
    $data = ['@context' => 'https://schema.org', '@graph' => $graph];
    return '<script type="application/ld+json">' . json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) . '</script>';
}

/** FAQ из админки: ключи prefix.faq1.q / prefix.faq1.a … → [[вопрос, ответ], ...] */
function cms_faq(string $prefix, array $vars = []): array
{
    $out = [];
    for ($i = 1; $i <= 12; $i++) {
        $q = cms_fill(t("$prefix.faq$i.q"), $vars);
        $a = cms_fill(t("$prefix.faq$i.a"), $vars);
        if (trim($q) === '' || trim($a) === '' || $q === "$prefix.faq$i.q") continue;
        $out[] = [trim($q), trim($a)];
    }
    return $out;
}
function ui_faq(array $faq): string
{
    $h = '<div class="divide-y divide-slate-100 rounded-3xl border border-slate-100 bg-white px-5 shadow-[0_10px_40px_rgba(15,23,42,.06)] sm:px-8">';
    foreach ($faq as $i => [$q, $a]) {
        $h .= '<details class="group py-5"' . ($i === 0 ? ' open' : '') . '><summary class="flex cursor-pointer list-none items-start justify-between gap-4 text-[16px] font-bold leading-snug text-slate-900 sm:text-lg [&::-webkit-details-marker]:hidden">'
            . '<h3 class="font-bold">' . e($q) . '</h3><span class="mt-0.5 grid h-7 w-7 shrink-0 place-items-center rounded-full bg-blue-50 text-blue-600 transition group-open:rotate-45" aria-hidden="true">+</span></summary>'
            . '<div class="mt-3 space-y-2 leading-relaxed">' . md_paragraphs($a) . '</div></details>';
    }
    return $h . '</div>';
}
function ld_faq(array $faq): ?array
{
    if (!$faq) return null;
    return ['@type' => 'FAQPage', 'mainEntity' => array_map(fn($f) => ['@type' => 'Question', 'name' => $f[0], 'acceptedAnswer' => ['@type' => 'Answer', 'text' => $f[1]]], $faq)];
}

/** Курс с хвостом без лишних нулей, минимум 2 знака */
function ui_rate(?float $n): string
{
    return e(fmt_rate($n));
}


/** Таблица курсов банка по валютам (десктоп) и карточки (телефон) */
function ui_bank_rates(array $rates, string $bankName, string $lang): string
{
    ob_start(); ?>
            <div class="hidden overflow-hidden rounded-3xl border border-slate-100 bg-white shadow-[0_24px_60px_-12px_rgba(15,23,42,.14)] md:block">
                <div class="relative overflow-x-auto">
                    <table class="w-full text-sm">
                        <caption class="sr-only"><?= e(t('rates_of')) ?> — <?= e($bankName) ?></caption>
                        <thead>
                            <tr class="border-b border-slate-200 bg-gradient-to-b from-slate-50 to-slate-100">
                                <th scope="col" class="px-5 py-3 text-left text-[11px] font-extrabold uppercase tracking-wider"><?= e(t('currency')) ?></th>
                                <th scope="col" class="px-4 py-3 text-right text-[11px] font-extrabold uppercase tracking-wider"><?= e(t('buy')) ?></th>
                                <th scope="col" class="px-4 py-3 text-right text-[11px] font-extrabold uppercase tracking-wider"><?= e(t('sell')) ?></th>
                                <th scope="col" class="px-4 py-3 text-right text-[11px] font-extrabold uppercase tracking-wider"><?= e(t('spread')) ?></th>
                                <th scope="col" class="px-4 py-3"><span class="sr-only"><?= e(t('bank.compare')) ?></span></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($rates as $c => $r):
                                $buy = isset($r['buy']) ? (float)$r['buy'] : null; $sell = isset($r['sell']) ? (float)$r['sell'] : null;
                                $sp = ($buy && $sell) ? calc_spread($buy, $sell) : 0;
                                $cn = currency_name($c, $lang);
                                $cmpTitle = tv('bank.compare_title', ['name' => $cn . ' (' . $c . ')']);
                            ?>
                            <tr class="border-t border-slate-100 hover:bg-blue-50/40">
                                <th scope="row" class="px-5 py-3 text-left font-normal">
                                    <span class="flex items-center gap-3 font-bold text-slate-900"><?= ui_flag($c, 32, 'h-8 w-8 rounded-full object-cover shadow') ?><span><?= e($cn) ?> <span class="text-xs font-semibold"><?= e($c) ?></span></span></span>
                                </th>
                                <td class="px-4 py-3 text-right font-semibold tabular-nums"><?= ui_rate($buy) ?></td>
                                <td class="px-4 py-3 text-right font-semibold tabular-nums"><?= ui_rate($sell) ?></td>
                                <td class="px-4 py-3 text-right tabular-nums"><?= e((string)$sp) ?>%</td>
                                <td class="px-4 py-3 text-right"><?= ui_link('/currency.php?cur=' . $c . '&lang=' . $lang, t('bank.compare'), $cmpTitle, 'inline-block rounded-lg bg-blue-50 px-3 py-1.5 text-[13px] font-bold no-underline') ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="grid gap-3 md:hidden">
                <?php foreach ($rates as $c => $r):
                    $cn = currency_name($c, $lang);
                    $cmpTitle = tv('bank.compare_title', ['name' => $cn . ' (' . $c . ')']);
                ?>
                <a href="/currency.php?cur=<?= e($c) ?>&amp;lang=<?= e($lang) ?>" title="<?= e($cmpTitle) ?>" class="flex items-center gap-3 rounded-2xl border border-slate-100 bg-white p-3.5 no-underline shadow-[0_6px_24px_rgba(15,23,42,.06)]">
                    <?= ui_flag($c, 40, 'h-10 w-10 shrink-0 rounded-full object-cover shadow') ?>
                    <span class="min-w-0 flex-1"><span class="block font-black text-slate-900"><?= e($c) ?></span><span class="block truncate text-xs"><?= e($cn) ?></span></span>
                    <span class="grid shrink-0 grid-cols-2 gap-3 text-right">
                        <span><span class="block text-[10px] font-bold uppercase tracking-wider"><?= e(t('buy')) ?></span><span class="block text-[17px] font-extrabold tabular-nums text-slate-900"><?= ui_rate(isset($r['buy']) ? (float)$r['buy'] : null) ?></span></span>
                        <span><span class="block text-[10px] font-bold uppercase tracking-wider"><?= e(t('sell')) ?></span><span class="block text-[17px] font-extrabold tabular-nums text-slate-900"><?= ui_rate(isset($r['sell']) ? (float)$r['sell'] : null) ?></span></span>
                    </span>
                </a>
                <?php endforeach; ?>
            </div>
<?php
    return ob_get_clean();
}
