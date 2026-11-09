<?php
declare(strict_types=1);
require __DIR__ . '/_boot.php';
adm_ready();

if (adm_user()) redirect('/admin/');

$users = (int)db()->query('SELECT COUNT(*) FROM admin_users')->fetchColumn();
$setup = $users === 0;           // первый запуск: создаём администратора
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $username = trim((string)($_POST['username'] ?? ''));
    $password = (string)($_POST['password'] ?? '');

    if ($setup) {
        $key = (string)($_POST['setup_key'] ?? '');
        if (!hash_equals(ADMIN_SETUP_KEY, $key)) {
            $error = 'Неверный ключ первого запуска (он указан в config.db.php).';
        } elseif (!preg_match('/^[A-Za-z0-9_.\-]{3,32}$/', $username)) {
            $error = 'Логин: 3–32 символа, латиница, цифры и _ . -';
        } elseif (mb_strlen($password) < 10) {
            $error = 'Пароль должен быть не короче 10 символов.';
        } elseif ($password !== (string)($_POST['password2'] ?? '')) {
            $error = 'Пароли не совпадают.';
        } else {
            db()->prepare('INSERT INTO admin_users (username, password_hash) VALUES (?, ?)')
                ->execute([$username, password_hash($password, PASSWORD_DEFAULT)]);
            adm_login_ok((int)db()->lastInsertId());
            flash('ok', 'Администратор создан. Добро пожаловать!');
            redirect('/admin/');
        }
    } elseif (adm_login_blocked()) {
        $error = 'Слишком много попыток. Подождите 15 минут.';
    } else {
        $st = db()->prepare('SELECT id, password_hash FROM admin_users WHERE username = ?');
        $st->execute([$username]);
        $row = $st->fetch();
        // password_verify выполняем всегда, чтобы время ответа не выдавало существование логина
        $hash = $row['password_hash'] ?? '$2y$10$usesomesillystringforsaltuseless0000000000000000000000';
        if ($row && password_verify($password, $hash)) {
            if (password_needs_rehash($hash, PASSWORD_DEFAULT)) {
                db()->prepare('UPDATE admin_users SET password_hash = ? WHERE id = ?')->execute([password_hash($password, PASSWORD_DEFAULT), $row['id']]);
            }
            adm_login_ok((int)$row['id']);
            redirect('/admin/');
        }
        adm_login_fail();
        $error = 'Неверный логин или пароль.';
    }
}

$inp = 'mt-1.5 block min-h-[48px] w-full rounded-xl border border-slate-300 bg-white px-4 text-base text-black outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-200';
?><!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>Вход — админка</title>
<link rel="icon" href="/favicon.ico">
<?php require __DIR__ . '/../includes/tw.php'; ?>
</head>
<body class="font-sans antialiased text-black min-h-screen bg-gradient-to-b from-blue-50 to-white px-4 py-8 sm:grid sm:place-items-center">
<main class="mx-auto w-full max-w-md">
    <div class="mb-6 flex items-center justify-center gap-3">
        <span class="grid h-11 w-11 place-items-center rounded-xl bg-blue-600 text-2xl font-extrabold text-white shadow-[0_6px_16px_rgba(37,99,235,.3)]">֏</span>
        <span class="text-xl font-black tracking-tight text-slate-900">Poxarjeq.am</span>
    </div>
    <form method="post" class="rounded-[28px] border border-slate-100 bg-white p-6 shadow-[0_24px_60px_-12px_rgba(15,23,42,.18)] sm:p-8" autocomplete="on">
        <?= csrf_field() ?>
        <h1 class="text-2xl font-black tracking-tight text-slate-900"><?= $setup ? 'Первый запуск' : 'Вход в админку' ?></h1>
        <p class="mt-1 text-sm text-black"><?= $setup ? 'Создайте первого администратора.' : 'Управление банками, логотипами и партнёрами.' ?></p>
        <?php if ($error): ?><div role="alert" class="mt-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-900"><?= e($error) ?></div><?php endif; ?>

        <?php if ($setup): ?>
        <label class="mt-5 block text-sm font-bold" for="setup_key">Ключ первого запуска
            <input id="setup_key" name="setup_key" type="password" required autocomplete="off" class="<?= $inp ?>"></label>
        <?php endif; ?>
        <label class="mt-5 block text-sm font-bold" for="username">Логин
            <input id="username" name="username" type="text" required autocomplete="username" autocapitalize="none" value="<?= e($_POST['username'] ?? '') ?>" class="<?= $inp ?>"></label>
        <label class="mt-4 block text-sm font-bold" for="password">Пароль
            <input id="password" name="password" type="password" required autocomplete="<?= $setup ? 'new-password' : 'current-password' ?>" class="<?= $inp ?>"></label>
        <?php if ($setup): ?>
        <label class="mt-4 block text-sm font-bold" for="password2">Повторите пароль
            <input id="password2" name="password2" type="password" required autocomplete="new-password" class="<?= $inp ?>"></label>
        <?php endif; ?>
        <button type="submit" class="mt-6 min-h-[50px] w-full rounded-[14px] bg-blue-600 text-base font-bold shadow-[0_6px_16px_rgba(37,99,235,.28)] hover:bg-blue-700 text-white"><?= $setup ? 'Создать и войти' : 'Войти' ?></button>
    </form>
</main>
</body>
</html>
