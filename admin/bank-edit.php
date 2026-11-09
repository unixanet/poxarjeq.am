<?php
declare(strict_types=1);
require __DIR__ . '/_boot.php';
require __DIR__ . '/_layout.php';
$user = adm_require();

$code = strtolower(preg_replace('/[^a-z0-9\-]/i', '', (string)($_GET['code'] ?? $_POST['code'] ?? '')));
$st = db()->prepare('SELECT * FROM banks WHERE code = ?');
$st->execute([$code]);
$bank = $st->fetch();
if (!$bank) {
    flash('error', 'Банк не найден.');
    redirect('/admin/banks.php');
}

$errors = [];
$v = $bank; // значения формы

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $text = fn(string $k, int $max) => mb_substr(trim((string)($_POST[$k] ?? '')), 0, $max);
    foreach (['name_hy', 'name_ru', 'name_en', 'legal', 'email'] as $k) $v[$k] = $text($k, 190);
    foreach (['phone', 'phone2'] as $k) $v[$k] = $text($k, 64);
    $v['swift'] = $text('swift', 32);
    $v['website'] = $text('website', 255);
    foreach (['address_hy', 'address_ru', 'address_en'] as $k) $v[$k] = $text($k, 255);
    foreach (['about_hy', 'about_ru', 'about_en'] as $k) $v[$k] = $text($k, 4000);
    foreach (['hy', 'ru', 'en'] as $l) { $v["seo_title_$l"] = $text("seo_title_$l", 255); $v["seo_description_$l"] = $text("seo_description_$l", 500); }
    $v['is_active'] = isset($_POST['is_active']) ? 1 : 0;
    $v['is_partner'] = isset($_POST['is_partner']) ? 1 : 0;

    if ($v['name_hy'] === '' && $v['name_ru'] === '' && $v['name_en'] === '') $errors[] = 'Укажите название хотя бы на одном языке.';
    if ($v['email'] !== '' && !filter_var($v['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Email указан неверно.';
    if ($v['website'] !== '' && !preg_match('#^https?://[^\s]+$#i', $v['website'])) $errors[] = 'Сайт должен начинаться с http:// или https://';

    // Логотип
    $newLogo = null;
    $hasFile = isset($_FILES['logo']) && ($_FILES['logo']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
    if (!$errors && $hasFile) {
        try { $newLogo = adm_save_logo($code, $_FILES['logo']); }
        catch (RuntimeException $e) { $errors[] = $e->getMessage(); }
    }

    if (!$errors) {
        $logoPath = $bank['logo_path'];
        if ($newLogo) { adm_delete_logo($logoPath); $logoPath = $newLogo; }
        elseif (!empty($_POST['logo_remove'])) { adm_delete_logo($logoPath); $logoPath = null; }

        $order = $bank['partner_order'];
        if ($v['is_partner'] && !$bank['is_partner']) $order = 100000; // новый партнёр встаёт в конец партнёров
        db()->prepare('UPDATE banks SET name_hy=:nh, name_ru=:nr, name_en=:ne, legal=:legal, phone=:phone, phone2=:phone2, email=:email, website=:website, swift=:swift,
            address_hy=:ah, address_ru=:ar, address_en=:ae, about_hy=:abh, about_ru=:abr, about_en=:abe,
            seo_title_hy=:sth, seo_title_ru=:str, seo_title_en=:ste, seo_description_hy=:sdh, seo_description_ru=:sdr, seo_description_en=:sde,
            logo_path=:logo, is_partner=:p, partner_order=:ord, is_active=:a WHERE id=:id')->execute([
            'sth' => $v['seo_title_hy'], 'str' => $v['seo_title_ru'], 'ste' => $v['seo_title_en'], 'sdh' => $v['seo_description_hy'], 'sdr' => $v['seo_description_ru'], 'sde' => $v['seo_description_en'],
            'nh' => $v['name_hy'], 'nr' => $v['name_ru'], 'ne' => $v['name_en'], 'legal' => $v['legal'], 'phone' => $v['phone'], 'phone2' => $v['phone2'],
            'email' => $v['email'], 'website' => $v['website'], 'swift' => $v['swift'],
            'ah' => $v['address_hy'], 'ar' => $v['address_ru'], 'ae' => $v['address_en'],
            'abh' => $v['about_hy'], 'abr' => $v['about_ru'], 'abe' => $v['about_en'],
            'logo' => $logoPath, 'p' => $v['is_partner'], 'ord' => $order, 'a' => $v['is_active'], 'id' => $bank['id'],
        ]);
        if ($v['is_partner'] !== (int)$bank['is_partner']) {
            $ids = db()->query('SELECT id FROM banks WHERE is_partner = 1 ORDER BY partner_order, id')->fetchAll(PDO::FETCH_COLUMN);
            $u = db()->prepare('UPDATE banks SET partner_order = ? WHERE id = ?');
            foreach ($ids as $i => $pid) $u->execute([($i + 1) * 10, $pid]);
        }
        bank_store_clear_cache();
        flash('ok', 'Сохранено. Изменения уже на сайте (кэш обновляется в течение минуты).');
        redirect('/admin/bank-edit.php?code=' . urlencode($code));
    }
}

$logo = adm_logo_src($bank);
$shownLogo = adm_logo_display($bank);
$inp = 'mt-1.5 block min-h-[48px] w-full rounded-xl border border-slate-300 bg-white px-4 text-base text-black outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-200';
$ta  = 'mt-1.5 block w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-base leading-relaxed text-black outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-200';
$lbl = 'block text-sm font-bold text-slate-900';
$field = fn(string $k, string $label, string $type = 'text') => '<label class="' . $lbl . '" for="f_' . $k . '">' . e($label) . '<input id="f_' . $k . '" name="' . $k . '" type="' . $type . '" value="' . e((string)$v[$k]) . '" class="' . $inp . '"></label>';
$area  = fn(string $k, string $label, int $rows = 4) => '<label class="' . $lbl . '" for="f_' . $k . '">' . e($label) . '<textarea id="f_' . $k . '" name="' . $k . '" rows="' . $rows . '" class="' . $ta . '">' . e((string)$v[$k]) . '</textarea></label>';

adm_head(adm_bank_name($bank), 'banks', $user);
?>
<nav class="mb-4 text-sm" aria-label="Навигация"><a href="/admin/banks.php" class="font-semibold">← Все банки</a></nav>
<div class="flex flex-wrap items-center gap-4">
    <h1 class="min-w-0 flex-1 text-2xl font-black tracking-tight text-slate-900 sm:text-3xl"><?= e(adm_bank_name($bank)) ?></h1>
    <a href="/<?= e($code) ?>" target="_blank" rel="noopener" class="inline-flex min-h-[44px] items-center rounded-xl border border-slate-200 bg-white px-4 text-sm font-bold">Страница на сайте ↗</a>
</div>

<?php if ($errors): ?>
<div role="alert" class="mt-5 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-900">
    <?php foreach ($errors as $er): ?><p><?= e($er) ?></p><?php endforeach; ?>
</div>
<?php endif; ?>

<form method="post" enctype="multipart/form-data" class="mt-6 grid gap-5 pb-24 lg:pb-6">
    <?= csrf_field() ?><input type="hidden" name="code" value="<?= e($code) ?>">

    <section class="rounded-3xl border border-slate-100 bg-white p-5 shadow-[0_10px_40px_rgba(15,23,42,.06)] sm:p-6">
        <h2 class="text-lg font-black text-slate-900">Логотип и статус</h2>
        <div class="mt-4 grid gap-6 md:grid-cols-[auto_1fr]">
            <div class="flex items-center gap-4">
                <div class="grid h-24 w-24 shrink-0 place-items-center overflow-hidden rounded-2xl border border-slate-200 bg-[repeating-conic-gradient(#f1f5f9_0%_25%,#fff_0%_50%)] bg-[length:16px_16px]">
                    <img id="logo-preview" src="<?= e($shownLogo) ?>" alt="Логотип банка" class="h-full w-full object-contain p-1.5 <?= $shownLogo ? '' : 'hidden' ?>">
                    <span id="logo-empty" class="px-2 text-center text-xs font-semibold <?= $shownLogo ? 'hidden' : '' ?>">нет логотипа</span>
                </div>
                <p class="max-w-[14rem] text-xs leading-relaxed">PNG, JPG или WebP до 3 МБ. Лучше квадрат с прозрачным фоном. Сайт сам уменьшит до 320 px.</p>
            </div>
            <div class="space-y-3">
                <label class="<?= $lbl ?>" for="f_logo">Загрузить новый логотип
                    <input id="f_logo" name="logo" type="file" accept="image/png,image/jpeg,image/webp" class="mt-1.5 block w-full cursor-pointer rounded-xl border border-dashed border-slate-300 bg-slate-50 p-3 text-sm file:mr-3 file:min-h-[40px] file:cursor-pointer file:rounded-lg file:border-0 file:bg-blue-600 file:px-4 file:text-sm file:font-bold file:text-white">
                </label>
                <?php if ($logo): ?>
                <label class="flex min-h-[40px] cursor-pointer items-center gap-2.5 text-sm font-semibold"><input type="checkbox" name="logo_remove" value="1" class="h-5 w-5 rounded border-slate-300 accent-blue-600"> Удалить свой логотип (вернётся логотип из API)</label>
                <?php endif; ?>
            </div>
        </div>
        <div class="mt-5 grid gap-1 border-t border-slate-100 pt-4 sm:grid-cols-2 sm:gap-x-8">
            <?= adm_switch('is_partner', (bool)$v['is_partner'], 'Партнёр: показывать в начале списка', 'sw_partner') ?>
            <?= adm_switch('is_active', (bool)$v['is_active'], 'Показывать на сайте', 'sw_active') ?>
        </div>
        <?php if ($v['is_partner']): ?><p class="mt-2 text-sm">Порядок среди партнёров меняется кнопками ↑ ↓ в списке банков.</p><?php endif; ?>
    </section>

    <section class="rounded-3xl border border-slate-100 bg-white p-5 shadow-[0_10px_40px_rgba(15,23,42,.06)] sm:p-6">
        <h2 class="text-lg font-black text-slate-900">Название</h2>
        <p class="mt-1 text-sm">Показывается в таблицах и на странице банка. Пустое поле — берётся стандартное название.</p>
        <div class="mt-4 grid gap-4 md:grid-cols-3">
            <?= $field('name_hy', 'Հայերեն') ?>
            <?= $field('name_ru', 'Русский') ?>
            <?= $field('name_en', 'English') ?>
        </div>
    </section>

    <section class="rounded-3xl border border-slate-100 bg-white p-5 shadow-[0_10px_40px_rgba(15,23,42,.06)] sm:p-6">
        <h2 class="text-lg font-black text-slate-900">Контакты</h2>
        <div class="mt-4 grid gap-4 sm:grid-cols-2">
            <?= $field('legal', 'Юридическое название') ?>
            <?= $field('swift', 'SWIFT') ?>
            <?= $field('phone', 'Телефон', 'tel') ?>
            <?= $field('phone2', 'Второй телефон', 'tel') ?>
            <?= $field('email', 'Email', 'email') ?>
            <?= $field('website', 'Сайт (https://…)', 'url') ?>
        </div>
        <div class="mt-4 grid gap-4 md:grid-cols-3">
            <?= $field('address_hy', 'Հասցե (HY)') ?>
            <?= $field('address_ru', 'Адрес (RU)') ?>
            <?= $field('address_en', 'Address (EN)') ?>
        </div>
    </section>

    <section class="rounded-3xl border border-slate-100 bg-white p-5 shadow-[0_10px_40px_rgba(15,23,42,.06)] sm:p-6">
        <h2 class="text-lg font-black text-slate-900">SEO этой страницы</h2>
        <p class="mt-1 text-sm">Свой заголовок и описание для поисковиков. Пусто — берётся общий шаблон из «Страницы и тексты → Страницы банков» (с названием банка).</p>
        <div class="mt-4 grid gap-5 lg:grid-cols-3">
            <?php foreach (['hy' => 'Հայերեն', 'ru' => 'Русский', 'en' => 'English'] as $l => $ln): ?>
            <div class="space-y-3">
                <?= $field("seo_title_$l", 'Заголовок (title) · ' . $ln) ?>
                <?= $area("seo_description_$l", 'Описание (description) · ' . $ln, 3) ?>
            </div>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="rounded-3xl border border-slate-100 bg-white p-5 shadow-[0_10px_40px_rgba(15,23,42,.06)] sm:p-6">
        <h2 class="text-lg font-black text-slate-900">О банке</h2>
        <div class="mt-4 grid gap-4 lg:grid-cols-3">
            <?= $area('about_hy', 'Հայերեն') ?>
            <?= $area('about_ru', 'Русский') ?>
            <?= $area('about_en', 'English') ?>
        </div>
    </section>

    <div class="fixed inset-x-0 bottom-0 z-30 border-t border-slate-200 bg-white/95 p-3 backdrop-blur lg:static lg:border-0 lg:bg-transparent lg:p-0 lg:backdrop-blur-none">
        <div class="mx-auto flex max-w-6xl gap-3 px-1 lg:px-0">
            <button type="submit" class="min-h-[50px] flex-1 rounded-[14px] bg-blue-600 px-8 text-base font-bold shadow-[0_6px_16px_rgba(37,99,235,.28)] hover:bg-blue-700 lg:flex-none text-white">Сохранить</button>
            <a href="/admin/banks.php" class="inline-flex min-h-[50px] items-center rounded-[14px] border border-slate-200 bg-white px-6 text-base font-bold text-black no-underline">Отмена</a>
        </div>
    </div>
</form>
<script>
(function(){
    var f=document.getElementById('f_logo'),img=document.getElementById('logo-preview'),em=document.getElementById('logo-empty');
    f&&f.addEventListener('change',function(){var file=f.files&&f.files[0];if(!file)return;var r=new FileReader();r.onload=function(e){img.src=e.target.result;img.classList.remove('hidden');em.classList.add('hidden');};r.readAsDataURL(file);});
})();
</script>
<?php adm_foot(); ?>
