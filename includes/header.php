<?php
if (!isset($pageTitle)) $pageTitle = t('site_name') . ' — ' . t('site_tagline');
if (!isset($pageDescription)) $pageDescription = t('hero_sub');
if (!isset($active)) $active = '';
if (!isset($pagePath)) {
    $pagePath = isset($pageCanonical) ? (preg_replace('~^https?://[^/]+~', '', preg_replace('/([?&])lang=[a-z]{2}(&|$)/', '$1', (string)$pageCanonical)) ?: '/') : '/';
    $pagePath = rtrim($pagePath, '?&') ?: '/';
}
$_urls = page_urls($pagePath);
$pageCanonical = $_urls[$lang];
$pageRobots = $pageRobots ?? 'index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1';
$_indexable = str_starts_with($pageRobots, 'index');
$ogLocale = $lang === 'hy' ? 'hy_AM' : ($lang === 'ru' ? 'ru_RU' : 'en_US');
$_hNav = cms_nav('header');
$_hLangs = ['hy' => 'Հայերեն', 'ru' => 'Русский', 'en' => 'English'];
$_tgUrl = t('tg.url');
$_ico = fn(string $d, string $c = 'h-4 w-4') => '<svg class="' . $c . '" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.4" d="' . $d . '"/></svg>';
$_chev = $_ico('M19 9l-7 7-7-7', 'h-3.5 w-3.5 shrink-0');
$_tgIcon = '<svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M21.9 4.6a1.2 1.2 0 0 0-1.3-.2L2.8 11.3a1.2 1.2 0 0 0 .1 2.2l4.4 1.6 1.7 5.3a1 1 0 0 0 1.7.4l2.5-2.5 4.5 3.3a1.2 1.2 0 0 0 1.9-.7l3-15.1a1.2 1.2 0 0 0-.7-1.2zM9.6 14.4l8.5-5.3-6.7 6.4-.3 2.9z"/></svg>';
$_dropCodes = array_values(array_unique(array_merge(['USD', 'EUR', 'RUR', 'GEL', 'GBP', 'CHF', 'CNY', 'AED'], all_currency_codes())));
?>
<!DOCTYPE html>
<html lang="<?= e($lang) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="<?= e($pageRobots) ?>">
    <meta name="theme-color" content="#2563eb">
    <meta name="author" content="<?= e(t('site_name')) ?>">
    <title><?= e($pageTitle) ?></title>
    <meta name="description" content="<?= e($pageDescription) ?>">
    <link rel="canonical" href="<?= e($pageCanonical) ?>">
    <?php if ($_indexable): foreach (SUPPORTED_LANGS as $hl): ?>
    <link rel="alternate" hreflang="<?= e($hl) ?>" href="<?= e($_urls[$hl]) ?>">
    <?php endforeach; ?>
    <link rel="alternate" hreflang="x-default" href="<?= e($_urls[DEFAULT_LANG]) ?>">
    <?php endif; ?>
    <meta property="og:locale" content="<?= e($ogLocale) ?>">
    <meta property="og:type" content="website">
    <meta property="og:title" content="<?= e($pageTitle) ?>">
    <meta property="og:description" content="<?= e($pageDescription) ?>">
    <meta property="og:url" content="<?= e($pageCanonical) ?>">
    <meta property="og:site_name" content="<?= e(t('site_name')) ?>">
    <meta property="og:image" content="<?= e(SITE_URL) ?>/assets/og.png">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= e($pageTitle) ?>">
    <meta name="twitter:description" content="<?= e($pageDescription) ?>">
    <meta name="twitter:image" content="<?= e(SITE_URL) ?>/assets/og.png">
    <link rel="icon" href="/favicon.ico">
<?php require __DIR__ . '/tw.php'; ?>
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-EN397C6CTH"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        gtag('config', 'G-EN397C6CTH');
    </script>
</head>
<body class="min-h-screen flex flex-col bg-[#fcfcfd] text-black antialiased font-sans">
<a href="#main" title="<?= e(t('skip')) ?>" class="sr-only focus:not-sr-only focus:fixed focus:left-3 focus:top-3 focus:z-[60] focus:rounded-xl focus:bg-blue-600 focus:px-4 focus:py-2 focus:font-bold focus:text-white"><?= e(t('skip')) ?></a>
<header class="sticky top-0 z-50 border-b border-[#eaedf2] bg-white/95 backdrop-blur">
    <div class="mx-auto flex h-[60px] max-w-7xl items-center px-4 sm:h-[68px] sm:px-7">
        <a href="<?= e(lurl('/')) ?>" title="<?= e(t('site_name')) ?> — <?= e(t('today_rates')) ?>" class="mr-3 flex min-w-0 shrink items-center gap-2 text-slate-900 no-underline sm:gap-2.5 lg:mr-8">
            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-[10px] bg-blue-600 text-[19px] font-extrabold text-white shadow-[0_4px_12px_rgba(37,99,235,.3)]" aria-hidden="true">֏</span>
            <span class="truncate text-[15px] font-black leading-tight tracking-tight sm:text-[18px]">Poxarjeq<span class="text-blue-600">.am</span></span>
        </a>

        <nav id="site-nav" class="absolute inset-x-0 top-full hidden max-h-[calc(100vh-60px)] flex-col gap-1 overflow-y-auto border-t border-[#eaedf2] bg-white px-4 py-3 shadow-[0_16px_32px_rgba(15,23,42,.08)] lg:static lg:inset-auto lg:flex lg:h-full lg:max-h-none lg:flex-1 lg:flex-row lg:items-center lg:gap-0 lg:overflow-visible lg:border-0 lg:bg-transparent lg:p-0 lg:shadow-none" aria-label="<?= e(t('nav_menu')) ?>">
            <?php foreach ($_hNav as $_hn):
                $_hu = $_hn['url'];
                $_hOn = nav_is_active($_hu);
                $_hLabel = nav_label($_hn, $lang);
                $_hBar = 'lg:after:absolute lg:after:bottom-0 lg:after:left-3 lg:after:right-3 lg:after:h-[2.5px] lg:after:rounded-t lg:after:bg-blue-600 lg:after:transition-transform ';
                if ($_hu === '#currencies' || $_hu === '#banks'): ?>
            <div class="group relative lg:flex lg:h-full lg:items-center">
                <button type="button" data-drop class="relative flex min-h-[48px] w-full items-center justify-between gap-1 rounded-xl px-4 text-[15px] font-semibold text-slate-900 hover:bg-slate-50 hover:text-blue-600 lg:h-full lg:min-h-0 lg:w-auto lg:justify-start lg:rounded-none lg:px-3 lg:text-[14px] lg:hover:bg-transparent lg:group-hover:text-blue-600 lg:group-focus-within:text-blue-600 <?= $_hBar ?>lg:after:scale-x-0 lg:group-hover:after:scale-x-100" aria-haspopup="true" aria-expanded="false" title="<?= e(nav_title($_hn, $lang)) ?>"><?= e($_hLabel) ?><?= $_chev ?></button>
                <div data-drop-panel class="hidden grid-cols-2 gap-1 px-1 pb-2 pt-1 lg:invisible lg:absolute lg:left-0 lg:top-full lg:z-50 lg:grid lg:w-72 lg:translate-y-1 lg:grid-cols-1 lg:rounded-2xl lg:border lg:border-[#eaedf2] lg:bg-white lg:p-2 lg:opacity-0 lg:shadow-[0_16px_40px_rgba(15,23,42,.12)] lg:transition lg:group-hover:visible lg:group-hover:translate-y-0 lg:group-hover:opacity-100 lg:group-focus-within:visible lg:group-focus-within:translate-y-0 lg:group-focus-within:opacity-100">
                    <?php if ($_hu === '#currencies'): foreach ($_dropCodes as $_dc): ?>
                    <?= ui_link(lurl('/currency.php?cur=' . $_dc), ui_flag($_dc, 22, 'h-[22px] w-[22px] rounded-full object-cover') . '<span class="truncate">' . e(cur_menu_label($_dc, $lang)) . '</span>', tv('common.link_currency', ['name' => currency_name($_dc, $lang), 'code' => $_dc]), 'flex min-h-[44px] items-center gap-2 rounded-xl bg-slate-50 px-3 text-[13px] font-semibold text-slate-900 no-underline hover:bg-blue-600 hover:text-white lg:gap-3 lg:bg-transparent lg:py-2 lg:text-[14px]', [], true) ?>
                    <?php endforeach; else: foreach (top_bank_codes(10) as $_bc): $_bn = bank_display_name($_bc, $lang); ?>
                    <?= ui_link(lurl(bank_page_url($_bc)), e($_bn), tv('common.link_bank', ['bank' => $_bn]), 'flex min-h-[44px] items-center truncate rounded-xl bg-slate-50 px-3 text-[13px] font-semibold text-slate-900 no-underline hover:bg-blue-600 hover:text-white lg:bg-transparent lg:py-2 lg:text-[14px]', [], true) ?>
                    <?php endforeach; ?>
                    <?= ui_link(lurl('/banks'), t('nav.banks_all') . ' →', t('menu.all_banks_title'), 'col-span-2 flex min-h-[44px] items-center justify-center rounded-xl bg-blue-600 px-3 text-[14px] font-bold text-white no-underline hover:bg-blue-700 lg:col-span-1 lg:mt-1 lg:justify-start lg:bg-transparent lg:text-blue-600 lg:hover:bg-blue-600 lg:hover:text-white') ?>
                    <?php endif; ?>
                </div>
            </div>
            <?php else: ?>
            <?= ui_link(lurl($_hu), $_hLabel, nav_title($_hn, $lang),
                'relative flex min-h-[48px] items-center rounded-xl px-4 text-[15px] font-semibold no-underline lg:h-full lg:min-h-0 lg:rounded-none lg:px-3 lg:text-[14px] ' . ($_hOn ? 'bg-blue-600 text-white lg:bg-transparent lg:text-blue-600 ' : 'text-slate-900 hover:bg-slate-50 hover:text-blue-600 lg:hover:bg-transparent ') . $_hBar . ($_hOn ? 'lg:after:scale-x-100' : 'lg:after:scale-x-0 lg:hover:after:scale-x-100'),
                array_filter(['aria-current' => $_hOn ? 'page' : null, 'target' => $_hn['new_tab'] ? '_blank' : null, 'rel' => trim(($_hn['new_tab'] ? 'noopener ' : '') . ($_hn['nofollow'] ? 'nofollow' : '')) ?: null])) ?>
            <?php endif; endforeach; ?>
            <?= ui_link($_tgUrl, $_tgIcon . '<span>' . e(t('tg.btn')) . ' · Telegram</span>', t('tg.title_attr'), 'mt-2 flex min-h-[48px] items-center justify-center gap-2 rounded-xl bg-blue-600 px-4 text-[15px] font-bold text-white no-underline hover:bg-blue-700 lg:hidden', ['target' => '_blank', 'rel' => 'noopener'], true) ?>
        </nav>

        <div class="ml-auto flex shrink-0 items-center gap-2">
            <?= ui_link($_tgUrl, $_tgIcon . '<span>Telegram</span>', t('tg.title_attr'), 'hidden items-center gap-2 rounded-[10px] bg-blue-600 px-3.5 py-2 text-[13px] font-bold text-white no-underline shadow-[0_4px_12px_rgba(37,99,235,.25)] hover:bg-blue-700 xl:inline-flex', ['target' => '_blank', 'rel' => 'noopener'], true) ?>
            <details id="lang-menu" class="group relative">
                <summary class="flex min-h-[40px] cursor-pointer list-none items-center gap-1.5 rounded-[10px] border border-[#eaedf2] bg-white px-3 text-[13px] font-extrabold uppercase tracking-wide text-slate-900 hover:border-blue-300 [&::-webkit-details-marker]:hidden" aria-label="<?= e(t('nav.language')) ?>: <?= e($_hLangs[$lang]) ?>" title="<?= e(t('nav.language')) ?>">
                    <?= $_ico('M3.6 9h16.8M3.6 15h16.8M12 3a15 15 0 010 18M12 3a15 15 0 000 18M21 12a9 9 0 11-18 0 9 9 0 0118 0z', 'h-4 w-4 shrink-0') ?><?= e($lang) ?><?= $_chev ?>
                </summary>
                <div class="absolute right-0 top-full z-50 mt-2 w-44 rounded-2xl border border-[#eaedf2] bg-white p-1.5 shadow-[0_16px_40px_rgba(15,23,42,.14)]">
                    <?php foreach ($_hLangs as $_hCode => $_hName): $_hCur = ($_hCode === $lang); ?>
                    <a href="<?= e(lang_url($_hCode)) ?>" hreflang="<?= e($_hCode) ?>" lang="<?= e($_hCode) ?>" title="<?= e($_hName) ?>" class="flex items-center justify-between rounded-xl px-3 py-2.5 text-[14px] font-bold no-underline <?= $_hCur ? 'bg-blue-600 text-white' : 'text-slate-900 hover:bg-blue-50 hover:text-blue-600' ?>" <?= $_hCur ? 'aria-current="true"' : '' ?>><span><?= e($_hName) ?></span><span class="text-[11px] font-black uppercase"><?= e($_hCode) ?></span></a>
                    <?php endforeach; ?>
                </div>
            </details>
            <button type="button" id="burger" class="grid h-10 w-10 place-items-center rounded-xl border border-[#eaedf2] bg-white text-slate-900 lg:hidden" aria-label="<?= e(t('menu')) ?>" title="<?= e(t('menu')) ?>" aria-expanded="false" aria-controls="site-nav">
                <svg id="icon-open" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M4 7h16M4 12h16M4 17h16"/></svg>
                <svg id="icon-close" class="hidden h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
    </div>
</header>
<script>
(function () {
    var b = document.getElementById('burger'), nav = document.getElementById('site-nav'), o = document.getElementById('icon-open'), c = document.getElementById('icon-close'), l = document.getElementById('lang-menu');
    function setNav(open) {
        nav.classList.toggle('hidden', !open);
        nav.classList.toggle('flex', open);
        o.classList.toggle('hidden', open);
        c.classList.toggle('hidden', !open);
        b.setAttribute('aria-expanded', open ? 'true' : 'false');
    }
    if (b) b.addEventListener('click', function () { setNav(nav.classList.contains('hidden')); });
    nav.querySelectorAll('[data-drop]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var panel = btn.parentNode.querySelector('[data-drop-panel]');
            var open = panel.classList.toggle('hidden') === false;
            panel.classList.toggle('grid', open);
            btn.setAttribute('aria-expanded', open ? 'true' : 'false');
        });
    });
    document.addEventListener('click', function (e) { if (l && l.open && !l.contains(e.target)) l.open = false; });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') { if (l) l.open = false; if (b && !nav.classList.contains('hidden') && window.innerWidth < 1024) setNav(false); } });
})();
</script>
<main id="main" class="flex-1">
<?php if (empty($fullWidth)): ?><div class="mx-auto max-w-7xl px-4 py-6 sm:px-7 sm:py-10"><?php endif; ?>
