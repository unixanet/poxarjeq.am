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
            // классы uPlot создаются скриптом уже после загрузки страницы: без safelist Tailwind не включит их правила
            safelist: ['uplot', 'u-hz', 'u-vt', 'u-wrap', 'u-under', 'u-over', 'u-axis', 'u-cursor-x', 'u-cursor-y', 'u-cursor-pt', 'u-select', 'u-off', 'u-legend', 'u-title'],
            theme: {
                extend: {
                    fontFamily: { sans: ['Inter', 'Noto Sans Armenian', 'Sylfaen', 'system-ui', '-apple-system', 'Segoe UI', 'Roboto', 'sans-serif'] },
                    colors: { brand: { DEFAULT: '#2563eb', hover: '#1d4ed8', light: '#eff6ff', dark: '#1e40af' } }
                }
            },
            plugins: [
                function (api) {
                    // Правила графика uPlot (без отдельного CSS-файла): нужны только страницам с графиком
                    api.addBase({".uplot": {"box-sizing": "border-box", "font-family": "system-ui, -apple-system, \"Segoe UI\", Roboto, \"Helvetica Neue\", Arial, \"Noto Sans\", sans-serif, \"Apple Color Emoji\", \"Segoe UI Emoji\", \"Segoe UI Symbol\", \"Noto Color Emoji\"", "line-height": "1.5", "width": "min-content"}, ".uplot *": {"box-sizing": "border-box"}, ".uplot *::before": {"box-sizing": "border-box"}, ".uplot *::after": {"box-sizing": "border-box"}, ".u-title": {"text-align": "center", "font-size": "18px", "font-weight": "bold"}, ".u-wrap": {"position": "relative", "user-select": "none"}, ".u-over": {"position": "absolute"}, ".u-under": {"position": "absolute", "overflow": "hidden"}, ".uplot canvas": {"display": "block", "position": "relative", "width": "100%", "height": "100%"}, ".u-axis": {"position": "absolute"}, ".u-legend": {"font-size": "14px", "margin": "auto", "text-align": "center"}, ".u-inline": {"display": "block"}, ".u-inline *": {"display": "inline-block"}, ".u-inline tr": {"margin-right": "16px"}, ".u-legend th": {"font-weight": "600"}, ".u-legend th > *": {"vertical-align": "middle", "display": "inline-block"}, ".u-legend .u-marker": {"width": "1em", "height": "1em", "margin-right": "4px", "background-clip": "padding-box !important"}, ".u-inline.u-live th::after": {"content": "\":\"", "vertical-align": "middle"}, ".u-inline:not(.u-live) .u-value": {"display": "none"}, ".u-series > *": {"padding": "4px"}, ".u-series th": {"cursor": "pointer"}, ".u-legend .u-off > *": {"opacity": "0.3"}, ".u-select": {"background": "rgba(0,0,0,0.07)", "position": "absolute", "pointer-events": "none"}, ".u-cursor-x": {"position": "absolute", "left": "0", "top": "0", "pointer-events": "none", "will-change": "transform"}, ".u-cursor-y": {"position": "absolute", "left": "0", "top": "0", "pointer-events": "none", "will-change": "transform"}, ".u-hz .u-cursor-x": {"height": "100%", "border-right": "1px dashed #607D8B"}, ".u-vt .u-cursor-y": {"height": "100%", "border-right": "1px dashed #607D8B"}, ".u-hz .u-cursor-y": {"width": "100%", "border-bottom": "1px dashed #607D8B"}, ".u-vt .u-cursor-x": {"width": "100%", "border-bottom": "1px dashed #607D8B"}, ".u-cursor-pt": {"position": "absolute", "top": "0", "left": "0", "border-radius": "50%", "border": "0 solid", "pointer-events": "none", "will-change": "transform", "background-clip": "padding-box !important"}, ".u-axis.u-off": {"display": "none"}, ".u-select.u-off": {"display": "none"}, ".u-cursor-x.u-off": {"display": "none"}, ".u-cursor-y.u-off": {"display": "none"}, ".u-cursor-pt.u-off": {"display": "none"}});
                },
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
