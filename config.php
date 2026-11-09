<?php
declare(strict_types=1);

define('SITE_NAME', 'Poxarjeq.am');
define('SITE_URL', 'https://poxarjeq.am'); // ← CHANGE TO YOUR DOMAIN
define('API_BASE', getenv('POXARJEQ_API_BASE') ?: 'https://poxarjeq.com/api');
define('CACHE_TTL', 900);
define('CACHE_DIR', getenv('POXARJEQ_CACHE_DIR') ?: __DIR__ . '/cache');
define('DEFAULT_LANG', 'hy');
define('SUPPORTED_LANGS', ['hy', 'ru', 'en']);

define('CONTACT_EMAIL', 'info@leads.am');
define('CONTACT_PHONE', '+374 44 140 813');

date_default_timezone_set('Asia/Yerevan');

if (!is_dir(CACHE_DIR)) {
    @mkdir(CACHE_DIR, 0755, true);
}

// Палитра как на poxarjeq.com (blue-600 / blue-700 / blue-50)
define('COLOR_PRIMARY', '#2563eb');
define('COLOR_PRIMARY_HOVER', '#1d4ed8');
define('COLOR_PRIMARY_LIGHT', '#eff6ff');
