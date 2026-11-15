<?php
require_once __DIR__ . '/includes/functions.php';

$bankAlias = ['arcaxbank' => 'arcakhbank', 'evokabank' => 'evocabank', 'aydibank' => 'aydi-bank', 'idbank' => 'aydi-bank', 'konversbank' => 'konvers-bank'];
$cityAlias = ['erevan' => 'yerevan-head', 'yerevan' => 'yerevan-head', 'glxamas' => 'yerevan-head', 'norqmarash' => 'yerevan-nork', 'nor-norq' => 'yerevan-nork', 'erebuni' => 'yerevan-erebuni', 'ejmiacin' => '', 'armavir-2' => 'armavir'];

$bank = strtolower((string)($_GET['b'] ?? ''));
$city = strtolower((string)($_GET['c'] ?? ''));
$bank = $bankAlias[$bank] ?? $bank;

if ($bank === '' || !get_bank_meta($bank)) pxq_redirect('/');

$slugs = array_column(get_bank_meta($bank)['branches'] ?? [], 'slug');
if ($city !== '') {
    $cands = [];
    if (isset($cityAlias[$city]) && $cityAlias[$city] !== '') $cands[] = $cityAlias[$city];
    array_push($cands, $city, 'yerevan-' . $city, $city . '-shirak', $city . '-lori');
    foreach ($cands as $cand) {
        if (in_array($cand, $slugs, true)) pxq_redirect(branch_page_url($bank, $cand));
    }
}
pxq_redirect(bank_page_url($bank));
