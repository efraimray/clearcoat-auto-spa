<?php
/**
 * Clearcoat Auto Spa - page not found.
 *
 * @package ClearcoatAutoSpa
 */

require __DIR__ . '/inc/bootstrap.php';

if (!cc_is_static() && !headers_sent()) {
    http_response_code(404);
}
$page = array(
    'slug' => '404',
    'title' => 'Page Not Found | Clearcoat Auto Spa',
    'description' => 'This page could not be found. Go back to the Clearcoat Auto Spa home page or book a car wash slot.',
    'noindex' => true,
);
require CC_ROOT . '/inc/header.php';
?>
<section class="page-hero not-found">
  <div class="wrap">
    <p class="eyebrow">Error 404</p>
    <h1>This page went through the wash and never came out.</h1>
    <p class="lead">The link may be old or mistyped. Here are some places that do exist:</p>
    <div class="hero-actions">
      <a class="btn btn-signal" href="<?php echo e(cc_url('index')); ?>">Home</a>
      <a class="btn btn-ghost" href="<?php echo e(cc_url('services')); ?>">Services &amp; prices</a>
      <a class="btn btn-ghost" href="<?php echo e(cc_url('book')); ?>">Book a slot</a>
    </div>
  </div>
</section>
<?php require CC_ROOT . '/inc/footer.php'; ?>
