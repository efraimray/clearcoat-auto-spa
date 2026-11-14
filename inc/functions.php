<?php
/**
 * Clearcoat Auto Spa - shared helpers.
 *
 * Loaded by every page through inc/bootstrap.php. Holds the small toolbox the
 * site needs: data loading, escaping, links, CSRF tokens and money/time format.
 *
 * @package ClearcoatAutoSpa
 */

defined('CC_ROOT') || exit;

/**
 * Read a JSON file from /data and cache it for the request.
 *
 * @param string $name File name without extension (services, staff, settings).
 * @return array
 */
function cc_data($name)
{
    static $cache = array();
    if (!isset($cache[$name])) {
        $file = CC_ROOT . '/data/' . basename($name) . '.json';
        $raw  = is_readable($file) ? file_get_contents($file) : '[]';
        $json = json_decode($raw, true);
        $cache[$name] = is_array($json) ? $json : array();
    }
    return $cache[$name];
}

/** Settings shortcut. */
function cc_settings()
{
    return cc_data('settings');
}

/** Business details shortcut (name, phone, address...). */
function cc_biz($key)
{
    $s = cc_settings();
    return isset($s['business'][$key]) ? $s['business'][$key] : '';
}

/** Escape for HTML text and attributes. Use on every value you print. */
function e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Is this run the static HTML export (tools/export-static.php)? */
function cc_is_static()
{
    return defined('CC_STATIC') && CC_STATIC;
}

/**
 * Link to a page. "about" becomes about.php on the PHP site and about.html in
 * the static export, so the same templates serve both.
 *
 * @param string $page  Page slug ("index", "about", "book"...).
 * @param array  $query Optional query string values.
 */
function cc_url($page, $query = array())
{
    $ext  = cc_is_static() ? '.html' : '.php';
    $file = ($page === 'index') ? ('index' . $ext) : ($page . $ext);
    if (!cc_is_static() && $page === 'index') {
        $file = './';
    }
    return $file . ($query ? '?' . http_build_query($query) : '');
}

/** Absolute URL for canonical / Open Graph tags. */
function cc_abs_url($page)
{
    $base = defined('CC_BASE_URL') ? CC_BASE_URL : cc_settings()['base_url'];
    $base = rtrim($base, '/') . '/';
    if ($page === 'index') {
        return $base;
    }
    return $base . $page . (cc_is_static() ? '.html' : '.php');
}

/** Format pesos: 1500 -> "₱1,500". */
function cc_peso($amount)
{
    return '₱' . number_format((float) $amount, 0, '.', ',');
}

/** 90 -> "1 hr 30 min". */
function cc_duration($minutes)
{
    $h = intdiv((int) $minutes, 60);
    $m = (int) $minutes % 60;
    $out = array();
    if ($h) {
        $out[] = $h . ' hr';
    }
    if ($m) {
        $out[] = $m . ' min';
    }
    return implode(' ', $out);
}

/** "13:30" -> 810 (minutes after midnight). */
function cc_to_min($hhmm)
{
    $p = explode(':', $hhmm);
    return ((int) $p[0]) * 60 + (int) (isset($p[1]) ? $p[1] : 0);
}

/** 810 -> "1:30 PM". */
function cc_min_label($min)
{
    $h = intdiv($min, 60);
    $m = $min % 60;
    $ampm = $h >= 12 ? 'PM' : 'AM';
    $h12 = $h % 12;
    if ($h12 === 0) {
        $h12 = 12;
    }
    return $h12 . ':' . str_pad((string) $m, 2, '0', STR_PAD_LEFT) . ' ' . $ampm;
}

/** Start the PHP session once (not used by the static export). */
function cc_session()
{
    if (cc_is_static() || session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    session_set_cookie_params(array(
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ));
    session_start();
}

/** Current CSRF token (created on first use). */
function cc_csrf_token()
{
    if (cc_is_static()) {
        return 'static-demo';
    }
    cc_session();
    if (empty($_SESSION['cc_csrf'])) {
        $_SESSION['cc_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['cc_csrf'];
}

/** Hidden input with the CSRF token. */
function cc_csrf_field()
{
    return '<input type="hidden" name="csrf" value="' . e(cc_csrf_token()) . '">';
}

/** Check a submitted CSRF token in constant time. */
function cc_csrf_check($token)
{
    cc_session();
    return is_string($token) && !empty($_SESSION['cc_csrf']) && hash_equals($_SESSION['cc_csrf'], $token);
}

/** Read a POST field as a trimmed single-line string with a length cap. */
function cc_post($key, $max = 200)
{
    $v = isset($_POST[$key]) && is_string($_POST[$key]) ? $_POST[$key] : '';
    $v = trim(preg_replace('/[\x00-\x1F\x7F]+/u', ' ', $v));
    return function_exists('mb_substr') ? mb_substr($v, 0, $max) : substr($v, 0, $max);
}

/** Read a POST field that may contain line breaks (messages, notes). */
function cc_post_text($key, $max = 1000)
{
    $v = isset($_POST[$key]) && is_string($_POST[$key]) ? $_POST[$key] : '';
    $v = str_replace("\r\n", "\n", $v);
    $v = trim(preg_replace('/[\x00-\x09\x0B-\x1F\x7F]+/u', ' ', $v));
    return function_exists('mb_substr') ? mb_substr($v, 0, $max) : substr($v, 0, $max);
}

/** Philippine mobile number check: 09XXXXXXXXX or +639XXXXXXXXX (spaces/dashes allowed). */
function cc_valid_mobile($v)
{
    $digits = preg_replace('/[\s\-()]/', '', $v);
    return (bool) preg_match('/^(09\d{9}|\+639\d{9})$/', $digits);
}

/** First line of every storage file: stops the web server from ever showing the data. */
define('CC_STORE_GUARD', "<?php exit; ?>\n");

/** Path of a storage file. Data is kept in .php files so no server can serve it as text. */
function cc_store_path($name)
{
    return CC_ROOT . '/storage/' . basename($name) . '.php';
}

/** Decode a storage file's contents (strip the PHP guard line). */
function cc_store_decode($raw)
{
    if (strpos($raw, CC_STORE_GUARD) === 0) {
        $raw = substr($raw, strlen(CC_STORE_GUARD));
    }
    $data = json_decode($raw !== '' ? $raw : '[]', true);
    return is_array($data) ? $data : array();
}

/**
 * Read a storage file under an exclusive lock, let $fn change the array, then
 * write it back. Returns whatever $fn returns. This lock is what stops two
 * people from saving the same slot at the same moment.
 */
function cc_with_store($name, callable $fn)
{
    $dir = CC_ROOT . '/storage';
    if (!is_dir($dir)) {
        mkdir($dir, 0775, true);
    }
    $fh = fopen(cc_store_path($name), 'c+');
    if (!$fh) {
        return array('ok' => false, 'errors' => array('form' => 'Bookings cannot be saved right now. Please call us.'));
    }
    flock($fh, LOCK_EX);
    $data = cc_store_decode((string) stream_get_contents($fh));
    $result = $fn($data);
    ftruncate($fh, 0);
    rewind($fh);
    fwrite($fh, CC_STORE_GUARD . json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    fflush($fh);
    flock($fh, LOCK_UN);
    fclose($fh);
    return $result;
}

/** Read a storage file without changing it. */
function cc_read_store($name)
{
    $path = cc_store_path($name);
    return is_readable($path) ? cc_store_decode((string) file_get_contents($path)) : array();
}

/** Send a plain-text notification e-mail when notify_email is set. */
function cc_notify($subject, $body)
{
    $to = cc_settings()['notify_email'];
    if (!$to || !filter_var($to, FILTER_VALIDATE_EMAIL) || !function_exists('mail')) {
        return false;
    }
    $headers = 'From: ' . cc_biz('name') . ' <no-reply@' . preg_replace('/[^a-z0-9.\-]/i', '', isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost') . ">\r\n"
        . "Content-Type: text/plain; charset=UTF-8\r\n";
    return @mail($to, '=?UTF-8?B?' . base64_encode($subject) . '?=', $body, $headers);
}

/**
 * Data the browser needs for the booking engine (no private data).
 * Printed as <script type="application/json" id="cc-data">.
 */
function cc_client_json()
{
    $s = cc_settings();
    $data = array(
        'mode'     => cc_is_static() ? 'static' : 'php',
        'api'      => 'api/slots.php',
        'services' => cc_data('services'),
        'staff'    => array_map(function ($st) {
            return array('id' => $st['id'], 'name' => $st['name'], 'initials' => $st['initials'], 'role' => $st['role'], 'days' => $st['days'], 'services' => $st['services']);
        }, cc_data('staff')),
        'settings' => array(
            'utc_offset_minutes' => $s['utc_offset_minutes'],
            'slot_step' => $s['slot_step'],
            'lead_minutes' => $s['lead_minutes'],
            'days_ahead' => $s['days_ahead'],
            'hours' => $s['hours'],
            'blocked_dates' => $s['blocked_dates'],
            'vehicles' => $s['vehicles'],
            'demo_busy' => !empty($s['demo_busy']),
        ),
        'business' => array(
            'name' => cc_biz('name'),
            'phone' => cc_biz('phone'),
            'location' => cc_biz('street') . ', ' . cc_biz('city') . ', ' . cc_biz('province'),
        ),
        'links'    => array(
            'confirmation' => cc_url('confirmation'),
            'book' => cc_url('book'),
        ),
    );
    return '<script type="application/json" id="cc-data">' . json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) . '</script>';
}

/** Data + engine + booking scripts for pages that use the booking engine. */
function cc_booking_scripts()
{
    return cc_client_json()
        . '<script src="assets/js/engine.js?v=' . CC_VERSION . '" defer></script>'
        . '<script src="assets/js/booking.js?v=' . CC_VERSION . '" defer></script>';
}
