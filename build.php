<?php
declare(strict_types=1);

$baseDir = __DIR__;
$outDir  = $baseDir . '/dist';

foreach ($argv as $arg) {
    if (str_starts_with($arg, '--out=')) {
        $outDir = substr($arg, 6);
        if ($outDir[0] !== '/') $outDir = $baseDir . '/' . $outDir;
    }
}

$pages = require $baseDir . '/chapters.php';

if (is_dir($outDir)) rrm($outDir);
mkdir($outDir, 0777, true);

$chapterSlugs = [];
foreach ($pages as $slug => $m) {
    if (($m['layout'] ?? '') === 'chapter') $chapterSlugs[] = $slug;
}

foreach ($pages as $slug => $m) {
    $content = file_get_contents("{$baseDir}/content/{$slug}.html");
    if ($content === false) {
        fwrite(STDERR, "Missing content/{$slug}.html\n");
        exit(1);
    }
    $html = render_page($slug, $m, $content, $pages, $chapterSlugs);
    file_put_contents("{$outDir}/{$slug}.html", $html);
}

foreach (['style.css', 'favicon.svg'] as $asset) {
    $src = "{$baseDir}/{$asset}";
    if (file_exists($src)) copy($src, "{$outDir}/{$asset}");
}

if (is_dir("{$baseDir}/photos")) rcopy("{$baseDir}/photos", "{$outDir}/photos");

fwrite(STDERR, "Built " . count($pages) . " pages -> {$outDir}\n");


function render_page(string $slug, array $m, string $content, array $pages, array $chapterSlugs): string
{
    $head = render_head($m);
    $foot = render_footer();

    if (($m['layout'] ?? '') === 'chapter') {
        $topNav = '<nav class="top-nav"><a href="index.html">&larr; Zpátky na přehled</a></nav>';
        $hero   = render_chapter_hero($m);
        $dayNav = render_day_nav($slug, $pages, $chapterSlugs);
        $main   = "<main>\n" . $content . "\n" . $dayNav . "\n</main>";
        return $head . "\n\n" . $topNav . "\n\n" . $hero . "\n\n" . $main . "\n\n" . $foot;
    }

    return $head . "\n\n" . $content . "\n" . $foot;
}

function render_head(array $m): string
{
    $title          = $m['title'];
    $description    = $m['description'];
    $ogTitle        = $m['og_title'];
    $ogDescription  = $m['og_description'];
    $ogUrl          = $m['og_url'];
    $ogType         = $m['og_type'];

    return <<<HTML
<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$title}</title>
    <meta name="description" content="{$description}">
    <meta property="og:title" content="{$ogTitle}">
    <meta property="og:description" content="{$ogDescription}">
    <meta property="og:url" content="{$ogUrl}">
    <meta property="og:type" content="{$ogType}">
    <meta name="twitter:card" content="summary">
    <link rel="stylesheet" href="style.css">
</head>
<body>
HTML;
}

function render_chapter_hero(array $m): string
{
    $h1       = $m['hero_h1'];
    $subtitle = $m['hero_subtitle'];
    $dates    = $m['hero_dates'];

    return <<<HTML
<header class="hero hero-day">
    <div class="hero-overlay">
        <h1>{$h1}</h1>
        <p class="subtitle">{$subtitle}</p>
        <p class="dates">{$dates}</p>
    </div>
</header>
HTML;
}

function render_day_nav(string $slug, array $pages, array $chapterSlugs): string
{
    $i = array_search($slug, $chapterSlugs, true);
    $prevSlug = $i > 0 ? $chapterSlugs[$i - 1] : null;
    $nextSlug = $i < count($chapterSlugs) - 1 ? $chapterSlugs[$i + 1] : null;

    $prev = $prevSlug !== null
        ? '<a href="' . $prevSlug . '.html">&larr; ' . $pages[$prevSlug]['chapter_label'] . '</a>'
        : '<span class="disabled">&larr; Předchozí</span>';

    $next = $nextSlug !== null
        ? '<a href="' . $nextSlug . '.html">' . $pages[$nextSlug]['chapter_label'] . ' &rarr;</a>'
        : '<span class="disabled">Další &rarr;</span>';

    return "    <nav class=\"day-nav\">\n        {$prev}\n        {$next}\n    </nav>";
}

function render_footer(): string
{
    return <<<HTML
<footer>
    <p class="footer-main">Vaklafův deník</p>
    <p class="footer-sub">Vaklaf žije, Holly zapisuje.</p>
</footer>
</body>
</html>

HTML;
}

function rcopy(string $src, string $dst): void
{
    if (!is_dir($src)) return;
    if (!is_dir($dst)) mkdir($dst, 0777, true);
    foreach (scandir($src) as $f) {
        if ($f === '.' || $f === '..') continue;
        $s = "{$src}/{$f}"; $d = "{$dst}/{$f}";
        if (is_dir($s)) rcopy($s, $d); else copy($s, $d);
    }
}

function rrm(string $path): void
{
    if (!file_exists($path)) return;
    if (is_file($path) || is_link($path)) { unlink($path); return; }
    foreach (scandir($path) as $f) {
        if ($f === '.' || $f === '..') continue;
        rrm("{$path}/{$f}");
    }
    rmdir($path);
}
