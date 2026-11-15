<?php if (empty($fullWidth)): ?></div><?php endif; ?>
</main>
<?php
$_fNav = cms_nav('footer');
$_fCols = [];
foreach ($_fNav as $_fi) $_fCols[(int)$_fi['grp']][] = $_fi;
ksort($_fCols);
$_fTg = t('tg.url');
$_fAddr = CONTACT_ADDRESS[$lang] ?? CONTACT_ADDRESS['hy'];
$_fTel = 'tel:' . preg_replace('/\s+/', '', CONTACT_PHONE);
$_fTgIcon = '<svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M21.9 4.6a1.2 1.2 0 0 0-1.3-.2L2.8 11.3a1.2 1.2 0 0 0 .1 2.2l4.4 1.6 1.7 5.3a1 1 0 0 0 1.7.4l2.5-2.5 4.5 3.3a1.2 1.2 0 0 0 1.9-.7l3-15.1a1.2 1.2 0 0 0-.7-1.2zM9.6 14.4l8.5-5.3-6.7 6.4-.3 2.9z"/></svg>';
?>
<footer class="mt-16 border-t border-[#eaedf2] bg-gradient-to-b from-slate-100/70 to-white">
    <div class="mx-auto max-w-7xl px-4 pb-8 pt-12 sm:px-7">
        <section class="mb-12 flex flex-col gap-5 rounded-[28px] bg-blue-600 p-6 text-white shadow-[0_24px_60px_-12px_rgba(37,99,235,.45)] sm:flex-row sm:items-center sm:justify-between sm:p-8" aria-labelledby="ft-tg">
            <div class="flex items-start gap-4">
                <span class="grid h-12 w-12 shrink-0 place-items-center rounded-2xl bg-white text-blue-600" aria-hidden="true"><?= $_fTgIcon ?></span>
                <div>
                    <h2 id="ft-tg" class="text-xl font-black tracking-tight text-white sm:text-2xl"><?= e(t('tg.title')) ?></h2>
                    <p class="mt-1 max-w-xl text-[15px] leading-relaxed text-white"><?= e(t('tg.text')) ?></p>
                </div>
            </div>
            <?= ui_link($_fTg, e(t('tg.btn')) . ' <span aria-hidden="true">→</span>', t('tg.title_attr'), 'inline-flex min-h-[50px] shrink-0 items-center justify-center gap-2 rounded-[14px] bg-white px-7 text-base font-extrabold text-blue-600 no-underline hover:bg-blue-50', ['target' => '_blank', 'rel' => 'noopener'], true) ?>
        </section>

        <div class="grid grid-cols-2 gap-x-6 gap-y-10 lg:grid-cols-[1.4fr_repeat(5,1fr)]">
            <div class="col-span-2 lg:col-span-1">
                <a href="<?= e(lurl('/')) ?>" title="<?= e(t('site_name')) ?> — <?= e(t('today_rates')) ?>" class="mb-4 inline-flex items-center gap-2.5 text-slate-900 no-underline">
                    <span class="flex h-9 w-9 items-center justify-center rounded-[10px] bg-blue-600 text-[19px] font-extrabold text-white" aria-hidden="true">֏</span>
                    <span class="text-[17px] font-black tracking-tight">Poxarjeq<span class="text-blue-600">.am</span></span>
                </a>
                <p class="max-w-xs text-sm leading-relaxed text-black"><?= e(t('footer_text')) ?></p>
                <p class="mt-3 max-w-xs text-sm leading-relaxed text-black"><?= e(t('footer.data')) ?></p>
            </div>
            <?php foreach ($_fCols as $_fg => $_fItems): ?>
            <nav aria-label="<?= e(t('footer.col' . min(max((int)$_fg, 1), 5))) ?>">
                <h3 class="mb-4 text-[15px] font-extrabold text-slate-900"><?= e(t('footer.col' . min(max((int)$_fg, 1), 5))) ?></h3>
                <ul class="space-y-2.5 text-sm">
                    <?php foreach ($_fItems as $_fl): ?>
                    <li><?= ui_link(str_starts_with($_fl['url'], 'mailto:') || str_starts_with($_fl['url'], 'tel:') || str_starts_with($_fl['url'], 'http') ? $_fl['url'] : lurl($_fl['url']), nav_label($_fl, $lang), nav_title($_fl, $lang), 'text-slate-900 no-underline hover:text-blue-600 hover:underline',
                        array_filter(['target' => $_fl['new_tab'] ? '_blank' : null, 'rel' => trim(($_fl['new_tab'] ? 'noopener ' : '') . ($_fl['nofollow'] ? 'nofollow' : '')) ?: null])) ?></li>
                    <?php endforeach; ?>
                </ul>
            </nav>
            <?php endforeach; ?>
        </div>

        <div class="mt-12 grid gap-6 rounded-3xl border border-[#eaedf2] bg-white p-5 sm:p-7 lg:grid-cols-[1.2fr_1fr]">
            <div>
                <h3 class="text-[15px] font-extrabold text-slate-900"><?= e(t('footer.leads')) ?></h3>
                <p class="mt-2 text-sm leading-relaxed text-black"><?= e(t('footer.leads_desc')) ?></p>
                <p class="mt-3 flex flex-wrap gap-x-5 gap-y-2 text-sm font-semibold">
                    <?= ui_link(LEADS_URL, 'Leads.am ↗', 'Leads.am', 'text-blue-600 no-underline hover:underline', ['target' => '_blank', 'rel' => 'noopener']) ?>
                    <?= ui_link(PARENT_SITE, 'Poxarjeq.com ↗', t('footer.sister_title'), 'text-blue-600 no-underline hover:underline', ['target' => '_blank', 'rel' => 'noopener']) ?>
                </p>
            </div>
            <address class="space-y-1.5 text-sm not-italic text-black">
                <p class="font-bold text-slate-900"><?= e($_fAddr) ?></p>
                <p><?= ui_link($_fTel, CONTACT_PHONE, t('phone') . ': ' . CONTACT_PHONE, 'font-semibold text-blue-600 no-underline hover:underline') ?></p>
                <p><?= ui_link('mailto:' . CONTACT_EMAIL, CONTACT_EMAIL, t('email') . ': ' . CONTACT_EMAIL, 'font-semibold text-blue-600 no-underline hover:underline') ?></p>
            </address>
        </div>

        <div class="mt-8 border-t border-[#eaedf2] pt-6 text-xs leading-relaxed text-black">
            <p class="mb-2"><?= e(t('disclaimer_short')) ?></p>
            <p>© <?= date('Y') ?> <?= e(t('site_name')) ?> · Poxarjeq.am. <?= e(t('rights')) ?></p>
        </div>
    </div>
</footer>
<?php require __DIR__ . '/tg-widget.php'; ?>
<script type="application/ld+json">
<?= json_encode([
    '@context' => 'https://schema.org',
    '@graph' => [
        [
            '@type' => 'Organization',
            '@id' => SITE_URL . '/#organization',
            'name' => 'Poxarjeq.am',
            'url' => SITE_URL . '/',
            'logo' => SITE_URL . '/assets/og.png',
            'description' => t('footer_text'),
            'address' => ['@type' => 'PostalAddress', 'streetAddress' => CONTACT_ADDRESS['en'], 'addressLocality' => 'Yerevan', 'addressCountry' => 'AM'],
            'contactPoint' => ['@type' => 'ContactPoint', 'email' => CONTACT_EMAIL, 'telephone' => CONTACT_PHONE, 'contactType' => 'customer service', 'availableLanguage' => ['hy', 'ru', 'en']],
            'parentOrganization' => ['@type' => 'Organization', 'name' => 'Leads.am', 'url' => LEADS_URL],
            'sameAs' => [TELEGRAM_URL, PARENT_SITE],
        ],
        [
            '@type' => 'WebSite',
            '@id' => SITE_URL . '/#website',
            'url' => SITE_URL . '/',
            'name' => 'Poxarjeq.am',
            'publisher' => ['@id' => SITE_URL . '/#organization'],
            'inLanguage' => ['hy', 'ru', 'en'],
        ],
    ],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) ?>

</script>
</body>
</html>
