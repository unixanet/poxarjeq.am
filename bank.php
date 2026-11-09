<?php
require_once __DIR__ . '/includes/functions.php';
$lang = detect_lang();
$L = load_lang($lang);

$code = strtolower(trim($_GET['code'] ?? ''));
$script = basename($_SERVER['SCRIPT_NAME'] ?? '', '.php');
if ($code === '' && $script !== 'bank' && $script !== 'index') {
    $code = $script;
}
if ($code === '') {
    header('Location: /');
    exit;
}

$apiBank = get_bank($code);
$meta = get_bank_meta($code);
$rates = $apiBank['rates'] ?? [];
$updatedAt = $apiBank['updated_at'] ?? null;
$displayName = bank_display_name($code, $lang, $apiBank['name'] ?? null);
$logoUrl = bank_logo_url($code, $apiBank['logo'] ?? '');
$isPartner = bank_is_partner($code);
$branches = $meta['branches'] ?? [];
$vars = ['bank' => $displayName];

/* ---- SEO: своё для банка (админка → Банки) или шаблон из админки → Тексты ---- */
[$tplTitle, $tplDesc] = seo_meta('bank', $vars);
[$ovTitle, $ovDesc] = bank_seo_override($code, $lang);
$pageTitle = $ovTitle !== '' ? $ovTitle : $tplTitle;
$pageDescription = $ovDesc !== '' ? $ovDesc : $tplDesc;
$pageCanonical = rtrim(SITE_URL, '/') . bank_page_url($code);
$active = '';

$address = $meta['address'][$lang] ?? '';
$phone = $meta['phone'] ?? '';
$email = $meta['email'] ?? '';
$website = $meta['website'] ?? '';
$about = $meta['about'][$lang] ?? '';
$faq = cms_faq('bank', $vars);
$crumbs = [[t('home'), '/'], [$displayName, null]];

$cities = [];
foreach ($branches as $br) {
    $c = $br['city'][$lang] ?? '';
    if ($c !== '' && !in_array($c, $cities, true)) $cities[] = $c;
}

require __DIR__ . '/includes/header.php';
?>

<?= ui_breadcrumbs($crumbs) ?>

<!-- Шапка банка -->
<section class="flex flex-col gap-5 rounded-[28px] border border-slate-100 bg-white p-5 shadow-[0_24px_60px_-12px_rgba(15,23,42,.14)] sm:flex-row sm:items-center sm:p-7">
    <?php if ($logoUrl): ?>
    <?= ui_img($logoUrl, tv('common.logo_alt', $vars), 96, 96, 'h-20 w-20 shrink-0 rounded-2xl border border-slate-100 object-contain p-2 sm:h-24 sm:w-24', ['fetchpriority' => 'high']) ?>
    <?php else: ?>
    <span class="grid h-20 w-20 shrink-0 place-items-center rounded-2xl border border-slate-100 bg-blue-50 text-3xl font-black text-blue-700 sm:h-24 sm:w-24" aria-hidden="true"><?= e(mb_strtoupper(mb_substr($displayName, 0, 1))) ?></span>
    <?php endif; ?>
    <div class="min-w-0 flex-1">
        <div class="flex flex-wrap items-center gap-2">
            <h1 class="text-[28px] font-black leading-tight tracking-tight text-slate-900 sm:text-4xl"><?= e($displayName) ?></h1>
            <?php if ($isPartner): ?><span class="inline-block rounded-full bg-amber-100 px-2.5 py-1 text-[11px] font-extrabold uppercase tracking-wide text-amber-800" title="<?= e(t('common.partner_title')) ?>">★ <?= e(t('partner')) ?></span><?php endif; ?>
        </div>
        <?php if ($meta && (!empty($meta['legal']) || !empty($meta['swift']))): ?>
        <p class="mt-1 text-sm"><?= e(implode(' · ', array_filter([$meta['legal'] ?? '', !empty($meta['swift']) ? 'SWIFT ' . $meta['swift'] : '']))) ?></p>
        <?php endif; ?>
        <div class="mt-2 flex flex-wrap items-center gap-x-4 gap-y-2 text-sm font-semibold">
            <?php if ($branches): ?><span><?= e(tv('bank.branches_count', ['n' => count($branches)])) ?></span><?php endif; ?>
            <?= ui_live($updatedAt) ?>
        </div>
    </div>
    <?php if ($phone || $website): ?>
    <div class="grid grid-cols-2 gap-2 sm:w-52 sm:grid-cols-1">
        <?php if ($phone): ?>
        <?= ui_link('tel:' . preg_replace('/\s+/', '', $phone), t('bank.call'), tv('bank.call_title', $vars), 'inline-flex min-h-[46px] items-center justify-center rounded-[14px] bg-blue-600 px-5 text-sm font-bold text-white no-underline shadow-[0_6px_16px_rgba(37,99,235,.28)] hover:bg-blue-700') ?>
        <?php endif; ?>
        <?php if ($website): ?>
        <?= ui_link($website, t('website') . ' ↗', tv('bank.site_title', $vars), 'inline-flex min-h-[46px] items-center justify-center rounded-[14px] border border-slate-200 bg-white px-5 text-sm font-bold text-black no-underline hover:border-blue-300', ['target' => '_blank', 'rel' => 'noopener nofollow']) ?>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</section>

<div class="mt-6 grid items-start gap-6 lg:grid-cols-[1fr_380px]">
    <div class="min-w-0 space-y-8">
        <!-- Курсы -->
        <section aria-labelledby="rates-h">
            <h2 id="rates-h" class="mb-4 text-xl font-black tracking-tight text-slate-900 sm:mb-5 sm:text-2xl"><?= e(t('rates_of')) ?> — <?= e($displayName) ?></h2>
            <?php if ($rates): ?>
            <?= ui_bank_rates($rates, $displayName, $lang) ?>
            <?php else: ?>
            <div class="rounded-3xl border border-slate-100 bg-white p-10 text-center shadow-sm"><?= e(t('no_data')) ?></div>
            <?php endif; ?>
        </section>

        <!-- Филиалы -->
        <?php if ($branches): ?>
        <section id="branches" aria-labelledby="br-h" class="scroll-mt-24">
            <h2 id="br-h" class="mb-4 text-xl font-black tracking-tight text-slate-900 sm:mb-5 sm:text-2xl"><?= e(t('bank.branches_title')) ?></h2>
            <?php if (count($cities) > 1): ?>
            <div id="city-chips" class="-mx-4 mb-4 flex gap-2 overflow-x-auto px-4 [scrollbar-width:none] sm:mx-0 sm:flex-wrap sm:px-0 [&::-webkit-scrollbar]:hidden" role="group" aria-label="<?= e(t('bank.branches_title')) ?>">
                <button type="button" data-city="" aria-pressed="true" class="city-chip min-h-[40px] shrink-0 rounded-xl border border-blue-600 bg-blue-600 px-4 text-sm font-bold text-white"><?= e(t('bank.all_cities')) ?></button>
                <?php foreach ($cities as $c): ?>
                <button type="button" data-city="<?= e($c) ?>" aria-pressed="false" class="city-chip min-h-[40px] shrink-0 rounded-xl border border-slate-200 bg-white px-4 text-sm font-bold text-black"><?= e($c) ?></button>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-2 xl:grid-cols-3">
                <?php foreach ($branches as $br):
                    $slug = $br['slug'] ?? '';
                    $bn = $br['name'][$lang] ?? ''; $bc = $br['city'][$lang] ?? ''; $ba = $br['address'][$lang] ?? ''; $bp = $br['phone'] ?? '';
                    $href = $slug ? branch_page_url($code, $slug) : '#';
                ?>
                <a href="<?= e($href) ?>" data-city="<?= e($bc) ?>" title="<?= e(tv('bank.branch_title', ['bank' => $displayName, 'branch' => $bn, 'city' => $bc])) ?>" class="branch-card block rounded-2xl border border-slate-100 bg-white p-4 no-underline shadow-[0_6px_24px_rgba(15,23,42,.05)] transition hover:border-blue-300 hover:shadow-[0_10px_30px_rgba(37,99,235,.12)]">
                    <span class="block font-bold text-slate-900"><?= e($bn) ?></span>
                    <span class="mt-0.5 block text-sm font-semibold text-blue-600"><?= e($bc) ?></span>
                    <?php if ($ba): ?><span class="mt-2 block text-sm text-black"><?= e($ba) ?></span><?php endif; ?>
                    <?php if ($bp): ?><span class="block text-sm font-semibold tabular-nums text-black"><?= e($bp) ?></span><?php endif; ?>
                </a>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>
    </div>

    <aside class="space-y-5 lg:sticky lg:top-24">
        <?php if ($meta): ?>
        <section class="rounded-3xl border border-slate-100 bg-white p-5 shadow-[0_10px_40px_rgba(15,23,42,.06)] sm:p-6" aria-labelledby="ct-h">
            <h2 id="ct-h" class="mb-4 text-lg font-black text-slate-900"><?= e(t('contacts')) ?></h2>
            <dl class="space-y-4 text-[15px]">
                <?php if ($address): ?><div><dt class="text-[11px] font-bold uppercase tracking-wider"><?= e(t('address')) ?></dt><dd class="mt-0.5 font-semibold"><?= e($address) ?></dd></div><?php endif; ?>
                <?php if ($phone): ?><div><dt class="text-[11px] font-bold uppercase tracking-wider"><?= e(t('phone')) ?></dt><dd class="mt-0.5 font-semibold"><?= ui_link('tel:' . preg_replace('/\s+/', '', $phone), $phone, t('phone') . ': ' . $phone, 'no-underline hover:underline') ?></dd></div><?php endif; ?>
                <?php if (!empty($meta['phone2'])): ?><div><dt class="text-[11px] font-bold uppercase tracking-wider"><?= e(t('phone')) ?> 2</dt><dd class="mt-0.5 font-semibold"><?= ui_link('tel:' . preg_replace('/\s+/', '', $meta['phone2']), $meta['phone2'], t('phone') . ': ' . $meta['phone2'], 'no-underline hover:underline') ?></dd></div><?php endif; ?>
                <?php if ($email): ?><div><dt class="text-[11px] font-bold uppercase tracking-wider"><?= e(t('email')) ?></dt><dd class="mt-0.5 break-all font-semibold"><?= ui_link('mailto:' . $email, $email, t('email') . ': ' . $email, 'no-underline hover:underline') ?></dd></div><?php endif; ?>
                <?php if ($website): ?><div><dt class="text-[11px] font-bold uppercase tracking-wider"><?= e(t('website')) ?></dt><dd class="mt-0.5 break-all font-semibold"><?= ui_link($website, preg_replace('#https?://#', '', $website), tv('bank.site_title', $vars), 'no-underline hover:underline', ['target' => '_blank', 'rel' => 'noopener nofollow']) ?></dd></div><?php endif; ?>
            </dl>
        </section>
        <?php if ($about): ?>
        <section class="rounded-3xl border border-blue-100 bg-blue-50 p-5 sm:p-6" aria-labelledby="ab-h">
            <h2 id="ab-h" class="mb-3 text-lg font-black text-slate-900"><?= e(t('about_bank')) ?></h2>
            <p class="text-[15px] leading-relaxed"><?= e($about) ?></p>
        </section>
        <?php endif; ?>
        <?php endif; ?>
    </aside>
</div>

<article class="mt-10 rounded-3xl border border-slate-100 bg-white p-5 shadow-[0_10px_40px_rgba(15,23,42,.06)] sm:p-8">
    <h2 class="mb-3 text-xl font-black text-slate-900 sm:text-2xl"><?= e(tv('bank.article_title', $vars)) ?></h2>
    <div class="space-y-2 text-[15px] leading-relaxed"><?= tp('bank.article_p1', $vars) . tp('bank.article_p2', $vars) ?></div>
</article>

<?php if ($faq): ?>
<section class="mt-10" aria-labelledby="faq-h">
    <h2 id="faq-h" class="mb-4 text-xl font-black tracking-tight text-slate-900 sm:mb-5 sm:text-2xl"><?= e(t('bank.faq_title')) ?></h2>
    <?= ui_faq($faq) ?>
</section>
<?php endif; ?>

<div class="mt-8 rounded-2xl border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-black"><?= e(t('disclaimer_short')) ?></div>

<?php if (count($cities) > 1): ?>
<script>
(function () {
    var chips = document.querySelectorAll('.city-chip'), cards = document.querySelectorAll('.branch-card');
    chips.forEach(function (ch) {
        ch.addEventListener('click', function () {
            var city = ch.getAttribute('data-city');
            chips.forEach(function (x) {
                var on = x === ch; x.setAttribute('aria-pressed', on ? 'true' : 'false');
                x.classList.toggle('bg-blue-600', on); x.classList.toggle('border-blue-600', on); x.classList.toggle('text-white', on);
                x.classList.toggle('bg-white', !on); x.classList.toggle('border-slate-200', !on); x.classList.toggle('text-black', !on);
            });
            cards.forEach(function (c) { c.classList.toggle('hidden', !!city && c.getAttribute('data-city') !== city); });
        });
    });
})();
</script>
<?php endif; ?>

<?php
$org = ['@type' => 'BankOrCreditUnion', '@id' => $pageCanonical . '#bank', 'name' => $displayName, 'url' => $website ?: $pageCanonical,
        'description' => $about ?: $pageDescription, 'areaServed' => 'AM'];
if ($logoUrl) $org['logo'] = str_starts_with($logoUrl, 'http') ? $logoUrl : rtrim(SITE_URL, '/') . $logoUrl;
if ($phone) $org['telephone'] = $phone;
if ($email) $org['email'] = $email;
if (!empty($meta['legal'])) $org['legalName'] = $meta['legal'];
if ($address) $org['address'] = ['@type' => 'PostalAddress', 'streetAddress' => $address, 'addressCountry' => 'AM'];
$graph = [
    ['@type' => 'WebPage', '@id' => $pageCanonical . '#webpage', 'url' => $pageCanonical, 'name' => $pageTitle, 'description' => $pageDescription,
     'inLanguage' => $lang, 'isPartOf' => ['@id' => rtrim(SITE_URL, '/') . '/#website'], 'about' => ['@id' => $pageCanonical . '#bank'], 'breadcrumb' => ['@id' => $pageCanonical . '#breadcrumb']],
    $org,
    array_merge(ld_breadcrumbs($crumbs), ['@id' => $pageCanonical . '#breadcrumb']),
];
if ($f = ld_faq($faq)) $graph[] = $f;
echo ld_script($graph);
require __DIR__ . '/includes/footer.php';
