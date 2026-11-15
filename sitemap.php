<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/sitemap_data.php';

header('Content-Type: application/xml; charset=utf-8');
header('Cache-Control: public, max-age=900');
$base = rtrim(SITE_URL, '/');
$parts = ['pages', 'currencies', 'banks', 'branches'];
$part = (string)($_GET['part'] ?? '');
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";

if (!in_array($part, $parts, true)) {
    echo '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
    foreach ($parts as $p) {
        $last = '';
        foreach (sm_entries($p) as $en) if ($en['lastmod'] > $last) $last = $en['lastmod'];
        echo '  <sitemap><loc>' . $base . '/sitemap-' . $p . '.xml</loc>' . ($last ? '<lastmod>' . $last . '</lastmod>' : '') . "</sitemap>\n";
    }
    echo '</sitemapindex>';
    exit;
}

echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">' . "\n";
foreach (sm_entries($part) as $en) {
    $alts = page_urls($en['path']);
    foreach (SUPPORTED_LANGS as $l) {
        echo "  <url>\n    <loc>" . htmlspecialchars($alts[$l], ENT_XML1) . "</loc>\n";
        foreach (SUPPORTED_LANGS as $al) echo '    <xhtml:link rel="alternate" hreflang="' . $al . '" href="' . htmlspecialchars($alts[$al], ENT_XML1) . '"/>' . "\n";
        echo '    <xhtml:link rel="alternate" hreflang="x-default" href="' . htmlspecialchars($alts[DEFAULT_LANG], ENT_XML1) . '"/>' . "\n";
        echo '    <lastmod>' . $en['lastmod'] . "</lastmod>\n    <changefreq>" . $en['freq'] . "</changefreq>\n    <priority>" . $en['prio'] . "</priority>\n  </url>\n";
    }
}
echo '</urlset>';
