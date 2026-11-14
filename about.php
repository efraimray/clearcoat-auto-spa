<?php
/**
 * Clearcoat Auto Spa - about page with the team.
 *
 * @package ClearcoatAutoSpa
 */

require __DIR__ . '/inc/bootstrap.php';

$page = array(
    'slug' => 'about',
    'title' => 'About Us | Clearcoat Auto Spa, Valencia City',
    'description' => 'Meet the Clearcoat Auto Spa crew in Valencia City, Bukidnon: a small car wash and detailing shop that cares about paint, water and your time.',
);
$staff = cc_data('staff');
$services = cc_data('services');
$names = array();
foreach ($services as $svc) {
    $names[$svc['id']] = $svc['name'];
}
require CC_ROOT . '/inc/header.php';
?>
<section class="page-hero">
  <div class="wrap">
    <p class="eyebrow">About us</p>
    <h1>A small shop that takes its time</h1>
    <p class="lead">Clearcoat started in 2021 as a two-person wash bay beside Sayre Highway. Today we run three bays and a covered lounge, and we still wash every car by hand.</p>
  </div>
</section>

<section class="section section-tight">
  <div class="wrap split">
    <div class="prose">
      <h2>Our story</h2>
      <p>Valencia City roads mean dust in the dry season and red mud when it rains. Farm trips, school runs and long drives to Malaybalay or Cagayan de Oro all leave their mark. We opened Clearcoat because we wanted a place that cleans a car properly without scratching it.</p>
      <p>Online booking came later, after customers told us they hated waiting in line on Saturday mornings. Now most of our cars are booked ahead, and walk-ins fill the gaps.</p>
      <h2>What we believe</h2>
      <ul class="check-list">
        <li><?php echo cc_icon('check'); ?> A good wash is slow at the start and quick at the end: rinse, foam and wait before anyone touches the paint.</li>
        <li><?php echo cc_icon('check'); ?> We tell you the price before we start, and we ask before any extra work.</li>
        <li><?php echo cc_icon('check'); ?> Clean water matters. We filter and reuse our rinse water.</li>
      </ul>
    </div>
    <dl class="facts">
      <div><dt>Founded</dt><dd>2021</dd></div>
      <div><dt>Bays</dt><dd>2 wash + 1 detail</dd></div>
      <div><dt>Detailers</dt><dd><?php echo e((string) count($staff)); ?></dd></div>
      <div><dt>Service area</dt><dd>Valencia City &amp; nearby barangays</dd></div>
    </dl>
  </div>
</section>

<section class="section section-tint">
  <div class="wrap">
    <div class="section-head">
      <p class="eyebrow">The crew</p>
      <h2>Who will look after your car</h2>
      <p class="small muted">Team members are fictional people for this website template.</p>
    </div>
    <div class="team-grid">
      <?php foreach ($staff as $st) : ?>
        <article class="team-card">
          <span class="avatar" aria-hidden="true"><?php echo e($st['initials']); ?></span>
          <h3><?php echo e($st['name']); ?></h3>
          <p class="role"><?php echo e($st['role']); ?> · since <?php echo e((string) $st['since']); ?></p>
          <p><?php echo e($st['bio']); ?></p>
          <p class="small muted">Does: <?php echo e(implode(', ', array_map(function ($id) use ($names) { return $names[$id]; }, $st['services']))); ?></p>
          <a class="text-link" href="<?php echo e(cc_url('book', array('staff' => $st['id']))); ?>">Book with <?php echo e(strtok($st['name'], ' ')); ?> <?php echo cc_icon('arrow'); ?></a>
        </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="section cta-band">
  <div class="wrap cta-row">
    <div><h2>Come see the shop</h2><p><?php echo e(cc_biz('street') . ', ' . cc_biz('city')); ?></p></div>
    <div class="cta-actions"><a class="btn btn-signal btn-lg" href="<?php echo e(cc_url('book')); ?>"><?php echo cc_icon('calendar'); ?> Book a slot</a></div>
  </div>
</section>
<?php require CC_ROOT . '/inc/footer.php'; ?>
