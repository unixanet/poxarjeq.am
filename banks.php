<?php
require_once __DIR__ . '/includes/functions.php';
$lang = detect_lang();
$L = load_lang($lang);

$matrix = get_rates_matrix(['USD', 'EUR']);
$codes = array_values(array_filter(array_unique(array_merge(top_bank_codes(40), sm_bank_codes_safe())), fn($c) => bank_is_visible($c) && get_bank_meta($c)));
$rows = [];
$byCity = [];
foreach ($codes as $code) {
    $meta = get_bank_meta($code);
    $name = bank_display_name($code, $lang);
    $rates = $matrix['banks'][$code]['rates'] ?? [];
    $branches = $meta['branches'] ?? [];
    $rows[] = ['code' => $code, 'name' => $name, 'logo' => bank_logo_url($code, $matrix['banks'][$code]['logo'] ?? ''), 'usd' => $rates['USD'] ?? null, 'eur' => $rates['EUR'] ?? null, 'n' => count($branches), 'partner' => bank_is_partner($code)];
    foreach ($branches as $br) {
        $cn = $br['city'][$lang] ?? '';
        if ($cn === '') continue;
        $byCity[$cn][$code] = ['bank' => $name, 'slug' => $br['slug'], 'branch' => $br['name'][$lang] ?? $cn];
    }
}
usort($rows, fn($a, $b) => [$b['partner'], $b['usd']['buy'] ?? 0] <=> [$a['partner'], $a['usd']['buy'] ?? 0]);
uasort($byCity, fn($a, $b) => count($b) <=> count($a));
$updatedAt = $matrix['updated_at'] ?? null;

$vars = ['n' => count($rows)];
$pageTitle = tv('seo.banks.title');
$pageDescription = tv('seo.banks.description', $vars);
$pagePath = '/banks';
$active = 'banks';
$crumbs = [[t('home'), lurl('/')], [t('banks'), null]];

require __DIR__ . '/includes/header.php';
?>
<?= ui_breadcrumbs($crumbs) ?>
<div class="flex flex-wrap items-center justify-between gap-4">
    <h1 class="text-[28px] font-black leading-tight tracking-tight text-slate-900 sm:text-5xl"><?= e(t('banks.h1')) ?></h1>
    <?= ui_live($updatedAt) ?>
</div>
<p class="mt-3 max-w-3xl text-base leading-relaxed sm:text-lg"><?= e(tv('banks.lead', $vars)) ?></p>

<section class="mt-8" aria-labelledby="list-h">
    <h2 id="list-h" class="sr-only"><?= e(t('banks.h1')) ?></h2>
    <div class="hidden overflow-hidden rounded-3xl border border-slate-100 bg-white shadow-[0_24px_60px_-12px_rgba(15,23,42,.14)] md:block">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <caption class="sr-only"><?= e(t('banks.h1')) ?></caption>
                <thead>
                    <tr class="border-b border-slate-200 bg-gradient-to-b from-slate-50 to-slate-100">
                        <th scope="col" class="px-5 py-3 text-left text-[11px] font-extrabold uppercase tracking-wider"><?= e(t('bank')) ?></th>
                        <th scope="col" class="px-4 py-3 text-right text-[11px] font-extrabold uppercase tracking-wider">USD <?= e(t('buy')) ?></th>
                        <th scope="col" class="px-4 py-3 text-right text-[11px] font-extrabold uppercase tracking-wider">USD <?= e(t('sell')) ?></th>
                        <th scope="col" class="px-4 py-3 text-right text-[11px] font-extrabold uppercase tracking-wider">EUR <?= e(t('buy')) ?></th>
                        <th scope="col" class="px-4 py-3 text-right text-[11px] font-extrabold uppercase tracking-wider">EUR <?= e(t('sell')) ?></th>
                        <th scope="col" class="px-4 py-3 text-right text-[11px] font-extrabold uppercase tracking-wider"><?= e(t('banks.col_branches')) ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $r): $bt = tv('common.link_bank', ['bank' => $r['name']]); ?>
                    <tr class="border-t border-slate-100 hover:bg-blue-50/40">
                        <td class="px-5 py-3"><a href="<?= e(lurl(bank_page_url($r['code']))) ?>" title="<?= e($bt) ?>" class="flex items-center gap-3 font-bold text-slate-900 no-underline hover:text-blue-600"><?= ui_bank_logo($r['code'], $r['name'], $r['logo'], 40, 'h-10 w-10 shrink-0 rounded-[10px] object-contain') ?><span><?= e($r['name']) ?><?php if ($r['partner']): ?><span class="mt-1 block"><?= ui_partner_badge() ?></span><?php endif; ?></span></a></td>
                        <td class="px-4 py-3 text-right font-semibold tabular-nums"><?= ui_rate($r['usd']['buy'] ?? null) ?></td>
                        <td class="px-4 py-3 text-right font-semibold tabular-nums"><?= ui_rate($r['usd']['sell'] ?? null) ?></td>
                        <td class="px-4 py-3 text-right font-semibold tabular-nums"><?= ui_rate($r['eur']['buy'] ?? null) ?></td>
                        <td class="px-4 py-3 text-right font-semibold tabular-nums"><?= ui_rate($r['eur']['sell'] ?? null) ?></td>
                        <td class="px-4 py-3 text-right tabular-nums"><?= $r['n'] ? e((string)$r['n']) : '—' ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <div class="grid gap-3 md:hidden">
        <?php foreach ($rows as $r): $bt = tv('common.link_bank', ['bank' => $r['name']]); ?>
        <a href="<?= e(lurl(bank_page_url($r['code']))) ?>" title="<?= e($bt) ?>" class="flex items-center gap-3 rounded-2xl border border-slate-100 bg-white p-3.5 text-slate-900 no-underline shadow-[0_6px_24px_rgba(15,23,42,.06)]">
            <?= ui_bank_logo($r['code'], $r['name'], $r['logo'], 48, 'h-12 w-12 shrink-0 rounded-xl object-contain') ?>
            <span class="min-w-0 flex-1">
                <span class="block truncate font-bold leading-tight text-slate-900"><?= e($r['name']) ?></span>
                <span class="mt-0.5 block text-xs"><?= $r['n'] ? e(tv('bank.branches_count', ['n' => $r['n']])) : '' ?></span>
            </span>
            <span class="grid shrink-0 grid-cols-2 gap-3 text-right">
                <span><span class="block text-[10px] font-bold uppercase tracking-wider">USD <?= e(t('buy')) ?></span><span class="block text-[17px] font-extrabold tabular-nums text-slate-900"><?= ui_rate($r['usd']['buy'] ?? null) ?></span></span>
                <span><span class="block text-[10px] font-bold uppercase tracking-wider">USD <?= e(t('sell')) ?></span><span class="block text-[17px] font-extrabold tabular-nums text-slate-900"><?= ui_rate($r['usd']['sell'] ?? null) ?></span></span>
            </span>
        </a>
        <?php endforeach; ?>
    </div>
</section>

<?php if ($byCity): ?>
<section class="mt-12" aria-labelledby="city-h">
    <h2 id="city-h" class="text-xl font-black tracking-tight text-slate-900 sm:text-2xl"><?= e(t('banks.cities_title')) ?></h2>
    <p class="mt-2 max-w-3xl text-[15px] leading-relaxed"><?= e(t('banks.cities_lead')) ?></p>
    <div class="mt-5 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <?php foreach ($byCity as $cn => $list): ?>
        <div class="rounded-3xl border border-slate-100 bg-white p-5 shadow-[0_10px_40px_rgba(15,23,42,.06)]">
            <h3 class="mb-3 text-lg font-black text-slate-900"><?= e($cn) ?> <span class="text-sm font-semibold"><?= e('· ' . count($list)) ?></span></h3>
            <ul class="flex flex-wrap gap-2">
                <?php foreach ($list as $code => $b): ?>
                <li><?= ui_link(lurl(branch_page_url($code, $b['slug'])), $b['bank'], tv('bank.branch_title', ['bank' => $b['bank'], 'branch' => $b['branch'], 'city' => $cn]), 'inline-flex min-h-[40px] items-center rounded-xl border border-slate-200 bg-white px-3 text-[13px] font-bold text-slate-900 no-underline hover:border-blue-300 hover:text-blue-600') ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<div class="mt-8 rounded-2xl border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-black"><?= e(t('disclaimer_short')) ?></div>
<?php
echo ld_script([
    ['@type' => 'CollectionPage', '@id' => $pageCanonical . '#webpage', 'url' => $pageCanonical, 'name' => $pageTitle, 'description' => $pageDescription, 'inLanguage' => $lang, 'isPartOf' => ['@id' => rtrim(SITE_URL, '/') . '/#website'], 'breadcrumb' => ['@id' => $pageCanonical . '#breadcrumb']],
    array_merge(ld_breadcrumbs($crumbs), ['@id' => $pageCanonical . '#breadcrumb']),
    ['@type' => 'ItemList', 'name' => t('banks.h1'), 'numberOfItems' => count($rows), 'itemListElement' => array_map(fn($r, $i) => ['@type' => 'ListItem', 'position' => $i + 1, 'name' => $r['name'], 'url' => rtrim(SITE_URL, '/') . bank_page_url($r['code'])], $rows, array_keys($rows))],
]);
require __DIR__ . '/includes/footer.php';
