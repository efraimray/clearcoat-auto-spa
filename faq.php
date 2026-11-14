<?php
/**
 * Clearcoat Auto Spa - frequently asked questions (with FAQPage JSON-LD).
 *
 * @package ClearcoatAutoSpa
 */

require __DIR__ . '/inc/bootstrap.php';

$faq = array(
    'Booking' => array(
        array('Do I need to book, or can I walk in?', 'Walk-ins are welcome, but booked cars go first. On Saturday mornings a booking saves you the wait.'),
        array('How do I change or cancel my booking?', 'Call or text us with your booking reference at least two hours before your slot. There is no cancellation fee.'),
        array('What if I am late?', 'We hold your slot for 15 minutes. After that we may give it to a walk-in and fit you in at the next free time.'),
        array('Can I choose who works on my car?', 'Yes. Pick a detailer in the booking form, or choose "Any detailer" to see the most open times.'),
    ),
    'Payment' => array(
        array('How do I pay?', 'You pay at the shop after the job: cash, bank transfer or e-wallet. The website does not take payments.'),
        array('Why do SUVs and vans cost more?', 'Bigger vehicles take more product and more time. The services page shows each price by vehicle size.'),
    ),
    'Your car' => array(
        array('Will a hand wash scratch my paint?', 'We use the two-bucket method, grit guards and clean microfiber for every car to keep the risk of swirl marks low.'),
        array('Is engine bay cleaning safe?', 'We cover the alternator, fuse box and intake first, use low pressure and a citrus degreaser, and never spray hot engines.'),
        array('Should I remove my things from the car?', 'Please take valuables and loose items with you, especially for interior cleaning. We bag anything we find in the car.'),
    ),
);
$ld_items = array();
foreach ($faq as $group) {
    foreach ($group as $qa) {
        $ld_items[] = array('@type' => 'Question', 'name' => $qa[0], 'acceptedAnswer' => array('@type' => 'Answer', 'text' => $qa[1]));
    }
}
$page = array(
    'slug' => 'faq',
    'title' => 'FAQ | Clearcoat Auto Spa, Valencia City',
    'description' => 'Answers about booking, walk-ins, payment, prices by vehicle size and how Clearcoat Auto Spa cares for your paint and engine bay.',
    'head' => '<script type="application/ld+json">' . json_encode(array('@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => $ld_items), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) . '</script>',
);
require CC_ROOT . '/inc/header.php';
?>
<section class="page-hero">
  <div class="wrap">
    <p class="eyebrow">FAQ</p>
    <h1>Questions before your visit</h1>
    <p class="lead">Can't find your answer? <a href="<?php echo e(cc_url('contact')); ?>">Send us a message</a> or call <a href="tel:<?php echo e(cc_biz('phone_e164')); ?>"><?php echo e(cc_biz('phone')); ?></a>.</p>
  </div>
</section>
<section class="section section-tight">
  <div class="wrap faq-wrap">
    <?php foreach ($faq as $group => $items) : ?>
      <h2 class="faq-group"><?php echo e($group); ?></h2>
      <div class="faq-list">
        <?php foreach ($items as $qa) : ?>
          <details class="faq-item"><summary><?php echo e($qa[0]); ?></summary><div class="faq-a"><p><?php echo e($qa[1]); ?></p></div></details>
        <?php endforeach; ?>
      </div>
    <?php endforeach; ?>
  </div>
</section>
<?php require CC_ROOT . '/inc/footer.php'; ?>
