<?php
declare(strict_types=1);
require __DIR__ . '/_boot.php';
require __DIR__ . '/_layout.php';
require __DIR__ . '/_forms.php';
$user = adm_require();

$code = strtoupper(preg_replace('/[^A-Za-z]/', '', (string)($_GET['code'] ?? $_POST['code'] ?? '')));
if (strlen($code) !== 3) { flash('error', 'Валюта не найдена.'); redirect('/admin/currencies.php'); }

$fields = ['name', 'h1', 'seo_title', 'seo_description', 'lead', 'about_title', 'about_body'];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $pdo = db();
    foreach (SUPPORTED_LANGS as $l) {
        $row = [];
        foreach ($fields as $f) $row[$f] = mb_substr(trim(str_replace("\r\n", "\n", (string)($_POST[$f][$l] ?? ''))), 0, in_array($f, ['lead', 'about_body'], true) ? 20000 : 500);
        if (!array_filter($row)) { $pdo->prepare('DELETE FROM currency_pages WHERE code = ? AND lang = ?')->execute([$code, $l]); continue; }
        $pdo->prepare('INSERT INTO currency_pages (code, lang, name, h1, seo_title, seo_description, lead, about_title, about_body) VALUES (?,?,?,?,?,?,?,?,?)
            ON DUPLICATE KEY UPDATE name=VALUES(name), h1=VALUES(h1), seo_title=VALUES(seo_title), seo_description=VALUES(seo_description), lead=VALUES(lead), about_title=VALUES(about_title), about_body=VALUES(about_body)')
            ->execute([$code, $l, $row['name'], $row['h1'], $row['seo_title'], $row['seo_description'], $row['lead'], $row['about_title'], $row['about_body']]);
    }
    adm_content_changed();
    flash('ok', 'Сохранено.');
    redirect('/admin/currency-edit.php?code=' . $code);
}

$saved = [];
foreach (db()->query("SELECT * FROM currency_pages WHERE code = " . db()->quote($code)) as $r) $saved[$r['lang']] = $r;

adm_head($code . ' · ' . currency_name($code, 'ru'), 'currencies', $user);
$labels = [
    'name' => ['Название валюты', 'Например: ԱՄՆ դոլար. Пусто — стандартное название.', false],
    'h1' => ['Заголовок H1 страницы', 'Пусто — «Название (КОД)».', false],
    'seo_title' => ['SEO: заголовок (title)', 'Пусто — общий шаблон. До 60–70 знаков.', false],
    'seo_description' => ['SEO: описание (description)', 'Пусто — общий шаблон. 120–160 знаков.', true],
    'lead' => ['Вводный текст под H1', 'Пусто — общий текст. Можно использовать {name} и {code}.', true],
    'about_title' => ['Заголовок статьи о валюте', 'Пусто — общий шаблон.', false],
    'about_body' => ['Статья о валюте', 'Абзацы разделяются пустой строкой, **так** — жирным. Пусто — общий текст. Для SEO лучше уникальный текст для каждой валюты.', true],
];
?>
<nav class="mb-4 text-sm"><a href="/admin/currencies.php" title="Все валюты" class="font-semibold">← Все валюты</a></nav>
<div class="flex flex-wrap items-center gap-3">
    <?= ui_flag($code, 48, 'h-12 w-12 rounded-full object-cover shadow') ?>
    <h1 class="min-w-0 flex-1 text-2xl font-black tracking-tight text-slate-900 sm:text-3xl"><?= e($code) ?> · <?= e(currency_name($code, 'hy')) ?></h1>
    <a href="/currency.php?cur=<?= e($code) ?>" target="_blank" rel="noopener" title="Страница на сайте" class="<?= ADM_BTN2 ?>">Страница на сайте ↗</a>
</div>
<form method="post" class="mt-6 grid gap-5 pb-24 lg:pb-6">
    <?= csrf_field() ?><input type="hidden" name="code" value="<?= e($code) ?>">
    <?php foreach (ADM_LANGS as $l => $ln): ?>
    <section class="<?= ADM_CARD ?>">
        <h2 class="text-lg font-black text-slate-900"><?= e($ln) ?></h2>
        <div class="mt-4 grid gap-4">
            <?php foreach ($labels as $f => [$lab, $hint, $long]): ?>
            <div>
                <?= adm_field("c_{$f}_{$l}", "{$f}[{$l}]", e($lab), (string)($saved[$l][$f] ?? ''), $long, $f === 'about_body' ? 8 : 3) ?>
                <p class="mt-1 text-xs text-slate-600"><?= e($hint) ?></p>
            </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endforeach; ?>
    <div class="fixed inset-x-0 bottom-0 z-30 border-t border-slate-200 bg-white/95 p-3 backdrop-blur lg:static lg:border-0 lg:bg-transparent lg:p-0">
        <div class="mx-auto flex max-w-6xl gap-3 px-1 lg:px-0"><button type="submit" class="<?= ADM_BTN ?> flex-1 lg:flex-none">Сохранить</button><a href="/admin/currencies.php" title="Все валюты" class="<?= ADM_BTN2 ?>">Отмена</a></div>
    </div>
</form>
<?php adm_foot(); ?>
