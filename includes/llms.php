<?php
declare(strict_types=1);

function llms_build(): array
{
    $site = rtrim(SITE_URL, '/');
    $T = fn(string $k, string $l = 'hy') => cms_text_in($k, $l);
    $codes = array_values(array_unique(array_merge(all_currency_codes(), table_currency_codes())));
    $bankCodes = array_values(array_filter(sm_bank_codes_safe(), fn($c) => bank_is_visible($c) && get_bank_meta($c)));
    $branchesN = 0;
    foreach ($bankCodes as $c) $branchesN += count(get_bank_meta($c)['branches'] ?? []);

    $en = "# Poxarjeq.am\n\n";
    $en .= "> Independent comparison of cash currency exchange rates in Armenia: buy and sell rates of " . count($bankCodes) . " banks and of exchange offices in Yerevan, bank branches with addresses and phone numbers, gold prices and financial news. Rates are refreshed about every 15 minutes and every page shows the time of its last update.\n\n";
    $en .= "Primary language: Armenian (hy). Russian and English versions: add `?lang=ru` or `?lang=en` to any page URL. Poxarjeq.am is an official project of Leads.am and a sister site of Poxarjeq.com (loans, deposits and cards).\n\n";
    $en .= "## Main pages\n";
    $en .= "- [Exchange rates today]($site/): bank-by-bank table for USD, EUR, RUR, GBP, GEL and more, with a converter.\n";
    $en .= "- [Banks]($site/banks): all banks with USD and EUR rates, plus branches grouped by city.\n";
    $en .= "- [Exchange offices]($site/poxanakman-keter): rates of exchange offices in Yerevan, sorted by the best USD buy rate.\n";
    $en .= "- [Gold price]($site/voski): daily gold price by purity (583 and others) and for bars.\n";
    $en .= "- [News]($site/news): financial news headlines; full articles are on poxarjeq.com.\n";
    $en .= "- [About]($site/about.php): who runs the site, where the data comes from, partner disclosure.\n";
    $en .= "- [Contact]($site/contact.php): " . CONTACT_ADDRESS['en'] . ", " . CONTACT_PHONE . ", " . CONTACT_EMAIL . ".\n\n";
    $en .= "## Currency pages\n";
    foreach ($codes as $c) {
        $en .= "- [" . cur_name($c, 'en') . " to AMD]($site/currency.php?cur=$c): buy and sell rates at every bank, best rate, history chart up to one year.\n";
    }
    $en .= "\n## Banks\n";
    foreach ($bankCodes as $code) {
        $m = get_bank_meta($code) ?? [];
        $n = $m['name']['en'] ?? $code;
        $en .= "- [$n]($site" . bank_page_url($code) . "): current cash rates, contacts" . (!empty($m['branches']) ? ', ' . count($m['branches']) . ' branches' : '') . ". Per-currency pages: $site" . bank_page_url($code) . "/usd, /eur, /rur and others.\n";
    }
    $en .= "\n## Optional\n";
    $en .= "- [Privacy]($site/privacy.php)\n- [Disclaimer]($site/disclaimer.php): rates are informational; confirm the final rate with the bank.\n- [Sitemap]($site/sitemap.xml)\n- [Full text version]($site/llms-full.txt)\n";

    $full = $en . "\n---\n\n# Details\n\n## Where the data comes from\n";
    $full .= "Cash rates are published by banks and exchange offices, collected into the Poxarjeq.com database and shown here. They refresh about every 15 minutes. Each page states the time of the last update. The highest buy rate and the lowest sell rate are highlighted. Partner banks carry a label and their rates are not altered. The site does not exchange money; the bank or office sets the final rate.\n\n";
    $full .= "## How to read a rate\n";
    $full .= "Buy is what the bank pays you in drams for one unit of currency. Sell is what you pay the bank in drams for one unit. The gap is the bank's margin.\n\n";
    $full .= "## Bank details\n";
    foreach ($bankCodes as $code) {
        $m = get_bank_meta($code) ?? [];
        $full .= "### " . ($m['name']['en'] ?? $code) . " / " . ($m['name']['hy'] ?? $code) . " / " . ($m['name']['ru'] ?? $code) . "\n";
        $full .= "Page: $site" . bank_page_url($code) . "\n";
        if (!empty($m['legal'])) $full .= "Legal name: {$m['legal']}\n";
        if (!empty($m['address']['en'])) $full .= "Head office: {$m['address']['en']}\n";
        if (!empty($m['phone'])) $full .= "Phone: {$m['phone']}\n";
        if (!empty($m['website'])) $full .= "Website: {$m['website']}\n";
        if (!empty($m['about']['en'])) $full .= $m['about']['en'] . "\n";
        if (!empty($m['branches'])) {
            $full .= "Branches:\n";
            foreach ($m['branches'] as $b) $full .= "- " . ($b['name']['en'] ?? '') . ", " . ($b['city']['en'] ?? '') . ", " . ($b['address']['en'] ?? '') . (!empty($b['phone']) ? ', ' . $b['phone'] : '') . " ($site" . branch_page_url($code, $b['slug']) . ")\n";
        }
        $full .= "\n";
    }
    $full .= "## Citing\nYou may cite rates and bank information with a link to the corresponding page on $site and mention the update time shown on the page. Exchange rates change during the day.\n";
    return ['llms.txt' => $en, 'llms-full.txt' => $full];
}

function llms_write(): array
{
    $files = llms_build();
    foreach ($files as $name => $content) {
        $path = __DIR__ . '/../' . $name;
        if (@file_put_contents($path, $content, LOCK_EX) === false) return [false, "Не удалось записать $name: проверьте права на запись в корне сайта."];
    }
    return [true, 'Файлы llms.txt и llms-full.txt обновлены.'];
}
