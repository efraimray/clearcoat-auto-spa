<?php
/**
 * Clearcoat Auto Spa - bootstrap. Every page includes this first.
 *
 * @package ClearcoatAutoSpa
 */

if (!defined('CC_ROOT')) {
    define('CC_ROOT', dirname(__DIR__));
}
defined('CC_VERSION') || define('CC_VERSION', '1.0.0');

require_once CC_ROOT . '/inc/functions.php';
require_once CC_ROOT . '/inc/booking.php';
require_once CC_ROOT . '/inc/icons.php';

date_default_timezone_set(cc_settings()['timezone']);

if (!cc_is_static() && !headers_sent()) {
    // Start the session before any output so the CSRF cookie can be sent.
    if (PHP_SAPI !== 'cli' && basename($_SERVER['SCRIPT_NAME']) !== 'slots.php') {
        cc_session();
    }
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('X-Frame-Options: SAMEORIGIN');
}
