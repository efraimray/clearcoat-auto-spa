<?php
/**
 * Clearcoat Auto Spa - contact page with a server-side message form.
 *
 * POST: CSRF check, honeypot, validation, save to storage/messages.json,
 * optional e-mail, then redirect (Post/Redirect/Get) to ?sent=1.
 *
 * @package ClearcoatAutoSpa
 */

require __DIR__ . '/inc/bootstrap.php';

$errors = array();
$values = array('name' => '', 'contact' => '', 'topic' => 'General question', 'message' => '');
$topics = array('General question', 'Fleet or company cars', 'Detailing quote', 'Feedback');
$sent = !cc_is_static() && isset($_GET['sent']);

if (!cc_is_static() && isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $values = array(
        'name' => cc_post('name', 80),
        'contact' => cc_post('contact', 120),
        'topic' => cc_post('topic', 40),
        'message' => cc_post_text('message', 1500),
    );
    if (!cc_csrf_check(isset($_POST['csrf']) ? $_POST['csrf'] : '')) {
        $errors['form'] = 'Your session expired. Please send the form again.';
    }
    if (cc_post('website') !== '') {
        // Honeypot filled in: a bot. Pretend it worked and drop it.
        header('Location: ' . cc_url('contact', array('sent' => 1)), true, 303);
        exit;
    }
    if (strlen($values['name']) < 2) {
        $errors['name'] = 'Enter your name.';
    }
    if (!cc_valid_mobile($values['contact']) && !filter_var($values['contact'], FILTER_VALIDATE_EMAIL)) {
        $errors['contact'] = 'Enter a mobile number (0917 123 4567) or an e-mail address.';
    }
    if (!in_array($values['topic'], $topics, true)) {
        $values['topic'] = 'General question';
    }
    if (strlen($values['message']) < 10) {
        $errors['message'] = 'Write a short message (at least 10 characters).';
    }
    if (!$errors) {
        $msg = $values + array('received' => gmdate('c'));
        cc_with_store('messages', function (&$all) use ($msg) {
            $all[] = $msg;
            return true;
        });
        cc_notify('New website message: ' . $values['topic'], "Name: {$values['name']}\nContact: {$values['contact']}\nTopic: {$values['topic']}\n\n{$values['message']}\n");
        header('Location: ' . cc_url('contact', array('sent' => 1)), true, 303);
        exit;
    }
}

$page = array(
    'slug' => 'contact',
    'title' => 'Contact | Clearcoat Auto Spa, Valencia City',
    'description' => 'Call, text or message Clearcoat Auto Spa on Sayre Highway, Valencia City, Bukidnon. Opening hours, directions and a quick contact form.',
);
require CC_ROOT . '/inc/header.php';

/** Print a field error. */
function cc_field_error($errors, $key)
{
    return isset($errors[$key]) ? '<p class="field-error" id="err-' . e($key) . '">' . e($errors[$key]) . '</p>' : '<p class="field-error" id="err-' . e($key) . '" hidden></p>';
}
?>
<section class="page-hero">
  <div class="wrap">
    <p class="eyebrow">Contact</p>
    <h1>Talk to the shop</h1>
    <p class="lead">For bookings, the fastest way is the <a href="<?php echo e(cc_url('book')); ?>">online booking form</a>. For everything else, call, text or send a message below.</p>
  </div>
</section>
<section class="section section-tight">
  <div class="wrap contact-grid">
    <div class="contact-cards">
      <div class="contact-card"><?php echo cc_icon('phone'); ?><div><h2>Call or text</h2><p><a href="tel:<?php echo e(cc_biz('phone_e164')); ?>"><?php echo e(cc_biz('phone')); ?></a></p><p class="small muted">Shown as text so you can copy it.</p></div></div>
      <div class="contact-card"><?php echo cc_icon('mail'); ?><div><h2>E-mail</h2><p><a href="mailto:<?php echo e(cc_biz('email')); ?>"><?php echo e(cc_biz('email')); ?></a></p></div></div>
      <div class="contact-card"><?php echo cc_icon('pin'); ?><div><h2>Visit</h2><address><?php echo e(cc_biz('street')); ?><br><?php echo e(cc_biz('city') . ', ' . cc_biz('province') . ' ' . cc_biz('postal')); ?></address><p><a href="<?php echo e(cc_biz('map_url')); ?>" target="_blank" rel="noopener">Open in Google Maps</a></p><p class="small muted">Look for the blue droplet sign beside the fuel station.</p></div></div>
      <div class="contact-card"><?php echo cc_icon('chat'); ?><div><h2>Messenger</h2><p><a href="<?php echo e(cc_biz('messenger_url')); ?>">Message us on Messenger</a></p><p class="small muted">Placeholder link: add your page's m.me address.</p></div></div>
    </div>

    <div class="form-card">
      <h2>Send a message</h2>
      <div class="form-success" data-form-success <?php echo $sent ? '' : 'hidden'; ?> role="status" tabindex="-1">
        <?php echo cc_icon('check'); ?>
        <div><h3>Message sent</h3><p>Thanks! We reply within the day during opening hours.<?php echo cc_is_static() ? ' (Demo: nothing was sent.)' : ''; ?></p></div>
      </div>
      <form method="post" action="<?php echo e(cc_url('contact')); ?>" novalidate data-validate data-static-form="<?php echo cc_is_static() ? '1' : '0'; ?>" <?php echo $sent ? 'hidden' : ''; ?>>
        <?php echo cc_csrf_field(); ?>
        <?php if (isset($errors['form'])) : ?><p class="form-alert" role="alert"><?php echo e($errors['form']); ?></p><?php endif; ?>
        <div class="hp" aria-hidden="true"><label for="website">Leave this empty</label><input id="website" name="website" type="text" tabindex="-1" autocomplete="off"></div>
        <div class="field">
          <label for="c-name">Your name</label>
          <input id="c-name" name="name" type="text" autocomplete="name" required minlength="2" maxlength="80" value="<?php echo e($values['name']); ?>" aria-describedby="err-name">
          <?php echo cc_field_error($errors, 'name'); ?>
        </div>
        <div class="field">
          <label for="c-contact">Mobile number or e-mail</label>
          <input id="c-contact" name="contact" type="text" autocomplete="tel" required data-rule="mobile-or-email" maxlength="120" value="<?php echo e($values['contact']); ?>" aria-describedby="err-contact">
          <?php echo cc_field_error($errors, 'contact'); ?>
        </div>
        <div class="field">
          <label for="c-topic">Topic</label>
          <select id="c-topic" name="topic">
            <?php foreach ($topics as $t) : ?><option<?php echo $t === $values['topic'] ? ' selected' : ''; ?>><?php echo e($t); ?></option><?php endforeach; ?>
          </select>
        </div>
        <div class="field">
          <label for="c-message">Message</label>
          <textarea id="c-message" name="message" rows="5" required minlength="10" maxlength="1500" aria-describedby="err-message"><?php echo e($values['message']); ?></textarea>
          <?php echo cc_field_error($errors, 'message'); ?>
        </div>
        <p class="small muted">We use your details only to reply. See our <a href="<?php echo e(cc_url('privacy')); ?>">privacy notice</a>.</p>
        <button class="btn btn-signal btn-lg" type="submit">Send message</button>
      </form>
    </div>
  </div>
</section>
<?php require CC_ROOT . '/inc/footer.php'; ?>
