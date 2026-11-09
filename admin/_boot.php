<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config.db.php';
require_once __DIR__ . '/../includes/db_schema.php';

header('X-Robots-Tag: noindex, nofollow, noarchive');
header('Cache-Control: no-store');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: same-origin');

$_https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
ini_set('session.use_strict_mode', '1');
session_name('pxq_admin');
session_set_cookie_params(['lifetime' => 0, 'path' => '/admin', 'httponly' => true, 'samesite' => 'Lax', 'secure' => $_https]);
session_start();

const LOGO_DIR = __DIR__ . '/../uploads/banks';
const LOGO_URL = 'uploads/banks';

/* ---------- БД ---------- */
function adm_ready(): void
{
    static $done = false;
    if ($done) return;
    try {
        $pdo = db();
        $need = ['banks', 'admin_users', 'admin_login_attempts', 'site_texts', 'currency_pages', 'nav_links', 'branches', 'contact_messages'];
        $in = "'" . implode("','", $need) . "'";
        $have = (int)$pdo->query("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME IN ($in)")->fetchColumn();
        $cols = (int)$pdo->query("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'banks' AND COLUMN_NAME = 'seo_title_hy'")->fetchColumn();
        if ($have < count($need) || $cols < 1) pxq_migrate($pdo);   // новые таблицы и колонки создаются сами при обновлении сайта
    } catch (Throwable $e) {
        http_response_code(503);
        adm_plain_page('Нет подключения к базе данных', 'Проверьте параметры в config.db.php и что база запущена. Сайт при этом продолжает работать.');
    }
    $done = true;
}

function adm_plain_page(string $title, string $text): never
{
    ob_start();
    require __DIR__ . '/../includes/tw.php';
    $tw = ob_get_clean();
    echo '<!DOCTYPE html><html lang="ru"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex,nofollow"><title>' . e($title) . '</title>' . $tw
       . '</head>'
       . '<body class="min-h-screen grid place-items-center bg-slate-50 px-4 font-sans"><div class="max-w-md rounded-3xl border border-slate-100 bg-white p-8 shadow-xl">'
       . '<h1 class="text-xl font-black text-slate-900">' . e($title) . '</h1><p class="mt-3 text-black">' . e($text) . '</p></div></body></html>';
    exit;
}

/* ---------- Сессия, CSRF, уведомления ---------- */
function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}
function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}
function csrf_verify(): void
{
    $ok = isset($_POST['_csrf'], $_SESSION['csrf']) && hash_equals($_SESSION['csrf'], (string)$_POST['_csrf']);
    if (!$ok) {
        http_response_code(400);
        adm_plain_page('Сессия устарела', 'Вернитесь назад, обновите страницу и повторите действие.');
    }
}
function flash(string $type, string $msg): void
{
    $_SESSION['flash'][] = [$type, $msg];
}
function flash_take(): array
{
    $f = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $f;
}
function redirect(string $to): never
{
    header('Location: ' . $to);
    exit;
}

/* ---------- Авторизация ---------- */
function adm_user(): ?array
{
    if (empty($_SESSION['uid'])) return null;
    $st = db()->prepare('SELECT id, username FROM admin_users WHERE id = ?');
    $st->execute([(int)$_SESSION['uid']]);
    return $st->fetch() ?: null;
}
function adm_require(): array
{
    adm_ready();
    $u = adm_user();
    if (!$u) redirect('/admin/login.php');
    return $u;
}
function adm_ip(): string
{
    return substr((string)($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'), 0, 45);
}
function adm_login_blocked(): bool
{
    $st = db()->prepare('SELECT COUNT(*) FROM admin_login_attempts WHERE ip = ? AND created_at > (NOW() - INTERVAL 15 MINUTE)');
    $st->execute([adm_ip()]);
    return (int)$st->fetchColumn() >= 8;
}
function adm_login_fail(): void
{
    db()->prepare('INSERT INTO admin_login_attempts (ip) VALUES (?)')->execute([adm_ip()]);
    db()->exec('DELETE FROM admin_login_attempts WHERE created_at < (NOW() - INTERVAL 1 DAY)');
}
function adm_login_ok(int $uid): void
{
    session_regenerate_id(true);
    $_SESSION['uid'] = $uid;
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
    db()->prepare('UPDATE admin_users SET last_login = NOW() WHERE id = ?')->execute([$uid]);
    db()->prepare('DELETE FROM admin_login_attempts WHERE ip = ?')->execute([adm_ip()]);
}

/* ---------- Банки ---------- */
function adm_sync_api_banks(): void
{
    $stamp = CACHE_DIR . '/api_sync.stamp';
    if (is_file($stamp) && time() - filemtime($stamp) < 600) return;
    @touch($stamp);
    $list = get_banks_list();
    if (!$list || empty($list['banks'])) return;
    $have = array_flip(db()->query('SELECT code FROM banks')->fetchAll(PDO::FETCH_COLUMN));
    $ins = db()->prepare('INSERT INTO banks (code, name_hy, name_ru, name_en) VALUES (?,?,?,?)');
    foreach ($list['banks'] as $b) {
        $code = strtolower(preg_replace('/[^a-z0-9\-]/i', '', (string)($b['code'] ?? '')));
        if ($code === '' || isset($have[$code])) continue;
        $n = (string)($b['name'] ?? $code);
        $ins->execute([$code, $n, $n, $n]);
    }
}

function adm_bank_name(array $b): string
{
    foreach (['name_ru', 'name_en', 'name_hy'] as $k) if (($b[$k] ?? '') !== '') return $b[$k];
    return $b['code'];
}
function adm_logo_src(array $b): string
{
    if (!empty($b['logo_path']) && is_file(__DIR__ . '/../' . $b['logo_path'])) return '/' . ltrim($b['logo_path'], '/');
    return '';
}

/** Логотипы из API (кэш) — чтобы в админке видеть то, что сейчас на сайте, пока свой логотип не загружен */
function adm_api_logos(): array
{
    static $map = null;
    if ($map !== null) return $map;
    $map = [];
    $d = api_get('banks.php', ['cur' => 'USD']);
    foreach (($d['banks'] ?? []) as $b) {
        if (!empty($b['code']) && !empty($b['logo'])) $map[$b['code']] = $b['logo'];
    }
    return $map;
}
function adm_logo_display(array $b): string
{
    return adm_logo_src($b) ?: (adm_api_logos()[$b['code']] ?? '');
}

/** Принимает загруженный файл, нормализует (≤320px, WebP/PNG, прозрачность сохраняется), возвращает путь относительно корня сайта */
function adm_save_logo(string $code, array $file): string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        $map = [UPLOAD_ERR_INI_SIZE => 'Файл слишком большой для сервера.', UPLOAD_ERR_FORM_SIZE => 'Файл слишком большой.', UPLOAD_ERR_PARTIAL => 'Файл загрузился не полностью, повторите.'];
        throw new RuntimeException($map[$file['error']] ?? 'Не удалось загрузить файл.');
    }
    if ($file['size'] > 3 * 1024 * 1024) throw new RuntimeException('Файл больше 3 МБ. Сожмите изображение.');
    $raw = (string)file_get_contents($file['tmp_name']);
    $info = @getimagesizefromstring($raw);
    $mime = $info['mime'] ?? '';
    if (!in_array($mime, ['image/png', 'image/jpeg', 'image/webp'], true)) throw new RuntimeException('Нужен файл PNG, JPG или WebP.');
    [$w, $h] = $info;
    if ($w < 32 || $h < 32) throw new RuntimeException('Изображение слишком маленькое: минимум 32×32 пикселя.');
    if ($w > 6000 || $h > 6000) throw new RuntimeException('Изображение слишком большое: максимум 6000×6000 пикселей.');
    $src = @imagecreatefromstring($raw);
    if (!$src) throw new RuntimeException('Не удалось прочитать изображение.');

    $scale = min(1.0, 320 / max($w, $h));
    $nw = max(1, (int)round($w * $scale)); $nh = max(1, (int)round($h * $scale));
    $dst = imagecreatetruecolor($nw, $nh);
    imagealphablending($dst, false);
    imagesavealpha($dst, true);
    imagefill($dst, 0, 0, imagecolorallocatealpha($dst, 255, 255, 255, 127));
    imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);

    if (!is_dir(LOGO_DIR) && !@mkdir(LOGO_DIR, 0755, true)) throw new RuntimeException('Не удаётся создать папку uploads/banks.');
    if (!is_writable(LOGO_DIR)) throw new RuntimeException('Папка uploads/banks недоступна для записи (права 755 или 775).');
    $useWebp = function_exists('imagewebp');
    $name = $code . '-' . bin2hex(random_bytes(4)) . ($useWebp ? '.webp' : '.png');
    $ok = $useWebp ? imagewebp($dst, LOGO_DIR . '/' . $name, 90) : imagepng($dst, LOGO_DIR . '/' . $name, 6);
    if (!$ok) throw new RuntimeException('Не удалось сохранить логотип.');
    return LOGO_URL . '/' . $name;
}
function adm_delete_logo(?string $path): void
{
    if ($path && str_starts_with($path, LOGO_URL . '/') && !str_contains($path, '..')) {
        @unlink(__DIR__ . '/../' . $path);
    }
}
