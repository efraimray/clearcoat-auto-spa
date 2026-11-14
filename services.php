<?php
/**
 * Clearcoat Auto Spa - services and price list.
 *
 * @package ClearcoatAutoSpa
 */

require __DIR__ . '/inc/bootstrap.php';

$page = array(
    'slug' => 'services',
    'title' => 'Services & Prices | Clearcoat Auto Spa, Valencia City',
    'description' => 'Car wash and detailing prices in pesos for sedans, SUVs, pickups, vans and motorcycles in Valencia City, Bukidnon. Durations, inclusions and online booking.',
);
$services = cc_data('services');
$vehicles = cc_settings()['vehicles'];
require CC_ROOT . '/inc/header.php';
?>
<section class="page-hero">
  <div class="wrap">
    <p class="eyebrow">Services &amp; prices</p>
    <h1>Honest prices, by vehicle size</h1>
    <p class="lead">Every price includes labor and materials. Pay at the shop after the job, in cash or by bank transfer or e-wallet. Choose your vehicle type to update the prices.</p>
    <div class="seg" role="radiogroup" aria-label="Vehicle type" data-price-switch>
      <?php $first = true; foreach (array('sedan', 'suv', 'van') as $v) : ?>
        <button type="button" role="radio" aria-checked="<?php echo $first ? 'true' : 'false'; ?>" data-vehicle="<?php echo e($v); ?>"><?php echo e($vehicles[$v]); ?></button>
      <?php $first = false; endforeach; ?>
    </div>
  </div>
</section>

<section class="section section-tight">
  <div class="wrap price-list">
    <?php foreach ($services as $svc) : ?>
      <article class="price-row" id="<?php echo e($svc['id']); ?>">
        <div class="price-main">
          <span class="svc-icon"><?php echo cc_icon($svc['icon']); ?></span>
          <div>
            <h2><?php echo e($svc['name']); ?><?php if (!empty($svc['popular'])) : ?> <span class="tag">Popular</span><?php endif; ?></h2>
            <p><?php echo e($svc['summary']); ?></p>
            <ul class="includes">
              <?php foreach ($svc['includes'] as $inc) : ?><li><?php echo cc_icon('check'); ?><?php echo e($inc); ?></li><?php endforeach; ?>
            </ul>
          </div>
        </div>
        <div class="price-side">
          <p class="duration"><?php echo cc_icon('clock'); ?> <?php echo e(cc_duration($svc['minutes'])); ?></p>
          <?php if (isset($svc['prices']['motorcycle'])) : ?>
            <p class="price-big"><?php echo e(cc_peso($svc['prices']['motorcycle'])); ?></p>
            <p class="small muted">Motorcycles and scooters</p>
          <?php else : ?>
            <p class="price-big" data-prices='<?php echo e(json_encode($svc['prices'])); ?>'><?php echo e(cc_peso($svc['prices']['sedan'])); ?></p>
            <p class="small muted" data-vehicle-label><?php echo e($vehicles['sedan']); ?></p>
          <?php endif; ?>
          <a class="btn btn-signal" href="<?php echo e(cc_url('book', array('service' => $svc['id']))); ?>">Book</a>
        </div>
      </article>
    <?php endforeach; ?>
  </div>
</section>

<section class="section section-tint">
  <div class="wrap">
    <div class="section-head">
      <p class="eyebrow">Full table</p>
      <h2>All prices at a glance</h2>
    </div>
    <div class="table-scroll">
      <table class="data-table">
        <caption class="sr-only">Prices in Philippine pesos by vehicle type</caption>
        <thead><tr><th scope="col">Service</th><th scope="col">Time</th><th scope="col">Sedan / Hatch</th><th scope="col">SUV / Pickup</th><th scope="col">Van / Large</th></tr></thead>
        <tbody>
        <?php foreach ($services as $svc) : ?>
          <tr>
            <th scope="row"><?php echo e($svc['name']); ?></th>
            <td><?php echo e(cc_duration($svc['minutes'])); ?></td>
            <?php if (isset($svc['prices']['motorcycle'])) : ?>
              <td colspan="3"><?php echo e(cc_peso($svc['prices']['motorcycle'])); ?> (motorcycles)</td>
            <?php else : ?>
              <td><?php echo e(cc_peso($svc['prices']['sedan'])); ?></td><td><?php echo e(cc_peso($svc['prices']['suv'])); ?></td><td><?php echo e(cc_peso($svc['prices']['van'])); ?></td>
            <?php endif; ?>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <div class="addons">
      <h3>Add-ons at the counter</h3>
      <ul>
        <li>Tire shine <strong>₱80</strong></li>
        <li>Air freshener refill <strong>₱60</strong></li>
        <li>Headlight restoration (pair) <strong>₱700</strong></li>
        <li>Pet hair removal <strong>from ₱300</strong></li>
      </ul>
      <p class="small muted">Heavily soiled vehicles (mud, sap, pet hair) may need extra time. We tell you before we start any extra work.</p>
    </div>
  </div>
</section>
<?php require CC_ROOT . '/inc/footer.php'; ?>
