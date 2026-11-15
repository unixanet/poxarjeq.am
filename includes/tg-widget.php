<?php
if (t('tg.show') !== '1' || str_starts_with($pageRobots ?? '', 'noindex')) return;
$_twUrl = t('tg.url');
?>
<aside id="tg-pop" class="pointer-events-none fixed inset-x-3 bottom-3 z-40 translate-y-6 opacity-0 transition duration-500 sm:inset-x-auto sm:bottom-5 sm:right-5 sm:w-[340px]" aria-label="<?= e(t('tg.title')) ?>" hidden>
    <div class="pointer-events-auto relative rounded-3xl border border-blue-100 bg-white p-4 pr-11 shadow-[0_20px_50px_rgba(15,23,42,.22)]">
        <button type="button" id="tg-close" class="absolute right-2.5 top-2.5 grid h-8 w-8 place-items-center rounded-full text-slate-900 hover:bg-slate-100" aria-label="<?= e(t('tg.close')) ?>" title="<?= e(t('tg.close')) ?>">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.4" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
        <div class="flex items-start gap-3">
            <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-blue-600 text-white" aria-hidden="true"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor"><path d="M21.9 4.6a1.2 1.2 0 0 0-1.3-.2L2.8 11.3a1.2 1.2 0 0 0 .1 2.2l4.4 1.6 1.7 5.3a1 1 0 0 0 1.7.4l2.5-2.5 4.5 3.3a1.2 1.2 0 0 0 1.9-.7l3-15.1a1.2 1.2 0 0 0-.7-1.2zM9.6 14.4l8.5-5.3-6.7 6.4-.3 2.9z"/></svg></span>
            <div>
                <p class="text-[15px] font-black leading-snug text-slate-900"><?= e(t('tg.title')) ?></p>
                <p class="mt-1 text-[13px] leading-snug text-black"><?= e(t('tg.text')) ?></p>
            </div>
        </div>
        <?= ui_link($_twUrl, e(t('tg.btn')) . ' <span aria-hidden="true">→</span>', t('tg.title_attr'), 'mt-3 flex min-h-[44px] items-center justify-center gap-2 rounded-xl bg-blue-600 px-4 text-sm font-bold text-white no-underline hover:bg-blue-700', ['target' => '_blank', 'rel' => 'noopener'], true) ?>
    </div>
</aside>
<script>
(function () {
    var box = document.getElementById('tg-pop'), btn = document.getElementById('tg-close'), shown = false, closed = false;
    if (!box) return;
    function show() {
        if (shown || closed) return;
        shown = true;
        box.hidden = false;
        requestAnimationFrame(function () { box.classList.remove('translate-y-6', 'opacity-0'); });
    }
    btn.addEventListener('click', function () {
        closed = true;
        box.classList.add('translate-y-6', 'opacity-0');
        setTimeout(function () { box.hidden = true; }, 400);
    });
    setTimeout(show, 15000);
    window.addEventListener('scroll', function () {
        var h = document.documentElement;
        if ((h.scrollTop + window.innerHeight) / h.scrollHeight > 0.8) show();
    }, { passive: true });
})();
</script>
