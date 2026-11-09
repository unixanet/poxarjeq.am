<?php
require_once __DIR__ . '/includes/functions.php';
$lang = detect_lang();
$L = load_lang($lang);
[$pageTitle, $pageDescription] = seo_meta('privacy');
$pageCanonical = SITE_URL . '/privacy.php?lang=' . $lang;
$active = '';
$crumbs = [[t('home'), '/'], [t('privacy'), null]];
require __DIR__ . '/includes/header.php';
?>
<?= ui_breadcrumbs($crumbs) ?>
<h1 class="mb-6 text-3xl font-black tracking-tight text-slate-900 sm:text-4xl"><?= e(t('privacy')) ?></h1>
<div class="rounded-3xl border border-slate-100 bg-white p-5 shadow-[0_10px_40px_rgba(15,23,42,.06)] sm:p-8 space-y-4 leading-relaxed">
    <?= tp('privacy.p1') ?>
    <p><?= e(t('privacy.contact_label')) ?><?= ui_link('mailto:' . CONTACT_EMAIL, CONTACT_EMAIL, t('email') . ': ' . CONTACT_EMAIL, 'no-underline hover:underline') ?></p>
</div>
<?php
echo ld_script([['@type' => 'WebPage', '@id' => $pageCanonical . '#webpage', 'url' => $pageCanonical, 'name' => $pageTitle, 'description' => $pageDescription, 'inLanguage' => $lang,
    'isPartOf' => ['@id' => rtrim(SITE_URL, '/') . '/#website'], 'breadcrumb' => ['@id' => $pageCanonical . '#breadcrumb']], array_merge(ld_breadcrumbs($crumbs), ['@id' => $pageCanonical . '#breadcrumb'])]);
require __DIR__ . '/includes/footer.php';
