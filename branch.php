<?php
require_once __DIR__ . '/includes/functions.php';
$lang = detect_lang();
$L = load_lang($lang);

$code = strtolower(trim($_GET['code'] ?? ''));
$city = strtolower(trim($_GET['city'] ?? ''));
if ($code === '' || $city === '') {
    header('Location: /');
    exit;
}

$meta = get_bank_meta($code);
$apiBank = get_bank($code);
$displayName = bank_display_name($code, $lang, $apiBank['name'] ?? null);
$rates = $apiBank['rates'] ?? [];
$logo = $apiBank['logo'] ?? '';
$updatedAt = $apiBank['updated_at'] ?? null;
$branches = $meta['branches'] ?? [];
$flags = currency_flags_map();

$branch = null;
foreach ($branches as $b) {
    if (($b['slug'] ?? '') === $city) {
        $branch = $b;
        break;
    }
}
if (!$branch) {
    header('Location: ' . bank_page_url($code));
    exit;
}

$cityName = $branch['city'][$lang] ?? $city;
$branchName = $branch['name'][$lang] ?? $cityName;
$addr = $branch['address'][$lang] ?? '';
$phone = $branch['phone'] ?? '';

// SEO titles — full oriented
if ($lang === 'hy') {
    $pageTitle = $displayName . ' ' . $branchName . ' | Փոխարժեք և կոնտակտներ';
    $pageDescription = $displayName . ' — ' . $branchName . ' (' . $cityName . '). Հասցե՝ ' . $addr . '. Ակտուալ փոխարժեքներ, հեռախոս և կոնտակտներ։';
} elseif ($lang === 'ru') {
    $pageTitle = $displayName . ' — ' . $branchName . ' | Курс и контакты';
    $pageDescription = $displayName . ' — ' . $branchName . ' (' . $cityName . '). Адрес: ' . $addr . '. Актуальные курсы валют, телефон и контакты филиала.';
} else {
    $pageTitle = $displayName . ' ' . $branchName . ' | Rates and contacts';
    $pageDescription = $displayName . ' — ' . $branchName . ' in ' . $cityName . '. Address: ' . $addr . '. Live exchange rates, phone and branch contacts.';
}
$pageCanonical = rtrim(SITE_URL, '/') . '/' . $code . '/' . $city;
$active = '';

require __DIR__ . '/includes/header.php';
?>

<nav class="text-sm text-black mb-6">
    <a href="/" class="no-underline hover:text-brand text-black"><?= e(t('home')) ?></a>
    <span class="mx-2">/</span>
    <a href="<?= e(bank_page_url($code)) ?>" class="no-underline hover:text-brand text-black"><?= e($displayName) ?></a>
    <span class="mx-2">/</span>
    <span class="text-black font-medium"><?= e($cityName) ?></span>
</nav>

<div class="flex flex-col sm:flex-row sm:items-center gap-5 mb-8">
    <div class="w-16 h-16 rounded-2xl bg-brand-light border border-black/10 flex items-center justify-center overflow-hidden shrink-0">
        <?php if ($logo): ?>
        <img src="<?= e($logo) ?>" alt="" class="w-12 h-12 object-contain" width="48" height="48">
        <?php else: ?>
        <span class="text-2xl font-bold text-brand"><?= e(substr($displayName, 0, 1)) ?></span>
        <?php endif; ?>
    </div>
    <div>
        <h1 class="text-2xl sm:text-3xl font-bold text-black"><?= e($displayName) ?> — <?= e($branchName) ?></h1>
        <p class="text-black text-sm mt-1"><?= e($cityName) ?><?php if ($addr): ?> · <?= e($addr) ?><?php endif; ?></p>
        <?php if ($updatedAt): ?>
        <p class="text-sm text-black mt-2"><?= e(t('updated')) ?>: <?= e(format_datetime($updatedAt)) ?></p>
        <?php endif; ?>
    </div>
</div>

<!-- Rates table — same style as index -->
<section class="mb-10">
    <h2 class="text-lg font-bold text-black mb-4"><?= e(t('rates_of')) ?> — <?= e($displayName) ?></h2>
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
                        <th class="text-right px-4 py-3 font-semibold text-black"><?= e(t('updated')) ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rates as $c => $r):
                        $buy = $r['buy'] ?? null; $sell = $r['sell'] ?? null;
                        $sp = ($buy && $sell) ? calc_spread((float)$buy, (float)$sell) : 0;
                    ?>
                    <tr class="border-b border-black/5 hover:bg-brand-light/40">
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center gap-2 font-medium text-black">
                                <img src="<?= e($flags[$c] ?? currency_flag($c)) ?>" alt="<?= e($c) ?>" width="20" height="14" class="object-contain" onerror="this.style.display='none'">
                                <?= e(currency_name($c, $lang)) ?>
                                <span class="text-black text-xs"><?= e($c) ?></span>
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right font-semibold text-emerald-700"><?= e(format_rate($buy)) ?></td>
                        <td class="px-4 py-3 text-right font-semibold text-black"><?= e(format_rate($sell)) ?></td>
                        <td class="px-4 py-3 text-right text-black"><?= e((string)$sp) ?>%</td>
                        <td class="px-4 py-3 text-right text-sm text-black"><?= $updatedAt ? e(format_datetime($updatedAt)) : '—' ?></td>
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
    <div class="bg-white border border-black/10 rounded-xl p-5">
        <h3 class="font-bold text-black mb-4"><?= e(t('contacts')) ?> — <?= e($cityName) ?></h3>
        <ul class="space-y-3 text-sm text-black">
            <li>
                <div class="text-xs font-semibold uppercase text-black mb-0.5"><?= e(t('address')) ?></div>
                <div><?= e($addr) ?></div>
            </li>
            <?php if ($phone): ?>
            <li>
                <div class="text-xs font-semibold uppercase text-black mb-0.5"><?= e(t('phone')) ?></div>
                <a href="tel:<?= e(preg_replace('/\s+/','',$phone)) ?>" class="text-brand font-medium no-underline"><?= e($phone) ?></a>
            </li>
            <?php endif; ?>
            <?php if ($meta && !empty($meta['website'])): ?>
            <li>
                <div class="text-xs font-semibold uppercase text-black mb-0.5"><?= e(t('website')) ?></div>
                <a href="<?= e($meta['website']) ?>" target="_blank" rel="noopener" class="text-brand no-underline"><?= e(preg_replace('#https?://#','',$meta['website'])) ?></a>
            </li>
            <?php endif; ?>
        </ul>
        <a href="<?= e(bank_page_url($code)) ?>" class="inline-block mt-4 text-sm text-brand font-medium no-underline hover:underline">← <?= e($displayName) ?></a>
    </div>
    <div class="bg-brand-light border border-brand/20 rounded-xl p-5">
        <h3 class="font-bold text-black mb-3"><?= e($branchName) ?></h3>
        <p class="text-sm text-black leading-relaxed">
            <?php if ($lang==='hy'): ?>
            <?= e($displayName) ?>-ի <?= e($branchName) ?> մասնաճյուղը գտնվում է <?= e($cityName) ?> քաղաքում։ Այստեղ կարող եք փոխանակել արժույթ պաշտոնական կուրսով, ստանալ բանկային ծառայություններ և խորհրդատվություն։
            <?php elseif ($lang==='ru'): ?>
            Филиал <?= e($displayName) ?> «<?= e($branchName) ?>» находится в городе <?= e($cityName) ?>. Здесь можно обменять валюту по официальному курсу банка и получить банковские услуги.
            <?php else: ?>
            The <?= e($displayName) ?> branch «<?= e($branchName) ?>» is located in <?= e($cityName) ?>. You can exchange currency at the bank's official rate and get banking services here.
            <?php endif; ?>
        </p>
    </div>
</div>

<article class="bg-white border border-black/10 rounded-2xl p-6 sm:p-8 mb-8">
    <h2 class="text-lg font-bold text-black mb-3">
        <?php if ($lang==='hy'): ?><?= e($displayName) ?> <?= e($cityName) ?> — փոխարժեք և կոնտակտներ
        <?php elseif ($lang==='ru'): ?><?= e($displayName) ?> <?= e($cityName) ?> — курс и контакты
        <?php else: ?><?= e($displayName) ?> <?= e($cityName) ?> — rates and contacts
        <?php endif; ?>
    </h2>
    <div class="text-sm text-black leading-relaxed space-y-2">
        <?php if ($lang==='hy'): ?>
        <p><?= e($displayName) ?>-ի <?= e($branchName) ?> մասնաճյուղում (<?= e($cityName) ?>) կարող եք փոխանակել ԱՄՆ դոլար, եվրո, ռուբլի և այլ արժույթներ։ Հասցե՝ <?= e($addr) ?><?php if ($phone): ?>։ Հեռախոս՝ <?= e($phone) ?><?php endif; ?>։</p>
        <p>Կուրսերը թարմացվում են ավտոմատ։ Գործարքից առաջ խորհուրդ ենք տալիս զանգահարել և հաստատել վերջնական փոխարժեքը։ Տվյալները իրազեկման բնույթ ունեն։</p>
        <?php elseif ($lang==='ru'): ?>
        <p>В филиале <?= e($displayName) ?> «<?= e($branchName) ?>» (<?= e($cityName) ?>) можно обменять доллар США, евро, рубль и другие валюты. Адрес: <?= e($addr) ?><?php if ($phone): ?>. Телефон: <?= e($phone) ?><?php endif; ?>.</p>
        <p>Курсы обновляются автоматически. Перед сделкой позвоните и подтвердите итоговый курс. Данные носят информационный характер.</p>
        <?php else: ?>
        <p>At the <?= e($displayName) ?> branch «<?= e($branchName) ?>» in <?= e($cityName) ?> you can exchange USD, EUR, RUB and other currencies. Address: <?= e($addr) ?><?php if ($phone): ?>. Phone: <?= e($phone) ?><?php endif; ?>.</p>
        <p>Rates update automatically. Before exchanging, call and confirm the final rate. Information is for reference only.</p>
        <?php endif; ?>
    </div>
</article>

<div class="border border-amber-400 bg-amber-50 rounded-xl px-4 py-3 text-sm text-black mb-6">
    <?= e(t('disclaimer_short')) ?>
</div>

<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "BankOrCreditUnion",
  "name": "<?= e($displayName) ?> — <?= e($branchName) ?>",
  "telephone": "<?= e($phone) ?>",
  "address": {
    "@type": "PostalAddress",
    "streetAddress": "<?= e($addr) ?>",
    "addressLocality": "<?= e($cityName) ?>",
    "addressCountry": "AM"
  }
}
</script>
<?php require __DIR__ . '/includes/footer.php'; ?>
