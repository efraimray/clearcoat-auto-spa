<?php
/**
 * Clearcoat Auto Spa - JSON API: open start times for a service, detailer and date.
 *
 * GET api/slots.php?service=wash-vacuum&staff=any&date=2026-11-07
 * Returns {"ok":true,"reason":"","slots":[{"start":480,"label":"8:00 AM","staff":["rico"]}]}
 * Only free times are returned; no customer data ever leaves the server.
 *
 * @package ClearcoatAutoSpa
 */

define('CC_ROOT', dirname(__DIR__));
require CC_ROOT . '/inc/bootstrap.php';

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store');

$service = isset($_GET['service']) ? preg_replace('/[^a-z0-9\-]/', '', (string) $_GET['service']) : '';
$staff = isset($_GET['staff']) ? preg_replace('/[^a-z0-9\-]/', '', (string) $_GET['staff']) : 'any';
$date = isset($_GET['date']) ? (string) $_GET['date'] : '';
if ($staff === '') {
    $staff = 'any';
}
echo json_encode(cc_slots($service, $staff, $date, cc_read_store('bookings')), JSON_UNESCAPED_UNICODE);
