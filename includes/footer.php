<?php if (empty($fullWidth)): ?></div><?php endif; ?>
</main>
<footer class="mt-16 bg-gradient-to-b from-slate-100/70 to-white border-t border-[#eaedf2]">
    <div class="mx-auto max-w-7xl px-4 sm:px-7 pt-12 pb-8">
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-x-6 gap-y-10 mb-10">
            <div class="col-span-2 lg:col-span-1">
                <a href="/" class="inline-flex items-center gap-2.5 mb-4 no-underline">
                    <span class="w-9 h-9 bg-blue-600 rounded-[10px] flex items-center justify-center text-white font-extrabold text-[19px]">֏</span>
                    <span class="text-[17px] font-black text-slate-900 tracking-tight"><?= e(t('site_name')) ?></span>
                </a>
                <p class="text-sm leading-relaxed text-black max-w-xs"><?= e(t('footer_text')) ?></p>
            </div>
            <div>
                <h4 class="text-[15px] font-extrabold text-slate-800 mb-4"><?= e(t('currency_rates')) ?></h4>
                <ul class="space-y-3 text-sm">
                    <?php foreach (['USD','EUR','RUR','GBP'] as $_fc): ?>
                    <li><a href="/currency.php?cur=<?= $_fc ?>" class="no-underline hover:underline"><?= $_fc ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <div>
                <h4 class="text-[15px] font-extrabold text-slate-800 mb-4"><?= e(t('about')) ?></h4>
                <ul class="space-y-3 text-sm">
                    <li><a href="/about.php" class="no-underline hover:underline"><?= e(t('about')) ?></a></li>
                    <li><a href="/contact.php" class="no-underline hover:underline"><?= e(t('contact')) ?></a></li>
                    <li><a href="/privacy.php" class="no-underline hover:underline"><?= e(t('privacy')) ?></a></li>
                    <li><a href="/disclaimer.php" class="no-underline hover:underline"><?= e(t('disclaimer')) ?></a></li>
                </ul>
            </div>
            <div class="col-span-2 lg:col-span-1">
                <h4 class="text-[15px] font-extrabold text-slate-800 mb-4"><?= e(t('contacts')) ?></h4>
                <ul class="space-y-3 text-sm">
                    <li><a href="mailto:<?= e(CONTACT_EMAIL) ?>" class="no-underline hover:underline"><?= e(CONTACT_EMAIL) ?></a></li>
                    <li class="text-black"><?= e(CONTACT_PHONE) ?></li>
                </ul>
            </div>
        </div>
        <div class="border-t border-[#eaedf2] pt-6 text-xs text-black">
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
