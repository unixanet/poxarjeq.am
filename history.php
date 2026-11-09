<?php
/**
 * История курса для графика (JSON). Кэширует ответ API на 10 минут, чтобы страницы не нагружали основной сервер.
 * GET /history.php?cur=USD&days=30
 */
require_once __DIR__ . '/includes/functions.php';
header('Content-Type: application/json; charset=utf-8');
header('X-Robots-Tag: noindex, nofollow');
header('Cache-Control: public, max-age=300');

$cur = strtoupper(trim((string)($_GET['cur'] ?? 'USD')));
if (!preg_match('/^[A-Z]{3}$/', $cur)) { http_response_code(400); echo '{"points":[]}'; exit; }
$days = (int)($_GET['days'] ?? 30);
$allowed = [7, 30, 90, 180, 365, 730];
foreach ($allowed as $a) { if ($days <= $a) { $days = $a; break; } }
if ($days > 730) $days = 730;

$d = api_get('history.php', ['cur' => $cur, 'days' => $days], 600);
$points = [];
foreach (($d['points'] ?? []) as $p) {
    if (!isset($p['date'])) continue;
    $points[] = ['date' => (string)$p['date'], 'buy' => (float)($p['buy'] ?? 0), 'sell' => (float)($p['sell'] ?? 0)];
}
echo json_encode(['cur' => $cur, 'days' => $days, 'points' => $points], JSON_UNESCAPED_UNICODE);
