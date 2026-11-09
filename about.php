<?php
require_once __DIR__ . '/includes/functions.php';
$lang = detect_lang();
$L = load_lang($lang);
$pageTitle = t('about') . ' | ' . t('site_name');
$pageDescription = t('footer_text');
$pageCanonical = SITE_URL . '/about.php?lang=' . $lang;
$active = 'about';
require __DIR__ . '/includes/header.php';
?>
<nav class="text-sm text-black mb-6">
    <a href="/?lang=<?= e($lang) ?>" class="hover:text-brand"><?= e(t('home')) ?></a>
    <span class="mx-2">/</span>
    <span class="text-black"><?= e(t('about')) ?></span>
</nav>
<h1 class="text-2xl sm:text-3xl font-bold text-black mb-6"><?= e(t('about')) ?></h1>

<div class="prose max-w-none space-y-6 text-black">
    <div class="bg-white border border-black/10 rounded-2xl p-6 sm:p-8">
        <h2 class="text-lg font-bold text-black mb-3"><?= e(t('site_name')) ?></h2>
        <p class="leading-relaxed"><?= e(t('footer_text')) ?></p>
        <p class="leading-relaxed mt-3"><?= e(t('hero_sub')) ?></p>
    </div>
    <div class="bg-brand-light border border-blue-100 rounded-2xl p-6 sm:p-8">
        <h2 class="text-lg font-bold text-black mb-3">E-E-A-T</h2>
        <ul class="space-y-2 text-sm">
            <li><strong>Experience</strong> — <?= $lang==='en' ? 'Years of experience aggregating financial data in Armenia.' : ($lang==='ru' ? 'Многолетний опыт агрегации финансовых данных в Армении.' : 'Տարիների փորձ ֆինանսական տվյալների ագրեգացման մեջ Հայաստանում։') ?></li>
            <li><strong>Expertise</strong> — <?= $lang==='en' ? 'We work only with official public sources and bank data.' : ($lang==='ru' ? 'Работаем только с официальными публичными источниками и данными банков.' : 'Աշխատում ենք միայն պաշտոնական հանրային աղբյուրների և բանկերի տվյալների հետ։') ?></li>
            <li><strong>Authoritativeness</strong> — <?= $lang==='en' ? 'Data from poxarjeq.com API based on real bank rates.' : ($lang==='ru' ? 'Данные из API poxarjeq.com на основе реальных курсов банков.' : 'Տվյալներ poxarjeq.com API-ից՝ իրական բանկային կուրսերի հիման վրա։') ?></li>
            <li><strong>Trustworthiness</strong> — <?= $lang==='en' ? 'Clear update times, disclaimer, privacy policy and contacts.' : ($lang==='ru' ? 'Прозрачное время обновления, отказ от ответственности, политика конфиденциальности и контакты.' : 'Թափանցիկ թարմացման ժամանակ, հրաժարում, գաղտնիության քաղաքականություն և կոնտակտներ։') ?></li>
        </ul>
    </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
