<?php
require_once __DIR__ . '/includes/functions.php';
$lang = detect_lang();
$L = load_lang($lang);

$d = api_v2('exchangers.php', ['lang' => 'hy'], 900);
$orgs = $d['organizations'] ?? [];
$summary = $d['summary'] ?? [];
$updatedAt = $d['data_updated_at'] ?? null;
$cols = array_values(array_filter(['USD', 'EUR', 'RUR', 'GEL'], fn($c) => isset($summary[$c])));
usort($orgs, fn($a, $b) => ($b['rates']['USD']['buy'] ?? 0) <=> ($a['rates']['USD']['buy'] ?? 0));
$orgs = array_values(array_filter($orgs, fn($o) => !empty($o['rates'])));

$usd = $summary['USD'] ?? null;
$vars = [
    'count' => $usd['count'] ?? count($orgs),
    'best_buy' => $usd ? fmt_rate((float)$usd['best_buy']['value']) : '',
    'best_sell' => $usd ? fmt_rate((float)$usd['best_sell']['value']) : '',
    'avg_buy' => $usd ? fmt_rate((float)$usd['avg_buy']) : '',
];
$bk = get_banks_for_currency('USD');
$bkBuys = [];
foreach (($bk['banks'] ?? []) as $b0) if (!empty($b0['buy']) && bank_is_visible((string)($b0['code'] ?? ''))) $bkBuys[] = (float)$b0['buy'];
$vars += [
    'ex_avg' => $usd ? number_format((float)$usd['avg_buy'], 2, '.', '') : '', 'ex_best' => $usd ? number_format((float)$usd['best_buy']['value'], 2, '.', '') : '',
    'bk_avg' => $bkBuys ? number_format(array_sum($bkBuys) / count($bkBuys), 2, '.', '') : '', 'bk_best' => $bkBuys ? number_format(max($bkBuys), 2, '.', '') : '',
];
$insight = '';
if ($usd && $bkBuys) {
    $d0 = (float)$usd['best_buy']['value'] - max($bkBuys);
    $winKey = abs($d0) < 0.005 ? 'exch.win_eq' : ($d0 > 0 ? 'exch.win_ex' : 'exch.win_bk');
    $winnerText = cms_text_in($winKey, $lang, ['diff' => number_format(abs($d0), 2, '.', '')]);
    $insight = cms_text_in('exch.insight', $lang, $vars + ['winner_hy' => $winnerText, 'winner_ru' => $winnerText, 'winner_en' => $winnerText]);
}
$faq = cms_faq('exch', $vars);
$pageTitle = tv('seo.exch.title');
$pageDescription = tv($usd ? 'seo.exch.description' : 'seo.exch.description_plain', $vars);
$pagePath = '/poxanakman-keter';
$active = 'exchange';
$crumbs = [[t('home'), lurl('/')], [t('menu.exchange'), null]];

require __DIR__ . '/includes/header.php';
?>
<?= ui_breadcrumbs($crumbs) ?>
<div class="flex flex-wrap items-center justify-between gap-4">
    <h1 class="text-[28px] font-black leading-tight tracking-tight text-slate-900 sm:text-5xl"><?= e(t('exch.h1')) ?></h1>
    <?= ui_live($updatedAt) ?>
</div>
<p class="mt-3 max-w-3xl text-base leading-relaxed sm:text-lg"><?= e(tv($usd ? 'exch.lead' : 'exch.lead_plain', $vars)) ?></p>

<?php if ($cols): ?>
<div class="mt-6 grid grid-cols-2 gap-2.5 sm:grid-cols-4 sm:gap-4">
    <?php foreach ($cols as $c): $s = $summary[$c]; ?>
    <a href="<?= e(lurl('/currency.php?cur=' . $c)) ?>" title="<?= e(tv('common.link_currency', ['name' => currency_name($c, $lang), 'code' => $c])) ?>" class="block rounded-[18px] border border-slate-100 bg-white p-3.5 text-slate-900 no-underline shadow-[0_6px_24px_rgba(15,23,42,.06)] transition hover:-translate-y-0.5 hover:shadow-[0_10px_30px_rgba(37,99,235,.14)] sm:rounded-[22px] sm:p-5">
        <span class="flex items-center gap-2 text-sm font-extrabold"><?= ui_flag($c, 26, 'h-6 w-6 rounded-full object-cover shadow') ?><?= e($c) ?></span>
        <span class="mt-2 block text-[24px] font-black leading-none tracking-tight tabular-nums text-slate-900 sm:text-[30px]"><?= e(fmt_rate((float)$s['best_buy']['value'])) ?></span>
        <span class="mt-1.5 block text-[12px] leading-tight"><?= e(t('best_buy')) ?> · <?= e(tv('exch.points_n', ['n' => $s['count']])) ?></span>
    </a>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<section class="mt-8" aria-labelledby="exch-h">
    <h2 id="exch-h" class="mb-4 text-xl font-black tracking-tight text-slate-900 sm:text-2xl"><?= e(t('exch.table_title')) ?></h2>
    <?php if ($orgs): ?>
    <div class="hidden overflow-hidden rounded-3xl border border-slate-100 bg-white shadow-[0_24px_60px_-12px_rgba(15,23,42,.14)] md:block">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <caption class="sr-only"><?= e(t('exch.table_title')) ?></caption>
                <thead>
                    <tr class="border-b border-slate-200 bg-gradient-to-b from-slate-50 to-slate-100">
                        <th scope="col" rowspan="2" class="px-5 py-3 text-left text-[11px] font-extrabold uppercase tracking-wider"><?= e(t('exch.point')) ?></th>
                        <?php foreach ($cols as $c): ?>
                        <th scope="colgroup" colspan="2" class="border-l border-slate-200 px-2 py-2 text-center text-[12px] font-extrabold"><span class="inline-flex items-center gap-1.5"><?= ui_flag($c, 20, 'h-5 w-5 rounded-full object-cover') ?><?= e($c) ?></span></th>
                        <?php endforeach; ?>
                    </tr>
                    <tr class="border-b border-slate-200 bg-slate-50 text-[10px] font-bold uppercase tracking-wider">
                        <?php foreach ($cols as $c): ?><th scope="col" class="border-l border-slate-200 px-2 py-1.5 text-center"><?= e(t('buy')) ?></th><th scope="col" class="px-2 py-1.5 text-center"><?= e(t('sell')) ?></th><?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orgs as $o): ?>
                    <tr class="border-t border-slate-100 hover:bg-blue-50/40">
                        <th scope="row" class="px-5 py-3 text-left font-bold text-slate-900"><?= e(trim((string)$o['name'])) ?></th>
                        <?php foreach ($cols as $c): $r = $o['rates'][$c] ?? null; $best = $r && abs(($r['buy'] ?? 0) - (float)$summary[$c]['best_buy']['value']) < 0.00001; ?>
                        <td class="border-l border-slate-100 px-2 py-3 text-center font-semibold tabular-nums"><span class="<?= $best ? 'inline-block rounded-lg bg-green-50 px-2.5 py-1 font-extrabold text-green-600' : '' ?>"><?= e($r ? fmt_rate((float)$r['buy']) : '—') ?></span></td>
                        <td class="px-2 py-3 text-center font-semibold tabular-nums"><?= e($r ? fmt_rate((float)$r['sell']) : '—') ?></td>
                        <?php endforeach; ?>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <div class="grid gap-3 md:hidden">
        <?php foreach ($orgs as $o): ?>
        <div class="rounded-2xl border border-slate-100 bg-white p-4 shadow-[0_6px_24px_rgba(15,23,42,.06)]">
            <p class="font-bold leading-tight text-slate-900"><?= e(trim((string)$o['name'])) ?></p>
            <dl class="mt-3 grid grid-cols-2 gap-x-4 gap-y-2">
                <?php foreach ($cols as $c): $r = $o['rates'][$c] ?? null; if (!$r) continue; ?>
                <div class="flex items-center justify-between gap-2 rounded-xl bg-slate-50 px-3 py-2">
                    <dt class="flex items-center gap-1.5 text-[12px] font-extrabold"><?= ui_flag($c, 18, 'h-[18px] w-[18px] rounded-full object-cover') ?><?= e($c) ?></dt>
                    <dd class="text-right text-[13px] font-bold tabular-nums text-slate-900"><?= e(fmt_rate((float)$r['buy'])) ?> / <?= e(fmt_rate((float)$r['sell'])) ?></dd>
                </div>
                <?php endforeach; ?>
            </dl>
        </div>
        <?php endforeach; ?>
    </div>
    <p class="mt-3 text-sm"><?= e(t('exch.note')) ?></p>
    <?php else: ?>
    <div class="rounded-3xl border border-slate-100 bg-white p-10 text-center shadow-sm"><?= e(t('no_data')) ?></div>
    <?php endif; ?>
</section>

<section class="mt-10 rounded-3xl border border-blue-100 bg-blue-50 p-5 sm:p-8">
    <h2 class="mb-2 text-xl font-black text-slate-900"><?= e($insight !== '' ? t('exch.insight_h') : t('exch.vs_title')) ?></h2>
    <p class="text-[15px] leading-relaxed"><?= e($insight !== '' ? $insight : t('exch.vs_text')) ?></p>
    <p class="mt-4"><?= ui_link(lurl('/'), t('exch.vs_link'), t('today_rates'), 'inline-flex min-h-[46px] items-center rounded-[14px] bg-blue-600 px-6 text-sm font-bold text-white no-underline hover:bg-blue-700') ?></p>
</section>

<?php if ($faq): ?>
<section class="mt-10" aria-labelledby="faq-h">
    <h2 id="faq-h" class="mb-4 text-xl font-black tracking-tight text-slate-900 sm:mb-5 sm:text-2xl"><?= e(t('exch.faq_title')) ?></h2>
    <?= ui_faq($faq) ?>
</section>
<?php endif; ?>

<div class="mt-8 rounded-2xl border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-black"><?= e(t('disclaimer_short')) ?></div>
<?php
echo ld_script([
    ['@type' => 'CollectionPage', '@id' => $pageCanonical . '#webpage', 'url' => $pageCanonical, 'name' => $pageTitle, 'description' => $pageDescription, 'inLanguage' => $lang, 'isPartOf' => ['@id' => rtrim(SITE_URL, '/') . '/#website'], 'breadcrumb' => ['@id' => $pageCanonical . '#breadcrumb'], 'dateModified' => $updatedAt ?: date('c')],
    array_merge(ld_breadcrumbs($crumbs), ['@id' => $pageCanonical . '#breadcrumb']),
]);
require __DIR__ . '/includes/footer.php';
