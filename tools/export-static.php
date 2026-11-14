<?php
/**
 * Build the static HTML demo into /demo (for GitHub Pages and the showcase).
 *
 * Usage:  php tools/export-static.php
 *
 * Every PHP page is rendered with CC_STATIC = true, which switches links to
 * .html, turns off sessions and lets the JavaScript simulate the server parts
 * (slot checking, saving the booking, the contact form).
 *
 * @package ClearcoatAutoSpa
 */

if (PHP_SAPI !== 'cli') {
    exit('Run from the command line.');
}
define('CC_ROOT', dirname(__DIR__));
define('CC_STATIC', true);
define('CC_BASE_URL', 'https://efraimray.github.io/clearcoat-auto-spa/demo/');
require CC_ROOT . '/inc/bootstrap.php';

$out = CC_ROOT . '/demo';
$pages = array('index', 'about', 'services', 'book', 'confirmation', 'faq', 'contact', 'privacy', '404');

/** Count source lines per language (excludes demo/ and the publisher). */
function cc_lang_stats()
{
    $map = array('php' => 'PHP', 'js' => 'JavaScript', 'css' => 'CSS', 'json' => 'JSON');
    $lines = array();
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(CC_ROOT, FilesystemIterator::SKIP_DOTS));
    foreach ($it as $f) {
        $rel = str_replace('\\', '/', substr($f->getPathname(), strlen(CC_ROOT) + 1));
        if (preg_match('#^(demo|storage|docs|\.git|\.github)/#', $rel) || strpos($rel, 'publish-to-github') === 0) {
            continue;
        }
        $ext = strtolower($f->getExtension());
        if (!isset($map[$ext])) {
            continue;
        }
        $n = count(file($f->getPathname()));
        $lines[$map[$ext]] = (isset($lines[$map[$ext]]) ? $lines[$map[$ext]] : 0) + $n;
    }
    arsort($lines);
    $total = array_sum($lines);
    $stats = array();
    foreach ($lines as $lang => $n) {
        $stats[$lang] = array('lines' => $n, 'pct' => round($n * 100 / $total));
    }
    return $stats;
}

/** Render one page file to a string. */
function cc_render($file, $vars = array())
{
    extract($vars);
    ob_start();
    include $file;
    return ob_get_clean();
}

// Fresh output folder.
if (is_dir($out)) {
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($out, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
    foreach ($it as $f) {
        $f->isDir() ? rmdir($f->getPathname()) : unlink($f->getPathname());
    }
}
mkdir($out . '/assets', 0775, true);

foreach ($pages as $p) {
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_GET = array();
    $html = cc_render(CC_ROOT . '/' . $p . '.php');
    file_put_contents($out . '/' . $p . '.html', $html);
    echo "  wrote demo/$p.html (" . strlen($html) . " bytes)\n";
}
$html = cc_render(CC_ROOT . '/tools/about-project.php', array('cc_lang_stats' => cc_lang_stats()));
file_put_contents($out . '/about-project.html', $html);
echo "  wrote demo/about-project.html\n";

// Copy assets.
$src = CC_ROOT . '/assets';
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($src, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::SELF_FIRST);
foreach ($it as $f) {
    $dest = $out . '/assets/' . substr($f->getPathname(), strlen($src) + 1);
    $f->isDir() ? (is_dir($dest) || mkdir($dest, 0775, true)) : copy($f->getPathname(), $dest);
}

// Sitemap and robots for the demo URL.
$xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n";
foreach (array('index', 'services', 'book', 'about', 'faq', 'contact', 'privacy', 'about-project') as $p) {
    $xml .= '  <url><loc>' . cc_abs_url($p) . "</loc></url>\n";
}
file_put_contents($out . '/sitemap.xml', $xml . "</urlset>\n");
file_put_contents($out . '/robots.txt', "User-agent: *\nAllow: /\n\nSitemap: " . CC_BASE_URL . "sitemap.xml\n");
echo "Static demo ready in demo/ (" . (count($pages) + 1) . " pages).\n";
