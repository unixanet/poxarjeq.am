<?php
declare(strict_types=1);

/** Мелкие помощники форм админки (Tailwind) */
const ADM_INP = 'mt-1 block min-h-[44px] w-full rounded-xl border border-slate-300 bg-white px-3.5 text-[15px] text-black outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-200';
const ADM_TA  = 'mt-1 block w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-[15px] leading-relaxed text-black outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-200';
const ADM_LANGS = ['hy' => 'HY · Հայերեն (основной)', 'ru' => 'RU · Русский', 'en' => 'EN · English'];
const ADM_CARD = 'rounded-3xl border border-slate-100 bg-white p-5 shadow-[0_10px_40px_rgba(15,23,42,.06)] sm:p-6';
const ADM_BTN = 'inline-flex min-h-[46px] items-center justify-center rounded-[14px] bg-blue-600 px-6 text-[15px] font-bold text-white no-underline shadow-[0_6px_16px_rgba(37,99,235,.28)] hover:bg-blue-700';
const ADM_BTN2 = 'inline-flex min-h-[46px] items-center justify-center rounded-[14px] border border-slate-200 bg-white px-5 text-[15px] font-bold text-black no-underline hover:border-blue-300';

function adm_field(string $id, string $name, string $label, string $value, bool $long = false, int $rows = 3, string $extra = '', string $placeholder = ''): string
{
    $lab = '<label class="block text-sm font-bold text-slate-900" for="' . e($id) . '">' . $label;
    if ($long) {
        return $lab . '<textarea id="' . e($id) . '" name="' . e($name) . '" rows="' . $rows . '" class="' . ADM_TA . '" placeholder="' . e($placeholder) . '" ' . $extra . '>' . e($value) . '</textarea></label>';
    }
    return $lab . '<input id="' . e($id) . '" type="text" name="' . e($name) . '" value="' . e($value) . '" class="' . ADM_INP . '" placeholder="' . e($placeholder) . '" ' . $extra . '></label>';
}

/** Сброс кэша сайта после любых правок контента */
function adm_content_changed(): void
{
    bank_store_clear_cache();
    pxq_cache_clear();
    @unlink(__DIR__ . '/../llms.txt.stale');
}
