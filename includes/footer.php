</div>
</main>
<footer class="bg-black text-white mt-auto">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 py-12">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-10 mb-10">
            <div>
                <div class="flex items-center gap-2.5 mb-4">
                    <span class="w-8 h-8 rounded-lg bg-brand text-white flex items-center justify-center font-bold">֏</span>
                    <span class="text-white font-semibold"><?= e(t('site_name')) ?></span>
                </div>
                <p class="text-sm leading-relaxed max-w-xs text-white/80"><?= e(t('footer_text')) ?></p>
            </div>
            <div>
                <h4 class="text-white text-sm font-semibold mb-3"><?= e(t('currency_rates')) ?></h4>
                <ul class="space-y-2 text-sm">
                    <li><a href="/currency.php?cur=USD" class="text-white/80 hover:text-white no-underline">USD</a></li>
                    <li><a href="/currency.php?cur=EUR" class="text-white/80 hover:text-white no-underline">EUR</a></li>
                    <li><a href="/currency.php?cur=RUR" class="text-white/80 hover:text-white no-underline">RUR</a></li>
                    <li><a href="/currency.php?cur=GBP" class="text-white/80 hover:text-white no-underline">GBP</a></li>
                </ul>
            </div>
            <div>
                <h4 class="text-white text-sm font-semibold mb-3"><?= e(t('about')) ?></h4>
                <ul class="space-y-2 text-sm">
                    <li><a href="/about.php" class="text-white/80 hover:text-white no-underline"><?= e(t('about')) ?></a></li>
                    <li><a href="/contact.php" class="text-white/80 hover:text-white no-underline"><?= e(t('contact')) ?></a></li>
                    <li><a href="/privacy.php" class="text-white/80 hover:text-white no-underline"><?= e(t('privacy')) ?></a></li>
                    <li><a href="/disclaimer.php" class="text-white/80 hover:text-white no-underline"><?= e(t('disclaimer')) ?></a></li>
                </ul>
            </div>
        </div>
        <div class="border-t border-white/15 pt-6 text-xs text-white/60">
            <p class="mb-2"><?= e(t('disclaimer_short')) ?></p>
            <p>© <?= date('Y') ?> <?= e(t('site_name')) ?>. <?= e(t('rights')) ?></p>
        </div>
    </div>
</footer>
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@graph": [
    {
      "@type": "Organization",
      "@id": "<?= e(SITE_URL) ?>/#organization",
      "name": "<?= e(t('site_name')) ?>",
      "url": "<?= e(SITE_URL) ?>",
      "description": "<?= e(t('footer_text')) ?>",
      "address": { "@type": "PostalAddress", "addressLocality": "Yerevan", "addressCountry": "AM" },
      "contactPoint": { "@type": "ContactPoint", "email": "<?= e(CONTACT_EMAIL) ?>", "contactType": "customer service", "availableLanguage": ["hy","ru","en"] }
    },
    {
      "@type": "WebSite",
      "@id": "<?= e(SITE_URL) ?>/#website",
      "url": "<?= e(SITE_URL) ?>",
      "name": "<?= e(t('site_name')) ?>",
      "publisher": { "@id": "<?= e(SITE_URL) ?>/#organization" },
      "inLanguage": ["hy","ru","en"]
    }
  ]
}
</script>
</body>
</html>
