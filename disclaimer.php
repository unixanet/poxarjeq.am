<?php
require_once __DIR__ . '/includes/functions.php';
$lang = detect_lang();
$L = load_lang($lang);
[$pageTitle, $pageDescription] = seo_meta('disclaimer');
$pagePath = '/disclaimer.php';
$active = '';
$crumbs = [[t('home'), lurl('/')], [t('menu.disclaimer'), null]];
require __DIR__ . '/includes/header.php';
?>
<?= ui_breadcrumbs($crumbs) ?>
<h1 class="text-[28px] font-black leading-tight tracking-tight text-slate-900 sm:text-5xl"><?= e(t('disclaimer.h1')) ?></h1>
<p class="mt-3 max-w-3xl text-base leading-relaxed sm:text-lg"><?= e(t('disclaimer.lead')) ?></p>
<div class="mt-8 space-y-4">
    <?php for ($i = 1; $i <= 5; $i++): ?>
    <section class="rounded-3xl border border-slate-100 bg-white p-5 shadow-[0_10px_40px_rgba(15,23,42,.06)] sm:p-7">
        <h2 class="text-lg font-black text-slate-900 sm:text-xl"><?= e(t('disclaimer.s' . $i . '_title')) ?></h2>
        <div class="mt-2 space-y-2 text-[15px] leading-relaxed"><?= tp('disclaimer.s' . $i . '_text') ?></div>
    </section>
    <?php endfor; ?>
    <p class="text-[15px]"><?= e(t('disclaimer.contact_label')) ?> <?= ui_link('mailto:' . CONTACT_EMAIL, CONTACT_EMAIL, t('email') . ': ' . CONTACT_EMAIL, 'font-bold text-blue-600 no-underline hover:underline') ?></p>
</div>
<?php
echo ld_script([['@type' => 'WebPage', '@id' => $pageCanonical . '#webpage', 'url' => $pageCanonical, 'name' => $pageTitle, 'description' => $pageDescription, 'inLanguage' => $lang,
    'isPartOf' => ['@id' => rtrim(SITE_URL, '/') . '/#website'], 'breadcrumb' => ['@id' => $pageCanonical . '#breadcrumb']], array_merge(ld_breadcrumbs($crumbs), ['@id' => $pageCanonical . '#breadcrumb'])]);
require __DIR__ . '/includes/footer.php';
