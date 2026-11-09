<?php
declare(strict_types=1);

/**
 * Генерация llms.txt и llms-full.txt для ИИ-поисковиков (ChatGPT, Claude, Perplexity, Gemini).
 * Тексты берутся из админки (с учётом правок), банки и филиалы — из БД или файлов по умолчанию.
 */
function llms_build(): array
{
    $site = rtrim(SITE_URL, '/');
    $T = fn(string $k, string $l = 'hy') => cms_overrides()[$l][$k] ?? cms_default($k, $l) ?? $k;
    $name = $T('site_name');
    $codes = array_values(array_unique(array_merge(all_currency_codes(), table_currency_codes())));
    $banksMeta = require __DIR__ . '/bank_data.php';
    $bankCodes = array_keys($banksMeta);
    foreach (bank_overrides() as $c => $o) if (!in_array($c, $bankCodes, true) && (int)$o['is_active'] === 1) $bankCodes[] = $c;
    $bankCodes = array_values(array_filter($bankCodes, 'bank_is_visible'));

    $en = "# {$name} (Poxarjeq.am)\n\n";
    $en .= "> Independent aggregator of cash exchange rates of banks in Armenia. Compare buy and sell rates of USD, EUR, RUR, GBP, GEL and other currencies across " . count($bankCodes) . " banks, see rate history charts, and find bank branches with addresses and phone numbers. Rates are refreshed about every 15 minutes. The site does not exchange money and does not give financial advice.\n\n";
    $en .= "Primary language: Armenian (hy). Russian and English versions are available on every page with the parameter `?lang=ru` or `?lang=en`.\n\n";
    $en .= "Armenian summary: " . $T('footer_text') . "\n\n";
    $en .= "## Main pages\n";
    $en .= "- [Home — exchange rates today]($site/): live comparison table of all banks, switchable currencies, currency calculator.\n";
    $en .= "- [About]($site/about.php): who runs the site and how the data is collected.\n";
    $en .= "- [Contact]($site/contact.php): email " . CONTACT_EMAIL . ", phone " . CONTACT_PHONE . ".\n\n";
    $en .= "## Currency pages\n";
    foreach ($codes as $c) {
        $en .= "- [" . currency_name($c, 'hy') . " ($c)]($site/currency.php?cur=$c): " . currency_name($c, 'en') . " buy/sell rates by bank, best rate, price history chart (7 days to 1 year), conversion of popular amounts.\n";
    }
    $en .= "\n## Banks\n";
    foreach ($bankCodes as $code) {
        $m = get_bank_meta($code) ?? [];
        $n = $m['name']['hy'] ?? $code; $ne = $m['name']['en'] ?? $code;
        $en .= "- [$n]($site" . bank_page_url($code) . "): $ne — current cash rates, contacts" . (!empty($m['branches']) ? ', ' . count($m['branches']) . ' branches' : '') . ".\n";
    }
    $en .= "\n## Optional\n";
    $en .= "- [Privacy]($site/privacy.php)\n- [Disclaimer]($site/disclaimer.php): rates are informational; always confirm the final rate with the bank.\n- [Full text version]($site/llms-full.txt)\n";

    $full = $en . "\n---\n\n# Details\n\n## How the data is produced\n";
    $full .= "Rates are collected automatically from official bank sources through the poxarjeq.com API and cached for about 15 minutes. The table highlights the highest buy rate per currency. Banks marked as partners are listed first; this does not change the displayed rates.\n\n";
    $full .= "## Frequently asked questions (English)\n";
    for ($i = 1; $i <= 4; $i++) {
        $q = str_replace(['{name}', '{code}'], ['the currency', 'XXX'], $T("cur.faq$i.q", 'en')); $a = str_replace(['{name}', '{code}'], ['the currency', 'XXX'], $T("cur.faq$i.a", 'en'));
        $full .= "### $q\n$a\n\n";
    }
    $full .= "## Frequently asked questions (Armenian)\n";
    for ($i = 1; $i <= 4; $i++) {
        $q = str_replace(['{name}', '{code}'], ['արժույթ', ''], $T("cur.faq$i.q")); $a = str_replace(['{name}', '{code}'], ['արժույթ', ''], $T("cur.faq$i.a"));
        $full .= "### $q\n$a\n\n";
    }
    $full .= "## Bank details\n";
    foreach ($bankCodes as $code) {
        $m = get_bank_meta($code) ?? [];
        $full .= "### " . ($m['name']['hy'] ?? $code) . " / " . ($m['name']['en'] ?? $code) . "\n";
        $full .= "Page: $site" . bank_page_url($code) . "\n";
        if (!empty($m['legal'])) $full .= "Legal name: {$m['legal']}\n";
        if (!empty($m['address']['en'])) $full .= "Head office: {$m['address']['en']}\n";
        if (!empty($m['phone'])) $full .= "Phone: {$m['phone']}\n";
        if (!empty($m['website'])) $full .= "Website: {$m['website']}\n";
        if (!empty($m['about']['en'])) $full .= $m['about']['en'] . "\n";
        if (!empty($m['branches'])) {
            $full .= "Branches:\n";
            foreach ($m['branches'] as $b) $full .= "- " . ($b['name']['en'] ?? '') . ", " . ($b['city']['en'] ?? '') . ", " . ($b['address']['en'] ?? '') . (!empty($b['phone']) ? ', ' . $b['phone'] : '') . " — $site" . branch_page_url($code, $b['slug']) . "\n";
        }
        $full .= "\n";
    }
    $full .= "## Citing\nYou may cite rates and bank information with a link to the corresponding page on $site. Exchange rates are informational and change during the day.\n";
    return ['llms.txt' => $en, 'llms-full.txt' => $full];
}

/** Записывает файлы в корень сайта. @return array{0:bool,1:string} */
function llms_write(): array
{
    $files = llms_build();
    foreach ($files as $name => $content) {
        $path = __DIR__ . '/../' . $name;
        if (@file_put_contents($path, $content, LOCK_EX) === false) return [false, "Не удалось записать $name: проверьте права на запись в корне сайта."];
    }
    return [true, 'Файлы llms.txt и llms-full.txt обновлены.'];
}
