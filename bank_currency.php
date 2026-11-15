<?php
$code = $bankCurCode;
$cur = $bankCurCur;
$meta = get_bank_meta($code);
if (!$meta || !bank_is_visible($code)) pxq_not_found();

$v2 = api_v2('bank.php', ['code' => $code, 'lang' => 'hy'], 600);
$apiBank = get_bank($code);
$rates = $apiBank['rates'] ?? [];
foreach (($v2['bank']['rates'] ?? []) as $c => $r) {
    if (isset($r['buy'], $r['sell'])) $rates[$c] = array_merge($rates[$c] ?? [], $r);
}
$r = $rates[$cur] ?? null;
if (!$r || empty($r['buy']) || empty($r['sell'])) pxq_not_found();

$displayName = bank_display_name($code, $lang, $apiBank['name'] ?? null);
$logoUrl = bank_logo_url($code, $apiBank['logo'] ?? '');
$updatedAt = $apiBank['updated_at'] ?? null;
$buy = (float)$r['buy'];
$sell = (float)$r['sell'];
$spread = calc_spread($buy, $sell);
$place = (int)($r['place_buy'] ?? 0);
$of = (int)($r['of'] ?? 0);
$change = isset($r['change_buy_pct']) ? (float)$r['change_buy_pct'] : null;

$all = get_banks_for_currency($cur);
$rows = [];
foreach (($all['banks'] ?? []) as $b) {
    $bc = (string)($b['code'] ?? '');
    if ($bc === '' || !bank_is_visible($bc)) continue;
    $bb = (float)($b['buy'] ?? 0); $bs = (float)($b['sell'] ?? 0);
    if ($bb <= 0 || $bs <= 0) continue;
    $rows[] = ['code' => $bc, 'name' => bank_display_name($bc, $lang, $b['name'] ?? null), 'logo' => $b['logo'] ?? '', 'buy' => $bb, 'sell' => $bs];
}
usort($rows, fn($a, $b) => $b['buy'] <=> $a['buy']);
$bestBuy = $rows ? max(array_column($rows, 'buy')) : $buy;
$bestSell = $rows ? min(array_column($rows, 'sell')) : $sell;
$avgBuy = $rows ? array_sum(array_column($rows, 'buy')) / count($rows) : $buy;
if ($place === 0 && $rows) {
    foreach ($rows as $i => $row) if ($row['code'] === $code) { $place = $i + 1; $of = count($rows); break; }
}

$curName = cur_name($cur, $lang);
$curGen = cur_gen($cur, $lang);
$vars = bank_vars($displayName, $lang) + [
    'cur' => $cur, 'name' => $curName, 'gen' => $curGen, 'gen_uc' => cur_ucfirst($curGen),
    'buy' => fmt_rate($buy), 'sell' => fmt_rate($sell), 'place' => $place, 'of' => $of, 'place_ord' => ord_num($place, $lang),
    'best_buy' => fmt_rate($bestBuy), 'best_sell' => fmt_rate($bestSell), 'avg_buy' => fmt_rate($avgBuy), 'spread' => number_format($spread, 2, '.', ''),
    'diff_best' => number_format($bestBuy - $buy, 2, '.', ''),
];
$pageTitle = cms_text_in('seo.bcur.title', $lang, $vars);
$pageDescription = cms_text_in($place > 0 && $of > 0 ? 'seo.bcur.description' : 'seo.bcur.description_plain', $lang, $vars);
$pagePath = bank_page_url($code) . '/' . strtolower($cur);
$active = '';
$h1 = cms_text_in('bcur.h1', $lang, $vars);
$lead = cms_text_in($place > 0 && $of > 0 ? 'bcur.lead_rank' : 'bcur.lead', $lang, $vars);
$crumbs = [[t('home'), lurl('/')], [$displayName, lurl(bank_page_url($code))], [$curName, null]];
$faq = cms_faq('bcur', $vars);
$otherCur = array_values(array_filter(array_keys($rates), fn($c) => $c !== $cur));
$otherBanksCur = array_slice(array_values(array_filter($rows, fn($x) => $x['code'] !== $code)), 0, 6);
$branches = $meta['branches'] ?? [];
$tiles = [
    [t('buy'), fmt_rate($buy), 'text-slate-900', $change !== null ? ui_change($change) : ''],
    [t('sell'), fmt_rate($sell), 'text-slate-900', ''],
    [t('bcur.tile_place'), $place > 0 ? $place . ' / ' . $of : '—', 'text-slate-900', ''],
    [t('spread'), number_format($spread, 2, '.', '') . '%', 'text-slate-900', ''],
];

require __DIR__ . '/includes/header.php';
?>

<?= ui_breadcrumbs($crumbs) ?>

<div class="flex flex-wrap items-center justify-between gap-4">
    <div class="flex min-w-0 items-center gap-3 sm:gap-4">
        <?= $logoUrl ? ui_img($logoUrl, tv('common.logo_alt', ['bank' => $displayName]), 64, 64, 'h-12 w-12 shrink-0 rounded-2xl border border-slate-100 bg-white object-contain p-1.5 sm:h-16 sm:w-16') : '' ?>
        <h1 class="text-[26px] font-black leading-tight tracking-tight text-slate-900 sm:text-4xl"><?= e($h1) ?></h1>
    </div>
    <?= ui_live($updatedAt) ?>
</div>
<p class="mt-3 max-w-3xl text-base leading-relaxed sm:text-lg"><?= e($lead) ?></p>

<div class="mt-6 grid grid-cols-2 gap-2.5 sm:grid-cols-4 sm:gap-4">
    <?php foreach ($tiles as [$lab, $val, $clr, $sub]): ?>
    <div class="rounded-[18px] border border-slate-100 bg-white p-3.5 shadow-[0_6px_24px_rgba(15,23,42,.06)] sm:rounded-[22px] sm:p-5">
        <div class="text-[10px] font-bold uppercase leading-tight tracking-wider sm:text-[11px]"><?= e($lab) ?></div>
        <div class="mt-1 text-[24px] font-black leading-none tracking-tight tabular-nums sm:mt-2 sm:text-[32px] <?= $clr ?>"><?= e($val) ?></div>
        <div class="mt-1.5 min-h-[16px] text-[12px] leading-tight"><?= $sub ?></div>
    </div>
    <?php endforeach; ?>
</div>

<section class="mt-6 rounded-3xl border border-slate-100 bg-white p-4 shadow-[0_10px_40px_rgba(15,23,42,.07)] sm:p-6" aria-labelledby="chart-h">
    <div class="mb-3 flex flex-wrap items-center justify-between gap-3">
        <h2 id="chart-h" class="text-lg font-black text-slate-900 sm:text-xl"><?= e(tv('bcur.chart_title', $vars)) ?></h2>
        <div class="inline-flex rounded-xl bg-slate-100 p-1 text-[13px] font-bold" role="group" aria-label="<?= e(t('cur.chart_title')) ?>">
            <?php foreach ([7, 30, 90, 365] as $d): ?>
            <button type="button" data-days="<?= $d ?>" aria-pressed="<?= $d === 30 ? 'true' : 'false' ?>" class="period-btn min-h-[36px] rounded-lg px-3 text-slate-900 sm:px-4 <?= $d === 30 ? 'bg-white shadow' : '' ?>"><?= e(t('cur.period' . $d)) ?></button>
            <?php endforeach; ?>
        </div>
    </div>
    <div id="rate-chart" class="relative h-[240px] w-full sm:h-[320px]" role="img" aria-label="<?= e(tv('bcur.chart_title', $vars)) ?>"></div>
    <p id="chart-empty" class="hidden py-16 text-center"><?= e(t('cur.no_history')) ?></p>
    <dl id="chart-stats" class="mt-3 grid grid-cols-3 gap-2 border-t border-slate-100 pt-3 text-sm">
        <div><dt class="text-[10px] font-bold uppercase tracking-wider"><?= e(t('cur.stat_min')) ?></dt><dd id="chart-stats-min" class="font-extrabold tabular-nums text-slate-900">—</dd></div>
        <div><dt class="text-[10px] font-bold uppercase tracking-wider"><?= e(t('cur.stat_max')) ?></dt><dd id="chart-stats-max" class="font-extrabold tabular-nums text-slate-900">—</dd></div>
        <div><dt class="text-[10px] font-bold uppercase tracking-wider"><?= e(t('cur.stat_change')) ?></dt><dd id="chart-stats-chg" class="font-extrabold tabular-nums text-slate-900">—</dd></div>
    </dl>
</section>

<?php if ($rows): ?>
<section class="mt-10" aria-labelledby="cmp-h">
    <h2 id="cmp-h" class="mb-4 text-xl font-black tracking-tight text-slate-900 sm:mb-5 sm:text-2xl"><?= e(tv('bcur.table_title', $vars)) ?></h2>
    <div class="overflow-hidden rounded-3xl border border-slate-100 bg-white shadow-[0_24px_60px_-12px_rgba(15,23,42,.14)]">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <caption class="sr-only"><?= e(tv('bcur.table_title', $vars)) ?></caption>
                <thead>
                    <tr class="border-b border-slate-200 bg-gradient-to-b from-slate-50 to-slate-100">
                        <th scope="col" class="w-12 px-4 py-3 text-left text-[11px] font-extrabold uppercase tracking-wider">#</th>
                        <th scope="col" class="px-2 py-3 text-left text-[11px] font-extrabold uppercase tracking-wider"><?= e(t('bank')) ?></th>
                        <th scope="col" class="px-4 py-3 text-right text-[11px] font-extrabold uppercase tracking-wider"><?= e(t('buy')) ?></th>
                        <th scope="col" class="px-4 py-3 text-right text-[11px] font-extrabold uppercase tracking-wider"><?= e(t('sell')) ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $i => $row): $me = $row['code'] === $code; $bt = tv('bcur.row_title', bank_vars($row['name'], $lang) + ['gen' => $curGen]); ?>
                    <tr class="border-t border-slate-100 <?= $me ? 'bg-blue-50' : 'hover:bg-blue-50/40' ?>">
                        <td class="px-4 py-3 font-semibold tabular-nums"><?= $i + 1 ?></td>
                        <td class="px-2 py-3">
                            <?php if ($me): ?>
                            <span class="flex items-center gap-3 font-black text-slate-900"><?= ui_bank_logo($row['code'], $row['name'], $row['logo'], 36, 'h-9 w-9 shrink-0 rounded-lg object-contain') ?><?= e($row['name']) ?></span>
                            <?php else: ?>
                            <a href="<?= e(lurl(bank_page_url($row['code']) . '/' . strtolower($cur))) ?>" title="<?= e($bt) ?>" class="flex items-center gap-3 font-bold text-slate-900 no-underline hover:text-blue-600"><?= ui_bank_logo($row['code'], $row['name'], $row['logo'], 36, 'h-9 w-9 shrink-0 rounded-lg object-contain') ?><?= e($row['name']) ?></a>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3 text-right font-semibold tabular-nums"><span class="<?= abs($row['buy'] - $bestBuy) < 0.00001 ? 'inline-block rounded-lg bg-green-50 px-2.5 py-1 font-extrabold text-green-600' : '' ?>"><?= ui_rate($row['buy']) ?></span></td>
                        <td class="px-4 py-3 text-right font-semibold tabular-nums"><?= ui_rate($row['sell']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>
<?php endif; ?>

<article class="mt-10 rounded-3xl border border-slate-100 bg-white p-5 shadow-[0_10px_40px_rgba(15,23,42,.06)] sm:p-8">
    <h2 class="mb-3 text-xl font-black text-slate-900 sm:text-2xl"><?= e(tv('bcur.article_title', $vars)) ?></h2>
    <div class="space-y-2 text-[15px] leading-relaxed"><?= tp('bcur.article_p1', $vars) . tp('bcur.article_p2', $vars) ?></div>
</article>

<?php if ($faq): ?>
<section class="mt-10" aria-labelledby="faq-h">
    <h2 id="faq-h" class="mb-4 text-xl font-black tracking-tight text-slate-900 sm:mb-5 sm:text-2xl"><?= e(t('bank.faq_title')) ?></h2>
    <?= ui_faq($faq) ?>
</section>
<?php endif; ?>

<nav class="mt-10 grid gap-6 lg:grid-cols-3" aria-label="<?= e(t('bcur.related')) ?>">
    <div>
        <h2 class="mb-3 text-lg font-black text-slate-900"><?= e(tv('bcur.more_cur', $vars)) ?></h2>
        <ul class="flex flex-wrap gap-2">
            <?php foreach ($otherCur as $oc): $on = currency_name($oc, $lang); ?>
            <li><?= ui_link(lurl(bank_page_url($code) . '/' . strtolower($oc)), $on, tv('bank.currency_link_title', ['bank' => $displayName, 'name' => $on]), 'flex min-h-[44px] items-center rounded-xl border border-slate-200 bg-white px-4 text-sm font-bold text-slate-900 no-underline hover:border-blue-300 hover:text-blue-600') ?></li>
            <?php endforeach; ?>
            <li><?= ui_link(lurl(bank_page_url($code)), $displayName, tv('common.link_bank', ['bank' => $displayName]), 'flex min-h-[44px] items-center rounded-xl bg-blue-600 px-4 text-sm font-bold text-white no-underline hover:bg-blue-700') ?></li>
        </ul>
    </div>
    <div>
        <h2 class="mb-3 text-lg font-black text-slate-900"><?= e(tv('bcur.other_banks', $vars)) ?></h2>
        <ul class="flex flex-wrap gap-2">
            <?php foreach ($otherBanksCur as $ob): ?>
            <li><?= ui_link(lurl(bank_page_url($ob['code']) . '/' . strtolower($cur)), $ob['name'], tv('bcur.row_title', bank_vars($ob['name'], $lang) + ['gen' => $curGen]), 'flex min-h-[44px] items-center rounded-xl border border-slate-200 bg-white px-4 text-sm font-bold text-slate-900 no-underline hover:border-blue-300 hover:text-blue-600') ?></li>
            <?php endforeach; ?>
            <li><?= ui_link(lurl('/currency.php?cur=' . $cur), $curName, tv('common.link_currency', ['name' => $curName, 'code' => $cur]), 'flex min-h-[44px] items-center rounded-xl bg-blue-600 px-4 text-sm font-bold text-white no-underline hover:bg-blue-700') ?></li>
        </ul>
    </div>
    <?php if ($branches): ?>
    <div>
        <h2 class="mb-3 text-lg font-black text-slate-900"><?= e(tv('bank.branches_h', $vars)) ?></h2>
        <ul class="flex flex-wrap gap-2">
            <?php $seen = []; foreach ($branches as $br): $cn = $br['city'][$lang] ?? ''; if ($cn === '' || isset($seen[$cn])) continue; $seen[$cn] = 1; ?>
            <li><?= ui_link(lurl(branch_page_url($code, $br['slug'])), $cn, tv('bank.branch_title', ['bank' => $displayName, 'branch' => $cn, 'city' => $cn]), 'flex min-h-[44px] items-center rounded-xl border border-slate-200 bg-white px-4 text-sm font-bold text-slate-900 no-underline hover:border-blue-300 hover:text-blue-600') ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>
</nav>

<div class="mt-8 rounded-2xl border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-black"><?= e(t('disclaimer_short')) ?></div>

<script src="/assets/js/uplot.min.js"></script>
<script src="/assets/js/pxq-chart.js"></script>
<script>
PxqChart({ el: 'rate-chart', empty: 'chart-empty', stats: 'chart-stats', buttons: '.period-btn', cur: <?= json_encode($cur) ?>, bank: <?= json_encode($code) ?>, days: 30, t: <?= json_encode(['buy' => t('buy'), 'sell' => t('sell')], JSON_UNESCAPED_UNICODE) ?> });
</script>

<?php
$graph = [
    ['@type' => 'WebPage', '@id' => $pageCanonical . '#webpage', 'url' => $pageCanonical, 'name' => $pageTitle, 'description' => $pageDescription, 'inLanguage' => $lang,
     'isPartOf' => ['@id' => rtrim(SITE_URL, '/') . '/#website'], 'breadcrumb' => ['@id' => $pageCanonical . '#breadcrumb'], 'dateModified' => $updatedAt ?: date('c')],
    array_merge(ld_breadcrumbs($crumbs), ['@id' => $pageCanonical . '#breadcrumb']),
];
if ($f = ld_faq($faq)) $graph[] = $f;
echo ld_script($graph);
require __DIR__ . '/includes/footer.php';
