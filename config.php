<?php
declare(strict_types=1);

define('SITE_NAME', 'Poxarjeq.am');
define('SITE_URL', 'https://poxarjeq.am');
define('API_BASE', getenv('POXARJEQ_API_BASE') ?: 'https://poxarjeq.com/api');
define('CACHE_TTL', 900);
define('CACHE_DIR', getenv('POXARJEQ_CACHE_DIR') ?: __DIR__ . '/cache');
define('DEFAULT_LANG', 'hy');
define('SUPPORTED_LANGS', ['hy', 'ru', 'en']);

define('CONTACT_EMAIL', 'info@leads.am');
define('CONTACT_PHONE', '+374 44 140 813');
define('CONTACT_ADDRESS', ['hy' => 'ք. Երևան, Պուշկին 41', 'ru' => 'г. Ереван, ул. Пушкина 41', 'en' => '41 Pushkin St., Yerevan, Armenia']);
define('TELEGRAM_URL', 'https://t.me/poxarjeqcom');
define('PARENT_SITE', 'https://poxarjeq.com');
define('LEADS_URL', 'https://leads.am');

date_default_timezone_set('Asia/Yerevan');

if (!is_dir(CACHE_DIR)) {
    @mkdir(CACHE_DIR, 0755, true);
}

define('COLOR_PRIMARY', '#2563eb');
define('COLOR_PRIMARY_HOVER', '#1d4ed8');
define('COLOR_PRIMARY_LIGHT', '#eff6ff');
