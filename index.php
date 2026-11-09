<?php
require_once __DIR__ . '/includes/functions.php';
$lang = detect_lang();
$L = load_lang($lang);

$allCur = all_currency_codes();
$defaultCols = ['USD', 'EUR', 'RUR', 'GBP', 'GEL'];
$multi = get_multi_currency_table($allCur);
$updatedAt = $multi['updated_at'] ?? null;
$flags = currency_flags_map();

$pageTitle = t('today_rates') . ' | ' . t('site_name');
$pageDescription = t('hero_sub');
$pageCanonical = rtrim(SITE_URL, '/') . '/';
$active = 'home';

$jsData = [
    'banks' => [],
    'best_buy' => $multi['best_buy'] ?? [],
    'updated' => $updatedAt ? format_datetime($updatedAt) : '',
    'flags' => $flags,
    'names' => [],
];
foreach ($multi['banks'] as $b) {
    $jsData['banks'][] = [
        'code' => $b['code'],
        'name' => bank_display_name($b['code'], $lang, $b['name'] ?? null),
        'logo' => $b['logo'] ?? '',
        'url' => bank_page_url($b['code']),
        'rates' => $b['rates'],
    ];
}
foreach ($allCur as $c) {
    $jsData['names'][$c] = currency_name($c, $lang);
}

require __DIR__ . '/includes/header.php';
?>

<section class="relative overflow-hidden rounded-2xl bg-brand text-white p-8 sm:p-12 mb-10 shadow-lg">
    <div class="relative z-10 max-w-2xl">
        <p class="text-sm font-medium mb-3 tracking-wide uppercase" style="color:#fff"><?= e(t('site_tagline')) ?></p>
        <h1 class="text-3xl sm:text-4xl lg:text-5xl font-bold leading-tight tracking-tight mb-4" style="color:#fff"><?= e(t('hero_title')) ?></h1>
        <p class="text-base sm:text-lg leading-relaxed mb-6" style="color:#fff"><?= e(t('hero_sub')) ?></p>
        <?php if ($updatedAt): ?>
        <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full text-sm" style="background:rgba(255,255,255,.15);color:#fff">
            <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
            <?= e(t('updated')) ?>: <?= e(format_datetime($updatedAt)) ?>
        </div>
        <?php endif; ?>
    </div>
</section>

<section class="mb-12">
    <h2 class="text-xl sm:text-2xl font-bold text-black mb-5"><?= e(t('compare_banks')) ?></h2>
    <div class="bg-white border border-black/10 rounded-2xl overflow-hidden shadow-sm">
        <div class="scroll-x">
            <table class="w-full text-sm" id="rates-table">
                <thead>
                    <tr class="bg-brand-light border-b border-black/10">
                        <th class="text-left px-4 py-3 font-semibold text-black sticky left-0 bg-brand-light z-10 min-w-[180px]"><?= e(t('bank')) ?></th>
                        <?php foreach ($defaultCols as $i => $c): ?>
                        <th colspan="2" class="text-center px-2 py-2 font-semibold text-black border-l border-black/5" data-col="<?= (int)$i ?>">
                            <div class="inline-flex items-center justify-center gap-1.5">
                                <img src="<?= e($flags[$c] ?? currency_flag($c)) ?>" alt="<?= e($c) ?>" width="20" height="14" class="flag-img object-contain" data-col="<?= (int)$i ?>" onerror="this.style.display='none'">
                                <select class="cur-select bg-transparent font-semibold text-black border border-black/15 rounded-lg px-2 py-1 text-sm cursor-pointer" data-col="<?= (int)$i ?>">
                                    <?php foreach ($allCur as $opt): ?>
                                    <option value="<?= e($opt) ?>" <?= $opt === $c ? 'selected' : '' ?>><?= e($opt) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </th>
                        <?php endforeach; ?>
                        <th class="text-right px-4 py-3 font-semibold text-black border-l border-black/5 whitespace-nowrap"><?= e(t('updated')) ?></th>
                    </tr>
                    <tr class="bg-brand-light border-b border-black/10 text-xs" id="subhead">
                        <th class="sticky left-0 bg-brand-light"></th>
                        <?php foreach ($defaultCols as $c): ?>
                        <th class="text-center px-2 py-1.5 font-medium text-black border-l border-black/5"><?= e(t('buy')) ?></th>
                        <th class="text-center px-2 py-1.5 font-medium text-black"><?= e(t('sell')) ?></th>
                        <?php endforeach; ?>
                        <th class="border-l border-black/5"></th>
                    </tr>
                </thead>
                <tbody id="rates-body"></tbody>
            </table>
        </div>
    </div>
    <p class="text-sm text-black mt-3"><?= e(t('disclaimer_short')) ?></p>
</section>

<script>
(function(){
    const DATA = <?= json_encode($jsData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const cols = <?= json_encode($defaultCols) ?>;
    const body = document.getElementById('rates-body');
    if (!DATA || !DATA.banks) return;

    function bestBuy(cur) {
        let best = null;
        DATA.banks.forEach(function(b) {
            var v = b.rates && b.rates[cur] ? b.rates[cur].buy : null;
            if (v != null && (best === null || v > best)) best = v;
        });
        return best;
    }

    function fmt(n) {
        if (n == null || n === '') return '—';
        return Number(n).toLocaleString('en', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    }

    function render() {
        var best = {};
        cols.forEach(function(c) { best[c] = bestBuy(c); });
        var html = '';
        DATA.banks.forEach(function(bank) {
            html += '<tr class="border-b border-black/5 hover:bg-brand-light/40">';
            html += '<td class="px-4 py-3 sticky left-0 bg-white z-10"><a href="' + bank.url + '" class="flex items-center gap-3 no-underline group">';
            if (bank.logo) {
                html += '<img src="' + bank.logo + '" alt="" class="w-8 h-8 rounded-lg object-contain border border-black/5" width="32" height="32" loading="lazy" onerror="this.style.display=\'none\'">';
            }
            html += '<span class="font-medium text-black group-hover:text-brand">' + bank.name + '</span></a></td>';
            cols.forEach(function(c) {
                var r = (bank.rates && bank.rates[c]) ? bank.rates[c] : {};
                var buy = r.buy, sell = r.sell;
                var isBest = buy != null && best[c] != null && Math.abs(buy - best[c]) < 0.001;
                html += '<td class="text-center px-2 py-3 border-l border-black/5 font-semibold ' + (isBest ? 'text-emerald-700 bg-emerald-50' : 'text-black') + '">' + fmt(buy) + '</td>';
                html += '<td class="text-center px-2 py-3 font-semibold text-black">' + fmt(sell) + '</td>';
            });
            html += '<td class="text-right px-4 py-3 text-sm text-black border-l border-black/5 whitespace-nowrap">' + (DATA.updated || '—') + '</td>';
            html += '</tr>';
        });
        body.innerHTML = html || '<tr><td colspan="12" class="px-4 py-8 text-center text-black"><?= e(t('no_data')) ?></td></tr>';
    }

    document.querySelectorAll('.cur-select').forEach(function(sel) {
        sel.addEventListener('change', function() {
            var i = +this.getAttribute('data-col');
            cols[i] = this.value;
            var img = document.querySelector('img.flag-img[data-col="' + i + '"]');
            if (img && DATA.flags && DATA.flags[this.value]) {
                img.src = DATA.flags[this.value];
                img.alt = this.value;
                img.style.display = '';
            }
            render();
        });
    });
    render();
})();
</script>

<section class="mb-12 space-y-8">
    <article class="bg-white border border-black/10 rounded-2xl p-6 sm:p-8">
        <h2 class="text-xl font-bold text-black mb-4">
            <?php if ($lang==='hy'): ?>Ինչպես փոխանակել արժույթ Հայաստանում անվտանգ
            <?php elseif ($lang==='ru'): ?>Как безопасно обменять валюту в Армении
            <?php else: ?>How to exchange currency safely in Armenia
            <?php endif; ?>
        </h2>
        <div class="text-black leading-relaxed space-y-3 text-[15px]">
            <?php if ($lang==='hy'): ?>
            <p>Հայաստանում արժույթի փոխանակումը կատարվում է առևտրային բանկերում և լիցենզավորված փոխանակման կետերում։ Լավագույն փոխարժեք գտնելու համար համեմատեք մի քանի բանկերի գնման և վաճառքի կուրսերը։</p>
            <p>Մեր կայքը հավաքում է բանկերի պաշտոնական կանխիկ փոխարժեքները և թարմացնում է դրանք մոտավորապես յուրաքանչյուր 15 րոպեն մեկ։ Աղյուսակում կարող եք փոխել արժույթները dropdown-ով։</p>
            <p><strong>Անվտանգության խորհուրդներ.</strong> Փոխանակեք միայն բանկում, պահանջեք անդորրագիր, ստուգեք գումարը։ Մեծ գումարների դեպքում նախապես զանգահարեք բանկ։</p>
            <?php elseif ($lang==='ru'): ?>
            <p>В Армении обмен валюты проводится в коммерческих банках и лицензированных пунктах. Сравнивайте покупку и продажу в нескольких банках.</p>
            <p>Сайт собирает официальные наличные курсы банков и обновляет их примерно каждые 15 минут. В таблице можно менять валюты через выпадающий список.</p>
            <p><strong>Советы по безопасности:</strong> Обменивайте только в банке, требуйте чек, проверяйте сумму. При крупных суммах заранее позвоните в банк.</p>
            <?php else: ?>
            <p>In Armenia, exchange currency at commercial banks and licensed offices. Compare buy and sell rates across banks.</p>
            <p>This site aggregates official cash rates and updates about every 15 minutes. Use the dropdowns in the table to switch currencies.</p>
            <p><strong>Safety tips:</strong> Exchange only at a bank, take a receipt, check the amount. For large sums, call the bank ahead.</p>
            <?php endif; ?>
        </div>
    </article>
    <article class="bg-brand-light border border-brand/20 rounded-2xl p-6 sm:p-8">
        <h2 class="text-xl font-bold text-black mb-4">
            <?php if ($lang==='hy'): ?>Ինչու համեմատել բանկերի փոխարժեքները
            <?php elseif ($lang==='ru'): ?>Зачем сравнивать курсы банков
            <?php else: ?>Why compare bank exchange rates
            <?php endif; ?>
        </h2>
        <div class="text-black leading-relaxed space-y-3 text-[15px]">
            <?php if ($lang==='hy'): ?>
            <p>Տարբեր բանկեր առաջարկում են տարբեր կուրսեր։ Տարբերությունը մեծ գումարների դեպքում նշանակալի է։ Կանաչով նշված են լավագույն գնման կուրսերը։</p>
            <p>Կուրսերը փոխվում են օրվա ընթացքում։ Միշտ հաստատեք վերջնական գինը բանկում։ Կայքը չի կատարում փոխանակում՝ միայն համեմատություն։</p>
            <?php elseif ($lang==='ru'): ?>
            <p>Разные банки дают разные курсы. На крупных суммах разница существенна. Лучшие курсы покупки выделены зелёным.</p>
            <p>Курсы меняются в течение дня. Всегда подтверждайте цену в банке. Сайт не проводит обмен — только сравнение.</p>
            <?php else: ?>
            <p>Banks offer different rates. On large amounts the difference matters. Best buy rates are highlighted in green.</p>
            <p>Rates change during the day. Always confirm at the bank. This site does not exchange currency — only comparison.</p>
            <?php endif; ?>
        </div>
    </article>
</section>

<div class="border border-amber-400 bg-amber-50 rounded-xl px-4 py-3 text-sm text-black mb-6">
    <?= e(t('disclaimer_full')) ?>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
