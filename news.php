<?php
require_once __DIR__ . '/includes/functions.php';
$lang = detect_lang();
$L = load_lang($lang);

$page = max(1, (int)($_GET['page'] ?? 1));
$cat = preg_match('/^[a-z0-9-]{2,30}$/', (string)($_GET['category'] ?? '')) ? (string)$_GET['category'] : '';
$d = news_fetch(12, $page, $cat);
$items = $d['items'] ?? [];
$pages = max(1, (int)($d['pages'] ?? 1));
if ($page > $pages && $items === [] && $d !== null) pxq_not_found();
$cats = $d['categories'] ?? [];

$q = [];
if ($cat !== '') $q['category'] = $cat;
if ($page > 1) $q['page'] = $page;
$pagePath = '/news' . ($q ? '?' . http_build_query($q) : '');
$pageTitle = tv('seo.news.title') . ($page > 1 ? ' — ' . tv('news.page_n', ['n' => $page]) : '');
$pageDescription = tv('seo.news.description');
$pageRobots = $cat !== '' ? 'noindex, follow' : 'index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1';
$active = 'news';
$crumbs = [[t('home'), lurl('/')], [t('menu.news'), null]];
$pageLink = function (int $p) use ($cat) {
    $qq = [];
    if ($cat !== '') $qq['category'] = $cat;
    if ($p > 1) $qq['page'] = $p;
    return lurl('/news' . ($qq ? '?' . http_build_query($qq) : ''));
};

require __DIR__ . '/includes/header.php';
?>
<?= ui_breadcrumbs($crumbs) ?>
<h1 class="text-[28px] font-black leading-tight tracking-tight text-slate-900 sm:text-5xl"><?= e(t('news.h1')) ?></h1>
<p class="mt-3 max-w-3xl text-base leading-relaxed sm:text-lg"><?= e(t('news.lead')) ?><?php if ($lang !== 'hy'): ?> <?= e(t('news.lang_note')) ?><?php endif; ?></p>

<?php if ($cats): ?>
<nav class="mt-5" aria-label="<?= e(t('news.categories')) ?>">
    <div class="-mx-4 flex gap-2 overflow-x-auto px-4 pb-1 [scrollbar-width:none] sm:mx-0 sm:flex-wrap sm:px-0 [&::-webkit-scrollbar]:hidden">
        <?= ui_link(lurl('/news'), t('news.all'), t('news.all_title'), 'flex min-h-[44px] shrink-0 items-center rounded-xl border px-4 text-sm font-bold no-underline ' . ($cat === '' ? 'border-blue-600 bg-blue-600 text-white' : 'border-slate-200 bg-white text-slate-900 hover:border-blue-300'), $cat === '' ? ['aria-current' => 'page'] : []) ?>
        <?php foreach ($cats as $c): $on = $cat === $c['slug']; ?>
        <?= ui_link(lurl('/news?category=' . rawurlencode($c['slug'])), (string)$c['name'], tv('news.cat_title', ['name' => $c['name']]), 'flex min-h-[44px] shrink-0 items-center rounded-xl border px-4 text-sm font-bold no-underline ' . ($on ? 'border-blue-600 bg-blue-600 text-white' : 'border-slate-200 bg-white text-slate-900 hover:border-blue-300'), array_merge(['lang' => 'hy'], $on ? ['aria-current' => 'page'] : [])) ?>
        <?php endforeach; ?>
    </div>
</nav>
<?php endif; ?>

<?php if ($items): ?>
<div class="mt-6 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
    <?php foreach ($items as $n) echo news_card($n); ?>
</div>
<?php if ($pages > 1): ?>
<nav class="mt-8 flex flex-wrap items-center justify-center gap-2" aria-label="<?= e(t('news.pagination')) ?>">
    <?php if ($page > 1): ?><?= ui_link($pageLink($page - 1), '← ' . t('news.prev'), tv('news.page_n', ['n' => $page - 1]), 'inline-flex min-h-[44px] items-center rounded-xl border border-slate-200 bg-white px-4 text-sm font-bold text-slate-900 no-underline hover:border-blue-300', ['rel' => 'prev']) ?><?php endif; ?>
    <span class="px-3 text-sm font-semibold"><?= e($page . ' / ' . $pages) ?></span>
    <?php if ($page < $pages): ?><?= ui_link($pageLink($page + 1), t('news.next') . ' →', tv('news.page_n', ['n' => $page + 1]), 'inline-flex min-h-[44px] items-center rounded-xl border border-slate-200 bg-white px-4 text-sm font-bold text-slate-900 no-underline hover:border-blue-300', ['rel' => 'next']) ?><?php endif; ?>
</nav>
<?php endif; ?>
<?php else: ?>
<div class="mt-6 rounded-3xl border border-slate-100 bg-white p-10 text-center shadow-sm"><?= e(t('no_data')) ?></div>
<?php endif; ?>

<p class="mt-8 text-sm"><?= e(t('news.source')) ?> <?= ui_link(PARENT_SITE . '/news/', 'Poxarjeq.com', t('news.source_title'), 'font-bold text-blue-600 no-underline hover:underline', ['target' => '_blank', 'rel' => 'noopener']) ?></p>
<?php
echo ld_script([
    ['@type' => 'CollectionPage', '@id' => $pageCanonical . '#webpage', 'url' => $pageCanonical, 'name' => $pageTitle, 'description' => $pageDescription, 'inLanguage' => $lang, 'isPartOf' => ['@id' => rtrim(SITE_URL, '/') . '/#website'], 'breadcrumb' => ['@id' => $pageCanonical . '#breadcrumb']],
    array_merge(ld_breadcrumbs($crumbs), ['@id' => $pageCanonical . '#breadcrumb']),
]);
require __DIR__ . '/includes/footer.php';
