<?php
require_once __DIR__ . '/includes/functions.php';
$lang = detect_lang();
$L = load_lang($lang);
[$pageTitle, $pageDescription] = seo_meta('about');
$pageCanonical = SITE_URL . '/about.php?lang=' . $lang;
$active = 'about';
$crumbs = [[t('home'), '/'], [t('about'), null]];
require __DIR__ . '/includes/header.php';
?>
<?= ui_breadcrumbs($crumbs) ?>
<h1 class="mb-6 text-3xl font-black tracking-tight text-slate-900 sm:text-4xl"><?= e(t('about')) ?></h1>
<div class="space-y-6">
    <section class="rounded-3xl border border-slate-100 bg-white p-5 shadow-[0_10px_40px_rgba(15,23,42,.06)] sm:p-8">
        <h2 class="mb-3 text-xl font-black text-slate-900"><?= e(t('site_name')) ?></h2>
        <div class="space-y-3 leading-relaxed"><p><?= e(t('footer_text')) ?></p><p><?= e(t('hero_sub')) ?></p></div>
    </section>
    <section class="rounded-3xl border border-blue-100 bg-blue-50 p-5 sm:p-8">
        <h2 class="mb-4 text-xl font-black text-slate-900"><?= e(t('about.eeat_title')) ?></h2>
        <ul class="grid gap-3 text-[15px] leading-relaxed sm:grid-cols-2">
            <?php foreach (['exp', 'expert', 'auth', 'trust'] as $k): ?>
            <li class="rounded-2xl bg-white p-4"><strong class="block"><?= e(t("about.{$k}_label")) ?></strong><?= e(t("about.{$k}_text")) ?></li>
            <?php endforeach; ?>
        </ul>
    </section>
</div>
<?php
echo ld_script([['@type' => 'WebPage', '@id' => $pageCanonical . '#webpage', 'url' => $pageCanonical, 'name' => $pageTitle, 'description' => $pageDescription, 'inLanguage' => $lang,
    'isPartOf' => ['@id' => rtrim(SITE_URL, '/') . '/#website'], 'breadcrumb' => ['@id' => $pageCanonical . '#breadcrumb']], array_merge(ld_breadcrumbs($crumbs), ['@id' => $pageCanonical . '#breadcrumb'])]);
require __DIR__ . '/includes/footer.php';
