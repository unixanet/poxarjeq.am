<?php
require_once __DIR__ . '/includes/functions.php';
$lang = detect_lang();
$L = load_lang($lang);

$code = strtolower(trim($_GET['code'] ?? ''));
$city = strtolower(trim($_GET['city'] ?? ''));
if ($code === '' || $city === '' || !preg_match('/^[a-z0-9-]{2,64}$/', $code) || !preg_match('/^[a-z0-9-]{2,80}$/', $city)) pxq_not_found();

if (preg_match('/^[a-z]{3}$/', $city)) {
    $alias = ['rub' => 'RUR'];
    $curCode = $alias[$city] ?? strtoupper($city);
    if (in_array($curCode, array_values(array_unique(array_merge(all_currency_codes(), table_currency_codes()))), true) && get_bank_meta($code)) {
        if ($curCode !== strtoupper($city)) pxq_redirect(with_lang(bank_page_url($code) . '/' . strtolower($curCode), $lang));
        $bankCurCode = $code;
        $bankCurCur = $curCode;
        require __DIR__ . '/bank_currency.php';
        exit;
    }
}

$meta = get_bank_meta($code);
if (!$meta || !bank_is_visible($code)) pxq_not_found();
$apiBank = get_bank($code);
$v2 = api_v2('bank.php', ['code' => $code, 'lang' => 'hy'], 600);
$displayName = bank_display_name($code, $lang, $apiBank['name'] ?? null);
$rates = $apiBank['rates'] ?? [];
foreach (($v2['bank']['rates'] ?? []) as $c => $r) {
    if (isset($r['buy'], $r['sell'])) $rates[$c] = array_merge($rates[$c] ?? [], $r);
}
$logoUrl = bank_logo_url($code, $apiBank['logo'] ?? '');
$updatedAt = $apiBank['updated_at'] ?? null;
$branches = $meta['branches'] ?? [];

$branch = null;
foreach ($branches as $b) {
    if (($b['slug'] ?? '') === $city) { $branch = $b; break; }
}
if (!$branch) pxq_not_found();

$cityName = $branch['city'][$lang] ?? $city;
$branchName = $branch['name'][$lang] ?? $cityName;
$addrRaw = $branch['address'][$lang] ?? '';
$phone = $branch['phone'] ?? '';
$addr = addr_clean($addrRaw, $cityName);
$website = $meta['website'] ?? '';
$apiBranch = match_api_branch($v2['branches'] ?? [], $branch);
$hours = trim((string)($apiBranch['working_hours'] ?? ($v2['profile']['working_hours'] ?? '')));
if ($phone === '' && !empty($apiBranch['phone'])) $phone = (string)$apiBranch['phone'];
$vars = bank_vars($displayName, $lang) + ['branch' => $branchName, 'city' => $cityName, 'city_loc' => city_loc($cityName, $lang), 'address' => $addr, 'phone' => $phone];
$label = branch_label($displayName, $branchName, $cityName);

$altBankName = bank_alt_name($meta, $lang);
$altPick = $lang === 'en' ? 'ru' : 'en';
$altLabel = branch_label($altBankName !== '' ? $altBankName : $displayName, (string)($branch['name'][$altPick] ?? ''), (string)($branch['city'][$altPick] ?? $cityName));
$pageTitle = branch_title($displayName, $branchName, $cityName, $lang, $altLabel);
$pageDescription = branch_description($displayName, $branchName, $cityName, $addr, $phone, $hours, $lang);
$pagePath = branch_page_url($code, $city);
$active = '';
$crumbs = [[t('home'), lurl('/')], [$displayName, lurl(bank_page_url($code))], [$cityName, null]];
$sameCity = array_values(array_filter($branches, fn($b) => ($b['slug'] ?? '') !== $city && ($b['city']['hy'] ?? '') === ($branch['city']['hy'] ?? '')));
$otherCities = [];
foreach ($branches as $b) {
    $cn = $b['city'][$lang] ?? '';
    if ($cn !== '' && $cn !== $cityName && !isset($otherCities[$cn])) $otherCities[$cn] = $b['slug'];
}
$bankPhone = (string)($meta['phone'] ?? '');
$hoursSentence = $hours !== '' ? cms_text_in('branch.hours_known', $lang, ['hours' => $hours]) : cms_text_in('branch.hours_unknown', $lang);
$faqVars = $vars + ['label' => $label, 'bank_phone' => $bankPhone, 'hours_sentence' => $hoursSentence];
$faq = [];
for ($i = 1; $i <= 3; $i++) {
    $ak = "branch.faq$i.a";
    if ($i === 1 && $addr === '') $ak .= '_noaddr';
    if ($i === 2 && $phone !== '' && $phone === $bankPhone) $ak .= '_same';
    if ($i === 2 && $phone === '') continue;
    $faq[] = [cms_text_in("branch.faq$i.q", $lang, $faqVars), cms_text_in($ak, $lang, $faqVars)];
}
$summary = bank_summary($displayName, $lang, $rates['USD'] ?? null);
$mapUrl = $addr !== '' ? 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode($displayName . ' ' . $addr . ' ' . $cityName) : '';

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
        <h1 class="text-[26px] font-black leading-tight tracking-tight text-slate-900 sm:text-4xl"><?= e($label) ?></h1>
        <?php if ($altLabel !== '' && mb_strtolower($altLabel) !== mb_strtolower($label)): ?><p class="mt-1 text-sm font-semibold"><?= e(cms_text_in('branch.alt_line', $lang, ['alt' => $altLabel])) ?></p><?php endif; ?>
        <p class="mt-1 text-sm font-semibold"><?= e($cityName) ?><?php if ($addr): ?> · <?= e($addr) ?><?php endif; ?></p>
        <div class="mt-2"><?= ui_live($updatedAt) ?></div>
    </div>
    <div class="grid gap-2 sm:w-52">
        <?php if ($phone): ?><?= ui_link('tel:' . preg_replace('/\s+/', '', $phone), t('bank.call') . ' ' . $phone, tv('bank.call_title', $vars), 'inline-flex min-h-[46px] w-full items-center justify-center rounded-[14px] bg-blue-600 px-5 text-sm font-bold text-white no-underline shadow-[0_6px_16px_rgba(37,99,235,.28)] hover:bg-blue-700') ?><?php endif; ?>
        <?php if ($mapUrl): ?><?= ui_link($mapUrl, t('branch.route') . ' ↗', tv('branch.route_title', $vars), 'inline-flex min-h-[46px] w-full items-center justify-center rounded-[14px] border border-slate-200 bg-white px-5 text-sm font-bold text-slate-900 no-underline hover:border-blue-300 hover:text-blue-600', ['target' => '_blank', 'rel' => 'noopener nofollow']) ?><?php endif; ?>
    </div>
</section>

<?php if ($summary): ?><p class="mt-5 max-w-3xl text-base leading-relaxed sm:text-lg"><?= e($summary) ?></p><?php endif; ?>

<div class="mt-6 grid items-start gap-6 lg:grid-cols-[1fr_380px]">
    <section class="min-w-0" aria-labelledby="rates-h">
        <h2 id="rates-h" class="mb-4 text-xl font-black tracking-tight text-slate-900 sm:mb-5 sm:text-2xl"><?= e(tv('branch.rates_title', $vars)) ?></h2>
        <?php if ($rates): ?>
        <?= ui_bank_rates($rates, $displayName, $lang, $code) ?>
        <?php else: ?>
        <div class="rounded-3xl border border-slate-100 bg-white p-10 text-center shadow-sm"><?= e(t('no_data')) ?></div>
        <?php endif; ?>
    </section>

    <aside class="space-y-5 lg:sticky lg:top-24">
        <section class="rounded-3xl border border-slate-100 bg-white p-5 shadow-[0_10px_40px_rgba(15,23,42,.06)] sm:p-6" aria-labelledby="ct-h">
            <h2 id="ct-h" class="mb-4 text-lg font-black text-slate-900"><?= e(t('contacts')) ?></h2>
            <dl class="space-y-4 text-[15px]">
                <div><dt class="text-[11px] font-bold uppercase tracking-wider"><?= e(t('address')) ?></dt><dd class="mt-0.5 font-semibold"><?= e($cityName . ($addr ? ', ' . $addr : '')) ?></dd></div>
                <?php if ($phone): ?><div><dt class="text-[11px] font-bold uppercase tracking-wider"><?= e(t('phone')) ?></dt><dd class="mt-0.5 font-semibold"><?= ui_link('tel:' . preg_replace('/\s+/', '', $phone), $phone, t('phone') . ': ' . $phone, 'text-blue-600 no-underline hover:underline') ?></dd></div><?php endif; ?>
                <?php if ($hours): ?><div><dt class="text-[11px] font-bold uppercase tracking-wider"><?= e(t('bank.hours')) ?></dt><dd class="mt-0.5 font-semibold"><?= e($hours) ?></dd></div><?php endif; ?>
                <?php if ($website): ?><div><dt class="text-[11px] font-bold uppercase tracking-wider"><?= e(t('website')) ?></dt><dd class="mt-0.5 break-all font-semibold"><?= ui_link($website, preg_replace('#https?://#', '', $website), tv('bank.site_title', $vars), 'text-blue-600 no-underline hover:underline', ['target' => '_blank', 'rel' => 'noopener nofollow']) ?></dd></div><?php endif; ?>
            </dl>
            <?= ui_link(lurl(bank_page_url($code)), tv('branch.back', $vars), $displayName . ' — ' . t('bank.branches_title'), 'mt-4 inline-block text-sm font-bold text-blue-600 no-underline hover:underline') ?>
        </section>
    </aside>
</div>

<article class="mt-10 rounded-3xl border border-slate-100 bg-white p-5 shadow-[0_10px_40px_rgba(15,23,42,.06)] sm:p-8">
    <h2 class="mb-3 text-xl font-black text-slate-900 sm:text-2xl"><?= e(tv('branch.article_title', $vars)) ?></h2>
    <div class="space-y-2 text-[15px] leading-relaxed">
        <p><?= e(cms_text_in($addr !== '' ? 'branch.article_p1' : 'branch.article_p1_noaddr', $lang, $vars)) ?><?= $phone ? ' ' . e(tv('branch.phone_sentence', $vars)) : '' ?> <?= e($hoursSentence) ?></p>
        <?= tp('branch.article_p2', $vars) ?>
    </div>
</article>

<?php if ($faq): ?>
<section class="mt-10" aria-labelledby="faq-h">
    <h2 id="faq-h" class="mb-4 text-xl font-black tracking-tight text-slate-900 sm:mb-5 sm:text-2xl"><?= e(t('bank.faq_title')) ?></h2>
    <?= ui_faq($faq) ?>
</section>
<?php endif; ?>

<?php $cityLinkSlug = city_slug_from((string)($branch['city']['en'] ?? '')); if ($cityLinkSlug !== '' && isset(cities_index()[$cityLinkSlug])): ?>
<p class="mt-10"><?= ui_link(lurl(city_page_url($cityLinkSlug)), cms_text_in('city.all_banks_link', $lang, ['city' => $cityName]) . ' →', cms_text_in('city.link_title', $lang, ['city' => $cityName, 'city_loc' => city_loc($cityName, $lang)]), 'inline-flex min-h-[46px] items-center rounded-[14px] bg-blue-600 px-6 text-sm font-bold text-white no-underline hover:bg-blue-700') ?></p>
<?php endif; ?>

<?php if ($sameCity || $otherCities): ?>
<nav class="mt-10 grid gap-6 sm:grid-cols-2" aria-label="<?= e(t('bank.branches_title')) ?>">
    <?php if ($sameCity): ?>
    <div>
        <h2 class="mb-3 text-lg font-black text-slate-900"><?= e(tv('branch.same_city', ['city' => $cityName, 'bank' => $displayName])) ?></h2>
        <ul class="flex flex-wrap gap-2">
            <?php foreach ($sameCity as $sb): $sn = $sb['name'][$lang] ?? ''; ?>
            <li><?= ui_link(lurl(branch_page_url($code, $sb['slug'])), $sn, tv('bank.branch_title', ['bank' => $displayName, 'branch' => $sn, 'city' => $cityName]), 'flex min-h-[44px] items-center rounded-xl border border-slate-200 bg-white px-4 text-sm font-bold text-slate-900 no-underline hover:border-blue-300 hover:text-blue-600') ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>
    <?php if ($otherCities): ?>
    <div>
        <h2 class="mb-3 text-lg font-black text-slate-900"><?= e(tv('branch.other_cities', ['bank' => $displayName])) ?></h2>
        <ul class="flex flex-wrap gap-2">
            <?php foreach ($otherCities as $cn => $slug): ?>
            <li><?= ui_link(lurl(branch_page_url($code, $slug)), $cn, tv('bank.branch_title', ['bank' => $displayName, 'branch' => $cn, 'city' => $cn]), 'flex min-h-[44px] items-center rounded-xl border border-slate-200 bg-white px-4 text-sm font-bold text-slate-900 no-underline hover:border-blue-300 hover:text-blue-600') ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>
</nav>
<?php endif; ?>

<div class="mt-8 rounded-2xl border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-black"><?= e(t('disclaimer_short')) ?></div>

<?php
$org = ['@type' => 'BankOrCreditUnion', '@id' => $pageCanonical . '#branch', 'name' => $label, 'url' => $pageCanonical, 'parentOrganization' => ['@id' => rtrim(SITE_URL, '/') . bank_page_url($code) . '#bank'],
        'address' => ['@type' => 'PostalAddress', 'streetAddress' => $addr, 'addressLocality' => $cityName, 'addressCountry' => 'AM'], 'areaServed' => 'AM'];
if ($phone) $org['telephone'] = $phone;
if ($hours) $org['openingHours'] = $hours;
$graph = [
    ['@type' => 'WebPage', '@id' => $pageCanonical . '#webpage', 'url' => $pageCanonical, 'name' => $pageTitle, 'description' => $pageDescription, 'inLanguage' => $lang,
     'isPartOf' => ['@id' => rtrim(SITE_URL, '/') . '/#website'], 'about' => ['@id' => $pageCanonical . '#branch'], 'breadcrumb' => ['@id' => $pageCanonical . '#breadcrumb']],
    $org,
    array_merge(ld_breadcrumbs($crumbs), ['@id' => $pageCanonical . '#breadcrumb']),
];
echo ld_script($graph);
require __DIR__ . '/includes/footer.php';
