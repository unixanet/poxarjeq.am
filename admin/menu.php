<?php
declare(strict_types=1);
require __DIR__ . '/_boot.php';
require __DIR__ . '/_layout.php';
require __DIR__ . '/_forms.php';
$user = adm_require();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $id = (int)($_POST['id'] ?? 0);
    $act = (string)($_POST['action'] ?? '');
    $pdo = db();
    $st = $pdo->prepare('SELECT * FROM nav_links WHERE id = ?'); $st->execute([$id]); $row = $st->fetch();
    if ($row) {
        if ($act === 'delete') { $pdo->prepare('DELETE FROM nav_links WHERE id = ?')->execute([$id]); flash('ok', 'Ссылка удалена.'); }
        elseif ($act === 'toggle') { $pdo->prepare('UPDATE nav_links SET is_active = 1 - is_active WHERE id = ?')->execute([$id]); flash('ok', 'Готово.'); }
        elseif ($act === 'up' || $act === 'down') {
            $st = $pdo->prepare('SELECT id FROM nav_links WHERE area = ? AND grp = ? ORDER BY sort, id'); $st->execute([$row['area'], $row['grp']]);
            $ids = array_map('intval', $st->fetchAll(PDO::FETCH_COLUMN));
            $i = array_search($id, $ids, true); $j = $act === 'up' ? $i - 1 : $i + 1;
            if ($i !== false && isset($ids[$j])) { [$ids[$i], $ids[$j]] = [$ids[$j], $ids[$i]]; }
            $u = $pdo->prepare('UPDATE nav_links SET sort = ? WHERE id = ?');
            foreach ($ids as $n => $x) $u->execute([($n + 1) * 10, $x]);
        }
        adm_content_changed();
    }
    redirect('/admin/menu.php');
}

$all = db()->query('SELECT * FROM nav_links ORDER BY area, grp, sort, id')->fetchAll();
$by = [];
foreach ($all as $r) $by[$r['area']][$r['grp']][] = $r;

function adm_menu_btn(int $id, string $action, string $label, string $cls, string $aria = ''): string
{
    return '<form method="post" class="inline">' . csrf_field() . '<input type="hidden" name="id" value="' . $id . '"><input type="hidden" name="action" value="' . e($action) . '"><button type="submit" class="' . $cls . '"' . ($aria ? ' aria-label="' . e($aria) . '" title="' . e($aria) . '"' : '') . '>' . $label . '</button></form>';
}
$sq = 'grid h-10 w-10 place-items-center rounded-xl border border-slate-200 bg-white text-lg font-bold hover:border-blue-300';
$titles = ['header' => 'Меню в шапке', 'footer' => 'Ссылки в подвале'];

adm_head('Меню и подвал', 'menu', $user);
?>
<div class="flex flex-wrap items-end justify-between gap-3">
    <div>
        <h1 class="text-2xl font-black tracking-tight text-slate-900 sm:text-3xl">Меню и подвал</h1>
        <p class="mt-1 max-w-3xl text-black">Любую ссылку можно изменить: текст на трёх языках, адрес и подсказку (title). Заголовки колонок подвала меняются в «Страницы и тексты → Подвал».</p>
    </div>
    <a href="/admin/menu-edit.php" title="Добавить ссылку" class="<?= ADM_BTN ?>">+ Добавить ссылку</a>
</div>

<?php foreach (['header', 'footer'] as $area): ?>
<section class="mt-8">
    <h2 class="text-xl font-black text-slate-900"><?= e($titles[$area]) ?></h2>
    <?php foreach ($by[$area] ?? [] as $grp => $rows): ?>
    <div class="mt-3 <?= ADM_CARD ?> !p-3 sm:!p-4">
        <?php if ($area === 'footer'): ?><h3 class="px-2 pb-2 pt-1 text-sm font-extrabold text-slate-900">Колонка <?= (int)$grp ?>: <?= e(cms_default('footer.col' . min(max((int)$grp, 1), 4), 'ru')) ?></h3><?php endif; ?>
        <ul class="divide-y divide-slate-100">
            <?php foreach ($rows as $i => $r): ?>
            <li class="flex flex-wrap items-center gap-3 px-2 py-3 <?= $r['is_active'] ? '' : 'opacity-60' ?>">
                <div class="min-w-0 flex-1 basis-56">
                    <div class="truncate font-bold text-slate-900"><?= e($r['label_hy'] ?: $r['label_ru'] ?: $r['url']) ?> <span class="font-normal text-slate-600">· <?= e($r['label_ru']) ?> · <?= e($r['label_en']) ?></span></div>
                    <div class="truncate font-mono text-xs text-blue-700"><?= e($r['url']) ?><?= $r['new_tab'] ? ' · новая вкладка' : '' ?><?= $r['nofollow'] ? ' · nofollow' : '' ?><?= $r['is_active'] ? '' : ' · скрыта' ?></div>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <?= adm_menu_btn((int)$r['id'], 'up', '↑', $sq . ($i === 0 ? ' pointer-events-none opacity-30' : ''), 'Выше') ?>
                    <?= adm_menu_btn((int)$r['id'], 'down', '↓', $sq . ($i === count($rows) - 1 ? ' pointer-events-none opacity-30' : ''), 'Ниже') ?>
                    <?= adm_menu_btn((int)$r['id'], 'toggle', $r['is_active'] ? 'Скрыть' : 'Показать', 'min-h-[40px] rounded-xl border border-slate-200 bg-white px-3.5 text-sm font-bold') ?>
                    <a href="/admin/menu-edit.php?id=<?= (int)$r['id'] ?>" title="Изменить ссылку" class="inline-flex min-h-[40px] items-center rounded-xl bg-blue-600 px-4 text-sm font-bold text-white no-underline hover:bg-blue-700">Изменить</a>
                </div>
            </li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endforeach; ?>
</section>
<?php endforeach; ?>
<?php adm_foot(); ?>
