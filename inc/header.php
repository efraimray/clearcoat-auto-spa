<?php
/**
 * Clearcoat Auto Spa - shared <head>, skip link and site header.
 *
 * Expects $page = array('slug' => 'about', 'title' => '...', 'description' => '...').
 *
 * @package ClearcoatAutoSpa
 */

defined('CC_ROOT') || exit;

$page = isset($page) ? $page : array();
$slug = isset($page['slug']) ? $page['slug'] : 'index';
$title = isset($page['title']) ? $page['title'] : cc_biz('name');
$desc = isset($page['description']) ? $page['description'] : cc_biz('tagline');
$canonical = cc_abs_url($slug);
$noindex = !empty($page['noindex']);

$s = cc_settings();
$days = array('Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday');
$hours_ld = array();
foreach ($s['hours'] as $d => $h) {
    $hours_ld[] = array('@type' => 'OpeningHoursSpecification', 'dayOfWeek' => $days[(int) $d], 'opens' => $h[0], 'closes' => $h[1]);
}
$ld = array(
    '@context' => 'https://schema.org',
    '@type' => 'AutoWash',
    'name' => cc_biz('name'),
    'description' => cc_biz('tagline'),
    'url' => cc_abs_url('index'),
    'telephone' => cc_biz('phone_e164'),
    'email' => cc_biz('email'),
    'priceRange' => cc_biz('price_range'),
    'image' => cc_abs_url('index') . 'assets/img/og-image.jpg',
    'logo' => cc_abs_url('index') . 'assets/img/favicon.svg',
    'address' => array(
        '@type' => 'PostalAddress',
        'streetAddress' => cc_biz('street'),
        'addressLocality' => cc_biz('city'),
        'addressRegion' => cc_biz('province'),
        'postalCode' => cc_biz('postal'),
        'addressCountry' => cc_biz('country'),
    ),
    'openingHoursSpecification' => $hours_ld,
    'potentialAction' => array(
        '@type' => 'ReserveAction',
        'target' => cc_abs_url('book'),
        'name' => 'Book a car wash or detailing slot',
    ),
);
$nav = array(
    'index' => 'Home',
    'services' => 'Services & Prices',
    'about' => 'About',
    'faq' => 'FAQ',
    'contact' => 'Contact',
);
?><!DOCTYPE html>
<html lang="en-PH">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title><?php echo e($title); ?></title>
<meta name="description" content="<?php echo e($desc); ?>">
<?php if ($noindex) : ?><meta name="robots" content="noindex">
<?php endif; ?>
<link rel="canonical" href="<?php echo e($canonical); ?>">
<meta property="og:type" content="website">
<meta property="og:site_name" content="<?php echo e(cc_biz('name')); ?>">
<meta property="og:title" content="<?php echo e($title); ?>">
<meta property="og:description" content="<?php echo e($desc); ?>">
<meta property="og:url" content="<?php echo e($canonical); ?>">
<meta property="og:image" content="<?php echo e(cc_abs_url('index') . 'assets/img/og-image.jpg'); ?>">
<meta property="og:locale" content="en_PH">
<meta name="twitter:card" content="summary_large_image">
<meta name="theme-color" content="#0a7f91">
<link rel="icon" href="assets/img/favicon.svg" type="image/svg+xml">
<link rel="preload" href="assets/fonts/archivo-latin-800-normal.woff2" as="font" type="font/woff2" crossorigin>
<link rel="preload" href="assets/fonts/instrument-sans-latin-400-normal.woff2" as="font" type="font/woff2" crossorigin>
<link rel="stylesheet" href="assets/css/style.css?v=<?php echo e(CC_VERSION); ?>">
<script>/* Apply the saved theme before paint to avoid a flash. */try{var t=localStorage.getItem('cc-theme');if(t==='dark'||t==='light'){document.documentElement.setAttribute('data-theme',t);}}catch(e){}</script>
<script type="application/ld+json"><?php echo json_encode($ld, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG); ?></script>
<?php if (!empty($page['head'])) { echo $page['head']; } ?>
</head>
<body class="page-<?php echo e($slug); ?>">
<a class="skip-link" href="#main">Skip to content</a>
<header class="site-header" data-header>
  <div class="wrap header-row">
    <a class="brand" href="<?php echo e(cc_url('index')); ?>" aria-label="<?php echo e(cc_biz('name')); ?> home"><?php echo cc_logo(); ?></a>
    <nav class="main-nav" id="main-nav" aria-label="Main">
      <ul>
        <?php foreach ($nav as $key => $label) : ?>
          <li><a href="<?php echo e(cc_url($key)); ?>"<?php echo $key === $slug ? ' aria-current="page"' : ''; ?>><?php echo e($label); ?></a></li>
        <?php endforeach; ?>
      </ul>
      <a class="btn btn-signal nav-book" href="<?php echo e(cc_url('book')); ?>"<?php echo $slug === 'book' ? ' aria-current="page"' : ''; ?>><?php echo cc_icon('calendar'); ?> Book a slot</a>
    </nav>
    <div class="header-tools">
      <button class="icon-btn theme-toggle" type="button" data-theme-toggle aria-label="Switch to dark theme">
        <span class="when-light"><?php echo cc_icon('moon'); ?></span><span class="when-dark"><?php echo cc_icon('sun'); ?></span>
      </button>
      <button class="icon-btn menu-toggle" type="button" data-menu-toggle aria-controls="main-nav" aria-expanded="false" aria-label="Open menu">
        <span class="when-closed"><?php echo cc_icon('menu'); ?></span><span class="when-open"><?php echo cc_icon('close'); ?></span>
      </button>
    </div>
  </div>
</header>
<main id="main" tabindex="-1">
