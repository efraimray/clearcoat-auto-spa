<?php
/**
 * Clearcoat Auto Spa - booking confirmation.
 *
 * PHP: looks the booking up by reference + private token.
 * Static demo: assets/js/booking.js fills the same markup from localStorage.
 *
 * @package ClearcoatAutoSpa
 */

require __DIR__ . '/inc/bootstrap.php';

$b = null;
if (!cc_is_static()) {
    $ref = isset($_GET['ref']) ? (string) $_GET['ref'] : '';
    $tok = isset($_GET['t']) ? (string) $_GET['t'] : '';
    $b = ($ref !== '' && $tok !== '') ? cc_find_booking($ref, $tok) : null;
}
$svc = $b ? cc_service($b['service']) : null;
$st = $b ? cc_staff($b['staff']) : null;
$vehicles = cc_settings()['vehicles'];

$page = array(
    'slug' => 'confirmation',
    'title' => 'Booking Confirmed | Clearcoat Auto Spa',
    'description' => 'Your Clearcoat Auto Spa booking confirmation, with your reference number and a calendar file.',
    'noindex' => true,
    'scripts' => cc_is_static() ? cc_booking_scripts() : '',
);
$date_label = '';
if ($b) {
    $p = explode('-', $b['date']);
    $date_label = gmdate('l, j F Y', gmmktime(0, 0, 0, (int) $p[1], (int) $p[2], (int) $p[0]));
}
require CC_ROOT . '/inc/header.php';
?>
<section class="section confirm-section">
  <div class="wrap confirm-wrap">
    <?php if (!cc_is_static() && !$b) : ?>
      <div class="confirm-card">
        <h1>We couldn't find that booking</h1>
        <p>The link may be incomplete. Call <?php echo e(cc_biz('phone')); ?> with your reference, or <a href="<?php echo e(cc_url('book')); ?>">make a new booking</a>.</p>
      </div>
    <?php else : ?>
      <div class="confirm-card" data-confirm <?php echo cc_is_static() ? 'data-static="1"' : ''; ?>>
        <span class="confirm-icon"><?php echo cc_icon('check'); ?></span>
        <p class="eyebrow">Booking confirmed</p>
        <h1>See you soon, <span data-c="first"><?php echo $b ? e(strtok($b['name'], ' ')) : ''; ?></span>!</h1>
        <p class="ref">Reference <strong data-c="ref"><?php echo $b ? e($b['ref']) : ''; ?></strong></p>
        <dl class="review">
          <div><dt>Service</dt><dd data-c="service"><?php echo $svc ? e($svc['name']) : ''; ?></dd></div>
          <div><dt>Vehicle</dt><dd data-c="vehicle"><?php echo $b ? e($vehicles[$b['vehicle']]) : ''; ?><?php echo ($b && $b['plate']) ? ' · ' . e($b['plate']) : ''; ?></dd></div>
          <div><dt>Date</dt><dd data-c="date"><?php echo e($date_label); ?></dd></div>
          <div><dt>Time</dt><dd data-c="time"><?php echo $b ? e(cc_min_label($b['start']) . ' – ' . cc_min_label($b['start'] + $b['minutes'])) : ''; ?></dd></div>
          <div><dt>Detailer</dt><dd data-c="staff"><?php echo $st ? e($st['name']) : ''; ?></dd></div>
          <div><dt>Price</dt><dd data-c="price"><?php echo $b ? e(cc_peso($b['price'])) : ''; ?> <span class="muted">pay at the shop</span></dd></div>
        </dl>
        <div class="hero-actions">
          <?php if (cc_is_static()) : ?>
            <button class="btn btn-signal" type="button" data-ics><?php echo cc_icon('calendar'); ?> Add to calendar (.ics)</button>
          <?php else : ?>
            <a class="btn btn-signal" href="ics.php?<?php echo e(http_build_query(array('ref' => $b['ref'], 't' => $b['token']))); ?>"><?php echo cc_icon('calendar'); ?> Add to calendar (.ics)</a>
          <?php endif; ?>
          <a class="btn btn-ghost" href="<?php echo e(cc_url('index')); ?>">Back to home</a>
        </div>
        <p class="small muted">Please arrive 5 minutes early. To change or cancel, call or text <?php echo e(cc_biz('phone')); ?> with your reference at least 2 hours before.</p>
        <?php if (cc_is_static()) : ?><p class="demo-note small">Demo booking: saved only in this browser. Nothing was sent to the shop.</p><?php endif; ?>
      </div>
      <?php if (cc_is_static()) : ?>
        <div class="confirm-card" data-confirm-missing hidden>
          <h1>No demo booking found</h1>
          <p>This page shows a booking made in this browser. <a href="<?php echo e(cc_url('book')); ?>">Make a demo booking</a> to see it.</p>
        </div>
      <?php endif; ?>
    <?php endif; ?>
  </div>
</section>
<?php require CC_ROOT . '/inc/footer.php'; ?>
