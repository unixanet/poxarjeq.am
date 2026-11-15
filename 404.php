<?php
require_once __DIR__ . '/includes/functions.php';
if (!isset($lang)) { $lang = detect_lang(); $L = load_lang($lang); }
http_response_code(404);
header('Cache-Control: no-store');
$pageTitle = t('nf.title') . ' | Poxarjeq.am';
$pageDescription = t('nf.text');
$pageRobots = 'noindex, follow';
$pagePath = '/';
$active = '';
require __DIR__ . '/includes/header.php';
?>
<section class="mx-auto max-w-3xl py-8 text-center sm:py-14">
    <p class="text-[72px] font-black leading-none tracking-tight text-blue-600 sm:text-[110px]" aria-hidden="true">404</p>
    <h1 class="mt-2 text-3xl font-black tracking-tight text-slate-900 sm:text-4xl"><?= e(t('nf.title')) ?></h1>
    <p class="mx-auto mt-4 max-w-xl text-base leading-relaxed sm:text-lg"><?= e(t('nf.text')) ?></p>
    <p class="mt-6"><?= ui_link(lurl('/'), t('nf.home'), t('today_rates'), 'inline-flex min-h-[50px] items-center justify-center rounded-[14px] bg-blue-600 px-8 font-bold text-white no-underline shadow-[0_6px_16px_rgba(37,99,235,.28)] hover:bg-blue-700') ?></p>
</section>
<section class="mx-auto mt-6 grid max-w-4xl gap-6 sm:grid-cols-2">
    <div class="rounded-3xl border border-slate-100 bg-white p-5 shadow-[0_10px_40px_rgba(15,23,42,.06)]">
        <h2 class="mb-3 text-lg font-black text-slate-900"><?= e(t('nf.currencies')) ?></h2>
        <ul class="flex flex-wrap gap-2">
            <?php foreach (['USD', 'EUR', 'RUR', 'GEL', 'GBP', 'CHF', 'CNY', 'AED'] as $c): ?>
            <li><?= ui_link(lurl('/currency.php?cur=' . $c), ui_flag($c, 20, 'h-5 w-5 rounded-full object-cover') . e($c), tv('common.link_currency', ['name' => currency_name($c, $lang), 'code' => $c]), 'flex min-h-[40px] items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 text-sm font-bold text-black no-underline hover:border-blue-300', [], true) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <div class="rounded-3xl border border-slate-100 bg-white p-5 shadow-[0_10px_40px_rgba(15,23,42,.06)]">
        <h2 class="mb-3 text-lg font-black text-slate-900"><?= e(t('nf.banks')) ?></h2>
        <ul class="flex flex-wrap gap-2">
            <?php foreach (top_bank_codes(8) as $bc): $bn = bank_display_name($bc, $lang); ?>
            <li><?= ui_link(lurl(bank_page_url($bc)), $bn, tv('common.link_bank', ['bank' => $bn]), 'flex min-h-[40px] items-center rounded-xl border border-slate-200 bg-white px-3 text-sm font-bold text-black no-underline hover:border-blue-300') ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
</section>
<?php require __DIR__ . '/includes/footer.php';
