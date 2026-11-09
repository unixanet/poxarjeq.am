<?php
declare(strict_types=1);
require __DIR__ . '/_boot.php';
require __DIR__ . '/_layout.php';
require __DIR__ . '/_forms.php';
$user = adm_require();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $id = (int)($_POST['id'] ?? 0); $act = (string)($_POST['action'] ?? '');
    if ($act === 'read') db()->prepare('UPDATE contact_messages SET is_read = 1 WHERE id = ?')->execute([$id]);
    if ($act === 'delete') db()->prepare('DELETE FROM contact_messages WHERE id = ?')->execute([$id]);
    redirect('/admin/messages.php');
}
$rows = db()->query('SELECT * FROM contact_messages ORDER BY is_read, created_at DESC LIMIT 200')->fetchAll();
adm_head('Сообщения', 'messages', $user);
?>
<h1 class="text-2xl font-black tracking-tight text-slate-900 sm:text-3xl">Сообщения с сайта</h1>
<p class="mt-1 text-black">Сюда приходят сообщения из формы на странице «Контакты».</p>
<ul class="mt-6 grid gap-3">
    <?php foreach ($rows as $m): ?>
    <li class="<?= ADM_CARD ?> !p-4 <?= $m['is_read'] ? 'opacity-70' : 'border-blue-200' ?>">
        <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-sm">
            <strong class="text-slate-900"><?= e($m['name']) ?></strong>
            <a href="mailto:<?= e($m['email']) ?>" title="Ответить: <?= e($m['email']) ?>" class="break-all"><?= e($m['email']) ?></a>
            <span class="text-slate-600"><?= e($m['created_at']) ?> · <?= e(strtoupper($m['lang'])) ?></span>
            <?php if (!$m['is_read']): ?><span class="rounded-full bg-blue-100 px-2 py-0.5 text-[11px] font-extrabold text-blue-800">новое</span><?php endif; ?>
        </div>
        <p class="mt-2 whitespace-pre-line break-words text-[15px] leading-relaxed"><?= e($m['message']) ?></p>
        <div class="mt-3 flex gap-2">
            <?php if (!$m['is_read']): ?><form method="post"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$m['id'] ?>"><input type="hidden" name="action" value="read"><button class="min-h-[40px] rounded-xl border border-slate-200 bg-white px-4 text-sm font-bold">Прочитано</button></form><?php endif; ?>
            <form method="post" onsubmit="return confirm('Удалить сообщение?');"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$m['id'] ?>"><input type="hidden" name="action" value="delete"><button class="min-h-[40px] rounded-xl border border-red-200 bg-white px-4 text-sm font-bold text-red-700">Удалить</button></form>
        </div>
    </li>
    <?php endforeach; ?>
    <?php if (!$rows): ?><li class="rounded-2xl border border-slate-100 bg-white p-8 text-center">Сообщений пока нет.</li><?php endif; ?>
</ul>
<?php adm_foot(); ?>
