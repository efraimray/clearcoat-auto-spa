<?php
/**
 * Clearcoat Auto Spa - privacy notice (Data Privacy Act of 2012, RA 10173).
 *
 * @package ClearcoatAutoSpa
 */

require __DIR__ . '/inc/bootstrap.php';

$page = array(
    'slug' => 'privacy',
    'title' => 'Privacy Notice | Clearcoat Auto Spa',
    'description' => 'How Clearcoat Auto Spa collects, uses and protects booking and contact details under the Philippine Data Privacy Act of 2012 (RA 10173).',
);
require CC_ROOT . '/inc/header.php';
?>
<section class="page-hero">
  <div class="wrap">
    <p class="eyebrow">Privacy notice</p>
    <h1>How we handle your details</h1>
    <p class="lead">This notice follows the Data Privacy Act of 2012 (Republic Act No. 10173). It is a sample notice for a website template; have it reviewed before using it for a real business.</p>
  </div>
</section>
<section class="section section-tight">
  <div class="wrap prose">
    <h2>What we collect</h2>
    <p>When you book: your name, mobile number, optional e-mail, optional plate number, the service, date and time, and any notes you type. When you send a message: your name, contact details and your message.</p>
    <h2>Why we collect it</h2>
    <p>Only to confirm and manage your booking, to contact you about it, and to answer your messages. We do not sell your details or use them for advertising.</p>
    <h2>How long we keep it</h2>
    <p>Booking records are kept for one year for scheduling and accounting, then deleted. Messages are deleted after six months.</p>
    <h2>How we protect it</h2>
    <p>Records are stored on our web server in a folder that cannot be opened from the internet. Only the owner and the shop manager can see them.</p>
    <h2>Your rights</h2>
    <p>You may ask to see, correct or delete your details, or withdraw your consent, at any time. Write to <a href="mailto:<?php echo e(cc_biz('email')); ?>"><?php echo e(cc_biz('email')); ?></a> or call <?php echo e(cc_biz('phone')); ?>. You may also file a complaint with the National Privacy Commission.</p>
    <h2>Cookies</h2>
    <p>The site uses one session cookie to protect forms from misuse, and your browser remembers your light or dark theme choice. There are no tracking or advertising cookies.</p>
  </div>
</section>
<?php require CC_ROOT . '/inc/footer.php'; ?>
