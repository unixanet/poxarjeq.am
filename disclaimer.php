<?php
require_once __DIR__ . '/includes/functions.php';
$lang = detect_lang();
$L = load_lang($lang);
$pageTitle = t('disclaimer') . ' | ' . t('site_name');
$pageDescription = t('disclaimer_full');
$pageCanonical = SITE_URL . '/disclaimer.php?lang=' . $lang;
$active = '';
require __DIR__ . '/includes/header.php';
?>
<nav class="text-sm text-black mb-6">
    <a href="/?lang=<?= e($lang) ?>" class="hover:text-brand"><?= e(t('home')) ?></a>
    <span class="mx-2">/</span>
    <span class="text-black"><?= e(t('disclaimer')) ?></span>
</nav>
<h1 class="text-2xl font-bold text-black mb-6"><?= e(t('disclaimer')) ?></h1>
<div class="bg-white border border-black/10 rounded-2xl p-6 sm:p-8 text-sm text-black leading-relaxed">
    <p><?= e(t('disclaimer_full')) ?></p>
    <p class="mt-4"><?= $lang==='en' ? 'This site does not provide financial advice and is not responsible for any losses related to the use of the published rates.' : ($lang==='ru' ? 'Сайт не предоставляет финансовых консультаций и не несёт ответственности за убытки, связанные с использованием опубликованных курсов.' : 'Կայքը ֆինանսական խորհուրդներ չի տալիս և պատասխանատվություն չի կրում հրապարակված կուրսերի օգտագործման հետ կապված կորուստների համար։') ?></p>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
