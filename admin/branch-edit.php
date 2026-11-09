<?php
declare(strict_types=1);
require __DIR__ . '/_boot.php';
require __DIR__ . '/_layout.php';
require __DIR__ . '/_forms.php';
$user = adm_require();

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$row = ['bank_code' => (string)($_GET['bank'] ?? ''), 'slug' => '', 'city_hy' => '', 'city_ru' => '', 'city_en' => '', 'name_hy' => '', 'name_ru' => '', 'name_en' => '', 'address_hy' => '', 'address_ru' => '', 'address_en' => '', 'phone' => '', 'sort' => 1000, 'is_active' => 1];
if ($id) {
    $st = db()->prepare('SELECT * FROM branches WHERE id = ?'); $st->execute([$id]); $row = $st->fetch();
    if (!$row) { flash('error', 'Филиал не найден.'); redirect('/admin/branches.php'); }
}
$banks = db()->query('SELECT code, name_ru, name_en, name_hy FROM banks ORDER BY name_ru, code')->fetchAll();
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $v = $row;
    $v['bank_code'] = strtolower(preg_replace('/[^a-z0-9\-]/i', '', (string)($_POST['bank_code'] ?? '')));
    $v['slug'] = strtolower(trim((string)($_POST['slug'] ?? '')));
    foreach (['city', 'name', 'address'] as $k) foreach (SUPPORTED_LANGS as $l) $v["{$k}_$l"] = mb_substr(trim((string)($_POST["{$k}_$l"] ?? '')), 0, 255);
    $v['phone'] = mb_substr(trim((string)($_POST['phone'] ?? '')), 0, 64);
    $v['is_active'] = isset($_POST['is_active']) ? 1 : 0;
    if (!preg_match('/^[a-z0-9][a-z0-9\-]{1,78}$/', $v['slug'])) $errors[] = 'Адрес страницы (slug): латиница, цифры и дефис, например yerevan-center.';
    if ($v['bank_code'] === '') $errors[] = 'Выберите банк.';
    if ($v['name_hy'] === '' || $v['city_hy'] === '') $errors[] = 'Название филиала и город на армянском обязательны (основной язык).';
    if (!$errors) {
        foreach (['city', 'name', 'address'] as $k) foreach (['ru', 'en'] as $l) if ($v["{$k}_$l"] === '') $v["{$k}_$l"] = $v["{$k}_hy"];
        try {
            if ($id) {
                db()->prepare('UPDATE branches SET bank_code=?, slug=?, city_hy=?, city_ru=?, city_en=?, name_hy=?, name_ru=?, name_en=?, address_hy=?, address_ru=?, address_en=?, phone=?, is_active=? WHERE id=?')
                    ->execute([$v['bank_code'], $v['slug'], $v['city_hy'], $v['city_ru'], $v['city_en'], $v['name_hy'], $v['name_ru'], $v['name_en'], $v['address_hy'], $v['address_ru'], $v['address_en'], $v['phone'], $v['is_active'], $id]);
            } else {
                $max = (int)db()->query('SELECT COALESCE(MAX(sort),0) FROM branches WHERE bank_code = ' . db()->quote($v['bank_code']))->fetchColumn();
                db()->prepare('INSERT INTO branches (bank_code, slug, city_hy, city_ru, city_en, name_hy, name_ru, name_en, address_hy, address_ru, address_en, phone, sort, is_active) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)')
                    ->execute([$v['bank_code'], $v['slug'], $v['city_hy'], $v['city_ru'], $v['city_en'], $v['name_hy'], $v['name_ru'], $v['name_en'], $v['address_hy'], $v['address_ru'], $v['address_en'], $v['phone'], $max + 10, $v['is_active']]);
            }
            adm_content_changed();
            flash('ok', 'Сохранено.');
            redirect('/admin/branches.php?bank=' . urlencode($v['bank_code']));
        } catch (PDOException $e) {
            $errors[] = 'У этого банка уже есть филиал с таким адресом страницы (slug).';
        }
    }
    $row = $v;
}
adm_head($id ? 'Изменить филиал' : 'Новый филиал', 'branches', $user);
?>
<nav class="mb-4 text-sm"><a href="/admin/branches.php?bank=<?= e(urlencode($row['bank_code'])) ?>" title="Филиалы" class="font-semibold">← Филиалы</a></nav>
<h1 class="text-2xl font-black tracking-tight text-slate-900 sm:text-3xl"><?= $id ? 'Изменить филиал' : 'Новый филиал' ?></h1>
<?php if ($errors): ?><div role="alert" class="mt-5 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-900"><?php foreach ($errors as $er): ?><p><?= e($er) ?></p><?php endforeach; ?></div><?php endif; ?>
<form method="post" class="mt-6 grid gap-5 pb-24 lg:pb-6">
    <?= csrf_field() ?><input type="hidden" name="id" value="<?= $id ?>">
    <section class="<?= ADM_CARD ?>">
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <label class="block text-sm font-bold" for="b_bank">Банк
                <select id="b_bank" name="bank_code" class="<?= ADM_INP ?>"><?php foreach ($banks as $b): ?><option value="<?= e($b['code']) ?>" <?= $b['code'] === $row['bank_code'] ? 'selected' : '' ?>><?= e($b['name_ru'] ?: $b['name_en'] ?: $b['code']) ?></option><?php endforeach; ?></select></label>
            <?= adm_field('b_slug', 'slug', 'Адрес страницы (латиницей)', (string)$row['slug'], false, 1, 'placeholder="yerevan-center"') ?>
            <?= adm_field('b_phone', 'phone', 'Телефон', (string)$row['phone']) ?>
        </div>
        <div class="mt-2"><?= adm_switch('is_active', (bool)$row['is_active'], 'Показывать на сайте', 'b_act') ?></div>
        <?php if ($id): ?><p class="mt-1 text-sm">Страница на сайте: <span class="font-mono text-blue-700">/<?= e($row['bank_code']) ?>/<?= e($row['slug']) ?></span>. После смены slug старый адрес перестанет открываться.</p><?php endif; ?>
    </section>
    <?php foreach (ADM_LANGS as $l => $ln): ?>
    <section class="<?= ADM_CARD ?>">
        <h2 class="text-lg font-black text-slate-900"><?= e($ln) ?></h2>
        <div class="mt-4 grid gap-4 sm:grid-cols-3">
            <?= adm_field("b_name_$l", "name_$l", 'Название филиала', (string)$row["name_$l"]) ?>
            <?= adm_field("b_city_$l", "city_$l", 'Город', (string)$row["city_$l"]) ?>
            <?= adm_field("b_addr_$l", "address_$l", 'Адрес', (string)$row["address_$l"]) ?>
        </div>
    </section>
    <?php endforeach; ?>
    <div class="fixed inset-x-0 bottom-0 z-30 border-t border-slate-200 bg-white/95 p-3 backdrop-blur lg:static lg:border-0 lg:bg-transparent lg:p-0">
        <div class="mx-auto flex max-w-6xl gap-3 px-1 lg:px-0"><button type="submit" class="<?= ADM_BTN ?> flex-1 lg:flex-none">Сохранить</button><a href="/admin/branches.php?bank=<?= e(urlencode($row['bank_code'])) ?>" title="Филиалы" class="<?= ADM_BTN2 ?>">Отмена</a></div>
    </div>
</form>
<?php if ($id): ?>
<form method="post" action="/admin/branches.php" class="mb-24 lg:mb-0" onsubmit="return confirm('Удалить филиал? Его страница перестанет открываться.');">
    <?= csrf_field() ?><input type="hidden" name="id" value="<?= $id ?>"><input type="hidden" name="bank" value="<?= e($row['bank_code']) ?>"><input type="hidden" name="action" value="delete">
    <button type="submit" class="min-h-[44px] rounded-xl border border-red-200 bg-white px-5 text-sm font-bold text-red-700 hover:bg-red-50">Удалить филиал</button>
</form>
<?php endif; ?>
<?php adm_foot(); ?>
