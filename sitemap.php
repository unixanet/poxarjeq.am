<?php
require_once __DIR__ . '/includes/functions.php';
header('Content-Type: application/xml; charset=utf-8');
$base = rtrim(SITE_URL, '/');
$urls = [
    ['loc' => $base . '/', 'priority' => '1.0', 'changefreq' => 'hourly'],
    ['loc' => $base . '/about.php', 'priority' => '0.5', 'changefreq' => 'monthly'],
    ['loc' => $base . '/contact.php', 'priority' => '0.4', 'changefreq' => 'monthly'],
    ['loc' => $base . '/privacy.php', 'priority' => '0.3', 'changefreq' => 'yearly'],
    ['loc' => $base . '/disclaimer.php', 'priority' => '0.3', 'changefreq' => 'yearly'],
];
foreach (all_currency_codes() as $c) {
    $urls[] = ['loc' => $base . '/currency.php?cur=' . $c, 'priority' => '0.9', 'changefreq' => 'hourly'];
}
$list = get_banks_list();
if ($list && !empty($list['banks'])) {
    foreach ($list['banks'] as $b) {
        $urls[] = ['loc' => $base . bank_page_url($b['code']), 'priority' => '0.7', 'changefreq' => 'daily'];
    }
}
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
<?php foreach ($urls as $u): ?>
  <url>
    <loc><?= htmlspecialchars($u['loc']) ?></loc>
    <changefreq><?= $u['changefreq'] ?></changefreq>
    <priority><?= $u['priority'] ?></priority>
    <lastmod><?= date('Y-m-d') ?></lastmod>
  </url>
<?php endforeach; ?>
</urlset>
