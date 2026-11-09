<?php
require_once __DIR__ . '/includes/functions.php';
$lang = detect_lang();
$L = load_lang($lang);
[$pageTitle, $pageDescription] = seo_meta('contact');
$pageCanonical = SITE_URL . '/contact.php?lang=' . $lang;
$active = 'contact';
$crumbs = [[t('home'), '/'], [t('contact'), null]];

$msg = ''; $msgOk = false;
$f = ['name' => '', 'email' => '', 'message' => ''];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $f['name'] = mb_substr(trim((string)($_POST['name'] ?? '')), 0, 190);
    $f['email'] = mb_substr(trim((string)($_POST['email'] ?? '')), 0, 190);
    $f['message'] = mb_substr(trim((string)($_POST['message'] ?? '')), 0, 4000);
    if (!empty($_POST['website'])) {                       // ловушка для ботов
        $msg = t('contact.ok'); $msgOk = true;
    } elseif ($f['name'] !== '' && filter_var($f['email'], FILTER_VALIDATE_EMAIL) && mb_strlen($f['message']) > 5) {
        try {
            require_once __DIR__ . '/config.db.php';
            $pdo = db();
            $ip = substr((string)($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45);
            $st = $pdo->prepare('SELECT COUNT(*) FROM contact_messages WHERE ip = ? AND created_at > (NOW() - INTERVAL 1 HOUR)');
            $st->execute([$ip]);
            if ((int)$st->fetchColumn() >= 5) {
                $msg = t('contact.err');
            } else {
                $pdo->prepare('INSERT INTO contact_messages (name, email, message, lang, ip) VALUES (?,?,?,?,?)')->execute([$f['name'], $f['email'], $f['message'], $lang, $ip]);
                $msg = t('contact.ok'); $msgOk = true; $f = ['name' => '', 'email' => '', 'message' => ''];
            }
        } catch (Throwable $e) {
            $msg = t('contact.err');
        }
    } else {
        $msg = t('contact.err');
    }
}
$inp = 'mt-1.5 block min-h-[48px] w-full rounded-xl border border-slate-300 bg-white px-4 text-base text-black outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-200';
require __DIR__ . '/includes/header.php';
?>
<?= ui_breadcrumbs($crumbs) ?>
<h1 class="mb-6 text-3xl font-black tracking-tight text-slate-900 sm:text-4xl"><?= e(t('contact')) ?></h1>
<div class="grid items-start gap-6 md:grid-cols-2">
    <section class="rounded-3xl border border-slate-100 bg-white p-5 shadow-[0_10px_40px_rgba(15,23,42,.06)] sm:p-8" aria-labelledby="ct-h">
        <h2 id="ct-h" class="mb-4 text-xl font-black text-slate-900"><?= e(t('contacts')) ?></h2>
        <dl class="space-y-4">
            <div><dt class="text-[11px] font-bold uppercase tracking-wider"><?= e(t('email')) ?></dt><dd class="mt-0.5 font-semibold"><?= ui_link('mailto:' . CONTACT_EMAIL, CONTACT_EMAIL, t('email') . ': ' . CONTACT_EMAIL, 'no-underline hover:underline') ?></dd></div>
            <div><dt class="text-[11px] font-bold uppercase tracking-wider"><?= e(t('phone')) ?></dt><dd class="mt-0.5 font-semibold"><?= ui_link('tel:' . preg_replace('/\s+/', '', CONTACT_PHONE), CONTACT_PHONE, t('phone') . ': ' . CONTACT_PHONE, 'no-underline hover:underline') ?></dd></div>
        </dl>
    </section>
    <section class="rounded-3xl border border-slate-100 bg-white p-5 shadow-[0_10px_40px_rgba(15,23,42,.06)] sm:p-8" aria-labelledby="fm-h">
        <h2 id="fm-h" class="sr-only"><?= e(t('contact.send')) ?></h2>
        <?php if ($msg): ?><p role="status" class="mb-4 rounded-xl px-4 py-3 text-sm font-semibold <?= $msgOk ? 'bg-green-50 text-green-900' : 'bg-red-50 text-red-900' ?>"><?= e($msg) ?></p><?php endif; ?>
        <form method="post" class="space-y-4">
            <label class="block text-sm font-bold" for="f-name"><?= e(t('contact.name')) ?><input id="f-name" type="text" name="name" required autocomplete="name" value="<?= e($f['name']) ?>" class="<?= $inp ?>"></label>
            <label class="block text-sm font-bold" for="f-email"><?= e(t('email')) ?><input id="f-email" type="email" name="email" required autocomplete="email" value="<?= e($f['email']) ?>" class="<?= $inp ?>"></label>
            <label class="block text-sm font-bold" for="f-msg"><?= e(t('contact.message')) ?><textarea id="f-msg" name="message" rows="5" required class="mt-1.5 block w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-base text-black outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-200"><?= e($f['message']) ?></textarea></label>
            <input type="text" name="website" class="hidden" tabindex="-1" autocomplete="off" aria-hidden="true">
            <button type="submit" class="min-h-[50px] w-full rounded-[14px] bg-blue-600 px-8 text-base font-bold text-white shadow-[0_6px_16px_rgba(37,99,235,.28)] hover:bg-blue-700 sm:w-auto"><?= e(t('contact.send')) ?></button>
        </form>
    </section>
</div>
<?php
echo ld_script([['@type' => 'ContactPage', '@id' => $pageCanonical . '#webpage', 'url' => $pageCanonical, 'name' => $pageTitle, 'description' => $pageDescription, 'inLanguage' => $lang,
    'isPartOf' => ['@id' => rtrim(SITE_URL, '/') . '/#website'], 'breadcrumb' => ['@id' => $pageCanonical . '#breadcrumb']], array_merge(ld_breadcrumbs($crumbs), ['@id' => $pageCanonical . '#breadcrumb'])]);
require __DIR__ . '/includes/footer.php';
