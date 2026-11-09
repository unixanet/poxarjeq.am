<?php
require_once __DIR__ . '/includes/functions.php';
$lang = detect_lang();
$L = load_lang($lang);

$code = strtolower(trim($_GET['code'] ?? ''));
$script = basename($_SERVER['SCRIPT_NAME'] ?? '', '.php');
if ($code === '' && $script !== 'bank' && $script !== 'index') {
    $code = $script;
}
if ($code === '') {
    header('Location: /');
    exit;
}

$apiBank = get_bank($code);
$meta = get_bank_meta($code);
$rates = $apiBank['rates'] ?? [];
$logo = bank_logo_url($code, $apiBank['logo'] ?? '');
$updatedAt = $apiBank['updated_at'] ?? null;
$displayName = bank_display_name($code, $lang, $apiBank['name'] ?? null);
$branches = $meta['branches'] ?? [];

if ($lang === 'hy') {
    $pageTitle = $displayName . ' փոխարժեքներ | Բանկի կուրսեր և մասնաճյուղեր';
    $pageDescription = $displayName . ' — ակտուալ փոխարժեքներ Հայաստանում, մասնաճյուղեր Երևանում և մարզերում, կոնտակտներ։';
} elseif ($lang === 'ru') {
    $pageTitle = $displayName . ' — курсы валют | Отделения и контакты';
    $pageDescription = $displayName . ' — актуальные курсы валют в Армении, филиалы в Ереване и регионах, контакты.';
} else {
    $pageTitle = $displayName . ' exchange rates | Branches and contacts';
    $pageDescription = $displayName . ' — live exchange rates in Armenia, branches in Yerevan and regions, contacts.';
}
$pageCanonical = rtrim(SITE_URL, '/') . bank_page_url($code);
$active = '';

$flags = currency_flags_map();

require __DIR__ . '/includes/header.php';
?>

<nav class="text-sm text-black mb-6">
    <a href="/" class="no-underline hover:text-brand text-black"><?= e(t('home')) ?></a>
    <span class="mx-2">/</span>
    <span class="text-black font-medium"><?= e($displayName) ?></span>
</nav>

<div class="flex flex-col sm:flex-row sm:items-center gap-5 mb-8">
    <div class="w-16 h-16 rounded-2xl bg-brand-light border border-black/10 flex items-center justify-center overflow-hidden shrink-0">
        <?php if ($logo): ?>
        <img src="<?= e($logo) ?>" alt="<?= e($displayName) ?>" class="w-12 h-12 object-contain" width="48" height="48">
        <?php else: ?>
        <span class="text-2xl font-bold text-brand"><?= e(substr($displayName, 0, 1)) ?></span>
        <?php endif; ?>
    </div>
    <div>
        <h1 class="text-2xl sm:text-3xl font-bold text-black"><?= e($displayName) ?></h1>
        <?php if ($meta && !empty($meta['legal'])): ?>
        <p class="text-black text-sm mt-1"><?= e($meta['legal']) ?></p>
        <?php endif; ?>
        <?php if ($updatedAt): ?>
        <p class="text-sm text-black mt-2"><?= e(t('updated')) ?>: <?= e(format_datetime($updatedAt)) ?></p>
        <?php endif; ?>
    </div>
</div>

<!-- Rates table same style as index -->
<section class="mb-10">
    <h2 class="text-lg font-bold text-black mb-4"><?= e(t('rates_of')) ?></h2>
    <?php if ($rates): ?>
    <div class="bg-white border border-black/10 rounded-2xl overflow-hidden shadow-sm">
        <div class="scroll-x">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-brand-light border-b border-black/10">
                        <th class="text-left px-4 py-3 font-semibold text-black"><?= e(t('currency')) ?></th>
                        <th class="text-right px-4 py-3 font-semibold text-black"><?= e(t('buy')) ?></th>
                        <th class="text-right px-4 py-3 font-semibold text-black"><?= e(t('sell')) ?></th>
                        <th class="text-right px-4 py-3 font-semibold text-black"><?= e(t('spread')) ?></th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rates as $c => $r):
                        $buy = $r['buy'] ?? null; $sell = $r['sell'] ?? null;
                        $sp = ($buy && $sell) ? calc_spread((float)$buy, (float)$sell) : 0;
                    ?>
                    <tr class="border-b border-black/5 hover:bg-brand-light/40">
                        <td class="px-4 py-3">
                            <span class="font-medium text-black inline-flex items-center gap-2"><img src="<?= e($flags[$c] ?? '') ?>" alt="<?= e($c) ?>" width="20" height="14" class="inline-block object-contain" onerror="this.style.display='none'"> <?= e(currency_name($c, $lang)) ?></span>
                            <span class="text-black text-xs ml-1"><?= e($c) ?></span>
                        </td>
                        <td class="px-4 py-3 text-right font-semibold text-emerald-700"><?= e(format_rate($buy)) ?></td>
                        <td class="px-4 py-3 text-right font-semibold text-black"><?= e(format_rate($sell)) ?></td>
                        <td class="px-4 py-3 text-right text-black"><?= e((string)$sp) ?>%</td>
                        <td class="px-4 py-3 text-right">
                            <a href="/currency.php?cur=<?= e($c) ?>" class="text-brand text-xs font-medium no-underline hover:underline"><?= e(t('compare_banks')) ?></a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php else: ?>
    <div class="border border-black/10 rounded-xl p-8 text-center text-black"><?= e(t('no_data')) ?></div>
    <?php endif; ?>
</section>

<div class="grid md:grid-cols-2 gap-6 mb-10">
    <?php if ($meta): ?>
    <div class="bg-white border border-black/10 rounded-xl p-5">
        <h3 class="font-bold text-black mb-4"><?= e(t('contacts')) ?></h3>
        <ul class="space-y-3 text-sm text-black">
            <?php if (!empty($meta['address'][$lang])): ?>
            <li><div class="text-xs font-semibold uppercase text-black mb-0.5"><?= e(t('address')) ?></div><div><?= e($meta['address'][$lang]) ?></div></li>
            <?php endif; ?>
            <?php if (!empty($meta['phone'])): ?>
            <li><div class="text-xs font-semibold uppercase text-black mb-0.5"><?= e(t('phone')) ?></div>
                <a href="tel:<?= e(preg_replace('/\s+/','',$meta['phone'])) ?>" class="text-brand font-medium no-underline"><?= e($meta['phone']) ?></a>
            </li>
            <?php endif; ?>
            <?php if (!empty($meta['email'])): ?>
            <li><div class="text-xs font-semibold uppercase text-black mb-0.5"><?= e(t('email')) ?></div>
                <a href="mailto:<?= e($meta['email']) ?>" class="text-brand no-underline"><?= e($meta['email']) ?></a></li>
            <?php endif; ?>
            <?php if (!empty($meta['website'])): ?>
            <li><div class="text-xs font-semibold uppercase text-black mb-0.5"><?= e(t('website')) ?></div>
                <a href="<?= e($meta['website']) ?>" target="_blank" rel="noopener" class="text-brand no-underline"><?= e(preg_replace('#https?://#','',$meta['website'])) ?></a></li>
            <?php endif; ?>
        </ul>
    </div>
    <div class="bg-brand-light border border-brand/20 rounded-xl p-5">
        <h3 class="font-bold text-black mb-3"><?= e(t('about_bank')) ?></h3>
        <p class="text-sm text-black leading-relaxed"><?= e($meta['about'][$lang] ?? '') ?></p>
    </div>
    <?php endif; ?>
</div>

<?php if ($branches): ?>
<section class="mb-10" id="branches">
    <h2 class="text-xl font-bold text-black mb-4">
        <?php if ($lang==='hy'): ?>Մասնաճյուղեր
        <?php elseif ($lang==='ru'): ?>Отделения и филиалы
        <?php else: ?>Branches
        <?php endif; ?>
    </h2>
    <div class="grid sm:grid-cols-2 gap-4">
        <?php foreach ($branches as $br):
            $slug = $br['slug'] ?? '';
            $href = $slug ? branch_page_url($code, $slug) : '#';
        ?>
        <a href="<?= e($href) ?>" class="block border border-black/10 rounded-xl p-5 bg-white no-underline hover:border-brand hover:bg-brand-light transition group">
            <div class="font-semibold text-black group-hover:text-brand mb-1"><?= e($br['name'][$lang] ?? '') ?></div>
            <div class="text-sm text-brand mb-2"><?= e($br['city'][$lang] ?? '') ?></div>
            <div class="text-sm text-black mb-1"><?= e($br['address'][$lang] ?? '') ?></div>
            <?php if (!empty($br['phone'])): ?>
            <div class="text-sm text-black"><?= e($br['phone']) ?></div>
            <?php endif; ?>
        </a>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<article class="bg-white border border-black/10 rounded-2xl p-6 sm:p-8 mb-8">
    <h2 class="text-lg font-bold text-black mb-3">
        <?php if ($lang==='hy'): ?><?= e($displayName) ?> — փոխարժեքներ և ծառայություններ
        <?php elseif ($lang==='ru'): ?><?= e($displayName) ?> — курсы и услуги
        <?php else: ?><?= e($displayName) ?> — rates and services
        <?php endif; ?>
    </h2>
    <div class="text-sm text-black leading-relaxed space-y-2">
        <?php if ($lang==='hy'): ?>
        <p>Այս էջում՝ <?= e($displayName) ?>-ի ակտուալ կանխիկ փոխարժեքները։ Կուրսերը թարմացվում են ավտոմատ։ Գործարքից առաջ զանգահարեք կամ այցելեք մասնաճյուղ։</p>
        <p>Մասնաճյուղերի ցանկում կան Երևան, Վանաձոր և այլ քաղաքներ։ Սեղմեք մասնաճյուղի վրա՝ կոնտակտները տեսնելու համար։</p>
        <?php elseif ($lang==='ru'): ?>
        <p>На этой странице — актуальные наличные курсы <?= e($displayName) ?>. Курсы обновляются автоматически. Перед сделкой позвоните или посетите отделение.</p>
        <p>В списке филиалов — Ереван, Ванадзор и другие города. Нажмите на филиал, чтобы увидеть контакты.</p>
        <?php else: ?>
        <p>This page shows live cash rates of <?= e($displayName) ?>. Rates update automatically. Before exchanging, call or visit a branch.</p>
        <p>The branch list includes Yerevan, Vanadzor and other cities. Click a branch for contacts.</p>
        <?php endif; ?>
    </div>
</article>

<div class="border border-amber-400 bg-amber-50 rounded-xl px-4 py-3 text-sm text-black mb-6">
    <?= e(t('disclaimer_short')) ?>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
