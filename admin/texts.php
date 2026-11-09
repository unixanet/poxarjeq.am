<?php
declare(strict_types=1);
require __DIR__ . '/_boot.php';
require __DIR__ . '/_layout.php';
require __DIR__ . '/_forms.php';
$user = adm_require();

$defs = cms_defs();
$groups = $defs['groups'];
$g = (string)($_GET['g'] ?? '');

/** Все ключи группы: [key => ['s'=>секция,'l'=>подпись,'long'=>bool,'h'=>подсказка]] — SEO-секция первой */
function adm_group_keys(array $defs, string $g): array
{
    $items = [];
    foreach ($defs['keys'] as $k => $m) if ($m['g'] === $g) $items[$k] = ['s' => $m['s'], 'l' => $m['l'], 'long' => !empty($m['long']), 'h' => $m['h'] ?? ''];
    foreach ($defs['legacy'] as $k => $m) if ($m['g'] === $g) $items[$k] = ['s' => $m['s'], 'l' => $m['l'], 'long' => !empty($m['long']), 'h' => ''];
    uasort($items, fn($a, $b) => ($a['s'] === 'SEO' ? 0 : 1) <=> ($b['s'] === 'SEO' ? 0 : 1));
    return $items;
}

if ($g !== '' && !isset($groups[$g])) { flash('error', 'Страница не найдена.'); redirect('/admin/texts.php'); }

if ($g !== '' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $items = adm_group_keys($defs, $g);
    $posted = $_POST['t'] ?? [];
    $pdo = db();
    $up = $pdo->prepare('INSERT INTO site_texts (k, lang, value) VALUES (?,?,?) ON DUPLICATE KEY UPDATE value = VALUES(value)');
    $del = $pdo->prepare('DELETE FROM site_texts WHERE k = ? AND lang = ?');
    $changed = 0;
    $pdo->beginTransaction();
    foreach ($items as $k => $m) {
        foreach (SUPPORTED_LANGS as $l) {
            if (!isset($posted[$k][$l])) continue;
            $v = trim(str_replace("\r\n", "\n", (string)$posted[$k][$l]));
            $v = mb_substr($v, 0, 8000);
            $dflt = (string)cms_default($k, $l);
            if ($v === '' || $v === trim($dflt)) { $del->execute([$k, $l]); }
            else { $up->execute([$k, $l, $v]); $changed++; }
        }
    }
    $pdo->commit();
    adm_content_changed();
    flash('ok', 'Сохранено. Изменения уже на сайте (кэш обновится в течение минуты).');
    redirect('/admin/texts.php?g=' . urlencode($g));
}

adm_head($g ? $groups[$g]['title'] : 'Страницы и тексты', 'texts', $user);

if ($g === ''):
    $over = cms_overrides();
    $custom = [];
    foreach ($over as $l => $kv) foreach ($kv as $k => $v) $custom[$k] = true;
?>
<h1 class="text-2xl font-black tracking-tight text-slate-900 sm:text-3xl">Страницы и тексты</h1>
<p class="mt-1 max-w-3xl text-black">Выберите страницу: можно изменить заголовок и описание для поисковиков (SEO) и любой текст на ней. Основной язык сайта армянский, русский и английский заполняются так же. Пустое поле возвращает стандартный текст.</p>
<div class="mt-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
    <?php foreach ($groups as $key => $gr):
        $keys = adm_group_keys($defs, $key);
        $n = count($keys); $c = count(array_intersect_key($keys, $custom));
    ?>
    <a href="/admin/texts.php?g=<?= e($key) ?>" title="<?= e($gr['title']) ?>" class="block rounded-2xl border border-slate-100 bg-white p-5 no-underline shadow-[0_6px_24px_rgba(15,23,42,.05)] transition hover:border-blue-300 hover:shadow-[0_10px_30px_rgba(37,99,235,.12)]">
        <span class="block text-[17px] font-extrabold text-slate-900"><?= e($gr['title']) ?></span>
        <span class="mt-1 block text-sm text-black"><?= $n ?> текстов<?= $c ? ', изменено: ' . $c : '' ?></span>
        <?php if ($gr['url']): ?><span class="mt-2 block break-all font-mono text-xs text-blue-600"><?= e($gr['url']) ?></span><?php endif; ?>
    </a>
    <?php endforeach; ?>
    <a href="/admin/currencies.php" title="Валюты" class="block rounded-2xl border border-dashed border-blue-300 bg-blue-50 p-5 no-underline"><span class="block text-[17px] font-extrabold text-slate-900">Тексты отдельных валют</span><span class="mt-1 block text-sm text-black">Свой заголовок, описание и статья для каждой валюты</span></a>
    <a href="/admin/banks.php" title="Банки" class="block rounded-2xl border border-dashed border-blue-300 bg-blue-50 p-5 no-underline"><span class="block text-[17px] font-extrabold text-slate-900">Тексты отдельных банков</span><span class="mt-1 block text-sm text-black">Название, контакты, «О банке», SEO каждого банка</span></a>
    <a href="/admin/menu.php" title="Меню и подвал" class="block rounded-2xl border border-dashed border-blue-300 bg-blue-50 p-5 no-underline"><span class="block text-[17px] font-extrabold text-slate-900">Меню и ссылки подвала</span><span class="mt-1 block text-sm text-black">Текст, адрес и подсказка каждой ссылки</span></a>
</div>
<?php else:
    $gr = $groups[$g];
    $items = adm_group_keys($defs, $g);
    $over = cms_overrides();
    $sections = [];
    foreach ($items as $k => $m) $sections[$m['s']][$k] = $m;
?>
<nav class="mb-4 text-sm"><a href="/admin/texts.php" title="Страницы и тексты" class="font-semibold">← Все страницы</a></nav>
<div class="flex flex-wrap items-center gap-3">
    <h1 class="min-w-0 flex-1 text-2xl font-black tracking-tight text-slate-900 sm:text-3xl"><?= e($gr['title']) ?></h1>
    <?php if ($gr['url']): ?><a href="<?= e($gr['url']) ?>" target="_blank" rel="noopener" title="Открыть страницу на сайте" class="<?= ADM_BTN2 ?>">Открыть на сайте ↗</a><?php endif; ?>
</div>
<p class="mt-2 max-w-3xl text-sm text-black">Сначала армянский (основной язык), затем русский и английский. Оставьте поле пустым, чтобы вернуть стандартный текст. Абзацы разделяются пустой строкой, <code class="rounded bg-slate-100 px-1">**так**</code> выделяется жирным.</p>

<form method="post" class="mt-6 grid gap-6 pb-24 lg:pb-6">
    <?= csrf_field() ?>
    <?php foreach ($sections as $sec => $keys): $isSeo = $sec === 'SEO'; ?>
    <section class="<?= ADM_CARD ?>">
        <h2 class="text-lg font-black text-slate-900"><?= $isSeo ? 'SEO: как страница выглядит в Google и Яндексе' : e($sec) ?></h2>
        <div class="mt-4 space-y-7">
            <?php foreach ($keys as $k => $m): $idb = 'f_' . md5($k); ?>
            <fieldset class="min-w-0">
                <legend class="text-[15px] font-extrabold text-slate-900"><?= e($m['l']) ?> <span class="ml-1 font-mono text-[11px] font-normal text-slate-500"><?= e($k) ?></span></legend>
                <?php if ($m['h']): ?><p class="mt-0.5 text-xs text-black"><?= e($m['h']) ?></p><?php endif; ?>
                <div class="mt-2 grid gap-3 <?= $m['long'] ? 'lg:grid-cols-1' : 'lg:grid-cols-3' ?>">
                    <?php foreach (ADM_LANGS as $l => $ln):
                        $dflt = (string)cms_default($k, $l);
                        $val = $over[$l][$k] ?? $dflt;
                        $isCustom = isset($over[$l][$k]);
                        $isTitle = $isSeo && str_ends_with($k, '.title'); $isDesc = $isSeo && str_ends_with($k, '.description');
                    ?>
                    <div class="min-w-0">
                        <?= adm_field($idb . '_' . $l, "t[$k][$l]", '<span class="flex items-center justify-between text-[11px] font-bold uppercase tracking-wider"><span>' . e($ln) . '</span>' . ($isCustom ? '<span class="rounded-full bg-blue-100 px-2 py-0.5 text-blue-800">изменено</span>' : '') . '</span>', $val, $m['long'], $m['long'] ? 4 : 2,
                            ($isTitle || $isDesc) ? 'data-seo="' . ($isTitle ? 'title' : 'desc') . '"' : '', $dflt) ?>
                        <?php if ($isTitle || $isDesc):
                            $keepLang = $lang; $lang = $l;    // подставляем плейсхолдеры на языке поля
                            $sample = cms_fill($val, ['name' => currency_name('USD', $l), 'code' => 'USD', 'bank' => cms_default('site_name', $l) ? ($l === 'hy' ? 'Ամերիաբանկ' : ($l === 'ru' ? 'Америабанк' : 'Ameriabank')) : '', 'branch' => '…', 'city' => '…', 'address' => '…', 'phone' => '…']);
                            $lang = $keepLang;
                        ?>
                        <p class="mt-1 text-xs text-slate-600" data-counter-for="<?= e($idb . '_' . $l) ?>"></p>
                        <p class="mt-1 break-words rounded-lg bg-slate-50 px-2.5 py-1.5 text-xs text-black"><span class="font-bold">Пример в поиске:</span> <?= e(trim(preg_replace('/\s+/u', ' ', $sample))) ?></p>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </fieldset>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endforeach; ?>
    <div class="fixed inset-x-0 bottom-0 z-30 border-t border-slate-200 bg-white/95 p-3 backdrop-blur lg:static lg:border-0 lg:bg-transparent lg:p-0">
        <div class="mx-auto flex max-w-6xl gap-3 px-1 lg:px-0">
            <button type="submit" class="<?= ADM_BTN ?> flex-1 lg:flex-none">Сохранить</button>
            <a href="/admin/texts.php" title="Все страницы" class="<?= ADM_BTN2 ?>">Отмена</a>
        </div>
    </div>
</form>
<script>
(function () {
    // Счётчики длины для SEO-полей (рекомендуется: заголовок до 60–70, описание 120–160 символов)
    document.querySelectorAll('[data-seo]').forEach(function (inp) {
        var out = document.querySelector('[data-counter-for="' + inp.id + '"]'), kind = inp.getAttribute('data-seo');
        var lo = kind === 'title' ? 30 : 100, hi = kind === 'title' ? 70 : 170;
        function upd() {
            var n = inp.value.replace(/\{[a-z_0-9]+\}/gi, 'ХХХХХХХХ').length;
            var ok = n >= lo && n <= hi;
            out.textContent = 'Около ' + n + ' знаков' + (ok ? ' — хорошо' : n < lo ? ' — коротковато' : ' — длинновато, Google обрежет') + ' (рекомендуется ' + lo + '–' + hi + ')';
            out.className = 'mt-1 text-xs ' + (ok ? 'text-green-700' : 'text-amber-700');
        }
        inp.addEventListener('input', upd); upd();
    });
})();
</script>
<?php endif; adm_foot(); ?>
