<?php
declare(strict_types=1);
require __DIR__ . '/_boot.php';
require __DIR__ . '/_layout.php';
require __DIR__ . '/_forms.php';
$user = adm_require();

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$row = ['area' => 'footer', 'grp' => 1, 'sort' => 1000, 'url' => '', 'label_hy' => '', 'label_ru' => '', 'label_en' => '', 'title_hy' => '', 'title_ru' => '', 'title_en' => '', 'new_tab' => 0, 'nofollow' => 0, 'is_active' => 1];
if ($id) {
    $st = db()->prepare('SELECT * FROM nav_links WHERE id = ?'); $st->execute([$id]); $row = $st->fetch();
    if (!$row) { flash('error', 'Ссылка не найдена.'); redirect('/admin/menu.php'); }
}
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $v = $row;
    $v['area'] = ($_POST['area'] ?? '') === 'header' ? 'header' : 'footer';
    $v['grp'] = $v['area'] === 'header' ? 0 : min(max((int)($_POST['grp'] ?? 1), 1), 4);
    $v['url'] = trim((string)($_POST['url'] ?? ''));
    foreach (['label_hy', 'label_ru', 'label_en'] as $k) $v[$k] = mb_substr(trim((string)($_POST[$k] ?? '')), 0, 190);
    foreach (['title_hy', 'title_ru', 'title_en'] as $k) $v[$k] = mb_substr(trim((string)($_POST[$k] ?? '')), 0, 255);
    $v['new_tab'] = isset($_POST['new_tab']) ? 1 : 0; $v['nofollow'] = isset($_POST['nofollow']) ? 1 : 0; $v['is_active'] = isset($_POST['is_active']) ? 1 : 0;
    if (!preg_match('~^(/|https?://|mailto:|tel:)[^\s]*$~i', $v['url'])) $errors[] = 'Адрес ссылки должен начинаться с «/», http://, https://, mailto: или tel:';
    if ($v['label_hy'] === '') $errors[] = 'Заполните текст ссылки на армянском (основной язык).';
    if (!$errors) {
        foreach (['ru', 'en'] as $l) if ($v["label_$l"] === '') $v["label_$l"] = $v['label_hy'];
        foreach (SUPPORTED_LANGS as $l) if ($v["title_$l"] === '') $v["title_$l"] = $v["label_$l"];   // title всегда заполнен
        if ($id) {
            db()->prepare('UPDATE nav_links SET area=?, grp=?, url=?, label_hy=?, label_ru=?, label_en=?, title_hy=?, title_ru=?, title_en=?, new_tab=?, nofollow=?, is_active=? WHERE id=?')
                ->execute([$v['area'], $v['grp'], $v['url'], $v['label_hy'], $v['label_ru'], $v['label_en'], $v['title_hy'], $v['title_ru'], $v['title_en'], $v['new_tab'], $v['nofollow'], $v['is_active'], $id]);
        } else {
            $max = (int)db()->query("SELECT COALESCE(MAX(sort),0) FROM nav_links WHERE area = " . db()->quote($v['area']) . " AND grp = " . (int)$v['grp'])->fetchColumn();
            db()->prepare('INSERT INTO nav_links (area, grp, sort, url, label_hy, label_ru, label_en, title_hy, title_ru, title_en, new_tab, nofollow, is_active) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)')
                ->execute([$v['area'], $v['grp'], $max + 10, $v['url'], $v['label_hy'], $v['label_ru'], $v['label_en'], $v['title_hy'], $v['title_ru'], $v['title_en'], $v['new_tab'], $v['nofollow'], $v['is_active']]);
        }
        adm_content_changed();
        flash('ok', 'Сохранено.');
        redirect('/admin/menu.php');
    }
    $row = $v;
}
adm_head($id ? 'Изменить ссылку' : 'Новая ссылка', 'menu', $user);
?>
<nav class="mb-4 text-sm"><a href="/admin/menu.php" title="Меню и подвал" class="font-semibold">← Меню и подвал</a></nav>
<h1 class="text-2xl font-black tracking-tight text-slate-900 sm:text-3xl"><?= $id ? 'Изменить ссылку' : 'Новая ссылка' ?></h1>
<?php if ($errors): ?><div role="alert" class="mt-5 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-900"><?php foreach ($errors as $er): ?><p><?= e($er) ?></p><?php endforeach; ?></div><?php endif; ?>
<form method="post" class="mt-6 grid gap-5 pb-24 lg:pb-6">
    <?= csrf_field() ?><input type="hidden" name="id" value="<?= $id ?>">
    <section class="<?= ADM_CARD ?>">
        <h2 class="text-lg font-black text-slate-900">Где показывать</h2>
        <div class="mt-4 grid gap-4 sm:grid-cols-3">
            <label class="block text-sm font-bold" for="m_area">Область
                <select id="m_area" name="area" class="<?= ADM_INP ?>"><option value="header" <?= $row['area'] === 'header' ? 'selected' : '' ?>>Шапка (верхнее меню)</option><option value="footer" <?= $row['area'] === 'footer' ? 'selected' : '' ?>>Подвал</option></select></label>
            <label class="block text-sm font-bold" for="m_grp">Колонка в подвале
                <select id="m_grp" name="grp" class="<?= ADM_INP ?>"><?php for ($c = 1; $c <= 4; $c++): ?><option value="<?= $c ?>" <?= (int)$row['grp'] === $c ? 'selected' : '' ?>><?= $c ?> · <?= e(cms_default('footer.col' . $c, 'ru')) ?></option><?php endfor; ?></select></label>
            <?= adm_field('m_url', 'url', 'Адрес ссылки', (string)$row['url'], false, 1, 'placeholder="/about.php или https://…"') ?>
        </div>
        <div class="mt-3 grid gap-1 sm:grid-cols-3">
            <?= adm_switch('is_active', (bool)$row['is_active'], 'Показывать', 'm_act') ?>
            <?= adm_switch('new_tab', (bool)$row['new_tab'], 'Открывать в новой вкладке', 'm_tab') ?>
            <?= adm_switch('nofollow', (bool)$row['nofollow'], 'nofollow (внешняя ссылка)', 'm_nf') ?>
        </div>
    </section>
    <section class="<?= ADM_CARD ?>">
        <h2 class="text-lg font-black text-slate-900">Текст ссылки и подсказка (title)</h2>
        <p class="mt-1 text-sm text-black">Подсказка показывается при наведении и помогает поисковикам понять, куда ведёт ссылка. Если оставить пустой, будет использован текст ссылки.</p>
        <div class="mt-4 grid gap-4 lg:grid-cols-3">
            <?php foreach (ADM_LANGS as $l => $ln): ?>
            <div class="space-y-3">
                <?= adm_field("m_label_$l", "label_$l", 'Текст · ' . e($ln), (string)$row["label_$l"]) ?>
                <?= adm_field("m_title_$l", "title_$l", 'Подсказка title · ' . strtoupper($l), (string)$row["title_$l"]) ?>
            </div>
            <?php endforeach; ?>
        </div>
    </section>
    <div class="fixed inset-x-0 bottom-0 z-30 border-t border-slate-200 bg-white/95 p-3 backdrop-blur lg:static lg:border-0 lg:bg-transparent lg:p-0">
        <div class="mx-auto flex max-w-6xl gap-3 px-1 lg:px-0"><button type="submit" class="<?= ADM_BTN ?> flex-1 lg:flex-none">Сохранить</button><a href="/admin/menu.php" title="Меню и подвал" class="<?= ADM_BTN2 ?>">Отмена</a></div>
    </div>
</form>
<?php if ($id): ?>
<form method="post" action="/admin/menu.php" class="mb-24 lg:mb-0" onsubmit="return confirm('Удалить эту ссылку?');">
    <?= csrf_field() ?><input type="hidden" name="id" value="<?= $id ?>"><input type="hidden" name="action" value="delete">
    <button type="submit" class="min-h-[44px] rounded-xl border border-red-200 bg-white px-5 text-sm font-bold text-red-700 hover:bg-red-50">Удалить ссылку</button>
</form>
<?php endif; ?>
<?php adm_foot(); ?>
