<?php
require_once __DIR__ . '/includes/functions.php';
$lang = detect_lang();
$L = load_lang($lang);

$slug = strtolower(trim((string)($_GET['slug'] ?? '')));
$all = cities_index();
if (!isset($all[$slug])) pxq_not_found();
$city = $all[$slug];
$cityName = $city['name'][$lang];
$cityLoc = city_loc($cityName, $lang);

$matrix = get_rates_matrix(['USD', 'EUR', 'RUR']);
$banks = [];
foreach ($city['items'] as $it) {
    $code = $it['bank'];
    if (!isset($banks[$code])) {
        $banks[$code] = ['code' => $code, 'name' => bank_display_name($code, $lang), 'logo' => bank_logo_url($code, $matrix['banks'][$code]['logo'] ?? ''), 'rates' => $matrix['banks'][$code]['rates'] ?? [], 'branches' => []];
    }
    $banks[$code]['branches'][] = $it['branch'];
}
uasort($banks, fn($a, $b) => ($b['rates']['USD']['buy'] ?? 0) <=> ($a['rates']['USD']['buy'] ?? 0));
$best = null;
foreach ($banks as $b) {
    $buy = $b['rates']['USD']['buy'] ?? null;
    if ($buy !== null && ($best === null || $buy > $best['buy'])) $best = ['buy' => $buy, 'bank' => $b['name']];
}
$branchesN = count($city['items']);
$vars = ['city' => $cityName, 'city_loc' => $cityLoc, 'banks' => count($banks), 'branches' => $branchesN, 'best_buy' => $best ? fmt_rate((float)$best['buy']) : '', 'best_bank' => $best['bank'] ?? ''];
$vars['bank_list'] = implode(', ', array_column($banks, 'name'));
$cityFaq = [];
foreach ([1, 2] as $i) {
    if ($i === 2 && !$best) continue;
    $cityFaq[] = [cms_text_in("city.faq$i.q", $lang, $vars), cms_text_in("city.faq$i.a", $lang, $vars)];
}
$pageTitle = cms_text_in('seo.city.title', $lang, $vars);
$pageDescription = cms_text_in($best ? 'seo.city.description' : 'seo.city.description_plain', $lang, $vars);
$pagePath = city_page_url($slug);
$active = '';
$crumbs = [[t('home'), lurl('/')], [t('banks'), lurl('/banks')], [$cityName, null]];
$updatedAt = $matrix['updated_at'] ?? null;
$others = array_filter($all, fn($k) => $k !== $slug, ARRAY_FILTER_USE_KEY);

require __DIR__ . '/includes/header.php';
?>
<?= ui_breadcrumbs($crumbs) ?>
<div class="flex flex-wrap items-center justify-between gap-4">
    <h1 class="text-[28px] font-black leading-tight tracking-tight text-slate-900 sm:text-5xl"><?= e(cms_text_in('city.h1', $lang, $vars)) ?></h1>
    <?= ui_live($updatedAt) ?>
</div>
<p class="mt-3 max-w-3xl text-base leading-relaxed sm:text-lg"><?= e(cms_text_in($best ? 'city.lead' : 'city.lead_plain', $lang, $vars)) ?></p>

<section class="mt-8" aria-labelledby="cb-h">
    <h2 id="cb-h" class="mb-4 text-xl font-black tracking-tight text-slate-900 sm:text-2xl"><?= e(cms_text_in('city.banks_title', $lang, $vars)) ?></h2>
    <div class="grid gap-4 lg:grid-cols-2">
        <?php foreach ($banks as $code => $b): $bt = tv('common.link_bank', ['bank' => $b['name']]); ?>
        <article class="rounded-3xl border border-slate-100 bg-white p-5 shadow-[0_10px_40px_rgba(15,23,42,.06)]">
            <div class="flex items-center gap-3">
                <?= ui_bank_logo($code, $b['name'], $b['logo'], 48, 'h-12 w-12 shrink-0 rounded-xl object-contain') ?>
                <h3 class="min-w-0 flex-1 text-lg font-black leading-tight"><?= ui_link(lurl(bank_page_url($code)), $b['name'], $bt, 'text-slate-900 no-underline hover:text-blue-600') ?></h3>
            </div>
            <dl class="mt-4 grid grid-cols-3 gap-2 text-center">
                <?php foreach (['USD', 'EUR', 'RUR'] as $c): $r = $b['rates'][$c] ?? null; ?>
                <div class="rounded-xl bg-slate-50 px-2 py-2.5">
                    <dt class="text-[11px] font-extrabold"><?= e($c) ?></dt>
                    <dd class="mt-0.5 text-[13px] font-bold tabular-nums text-slate-900"><?= $r ? e(fmt_rate((float)$r['buy']) . ' / ' . fmt_rate((float)$r['sell'])) : '—' ?></dd>
                </div>
                <?php endforeach; ?>
            </dl>
            <ul class="mt-4 space-y-2">
                <?php foreach ($b['branches'] as $br):
                    $bn = $br['name'][$lang] ?? $cityName; $ba = $br['address'][$lang] ?? ''; $bp = $br['phone'] ?? '';
                ?>
                <li class="rounded-xl border border-slate-100 px-3 py-2.5 text-[14px]">
                    <?= ui_link(lurl(branch_page_url($code, $br['slug'])), $bn, tv('bank.branch_title', ['bank' => $b['name'], 'branch' => $bn, 'city' => $cityName]), 'font-bold text-blue-600 no-underline hover:underline') ?>
                    <?php if ($ba): ?><span class="block"><?= e($ba) ?></span><?php endif; ?>
                    <?php if ($bp): ?><?= ui_link('tel:' . preg_replace('/\s+/', '', $bp), $bp, t('phone') . ': ' . $bp, 'font-semibold tabular-nums text-slate-900 no-underline hover:text-blue-600') ?><?php endif; ?>
                </li>
                <?php endforeach; ?>
            </ul>
        </article>
        <?php endforeach; ?>
    </div>
    <p class="mt-3 text-sm"><?= e(t('city.note')) ?></p>
</section>

<?php if ($cityFaq): ?>
<section class="mt-10" aria-labelledby="faq-h">
    <h2 id="faq-h" class="mb-4 text-xl font-black tracking-tight text-slate-900 sm:mb-5 sm:text-2xl"><?= e(t('city.faq_title')) ?></h2>
    <?= ui_faq($cityFaq) ?>
</section>
<?php endif; ?>

<nav class="mt-10" aria-label="<?= e(t('city.other')) ?>">
    <h2 class="mb-3 text-lg font-black text-slate-900"><?= e(t('city.other')) ?></h2>
    <ul class="flex flex-wrap gap-2">
        <?php foreach ($others as $os => $oc): $on = $oc['name'][$lang]; ?>
        <li><?= ui_link(lurl(city_page_url($os)), $on, cms_text_in('city.link_title', $lang, ['city' => $on, 'city_loc' => city_loc($on, $lang)]), 'flex min-h-[44px] items-center rounded-xl border border-slate-200 bg-white px-4 text-sm font-bold text-slate-900 no-underline hover:border-blue-300 hover:text-blue-600') ?></li>
        <?php endforeach; ?>
    </ul>
</nav>

<div class="mt-8 rounded-2xl border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-black"><?= e(t('disclaimer_short')) ?></div>
<?php
echo ld_script([
    ['@type' => 'CollectionPage', '@id' => $pageCanonical . '#webpage', 'url' => $pageCanonical, 'name' => $pageTitle, 'description' => $pageDescription, 'inLanguage' => $lang, 'isPartOf' => ['@id' => rtrim(SITE_URL, '/') . '/#website'], 'breadcrumb' => ['@id' => $pageCanonical . '#breadcrumb'], 'dateModified' => $updatedAt ?: date('c')],
    array_merge(ld_breadcrumbs($crumbs), ['@id' => $pageCanonical . '#breadcrumb']),
]);
require __DIR__ . '/includes/footer.php';
