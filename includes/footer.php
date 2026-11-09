<?php if (empty($fullWidth)): ?></div><?php endif; ?>
</main>
<footer class="mt-16 bg-gradient-to-b from-slate-100/70 to-white border-t border-[#eaedf2]">
    <div class="mx-auto max-w-7xl px-4 sm:px-7 pt-12 pb-8">
        <?php
        $_fNav = cms_nav('footer');
        $_fCols = [];
        foreach ($_fNav as $_fi) $_fCols[(int)$_fi['grp']][] = $_fi;
        ksort($_fCols);
        ?>
        <div class="grid grid-cols-2 lg:grid-cols-5 gap-x-6 gap-y-10 mb-10">
            <div class="col-span-2 lg:col-span-1">
                <a href="/" title="<?= e(t('site_name')) ?> — <?= e(t('today_rates')) ?>" class="inline-flex items-center gap-2.5 mb-4 no-underline">
                    <span class="w-9 h-9 bg-blue-600 rounded-[10px] flex items-center justify-center text-white font-extrabold text-[19px]" aria-hidden="true">֏</span>
                    <span class="text-[17px] font-black text-slate-900 tracking-tight"><?= e(t('site_name')) ?></span>
                </a>
                <p class="text-sm leading-relaxed text-black max-w-xs"><?= e(t('footer_text')) ?></p>
            </div>
            <?php foreach ($_fCols as $_fg => $_fItems): ?>
            <div>
                <h4 class="text-[15px] font-extrabold text-slate-800 mb-4"><?= e(t('footer.col' . min(max((int)$_fg, 1), 4))) ?></h4>
                <ul class="space-y-3 text-sm">
                    <?php foreach ($_fItems as $_fl): ?>
                    <li><?= ui_link($_fl['url'], nav_label($_fl, $lang), nav_title($_fl, $lang), 'no-underline hover:underline',
                        array_filter(['target' => $_fl['new_tab'] ? '_blank' : null, 'rel' => trim(($_fl['new_tab'] ? 'noopener ' : '') . ($_fl['nofollow'] ? 'nofollow' : '')) ?: null])) ?></li>
                    <?php endforeach; ?>
                    <?php if ((int)$_fg === 4 && CONTACT_PHONE): ?>
                    <li><?= ui_link('tel:' . preg_replace('/\s+/', '', CONTACT_PHONE), CONTACT_PHONE, t('phone') . ': ' . CONTACT_PHONE, 'no-underline hover:underline') ?></li>
                    <?php endif; ?>
                </ul>
            </div>
            <?php endforeach; ?>
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
