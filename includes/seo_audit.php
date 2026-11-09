<?php
declare(strict_types=1);

/**
 * Проверка HTML страницы по SEO-правилам проекта:
 * у каждой ссылки есть title, у каждой картинки — alt, один H1, есть title/description/canonical/hreflang/lang.
 * @return string[] список проблем (пусто — всё в порядке)
 */
function seo_audit_html(string $html): array
{
    $issues = [];
    $doc = new DOMDocument();
    libxml_use_internal_errors(true);
    $doc->loadHTML('<?xml encoding="utf-8" ?>' . $html);
    libxml_clear_errors();
    $x = new DOMXPath($doc);

    foreach ($x->query('//a[@href]') as $a) {
        /** @var DOMElement $a */
        if (trim($a->getAttribute('title')) === '' && !str_starts_with($a->getAttribute('href'), '#')) {
            $issues[] = 'ссылка без title: ' . $a->getAttribute('href') . ' («' . mb_substr(trim(preg_replace('/\s+/u', ' ', $a->textContent)), 0, 40) . '»)';
        }
        if (trim($a->getAttribute('title')) === '' && str_starts_with($a->getAttribute('href'), '#')) {
            $issues[] = 'якорная ссылка без title: ' . $a->getAttribute('href');
        }
    }
    foreach ($x->query('//img') as $img) {
        /** @var DOMElement $img */
        if (trim($img->getAttribute('alt')) === '') $issues[] = 'картинка без alt: ' . $img->getAttribute('src');
        if ($img->getAttribute('width') === '' || $img->getAttribute('height') === '') $issues[] = 'картинка без width/height: ' . $img->getAttribute('src');
    }
    $h1 = $x->query('//h1')->length;
    if ($h1 !== 1) $issues[] = "H1 на странице: $h1 (нужен ровно один)";
    $title = trim((string)$x->evaluate('string(//title)'));
    if ($title === '') $issues[] = 'пустой <title>';
    elseif (mb_strlen($title) > 70) $issues[] = 'title длиннее 70 символов (' . mb_strlen($title) . ')';
    $desc = trim((string)$x->evaluate('string(//meta[@name="description"]/@content)'));
    if ($desc === '') $issues[] = 'нет meta description';
    elseif (mb_strlen($desc) > 200) $issues[] = 'description длиннее 200 символов (' . mb_strlen($desc) . ')';
    elseif (mb_strlen($desc) < 40) $issues[] = 'description короче 40 символов (' . mb_strlen($desc) . ')';
    if ($x->query('//link[@rel="canonical"]')->length !== 1) $issues[] = 'нет canonical';
    if ($x->query('//link[@rel="alternate"][@hreflang]')->length < 3) $issues[] = 'не хватает hreflang';
    if (trim((string)$x->evaluate('string(/html/@lang)')) === '') $issues[] = 'нет атрибута lang у <html>';
    foreach ($x->query('//script[@type="application/ld+json"]') as $s) {
        if (json_decode($s->textContent, true) === null) $issues[] = 'ошибка в JSON-LD';
    }
    foreach ($x->query('//input[not(@type="hidden") and not(@type="submit")]|//select|//textarea') as $in) {
        /** @var DOMElement $in */
        $id = $in->getAttribute('id');
        $labelled = $in->getAttribute('aria-label') !== '' || ($id !== '' && $x->query('//label[@for="' . $id . '"]')->length > 0) || $x->query('ancestor::label', $in)->length > 0;
        if (!$labelled && $in->getAttribute('type') !== 'hidden' && $in->getAttribute('tabindex') !== '-1') $issues[] = 'поле формы без подписи: ' . ($id ?: $in->getAttribute('name'));
    }
    return array_values(array_unique($issues));
}
