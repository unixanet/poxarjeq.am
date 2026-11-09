<?php
require_once __DIR__ . '/includes/functions.php';
$lang = detect_lang();
$L = load_lang($lang);
$pageTitle = t('privacy') . ' | ' . t('site_name');
$pageDescription = t('privacy');
$pageCanonical = SITE_URL . '/privacy.php?lang=' . $lang;
$active = '';
require __DIR__ . '/includes/header.php';
?>
<nav class="text-sm text-black mb-6">
    <a href="/?lang=<?= e($lang) ?>" class="hover:text-brand"><?= e(t('home')) ?></a>
    <span class="mx-2">/</span>
    <span class="text-black"><?= e(t('privacy')) ?></span>
</nav>
<h1 class="text-2xl font-bold text-black mb-6"><?= e(t('privacy')) ?></h1>
<div class="bg-white border border-black/10 rounded-2xl p-6 sm:p-8 text-sm text-black space-y-4 leading-relaxed">
    <p><?= $lang==='en' ? 'We respect your privacy. We collect technical data (IP, browser) and contact form data only to operate the site and respond to messages. We do not sell personal data.' : ($lang==='ru' ? 'Мы уважаем вашу конфиденциальность. Собираем технические данные (IP, браузер) и данные формы обратной связи только для работы сайта и ответов. Мы не продаём персональные данные.' : 'Մենք հարգում ենք ձեր գաղտնիությունը։ Հավաքում ենք տեխնիկական տվյալներ (IP, բրաուզեր) և կոնտակտային ձևի տվյալներ միայն կայքի աշխատանքի և պատասխանների համար։ Անձնական տվյալներ չենք վաճառում։') ?></p>
    <p><?= $lang==='en' ? 'Contact us at ' : ($lang==='ru' ? 'Связь: ' : 'Կապ՝ ') ?><a href="mailto:<?= e(CONTACT_EMAIL) ?>" class="text-brand"><?= e(CONTACT_EMAIL) ?></a></p>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
