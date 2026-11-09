<?php
if (!isset($pageTitle)) $pageTitle = t('site_name') . ' — ' . t('site_tagline');
if (!isset($pageDescription)) $pageDescription = t('hero_sub');
if (!isset($pageCanonical)) $pageCanonical = rtrim(SITE_URL, '/') . '/';
if (!isset($active)) $active = '';
$ogLocale = $lang === 'hy' ? 'hy_AM' : ($lang === 'ru' ? 'ru_RU' : 'en_US');
$pageCanonical = preg_replace('/([?&])lang=[a-z]{2}/', '', $pageCanonical);
$pageCanonical = rtrim($pageCanonical, '?&');
?>
<!DOCTYPE html>
<html lang="<?= e($lang) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1">
    <meta name="theme-color" content="#0078D4">
    <meta name="author" content="<?= e(t('site_name')) ?>">
    <title><?= e($pageTitle) ?></title>
    <meta name="description" content="<?= e($pageDescription) ?>">
    <link rel="canonical" href="<?= e($pageCanonical) ?>">
    <?php foreach (SUPPORTED_LANGS as $hl): ?>
    <link rel="alternate" hreflang="<?= e($hl) ?>" href="<?= e($pageCanonical . (str_contains($pageCanonical, '?') ? '&' : '?') . 'lang=' . $hl) ?>">
    <?php endforeach; ?>
    <link rel="alternate" hreflang="x-default" href="<?= e($pageCanonical) ?>">
    <meta property="og:locale" content="<?= e($ogLocale) ?>">
    <meta property="og:type" content="website">
    <meta property="og:title" content="<?= e($pageTitle) ?>">
    <meta property="og:description" content="<?= e($pageDescription) ?>">
    <meta property="og:url" content="<?= e($pageCanonical) ?>">
    <meta property="og:site_name" content="<?= e(t('site_name')) ?>">
    <meta property="og:image" content="<?= e(SITE_URL) ?>/assets/og.jpg">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= e($pageTitle) ?>">
    <meta name="twitter:description" content="<?= e($pageDescription) ?>">
    <link rel="icon" href="/favicon.ico">
<?php require __DIR__ . '/tw.php'; ?>

<!-- Google tag (gtag.js) -->
<script async src="https://www.googletagmanager.com/gtag/js?id=G-EN397C6CTH"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());

  gtag('config', 'G-EN397C6CTH');
</script>

</head>
<body class="min-h-screen flex flex-col bg-[#fcfcfd] text-black antialiased font-sans">
<?php
$_hNav = cms_nav('header');
$_hLangs = ['hy' => 'Հայերեն', 'ru' => 'Русский', 'en' => 'English'];
?>
<header class="sticky top-0 z-50 bg-white/95 backdrop-blur border-b border-[#eaedf2]">
    <div class="mx-auto max-w-7xl px-4 sm:px-7 h-[60px] sm:h-[68px] flex items-center">
        <a href="/" title="<?= e(t('site_name')) ?> — <?= e(t('today_rates')) ?>" class="flex min-w-0 shrink items-center gap-2 sm:gap-2.5 mr-3 lg:mr-10">
            <span class="w-9 h-9 shrink-0 bg-blue-600 rounded-[10px] flex items-center justify-center text-white font-extrabold text-[19px] shadow-[0_4px_12px_rgba(37,99,235,.3)]" aria-hidden="true">֏</span>
            <span class="truncate text-[14px] min-[400px]:text-[16px] sm:text-[18px] font-black text-slate-900 tracking-tight leading-tight"><?= e(t('site_name')) ?></span>
        </a>

        <nav class="hidden md:flex items-center h-full flex-1" aria-label="<?= e(t('nav_menu')) ?>">
            <?php foreach ($_hNav as $_hn): $_hOn = nav_is_active($_hn['url']); ?>
            <?= ui_link($_hn['url'], nav_label($_hn, $lang), nav_title($_hn, $lang),
                'relative flex items-center px-4 h-full text-[14px] font-semibold no-underline transition-colors after:absolute after:bottom-0 after:left-4 after:right-4 after:h-[2.5px] after:bg-blue-600 after:rounded-t after:transition-transform ' . ($_hOn ? 'after:scale-x-100' : 'after:scale-x-0 hover:after:scale-x-100'),
                array_filter(['aria-current' => $_hOn ? 'page' : null, 'target' => $_hn['new_tab'] ? '_blank' : null, 'rel' => trim(($_hn['new_tab'] ? 'noopener ' : '') . ($_hn['nofollow'] ? 'nofollow' : '')) ?: null])) ?>
            <?php endforeach; ?>
        </nav>

        <div class="ml-auto flex shrink-0 items-center gap-2">
            <div class="flex items-center bg-slate-100 rounded-[9px] p-[3px]" role="group" aria-label="<?= e(t('lang')) ?>">
                <?php foreach ($_hLangs as $_hCode => $_hName): $_hCur = ($_hCode === $lang); ?>
                <a href="<?= e(lang_url($_hCode)) ?>" hreflang="<?= e($_hCode) ?>" lang="<?= e($_hCode) ?>" title="<?= e($_hName) ?>" class="px-2.5 sm:px-3 py-1.5 rounded-[7px] text-[11.5px] font-black tracking-wide no-underline transition <?= $_hCur ? 'bg-blue-600 text-white shadow-[0_2px_6px_rgba(37,99,235,.35)]' : 'text-slate-700' ?>" <?= $_hCur ? 'aria-current="true"' : '' ?>><?= e(strtoupper($_hCode)) ?></a>
                <?php endforeach; ?>
            </div>
            <button type="button" id="burger" class="md:hidden w-10 h-10 grid place-items-center rounded-xl border border-[#eaedf2] bg-white text-slate-900" aria-label="<?= e(t('menu')) ?>" title="<?= e(t('menu')) ?>" aria-expanded="false" aria-controls="mobile-menu">
                <svg id="icon-open" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M4 7h16M4 12h16M4 17h16"/></svg>
                <svg id="icon-close" class="w-5 h-5 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
    </div>
    <div id="mobile-menu" class="hidden md:hidden border-t border-[#eaedf2] bg-white shadow-[0_16px_32px_rgba(15,23,42,.08)]">
        <div class="px-4 py-3 grid gap-1">
            <?php foreach ($_hNav as $_hn): $_hOn = nav_is_active($_hn['url']); ?>
            <?= ui_link($_hn['url'], nav_label($_hn, $lang), nav_title($_hn, $lang), 'flex items-center min-h-[48px] px-4 rounded-xl text-[15px] font-semibold no-underline ' . ($_hOn ? 'bg-blue-50' : 'hover:bg-slate-50'),
                array_filter(['aria-current' => $_hOn ? 'page' : null, 'target' => $_hn['new_tab'] ? '_blank' : null, 'rel' => trim(($_hn['new_tab'] ? 'noopener ' : '') . ($_hn['nofollow'] ? 'nofollow' : '')) ?: null])) ?>
            <?php endforeach; ?>
        </div>
    </div>
</header>
<script>
(function(){
    var b=document.getElementById('burger'),m=document.getElementById('mobile-menu'),o=document.getElementById('icon-open'),c=document.getElementById('icon-close');
    if(!b)return;
    b.addEventListener('click',function(){var h=m.classList.toggle('hidden');o.classList.toggle('hidden',!h);c.classList.toggle('hidden',h);b.setAttribute('aria-expanded',h?'false':'true');});
})();
</script>
<main class="flex-1">
<?php if (empty($fullWidth)): ?><div class="max-w-7xl mx-auto px-4 sm:px-7 py-6 sm:py-10"><?php endif; ?>
