<?php
require_once __DIR__ . '/includes/functions.php';
$lang = detect_lang();
$L = load_lang($lang);

$code = strtolower(trim($_GET['code'] ?? ''));
$script = basename($_SERVER['SCRIPT_NAME'] ?? '', '.php');
if ($code === '' && $script !== 'bank' && $script !== 'index') $code = $script;
if ($code === '') pxq_redirect(with_lang('/', $lang));
if (!preg_match('/^[a-z0-9-]{2,64}$/', $code)) pxq_not_found();

$v2 = api_v2('bank.php', ['code' => $code, 'lang' => $lang === 'ru' ? 'ru' : 'hy'], 600);
$v2b = $v2['bank'] ?? null;
$apiBank = get_bank($code);
$meta = get_bank_meta($code);
if ((!$meta && !$apiBank && !$v2b) || !bank_is_visible($code)) pxq_not_found();

$rates = $apiBank['rates'] ?? [];
foreach (($v2b['rates'] ?? []) as $c => $r) {
    if (isset($r['buy'], $r['sell'])) $rates[$c] = array_merge($rates[$c] ?? [], $r);
}
$order = ['USD', 'EUR', 'RUR', 'GEL', 'GBP', 'CHF', 'CNY', 'AED', 'CAD', 'AUD', 'JPY'];
uksort($rates, fn($a, $b) => (array_search($a, $order, true) === false ? 99 : array_search($a, $order, true)) <=> (array_search($b, $order, true) === false ? 99 : array_search($b, $order, true)) ?: strcmp($a, $b));
$updatedAt = $apiBank['updated_at'] ?? ($v2['data_updated_at'] ?? null);
$displayName = bank_display_name($code, $lang, $apiBank['name'] ?? null);
$logoUrl = bank_logo_url($code, $apiBank['logo'] ?? '');
$isPartner = bank_is_partner($code);
$branches = $meta['branches'] ?? [];
$vars = bank_vars($displayName, $lang);
$profile = $v2['profile'] ?? [];

[$ovTitle, $ovDesc] = bank_seo_override($code, $lang);
$pageTitle = $ovTitle !== '' ? $ovTitle : bank_title($displayName, $lang, $meta ? bank_alt_name($meta, $lang) : '');
$pageDescription = $ovDesc !== '' ? $ovDesc : bank_description($displayName, $lang, $rates, count($branches));
$pagePath = bank_page_url($code);
$active = '';

$address = $meta['address'][$lang] ?? '';
$phone = $meta['phone'] ?? ($profile['phone'] ?? '');
$email = $meta['email'] ?? ($profile['email'] ?? '');
$website = $meta['website'] ?? ($profile['website'] ?? '');
$about = $meta['about'][$lang] ?? '';
$aboutDefault = (require __DIR__ . '/includes/bank_data.php')[$code]['about'][$lang] ?? '';
$aboutIsDefault = $about === '' || $about === $aboutDefault;
$hours = trim((string)($profile['working_hours'] ?? ''));
$faq = cms_faq('bank', $vars);
$crumbs = [[t('home'), lurl('/')], [t('banks'), lurl('/banks')], [$displayName, null]];
$factsText = $meta ? bank_facts_text($code, $lang, $meta, $rates, $displayName) : '';
$summary = bank_summary($displayName, $lang, $rates['USD'] ?? ($rates ? reset($rates) : null));
$namesLine = $meta ? bank_names_line($meta, $lang, $code) : '';
$rating = $v2b['rating'] ?? null;

$cityCount = [];
foreach ($branches as $br) {
    $cn0 = $br['city'][$lang] ?? '';
    if ($cn0 !== '') $cityCount[$cn0] = ($cityCount[$cn0] ?? 0) + 1;
}
$citiesText = implode(', ', array_map(fn($n, $k) => $k > 1 ? $n . ' (' . $k . ')' : $n, array_keys($cityCount), $cityCount));
$ctVars = $vars + ['phone' => $phone, 'phone2' => $meta['phone2'] ?? '', 'address' => $address, 'hours' => $hours, 'email' => $email, 'site' => preg_replace('#^https?://#', '', (string)$website), 'n' => count($branches), 'cities' => $citiesText];
$ctParts = [];
foreach ([['phone', 'bank.ct_phone'], ['phone2', 'bank.ct_phone2'], ['address', 'bank.ct_address'], ['hours', 'bank.ct_hours'], ['email', 'bank.ct_email'], ['site', 'bank.ct_site']] as [$field, $tk]) {
    if (trim((string)$ctVars[$field]) !== '') $ctParts[] = cms_text_in($tk, $lang, $ctVars);
}
if ($branches && $citiesText !== '') $ctParts[] = cms_text_in('bank.ct_branches', $lang, $ctVars);
$cities = [];
foreach ($branches as $br) {
    $c = $br['city'][$lang] ?? '';
    if ($c !== '' && !in_array($c, $cities, true)) $cities[] = $c;
}
$chartCur = isset($rates['USD']) ? 'USD' : (array_key_first($rates) ?? 'USD');
$bankHist = $rates ? (hist_table($chartCur, $code, [30])[30] ?? null) : null;
$bankHistText = $bankHist ? cms_text_in('bank.hist_text', $lang, $vars + hist_fmt($bankHist, $lang) + ['cur' => $chartCur]) : '';
$others = array_values(array_filter(top_bank_codes(12), fn($c) => $c !== $code));
$others = array_slice($others, 0, 8);

require __DIR__ . '/includes/header.php';
?>

<?= ui_breadcrumbs($crumbs) ?>

<section class="flex flex-col gap-5 rounded-[28px] border border-slate-100 bg-white p-5 shadow-[0_24px_60px_-12px_rgba(15,23,42,.14)] sm:flex-row sm:items-center sm:p-7">
    <?php if ($logoUrl): ?>
    <?= ui_img($logoUrl, tv('common.logo_alt', $vars), 96, 96, 'h-20 w-20 shrink-0 rounded-2xl border border-slate-100 object-contain p-2 sm:h-24 sm:w-24', ['fetchpriority' => 'high']) ?>
    <?php else: ?>
    <span class="grid h-20 w-20 shrink-0 place-items-center rounded-2xl border border-slate-100 bg-blue-50 text-3xl font-black text-blue-700 sm:h-24 sm:w-24" aria-hidden="true"><?= e(mb_strtoupper(mb_substr($displayName, 0, 1))) ?></span>
    <?php endif; ?>
    <div class="min-w-0 flex-1">
        <h1 class="text-[26px] font-black leading-tight tracking-tight text-slate-900 sm:text-4xl"><?= e(bank_h1($displayName, $lang)) ?></h1>
        <?php if ($namesLine): ?><p class="mt-1 text-sm font-semibold"><?= e($namesLine) ?></p><?php endif; ?>
        <?php if ($meta && (!empty($meta['legal']) || !empty($meta['swift']))): ?>
        <p class="mt-1 text-sm"><?= e(implode(' · ', array_filter([$meta['legal'] ?? '', !empty($meta['swift']) ? 'SWIFT ' . $meta['swift'] : '']))) ?></p>
        <?php endif; ?>
        <div class="mt-3 flex flex-wrap items-center gap-x-4 gap-y-2 text-sm font-semibold">
            <?php if ($isPartner): ?><?= ui_partner_badge() ?><?php endif; ?>
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
        <?= ui_link($website, t('website') . ' ↗', tv('bank.site_title', $vars), 'inline-flex min-h-[46px] items-center justify-center rounded-[14px] border border-slate-200 bg-white px-5 text-sm font-bold text-slate-900 no-underline hover:border-blue-300 hover:text-blue-600', ['target' => '_blank', 'rel' => 'noopener nofollow']) ?>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</section>

<?php if ($summary): ?><p class="mt-5 max-w-3xl text-base leading-relaxed sm:text-lg"><?= e($summary) ?></p><?php endif; ?>

<div class="mt-6 grid items-start gap-6 lg:grid-cols-[1fr_380px]">
    <div class="min-w-0 space-y-8">
        <section aria-labelledby="rates-h">
            <h2 id="rates-h" class="mb-4 text-xl font-black tracking-tight text-slate-900 sm:mb-5 sm:text-2xl"><?= e(tv('bank.rates_title', $vars)) ?></h2>
            <?php if ($rates): ?>
            <?= ui_bank_rates($rates, $displayName, $lang, $code) ?>
            <?php else: ?>
            <div class="rounded-3xl border border-slate-100 bg-white p-10 text-center shadow-sm"><?= e(t('no_data')) ?></div>
            <?php endif; ?>
        </section>

        <?php if ($ctParts): ?>
        <section class="rounded-3xl border border-slate-100 bg-white p-5 shadow-[0_10px_40px_rgba(15,23,42,.06)] sm:p-7" aria-labelledby="contact-h">
            <h2 id="contact-h" class="mb-3 text-xl font-black tracking-tight text-slate-900 sm:text-2xl"><?= e(tv('bank.contact_h', $vars)) ?></h2>
            <p class="text-[15px] leading-relaxed sm:text-base"><?= e(implode(' ', $ctParts)) ?></p>
        </section>
        <?php endif; ?>

        <?php if ($rates): ?>
        <section class="rounded-3xl border border-slate-100 bg-white p-4 shadow-[0_10px_40px_rgba(15,23,42,.07)] sm:p-6" aria-labelledby="chart-h">
            <div class="mb-3 flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 id="chart-h" class="text-lg font-black text-slate-900 sm:text-xl"><?= e(tv('bank.chart_title', $vars)) ?> · <span id="chart-cur"><?= e($chartCur) ?></span></h2>
                    <div class="mt-1 flex items-center gap-4 text-[13px] font-semibold">
                        <span class="flex items-center gap-1.5"><i class="inline-block h-2.5 w-2.5 rounded-full bg-blue-600" aria-hidden="true"></i><?= e(t('buy')) ?></span>
                        <span class="flex items-center gap-1.5"><i class="inline-block h-2.5 w-2.5 rounded-full bg-indigo-500" aria-hidden="true"></i><?= e(t('sell')) ?></span>
                    </div>
                </div>
                <div class="inline-flex rounded-xl bg-slate-100 p-1 text-[13px] font-bold" role="group" aria-label="<?= e(t('cur.chart_title')) ?>">
                    <?php foreach ([7, 30, 90, 365] as $d): ?>
                    <button type="button" data-days="<?= $d ?>" aria-pressed="<?= $d === 30 ? 'true' : 'false' ?>" class="period-btn min-h-[36px] rounded-lg px-3 text-slate-900 sm:px-4 <?= $d === 30 ? 'bg-white shadow' : '' ?>"><?= e(t('cur.period' . $d)) ?></button>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php if (count($rates) > 1): ?>
            <div class="-mx-1 mb-3 flex gap-2 overflow-x-auto px-1 pb-1 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden" role="group" aria-label="<?= e(t('currency')) ?>">
                <?php foreach (array_keys($rates) as $c): $on = $c === $chartCur; ?>
                <button type="button" data-cur="<?= e($c) ?>" aria-pressed="<?= $on ? 'true' : 'false' ?>" title="<?= e(currency_name($c, $lang)) ?>" class="cur-btn flex min-h-[38px] shrink-0 items-center gap-1.5 rounded-xl border px-3 text-[13px] font-bold <?= $on ? 'border-blue-600 bg-blue-600 text-white' : 'border-slate-200 bg-white text-slate-900' ?>"><?= ui_flag($c, 18, 'h-[18px] w-[18px] rounded-full object-cover') ?><?= e($c) ?></button>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            <?php if ($bankHistText): ?><p id="bank-hist" class="mb-3 text-[15px] leading-relaxed"><?= e($bankHistText) ?></p><?php endif; ?>
            <div id="rate-chart" class="relative h-[240px] w-full sm:h-[320px]" role="img" aria-label="<?= e(tv('bank.chart_title', $vars)) ?>"></div>
            <p id="chart-empty" class="hidden py-16 text-center"><?= e(t('cur.no_history')) ?></p>
            <dl id="chart-stats" class="mt-3 grid grid-cols-3 gap-2 border-t border-slate-100 pt-3 text-sm">
                <div><dt class="text-[10px] font-bold uppercase tracking-wider"><?= e(t('cur.stat_min')) ?></dt><dd id="chart-stats-min" class="font-extrabold tabular-nums text-slate-900">—</dd></div>
                <div><dt class="text-[10px] font-bold uppercase tracking-wider"><?= e(t('cur.stat_max')) ?></dt><dd id="chart-stats-max" class="font-extrabold tabular-nums text-slate-900">—</dd></div>
                <div><dt class="text-[10px] font-bold uppercase tracking-wider"><?= e(t('cur.stat_change')) ?></dt><dd id="chart-stats-chg" class="font-extrabold tabular-nums text-slate-900">—</dd></div>
            </dl>
        </section>
        <?php endif; ?>

        <?php if ($branches): ?>
        <section id="branches" aria-labelledby="br-h" class="scroll-mt-24">
            <h2 id="br-h" class="mb-4 text-xl font-black tracking-tight text-slate-900 sm:mb-5 sm:text-2xl"><?= e(tv('bank.branches_h', $vars)) ?></h2>
            <?php if (count($cities) > 1): ?>
            <div id="city-chips" class="-mx-4 mb-4 flex gap-2 overflow-x-auto px-4 [scrollbar-width:none] sm:mx-0 sm:flex-wrap sm:px-0 [&::-webkit-scrollbar]:hidden" role="group" aria-label="<?= e(t('bank.branches_title')) ?>">
                <button type="button" data-city="" aria-pressed="true" class="city-chip min-h-[40px] shrink-0 rounded-xl border border-blue-600 bg-blue-600 px-4 text-sm font-bold text-white"><?= e(t('bank.all_cities')) ?></button>
                <?php foreach ($cities as $c): ?>
                <button type="button" data-city="<?= e($c) ?>" aria-pressed="false" class="city-chip min-h-[40px] shrink-0 rounded-xl border border-slate-200 bg-white px-4 text-sm font-bold text-slate-900"><?= e($c) ?></button>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                <?php foreach ($branches as $br):
                    $slug = $br['slug'] ?? '';
                    $bn = $br['name'][$lang] ?? ''; $bc = $br['city'][$lang] ?? ''; $ba = $br['address'][$lang] ?? ''; $bp = $br['phone'] ?? '';
                    $href = $slug ? lurl(branch_page_url($code, $slug)) : '#';
                ?>
                <a href="<?= e($href) ?>" data-city="<?= e($bc) ?>" title="<?= e(tv('bank.branch_title', ['bank' => $displayName, 'branch' => $bn, 'city' => $bc])) ?>" class="branch-card block rounded-2xl border border-slate-100 bg-white p-4 text-slate-900 no-underline shadow-[0_6px_24px_rgba(15,23,42,.05)] transition hover:border-blue-300 hover:shadow-[0_10px_30px_rgba(37,99,235,.12)]">
                    <span class="block font-bold text-slate-900"><?= e($bn) ?></span>
                    <span class="mt-0.5 block text-sm font-semibold text-blue-600"><?= e($bc) ?></span>
                    <?php if ($ba): ?><span class="mt-2 block text-sm"><?= e($ba) ?></span><?php endif; ?>
                    <?php if ($bp): ?><span class="block text-sm font-semibold tabular-nums"><?= e($bp) ?></span><?php endif; ?>
                </a>
                <?php endforeach; ?>
            </div>
            <?php $cityIdx = cities_index(); $bankCityLinks = []; foreach ($branches as $br0) { $cs0 = city_slug_from((string)($br0['city']['en'] ?? '')); if ($cs0 !== '' && isset($cityIdx[$cs0])) $bankCityLinks[$cs0] = $br0['city'][$lang] ?? ''; } if ($bankCityLinks): ?>
            <div class="mt-6">
                <h3 class="mb-2 text-base font-black text-slate-900"><?= e(t('bank.city_links_h')) ?></h3>
                <ul class="flex flex-wrap gap-2">
                    <?php foreach ($bankCityLinks as $cs0 => $cn0): ?>
                    <li><?= ui_link(lurl(city_page_url($cs0)), $cn0, cms_text_in('city.link_title', $lang, ['city' => $cn0, 'city_loc' => city_loc($cn0, $lang)]), 'flex min-h-[40px] items-center rounded-xl border border-slate-200 bg-white px-3 text-[13px] font-bold text-slate-900 no-underline hover:border-blue-300 hover:text-blue-600') ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>
        </section>
        <?php endif; ?>
    </div>

    <aside class="space-y-5 lg:sticky lg:top-24">
        <?php if ($meta || $profile): ?>
        <section class="rounded-3xl border border-slate-100 bg-white p-5 shadow-[0_10px_40px_rgba(15,23,42,.06)] sm:p-6" aria-labelledby="ct-h">
            <h2 id="ct-h" class="mb-4 text-lg font-black text-slate-900"><?= e(t('contacts')) ?></h2>
            <dl class="space-y-4 text-[15px]">
                <?php if ($address): ?><div><dt class="text-[11px] font-bold uppercase tracking-wider"><?= e(t('address')) ?></dt><dd class="mt-0.5 font-semibold"><?= e($address) ?></dd></div><?php endif; ?>
                <?php if ($phone): ?><div><dt class="text-[11px] font-bold uppercase tracking-wider"><?= e(t('phone')) ?></dt><dd class="mt-0.5 font-semibold"><?= ui_link('tel:' . preg_replace('/\s+/', '', $phone), $phone, t('phone') . ': ' . $phone, 'text-blue-600 no-underline hover:underline') ?></dd></div><?php endif; ?>
                <?php if (!empty($meta['phone2'])): ?><div><dt class="text-[11px] font-bold uppercase tracking-wider"><?= e(t('phone')) ?> 2</dt><dd class="mt-0.5 font-semibold"><?= ui_link('tel:' . preg_replace('/\s+/', '', $meta['phone2']), $meta['phone2'], t('phone') . ': ' . $meta['phone2'], 'text-blue-600 no-underline hover:underline') ?></dd></div><?php endif; ?>
                <?php if ($hours): ?><div><dt class="text-[11px] font-bold uppercase tracking-wider"><?= e(t('bank.hours')) ?></dt><dd class="mt-0.5 font-semibold"><?= e($hours) ?></dd></div><?php endif; ?>
                <?php if ($email): ?><div><dt class="text-[11px] font-bold uppercase tracking-wider"><?= e(t('email')) ?></dt><dd class="mt-0.5 break-all font-semibold"><?= ui_link('mailto:' . $email, $email, t('email') . ': ' . $email, 'text-blue-600 no-underline hover:underline') ?></dd></div><?php endif; ?>
                <?php if ($website): ?><div><dt class="text-[11px] font-bold uppercase tracking-wider"><?= e(t('website')) ?></dt><dd class="mt-0.5 break-all font-semibold"><?= ui_link($website, preg_replace('#https?://#', '', $website), tv('bank.site_title', $vars), 'text-blue-600 no-underline hover:underline', ['target' => '_blank', 'rel' => 'noopener nofollow']) ?></dd></div><?php endif; ?>
            </dl>
        </section>
        <?php $aboutShow = $aboutIsDefault ? $factsText : $about; if ($aboutShow !== ''): ?>
        <section class="rounded-3xl border border-blue-100 bg-blue-50 p-5 sm:p-6" aria-labelledby="ab-h">
            <h2 id="ab-h" class="mb-3 text-lg font-black text-slate-900"><?= e(tv('bank.about_title_facts', $vars)) ?></h2>
            <p class="text-[15px] leading-relaxed"><?= e($aboutShow) ?></p>
        </section>
        <?php endif; ?>
        <?php endif; ?>
        <?php if ($rating && !empty($rating['count'])): ?>
        <section class="rounded-3xl border border-slate-100 bg-white p-5 shadow-[0_10px_40px_rgba(15,23,42,.06)]">
            <p class="text-sm font-semibold"><?= e(tv('bank.rating_line', ['avg' => number_format((float)$rating['avg'], 1), 'count' => (int)$rating['count']])) ?></p>
            <p class="mt-2"><?= ui_link(PARENT_SITE . '/banks/' . rawurlencode($code) . '.php', t('bank.rating_link'), tv('bank.rating_link_title', $vars), 'text-sm font-bold text-blue-600 no-underline hover:underline', ['target' => '_blank', 'rel' => 'noopener']) ?></p>
        </section>
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

<?php if ($others): ?>
<nav class="mt-10" aria-label="<?= e(t('bank.others_title')) ?>">
    <h2 class="mb-3 text-lg font-black text-slate-900 sm:text-xl"><?= e(t('bank.others_title')) ?></h2>
    <ul class="flex flex-wrap gap-2">
        <?php foreach ($others as $oc): $on = bank_display_name($oc, $lang); ?>
        <li><?= ui_link(lurl(bank_page_url($oc)), $on, tv('common.link_bank', ['bank' => $on]), 'flex min-h-[44px] items-center rounded-xl border border-slate-200 bg-white px-4 text-sm font-bold text-slate-900 no-underline hover:border-blue-300 hover:text-blue-600') ?></li>
        <?php endforeach; ?>
    </ul>
</nav>
<?php endif; ?>

<div class="mt-8 rounded-2xl border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-black"><?= e(t('disclaimer_short')) ?></div>

<script src="/assets/js/uplot.min.js"></script>
<script src="/assets/js/pxq-chart.js"></script>
<script>
(function () {
    var chart = PxqChart({ el: 'rate-chart', empty: 'chart-empty', stats: 'chart-stats', buttons: '.period-btn', cur: <?= json_encode($chartCur) ?>, bank: <?= json_encode($code) ?>, days: 30, t: <?= json_encode(['buy' => t('buy'), 'sell' => t('sell')], JSON_UNESCAPED_UNICODE) ?> });
    var curBtns = document.querySelectorAll('.cur-btn'), label = document.getElementById('chart-cur');
    curBtns.forEach(function (b) {
        b.addEventListener('click', function () {
            curBtns.forEach(function (x) {
                var on = x === b;
                x.setAttribute('aria-pressed', on ? 'true' : 'false');
                x.classList.toggle('bg-blue-600', on); x.classList.toggle('border-blue-600', on); x.classList.toggle('text-white', on);
                x.classList.toggle('bg-white', !on); x.classList.toggle('border-slate-200', !on); x.classList.toggle('text-slate-900', !on);
            });
            var c = b.getAttribute('data-cur');
            label.textContent = c;
            chart.setCur(c);
        });
    });
    var chips = document.querySelectorAll('.city-chip'), cards = document.querySelectorAll('.branch-card');
    chips.forEach(function (ch) {
        ch.addEventListener('click', function () {
            var city = ch.getAttribute('data-city');
            chips.forEach(function (x) {
                var on = x === ch;
                x.setAttribute('aria-pressed', on ? 'true' : 'false');
                x.classList.toggle('bg-blue-600', on); x.classList.toggle('border-blue-600', on); x.classList.toggle('text-white', on);
                x.classList.toggle('bg-white', !on); x.classList.toggle('border-slate-200', !on); x.classList.toggle('text-slate-900', !on);
            });
            cards.forEach(function (c) { c.classList.toggle('hidden', !!city && c.getAttribute('data-city') !== city); });
        });
    });
})();
</script>

<?php
$org = ['@type' => 'BankOrCreditUnion', '@id' => $pageCanonical . '#bank', 'name' => $displayName, 'url' => $website ?: $pageCanonical,
        'description' => ($aboutIsDefault ? $factsText : $about) ?: $pageDescription, 'areaServed' => 'AM'];
if ($logoUrl) $org['logo'] = str_starts_with($logoUrl, 'http') ? $logoUrl : rtrim(SITE_URL, '/') . $logoUrl;
if ($phone) $org['telephone'] = $phone;
if ($email) $org['email'] = $email;
if (!empty($meta['legal'])) $org['legalName'] = $meta['legal'];
if ($address) $org['address'] = ['@type' => 'PostalAddress', 'streetAddress' => $address, 'addressCountry' => 'AM'];
if ($hours) $org['openingHours'] = $hours;
$graph = [
    ['@type' => 'WebPage', '@id' => $pageCanonical . '#webpage', 'url' => $pageCanonical, 'name' => $pageTitle, 'description' => $pageDescription,
     'inLanguage' => $lang, 'isPartOf' => ['@id' => rtrim(SITE_URL, '/') . '/#website'], 'about' => ['@id' => $pageCanonical . '#bank'], 'breadcrumb' => ['@id' => $pageCanonical . '#breadcrumb'], 'dateModified' => $updatedAt ?: date('c')],
    $org,
    array_merge(ld_breadcrumbs($crumbs), ['@id' => $pageCanonical . '#breadcrumb']),
];
if ($f = ld_faq($faq)) $graph[] = $f;
echo ld_script($graph);
require __DIR__ . '/includes/footer.php';
