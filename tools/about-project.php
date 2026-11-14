<?php
/**
 * "About this project" page - only in the static demo (built by export-static.php).
 * Explains the template to visitors of the showcase and GitHub Pages demo.
 *
 * @package ClearcoatAutoSpa
 */

defined('CC_ROOT') || exit;

$page = array(
    'slug' => 'about-project',
    'title' => 'About This Project | Clearcoat Auto Spa Website Template',
    'description' => 'How the Clearcoat Auto Spa booking website template was built: PHP, vanilla JavaScript and CSS, no framework, with a double-booking-safe slot engine.',
);
$langs = isset($cc_lang_stats) ? $cc_lang_stats : array();
require CC_ROOT . '/inc/header.php';
?>
<section class="page-hero">
  <div class="wrap">
    <p class="eyebrow">About this project</p>
    <h1>A booking website for a car wash, built without a framework</h1>
    <p class="lead">Clearcoat Auto Spa is a fictional business. This site is a client-ready template by <a href="https://www.efraimray.com/">Efraim Ray</a> for local service shops in the Philippines that take appointments: car washes, salons, laundry pickup, tutorial centers.</p>
  </div>
</section>
<section class="section section-tight">
  <div class="wrap split">
    <div class="prose">
      <h2>The problem</h2>
      <p>Small service shops in towns like Valencia City still take bookings by text and Messenger. Customers wait in line on busy mornings, and the owner juggles a paper schedule. A booking page that shows real open times fixes both, without monthly fees for a booking platform.</p>
      <h2>What it does</h2>
      <ul class="check-list">
        <li><?php echo cc_icon('check'); ?> Five-step booking: vehicle and service, detailer, date and time, details, confirm.</li>
        <li><?php echo cc_icon('check'); ?> Real slot engine: business hours, holidays, staff working days and skills, service length, a one-hour lead time.</li>
        <li><?php echo cc_icon('check'); ?> No double booking: the PHP version re-checks the slot inside a file lock before saving.</li>
        <li><?php echo cc_icon('check'); ?> Confirmation page with a booking reference and an .ics calendar file.</li>
        <li><?php echo cc_icon('check'); ?> Prices in pesos by vehicle size, a contact form with CSRF token and honeypot, a privacy notice for RA 10173.</li>
        <li><?php echo cc_icon('check'); ?> Light and dark themes, mobile menu, AutoWash JSON-LD, sitemap, no external requests.</li>
      </ul>
      <h2>This demo</h2>
      <p>You are looking at the static HTML export. It runs the same booking rules in JavaScript (<code>engine.js</code>, a twin of <code>inc/booking.php</code>) and keeps demo bookings in your browser only. Grey blocks in the schedule are generated demo bookings so the calendar never looks empty; switch them off with <code>"demo_busy": false</code>.</p>
      <h2>Install the real thing</h2>
      <p>Upload the folder to any PHP 7.4+ host (cPanel works), make <code>storage/</code> writable, and edit <code>data/settings.json</code>, <code>services.json</code> and <code>staff.json</code>. No database, Composer or npm needed. Full steps are in DEPLOYMENT.md on GitHub.</p>
      <p><a class="btn btn-signal" href="https://github.com/EfraimRay/clearcoat-auto-spa">View the code on GitHub</a></p>
    </div>
    <div>
      <h2>Languages used</h2>
      <div class="table-scroll">
        <table class="data-table" style="min-width:0">
          <thead><tr><th scope="col">Language</th><th scope="col">Lines</th><th scope="col">Share</th></tr></thead>
          <tbody>
          <?php foreach ($langs as $lang => $info) : ?>
            <tr><th scope="row"><?php echo e($lang); ?></th><td><?php echo e(number_format($info['lines'])); ?></td><td><?php echo e($info['pct']); ?>%</td></tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <p class="small muted">Counted from the source files (demo export and publisher scripts excluded).</p>
      <dl class="facts" style="margin-top:var(--s5)">
        <div><dt>Stack</dt><dd>PHP, no framework</dd></div>
        <div><dt>Storage</dt><dd>JSON files</dd></div>
        <div><dt>Pages</dt><dd>9 + this one</dd></div>
        <div><dt>License</dt><dd>MIT</dd></div>
      </dl>
      <h2 style="margin-top:var(--s6)">Pitch to a client</h2>
      <p>One-time build, hosted on the shop's own cPanel plan, no booking-platform subscription. Owners edit prices and staff in three small JSON files, and bookings arrive by e-mail.</p>
    </div>
  </div>
</section>
<?php require CC_ROOT . '/inc/footer.php'; ?>
