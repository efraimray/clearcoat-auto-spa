<?php
/**
 * Clearcoat Auto Spa - download a booking as an .ics calendar file.
 *
 * @package ClearcoatAutoSpa
 */

require __DIR__ . '/inc/bootstrap.php';

$b = cc_find_booking(isset($_GET['ref']) ? (string) $_GET['ref'] : '', isset($_GET['t']) ? (string) $_GET['t'] : '');
if (!$b) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'Booking not found.';
    exit;
}
header('Content-Type: text/calendar; charset=UTF-8');
header('Content-Disposition: attachment; filename="clearcoat-' . preg_replace('/[^A-Z0-9\-]/', '', $b['ref']) . '.ics"');
header('Cache-Control: private, no-store');
echo cc_ics($b);
