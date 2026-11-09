<?php
require_once __DIR__ . '/includes/functions.php';

$lang = detect_lang();
$L = load_lang($lang);

$cur = strtoupper(trim($_GET['cur'] ?? 'USD'));
if (!in_array($cur, all_currency_codes(), true)) $cur = 'USD';

$banksData = get_banks_for_currency($cur);
$summary = get_rates_summary();
$history = get_history($cur, 14);
$s = $summary['summary'][$cur] ?? null;
$updatedAt = $banksData['updated_at'] ?? ($summary['updated_at'] ?? null);
$name = currency_name($cur, $lang);

$pageTitle = $name . ' (' . $cur . ') — ' . t('today_rates') . ' | ' . t('site_name');
$pageDescription = $name . ' ' . t('best_rates') . '. ' . t('hero_sub');
$pageCanonical = SITE_URL . '/currency.php?cur=' . $cur . '&lang=' . $lang;
$active = strtolower($cur);

require __DIR__ . '/includes/header.php';
?>

<nav class="text-sm text-black mb-6">
    <a href="/?lang=<?= e($lang) ?>" class="hover:text-brand"><?= e(t('home')) ?></a>
    <span class="mx-2">/</span>
    <span class="text-black"><?= e($name) ?></span>
</nav>

<header class="mb-8">
    <h1 class="text-2xl sm:text-3xl font-bold text-black mb-2"><?= e($name) ?> <span class="text-black font-medium">(<?= e($cur) ?>)</span></h1>
    <p class="text-black max-w-2xl"><?= e(t('compare_banks')) ?> — <?= e($name) ?></p>
    <?php if ($updatedAt): ?>
    <div class="mt-3 inline-flex items-center gap-2 text-sm text-black">
        <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
        <?= e(t('updated')) ?>: <?= e(format_datetime($updatedAt)) ?>
    </div>
    <?php endif; ?>
</header>

<?php if ($s): ?>
<div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-10">
    <div class="bg-white border border-black/10 rounded-xl p-5 text-center">
        <div class="text-2xl font-bold text-emerald-600"><?= e(format_rate($s['best_buy'] ?? null)) ?></div>
        <div class="text-xs text-black mt-1 uppercase tracking-wide"><?= e(t('best_buy')) ?></div>
    </div>
    <div class="bg-white border border-black/10 rounded-xl p-5 text-center">
        <div class="text-2xl font-bold text-rose-600"><?= e(format_rate($s['best_sell'] ?? null)) ?></div>
        <div class="text-xs text-black mt-1 uppercase tracking-wide"><?= e(t('best_sell')) ?></div>
    </div>
    <div class="bg-white border border-black/10 rounded-xl p-5 text-center">
        <div class="text-2xl font-bold text-black"><?= e(format_rate($s['avg_buy'] ?? null)) ?></div>
        <div class="text-xs text-black mt-1 uppercase tracking-wide"><?= e(t('avg_buy')) ?></div>
    </div>
</div>
<?php endif; ?>

<!-- Currency switcher chips -->
<div class="flex flex-wrap gap-2 mb-6">
    <?php foreach (all_currency_codes() as $c): ?>
    <a href="/currency.php?cur=<?= e($c) ?>&lang=<?= e($lang) ?>"
       class="px-3 py-1.5 rounded-lg text-sm font-medium border transition <?= $c===$cur ? 'bg-brand text-white border-brand' : 'bg-white text-black border-black/10 hover:border-brand hover:text-brand' ?>">
        <?= e($c) ?>
    </a>
    <?php endforeach; ?>
</div>

<section class="mb-10">
    <h2 class="text-lg font-bold text-black mb-4"><?= e(t('all_banks')) ?> — <?= e($cur) ?></h2>
    <?php if ($banksData && !empty($banksData['banks'])): ?>
    <div class="bg-white border border-black/10 rounded-xl overflow-hidden shadow-sm">
        <div class="scroll-x">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-white border-b border-black/10">
                        <th class="text-left px-4 py-3 font-semibold text-black text-xs uppercase">#</th>
                        <th class="text-left px-4 py-3 font-semibold text-black text-xs uppercase"><?= e(t('bank')) ?></th>
                        <th class="text-right px-4 py-3 font-semibold text-black text-xs uppercase"><?= e(t('buy')) ?></th>
                        <th class="text-right px-4 py-3 font-semibold text-black text-xs uppercase"><?= e(t('sell')) ?></th>
                        <th class="text-right px-4 py-3 font-semibold text-black text-xs uppercase"><?= e(t('spread')) ?></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php foreach ($banksData['banks'] as $i => $bank):
                        $spread = calc_spread((float)$bank['buy'], (float)$bank['sell']);
                        $bName = bank_display_name($bank['code'], $lang, $bank['name'] ?? null);
                    ?>
                    <tr class="hover:bg-white">
                        <td class="px-4 py-3 text-black"><?= $i+1 ?></td>
                        <td class="px-4 py-3">
                            <a href="<?= e(bank_page_url($bank['code'])) ?>?lang=<?= e($lang) ?>" class="flex items-center gap-3 group">
                                <?php if (!empty($bank['logo'])): ?>
                                <img src="<?= e(bank_logo_url($bank['code'], $bank['logo'])) ?>" alt="" class="w-8 h-8 rounded-lg object-contain bg-white" width="32" height="32" loading="lazy" onerror="this.style.display='none'">
                                <?php endif; ?>
                                <span class="font-medium group-hover:text-brand transition"><?= e($bName) ?></span>
                            </a>
                        </td>
                        <td class="px-4 py-3 text-right font-semibold text-emerald-600"><?= e(format_rate($bank['buy'])) ?></td>
                        <td class="px-4 py-3 text-right font-semibold text-rose-600"><?= e(format_rate($bank['sell'])) ?></td>
                        <td class="px-4 py-3 text-right text-black"><?= e((string)$spread) ?>%</td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php else: ?>
    <div class="bg-white rounded-xl p-8 text-center text-black"><?= e(t('no_data')) ?></div>
    <?php endif; ?>
</section>

<?php if ($history && !empty($history['points'])): ?>
<section class="mb-10">
    <h2 class="text-lg font-bold text-black mb-4"><?= e(t('history')) ?> — <?= e(t('last_days')) ?></h2>
    <div class="bg-white border border-black/10 rounded-xl overflow-hidden">
        <div class="scroll-x">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-white border-b">
                        <th class="text-left px-4 py-3 text-xs font-semibold text-black uppercase">Date</th>
                        <th class="text-right px-4 py-3 text-xs font-semibold text-black uppercase"><?= e(t('buy')) ?></th>
                        <th class="text-right px-4 py-3 text-xs font-semibold text-black uppercase"><?= e(t('sell')) ?></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php foreach (array_reverse(array_slice($history['points'], -14)) as $p): ?>
                    <tr class="hover:bg-white">
                        <td class="px-4 py-2.5"><?= e($p['date'] ?? '') ?></td>
                        <td class="px-4 py-2.5 text-right text-emerald-600 font-medium"><?= e(format_rate($p['buy'] ?? null)) ?></td>
                        <td class="px-4 py-2.5 text-right text-rose-600 font-medium"><?= e(format_rate($p['sell'] ?? null)) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>
<?php endif; ?>

<div class="bg-amber-50 border border-amber-200 rounded-xl px-4 py-3 text-sm text-amber-900">
    <?= e(t('disclaimer_short')) ?>
</div>

<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "ItemList",
  "name": "<?= e($name) ?> <?= e(t('rates_of')) ?>",
  "numberOfItems": <?= (int)count($banksData['banks'] ?? []) ?>,
  "itemListElement": [
    <?php
    $items = [];
    foreach (($banksData['banks'] ?? []) as $idx => $b) {
        $items[] = '{"@type":"ListItem","position":'.($idx+1).',"name":"'.e(bank_display_name($b['code'],$lang,$b['name']??'')).'","url":"'.e(SITE_URL.bank_page_url($b['code'])).'"}';
    }
    echo implode(',', $items);
    ?>
  ]
}
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>
