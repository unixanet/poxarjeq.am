<?php
declare(strict_types=1);

function news_fetch(int $limit, int $page = 1, string $category = ''): ?array
{
    $q = ['limit' => $limit, 'page' => $page, 'lang' => 'hy'];
    if ($category !== '') $q['category'] = $category;
    return api_v2('news.php', $q, 600);
}

function news_date(?string $iso): string
{
    $ts = $iso ? strtotime($iso) : false;
    return $ts ? date('d.m.Y', $ts) : '';
}

function news_card(array $n, bool $big = false): string
{
    $title = trim((string)($n['title'] ?? ''));
    $url = (string)($n['url'] ?? '');
    if ($title === '' || $url === '') return '';
    $img = (string)($n['image'] ?? '');
    $cat = trim((string)($n['category']['name'] ?? ''));
    $linkTitle = tv('news.link_title', ['title' => $title]);
    $h = '<article class="flex flex-col overflow-hidden rounded-3xl border border-slate-100 bg-white shadow-[0_10px_40px_rgba(15,23,42,.06)] transition hover:-translate-y-0.5 hover:shadow-[0_14px_44px_rgba(37,99,235,.14)]">';
    $h .= '<a href="' . e($url) . '" title="' . e($linkTitle) . '" target="_blank" rel="noopener" class="block overflow-hidden bg-slate-100 text-slate-900 no-underline">';
    $h .= $img !== '' ? ui_img($img, $title, 640, 360, 'aspect-[16/9] w-full object-cover', ['loading' => 'lazy', 'lang' => 'hy']) : '<span class="block aspect-[16/9] w-full bg-blue-50"></span>';
    $h .= '</a><div class="flex flex-1 flex-col p-5">';
    $h .= '<p class="flex flex-wrap items-center gap-x-3 gap-y-1 text-xs font-bold">' . ($cat !== '' ? '<span class="rounded-full bg-blue-50 px-2.5 py-1 text-blue-600" lang="hy">' . e($cat) . '</span>' : '') . '<time datetime="' . e(substr((string)($n['published_at'] ?? ''), 0, 10)) . '">' . e(news_date($n['published_at'] ?? null)) . '</time></p>';
    $h .= '<h' . ($big ? '2' : '3') . ' class="mt-3 text-[17px] font-black leading-snug text-slate-900" lang="hy"><a href="' . e($url) . '" title="' . e($linkTitle) . '" target="_blank" rel="noopener" class="text-slate-900 no-underline hover:text-blue-600">' . e($title) . '</a></h' . ($big ? '2' : '3') . '>';
    $ex = trim((string)($n['excerpt'] ?? ''));
    if ($ex !== '') $h .= '<p class="mt-2 line-clamp-3 text-[14px] leading-relaxed" lang="hy">' . e($ex) . '</p>';
    $h .= '<p class="mt-auto pt-4"><a href="' . e($url) . '" title="' . e($linkTitle) . '" target="_blank" rel="noopener" class="text-sm font-bold text-blue-600 no-underline hover:underline">' . e(t('news.read')) . ' →</a></p>';
    return $h . '</div></article>';
}
