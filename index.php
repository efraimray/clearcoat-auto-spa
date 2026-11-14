<?php
/**
 * Clearcoat Auto Spa - home page.
 *
 * @package ClearcoatAutoSpa
 */

require __DIR__ . '/inc/bootstrap.php';

$page = array(
    'slug' => 'index',
    'title' => 'Clearcoat Auto Spa | Car Wash & Detailing in Valencia City, Bukidnon',
    'description' => 'Hand car wash, interior deep cleaning and detailing in Valencia City, Bukidnon. See prices in pesos and book a time slot online in under a minute.',
    'scripts' => cc_booking_scripts(),
);
$services = cc_data('services');
// Four most-booked services for the home page cards.
$featured = array($services[1], $services[4], $services[5], $services[6]);

require CC_ROOT . '/inc/header.php';
?>
<section class="hero">
  <div class="wrap hero-grid">
    <div class="hero-copy">
      <p class="eyebrow"><span class="open-pill" data-open-status>Open today</span> Sayre Highway, Valencia City</p>
      <h1>A clean car, on <em>your</em> schedule.</h1>
      <p class="lead">Hand wash, interior deep cleaning and paint care by a small crew that takes its time. Pick a slot online, drop by, and be back on the road with a car that looks new.</p>
      <div class="hero-actions">
        <a class="btn btn-signal btn-lg" href="<?php echo e(cc_url('book')); ?>"><?php echo cc_icon('calendar'); ?> Book a slot</a>
        <a class="btn btn-ghost btn-lg" href="<?php echo e(cc_url('services')); ?>">See prices</a>
      </div>
      <div class="next-slot" data-next-slot aria-live="polite">
        <span class="next-slot-label">Next free Wash &amp; Vacuum slot</span>
        <strong data-next-slot-value>Checking the schedule…</strong>
      </div>
    </div>
    <div class="hero-visual">
      <?php echo cc_hero_art(); ?>
      <ul class="hero-badges" aria-label="Highlights">
        <li><strong>3</strong> wash &amp; detail bays</li>
        <li><strong>₱180</strong> express wash, sedan</li>
        <li><strong>60 s</strong> to book online</li>
      </ul>
    </div>
  </div>
</section>

<section class="section">
  <div class="wrap">
    <div class="section-head">
      <p class="eyebrow">Most booked</p>
      <h2>Services people come back for</h2>
      <p>Prices shown are for a sedan or hatchback. SUVs, pickups and vans are listed on the <a href="<?php echo e(cc_url('services')); ?>">full price list</a>.</p>
    </div>
    <div class="card-grid">
      <?php foreach ($featured as $svc) : ?>
        <article class="svc-card">
          <span class="svc-icon"><?php echo cc_icon($svc['icon']); ?></span>
          <h3><?php echo e($svc['name']); ?></h3>
          <p><?php echo e($svc['summary']); ?></p>
          <p class="svc-meta"><span><?php echo cc_icon('clock'); ?> <?php echo e(cc_duration($svc['minutes'])); ?></span><span class="price">from <?php echo e(cc_peso(min($svc['prices']))); ?></span></p>
          <a class="text-link" href="<?php echo e(cc_url('book', array('service' => $svc['id']))); ?>">Book this <?php echo cc_icon('arrow'); ?></a>
        </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="section section-tint">
  <div class="wrap">
    <div class="section-head">
      <p class="eyebrow">How booking works</p>
      <h2>Three steps, no waiting in line</h2>
    </div>
    <ol class="steps">
      <li><span class="step-n">1</span><h3>Pick a service</h3><p>Choose your vehicle type and what it needs. Prices update for SUVs and vans.</p></li>
      <li><span class="step-n">2</span><h3>Choose a time</h3><p>See real open slots for the next three weeks. Pick a detailer or let us assign one.</p></li>
      <li><span class="step-n">3</span><h3>Drop by</h3><p>Get a booking reference and a calendar file. Pay at the shop when you pick up.</p></li>
    </ol>
  </div>
</section>

<section class="section">
  <div class="wrap split">
    <div>
      <p class="eyebrow">Why Clearcoat</p>
      <h2>Careful with your paint, careful with water</h2>
      <p>Most swirl marks come from dirty sponges and rushed drying. We wash with two buckets, clean microfiber for every car, and pH-neutral soap. Our rinse water is filtered and reused for the first rinse of the next car.</p>
      <a class="btn btn-ghost" href="<?php echo e(cc_url('about')); ?>">Meet the crew</a>
    </div>
    <ul class="feature-list">
      <li><?php echo cc_icon('shield'); ?><div><h3>Paint-safe wash</h3><p>Two-bucket method, grit guards and fresh microfiber towels.</p></div></li>
      <li><?php echo cc_icon('leaf'); ?><div><h3>Water recycling</h3><p>Filtered rinse water is reused for pre-rinses.</p></div></li>
      <li><?php echo cc_icon('cup'); ?><div><h3>Covered waiting lounge</h3><p>Free coffee, Wi-Fi and a view of your car.</p></div></li>
      <li><?php echo cc_icon('clock'); ?><div><h3>On-time promise</h3><p>Booked cars go first. We tell you if a job runs long.</p></div></li>
    </ul>
  </div>
</section>

<section class="section section-tint">
  <div class="wrap">
    <div class="section-head">
      <p class="eyebrow">Sample testimonials</p>
      <h2>What customers say</h2>
      <p class="small muted">These are sample testimonials for this website template. Replace them with real reviews from your customers.</p>
    </div>
    <div class="quote-grid">
      <figure class="quote"><blockquote><p>“Booked a Wash &amp; Vacuum online before work. In and out in an hour, and they even cleaned the door jambs.”</p></blockquote><figcaption>Sample customer, Poblacion</figcaption></figure>
      <figure class="quote"><blockquote><p>“Our pickup came back from a farm trip covered in mud. After the Full Detail it looked like it left the dealer.”</p></blockquote><figcaption>Sample customer, Lumbo</figcaption></figure>
      <figure class="quote"><blockquote><p>“The watermark removal on the windshield made night driving in the rain so much easier.”</p></blockquote><figcaption>Sample customer, Bagontaas</figcaption></figure>
    </div>
  </div>
</section>

<section class="section cta-band">
  <div class="wrap cta-row">
    <div>
      <h2>Ready for a cleaner car?</h2>
      <p>Pick a time that suits you. It takes about a minute.</p>
    </div>
    <div class="cta-actions">
      <a class="btn btn-signal btn-lg" href="<?php echo e(cc_url('book')); ?>"><?php echo cc_icon('calendar'); ?> Book a slot</a>
      <p class="small">Or call <a href="tel:<?php echo e(cc_biz('phone_e164')); ?>"><?php echo e(cc_biz('phone')); ?></a></p>
    </div>
  </div>
</section>
<?php require CC_ROOT . '/inc/footer.php'; ?>
