<?php
declare(strict_types=1);

function city_slug_from(string $en): string
{
    return trim(preg_replace('/[^a-z0-9]+/', '-', strtolower($en)), '-');
}

function city_loc(string $name, string $lang): string
{
    if ($lang === 'hy') return $name . 'ում';
    if ($lang === 'en') return $name;
    return preg_match('/[иаяоеыуюэ]$/ui', $name) ? $name : $name . 'е';
}

function cities_index(): array
{
    static $idx = null;
    if ($idx !== null) return $idx;
    $idx = [];
    foreach (array_filter(sm_bank_codes_safe(), fn($c) => bank_is_visible($c)) as $code) {
        $meta = get_bank_meta($code);
        foreach (($meta['branches'] ?? []) as $br) {
            $en = (string)($br['city']['en'] ?? '');
            $slug = city_slug_from($en !== '' ? $en : (string)($br['city']['hy'] ?? ''));
            if ($slug === '') continue;
            $idx[$slug]['name'] = ['hy' => $br['city']['hy'] ?? $en, 'ru' => $br['city']['ru'] ?? $en, 'en' => $en ?: ($br['city']['hy'] ?? '')];
            $idx[$slug]['items'][] = ['bank' => $code, 'branch' => $br];
        }
    }
    uasort($idx, fn($a, $b) => count($b['items']) <=> count($a['items']));
    return $idx;
}

function city_page_url(string $slug): string
{
    return '/cities/' . $slug;
}
