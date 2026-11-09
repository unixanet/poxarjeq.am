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
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: { DEFAULT: '#0078D4', hover: '#106EBE', light: '#E8F4FD', dark: '#005A9E' }
                    }
                }
            }
        }
    </script>
    <style>
        body, p, h1, h2, h3, h4, h5, h6, td, th, li, span, div, label, input, select, textarea {
            font-family: system-ui, -apple-system, 'Segoe UI', Roboto, 'Noto Sans Armenian', sans-serif;
        }
        body { color: #000 !important; background: #fff; }
        /* NO GRAY TEXT — only black */
        .text-black, p, td, th, li, span, div, h1, h2, h3, h4, nav, footer li a {
            color: #000;
        }
        a { color: #0078D4; }
        a:hover { color: #106EBE; }
        .text-brand, a.text-brand { color: #0078D4 !important; }
        .text-emerald-700, .text-emerald-600 { color: #047857 !important; }
        header .text-white, .bg-brand, .bg-brand * { color: #fff !important; }
        footer, footer * { color: #fff !important; }
        footer a:hover { color: #E8F4FD !important; }
        .scroll-x { overflow-x: auto; -webkit-overflow-scrolling: touch; }
        .scroll-x table { min-width: 920px; }
        select.cur-select { color: #000 !important; }
    </style>

<!-- Google tag (gtag.js) -->
<script async src="https://www.googletagmanager.com/gtag/js?id=G-EN397C6CTH"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());

  gtag('config', 'G-EN397C6CTH');
</script>

</head>
<body class="bg-white text-black antialiased min-h-screen flex flex-col">
<header class="sticky top-0 z-50 bg-white/95 backdrop-blur border-b border-black/10">
    <div class="max-w-6xl mx-auto px-4 sm:px-6">
        <div class="flex items-center justify-between h-16">
            <a href="/" class="flex items-center gap-2.5 group no-underline">
                <span class="w-9 h-9 rounded-lg bg-brand text-white flex items-center justify-center font-bold text-lg group-hover:bg-brand-hover transition">֏</span>
                <span class="font-semibold text-black hidden sm:block"><?= e(t('site_name')) ?></span>
            </a>
            <nav class="hidden md:flex items-center gap-1">
                <a href="/" class="px-3 py-2 rounded-lg text-sm font-medium no-underline <?= $active==='home' ? 'text-brand bg-brand-light' : 'text-black hover:text-brand hover:bg-brand-light' ?>"><?= e(t('home')) ?></a>
                <a href="/currency.php?cur=USD" class="px-3 py-2 rounded-lg text-sm font-medium no-underline text-black hover:text-brand hover:bg-brand-light">USD</a>
                <a href="/currency.php?cur=EUR" class="px-3 py-2 rounded-lg text-sm font-medium no-underline text-black hover:text-brand hover:bg-brand-light">EUR</a>
                <a href="/currency.php?cur=RUR" class="px-3 py-2 rounded-lg text-sm font-medium no-underline text-black hover:text-brand hover:bg-brand-light">RUR</a>
                <a href="/about.php" class="px-3 py-2 rounded-lg text-sm font-medium no-underline <?= $active==='about' ? 'text-brand bg-brand-light' : 'text-black hover:text-brand hover:bg-brand-light' ?>"><?= e(t('about')) ?></a>
                <a href="/contact.php" class="px-3 py-2 rounded-lg text-sm font-medium no-underline <?= $active==='contact' ? 'text-brand bg-brand-light' : 'text-black hover:text-brand hover:bg-brand-light' ?>"><?= e(t('contact')) ?></a>
            </nav>
            <div class="flex items-center gap-2">
                <div class="relative" id="lang-wrap">
                    <button type="button" id="lang-btn" class="flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg border border-black/15 text-sm font-medium text-black hover:border-brand hover:text-brand transition">
                        <img src="<?= e(lang_flag($lang)) ?>" alt="" width="18" height="12" class="object-contain" onerror="this.style.display='none'"><span class="uppercase"><?= e($lang) ?></span>
                        <svg class="w-3.5 h-3.5 opacity-70" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div id="lang-menu" class="hidden absolute right-0 mt-1.5 w-40 bg-white border border-black/10 rounded-xl shadow-lg py-1 z-50">
                        <a href="<?= e(lang_url('hy')) ?>" class="flex items-center gap-2 px-3 py-2 text-sm no-underline text-black hover:bg-brand-light <?= $lang==='hy'?'text-brand font-semibold':'' ?>">
                            <img src="<?= e(lang_flag('hy')) ?>" alt="hy" width="20" height="14" class="object-contain" onerror="this.style.display='none'"> Հայերեն
                        </a>
                        <a href="<?= e(lang_url('ru')) ?>" class="flex items-center gap-2 px-3 py-2 text-sm no-underline text-black hover:bg-brand-light <?= $lang==='ru'?'text-brand font-semibold':'' ?>">
                            <img src="<?= e(lang_flag('ru')) ?>" alt="ru" width="20" height="14" class="object-contain" onerror="this.style.display='none'"> Русский
                        </a>
                        <a href="<?= e(lang_url('en')) ?>" class="flex items-center gap-2 px-3 py-2 text-sm no-underline text-black hover:bg-brand-light <?= $lang==='en'?'text-brand font-semibold':'' ?>">
                            <img src="<?= e(lang_flag('en')) ?>" alt="en" width="20" height="14" class="object-contain" onerror="this.style.display='none'"> English
                        </a>
                    </div>
                </div>
                <button type="button" id="burger" class="md:hidden p-2 rounded-lg text-black hover:bg-brand-light" aria-label="<?= e(t('menu')) ?>">
                    <svg id="icon-open" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                    <svg id="icon-close" class="w-6 h-6 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
        </div>
    </div>
    <div id="mobile-menu" class="hidden md:hidden border-t border-black/10 bg-white">
        <div class="px-4 py-3 space-y-1">
            <a href="/" class="block px-3 py-2.5 rounded-lg text-sm font-medium no-underline text-black hover:bg-brand-light"><?= e(t('home')) ?></a>
            <a href="/currency.php?cur=USD" class="block px-3 py-2.5 rounded-lg text-sm font-medium no-underline text-black hover:bg-brand-light">USD</a>
            <a href="/currency.php?cur=EUR" class="block px-3 py-2.5 rounded-lg text-sm font-medium no-underline text-black hover:bg-brand-light">EUR</a>
            <a href="/currency.php?cur=RUR" class="block px-3 py-2.5 rounded-lg text-sm font-medium no-underline text-black hover:bg-brand-light">RUR</a>
            <a href="/about.php" class="block px-3 py-2.5 rounded-lg text-sm font-medium no-underline text-black hover:bg-brand-light"><?= e(t('about')) ?></a>
            <a href="/contact.php" class="block px-3 py-2.5 rounded-lg text-sm font-medium no-underline text-black hover:bg-brand-light"><?= e(t('contact')) ?></a>
        </div>
    </div>
</header>
<script>
(function(){
    const btn=document.getElementById('lang-btn'), menu=document.getElementById('lang-menu');
    btn&&btn.addEventListener('click',e=>{e.stopPropagation();menu.classList.toggle('hidden');});
    document.addEventListener('click',()=>{menu&&menu.classList.add('hidden');});
    const burger=document.getElementById('burger'), mob=document.getElementById('mobile-menu');
    const io=document.getElementById('icon-open'), ic=document.getElementById('icon-close');
    burger&&burger.addEventListener('click',()=>{mob.classList.toggle('hidden');io.classList.toggle('hidden');ic.classList.toggle('hidden');});
})();
</script>
<main class="flex-1">
<div class="max-w-6xl mx-auto px-4 sm:px-6 py-6 sm:py-10">
