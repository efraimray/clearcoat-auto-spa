<?php
/**
 * Engine tests (PHP side). Run: php tools/test-engine.php
 * With --json it prints slot results for tools/test-engine.js to compare.
 *
 * @package ClearcoatAutoSpa
 */

if (PHP_SAPI !== 'cli') {
    exit;
}
define('CC_ROOT', dirname(__DIR__));
require CC_ROOT . '/inc/bootstrap.php';

// Fixed "now": Friday 2026-10-30 10:05 in Manila (02:05 UTC).
$now = gmmktime(2, 5, 0, 10, 30, 2026);
$cases = array(
    array('wash-vacuum', 'any', '2026-10-30'),   // today, after lead time
    array('full-detail', 'any', '2026-10-31'),   // 4-hour job, Saturday
    array('interior-deep', 'dexter', '2026-11-03'), // Dexter doesn't do interiors
    array('moto-wash', 'dexter', '2026-11-03'),  // Tuesday: Dexter's day off
    array('express-wash', 'ana', '2026-11-08'),  // Sunday short hours
    array('express-wash', 'any', '2026-11-01'),  // holiday
    array('engine-bay', 'rico', '2026-10-29'),   // past
    array('wax-polish', 'any', '2026-12-15'),    // beyond 21 days
    array('glass-care', 'rico', '2026-11-04'),
    array('wash-vacuum', 'any', '2026-11-07'),
);
$out = array();
foreach ($cases as $c) {
    $r = cc_slots($c[0], $c[1], $c[2], array(), $now);
    $out[] = array('case' => $c, 'ok' => $r['ok'], 'reason' => $r['reason'], 'slots' => $r['slots']);
}
if (in_array('--json', $argv, true)) {
    echo json_encode(array('now' => $now, 'results' => $out));
    exit;
}

$pass = 0;
$fail = 0;
function check($label, $cond)
{
    global $pass, $fail;
    echo ($cond ? '  PASS ' : '  FAIL ') . $label . "\n";
    $cond ? $pass++ : $fail++;
}
foreach ($out as $r) {
    echo '  ' . implode(' / ', $r['case']) . ': ' . count($r['slots']) . ' slots' . ($r['reason'] ? ' (' . $r['reason'] . ')' : '') . "\n";
}
check('today starts after 10:05 + 60 min lead', $out[0]['slots'][0]['start'] >= 11 * 60 + 5);
check('4-hour job never ends after 18:00', end($out[1]['slots'])['start'] + 240 <= 18 * 60);
check('Dexter cannot do interiors', !$out[2]['ok'] && strpos($out[2]['reason'], 'No detailer') === 0);
check('Dexter off on Tuesday', !$out[3]['ok']);
check('Sunday closes 14:00', end($out[4]['slots'])['start'] + 30 <= 14 * 60);
check('All Saints Day is closed', $out[5]['reason'] === "Closed: All Saints' Day.");
check('past date refused', $out[6]['reason'] === 'That date has passed.');
check('beyond 21 days refused', strpos($out[7]['reason'], 'Bookings open') === 0);

// Double-booking: book the same slot twice with a specific detailer.
$store = cc_store_path('bookings');
$backup = is_file($store) ? file_get_contents($store) : null;
@unlink($store);
$base = array('service' => 'glass-care', 'vehicle' => 'suv', 'staff' => 'rico', 'date' => '2026-11-04', 'name' => 'Test Customer', 'mobile' => '0917 123 4567', 'email' => '', 'plate' => 'abc 1234', 'notes' => '', 'consent' => 1);
$first = $out[8]['slots'][0]['start'];
$a = cc_create_booking($base + array('start' => (string) $first), $now);
$b = cc_create_booking($base + array('start' => (string) $first), $now);
check('first booking saved', $a['ok'] && $a['booking']['price'] === 950 && $a['booking']['plate'] === 'ABC 1234');
check('same slot refused the second time', !$b['ok'] && isset($b['errors']['start']));
$c = cc_create_booking($base + array('start' => (string) ($first + 30)), $now);
check('overlapping slot (+30 min of a 60-min job) refused', !$c['ok']);
$after = cc_slots('glass-care', 'rico', '2026-11-04', cc_read_store('bookings'), $now);
check('slot no longer offered', !in_array($first, array_column($after['slots'], 'start'), true));
$d = cc_create_booking(array('service' => 'moto-wash', 'vehicle' => 'sedan') + $base + array('start' => '600'), $now);
check('wrong vehicle for service refused', !$d['ok'] && isset($d['errors']['vehicle']));
$e = cc_create_booking(array('mobile' => '12345', 'consent' => 0) + $base + array('start' => '600'), $now);
check('bad mobile + missing consent refused', isset($e['errors']['mobile']) && isset($e['errors']['consent']));
$found = cc_find_booking($a['booking']['ref'], $a['booking']['token']);
check('booking found with ref + token', $found !== null);
check('booking hidden with wrong token', cc_find_booking($a['booking']['ref'], 'nope') === null);
$ics = cc_ics($a['booking']);
check('ics has UTC start (Manila minus 8h)', strpos($ics, 'DTSTART:20261104T' . sprintf('%02d%02d', intdiv($first, 60) - 8, $first % 60) . '00Z') !== false);
check('ics uses CRLF line endings', substr_count($ics, "\r\n") > 10);
check('storage file starts with the PHP exit guard', strpos((string) file_get_contents($store), '<?php exit; ?>') === 0);
check('XSS in name escaped by e()', e('<script>') === '&lt;script&gt;');
@unlink($store);
if ($backup !== null) {
    file_put_contents($store, $backup);
}
echo "\n$pass passed, $fail failed\n";
exit($fail ? 1 : 0);
