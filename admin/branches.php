<?php
declare(strict_types=1);
require __DIR__ . '/_boot.php';
require __DIR__ . '/_layout.php';
require __DIR__ . '/_forms.php';
$user = adm_require();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $id = (int)($_POST['id'] ?? 0); $act = (string)($_POST['action'] ?? '');
    if ($act === 'toggle') db()->prepare('UPDATE branches SET is_active = 1 - is_active WHERE id = ?')->execute([$id]);
    if ($act === 'delete') db()->prepare('DELETE FROM branches WHERE id = ?')->execute([$id]);
    adm_content_changed();
    flash('ok', $act === 'delete' ? 'Филиал удалён.' : 'Готово.');
    redirect('/admin/branches.php?bank=' . urlencode((string)($_POST['bank'] ?? '')));
}
$banks = db()->query('SELECT code, name_ru, name_en, name_hy FROM banks ORDER BY name_ru, code')->fetchAll();
$bank = (string)($_GET['bank'] ?? ($banks[0]['code'] ?? ''));
$st = db()->prepare('SELECT * FROM branches WHERE bank_code = ? ORDER BY sort, id'); $st->execute([$bank]);
$rows = $st->fetchAll();

adm_head('Филиалы', 'branches', $user);
?>
<div class="flex flex-wrap items-end justify-between gap-3">
    <div>
        <h1 class="text-2xl font-black tracking-tight text-slate-900 sm:text-3xl">Филиалы</h1>
        <p class="mt-1 max-w-3xl text-black">Каждый филиал — своя страница на сайте. Изменение адреса в латинице (slug) меняет адрес страницы, поэтому делайте это осторожно.</p>
    </div>
    <a href="/admin/branch-edit.php?bank=<?= e(urlencode($bank)) ?>" title="Добавить филиал" class="<?= ADM_BTN ?>">+ Добавить филиал</a>
</div>
<form method="get" class="mt-5 max-w-sm">
    <label class="block text-sm font-bold" for="bank">Банк
        <select id="bank" name="bank" onchange="this.form.submit()" class="<?= ADM_INP ?>">
            <?php foreach ($banks as $b): ?><option value="<?= e($b['code']) ?>" <?= $b['code'] === $bank ? 'selected' : '' ?>><?= e($b['name_ru'] ?: $b['name_en'] ?: $b['name_hy'] ?: $b['code']) ?></option><?php endforeach; ?>
        </select></label>
</form>
<ul class="mt-5 grid gap-3">
    <?php foreach ($rows as $r): ?>
    <li class="<?= ADM_CARD ?> !p-4 <?= $r['is_active'] ? '' : 'opacity-60' ?>">
        <div class="flex flex-wrap items-center gap-3">
            <div class="min-w-0 flex-1 basis-56">
                <div class="font-extrabold text-slate-900"><?= e($r['name_hy'] ?: $r['name_ru']) ?> <span class="font-normal text-slate-600">· <?= e($r['city_hy'] ?: $r['city_ru']) ?></span></div>
                <div class="text-sm text-black"><?= e($r['address_hy'] ?: $r['address_ru']) ?><?= $r['phone'] ? ' · ' . e($r['phone']) : '' ?></div>
                <div class="font-mono text-xs text-blue-700">/<?= e($r['bank_code']) ?>/<?= e($r['slug']) ?><?= $r['is_active'] ? '' : ' · скрыт' ?></div>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <form method="post" class="inline"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><input type="hidden" name="bank" value="<?= e($bank) ?>"><input type="hidden" name="action" value="toggle"><button type="submit" class="min-h-[40px] rounded-xl border border-slate-200 bg-white px-3.5 text-sm font-bold"><?= $r['is_active'] ? 'Скрыть' : 'Показать' ?></button></form>
                <a href="/admin/branch-edit.php?id=<?= (int)$r['id'] ?>" title="Изменить филиал" class="inline-flex min-h-[40px] items-center rounded-xl bg-blue-600 px-4 text-sm font-bold text-white no-underline hover:bg-blue-700">Изменить</a>
            </div>
        </div>
    </li>
    <?php endforeach; ?>
    <?php if (!$rows): ?><li class="rounded-2xl border border-slate-100 bg-white p-8 text-center">У этого банка пока нет филиалов.</li><?php endif; ?>
</ul>
<?php adm_foot(); ?>
