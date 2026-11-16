<?php
require_once __DIR__ . '/includes/functions.php';
$lang = detect_lang();
$L = load_lang($lang);

$d = api_get('currencies.php', [], 900);
$updatedAt = $d['updated_at'] ?? null;
$ex = api_v2('exchangers.php', ['lang' => 'hy'], 900);
$exAt = $ex['data_updated_at'] ?? null;
$vars = ['bank_n' => count(array_filter(sm_bank_codes_safe(), fn($c) => bank_is_visible($c) && get_bank_meta($c))), 'cur_n' => count(table_currency_codes())];
$pageTitle = tv('seo.method.title');
$pageDescription = tv('seo.method.description');
$pagePath = '/methodology.php';
$active = '';
$crumbs = [[t('home'), lurl('/')], [t('menu.method'), null]];
$terms = [1, 2, 3, 4, 5];

require __DIR__ . '/includes/header.php';
?>
<?= ui_breadcrumbs($crumbs) ?>
<h1 class="text-[28px] font-black leading-tight tracking-tight text-slate-900 sm:text-5xl"><?= e(t('method.h1')) ?></h1>
<p class="mt-3 max-w-3xl text-base leading-relaxed sm:text-lg"><?= e(tv('method.lead', $vars)) ?></p>
<div class="mt-4 flex flex-wrap gap-3">
    <?= ui_live($updatedAt) ?>
    <?php if ($exAt): ?><span class="inline-flex items-center rounded-full border border-slate-200 bg-white/80 px-3 py-1.5 text-xs"><?= e(tv('method.ex_update', ['time' => format_datetime($exAt)])) ?></span><?php endif; ?>
</div>

<div class="mt-8 space-y-4">
    <?php foreach ($terms as $i): ?>
    <section class="rounded-3xl border border-slate-100 bg-white p-5 shadow-[0_10px_40px_rgba(15,23,42,.06)] sm:p-7">
        <h2 class="text-lg font-black text-slate-900 sm:text-xl"><?= e(t('method.s' . $i . '_title')) ?></h2>
        <div class="mt-2 space-y-2 text-[15px] leading-relaxed"><?= tp('method.s' . $i . '_text', $vars) ?></div>
    </section>
    <?php endforeach; ?>
</div>

<section class="mt-10" aria-labelledby="gl-h">
    <h2 id="gl-h" class="mb-4 text-xl font-black tracking-tight text-slate-900 sm:text-2xl"><?= e(t('method.gl_title')) ?></h2>
    <dl class="grid gap-3 sm:grid-cols-2">
        <?php foreach (['buy', 'sell', 'spread', 'best'] as $g): ?>
        <div class="rounded-2xl border border-slate-100 bg-white p-4">
            <dt class="font-black text-slate-900"><?= e(t('method.gl_' . $g . '_t')) ?></dt>
            <dd class="mt-1 text-[15px] leading-relaxed"><?= e(t('method.gl_' . $g . '_d')) ?></dd>
        </div>
        <?php endforeach; ?>
    </dl>
</section>

<p class="mt-8 text-[15px]"><?= ui_link(lurl('/about.php'), t('menu.about'), t('menu.about_title'), 'font-bold text-blue-600 no-underline hover:underline') ?> · <?= ui_link(lurl('/disclaimer.php'), t('menu.disclaimer'), t('menu.disclaimer_title'), 'font-bold text-blue-600 no-underline hover:underline') ?> · <?= ui_link(lurl('/contact.php'), t('menu.contact'), t('menu.contact_title'), 'font-bold text-blue-600 no-underline hover:underline') ?></p>
<?php
echo ld_script([
    ['@type' => 'WebPage', '@id' => $pageCanonical . '#webpage', 'url' => $pageCanonical, 'name' => $pageTitle, 'description' => $pageDescription, 'inLanguage' => $lang, 'isPartOf' => ['@id' => rtrim(SITE_URL, '/') . '/#website'], 'breadcrumb' => ['@id' => $pageCanonical . '#breadcrumb'], 'dateModified' => $updatedAt ?: date('c')],
    array_merge(ld_breadcrumbs($crumbs), ['@id' => $pageCanonical . '#breadcrumb']),
]);
require __DIR__ . '/includes/footer.php';
