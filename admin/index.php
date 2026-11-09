<?php
declare(strict_types=1);
require __DIR__ . '/_boot.php';
require __DIR__ . '/_layout.php';
$user = adm_require();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'clear_cache') {
    csrf_verify();
    $n = 0;
    foreach (glob(CACHE_DIR . '/*.json') ?: [] as $f) { if (@unlink($f)) $n++; }
    @unlink(CACHE_DIR . '/api_sync.stamp');
    flash('ok', 'Кэш очищен (файлов: ' . $n . '). Курсы загрузятся заново при следующем визите.');
    redirect('/admin/');
}

adm_sync_api_banks();
$st = db()->query('SELECT COUNT(*) total, SUM(is_active) active, SUM(is_partner) partners, SUM(logo_path IS NOT NULL AND logo_path <> "") logos FROM banks')->fetch();
$partners = db()->query('SELECT * FROM banks WHERE is_partner = 1 ORDER BY partner_order, id')->fetchAll();

$files = glob(CACHE_DIR . '/*.json') ?: [];
$newest = 0;
foreach ($files as $f) $newest = max($newest, (int)filemtime($f));
$age = $newest ? (int)round((time() - $newest) / 60) : null;

adm_head('Обзор', 'dashboard', $user);
?>
<h1 class="text-2xl font-black tracking-tight text-slate-900 sm:text-3xl">Обзор</h1>
<p class="mt-1 text-black">Здесь видно, что показывается на сайте и как работает загрузка курсов.</p>

<div class="mt-6 grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-4">
    <?php foreach ([['Банков', $st['total'], 'в списке'], ['Показываются', $st['active'], 'на сайте'], ['Партнёры', $st['partners'], 'сверху списка'], ['Свои логотипы', $st['logos'], 'загружено']] as [$l, $v, $s]): ?>
    <div class="rounded-2xl border border-slate-100 bg-white p-4 shadow-[0_6px_24px_rgba(15,23,42,.05)] sm:p-5">
        <div class="text-xs font-bold uppercase tracking-wider"><?= e($l) ?></div>
        <div class="mt-1 text-3xl font-black tabular-nums text-slate-900"><?= (int)$v ?></div>
        <div class="text-xs"><?= e($s) ?></div>
    </div>
    <?php endforeach; ?>
</div>

<div class="mt-6 grid gap-5 lg:grid-cols-2">
    <section class="rounded-3xl border border-slate-100 bg-white p-5 shadow-[0_10px_40px_rgba(15,23,42,.06)] sm:p-6">
        <div class="flex items-center justify-between gap-3">
            <h2 class="text-lg font-black text-slate-900">Партнёры в списке</h2>
            <a href="/admin/banks.php" class="text-sm font-bold">Управлять</a>
        </div>
        <?php if ($partners): ?>
        <ol class="mt-4 divide-y divide-slate-100">
            <?php foreach ($partners as $i => $p): $logo = adm_logo_src($p); ?>
            <li class="flex items-center gap-3 py-3">
                <span class="w-5 text-sm font-bold tabular-nums"><?= $i + 1 ?></span>
                <?php if ($logo): ?><img src="<?= e($logo) ?>" alt="" class="h-10 w-10 rounded-lg object-contain"><?php else: ?><span class="grid h-10 w-10 place-items-center rounded-lg bg-slate-100 text-sm font-black"><?= e(mb_strtoupper(mb_substr(adm_bank_name($p), 0, 1))) ?></span><?php endif; ?>
                <a href="/admin/bank-edit.php?code=<?= e(urlencode($p['code'])) ?>" class="min-w-0 flex-1 truncate font-bold"><?= e(adm_bank_name($p)) ?></a>
            </li>
            <?php endforeach; ?>
        </ol>
        <?php else: ?>
        <p class="mt-3 text-black">Пока нет партнёров. Откройте раздел «Банки» и включите тумблер «Партнёр».</p>
        <?php endif; ?>
    </section>

    <section class="rounded-3xl border border-slate-100 bg-white p-5 shadow-[0_10px_40px_rgba(15,23,42,.06)] sm:p-6">
        <h2 class="text-lg font-black text-slate-900">Курсы и кэш</h2>
        <dl class="mt-4 space-y-3 text-[15px]">
            <div class="flex justify-between gap-4"><dt>Файлов в кэше</dt><dd class="font-bold tabular-nums"><?= count($files) ?></dd></div>
            <div class="flex justify-between gap-4"><dt>Последнее обновление</dt><dd class="font-bold"><?= $age === null ? 'нет данных' : ($age < 1 ? 'только что' : $age . ' мин назад') ?></dd></div>
            <div class="flex justify-between gap-4"><dt>Источник курсов</dt><dd class="min-w-0 truncate font-bold"><?= e(preg_replace('#^https?://#', '', API_BASE)) ?></dd></div>
        </dl>
        <form method="post" class="mt-5">
            <?= csrf_field() ?><input type="hidden" name="action" value="clear_cache">
            <button type="submit" class="min-h-[46px] w-full rounded-xl border border-slate-200 bg-white px-5 text-sm font-bold text-black hover:border-blue-300 sm:w-auto">Сбросить кэш курсов</button>
        </form>
    </section>
</div>
<?php adm_foot(); ?>
