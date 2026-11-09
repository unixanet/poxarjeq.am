# BestRate.am — SEO Exchange Rates (HY / RU / EN)

Modern Tailwind CSS site based on **https://poxarjeq.com/api**

## Features
- 3 languages: Armenian, Russian, English (switcher keeps current page)
- Tailwind CSS, white bg, black text, Microsoft blue (#0078D4)
- Burger menu, fully responsive
- All 11 currencies from API
- Horizontal scroll tables
- Bank pages: `/ameriabank.php`, `/acba-bank.php`, … with contacts & texts
- Logo placeholders (API logos + fallback)
- Full SEO 2026: canonical, hreflang, OG, Twitter, JSON-LD (Organization, WebSite, FAQPage, ItemList, BankOrCreditUnion)
- File cache 15 min

## Setup
1. Upload folder to hosting (PHP 8+)
2. Edit `config.php` → `SITE_URL`, `CONTACT_EMAIL`
3. Ensure `cache/` is writable
4. Open site

## Bank pages
Click bank name → `/ameriabank.php?lang=hy` (and other codes)

## Colors
- Background: white
- Text: black / gray-900
- Links & accents: #0078D4 (Microsoft blue)

## Редизайн 2026 (стиль poxarjeq.com)
- Стили: **локальная сборка Tailwind v4** (без cdn.tailwindcss.com). Исходник `css/input.css`, результат `css/output.css` (закоммичен, заливается по FTP).
  Пересборка после правок вёрстки: `npm install && npm run build:css`.
- Шрифт Inter (латиница + кириллица) лежит в `fonts/`, флаги валют в `assets/flags/{CODE}.png`.
- Главная: курсы по **всем** валютам из API (`currencies.php` + `banks.php?cur=…`, параллельная загрузка, кэш 15 мин). У каждой колонки таблицы свой переключатель валюты, на телефоне — чипы валют и карточки банков, калькулятор работает для любой пары.
- Подключение к MySQL — `config.db.php` (функция `db()`).

## Админка `/admin/`
- Первый вход: открыть `/admin/`, ввести ключ первого запуска (`ADMIN_SETUP_KEY` в `config.db.php`) и создать логин/пароль. Таблицы в MySQL создаются автоматически, банки заполняются из `includes/bank_data.php` и API.
- Раздел «Банки»: загрузка логотипа (PNG/JPG/WebP → сайт сам уменьшает до 320 px и сохраняет в `uploads/banks/`), названия на HY/RU/EN, контакты, тексты, тумблеры **Партнёр** (закрепляет банк в начале таблицы и списка, порядок кнопками ↑ ↓) и **Показывать на сайте**.
- Папка `uploads/banks/` должна быть доступна для записи (755/775).
- Если база недоступна, публичный сайт продолжает работать на данных из `bank_data.php` и API.
- Стили админки и сайта — только Tailwind-утилиты в разметке; собственных CSS-классов нет.
