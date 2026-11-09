<?php
declare(strict_types=1);
require __DIR__ . '/_boot.php';
require __DIR__ . '/_layout.php';
$user = adm_require();
adm_sync_api_banks();

function adm_partner_ids(): array
{
    return db()->query('SELECT id FROM banks WHERE is_partner = 1 ORDER BY partner_order, id')->fetchAll(PDO::FETCH_COLUMN);
}
function adm_renumber(array $ids): void
{
    $st = db()->prepare('UPDATE banks SET partner_order = ? WHERE id = ?');
    foreach (array_values($ids) as $i => $id) $st->execute([($i + 1) * 10, $id]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $id = (int)($_POST['id'] ?? 0);
    $action = (string)($_POST['action'] ?? '');
    $st = db()->prepare('SELECT * FROM banks WHERE id = ?');
    $st->execute([$id]);
    $b = $st->fetch();
    if ($b) {
        $name = adm_bank_name($b);
        if ($action === 'partner_on') {
            db()->prepare('UPDATE banks SET is_partner = 1, partner_order = 100000 WHERE id = ?')->execute([$id]);
            adm_renumber(adm_partner_ids());
            flash('ok', "«{$name}» теперь партнёр и показывается в начале списка.");
        } elseif ($action === 'partner_off') {
            db()->prepare('UPDATE banks SET is_partner = 0 WHERE id = ?')->execute([$id]);
            adm_renumber(adm_partner_ids());
            flash('ok', "«{$name}» больше не партнёр.");
        } elseif ($action === 'active_on' || $action === 'active_off') {
            db()->prepare('UPDATE banks SET is_active = ? WHERE id = ?')->execute([$action === 'active_on' ? 1 : 0, $id]);
            flash('ok', $action === 'active_on' ? "«{$name}» снова показывается на сайте." : "«{$name}» скрыт из списка курсов.");
        } elseif ($action === 'up' || $action === 'down') {
            $ids = adm_partner_ids();
            $i = array_search($id, array_map('intval', $ids), true);
            $j = $action === 'up' ? $i - 1 : $i + 1;
            if ($i !== false && isset($ids[$j])) {
                [$ids[$i], $ids[$j]] = [$ids[$j], $ids[$i]];
                adm_renumber($ids);
                flash('ok', 'Порядок партнёров обновлён.');
            }
        }
        bank_store_clear_cache();
    }
    redirect('/admin/banks.php' . (!empty($_POST['q']) ? '?q=' . urlencode((string)$_POST['q']) : ''));
}

$all = db()->query('SELECT * FROM banks ORDER BY is_partner DESC, partner_order, name_ru, code')->fetchAll();
$partnerCount = 0;
foreach ($all as $b) if ($b['is_partner']) $partnerCount++;

/** Мини-форма с одной кнопкой действия */
function adm_act(int $id, string $action, string $label, string $cls, string $aria = ''): string
{
    return '<form method="post" class="inline">' . csrf_field() . '<input type="hidden" name="id" value="' . $id . '"><input type="hidden" name="action" value="' . e($action) . '">'
         . '<button type="submit" class="' . $cls . '"' . ($aria ? ' aria-label="' . e($aria) . '"' : '') . '>' . $label . '</button></form>';
}
$arrow = 'grid h-10 w-10 place-items-center rounded-xl border border-slate-200 bg-white text-lg font-bold hover:border-blue-300';

adm_head('Банки', 'banks', $user);
?>
<div class="flex flex-wrap items-end justify-between gap-3">
    <div>
        <h1 class="text-2xl font-black tracking-tight text-slate-900 sm:text-3xl">Банки</h1>
        <p class="mt-1 text-black">Партнёры закреплены в начале списка на сайте. Остальные идут по лучшему курсу.</p>
    </div>
    <label class="relative block w-full sm:w-72">
        <span class="sr-only">Поиск банка</span>
        <input id="bank-search" type="search" placeholder="Поиск по названию" class="min-h-[46px] w-full rounded-xl border border-slate-300 bg-white px-4 text-base outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-200">
    </label>
</div>

<ul id="bank-list" class="mt-6 grid gap-3">
    <?php foreach ($all as $i => $b):
        $logo = adm_logo_src($b);
        $shown = adm_logo_display($b);
        $search = mb_strtolower(implode(' ', [$b['code'], $b['name_hy'], $b['name_ru'], $b['name_en']]));
        $isP = (int)$b['is_partner'] === 1; $isA = (int)$b['is_active'] === 1;
    ?>
    <li data-search="<?= e($search) ?>" class="rounded-2xl border bg-white p-3.5 shadow-[0_6px_24px_rgba(15,23,42,.05)] sm:p-4 <?= $isP ? 'border-amber-200 bg-amber-50/40' : 'border-slate-100' ?> <?= $isA ? '' : 'opacity-70' ?>">
        <div class="flex flex-wrap items-center gap-3 sm:flex-nowrap sm:gap-4">
            <?php if ($shown): ?>
            <img src="<?= e($shown) ?>" alt="" width="56" height="56" class="h-14 w-14 shrink-0 rounded-xl border border-slate-100 bg-white object-contain p-1">
            <?php else: ?>
            <span class="grid h-14 w-14 shrink-0 place-items-center rounded-xl border border-slate-100 bg-slate-50 text-lg font-black text-slate-800"><?= e(mb_strtoupper(mb_substr(adm_bank_name($b), 0, 1))) ?></span>
            <?php endif; ?>

            <div class="min-w-0 flex-1 basis-[60%] sm:basis-auto">
                <a href="/admin/bank-edit.php?code=<?= e(urlencode($b['code'])) ?>" class="block truncate text-[17px] font-extrabold text-slate-900 no-underline hover:text-blue-600"><?= e(adm_bank_name($b)) ?></a>
                <div class="mt-0.5 flex flex-wrap items-center gap-x-2 gap-y-1 text-xs">
                    <span class="font-mono"><?= e($b['code']) ?></span>
                    <?php if ($isP): ?><span class="rounded-full bg-amber-100 px-2 py-0.5 font-extrabold uppercase tracking-wide text-amber-800">★ Партнёр № <?= $i + 1 ?></span><?php endif; ?>
                    <?php if (!$isA): ?><span class="rounded-full bg-slate-200 px-2 py-0.5 font-extrabold uppercase tracking-wide text-slate-800">Скрыт</span><?php endif; ?>
                    <?php if (!$logo): ?><span class="rounded-full bg-blue-50 px-2 py-0.5 font-bold text-blue-700">логотип из API</span><?php endif; ?>
                </div>
            </div>

            <div class="flex w-full flex-wrap items-center gap-2 border-t border-slate-100 pt-3 sm:w-auto sm:flex-nowrap sm:border-0 sm:pt-0">
                <?php if ($isP): ?>
                    <?= adm_act((int)$b['id'], 'up', '↑', $arrow . ($i === 0 ? ' pointer-events-none opacity-30' : ''), 'Выше') ?>
                    <?= adm_act((int)$b['id'], 'down', '↓', $arrow . ($i === $partnerCount - 1 ? ' pointer-events-none opacity-30' : ''), 'Ниже') ?>
                    <?= adm_act((int)$b['id'], 'partner_off', 'Убрать из партнёров', 'min-h-[40px] rounded-xl border border-amber-300 bg-white px-3.5 text-sm font-bold text-amber-900 hover:bg-amber-50') ?>
                <?php else: ?>
                    <?= adm_act((int)$b['id'], 'partner_on', '★ Сделать партнёром', 'min-h-[40px] rounded-xl border border-slate-200 bg-white px-3.5 text-sm font-bold text-black hover:border-amber-300 hover:bg-amber-50') ?>
                <?php endif; ?>
                <?= adm_act((int)$b['id'], $isA ? 'active_off' : 'active_on', $isA ? 'Скрыть' : 'Показать', 'min-h-[40px] rounded-xl border border-slate-200 bg-white px-3.5 text-sm font-bold text-black hover:border-blue-300') ?>
                <a href="/admin/bank-edit.php?code=<?= e(urlencode($b['code'])) ?>" class="ml-auto inline-flex min-h-[40px] items-center rounded-xl bg-blue-600 px-4 text-sm font-bold no-underline hover:bg-blue-700 sm:ml-0 text-white">Изменить</a>
            </div>
        </div>
    </li>
    <?php endforeach; ?>
</ul>
<p id="bank-empty" class="mt-6 hidden rounded-2xl border border-slate-100 bg-white p-8 text-center">Ничего не найдено.</p>

<script>
(function(){
    var q=document.getElementById('bank-search'),items=[].slice.call(document.querySelectorAll('#bank-list > li')),empty=document.getElementById('bank-empty');
    function f(){var v=q.value.trim().toLowerCase(),n=0;items.forEach(function(li){var ok=!v||li.getAttribute('data-search').indexOf(v)>=0;li.classList.toggle('hidden',!ok);if(ok)n++;});empty.classList.toggle('hidden',n>0);}
    q.addEventListener('input',f);
    var p=new URLSearchParams(location.search).get('q');if(p){q.value=p;f();}
})();
</script>
<?php adm_foot(); ?>
