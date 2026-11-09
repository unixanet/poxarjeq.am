<?php
require_once __DIR__ . '/includes/functions.php';

$lang = detect_lang();
$L = load_lang($lang);

/* ---- Валюта: все 11 прежних кодов + всё, что сейчас торгуется у банков по данным API ---- */
$validCodes = array_values(array_unique(array_merge(all_currency_codes(), table_currency_codes())));
$cur = strtoupper(trim($_GET['cur'] ?? 'USD'));
if (!in_array($cur, $validCodes, true)) $cur = 'USD';

$ov = currency_override($cur, $lang);                       // свои тексты валюты из админки
$name = ($ov['name'] ?? '') !== '' ? $ov['name'] : currency_name($cur, $lang);
$vars = ['name' => $name, 'code' => $cur];

/* ---- Банки: видимость, партнёры, логотипы из админки ---- */
$banksData = get_banks_for_currency($cur);
$rows = [];
foreach (($banksData['banks'] ?? []) as $b) {
    $code = (string)($b['code'] ?? '');
    if ($code === '' || !bank_is_visible($code)) continue;
    $buy = (float)($b['buy'] ?? 0); $sell = (float)($b['sell'] ?? 0);
    if ($buy <= 0 || $sell <= 0) continue;
    $rows[] = ['code' => $code, 'name' => bank_display_name($code, $lang, $b['name'] ?? null), 'logo' => $b['logo'] ?? '',
               'buy' => $buy, 'sell' => $sell, 'partner' => bank_is_partner($code), 'order' => bank_partner_order($code), 'url' => bank_page_url($code)];
}
usort($rows, function ($a, $b) {
    if ($a['partner'] !== $b['partner']) return $a['partner'] ? -1 : 1;
    if ($a['partner']) return $a['order'] <=> $b['order'];
    return $b['buy'] <=> $a['buy'];
});
$bestBuy = $bestSell = $avgBuy = null;
$bestBuyBank = $bestSellBank = '';
foreach ($rows as $r) {
    if ($bestBuy === null || $r['buy'] > $bestBuy) { $bestBuy = $r['buy']; $bestBuyBank = $r['name']; }
    if ($bestSell === null || $r['sell'] < $bestSell) { $bestSell = $r['sell']; $bestSellBank = $r['name']; }
}
if ($rows) $avgBuy = array_sum(array_column($rows, 'buy')) / count($rows);
$updatedAt = $banksData['updated_at'] ?? null;

/* ---- История для графика (первые 30 дней — сразу в странице, остальные периоды подгружает history.php) ---- */
$hist = get_history($cur, 30);
$points = [];
foreach (($hist['points'] ?? []) as $p) {
    if (!isset($p['date'])) continue;
    $points[] = ['date' => (string)$p['date'], 'buy' => (float)($p['buy'] ?? 0), 'sell' => (float)($p['sell'] ?? 0)];
}

/* ---- Тексты и SEO ---- */
[$seoTitle, $seoDesc] = seo_meta('currency', $vars);
$pageTitle = ($ov['seo_title'] ?? '') !== '' ? $ov['seo_title'] : $seoTitle;
$pageDescription = ($ov['seo_description'] ?? '') !== '' ? $ov['seo_description'] : $seoDesc;
$pageCanonical = SITE_URL . '/currency.php?cur=' . $cur . '&lang=' . $lang;
$active = strtolower($cur);
$fullWidth = false;

$h1 = ($ov['h1'] ?? '') !== '' ? $ov['h1'] : $name . ' (' . $cur . ')';
$leadHtml = ($ov['lead'] ?? '') !== '' ? md_paragraphs(cms_fill($ov['lead'], $vars)) : md_paragraphs(tv('cur.lead', $vars));
$aboutTitle = ($ov['about_title'] ?? '') !== '' ? $ov['about_title'] : tv('cur.about_title', $vars);
$aboutHtml = ($ov['about_body'] ?? '') !== '' ? md_paragraphs(cms_fill($ov['about_body'], $vars)) : tp('cur.about_p1', $vars) . tp('cur.about_p2', $vars);
$faq = cms_faq('cur', $vars);
$crumbs = [[t('home'), '/'], [$name, null]];

$chipCodes = $validCodes;
usort($chipCodes, function ($a, $b) {
    $pr = ['USD', 'EUR', 'RUR', 'GBP', 'GEL', 'CHF', 'CNY', 'AED', 'KZT'];
    $ia = array_search($a, $pr, true); $ib = array_search($b, $pr, true);
    return ($ia === false ? 99 : $ia) <=> ($ib === false ? 99 : $ib) ?: strcmp($a, $b);
});

$amounts = [1, 5, 10, 50, 100, 500, 1000, 5000];
$jsCfg = [
    'cur' => $cur, 'bestBuy' => $bestBuy, 'bestSell' => $bestSell, 'bestBuyBank' => $bestBuyBank, 'bestSellBank' => $bestSellBank,
    'points' => $points, 'days' => 30,
    'flag' => currency_flag($cur), 'amdFlag' => currency_flag('AMD'),
    't' => ['buy' => t('buy'), 'sell' => t('sell'), 'min' => t('cur.stat_min'), 'max' => t('cur.stat_max'), 'change' => t('cur.stat_change'),
            'noData' => t('cur.no_history'), 'best' => t('calc_best'), 'cur' => $cur],
];

require __DIR__ . '/includes/header.php';
?>

<?= ui_breadcrumbs($crumbs) ?>

<div class="flex flex-wrap items-center justify-between gap-4">
    <div class="flex min-w-0 items-center gap-3 sm:gap-4">
        <?= ui_flag($cur, 56, 'h-11 w-11 shrink-0 rounded-full object-cover shadow sm:h-14 sm:w-14') ?>
        <h1 class="text-[28px] sm:text-5xl font-black leading-tight tracking-tight text-slate-900 text-balance"><?= e($h1) ?></h1>
    </div>
    <?= ui_live($updatedAt) ?>
</div>
<div class="mt-3 max-w-3xl space-y-2 text-base sm:text-lg leading-relaxed"><?= $leadHtml ?></div>

<!-- Переключатель валют -->
<nav class="mt-5" aria-label="<?= e(t('currency_rates')) ?>">
    <div class="-mx-4 flex gap-2 overflow-x-auto px-4 pb-1 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden sm:mx-0 sm:flex-wrap sm:px-0">
        <?php foreach ($chipCodes as $c): $on = ($c === $cur); $cn = currency_name($c, $lang); ?>
        <?= ui_link('/currency.php?cur=' . $c . '&lang=' . $lang,
            ui_flag($c, 22, 'h-[22px] w-[22px] rounded-full object-cover') . e($c),
            tv('common.link_currency', ['name' => $cn, 'code' => $c]),
            'flex min-h-[44px] shrink-0 items-center gap-2 rounded-xl border px-3.5 text-sm font-bold no-underline ' . ($on ? 'border-blue-600 bg-blue-600 text-white shadow-[0_2px_8px_rgba(37,99,235,.25)]' : 'border-slate-200 bg-white text-black hover:border-blue-300'),
            $on ? ['aria-current' => 'page'] : [], true) ?>
        <?php endforeach; ?>
    </div>
</nav>

<div class="mt-6 grid items-start gap-5 lg:grid-cols-[1fr_400px] lg:gap-6">
    <div class="min-w-0 space-y-5">
        <!-- Плитки -->
        <div class="grid grid-cols-3 gap-2.5 sm:gap-4">
            <?php foreach ([[t('best_buy'), $bestBuy, 'text-green-600', $bestBuyBank], [t('best_sell'), $bestSell, 'text-slate-900', $bestSellBank], [t('avg_buy'), $avgBuy, 'text-slate-900', count($rows) . ' ' . t('banks')]] as [$lab, $val, $clr, $sub]): ?>
            <div class="rounded-[18px] border border-slate-100 bg-white p-3 shadow-[0_6px_24px_rgba(15,23,42,.06)] sm:rounded-[22px] sm:p-5">
                <div class="text-[10px] font-bold uppercase leading-tight tracking-wider sm:text-[11px]"><?= e($lab) ?></div>
                <div class="mt-1 text-[22px] font-black leading-none tracking-tight tabular-nums sm:mt-2 sm:text-[34px] <?= $clr ?>"><?= $val !== null ? e(fmt_rate((float)$val)) : '—' ?></div>
                <div class="mt-1.5 text-[11px] leading-tight sm:text-xs"><?= e($sub) ?></div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- График uPlot -->
        <section class="rounded-3xl border border-slate-100 bg-white p-4 shadow-[0_10px_40px_rgba(15,23,42,.07)] sm:p-6" aria-labelledby="chart-h">
            <div class="mb-3 flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 id="chart-h" class="text-lg font-black text-slate-900 sm:text-xl"><?= e(t('cur.chart_title')) ?> · <?= e($cur) ?></h2>
                    <div class="mt-1 flex items-center gap-4 text-[13px] font-semibold">
                        <span class="flex items-center gap-1.5"><i class="inline-block h-2.5 w-2.5 rounded-full bg-blue-600" aria-hidden="true"></i><?= e(t('buy')) ?></span>
                        <span class="flex items-center gap-1.5"><i class="inline-block h-2.5 w-2.5 rounded-full bg-indigo-500" aria-hidden="true"></i><?= e(t('sell')) ?></span>
                    </div>
                </div>
                <div class="inline-flex rounded-xl bg-slate-100 p-1 text-[13px] font-bold" role="group" aria-label="<?= e(t('cur.chart_title')) ?>">
                    <?php foreach ([7, 30, 90, 365] as $d): ?>
                    <button type="button" data-days="<?= $d ?>" aria-pressed="<?= $d === 30 ? 'true' : 'false' ?>" class="period-btn min-h-[36px] rounded-lg px-3 sm:px-4 <?= $d === 30 ? 'bg-white shadow' : '' ?>"><?= e(t('cur.period' . $d)) ?></button>
                    <?php endforeach; ?>
                </div>
            </div>
            <div id="rate-chart" class="relative h-[240px] w-full sm:h-[340px]" role="img" aria-label="<?= e(t('cur.chart_title') . ' ' . $cur) ?>"></div>
            <p id="chart-empty" class="hidden py-16 text-center"><?= e(t('cur.no_history')) ?></p>
            <dl id="chart-stats" class="mt-3 grid grid-cols-3 gap-2 border-t border-slate-100 pt-3 text-sm">
                <div><dt class="text-[10px] font-bold uppercase tracking-wider"><?= e(t('cur.stat_min')) ?></dt><dd id="st-min" class="font-extrabold tabular-nums text-slate-900">—</dd></div>
                <div><dt class="text-[10px] font-bold uppercase tracking-wider"><?= e(t('cur.stat_max')) ?></dt><dd id="st-max" class="font-extrabold tabular-nums text-slate-900">—</dd></div>
                <div><dt class="text-[10px] font-bold uppercase tracking-wider"><?= e(t('cur.stat_change')) ?></dt><dd id="st-chg" class="font-extrabold tabular-nums text-slate-900">—</dd></div>
            </dl>
        </section>
    </div>

    <!-- Калькулятор -->
    <div class="lg:sticky lg:top-24">
        <div id="calc" class="rounded-[28px] border border-slate-100 bg-white p-4 shadow-[0_24px_60px_-12px_rgba(15,23,42,.18)] sm:p-6">
            <h2 class="mb-4 flex items-center gap-3 text-lg font-black text-slate-900 sm:text-xl">
                <span class="grid h-9 w-9 place-items-center rounded-xl bg-indigo-600 text-white"><svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg></span>
                <?= e(t('calc_title')) ?>
            </h2>
            <div class="rounded-[20px] border border-slate-200 bg-gray-50 p-4 focus-within:border-indigo-500 focus-within:bg-white">
                <label for="cc-from" class="block text-[10.5px] font-bold uppercase tracking-widest"><?= e(t('calc_give')) ?></label>
                <div class="mt-1.5 flex items-center justify-between gap-3">
                    <input id="cc-from" type="number" inputmode="decimal" min="0" value="1000" class="min-w-0 w-full border-0 bg-transparent p-0 text-[28px] font-black tabular-nums text-slate-900 outline-none sm:text-3xl">
                    <span id="cc-from-chip" class="flex shrink-0 items-center gap-2 rounded-2xl border border-slate-100 bg-white px-2.5 py-2 text-[15px] font-extrabold shadow-sm"><?= ui_flag($cur, 28, 'h-7 w-7 rounded-full object-cover') ?><span><?= e($cur) ?></span></span>
                </div>
            </div>
            <div class="relative z-10 -my-3.5 flex justify-center">
                <button type="button" id="cc-swap" title="<?= e(t('swap')) ?>" aria-label="<?= e(t('swap')) ?>" class="grid h-11 w-11 place-items-center rounded-full border border-slate-100 bg-white text-indigo-600 shadow-md transition hover:scale-110">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"/></svg>
                </button>
            </div>
            <div class="rounded-[20px] border border-slate-200 bg-gray-50 p-4">
                <span class="block text-[10.5px] font-bold uppercase tracking-widest"><?= e(t('calc_get')) ?></span>
                <div class="mt-1.5 flex items-center justify-between gap-3">
                    <output id="cc-to" class="min-w-0 w-full truncate text-[28px] font-black tabular-nums text-slate-900 sm:text-3xl"><?= $bestBuy ? e(number_format(1000 * $bestBuy, 0, '.', ' ')) : '0' ?></output>
                    <span id="cc-to-chip" class="flex shrink-0 items-center gap-2 rounded-2xl border border-slate-100 bg-white px-2.5 py-2 text-[15px] font-extrabold shadow-sm"><?= ui_flag('AMD', 28, 'h-7 w-7 rounded-full object-cover') ?><span>AMD</span></span>
                </div>
            </div>
            <p id="cc-note" class="mt-4 min-h-[22px] text-center text-[13px] font-semibold sm:text-sm" aria-live="polite"><?= $bestBuy ? e(t('calc_best') . ': ' . $bestBuyBank . ' · 1 ' . $cur . ' = ' . fmt_rate($bestBuy) . ' AMD') : '' ?></p>
        </div>
    </div>
</div>

<!-- Таблица банков -->
<section class="mt-10" aria-labelledby="banks-h">
    <h2 id="banks-h" class="mb-4 text-xl font-black tracking-tight text-slate-900 sm:mb-5 sm:text-2xl"><?= e(tv('cur.table_title', $vars)) ?></h2>
    <?php if ($rows): ?>
    <div class="hidden overflow-hidden rounded-3xl border border-slate-100 bg-white shadow-[0_24px_60px_-12px_rgba(15,23,42,.14)] md:block">
        <div class="relative overflow-x-auto">
            <table class="w-full text-sm">
                <caption class="sr-only"><?= e(tv('cur.table_title', $vars)) ?></caption>
                <thead>
                    <tr class="border-b border-slate-200 bg-gradient-to-b from-slate-50 to-slate-100">
                        <th scope="col" class="w-12 px-4 py-3 text-left text-[11px] font-extrabold uppercase tracking-wider">#</th>
                        <th scope="col" class="px-2 py-3 text-left text-[11px] font-extrabold uppercase tracking-wider"><?= e(t('bank')) ?></th>
                        <th scope="col" class="px-4 py-3 text-right text-[11px] font-extrabold uppercase tracking-wider"><?= e(t('buy')) ?></th>
                        <th scope="col" class="px-4 py-3 text-right text-[11px] font-extrabold uppercase tracking-wider"><?= e(t('sell')) ?></th>
                        <th scope="col" class="px-4 py-3 text-right text-[11px] font-extrabold uppercase tracking-wider"><?= e(t('spread')) ?></th>
                        <th scope="col" class="w-28 px-4 py-3"><span class="sr-only"><?= e(t('common.details')) ?></span></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $i => $r):
                        $isBest = abs($r['buy'] - (float)$bestBuy) < 0.00001;
                        $sp = calc_spread($r['buy'], $r['sell']);
                        $bt = tv('common.link_bank', ['bank' => $r['name']]);
                    ?>
                    <tr class="border-t border-slate-100 hover:bg-blue-50/40 <?= $r['partner'] ? 'bg-amber-50/40' : '' ?>">
                        <td class="px-4 py-3 font-semibold tabular-nums"><?= $i + 1 ?></td>
                        <td class="px-2 py-3">
                            <a href="<?= e($r['url']) ?>?lang=<?= e($lang) ?>" title="<?= e($bt) ?>" class="flex items-center gap-3 no-underline">
                                <?= ui_bank_logo($r['code'], $r['name'], $r['logo'], 44, 'h-11 w-11 shrink-0 rounded-[10px] object-contain') ?>
                                <span class="font-bold leading-tight text-slate-900"><?= e($r['name']) ?><?php if ($r['partner']): ?><span class="mt-1 block"><?= ui_partner_badge() ?></span><?php endif; ?></span>
                            </a>
                        </td>
                        <td class="px-4 py-3 text-right font-semibold tabular-nums"><span class="<?= $isBest ? 'inline-block rounded-lg bg-green-50 px-2.5 py-1 font-extrabold text-green-600' : '' ?>"><?= ui_rate($r['buy']) ?></span></td>
                        <td class="px-4 py-3 text-right font-semibold tabular-nums"><?= ui_rate($r['sell']) ?></td>
                        <td class="px-4 py-3 text-right tabular-nums"><?= e((string)$sp) ?>%</td>
                        <td class="px-4 py-3 text-right"><?= ui_link($r['url'] . '?lang=' . $lang, t('cur.details'), $bt, 'inline-block rounded-lg bg-blue-50 px-3 py-1.5 text-[13px] font-bold no-underline') ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <div class="grid gap-3 md:hidden">
        <?php foreach ($rows as $i => $r):
            $isBest = abs($r['buy'] - (float)$bestBuy) < 0.00001;
            $bt = tv('common.link_bank', ['bank' => $r['name']]);
        ?>
        <a href="<?= e($r['url']) ?>?lang=<?= e($lang) ?>" title="<?= e($bt) ?>" class="flex items-center gap-3 rounded-2xl border p-3.5 no-underline shadow-[0_6px_24px_rgba(15,23,42,.06)] <?= $r['partner'] ? 'border-amber-200 bg-amber-50/40' : 'border-slate-100 bg-white' ?>">
            <?= ui_bank_logo($r['code'], $r['name'], $r['logo'], 48, 'h-12 w-12 shrink-0 rounded-xl object-contain') ?>
            <span class="min-w-0 flex-1">
                <span class="block truncate font-bold leading-tight text-slate-900"><?= e($r['name']) ?></span>
                <?php if ($r['partner']): ?><span class="mt-1 mr-1 inline-block"><?= ui_partner_badge() ?></span><?php endif; ?>
                <?php if ($isBest): ?><span class="mt-1 inline-block rounded-full bg-green-50 px-2 py-0.5 text-[10px] font-extrabold uppercase tracking-wide text-green-600"><?= e(t('best_buy')) ?></span><?php endif; ?>
            </span>
            <span class="grid shrink-0 grid-cols-2 gap-3 text-right">
                <span><span class="block text-[10px] font-bold uppercase tracking-wider"><?= e(t('buy')) ?></span><span class="block text-[17px] font-extrabold tabular-nums <?= $isBest ? 'text-green-600' : 'text-slate-900' ?>"><?= ui_rate($r['buy']) ?></span></span>
                <span><span class="block text-[10px] font-bold uppercase tracking-wider"><?= e(t('sell')) ?></span><span class="block text-[17px] font-extrabold tabular-nums text-slate-900"><?= ui_rate($r['sell']) ?></span></span>
            </span>
        </a>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div class="rounded-3xl border border-slate-100 bg-white p-10 text-center shadow-sm"><?= e(t('no_data')) ?></div>
    <?php endif; ?>
</section>

<!-- Пересчёт сумм и текст о валюте -->
<div class="mt-10 grid gap-6 lg:grid-cols-2">
    <?php if ($bestBuy): ?>
    <section aria-labelledby="conv-h">
        <h2 id="conv-h" class="mb-4 text-xl font-black tracking-tight text-slate-900 sm:mb-5 sm:text-2xl"><?= e(t('cur.conv_title')) ?></h2>
        <div class="grid grid-cols-2 gap-2.5 sm:grid-cols-4">
            <?php foreach ($amounts as $a): ?>
            <div class="rounded-2xl border border-slate-100 bg-white p-3.5 text-center shadow-[0_6px_24px_rgba(15,23,42,.05)]">
                <div class="text-sm font-bold"><?= e(number_format($a, 0, '.', ' ')) ?> <?= e($cur) ?></div>
                <div class="mt-1 text-[17px] font-black tabular-nums text-slate-900"><?= e(number_format(round($a * $bestBuy), 0, '.', ' ')) ?> <span class="text-xs font-bold"><?= e(t('cur.conv_unit')) ?></span></div>
            </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>
    <article class="rounded-3xl border border-slate-100 bg-white p-5 shadow-[0_10px_40px_rgba(15,23,42,.06)] sm:p-8 <?= $bestBuy ? '' : 'lg:col-span-2' ?>">
        <h2 class="mb-4 text-xl font-black text-slate-900 sm:text-2xl"><?= e($aboutTitle) ?></h2>
        <div class="space-y-3 text-[15px] leading-relaxed"><?= $aboutHtml ?></div>
    </article>
</div>

<?php if ($faq): ?>
<section class="mt-10" aria-labelledby="faq-h">
    <h2 id="faq-h" class="mb-4 text-xl font-black tracking-tight text-slate-900 sm:mb-5 sm:text-2xl"><?= e(t('cur.faq_title')) ?></h2>
    <?= ui_faq($faq) ?>
</section>
<?php endif; ?>

<div class="mt-8 rounded-2xl border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-black"><?= e(t('disclaimer_full')) ?></div>

<script src="/assets/js/uplot.min.js"></script>
<script>
(function () {
    var C = <?= json_encode($jsCfg, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) ?>;
    var el = document.getElementById('rate-chart'), empty = document.getElementById('chart-empty'), stats = document.getElementById('chart-stats');
    var btns = document.querySelectorAll('.period-btn'), u = null, tip = null, ro = null, data = null;

    function esc(s) { return String(s).replace(/[&<>"']/g, function (c) { return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]; }); }
    function f2(n) { return Number(n).toFixed(n < 10 ? 2 : 2); }
    function fmtD(v, long) { var d = new Date(v * 1000); var dd = ('0' + d.getUTCDate()).slice(-2) + '.' + ('0' + (d.getUTCMonth() + 1)).slice(-2); return long ? dd + '.' + d.getUTCFullYear() : dd; }

    function setStats(d) {
        var buys = d[1]; if (!buys.length) return;
        var mn = Math.min.apply(null, buys), mx = Math.max.apply(null, buys), ch = (buys[buys.length - 1] - buys[0]) / buys[0] * 100;
        document.getElementById('st-min').textContent = f2(mn); document.getElementById('st-max').textContent = f2(mx);
        var c = document.getElementById('st-chg'); c.textContent = (ch > 0 ? '+' : '') + ch.toFixed(2) + '%';
        c.className = 'font-extrabold tabular-nums ' + (ch > 0 ? 'text-green-600' : ch < 0 ? 'text-red-600' : 'text-slate-900');
    }

    function draw(points) {
        if (u) { u.destroy(); u = null; }
        if (ro) { ro.disconnect(); ro = null; }
        if (tip && tip.parentNode) tip.parentNode.removeChild(tip);
        el.innerHTML = '';
        if (!points || points.length < 2 || typeof uPlot === 'undefined') { el.classList.add('hidden'); stats.classList.add('hidden'); empty.classList.remove('hidden'); return; }
        el.classList.remove('hidden'); stats.classList.remove('hidden'); empty.classList.add('hidden');
        data = [points.map(function (p) { return Date.parse(p.date + 'T00:00:00Z') / 1000; }), points.map(function (p) { return p.buy; }), points.map(function (p) { return p.sell; })];
        setStats(data);
        tip = document.createElement('div');
        tip.className = 'pointer-events-none absolute z-10 hidden whitespace-nowrap rounded-xl border border-slate-100 bg-white px-3 py-2 text-xs shadow-[0_10px_30px_rgba(15,23,42,.15)]';
        el.appendChild(tip);
        var h = el.clientHeight || 300, font = '600 12px Inter, "Noto Sans Armenian", sans-serif';
        var grad = function (rgb) { return function (uu) { var g = uu.ctx.createLinearGradient(0, uu.bbox.top, 0, uu.bbox.top + uu.bbox.height); g.addColorStop(0, 'rgba(' + rgb + ',.22)'); g.addColorStop(1, 'rgba(' + rgb + ',0)'); return g; }; };
        u = new uPlot({
            width: el.clientWidth, height: h, legend: { show: false }, padding: [12, 8, 0, 0],
            cursor: { drag: { x: false, y: false }, points: { size: 9, fill: '#fff', width: 2 } },
            scales: { x: { time: true }, y: { range: function (uu, mn, mx) { var p = (mx - mn) * 0.15 || 1; return [mn - p, mx + p]; } } },
            axes: [
                { stroke: '#0f172a', font: font, grid: { stroke: '#eef0f4', width: 1 }, ticks: { show: false }, gap: 8, values: function (uu, s) { return s.map(function (v) { return fmtD(v, false); }); } },
                { stroke: '#0f172a', font: font, grid: { stroke: '#eef0f4', width: 1 }, ticks: { show: false }, size: 56, values: function (uu, s) { return s.map(function (v) { return f2(v); }); } }
            ],
            series: [{}, { label: C.t.buy, stroke: '#2563eb', width: 2.5, fill: grad('37,99,235'), points: { show: false } },
                         { label: C.t.sell, stroke: '#6366f1', width: 2, dash: [6, 5], points: { show: false } }],
            hooks: { setCursor: [function (uu) {
                var i = uu.cursor.idx; if (i == null) { tip.classList.add('hidden'); return; }
                tip.classList.remove('hidden');
                tip.innerHTML = '<div class="font-bold text-slate-900">' + fmtD(data[0][i], true) + '</div>' +
                    '<div class="mt-0.5 flex items-center gap-1.5"><i class="inline-block h-2 w-2 rounded-full bg-blue-600"></i>' + esc(C.t.buy) + '<b class="ml-auto pl-3 tabular-nums">' + f2(data[1][i]) + '</b></div>' +
                    '<div class="flex items-center gap-1.5"><i class="inline-block h-2 w-2 rounded-full bg-indigo-500"></i>' + esc(C.t.sell) + '<b class="ml-auto pl-3 tabular-nums">' + f2(data[2][i]) + '</b></div>';
                var x = uu.valToPos(data[0][i], 'x'), y = uu.valToPos(data[1][i], 'y'), w = tip.offsetWidth;
                tip.style.left = Math.max(4, Math.min(el.clientWidth - w - 4, x - w / 2)) + 'px'; tip.style.top = Math.max(0, y - 78) + 'px';
            }] }
        }, data, el);
        ro = new ResizeObserver(function () { if (u) u.setSize({ width: el.clientWidth, height: el.clientHeight || h }); });
        ro.observe(el);
    }

    function load(days) {
        fetch('/history.php?cur=' + encodeURIComponent(C.cur) + '&days=' + days, { headers: { 'Accept': 'application/json' } })
            .then(function (r) { return r.json(); }).then(function (j) { draw(j.points || []); }).catch(function () { draw([]); });
    }
    btns.forEach(function (b) {
        b.addEventListener('click', function () {
            btns.forEach(function (x) { var on = x === b; x.setAttribute('aria-pressed', on ? 'true' : 'false'); x.classList.toggle('bg-white', on); x.classList.toggle('shadow', on); });
            load(+b.getAttribute('data-days'));
        });
    });
    var ready = (document.fonts && document.fonts.load) ? document.fonts.load('600 12px Inter').catch(function () {}) : Promise.resolve();
    ready.then(function () { draw(C.points); });

    /* Калькулятор: валюта <-> драм по лучшему курсу банков */
    var from = document.getElementById('cc-from'), to = document.getElementById('cc-to'), note = document.getElementById('cc-note'),
        fc = document.getElementById('cc-from-chip'), tc = document.getElementById('cc-to-chip'), swapped = false;
    function calc() {
        var a = parseFloat(from.value); if (isNaN(a) || a < 0) { to.textContent = '0'; return; }
        var rate = swapped ? C.bestSell : C.bestBuy; if (!rate) { to.textContent = '—'; return; }
        var out = swapped ? a / rate : a * rate;
        to.textContent = out.toLocaleString('en', { maximumFractionDigits: (!swapped || out >= 100) ? 0 : 2 }).replace(/,/g, ' ');
        note.textContent = C.t.best + ': ' + (swapped ? C.bestSellBank : C.bestBuyBank) + ' · 1 ' + C.cur + ' = ' + f2(rate) + ' AMD';
    }
    from.addEventListener('input', calc);
    document.getElementById('cc-swap').addEventListener('click', function () {
        swapped = !swapped;
        var a = fc.innerHTML; fc.innerHTML = tc.innerHTML; tc.innerHTML = a;
        calc();
    });
})();
</script>

<?php
$graph = [
    ['@type' => 'WebPage', '@id' => $pageCanonical . '#webpage', 'url' => $pageCanonical, 'name' => $pageTitle, 'description' => $pageDescription,
     'inLanguage' => $lang, 'isPartOf' => ['@id' => rtrim(SITE_URL, '/') . '/#website'], 'breadcrumb' => ['@id' => $pageCanonical . '#breadcrumb']],
    array_merge(ld_breadcrumbs($crumbs), ['@id' => $pageCanonical . '#breadcrumb']),
    ['@type' => 'ItemList', 'name' => $name . ' ' . t('rates_of'), 'numberOfItems' => count($rows),
     'itemListElement' => array_map(fn($r, $i) => ['@type' => 'ListItem', 'position' => $i + 1, 'name' => $r['name'], 'url' => rtrim(SITE_URL, '/') . $r['url']], $rows, array_keys($rows))],
];
if ($f = ld_faq($faq)) $graph[] = $f;
echo ld_script($graph);
require __DIR__ . '/includes/footer.php';
