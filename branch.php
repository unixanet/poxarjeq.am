<?php
require_once __DIR__ . '/includes/functions.php';
$lang = detect_lang();
$L = load_lang($lang);

$code = strtolower(trim($_GET['code'] ?? ''));
$city = strtolower(trim($_GET['city'] ?? ''));
if ($code === '' || $city === '') {
    header('Location: /');
    exit;
}

$meta = get_bank_meta($code);
$apiBank = get_bank($code);
$displayName = bank_display_name($code, $lang, $apiBank['name'] ?? null);
$rates = $apiBank['rates'] ?? [];
$logoUrl = bank_logo_url($code, $apiBank['logo'] ?? '');
$updatedAt = $apiBank['updated_at'] ?? null;
$branches = $meta['branches'] ?? [];

$branch = null;
foreach ($branches as $b) {
    if (($b['slug'] ?? '') === $city) { $branch = $b; break; }
}
if (!$branch) {
    header('Location: ' . bank_page_url($code));
    exit;
}

$cityName = $branch['city'][$lang] ?? $city;
$branchName = $branch['name'][$lang] ?? $cityName;
$addr = $branch['address'][$lang] ?? '';
$phone = $branch['phone'] ?? '';
$website = $meta['website'] ?? '';
$vars = ['bank' => $displayName, 'branch' => $branchName, 'city' => $cityName, 'address' => $addr, 'phone' => $phone];

[$pageTitle, $pageDescription] = seo_meta('branch', $vars);
$pageCanonical = rtrim(SITE_URL, '/') . '/' . $code . '/' . $city;
$active = '';
$crumbs = [[t('home'), '/'], [$displayName, bank_page_url($code)], [$cityName, null]];

require __DIR__ . '/includes/header.php';
?>

<?= ui_breadcrumbs($crumbs) ?>

<section class="flex flex-col gap-5 rounded-[28px] border border-slate-100 bg-white p-5 shadow-[0_24px_60px_-12px_rgba(15,23,42,.14)] sm:flex-row sm:items-center sm:p-7">
    <?php if ($logoUrl): ?>
    <?= ui_img($logoUrl, tv('common.logo_alt', $vars), 96, 96, 'h-20 w-20 shrink-0 rounded-2xl border border-slate-100 object-contain p-2 sm:h-24 sm:w-24') ?>
    <?php else: ?>
    <span class="grid h-20 w-20 shrink-0 place-items-center rounded-2xl border border-slate-100 bg-blue-50 text-3xl font-black text-blue-700 sm:h-24 sm:w-24" aria-hidden="true"><?= e(mb_strtoupper(mb_substr($displayName, 0, 1))) ?></span>
    <?php endif; ?>
    <div class="min-w-0 flex-1">
        <h1 class="text-[26px] font-black leading-tight tracking-tight text-slate-900 sm:text-4xl"><?= e($displayName) ?> — <?= e($branchName) ?></h1>
        <p class="mt-1 text-sm font-semibold"><?= e($cityName) ?><?php if ($addr): ?> · <?= e($addr) ?><?php endif; ?></p>
        <div class="mt-2"><?= ui_live($updatedAt) ?></div>
    </div>
    <?php if ($phone): ?>
    <div class="sm:w-52"><?= ui_link('tel:' . preg_replace('/\s+/', '', $phone), t('bank.call') . ' ' . $phone, tv('bank.call_title', $vars), 'inline-flex min-h-[46px] w-full items-center justify-center rounded-[14px] bg-blue-600 px-5 text-sm font-bold text-white no-underline shadow-[0_6px_16px_rgba(37,99,235,.28)] hover:bg-blue-700') ?></div>
    <?php endif; ?>
</section>

<div class="mt-6 grid items-start gap-6 lg:grid-cols-[1fr_380px]">
    <section class="min-w-0" aria-labelledby="rates-h">
        <h2 id="rates-h" class="mb-4 text-xl font-black tracking-tight text-slate-900 sm:mb-5 sm:text-2xl"><?= e(t('rates_of')) ?> — <?= e($displayName) ?></h2>
        <?php if ($rates): ?>
        <?= ui_bank_rates($rates, $displayName, $lang) ?>
        <?php else: ?>
        <div class="rounded-3xl border border-slate-100 bg-white p-10 text-center shadow-sm"><?= e(t('no_data')) ?></div>
        <?php endif; ?>
    </section>

    <aside class="space-y-5 lg:sticky lg:top-24">
        <section class="rounded-3xl border border-slate-100 bg-white p-5 shadow-[0_10px_40px_rgba(15,23,42,.06)] sm:p-6" aria-labelledby="ct-h">
            <h2 id="ct-h" class="mb-4 text-lg font-black text-slate-900"><?= e(t('contacts')) ?> — <?= e($cityName) ?></h2>
            <dl class="space-y-4 text-[15px]">
                <div><dt class="text-[11px] font-bold uppercase tracking-wider"><?= e(t('address')) ?></dt><dd class="mt-0.5 font-semibold"><?= e($addr) ?></dd></div>
                <?php if ($phone): ?><div><dt class="text-[11px] font-bold uppercase tracking-wider"><?= e(t('phone')) ?></dt><dd class="mt-0.5 font-semibold"><?= ui_link('tel:' . preg_replace('/\s+/', '', $phone), $phone, t('phone') . ': ' . $phone, 'no-underline hover:underline') ?></dd></div><?php endif; ?>
                <?php if ($website): ?><div><dt class="text-[11px] font-bold uppercase tracking-wider"><?= e(t('website')) ?></dt><dd class="mt-0.5 break-all font-semibold"><?= ui_link($website, preg_replace('#https?://#', '', $website), tv('bank.site_title', $vars), 'no-underline hover:underline', ['target' => '_blank', 'rel' => 'noopener nofollow']) ?></dd></div><?php endif; ?>
            </dl>
            <?= ui_link(bank_page_url($code), tv('branch.back', $vars), $displayName . ' — ' . t('bank.branches_title'), 'mt-4 inline-block text-sm font-bold no-underline hover:underline') ?>
        </section>
        <section class="rounded-3xl border border-blue-100 bg-blue-50 p-5 sm:p-6">
            <h2 class="mb-3 text-lg font-black text-slate-900"><?= e($branchName) ?></h2>
            <p class="text-[15px] leading-relaxed"><?= e(tv('branch.intro', $vars)) ?></p>
        </section>
    </aside>
</div>

<article class="mt-10 rounded-3xl border border-slate-100 bg-white p-5 shadow-[0_10px_40px_rgba(15,23,42,.06)] sm:p-8">
    <h2 class="mb-3 text-xl font-black text-slate-900 sm:text-2xl"><?= e(tv('branch.article_title', $vars)) ?></h2>
    <div class="space-y-2 text-[15px] leading-relaxed">
        <p><?= e(tv('branch.article_p1', $vars)) ?><?= $phone ? ' ' . e(tv('branch.phone_sentence', $vars)) : '' ?></p>
        <?= tp('branch.article_p2', $vars) ?>
    </div>
</article>

<div class="mt-8 rounded-2xl border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-black"><?= e(t('disclaimer_short')) ?></div>

<?php
$org = ['@type' => 'BankOrCreditUnion', '@id' => $pageCanonical . '#branch', 'name' => $displayName . ' — ' . $branchName, 'url' => $pageCanonical, 'parentOrganization' => ['@id' => rtrim(SITE_URL, '/') . bank_page_url($code) . '#bank'],
        'address' => ['@type' => 'PostalAddress', 'streetAddress' => $addr, 'addressLocality' => $cityName, 'addressCountry' => 'AM'], 'areaServed' => 'AM'];
if ($phone) $org['telephone'] = $phone;
echo ld_script([
    ['@type' => 'WebPage', '@id' => $pageCanonical . '#webpage', 'url' => $pageCanonical, 'name' => $pageTitle, 'description' => $pageDescription, 'inLanguage' => $lang,
     'isPartOf' => ['@id' => rtrim(SITE_URL, '/') . '/#website'], 'about' => ['@id' => $pageCanonical . '#branch'], 'breadcrumb' => ['@id' => $pageCanonical . '#breadcrumb']],
    $org,
    array_merge(ld_breadcrumbs($crumbs), ['@id' => $pageCanonical . '#breadcrumb']),
]);
require __DIR__ . '/includes/footer.php';
