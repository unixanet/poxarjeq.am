<?php
require_once __DIR__ . '/includes/functions.php';
$lang = detect_lang();
$L = load_lang($lang);

$pageTitle = tv('seo.about.title');
$pageDescription = tv('seo.about.description');
$pagePath = '/about.php';
$active = 'about';
$crumbs = [[t('home'), lurl('/')], [t('menu.about'), null]];

$bankCodes = array_values(array_filter(sm_bank_codes_safe(), fn($c) => bank_is_visible($c) && get_bank_meta($c)));
$branchesN = 0;
foreach ($bankCodes as $bc) $branchesN += count(get_bank_meta($bc)['branches'] ?? []);
$ex = api_v2('exchangers.php', ['lang' => 'hy'], 900);
$pointsN = (int)($ex['summary']['USD']['count'] ?? 0);
$stats = array_filter([
    [count($bankCodes), 'about.stat_banks'],
    [$branchesN, 'about.stat_branches'],
    [$pointsN, 'about.stat_points'],
    [count(table_currency_codes()), 'about.stat_cur'],
], fn($s) => $s[0] > 0);

$does = [
    ['/', 'about.do1', 'M3 10h18M6 10V6m6 4V6m6 4V6M5 20h14M7 20v-6m5 6v-6m5 6v-6'],
    ['/poxanakman-keter', 'about.do2', 'M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4'],
    ['/voski', 'about.do3', 'M12 3l2.5 5.2 5.7.8-4.1 4 1 5.7L12 16l-5.1 2.7 1-5.7-4.1-4 5.7-.8L12 3z'],
    ['/banks', 'about.do4', 'M17.7 8.3a6 6 0 10-11.4 0C6.3 12 12 20 12 20s5.7-8 5.7-11.7zM12 10a1.8 1.8 0 100-3.6A1.8 1.8 0 0012 10z'],
    ['/currency.php?cur=USD', 'about.do5', 'M4 19V5m0 14h16M8 15l3-4 3 2 4-6'],
    ['/news', 'about.do6', 'M4 5h13a2 2 0 012 2v12H6a2 2 0 01-2-2V5zm4 4h7m-7 4h7'],
];
$steps = ['about.step1', 'about.step2', 'about.step3', 'about.step4'];
$trust = ['about.trust1', 'about.trust2', 'about.trust3', 'about.trust4'];

require __DIR__ . '/includes/header.php';
?>
<?= ui_breadcrumbs($crumbs) ?>

<section class="overflow-hidden rounded-[32px] bg-gradient-to-br from-blue-600 to-blue-800 px-6 py-10 text-white shadow-[0_24px_60px_-12px_rgba(37,99,235,.45)] sm:px-12 sm:py-16">
    <p class="text-sm font-bold uppercase tracking-widest text-white"><?= e(t('about.kicker')) ?></p>
    <h1 class="mt-3 max-w-3xl text-[32px] font-black leading-[1.1] tracking-tight text-white sm:text-[52px]"><?= e(t('about.h1')) ?></h1>
    <p class="mt-5 max-w-2xl text-base leading-relaxed text-white sm:text-xl"><?= e(t('about.lead')) ?></p>
    <?php if ($stats): ?>
    <dl class="mt-8 grid grid-cols-2 gap-3 sm:grid-cols-4 sm:gap-4">
        <?php foreach ($stats as [$n, $k]): ?>
        <div class="flex flex-col-reverse rounded-2xl bg-white/15 px-4 py-4">
            <dt class="text-[13px] font-semibold text-white"><?= e(t($k)) ?></dt>
            <dd class="text-[34px] font-black leading-none tabular-nums text-white sm:text-[44px]"><?= e((string)$n) ?></dd>
        </div>
        <?php endforeach; ?>
    </dl>
    <?php endif; ?>
</section>

<section class="mt-12" aria-labelledby="do-h">
    <h2 id="do-h" class="text-2xl font-black tracking-tight text-slate-900 sm:text-3xl"><?= e(t('about.do_title')) ?></h2>
    <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <?php foreach ($does as [$url, $k, $icon]): ?>
        <a href="<?= e(lurl($url)) ?>" title="<?= e(t($k . '_title')) ?>" class="group block rounded-3xl border border-slate-100 bg-white p-6 text-slate-900 no-underline shadow-[0_10px_40px_rgba(15,23,42,.06)] transition hover:-translate-y-0.5 hover:border-blue-300 hover:shadow-[0_14px_44px_rgba(37,99,235,.14)]">
            <span class="grid h-11 w-11 place-items-center rounded-2xl bg-blue-600 text-white" aria-hidden="true"><svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?= e($icon) ?>"/></svg></span>
            <h3 class="mt-4 text-lg font-black text-slate-900 group-hover:text-blue-600"><?= e(t($k . '_title')) ?></h3>
            <p class="mt-2 text-[15px] leading-relaxed"><?= e(t($k . '_text')) ?></p>
        </a>
        <?php endforeach; ?>
    </div>
</section>

<section class="mt-12" aria-labelledby="how-h">
    <h2 id="how-h" class="text-2xl font-black tracking-tight text-slate-900 sm:text-3xl"><?= e(t('about.how_title')) ?></h2>
    <ol class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <?php foreach ($steps as $i => $k): ?>
        <li class="rounded-3xl border border-slate-100 bg-white p-6 shadow-[0_10px_40px_rgba(15,23,42,.06)]">
            <span class="grid h-9 w-9 place-items-center rounded-full bg-blue-600 text-sm font-black text-white" aria-hidden="true"><?= $i + 1 ?></span>
            <h3 class="mt-4 text-lg font-black text-slate-900"><?= e(t($k . '_title')) ?></h3>
            <p class="mt-2 text-[15px] leading-relaxed"><?= e(t($k . '_text')) ?></p>
        </li>
        <?php endforeach; ?>
    </ol>
</section>

<section class="mt-12 rounded-[28px] border border-blue-100 bg-blue-50 p-6 sm:p-10" aria-labelledby="trust-h">
    <h2 id="trust-h" class="text-2xl font-black tracking-tight text-slate-900 sm:text-3xl"><?= e(t('about.trust_title')) ?></h2>
    <div class="mt-6 grid gap-4 sm:grid-cols-2">
        <?php foreach ($trust as $k): ?>
        <div class="rounded-2xl bg-white p-5">
            <h3 class="text-[17px] font-black text-slate-900"><?= e(t($k . '_title')) ?></h3>
            <p class="mt-2 text-[15px] leading-relaxed"><?= e(t($k . '_text')) ?></p>
        </div>
        <?php endforeach; ?>
    </div>
    <p class="mt-5 text-[15px]"><?= ui_link(lurl('/disclaimer.php'), t('menu.disclaimer'), t('menu.disclaimer_title'), 'font-bold text-blue-600 no-underline hover:underline') ?> · <?= ui_link(lurl('/privacy.php'), t('menu.privacy'), t('menu.privacy_title'), 'font-bold text-blue-600 no-underline hover:underline') ?></p>
</section>

<section class="mt-12" aria-labelledby="team-h">
    <div class="rounded-3xl border border-slate-100 bg-white p-6 shadow-[0_10px_40px_rgba(15,23,42,.06)] sm:p-8">
        <h2 id="team-h" class="text-2xl font-black tracking-tight text-slate-900"><?= e(t('about.team_title')) ?></h2>
        <p class="mt-3 text-[15px] leading-relaxed"><?= e(t('about.team_p1')) ?></p>
        <p class="mt-3 text-[15px] leading-relaxed"><?= e(t('about.team_p2')) ?></p>
        <p class="mt-5 flex flex-wrap gap-x-5 gap-y-2 text-sm font-bold">
            <?= ui_link(LEADS_URL, 'Leads.am ↗', 'Leads.am', 'text-blue-600 no-underline hover:underline', ['target' => '_blank', 'rel' => 'noopener']) ?>
            <?= ui_link(PARENT_SITE, 'Poxarjeq.com ↗', t('footer.sister_title'), 'text-blue-600 no-underline hover:underline', ['target' => '_blank', 'rel' => 'noopener']) ?>
            <?= ui_link(lurl('/contact.php'), t('menu.contact'), t('menu.contact_title'), 'text-blue-600 no-underline hover:underline') ?>
        </p>
    </div>
</section>
<?php
echo ld_script([
    ['@type' => 'AboutPage', '@id' => $pageCanonical . '#webpage', 'url' => $pageCanonical, 'name' => $pageTitle, 'description' => $pageDescription, 'inLanguage' => $lang, 'isPartOf' => ['@id' => rtrim(SITE_URL, '/') . '/#website'], 'about' => ['@id' => rtrim(SITE_URL, '/') . '/#organization'], 'breadcrumb' => ['@id' => $pageCanonical . '#breadcrumb']],
    array_merge(ld_breadcrumbs($crumbs), ['@id' => $pageCanonical . '#breadcrumb']),
]);
require __DIR__ . '/includes/footer.php';
