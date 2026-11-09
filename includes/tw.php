<?php
/**
 * Tailwind + шрифт Inter для публичного сайта и админки.
 * Никаких CSS-файлов: вся вёрстка — классы Tailwind в разметке.
 * Базовые правила (цвет ссылок) задаются плагином Tailwind, а не CSS.
 */
?>
    <link rel="preload" href="/fonts/inter-600.woff2" as="font" type="font/woff2" crossorigin>
    <link rel="preload" href="/fonts/noto-armenian-700.woff2" as="font" type="font/woff2" crossorigin>
    <script src="https://cdn.tailwindcss.com/3.4.17"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Inter', 'Noto Sans Armenian', 'Sylfaen', 'system-ui', '-apple-system', 'Segoe UI', 'Roboto', 'sans-serif'] },
                    colors: { brand: { DEFAULT: '#2563eb', hover: '#1d4ed8', light: '#eff6ff', dark: '#1e40af' } }
                }
            },
            plugins: [
                function (api) {
                    // Шрифты своего хостинга: Inter (латиница, кириллица) и Noto Sans Armenian (армянский — основной язык сайта)
                    var inter = function (w) { return { '@font-face': { fontFamily: 'Inter', fontStyle: 'normal', fontWeight: String(w), fontDisplay: 'swap', src: "url('/fonts/inter-" + w + ".woff2') format('woff2')" } }; };
                    var hy = function (range, w) { return { '@font-face': { fontFamily: 'Noto Sans Armenian', fontStyle: 'normal', fontWeight: range, fontDisplay: 'swap', src: "url('/fonts/noto-armenian-" + w + ".woff2') format('woff2')", unicodeRange: 'U+0531-0587, U+0589-058A, U+058D-058F, U+FB13-FB17' } }; };
                    api.addBase([inter(400), inter(500), inter(600), inter(700), inter(800), hy('400 500', 400), hy('600 800', 700)]);
                    api.addBase({
                        'html': { scrollBehavior: 'smooth', '-webkit-text-size-adjust': '100%' },
                        // Ссылки в тексте всегда голубые во всех состояниях (правило проекта); утилитами цвет переопределяется
                        'a, a:visited, a:hover, a:active': { color: '#2563eb' }
                    });
                }
            ]
        };
    </script>
