<?php
/**
 * Tailwind + шрифт Inter для публичного сайта и админки.
 * Никаких CSS-файлов: вся вёрстка — классы Tailwind в разметке.
 * Базовые правила (цвет ссылок) задаются плагином Tailwind, а не CSS.
 */
?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&amp;family=Noto+Sans+Armenian:wght@400;600;700;800&amp;display=swap" rel="stylesheet">
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
                    // Ссылки в тексте всегда голубые во всех состояниях (правило проекта); утилитами цвет переопределяется
                    api.addBase({
                        'html': { scrollBehavior: 'smooth', '-webkit-text-size-adjust': '100%' },
                        'a, a:visited, a:hover, a:active': { color: '#2563eb' }
                    });
                }
            ]
        };
    </script>
