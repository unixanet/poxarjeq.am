<?php
declare(strict_types=1);

function adm_head(string $title, string $active = '', ?array $user = null): void
{
    $nav = [
        'dashboard'  => ['/admin/', 'Обзор', 'M3 12l9-9 9 9M5 10v10h5v-6h4v6h5V10'],
        'texts'      => ['/admin/texts.php', 'Страницы и тексты', 'M4 6h16M4 10h16M4 14h10M4 18h7'],
        'currencies' => ['/admin/currencies.php', 'Валюты', 'M12 3v18M16 7.5c0-1.4-1.8-2.5-4-2.5s-4 1.1-4 2.5 1.8 2.5 4 2.5 4 1.1 4 2.5-1.8 2.5-4 2.5-4-1.1-4-2.5'],
        'banks'      => ['/admin/banks.php', 'Банки', 'M3 10l9-6 9 6M5 10v8m4-8v8m6-8v8m4-8v8M3 20h18'],
        'branches'   => ['/admin/branches.php', 'Филиалы', 'M12 21s-7-6.2-7-11a7 7 0 1114 0c0 4.8-7 11-7 11zM12 12a2 2 0 100-4 2 2 0 000 4z'],
        'menu'       => ['/admin/menu.php', 'Меню и подвал', 'M4 6h16M4 12h16M4 18h16'],
        'messages'   => ['/admin/messages.php', 'Сообщения', 'M4 6h16v12H4zM4 7l8 6 8-6'],
        'tools'      => ['/admin/tools.php', 'SEO и llms.txt', 'M11 4a7 7 0 105.2 11.7L21 20.5M11 8v6M8 11h6'],
    ];
    ?><!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<meta name="theme-color" content="#ffffff">
<title><?= e($title) ?> — админка</title>
<link rel="icon" href="/favicon.ico">
<?php require __DIR__ . '/../includes/tw.php'; ?>
</head>
<body class="min-h-screen bg-slate-50 text-black antialiased font-sans">
<div class="lg:flex">
    <!-- Боковое меню (десктоп) -->
    <aside class="hidden lg:flex lg:sticky lg:top-0 lg:h-screen w-64 shrink-0 flex-col border-r border-slate-200 bg-white">
        <a href="/admin/" class="flex h-[68px] items-center gap-2.5 px-6 border-b border-slate-100 no-underline">
            <span class="grid h-9 w-9 place-items-center rounded-[10px] bg-blue-600 text-[19px] font-extrabold text-white">֏</span>
            <span class="font-black tracking-tight text-slate-900">Админка</span>
        </a>
        <nav class="flex-1 space-y-1 p-3" aria-label="Разделы">
            <?php foreach ($nav as $k => [$href, $label, $icon]): $on = $k === $active; ?>
            <a href="<?= e($href) ?>" class="flex min-h-[44px] items-center gap-3 rounded-xl px-3.5 text-[15px] font-semibold no-underline <?= $on ? 'bg-blue-50' : 'text-slate-800 hover:bg-slate-50' ?>" <?= $on ? 'aria-current="page"' : '' ?>>
                <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="<?= $icon ?>"/></svg><?= e($label) ?>
            </a>
            <?php endforeach; ?>
            <a href="/" target="_blank" rel="noopener" class="flex min-h-[44px] items-center gap-3 rounded-xl px-3.5 text-[15px] font-semibold no-underline hover:bg-slate-50">
                <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M14 4h6v6M20 4l-9 9M18 14v5a1 1 0 01-1 1H5a1 1 0 01-1-1V7a1 1 0 011-1h5"/></svg>Открыть сайт
            </a>
        </nav>
        <?php if ($user): ?>
        <form method="post" action="/admin/logout.php" class="border-t border-slate-100 p-4">
            <?= csrf_field() ?>
            <p class="mb-2 truncate text-sm font-semibold text-slate-900"><?= e($user['username']) ?></p>
            <button type="submit" class="min-h-[40px] w-full rounded-xl border border-slate-200 bg-white text-sm font-bold text-black hover:bg-slate-50">Выйти</button>
        </form>
        <?php endif; ?>
    </aside>

    <div class="min-w-0 flex-1">
        <!-- Верхняя панель (телефон/планшет) -->
        <header class="sticky top-0 z-40 border-b border-slate-200 bg-white/95 backdrop-blur lg:hidden">
            <div class="flex h-14 items-center gap-3 px-4">
                <button type="button" id="adm-burger" class="grid h-10 w-10 place-items-center rounded-xl border border-slate-200 bg-white" aria-label="Меню" aria-expanded="false" aria-controls="adm-menu">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" viewBox="0 0 24 24"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
                </button>
                <span class="grid h-8 w-8 place-items-center rounded-lg bg-blue-600 text-[17px] font-extrabold text-white">֏</span>
                <span class="font-black tracking-tight text-slate-900"><?= e($title) ?></span>
            </div>
            <div id="adm-menu" class="hidden border-t border-slate-100 bg-white p-3 shadow-lg">
                <?php foreach ($nav as $k => [$href, $label, $icon]): $on = $k === $active; ?>
                <a href="<?= e($href) ?>" class="flex min-h-[48px] items-center rounded-xl px-4 text-[15px] font-semibold no-underline <?= $on ? 'bg-blue-50' : 'text-slate-800' ?>"><?= e($label) ?></a>
                <?php endforeach; ?>
                <a href="/" target="_blank" rel="noopener" class="flex min-h-[48px] items-center rounded-xl px-4 text-[15px] font-semibold no-underline">Открыть сайт</a>
                <?php if ($user): ?>
                <form method="post" action="/admin/logout.php" class="mt-1 border-t border-slate-100 pt-2"><?= csrf_field() ?>
                    <button type="submit" class="min-h-[48px] w-full rounded-xl px-4 text-left text-[15px] font-semibold">Выйти (<?= e($user['username']) ?>)</button>
                </form>
                <?php endif; ?>
            </div>
        </header>

        <main class="mx-auto max-w-6xl px-4 py-6 sm:px-6 lg:px-8 lg:py-10">
            <?php foreach (flash_take() as [$type, $msg]): ?>
            <div role="status" class="mb-5 rounded-2xl border px-4 py-3 text-sm font-semibold <?= $type === 'error' ? 'border-red-200 bg-red-50 text-red-900' : 'border-green-200 bg-green-50 text-green-900' ?>"><?= e($msg) ?></div>
            <?php endforeach; ?>
<?php
}

function adm_foot(): void
{
    ?>
        </main>
    </div>
</div>
<script>
(function(){var b=document.getElementById('adm-burger'),m=document.getElementById('adm-menu');if(!b)return;b.addEventListener('click',function(){var h=m.classList.toggle('hidden');b.setAttribute('aria-expanded',h?'false':'true');});})();
</script>
</body>
</html>
<?php
}

/** Переключатель-тумблер на Tailwind (peer) */
function adm_switch(string $name, bool $on, string $label, string $id): string
{
    return '<label for="' . e($id) . '" class="flex min-h-[44px] cursor-pointer items-center gap-3">'
         . '<span class="relative inline-flex shrink-0 items-center">'
         . '<input type="checkbox" id="' . e($id) . '" name="' . e($name) . '" value="1" class="peer sr-only"' . ($on ? ' checked' : '') . '>'
         . '<span class="h-7 w-12 rounded-full bg-slate-300 transition peer-checked:bg-blue-600 peer-focus-visible:ring-2 peer-focus-visible:ring-blue-400 peer-focus-visible:ring-offset-2 after:absolute after:left-0.5 after:top-0.5 after:h-6 after:w-6 after:rounded-full after:bg-white after:shadow after:transition peer-checked:after:translate-x-5"></span></span>'
         . '<span class="text-[15px] font-semibold text-slate-900">' . e($label) . '</span></label>';
}
