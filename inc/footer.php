<?php
/**
 * Clearcoat Auto Spa - site footer, floating call/book buttons and scripts.
 *
 * @package ClearcoatAutoSpa
 */

defined('CC_ROOT') || exit;

$s = cc_settings();
$day_names = array('1' => 'Mon', '2' => 'Tue', '3' => 'Wed', '4' => 'Thu', '5' => 'Fri', '6' => 'Sat', '0' => 'Sun');
?>
</main>
<footer class="site-footer">
  <div class="wrap footer-grid">
    <div class="footer-brand">
      <?php echo cc_logo('logo logo-footer'); ?>
      <p>Hand car wash and detailing on Sayre Highway, Valencia City. Book a slot online and skip the line.</p>
      <ul class="social" aria-label="Social media (placeholder links)">
        <li><a href="<?php echo e(cc_biz('facebook_url')); ?>">Facebook</a></li>
        <li><a href="<?php echo e(cc_biz('instagram_url')); ?>">Instagram</a></li>
        <li><a href="<?php echo e(cc_biz('tiktok_url')); ?>">TikTok</a></li>
      </ul>
    </div>
    <div>
      <h2 class="footer-h">Opening hours</h2>
      <table class="hours-table">
        <caption class="sr-only">Opening hours</caption>
        <tbody>
        <?php foreach ($day_names as $d => $label) : ?>
          <tr data-day="<?php echo e($d); ?>"><th scope="row"><?php echo e($label); ?></th><td><?php echo isset($s['hours'][$d]) ? e(cc_min_label(cc_to_min($s['hours'][$d][0])) . ' – ' . cc_min_label(cc_to_min($s['hours'][$d][1]))) : 'Closed'; ?></td></tr>
        <?php endforeach; ?>
        </tbody>
      </table>
      <p class="small muted">Closed on major holidays. Last booking ends at closing time.</p>
    </div>
    <div>
      <h2 class="footer-h">Visit or call</h2>
      <address>
        <?php echo e(cc_biz('street')); ?><br>
        <?php echo e(cc_biz('city') . ', ' . cc_biz('province') . ' ' . cc_biz('postal')); ?>
      </address>
      <p><a href="<?php echo e(cc_biz('map_url')); ?>" rel="noopener" target="_blank">Open in Google Maps</a></p>
      <p>Call or text: <a href="tel:<?php echo e(cc_biz('phone_e164')); ?>"><?php echo e(cc_biz('phone')); ?></a><br>
      E-mail: <a href="mailto:<?php echo e(cc_biz('email')); ?>"><?php echo e(cc_biz('email')); ?></a></p>
    </div>
    <div>
      <h2 class="footer-h">Pages</h2>
      <ul class="footer-links">
        <li><a href="<?php echo e(cc_url('services')); ?>">Services &amp; prices</a></li>
        <li><a href="<?php echo e(cc_url('book')); ?>">Book a slot</a></li>
        <li><a href="<?php echo e(cc_url('about')); ?>">About us</a></li>
        <li><a href="<?php echo e(cc_url('faq')); ?>">FAQ</a></li>
        <li><a href="<?php echo e(cc_url('contact')); ?>">Contact</a></li>
        <li><a href="<?php echo e(cc_url('privacy')); ?>">Privacy notice</a></li>
        <?php if (cc_is_static()) : ?><li><a href="about-project.html">About this project</a></li><?php endif; ?>
      </ul>
    </div>
  </div>
  <div class="wrap footer-base">
    <p>&copy; <?php echo e(date('Y')); ?> <?php echo e(cc_biz('name')); ?>. A fictional business for a website template.</p>
    <p>Website template by <a href="https://www.efraimray.com/" rel="noopener">Efraim Ray</a></p>
  </div>
</footer>
<div class="fab" data-fab>
  <a class="fab-call" href="tel:<?php echo e(cc_biz('phone_e164')); ?>"><?php echo cc_icon('phone'); ?><span><span class="fab-label">Call</span> <?php echo e(cc_biz('phone')); ?></span></a>
  <?php if ($slug !== 'book') : ?><a class="fab-book" href="<?php echo e(cc_url('book')); ?>"><?php echo cc_icon('calendar'); ?><span>Book</span></a><?php endif; ?>
</div>
<script src="assets/js/main.js?v=<?php echo e(CC_VERSION); ?>" defer></script>
<?php if (!empty($page['scripts'])) { echo $page['scripts']; } ?>
</body>
</html>
