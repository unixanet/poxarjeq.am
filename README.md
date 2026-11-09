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
