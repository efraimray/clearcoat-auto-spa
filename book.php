<?php
/**
 * Clearcoat Auto Spa - booking wizard.
 *
 * GET  shows the five-step wizard (assets/js/booking.js drives it).
 * POST validates and saves the booking with cc_create_booking(), which
 *      re-checks the slot inside a file lock, then redirects to the
 *      confirmation page. On error the wizard is shown again with messages.
 *
 * @package ClearcoatAutoSpa
 */

require __DIR__ . '/inc/bootstrap.php';

$errors = array();
$old = array();
if (!cc_is_static() && isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $old = array(
        'vehicle' => cc_post('vehicle', 20),
        'service' => cc_post('service', 40),
        'staff'   => cc_post('staff', 40),
        'date'    => cc_post('date', 10),
        'start'   => cc_post('start', 5),
        'name'    => cc_post('name', 80),
        'mobile'  => cc_post('mobile', 20),
        'email'   => cc_post('email', 120),
        'plate'   => preg_replace('/[^A-Za-z0-9 \-]/', '', cc_post('plate', 12)),
        'notes'   => cc_post_text('notes', 500),
        'consent' => !empty($_POST['consent']),
    );
    if (!cc_csrf_check(isset($_POST['csrf']) ? $_POST['csrf'] : '')) {
        $errors['form'] = 'Your session expired. Please check your choices and confirm again.';
    } elseif (cc_post('website') !== '') {
        $errors['form'] = 'Something went wrong. Please call us to book.';
    } else {
        $result = cc_create_booking($old);
        if ($result['ok']) {
            $b = $result['booking'];
            $svc = cc_service($b['service']);
            $st = cc_staff($b['staff']);
            cc_notify(
                'New booking ' . $b['ref'],
                "{$b['ref']}\n{$svc['name']} ({$b['vehicle']}) " . cc_peso($b['price']) . "\n{$b['date']} " . cc_min_label($b['start']) . " with {$st['name']}\n{$b['name']} {$b['mobile']} {$b['email']}\nPlate: {$b['plate']}\nNotes: {$b['notes']}\n"
            );
            header('Location: ' . cc_url('confirmation', array('ref' => $b['ref'], 't' => $b['token'])), true, 303);
            exit;
        }
        $errors = $result['errors'];
    }
}

$page = array(
    'slug' => 'book',
    'title' => 'Book a Slot | Clearcoat Auto Spa, Valencia City',
    'description' => 'Book a car wash or detailing slot at Clearcoat Auto Spa in Valencia City, Bukidnon. Choose a service, a detailer and an open time in under a minute.',
    'scripts' => cc_booking_scripts(),
);
$vehicles = cc_settings()['vehicles'];
$services = cc_data('services');
$staff = cc_data('staff');
require CC_ROOT . '/inc/header.php';
?>
<section class="page-hero page-hero-compact">
  <div class="wrap">
    <p class="eyebrow">Online booking</p>
    <h1>Book your slot</h1>
    <p class="lead">Five quick steps. You pay at the shop after the job.</p>
  </div>
</section>

<section class="section section-tight">
  <div class="wrap">
    <?php if (cc_is_static()) : ?>
      <p class="demo-note" role="note"><strong>Demo booking.</strong> This static preview saves bookings only in your browser to show how the flow works. Connect the PHP version (or another backend) to take real bookings.</p>
    <?php endif; ?>
    <noscript><p class="form-alert">The booking form needs JavaScript. Please call <?php echo e(cc_biz('phone')); ?> to book.</p></noscript>
    <?php if ($errors) : ?>
      <div class="form-alert" role="alert" tabindex="-1" data-server-errors>
        <p><strong>Please check your booking:</strong></p>
        <ul><?php foreach ($errors as $msg) : ?><li><?php echo e($msg); ?></li><?php endforeach; ?></ul>
      </div>
    <?php endif; ?>

    <div class="booking-layout">
      <form class="wizard" method="post" action="<?php echo e(cc_url('book')); ?>" novalidate data-wizard
        data-old='<?php echo e(json_encode($old)); ?>'>
        <?php echo cc_csrf_field(); ?>
        <div class="hp" aria-hidden="true"><label for="b-website">Leave this empty</label><input id="b-website" name="website" type="text" tabindex="-1" autocomplete="off"></div>

        <ol class="wizard-progress" aria-label="Booking steps">
          <li data-progress="1" aria-current="step"><span>1</span> Service</li>
          <li data-progress="2"><span>2</span> Detailer</li>
          <li data-progress="3"><span>3</span> Date &amp; time</li>
          <li data-progress="4"><span>4</span> Your details</li>
          <li data-progress="5"><span>5</span> Confirm</li>
        </ol>

        <!-- Step 1: vehicle + service -->
        <fieldset class="step" data-step="1">
          <legend class="step-title">What are we cleaning?</legend>
          <div class="choice-row" role="radiogroup" aria-label="Vehicle type">
            <?php foreach ($vehicles as $key => $label) : ?>
              <label class="chip-radio"><input type="radio" name="vehicle" value="<?php echo e($key); ?>"<?php echo $key === 'sedan' ? ' checked' : ''; ?>><span><?php echo e($label); ?></span></label>
            <?php endforeach; ?>
          </div>
          <p class="step-sub">Choose a service</p>
          <div class="option-grid" data-service-list>
            <?php foreach ($services as $svc) : ?>
              <label class="option-card" data-service-card="<?php echo e($svc['id']); ?>" data-vehicles="<?php echo e(implode(' ', array_keys($svc['prices']))); ?>">
                <input type="radio" name="service" value="<?php echo e($svc['id']); ?>">
                <span class="option-body">
                  <span class="option-title"><?php echo e($svc['name']); ?></span>
                  <span class="option-meta"><?php echo e(cc_duration($svc['minutes'])); ?> · <span data-price-for="<?php echo e($svc['id']); ?>"><?php echo e(cc_peso(reset($svc['prices']))); ?></span></span>
                  <span class="option-desc"><?php echo e($svc['summary']); ?></span>
                </span>
              </label>
            <?php endforeach; ?>
          </div>
          <p class="field-error" data-error-for="service" hidden></p>
        </fieldset>

        <!-- Step 2: detailer -->
        <fieldset class="step" data-step="2" hidden>
          <legend class="step-title">Who should do it?</legend>
          <div class="option-grid option-grid-staff" data-staff-list>
            <label class="option-card" data-staff-card="any">
              <input type="radio" name="staff" value="any" checked>
              <span class="option-body"><span class="avatar avatar-sm" aria-hidden="true">★</span><span class="option-title">Any detailer</span><span class="option-desc">Shows the most open times.</span></span>
            </label>
            <?php foreach ($staff as $st) : ?>
              <label class="option-card" data-staff-card="<?php echo e($st['id']); ?>">
                <input type="radio" name="staff" value="<?php echo e($st['id']); ?>">
                <span class="option-body"><span class="avatar avatar-sm" aria-hidden="true"><?php echo e($st['initials']); ?></span><span class="option-title"><?php echo e($st['name']); ?></span><span class="option-desc"><?php echo e($st['role']); ?></span><span class="option-note" data-staff-note></span></span>
              </label>
            <?php endforeach; ?>
          </div>
        </fieldset>

        <!-- Step 3: date + time -->
        <fieldset class="step" data-step="3" hidden>
          <legend class="step-title">Pick a date and time</legend>
          <input type="hidden" name="date" value="" data-date-input>
          <div class="date-strip" data-date-strip role="listbox" aria-label="Date"></div>
          <p class="slot-status" data-slot-status aria-live="polite"></p>
          <div class="slot-grid" data-slot-grid role="radiogroup" aria-label="Start time"></div>
          <p class="field-error" data-error-for="start" hidden></p>
        </fieldset>

        <!-- Step 4: details -->
        <fieldset class="step" data-step="4" hidden>
          <legend class="step-title">Your details</legend>
          <div class="field-grid">
            <div class="field">
              <label for="b-name">Full name</label>
              <input id="b-name" name="name" type="text" autocomplete="name" required minlength="2" maxlength="80">
              <p class="field-error" data-error-for="name" hidden></p>
            </div>
            <div class="field">
              <label for="b-mobile">Mobile number</label>
              <input id="b-mobile" name="mobile" type="tel" autocomplete="tel" inputmode="tel" required placeholder="0917 123 4567" data-rule="mobile" maxlength="20">
              <p class="field-error" data-error-for="mobile" hidden></p>
            </div>
            <div class="field">
              <label for="b-email">E-mail <span class="muted">(optional)</span></label>
              <input id="b-email" name="email" type="email" autocomplete="email" maxlength="120">
              <p class="field-error" data-error-for="email" hidden></p>
            </div>
            <div class="field">
              <label for="b-plate">Plate number <span class="muted">(optional)</span></label>
              <input id="b-plate" name="plate" type="text" autocapitalize="characters" maxlength="12" placeholder="ABC 1234">
            </div>
            <div class="field field-wide">
              <label for="b-notes">Notes <span class="muted">(optional)</span></label>
              <textarea id="b-notes" name="notes" rows="3" maxlength="500" placeholder="Pet hair, child seat, mud from a farm trip…"></textarea>
            </div>
            <div class="field field-wide">
              <label class="check"><input type="checkbox" name="consent" value="1" required> <span>I agree that Clearcoat Auto Spa may use these details to manage my booking, as described in the <a href="<?php echo e(cc_url('privacy')); ?>">privacy notice</a>.</span></label>
              <p class="field-error" data-error-for="consent" hidden></p>
            </div>
          </div>
        </fieldset>

        <!-- Step 5: review -->
        <fieldset class="step" data-step="5" hidden>
          <legend class="step-title">Check and confirm</legend>
          <dl class="review" data-review></dl>
          <p class="small muted">Payment is made at the shop after the job: cash, bank transfer or e-wallet.</p>
        </fieldset>

        <div class="wizard-nav">
          <button class="btn btn-ghost" type="button" data-prev hidden>Back</button>
          <button class="btn btn-signal" type="button" data-next>Continue</button>
          <button class="btn btn-signal" type="submit" data-submit hidden><?php echo cc_icon('check'); ?> Confirm booking</button>
        </div>
      </form>

      <aside class="summary" aria-label="Booking summary">
        <h2>Your booking</h2>
        <dl data-summary>
          <div><dt>Vehicle</dt><dd data-sum="vehicle">Sedan / Hatchback</dd></div>
          <div><dt>Service</dt><dd data-sum="service">Not chosen</dd></div>
          <div><dt>Detailer</dt><dd data-sum="staff">Any detailer</dd></div>
          <div><dt>Date</dt><dd data-sum="date">Not chosen</dd></div>
          <div><dt>Time</dt><dd data-sum="time">Not chosen</dd></div>
        </dl>
        <p class="summary-total"><span>Total, pay at the shop</span><strong data-sum="price">₱0</strong></p>
        <p class="small muted">Need help? Call <?php echo e(cc_biz('phone')); ?>.</p>
      </aside>
    </div>
  </div>
</section>
<?php require CC_ROOT . '/inc/footer.php'; ?>
