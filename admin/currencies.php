<?php
declare(strict_types=1);
require __DIR__ . '/_boot.php';
require __DIR__ . '/_layout.php';
require __DIR__ . '/_forms.php';
$user = adm_require();

$codes = array_values(array_unique(array_merge(all_currency_codes(), table_currency_codes())));
$custom = [];
foreach (db()->query('SELECT code, COUNT(*) n FROM currency_pages GROUP BY code') as $r) $custom[$r['code']] = (int)$r['n'];

adm_head('Валюты', 'currencies', $user);
?>
<h1 class="text-2xl font-black tracking-tight text-slate-900 sm:text-3xl">Валюты</h1>
<p class="mt-1 max-w-3xl text-black">У каждой валюты своя страница. Здесь можно задать для неё собственные название, H1, заголовок и описание для поисковиков, вводный текст и статью. Пока поле пустое, берётся общий шаблон из «Страницы и тексты → Страницы валют».</p>
<div class="mt-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
    <?php foreach ($codes as $c): ?>
    <a href="/admin/currency-edit.php?code=<?= e($c) ?>" title="<?= e(currency_name($c, 'ru')) ?>" class="flex items-center gap-3 rounded-2xl border border-slate-100 bg-white p-4 no-underline shadow-[0_6px_24px_rgba(15,23,42,.05)] transition hover:border-blue-300">
        <?= ui_flag($c, 44, 'h-11 w-11 shrink-0 rounded-full object-cover shadow') ?>
        <span class="min-w-0 flex-1"><span class="block font-extrabold text-slate-900"><?= e($c) ?> · <?= e(currency_name($c, 'hy')) ?></span>
            <span class="block text-sm text-black"><?= e(currency_name($c, 'ru')) ?></span></span>
        <?php if (!empty($custom[$c])): ?><span class="rounded-full bg-blue-100 px-2.5 py-1 text-[11px] font-extrabold text-blue-800">свои тексты</span><?php endif; ?>
    </a>
    <?php endforeach; ?>
</div>
<?php adm_foot(); ?>
