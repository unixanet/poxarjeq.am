<?php
require_once __DIR__ . '/includes/functions.php';
$lang = detect_lang();
$L = load_lang($lang);

/* ---- Данные: курсы по ВСЕМ валютам из API одним параллельным запросом ---- */
$matrix   = get_rates_matrix(table_currency_codes());
$have     = $matrix['currencies'];                 // валюты, по которым реально есть курсы
$updatedAt = $matrix['updated_at'] ?? null;

$defaultPref = ['USD', 'EUR', 'RUR', 'GBP', 'GEL'];
$cols = array_values(array_intersect($defaultPref, $have));
foreach ($have as $c) { if (count($cols) >= 5) break; if (!in_array($c, $cols, true)) $cols[] = $c; }

$banks = [];
foreach ($matrix['banks'] as $code => $b) {
    if (!bank_is_visible($code)) continue;           // скрыт в админке
    $banks[$code] = [
        'code'    => $code,
        'name'    => bank_display_name($code, $lang, $b['name'] ?? null),
        'logo'    => bank_logo_url($code, $b['logo'] ?? ''),
        'url'     => bank_page_url($code),
        'partner' => bank_is_partner($code),
        'order'   => bank_partner_order($code),
        'rates'   => $b['rates'],
    ];
}
$sortCur = $cols[0] ?? 'USD';
// Партнёры закреплены сверху (по приоритету из админки), остальные по лучшей покупке
uasort($banks, function ($a, $b) use ($sortCur) {
    if ($a['partner'] !== $b['partner']) return $a['partner'] ? -1 : 1;
    if ($a['partner']) return $a['order'] <=> $b['order'];
    return ($b['rates'][$sortCur]['buy'] ?? 0) <=> ($a['rates'][$sortCur]['buy'] ?? 0);
});

$best = [];
foreach ($have as $c) {
    $m = matrix_best($banks, $c);
    $best[$c] = [
        'buy'  => $m['buy']  ? ['rate' => $m['buy']['rate'],  'name' => $banks[$m['buy']['code']]['name'],  'logo' => $banks[$m['buy']['code']]['logo']]  : null,
        'sell' => $m['sell'] ? ['rate' => $m['sell']['rate'], 'name' => $banks[$m['sell']['code']]['name'], 'logo' => $banks[$m['sell']['code']]['logo']] : null,
    ];
}

$flags = [];
$names = [];
foreach (array_merge($have, ['AMD']) as $c) { $flags[$c] = currency_flag($c); $names[$c] = currency_name($c, $lang); }

$jsData = [
    'banks'   => array_values($banks),
    'cur'     => $have,
    'cols'    => $cols,
    'best'    => $best,
    'flags'   => $flags,
    'names'   => $names,
    'noData'  => t('no_data'),
    'i18n'    => ['partner' => t('partner'), 'buy' => t('buy'), 'sell' => t('sell'), 'best' => t('best_buy'), 'chooseCurrency' => t('choose_currency'), 'bestRate' => t('calc_best')],
];

$pageTitle = t('today_rates') . ' | ' . t('site_name');
$pageDescription = t('hero_sub');
$pageCanonical = rtrim(SITE_URL, '/') . '/';
$active = 'home';
$fullWidth = true;

/* ---- Хелперы серверного рендера (тот же HTML строит JS при смене валюты) ---- */
function idx_chevron(string $cls = 'right-0'): string
{
    return '<svg class="pointer-events-none absolute top-1/2 h-3 w-3 -translate-y-1/2 ' . $cls . '" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 9l6 6 6-6"/></svg>';
}

function idx_row(array $bank, array $cols, array $best, bool $first): string
{
    $h = '<tr class="border-t border-slate-100 hover:bg-blue-50/40 transition-colors">';
    $h .= '<td class="sticky left-0 z-10 bg-white px-4 sm:px-5 py-3 text-left"><a href="' . e($bank['url']) . '" class="flex items-center gap-3 no-underline group">';
    if ($bank['logo']) $h .= '<img src="' . e($bank['logo']) . '" alt="" width="44" height="44" loading="lazy" class="w-11 h-11 rounded-[10px] object-contain shrink-0">';
    $h .= '<span class="font-bold text-slate-900 group-hover:text-blue-600 leading-tight">' . e($bank['name']) . ($bank['partner'] ? '<span class="mt-1 block w-fit rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-extrabold uppercase tracking-wide text-amber-800">★ ' . e(t('partner')) . '</span>' : '') . '</span></a></td>';
    foreach ($cols as $c) {
        $r = $bank['rates'][$c] ?? null;
        $isBest = $r && !empty($best[$c]['buy']) && abs($r['buy'] - $best[$c]['buy']['rate']) < 0.00001;
        $h .= '<td class="px-2 py-3 text-center tabular-nums border-l border-slate-100 font-semibold"><span class="' . ($isBest ? 'inline-block rounded-lg bg-green-50 px-2.5 py-1 font-extrabold text-green-600' : '') . '">' . e($r ? fmt_rate($r['buy']) : '—') . '</span></td>';
        $h .= '<td class="px-2 py-3 text-center tabular-nums font-semibold">' . e($r ? fmt_rate($r['sell']) : '—') . '</td>';
    }
    return $h . '</tr>';
}

function idx_card(array $bank, string $cur, array $best): string
{
    $r = $bank['rates'][$cur] ?? null;
    if (!$r) return '';
    $isBest = !empty($best[$cur]['buy']) && abs($r['buy'] - $best[$cur]['buy']['rate']) < 0.00001;
    $h = '<a href="' . e($bank['url']) . '" class="flex items-center gap-3 rounded-2xl border border-slate-100 bg-white p-3.5 no-underline shadow-[0_6px_24px_rgba(15,23,42,.06)]">';
    $h .= $bank['logo'] ? '<img src="' . e($bank['logo']) . '" alt="" width="48" height="48" loading="lazy" class="w-12 h-12 rounded-xl object-contain shrink-0">' : '<span class="w-12 h-12 shrink-0"></span>';
    $h .= '<span class="min-w-0 flex-1"><span class="block font-bold text-slate-900 leading-tight truncate">' . e($bank['name']) . '</span>';
    if ($bank['partner']) $h .= '<span class="mt-1 mr-1 inline-block rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-extrabold uppercase tracking-wide text-amber-800">★ ' . e(t('partner')) . '</span>';
    if ($isBest) $h .= '<span class="mt-1 inline-block rounded-full bg-green-50 px-2 py-0.5 text-[10px] font-extrabold uppercase tracking-wide text-green-600">' . e(t('best_buy')) . '</span>';
    $h .= '</span><span class="grid grid-cols-2 gap-3 text-right shrink-0">';
    $h .= '<span><span class="block text-[10px] font-bold uppercase tracking-wider text-black">' . e(t('buy')) . '</span><span class="block text-[17px] font-extrabold tabular-nums ' . ($isBest ? 'text-green-600' : 'text-slate-900') . '">' . e(fmt_rate($r['buy'])) . '</span></span>';
    $h .= '<span><span class="block text-[10px] font-bold uppercase tracking-wider text-black">' . e(t('sell')) . '</span><span class="block text-[17px] font-extrabold tabular-nums text-slate-900">' . e(fmt_rate($r['sell'])) . '</span></span>';
    return $h . '</span></a>';
}

$calcFrom = in_array('USD', $have, true) ? 'USD' : ($have[0] ?? 'USD');
$calcBest = $best[$calcFrom]['buy'] ?? null;
$calcInit = $calcBest ? number_format(1000 * $calcBest['rate'], 0, '.', ' ') : '0';
$mobileCur = $cols[0] ?? $calcFrom;
$heroCards = array_values(array_filter(['USD', 'EUR', 'RUR'], fn($c) => !empty($best[$c]['buy'])));

require __DIR__ . '/includes/header.php';
?>

<!-- ===== HERO ===== -->
<section class="bg-white border-b border-slate-100">
    <div class="mx-auto max-w-7xl px-4 sm:px-7 py-8 sm:py-12 lg:py-14 grid lg:grid-cols-[1fr_440px] gap-8 lg:gap-14 items-center">
        <div>
            <?php if ($updatedAt): ?>
            <div class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white/80 px-3 py-1.5 text-xs">
                <span class="relative flex h-2 w-2"><span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-green-400 opacity-75"></span><span class="relative inline-flex h-2 w-2 rounded-full bg-green-500"></span></span>
                <?= e(t('updated')) ?>: <?= e(format_datetime($updatedAt)) ?>
            </div>
            <?php endif; ?>
            <h1 class="mt-4 sm:mt-5 max-w-2xl text-[32px] sm:text-5xl lg:text-[54px] font-black leading-[1.08] tracking-tight text-slate-900 text-balance"><?= e(t('hero_title')) ?></h1>
            <p class="mt-4 max-w-xl text-base sm:text-lg leading-relaxed text-black"><?= e(t('hero_sub')) ?></p>
            <div class="mt-6 flex flex-wrap gap-3">
                <a href="#rates" class="inline-flex items-center justify-center gap-2 min-h-[48px] rounded-[14px] bg-blue-600 px-6 font-bold no-underline shadow-[0_6px_16px_rgba(37,99,235,.28)] hover:bg-blue-700 text-white"><?= e(t('cta_banks')) ?> <span aria-hidden="true">→</span></a>
                <a href="#calc" class="inline-flex items-center justify-center min-h-[48px] rounded-[14px] border border-slate-200 bg-white px-6 font-bold no-underline hover:border-blue-300"><?= e(t('calc_btn')) ?></a>
            </div>
            <?php if ($heroCards): ?>
            <div class="mt-7 sm:mt-9 grid grid-cols-3 gap-2.5 sm:gap-3.5">
                <?php foreach ($heroCards as $c): $bb = $best[$c]['buy']; ?>
                <a href="/currency.php?cur=<?= e($c) ?>" class="block rounded-[18px] sm:rounded-[20px] border border-slate-100 bg-white p-3 sm:p-4 no-underline shadow-[0_6px_24px_rgba(15,23,42,.06)] transition hover:-translate-y-0.5 hover:shadow-[0_10px_30px_rgba(37,99,235,.14)]">
                    <span class="flex items-center gap-2 text-[13px] sm:text-sm font-extrabold text-slate-900"><img src="<?= e($flags[$c]) ?>" alt="" width="28" height="28" class="w-6 h-6 sm:w-7 sm:h-7 rounded-full object-cover shadow"><?= e($c) ?></span>
                    <span class="mt-1.5 block text-[22px] sm:text-[28px] font-black tracking-tight text-slate-900 tabular-nums leading-none"><?= e(fmt_rate($bb['rate'])) ?></span>
                    <span class="mt-1.5 block text-[11px] sm:text-xs leading-tight text-black"><?= e(t('best_buy')) ?> · <?= e($bb['name']) ?></span>
                </a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- Калькулятор -->
        <div id="calc" class="scroll-mt-24 rounded-[28px] sm:rounded-[32px] border border-slate-100 bg-white p-4 sm:p-6 shadow-[0_24px_60px_-12px_rgba(15,23,42,.18)]">
            <h2 class="mb-4 sm:mb-5 flex items-center gap-3 text-lg sm:text-xl font-black text-slate-900">
                <span class="grid h-9 w-9 place-items-center rounded-xl bg-indigo-600 text-white"><svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg></span>
                <?= e(t('calc_title')) ?>
            </h2>
            <div class="rounded-[20px] border border-slate-200 bg-gray-50 p-4 focus-within:border-indigo-500 focus-within:bg-white transition">
                <label for="calc-from-val" class="block text-[10.5px] font-bold uppercase tracking-widest"><?= e(t('calc_give')) ?></label>
                <div class="mt-1.5 flex items-center justify-between gap-3">
                    <input type="number" inputmode="decimal" min="0" id="calc-from-val" value="1000" class="min-w-0 w-full bg-transparent p-0 text-[28px] sm:text-3xl font-black text-slate-900 outline-none border-0 tabular-nums">
                    <div class="flex shrink-0 items-center gap-2 rounded-2xl border border-slate-100 bg-white px-2.5 py-2 shadow-sm">
                        <img id="calc-from-flag" src="<?= e($flags[$calcFrom] ?? '') ?>" alt="" width="28" height="28" class="h-7 w-7 rounded-full object-cover">
                        <span class="relative inline-flex items-center"><select id="calc-from-curr" aria-label="<?= e(t('calc_give')) ?>" class="appearance-none bg-transparent pr-5 text-[15px] font-extrabold uppercase cursor-pointer outline-none">
                            <?php foreach ($have as $c): ?><option value="<?= e($c) ?>" <?= $c === $calcFrom ? 'selected' : '' ?>><?= e($c) ?></option><?php endforeach; ?>
                            <option value="AMD">AMD</option>
                        </select><?= idx_chevron() ?></span>
                    </div>
                </div>
            </div>
            <div class="relative z-10 -my-3.5 flex justify-center">
                <button type="button" id="calc-swap" aria-label="<?= e(t('swap')) ?>" class="grid h-11 w-11 place-items-center rounded-full border border-slate-100 bg-white text-indigo-600 shadow-md transition hover:scale-110 hover:shadow-lg">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"/></svg>
                </button>
            </div>
            <div class="rounded-[20px] border border-slate-200 bg-gray-50 p-4">
                <span class="block text-[10.5px] font-bold uppercase tracking-widest"><?= e(t('calc_get')) ?></span>
                <div class="mt-1.5 flex items-center justify-between gap-3">
                    <output id="calc-to-val" class="min-w-0 w-full truncate text-[28px] sm:text-3xl font-black text-slate-900 tabular-nums"><?= e($calcInit) ?></output>
                    <div class="flex shrink-0 items-center gap-2 rounded-2xl border border-slate-100 bg-white px-2.5 py-2 shadow-sm">
                        <img id="calc-to-flag" src="<?= e($flags['AMD']) ?>" alt="" width="28" height="28" class="h-7 w-7 rounded-full object-cover">
                        <span class="relative inline-flex items-center"><select id="calc-to-curr" aria-label="<?= e(t('calc_get')) ?>" class="appearance-none bg-transparent pr-5 text-[15px] font-extrabold uppercase cursor-pointer outline-none">
                            <option value="AMD" selected>AMD</option>
                            <?php foreach ($have as $c): ?><option value="<?= e($c) ?>"><?= e($c) ?></option><?php endforeach; ?>
                        </select><?= idx_chevron() ?></span>
                    </div>
                </div>
            </div>
            <p id="calc-note" class="mt-4 min-h-[22px] text-center text-[13px] sm:text-sm font-semibold" aria-live="polite">
                <?php if ($calcBest): ?><?= e(t('calc_best')) ?>: <?= e($calcBest['name']) ?> · 1 <?= e($calcFrom) ?> = <?= e(fmt_rate($calcBest['rate'])) ?> AMD<?php endif; ?>
            </p>
        </div>
    </div>
</section>

<!-- ===== КУРСЫ ===== -->
<section id="rates" class="mx-auto max-w-7xl px-4 sm:px-7 pt-10 sm:pt-14 scroll-mt-20">
    <h2 class="mb-5 sm:mb-6 text-2xl sm:text-3xl font-black tracking-tight text-slate-900"><?= e(t('compare_banks')) ?></h2>

    <?php if ($banks): ?>
    <!-- Мобильная версия: одна валюта, карточки банков -->
    <div class="md:hidden">
        <div id="m-chips" class="[scrollbar-width:none] [&::-webkit-scrollbar]:hidden -mx-4 mb-4 flex gap-2 overflow-x-auto px-4 pb-1" role="group" aria-label="<?= e(t('choose_currency')) ?>">
            <?php foreach ($have as $c): $on = ($c === $mobileCur); ?>
            <button type="button" data-cur="<?= e($c) ?>" aria-pressed="<?= $on ? 'true' : 'false' ?>" class="m-chip flex shrink-0 items-center gap-2 min-h-[44px] rounded-xl border px-3.5 text-sm font-bold <?= $on ? 'border-blue-600 bg-blue-600 text-white shadow-[0_2px_8px_rgba(37,99,235,.25)]' : 'border-slate-200 bg-white text-black' ?>">
                <img src="<?= e($flags[$c]) ?>" alt="" width="22" height="22" class="h-[22px] w-[22px] rounded-full object-cover"><?= e($c) ?>
            </button>
            <?php endforeach; ?>
        </div>
        <div id="m-list" class="grid gap-3">
            <?php foreach ($banks as $b) echo idx_card($b, $mobileCur, $best); ?>
        </div>
    </div>

    <!-- Десктоп: таблица, у каждой колонки свой переключатель валюты -->
    <div class="hidden md:block overflow-hidden rounded-3xl border border-slate-100 bg-white shadow-[0_24px_60px_-12px_rgba(15,23,42,.14)]">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[860px] text-sm" id="rates-table">
                <thead>
                    <tr class="bg-gradient-to-b from-slate-50 to-slate-100 border-b border-slate-200">
                        <th scope="col" class="sticky left-0 z-20 bg-slate-50 px-5 py-3 text-left text-[11px] font-extrabold uppercase tracking-wider w-[260px]"><?= e(t('bank')) ?></th>
                        <?php foreach ($cols as $i => $c): ?>
                        <th scope="colgroup" colspan="2" class="border-l border-slate-200 px-2 py-3 text-center" data-col="<?= (int)$i ?>">
                            <span class="flex flex-col items-center gap-1.5">
                                <img src="<?= e($flags[$c]) ?>" alt="" width="32" height="32" class="col-flag h-8 w-8 rounded-full object-cover shadow" data-col="<?= (int)$i ?>">
                                <span class="relative inline-flex items-center"><select class="col-select appearance-none rounded-lg border border-slate-200 bg-white py-1.5 pl-3 pr-7 text-sm font-extrabold uppercase cursor-pointer shadow-sm hover:border-blue-400 focus:border-blue-500 outline-none" data-col="<?= (int)$i ?>" aria-label="<?= e(t('choose_currency')) ?> <?= (int)$i + 1 ?>">
                                    <?php foreach ($have as $opt): ?><option value="<?= e($opt) ?>" <?= $opt === $c ? 'selected' : '' ?>><?= e($opt) ?></option><?php endforeach; ?>
                                </select><?= idx_chevron('right-2.5') ?></span>
                            </span>
                        </th>
                        <?php endforeach; ?>
                    </tr>
                    <tr class="bg-slate-50/80 text-[10px] font-bold uppercase tracking-wider">
                        <th class="sticky left-0 z-20 bg-slate-50"></th>
                        <?php foreach ($cols as $c): ?>
                        <th class="border-l border-slate-200 px-2 py-1.5 text-center"><?= e(t('buy')) ?></th>
                        <th class="px-2 py-1.5 text-center"><?= e(t('sell')) ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody id="rates-body">
                    <?php foreach ($banks as $b) echo idx_row($b, $cols, $best, false); ?>
                </tbody>
            </table>
        </div>
    </div>
    <p class="mt-3 text-sm text-black"><?= e(t('best_marked')) ?> <?= e(t('disclaimer_short')) ?></p>
    <?php else: ?>
    <div class="rounded-3xl border border-slate-100 bg-white p-10 text-center shadow-sm"><?= e(t('no_data')) ?></div>
    <?php endif; ?>
</section>

<script>
(function () {
    var D = <?= json_encode($jsData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) ?>;
    if (!D || !D.banks || !D.banks.length) return;
    var cols = D.cols.slice(), mobileCur = <?= json_encode($mobileCur) ?>;

    function esc(s) { return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) { return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]; }); }
    function fmt(n) {
        if (n == null || !(n > 0)) return '—';
        var s = (Math.round(n * 10000) / 10000).toString();
        var d = s.indexOf('.') < 0 ? 0 : s.length - s.indexOf('.') - 1;
        return d < 2 ? Number(n).toFixed(2) : s;
    }
    function safeGet(k) { try { return localStorage.getItem(k); } catch (e) { return null; } }
    function safeSet(k, v) { try { localStorage.setItem(k, v); } catch (e) {} }
    function isBest(cur, buy) { var b = D.best[cur] && D.best[cur].buy; return b && buy != null && Math.abs(buy - b.rate) < 0.00001; }

    /* ---------- Таблица (десктоп) ---------- */
    function sortBanks(cur) {
        return D.banks.slice().sort(function (a, b) {
            if (!!a.partner !== !!b.partner) return a.partner ? -1 : 1;
            if (a.partner) return (a.order || 0) - (b.order || 0);
            return ((b.rates[cur] || {}).buy || 0) - ((a.rates[cur] || {}).buy || 0);
        });
    }
    var body = document.getElementById('rates-body');
    function rowHtml(b) {
        var h = '<tr class="border-t border-slate-100 hover:bg-blue-50/40 transition-colors"><td class="sticky left-0 z-10 bg-white px-4 sm:px-5 py-3 text-left"><a href="' + esc(b.url) + '" class="flex items-center gap-3 no-underline group">';
        if (b.logo) h += '<img src="' + esc(b.logo) + '" alt="" width="44" height="44" loading="lazy" class="w-11 h-11 rounded-[10px] object-contain shrink-0">';
        h += '<span class="font-bold text-slate-900 group-hover:text-blue-600 leading-tight">' + esc(b.name) + (b.partner ? '<span class="mt-1 block w-fit rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-extrabold uppercase tracking-wide text-amber-800">★ ' + esc(D.i18n.partner) + '</span>' : '') + '</span></a></td>';
        cols.forEach(function (c) {
            var r = b.rates && b.rates[c];
            h += '<td class="px-2 py-3 text-center tabular-nums border-l border-slate-100 font-semibold"><span class="' + (r && isBest(c, r.buy) ? 'inline-block rounded-lg bg-green-50 px-2.5 py-1 font-extrabold text-green-600' : '') + '">' + (r ? fmt(r.buy) : '—') + '</span></td>';
            h += '<td class="px-2 py-3 text-center tabular-nums font-semibold">' + (r ? fmt(r.sell) : '—') + '</td>';
        });
        return h + '</tr>';
    }
    function renderTable() {
        if (!body) return;
        body.innerHTML = sortBanks(cols[0]).map(rowHtml).join('');
    }
    document.querySelectorAll('.col-select').forEach(function (sel) {
        sel.addEventListener('change', function () {
            var i = +this.getAttribute('data-col');
            cols[i] = this.value;
            var img = document.querySelector('.col-flag[data-col="' + i + '"]');
            if (img) img.src = D.flags[this.value] || '';
            renderTable();
            safeSet('pxq_cols', JSON.stringify(cols));
        });
    });

    /* ---------- Карточки (мобильные) ---------- */
    var mList = document.getElementById('m-list'), chips = document.querySelectorAll('.m-chip');
    function cardHtml(b) {
        var r = b.rates && b.rates[mobileCur]; if (!r) return '';
        var best = isBest(mobileCur, r.buy);
        return '<a href="' + esc(b.url) + '" class="flex items-center gap-3 rounded-2xl border border-slate-100 bg-white p-3.5 no-underline shadow-[0_6px_24px_rgba(15,23,42,.06)]">' +
            (b.logo ? '<img src="' + esc(b.logo) + '" alt="" width="48" height="48" loading="lazy" class="w-12 h-12 rounded-xl object-contain shrink-0">' : '<span class="w-12 h-12 shrink-0"></span>') +
            '<span class="min-w-0 flex-1"><span class="block font-bold text-slate-900 leading-tight truncate">' + esc(b.name) + '</span>' +
            (b.partner ? '<span class="mt-1 mr-1 inline-block rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-extrabold uppercase tracking-wide text-amber-800">★ ' + esc(D.i18n.partner) + '</span>' : '') + (best ? '<span class="mt-1 inline-block rounded-full bg-green-50 px-2 py-0.5 text-[10px] font-extrabold uppercase tracking-wide text-green-600">' + esc(D.i18n.best) + '</span>' : '') + '</span>' +
            '<span class="grid grid-cols-2 gap-3 text-right shrink-0"><span><span class="block text-[10px] font-bold uppercase tracking-wider text-black">' + esc(D.i18n.buy) + '</span><span class="block text-[17px] font-extrabold tabular-nums ' + (best ? 'text-green-600' : 'text-slate-900') + '">' + fmt(r.buy) + '</span></span>' +
            '<span><span class="block text-[10px] font-bold uppercase tracking-wider text-black">' + esc(D.i18n.sell) + '</span><span class="block text-[17px] font-extrabold tabular-nums text-slate-900">' + fmt(r.sell) + '</span></span></span></a>';
    }
    function renderCards() {
        if (!mList) return;
        var html = sortBanks(mobileCur).map(cardHtml).join('');
        mList.innerHTML = html || '<div class="rounded-2xl border border-slate-100 bg-white p-8 text-center">' + esc(D.noData) + '</div>';
        chips.forEach(function (ch) {
            var on = ch.getAttribute('data-cur') === mobileCur;
            ch.setAttribute('aria-pressed', on ? 'true' : 'false');
            ch.classList.toggle('border-blue-600', on); ch.classList.toggle('bg-blue-600', on); ch.classList.toggle('border-slate-200', !on); ch.classList.toggle('bg-white', !on);
            ch.classList.toggle('text-white', on); ch.classList.toggle('text-black', !on);
            ch.classList.toggle('shadow-[0_2px_8px_rgba(37,99,235,.25)]', on);
        });
    }
    chips.forEach(function (ch) {
        ch.addEventListener('click', function () { mobileCur = this.getAttribute('data-cur'); renderCards(); safeSet('pxq_mcur', mobileCur); });
    });

    /* ---------- Калькулятор: любая валюта через лучший курс банков ---------- */
    var fromV = document.getElementById('calc-from-val'), fromC = document.getElementById('calc-from-curr'), toC = document.getElementById('calc-to-curr'),
        toV = document.getElementById('calc-to-val'), fromF = document.getElementById('calc-from-flag'), toF = document.getElementById('calc-to-flag'),
        note = document.getElementById('calc-note'), swap = document.getElementById('calc-swap');
    function buyRate(c) { return D.best[c] && D.best[c].buy ? D.best[c].buy.rate : null; }   // банк покупает у вас валюту
    function sellRate(c) { return D.best[c] && D.best[c].sell ? D.best[c].sell.rate : null; } // банк продаёт вам валюту
    function calc() {
        var a = parseFloat(fromV.value), f = fromC.value, t = toC.value;
        fromF.src = D.flags[f] || ''; toF.src = D.flags[t] || '';
        if (!(a >= 0) || isNaN(a)) { toV.textContent = '0'; note.textContent = ''; return; }
        var out = null, info = '';
        if (f === t) { out = a; }
        else if (t === 'AMD') {
            var br = buyRate(f); if (br) { out = a * br; info = D.i18n.bestRate + ': ' + D.best[f].buy.name + ' · 1 ' + f + ' = ' + fmt(br) + ' AMD'; }
        } else if (f === 'AMD') {
            var sr = sellRate(t); if (sr) { out = a / sr; info = D.i18n.bestRate + ': ' + D.best[t].sell.name + ' · 1 ' + t + ' = ' + fmt(sr) + ' AMD'; }
        } else {
            var b1 = buyRate(f), s2 = sellRate(t); if (b1 && s2) { out = a * b1 / s2; info = '1 ' + f + ' ≈ ' + fmt(b1 / s2) + ' ' + t; }
        }
        toV.textContent = out == null ? '—' : out.toLocaleString('en', { maximumFractionDigits: (t === 'AMD' || out >= 100) ? 0 : 2 }).replace(/,/g, ' ');
        note.textContent = info;
    }
    [fromV, fromC, toC].forEach(function (el) { el && el.addEventListener('input', calc); el && el.addEventListener('change', calc); });
    swap && swap.addEventListener('click', function () { var x = fromC.value; fromC.value = toC.value; toC.value = x; calc(); });

    /* ---------- Восстановление выбора посетителя ---------- */
    try {
        var sc = JSON.parse(safeGet('pxq_cols') || 'null');
        if (Array.isArray(sc) && sc.length === cols.length && sc.every(function (c) { return D.cur.indexOf(c) >= 0; })) {
            cols = sc;
            document.querySelectorAll('.col-select').forEach(function (s) { var i = +s.getAttribute('data-col'); s.value = cols[i]; var im = document.querySelector('.col-flag[data-col="' + i + '"]'); if (im) im.src = D.flags[cols[i]] || ''; });
            renderTable();
        }
        var mc = safeGet('pxq_mcur');
        if (mc && D.cur.indexOf(mc) >= 0 && mc !== mobileCur) { mobileCur = mc; renderCards(); }
    } catch (e) {}
    calc();
})();
</script>

<!-- ===== Текст для читателя и поисковиков (без изменений по смыслу) ===== -->
<section class="mx-auto max-w-7xl px-4 sm:px-7 pt-10 sm:pt-14 grid gap-5 lg:grid-cols-2">
    <article class="rounded-3xl border border-slate-100 bg-white p-5 sm:p-8 shadow-[0_10px_40px_rgba(15,23,42,.06)]">
        <h2 class="mb-4 text-xl font-black text-slate-900">
            <?php if ($lang==='hy'): ?>Ինչպես փոխանակել արժույթ Հայաստանում անվտանգ
            <?php elseif ($lang==='ru'): ?>Как безопасно обменять валюту в Армении
            <?php else: ?>How to exchange currency safely in Armenia
            <?php endif; ?>
        </h2>
        <div class="space-y-3 text-[15px] leading-relaxed text-black">
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
    <article class="rounded-3xl border border-blue-100 bg-blue-50 p-5 sm:p-8">
        <h2 class="mb-4 text-xl font-black text-slate-900">
            <?php if ($lang==='hy'): ?>Ինչու համեմատել բանկերի փոխարժեքները
            <?php elseif ($lang==='ru'): ?>Зачем сравнивать курсы банков
            <?php else: ?>Why compare bank exchange rates
            <?php endif; ?>
        </h2>
        <div class="space-y-3 text-[15px] leading-relaxed text-black">
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

<div class="mx-auto max-w-7xl px-4 sm:px-7 pt-6">
    <div class="rounded-2xl border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-black"><?= e(t('disclaimer_full')) ?></div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
