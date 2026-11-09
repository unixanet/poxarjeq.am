<?php
require_once __DIR__ . '/includes/functions.php';
$lang = detect_lang();
$L = load_lang($lang);
$pageTitle = t('contact') . ' | ' . t('site_name');
$pageDescription = t('contact') . ' — ' . t('site_name');
$pageCanonical = SITE_URL . '/contact.php?lang=' . $lang;
$active = 'contact';
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $body = trim($_POST['message'] ?? '');
    if (empty($_POST['website']) && $name && filter_var($email, FILTER_VALIDATE_EMAIL) && strlen($body) > 5) {
        $msg = $lang === 'en' ? 'Thank you. Message received (demo mode).' : ($lang === 'ru' ? 'Спасибо. Сообщение принято (демо).' : 'Շնորհակալություն։ Հաղորդագրությունն ընդունված է (դեմո)։');
    } else {
        $msg = $lang === 'en' ? 'Please fill all fields correctly.' : ($lang === 'ru' ? 'Заполните все поля корректно.' : 'Լրացրեք բոլոր դաշտերը ճիշտ։');
    }
}
require __DIR__ . '/includes/header.php';
?>
<nav class="text-sm text-black mb-6">
    <a href="/?lang=<?= e($lang) ?>" class="hover:text-brand"><?= e(t('home')) ?></a>
    <span class="mx-2">/</span>
    <span class="text-black"><?= e(t('contact')) ?></span>
</nav>
<h1 class="text-2xl sm:text-3xl font-bold text-black mb-6"><?= e(t('contact')) ?></h1>
<div class="grid md:grid-cols-2 gap-8">
    <div class="bg-white border border-black/10 rounded-2xl p-6">
        <h2 class="font-bold text-black mb-4"><?= e(t('contacts')) ?></h2>
        <p class="text-sm text-black mb-2"><strong><?= e(t('email')) ?>:</strong> <a href="mailto:<?= e(CONTACT_EMAIL) ?>" class="text-brand"><?= e(CONTACT_EMAIL) ?></a></p>
        <p class="text-sm text-black"><strong><?= e(t('phone')) ?>:</strong> <?= e(CONTACT_PHONE) ?></p>
    </div>
    <div class="bg-white border border-black/10 rounded-2xl p-6">
        <?php if ($msg): ?><p class="mb-4 text-sm bg-brand-light text-brand px-3 py-2 rounded-lg"><?= e($msg) ?></p><?php endif; ?>
        <form method="post" class="space-y-4">
            <div>
                <label class="block text-xs font-medium text-black mb-1">Name</label>
                <input type="text" name="name" required class="w-full border border-black/10 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand/30 focus:border-brand">
            </div>
            <div>
                <label class="block text-xs font-medium text-black mb-1">Email</label>
                <input type="email" name="email" required class="w-full border border-black/10 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand/30 focus:border-brand">
            </div>
            <div>
                <label class="block text-xs font-medium text-black mb-1">Message</label>
                <textarea name="message" rows="4" required class="w-full border border-black/10 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand/30 focus:border-brand"></textarea>
            </div>
            <input type="text" name="website" class="hidden" tabindex="-1" autocomplete="off">
            <button type="submit" class="bg-brand hover:bg-brand-hover text-white font-medium px-5 py-2.5 rounded-lg text-sm transition"><?= e(t('contact')) ?></button>
        </form>
    </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
