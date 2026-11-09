<?php
declare(strict_types=1);
require __DIR__ . '/_boot.php';
require __DIR__ . '/_layout.php';
require __DIR__ . '/_forms.php';
$user = adm_require();

$root = dirname(__DIR__);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $act = (string)($_POST['action'] ?? '');
    if ($act === 'llms') { [$ok, $msg] = llms_write(); flash($ok ? 'ok' : 'error', $msg); }
    if ($act === 'cache') {
        $n = 0; foreach (glob(CACHE_DIR . '/*.json') ?: [] as $f) { if (@unlink($f)) $n++; }
        @unlink(CACHE_DIR . '/api_sync.stamp');
        flash('ok', 'Кэш очищен (файлов: ' . $n . ').');
    }
    if ($act !== 'audit') redirect('/admin/tools.php');
}

$audit = null;
if (($_SERVER['REQUEST_METHOD'] === 'POST') && ($_POST['action'] ?? '') === 'audit') {
    $langs = !empty($_POST['all_langs']) ? SUPPORTED_LANGS : ['hy'];
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https') ? 'https' : 'http';
    $base = $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
    $paths = ['/', '/currency.php?cur=USD', '/currency.php?cur=EUR', '/currency.php?cur=RUR', '/about.php', '/contact.php', '/privacy.php', '/disclaimer.php'];
    $bank = db()->query('SELECT code FROM banks WHERE is_active = 1 ORDER BY is_partner DESC, partner_order, id LIMIT 1')->fetchColumn();
    if ($bank) {
        $paths[] = bank_page_url((string)$bank);
        $br = db()->prepare('SELECT slug FROM branches WHERE bank_code = ? AND is_active = 1 ORDER BY sort, id LIMIT 1'); $br->execute([$bank]);
        if ($slug = $br->fetchColumn()) $paths[] = '/branch.php?code=' . $bank . '&city=' . $slug;
    }
    $ctx = stream_context_create(['http' => ['timeout' => 20, 'ignore_errors' => true, 'header' => "User-Agent: PoxarjeqAudit\r\n"], 'ssl' => ['verify_peer' => false, 'verify_peer_name' => false]]);
    $audit = [];
    foreach ($langs as $l) foreach ($paths as $p) {
        $url = $base . $p . (str_contains($p, '?') ? '&' : '?') . 'lang=' . $l;
        $html = @file_get_contents($url, false, $ctx);
        $audit[] = ['lang' => $l, 'path' => $p, 'issues' => $html === false ? ['страница не открылась'] : seo_audit_html($html)];
    }
}
$llmsInfo = [];
foreach (['llms.txt', 'llms-full.txt'] as $f) $llmsInfo[$f] = is_file("$root/$f") ? date('d.m.Y H:i', (int)filemtime("$root/$f")) : null;

adm_head('SEO и llms.txt', 'tools', $user);
?>
<h1 class="text-2xl font-black tracking-tight text-slate-900 sm:text-3xl">SEO и llms.txt</h1>
<div class="mt-6 grid gap-5 lg:grid-cols-2">
    <section class="<?= ADM_CARD ?>">
        <h2 class="text-lg font-black text-slate-900">llms.txt для ИИ-поисковиков</h2>
        <p class="mt-2 text-[15px] leading-relaxed">Файлы <span class="font-mono text-sm">/llms.txt</span> и <span class="font-mono text-sm">/llms-full.txt</span> объясняют ChatGPT, Claude, Perplexity и другим системам, что за сайт, какие на нём страницы, валюты и банки. Содержимое собирается из ваших текстов и данных банков. Обновляйте после заметных правок.</p>
        <dl class="mt-3 space-y-1 text-sm">
            <?php foreach ($llmsInfo as $f => $when): ?>
            <div class="flex justify-between gap-3"><dt class="font-mono"><?= e($f) ?></dt><dd class="font-semibold"><?= $when ? 'обновлён ' . e($when) : 'ещё не создан' ?></dd></div>
            <?php endforeach; ?>
        </dl>
        <div class="mt-4 flex flex-wrap gap-2">
            <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="llms"><button type="submit" class="<?= ADM_BTN ?>">Обновить llms.txt</button></form>
            <a href="/llms.txt" target="_blank" rel="noopener" title="Открыть llms.txt" class="<?= ADM_BTN2 ?>">Открыть ↗</a>
        </div>
    </section>
    <section class="<?= ADM_CARD ?>">
        <h2 class="text-lg font-black text-slate-900">Проверка SEO сайта</h2>
        <p class="mt-2 text-[15px] leading-relaxed">Проходит по главным страницам и проверяет: у каждой ссылки есть title, у каждой картинки alt и размеры, один H1, есть title, description, canonical, hreflang, корректная разметка JSON-LD.</p>
        <form method="post" class="mt-4 flex flex-wrap items-center gap-3"><?= csrf_field() ?><input type="hidden" name="action" value="audit">
            <label class="flex min-h-[44px] items-center gap-2 text-sm font-semibold"><input type="checkbox" name="all_langs" value="1" class="h-5 w-5 accent-blue-600"> Проверить все языки (HY, RU, EN)</label>
            <button type="submit" class="<?= ADM_BTN ?>">Проверить сайт</button>
        </form>
        <form method="post" class="mt-3"><?= csrf_field() ?><input type="hidden" name="action" value="cache"><button type="submit" class="<?= ADM_BTN2 ?>">Сбросить кэш курсов и текстов</button></form>
    </section>
</div>

<?php if ($audit !== null):
    $bad = array_filter($audit, fn($r) => $r['issues']);
?>
<section class="mt-6 <?= ADM_CARD ?>">
    <h2 class="text-lg font-black text-slate-900">Результат: <?= $bad ? 'есть замечания (' . count($bad) . ' из ' . count($audit) . ' страниц)' : 'замечаний нет' ?></h2>
    <ul class="mt-4 divide-y divide-slate-100">
        <?php foreach ($audit as $r): ?>
        <li class="py-3">
            <div class="flex flex-wrap items-center gap-2 text-sm"><span class="rounded-full px-2.5 py-0.5 text-[11px] font-extrabold <?= $r['issues'] ? 'bg-amber-100 text-amber-900' : 'bg-green-100 text-green-900' ?>"><?= $r['issues'] ? 'проверить' : 'ок' ?></span><strong><?= e(strtoupper($r['lang'])) ?></strong><span class="break-all font-mono"><?= e($r['path']) ?></span></div>
            <?php foreach ($r['issues'] as $i): ?><p class="mt-1 pl-1 text-sm text-amber-900">— <?= e($i) ?></p><?php endforeach; ?>
        </li>
        <?php endforeach; ?>
    </ul>
</section>
<?php endif; ?>
<?php adm_foot(); ?>
