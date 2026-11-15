<?php
require_once __DIR__ . '/includes/functions.php';
$lang = detect_lang();
$L = load_lang($lang);

$d = api_v2('gold.php', [], 1800);
$eval = $d['evaluation'] ?? [];
$bars = $d['bars'] ?? [];
$date = $d['date'] ?? null;
$p583 = null;
foreach ($eval as $e) if ((string)$e['label'] === '583') $p583 = $e;
$fmtDate = $date ? date('d.m.Y', strtotime($date)) : '';
$money = fn($n) => number_format((float)$n, 0, '.', ' ');
$vars = ['p583' => $p583 ? $money($p583['price']) : '', 'date' => $fmtDate];
$pageTitle = tv('seo.gold.title');
$pageDescription = tv($p583 ? 'seo.gold.description' : 'seo.gold.description_plain', $vars);
$pagePath = '/voski';
$active = 'gold';
$crumbs = [[t('home'), lurl('/')], [t('menu.gold'), null]];
$faq = cms_faq('gold', $vars);

$chg = function ($row) {
    $p = isset($row['change_pct']) ? (float)$row['change_pct'] : null;
    return ui_change($p);
};

require __DIR__ . '/includes/header.php';
?>
<?= ui_breadcrumbs($crumbs) ?>
<div class="flex flex-wrap items-center justify-between gap-4">
    <h1 class="text-[28px] font-black leading-tight tracking-tight text-slate-900 sm:text-5xl"><?= e(t('gold.h1')) ?></h1>
    <?php if ($fmtDate): ?><span class="inline-flex items-center rounded-full border border-slate-200 bg-white/80 px-3 py-1.5 text-xs"><?= e(tv('gold.date_label', $vars)) ?></span><?php endif; ?>
</div>
<p class="mt-3 max-w-3xl text-base leading-relaxed sm:text-lg"><?= e(tv($p583 ? 'gold.lead' : 'gold.lead_plain', $vars)) ?></p>

<?php if ($p583): ?>
<div class="mt-6 grid grid-cols-2 gap-2.5 sm:max-w-xl sm:gap-4">
    <div class="rounded-[18px] border border-slate-100 bg-white p-4 shadow-[0_6px_24px_rgba(15,23,42,.06)] sm:rounded-[22px] sm:p-5">
        <div class="text-[10px] font-bold uppercase tracking-wider sm:text-[11px]"><?= e(t('gold.tile_583')) ?></div>
        <div class="mt-2 text-[26px] font-black leading-none tracking-tight tabular-nums text-slate-900 sm:text-[34px]"><?= e($money($p583['price'])) ?> <span class="text-base">֏</span></div>
        <div class="mt-1.5 text-[12px]"><?= $chg($p583) ?></div>
    </div>
    <?php $bar1 = $bars[0] ?? null; if ($bar1): ?>
    <div class="rounded-[18px] border border-slate-100 bg-white p-4 shadow-[0_6px_24px_rgba(15,23,42,.06)] sm:rounded-[22px] sm:p-5">
        <div class="text-[10px] font-bold uppercase tracking-wider sm:text-[11px]"><?= e(t('gold.tile_bar')) ?></div>
        <div class="mt-2 text-[26px] font-black leading-none tracking-tight tabular-nums text-slate-900 sm:text-[34px]"><?= e($money($bar1['price'])) ?> <span class="text-base">֏</span></div>
        <div class="mt-1.5 text-[12px]"><?= $chg($bar1) ?></div>
    </div>
    <?php endif; ?>
</div>
<?php endif; ?>

<?php foreach ([['eval', $eval, 'gold.eval_title', 'gold.col_probe', 'gold.eval_note'], ['bars', $bars, 'gold.bars_title', 'gold.col_bar', 'gold.bars_note']] as [$id, $rows, $tk, $ck, $nk]): if (!$rows) continue; ?>
<section class="mt-10" aria-labelledby="<?= $id ?>-h">
    <h2 id="<?= $id ?>-h" class="mb-4 text-xl font-black tracking-tight text-slate-900 sm:text-2xl"><?= e(t($tk)) ?></h2>
    <div class="overflow-hidden rounded-3xl border border-slate-100 bg-white shadow-[0_24px_60px_-12px_rgba(15,23,42,.14)]">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <caption class="sr-only"><?= e(t($tk)) ?></caption>
                <thead>
                    <tr class="border-b border-slate-200 bg-gradient-to-b from-slate-50 to-slate-100">
                        <th scope="col" class="px-5 py-3 text-left text-[11px] font-extrabold uppercase tracking-wider"><?= e(t($ck)) ?></th>
                        <th scope="col" class="px-4 py-3 text-right text-[11px] font-extrabold uppercase tracking-wider"><?= e(t('gold.col_price')) ?></th>
                        <th scope="col" class="px-4 py-3 text-right text-[11px] font-extrabold uppercase tracking-wider"><?= e(t('gold.col_change')) ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $r): ?>
                    <tr class="border-t border-slate-100 hover:bg-blue-50/40">
                        <th scope="row" class="px-5 py-3 text-left font-bold text-slate-900"><?= e((string)$r['label']) ?></th>
                        <td class="px-4 py-3 text-right font-semibold tabular-nums"><?= e($money($r['price'])) ?> ֏</td>
                        <td class="px-4 py-3 text-right tabular-nums"><?= $chg($r) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <p class="mt-3 text-sm"><?= e(t($nk)) ?></p>
</section>
<?php endforeach; ?>

<?php if (!$eval && !$bars): ?>
<div class="mt-8 rounded-3xl border border-slate-100 bg-white p-10 text-center shadow-sm"><?= e(t('no_data')) ?></div>
<?php endif; ?>

<?php if ($faq): ?>
<section class="mt-10" aria-labelledby="faq-h">
    <h2 id="faq-h" class="mb-4 text-xl font-black tracking-tight text-slate-900 sm:mb-5 sm:text-2xl"><?= e(t('cur.faq_title')) ?></h2>
    <?= ui_faq($faq) ?>
</section>
<?php endif; ?>

<div class="mt-8 rounded-2xl border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-black"><?= e(t('gold.disclaimer')) ?></div>
<?php
$graph = [
    ['@type' => 'WebPage', '@id' => $pageCanonical . '#webpage', 'url' => $pageCanonical, 'name' => $pageTitle, 'description' => $pageDescription, 'inLanguage' => $lang, 'isPartOf' => ['@id' => rtrim(SITE_URL, '/') . '/#website'], 'breadcrumb' => ['@id' => $pageCanonical . '#breadcrumb'], 'dateModified' => $date ?: date('c')],
    array_merge(ld_breadcrumbs($crumbs), ['@id' => $pageCanonical . '#breadcrumb']),
];
if ($f = ld_faq($faq)) $graph[] = $f;
echo ld_script($graph);
require __DIR__ . '/includes/footer.php';
