<?php
require_once __DIR__ . '/includes/functions.php';
$lang = detect_lang();
$L = load_lang($lang);
[$pageTitle, $pageDescription] = seo_meta('contact');
$pagePath = '/contact.php';
$active = 'contact';
$crumbs = [[t('home'), lurl('/')], [t('contact'), null]];

$msg = ''; $msgOk = false;
$f = ['name' => '', 'phone' => '', 'email' => '', 'topic' => '', 'message' => ''];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $f['name'] = mb_substr(trim((string)($_POST['name'] ?? '')), 0, 190);
    $f['phone'] = mb_substr(trim((string)($_POST['phone'] ?? '')), 0, 60);
    $f['email'] = mb_substr(trim((string)($_POST['email'] ?? '')), 0, 190);
    $f['topic'] = mb_substr(trim((string)($_POST['topic'] ?? '')), 0, 190);
    $f['message'] = mb_substr(trim((string)($_POST['message'] ?? '')), 0, 4000);
    if (!empty($_POST['website'])) {
        $msg = t('contact.ok'); $msgOk = true;
    } elseif ($f['name'] !== '' && preg_match('/^[0-9+()\s-]{6,}$/', $f['phone']) && filter_var($f['email'], FILTER_VALIDATE_EMAIL) && mb_strlen($f['message']) > 5) {
        try {
            require_once __DIR__ . '/config.db.php';
            $pdo = db();
            $ip = substr((string)($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45);
            $st = $pdo->prepare('SELECT COUNT(*) FROM contact_messages WHERE ip = ? AND created_at > (NOW() - INTERVAL 1 HOUR)');
            $st->execute([$ip]);
            if ((int)$st->fetchColumn() >= 5) {
                $msg = t('contact.err');
            } else {
                $body = ($f['topic'] !== '' ? t('contact.topic') . ': ' . $f['topic'] . "\n" : '') . t('contact.phone') . ': ' . $f['phone'] . "\n\n" . $f['message'];
                $pdo->prepare('INSERT INTO contact_messages (name, email, message, lang, ip) VALUES (?,?,?,?,?)')->execute([$f['name'], $f['email'], $body, $lang, $ip]);
                @mail(CONTACT_EMAIL, '=?UTF-8?B?' . base64_encode('Poxarjeq.am: ' . ($f['topic'] ?: $f['name'])) . '?=', $body . "\n\n" . $f['name'] . ' <' . $f['email'] . '>', "Content-Type: text/plain; charset=UTF-8\r\nReply-To: " . $f['email']);
                $msg = t('contact.ok'); $msgOk = true; $f = ['name' => '', 'phone' => '', 'email' => '', 'topic' => '', 'message' => ''];
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
<h1 class="text-[28px] font-black leading-tight tracking-tight text-slate-900 sm:text-5xl"><?= e(t('contact.h1')) ?></h1>
<p class="mt-3 max-w-3xl text-base leading-relaxed sm:text-lg"><?= e(t('contact.lead')) ?></p>
<div class="mt-8 grid items-start gap-6 lg:grid-cols-[1fr_1.1fr]">
    <section class="rounded-3xl border border-slate-100 bg-white p-5 shadow-[0_10px_40px_rgba(15,23,42,.06)] sm:p-8" aria-labelledby="ct-h">
        <h2 id="ct-h" class="mb-5 text-xl font-black text-slate-900"><?= e(t('contacts')) ?></h2>
        <dl class="space-y-5">
            <div><dt class="text-[11px] font-bold uppercase tracking-wider"><?= e(t('address')) ?></dt><dd class="mt-1 text-[17px] font-bold text-slate-900"><?= e(CONTACT_ADDRESS[$lang] ?? CONTACT_ADDRESS['hy']) ?><?php if ($lang !== 'en'): ?><span class="block text-sm font-semibold">(<?= e(CONTACT_ADDRESS['en']) ?>)</span><?php endif; ?></dd>
                <dd class="mt-2"><?= ui_link('https://www.google.com/maps/search/?api=1&query=' . rawurlencode(CONTACT_ADDRESS['en']), t('contact.map') . ' ↗', t('contact.map_title'), 'text-sm font-bold text-blue-600 no-underline hover:underline', ['target' => '_blank', 'rel' => 'noopener nofollow']) ?></dd></div>
            <div><dt class="text-[11px] font-bold uppercase tracking-wider"><?= e(t('phone')) ?></dt><dd class="mt-1 text-[17px] font-bold"><?= ui_link('tel:' . preg_replace('/\s+/', '', CONTACT_PHONE), CONTACT_PHONE, t('phone') . ': ' . CONTACT_PHONE, 'text-blue-600 no-underline hover:underline') ?></dd></div>
            <div><dt class="text-[11px] font-bold uppercase tracking-wider"><?= e(t('email')) ?></dt><dd class="mt-1 text-[17px] font-bold"><?= ui_link('mailto:' . CONTACT_EMAIL, CONTACT_EMAIL, t('email') . ': ' . CONTACT_EMAIL, 'text-blue-600 no-underline hover:underline') ?></dd></div>
            <div><dt class="text-[11px] font-bold uppercase tracking-wider">Telegram</dt><dd class="mt-1 text-[17px] font-bold"><?= ui_link(t('tg.url'), '@poxarjeqcom', t('tg.title_attr'), 'text-blue-600 no-underline hover:underline', ['target' => '_blank', 'rel' => 'noopener']) ?></dd></div>
        </dl>
        <div class="mt-6 rounded-2xl bg-blue-50 p-4 text-[15px] leading-relaxed">
            <p class="font-bold text-slate-900"><?= e(t('footer.leads')) ?></p>
            <p class="mt-1"><?= e(t('footer.leads_desc')) ?></p>
            <p class="mt-2 flex flex-wrap gap-x-5 gap-y-1 text-sm font-bold">
                <?= ui_link(LEADS_URL, 'Leads.am ↗', 'Leads.am', 'text-blue-600 no-underline hover:underline', ['target' => '_blank', 'rel' => 'noopener']) ?>
                <?= ui_link(PARENT_SITE . '/contact.php', 'Poxarjeq.com ↗', t('footer.sister_title'), 'text-blue-600 no-underline hover:underline', ['target' => '_blank', 'rel' => 'noopener']) ?>
            </p>
        </div>
    </section>
    <section class="rounded-3xl border border-slate-100 bg-white p-5 shadow-[0_10px_40px_rgba(15,23,42,.06)] sm:p-8" aria-labelledby="fm-h">
        <h2 id="fm-h" class="mb-4 text-xl font-black text-slate-900"><?= e(t('contact.form_title')) ?></h2>
        <?php if ($msg): ?><p role="status" class="mb-4 rounded-xl px-4 py-3 text-sm font-semibold <?= $msgOk ? 'bg-green-50 text-green-900' : 'bg-red-50 text-red-900' ?>"><?= e($msg) ?></p><?php endif; ?>
        <form method="post" class="space-y-4">
            <label class="block text-sm font-bold" for="f-name"><?= e(t('contact.name')) ?> *<input id="f-name" type="text" name="name" required autocomplete="name" value="<?= e($f['name']) ?>" class="<?= $inp ?>"></label>
            <div class="grid gap-4 sm:grid-cols-2">
                <label class="block text-sm font-bold" for="f-phone"><?= e(t('contact.phone')) ?> *<input id="f-phone" type="tel" name="phone" required autocomplete="tel" inputmode="tel" value="<?= e($f['phone']) ?>" class="<?= $inp ?>"></label>
                <label class="block text-sm font-bold" for="f-email"><?= e(t('email')) ?> *<input id="f-email" type="email" name="email" required autocomplete="email" value="<?= e($f['email']) ?>" class="<?= $inp ?>"></label>
            </div>
            <label class="block text-sm font-bold" for="f-topic"><?= e(t('contact.topic')) ?><input id="f-topic" type="text" name="topic" value="<?= e($f['topic']) ?>" class="<?= $inp ?>"></label>
            <label class="block text-sm font-bold" for="f-msg"><?= e(t('contact.message')) ?> *<textarea id="f-msg" name="message" rows="5" required class="mt-1.5 block w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-base text-black outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-200"><?= e($f['message']) ?></textarea></label>
            <input type="text" name="website" class="hidden" tabindex="-1" autocomplete="off" aria-hidden="true">
            <button type="submit" class="min-h-[50px] w-full rounded-[14px] bg-blue-600 px-8 text-base font-bold text-white shadow-[0_6px_16px_rgba(37,99,235,.28)] hover:bg-blue-700 sm:w-auto"><?= e(t('contact.send')) ?></button>
        </form>
    </section>
</div>
<?php
echo ld_script([['@type' => 'ContactPage', '@id' => $pageCanonical . '#webpage', 'url' => $pageCanonical, 'name' => $pageTitle, 'description' => $pageDescription, 'inLanguage' => $lang,
    'isPartOf' => ['@id' => rtrim(SITE_URL, '/') . '/#website'], 'about' => ['@id' => rtrim(SITE_URL, '/') . '/#organization'], 'breadcrumb' => ['@id' => $pageCanonical . '#breadcrumb']], array_merge(ld_breadcrumbs($crumbs), ['@id' => $pageCanonical . '#breadcrumb'])]);
require __DIR__ . '/includes/footer.php';
