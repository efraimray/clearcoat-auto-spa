<?php
/**
 * Clearcoat Auto Spa - booking engine (server side, authoritative).
 *
 * The same rules live in assets/js/booking.js so the static demo behaves the
 * same way. If you change a rule here, change it there too and run
 * tools/test-engine.php.
 *
 * Rules:
 *  - a day is bookable when it has business hours, is not in blocked_dates,
 *    is not in the past and is at most days_ahead days away;
 *  - a start time is offered every slot_step minutes; the service must end by
 *    closing time; today's slots start at least lead_minutes from now;
 *  - a detailer is free when none of their bookings overlaps the new one;
 *  - "any" detailer means: the first qualified detailer who is free.
 *
 * @package ClearcoatAutoSpa
 */

defined('CC_ROOT') || exit;

/** Find a service by id, or null. */
function cc_service($id)
{
    foreach (cc_data('services') as $s) {
        if ($s['id'] === $id) {
            return $s;
        }
    }
    return null;
}

/** Find a staff member by id, or null. */
function cc_staff($id)
{
    foreach (cc_data('staff') as $s) {
        if ($s['id'] === $id) {
            return $s;
        }
    }
    return null;
}

/** "Now" in the shop's time zone as array(date 'Y-m-d', minutes after midnight). */
function cc_now_local($timestamp = null)
{
    $ts  = $timestamp === null ? time() : (int) $timestamp;
    $off = (int) cc_settings()['utc_offset_minutes'];
    $t   = $ts + $off * 60;
    return array(gmdate('Y-m-d', $t), ((int) gmdate('G', $t)) * 60 + (int) gmdate('i', $t));
}

/** Day of week (0 = Sunday) for a Y-m-d date, time-zone independent. */
function cc_weekday($date)
{
    $p = explode('-', $date);
    return (int) gmdate('w', gmmktime(0, 0, 0, (int) $p[1], (int) $p[2], (int) $p[0]));
}

/** Whole days from $from to $to (both Y-m-d). */
function cc_days_between($from, $to)
{
    $a = explode('-', $from);
    $b = explode('-', $to);
    $ta = gmmktime(0, 0, 0, (int) $a[1], (int) $a[2], (int) $a[0]);
    $tb = gmmktime(0, 0, 0, (int) $b[1], (int) $b[2], (int) $b[0]);
    return (int) round(($tb - $ta) / 86400);
}

/** Valid Y-m-d string? */
function cc_valid_date($date)
{
    if (!is_string($date) || !preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $date, $m)) {
        return false;
    }
    return checkdate((int) $m[2], (int) $m[3], (int) $m[1]);
}

/**
 * Opening hours for a date: array(open, close) in minutes, or a string reason
 * when the day cannot be booked.
 */
function cc_day_hours($date, $today)
{
    $s = cc_settings();
    if (!cc_valid_date($date)) {
        return 'Invalid date.';
    }
    $diff = cc_days_between($today, $date);
    if ($diff < 0) {
        return 'That date has passed.';
    }
    if ($diff > (int) $s['days_ahead']) {
        return 'Bookings open ' . (int) $s['days_ahead'] . ' days ahead.';
    }
    if (isset($s['blocked_dates'][$date])) {
        return 'Closed: ' . $s['blocked_dates'][$date] . '.';
    }
    $wd = (string) cc_weekday($date);
    if (empty($s['hours'][$wd])) {
        return 'Closed on this day.';
    }
    return array(cc_to_min($s['hours'][$wd][0]), cc_to_min($s['hours'][$wd][1]));
}

/** 32-bit FNV-1a hash (identical in booking.js) used for demo "busy" blocks. */
function cc_fnv($str)
{
    $h = 0x811c9dc5;
    $len = strlen($str);
    for ($i = 0; $i < $len; $i++) {
        $h ^= ord($str[$i]);
        $h = ($h * 0x01000193) & 0xffffffff;
    }
    return $h;
}

/**
 * Demo-only busy blocks so the calendar never looks empty on a fresh install.
 * Deterministic per date + detailer. Turn off with "demo_busy": false.
 *
 * @return array List of array(start, end) in minutes.
 */
function cc_demo_busy($date, $staff_id, $open, $close)
{
    if (empty(cc_settings()['demo_busy'])) {
        return array();
    }
    $h = cc_fnv($date . '|' . $staff_id);
    $count = $h % 3; // 0, 1 or 2 blocks.
    $lengths = array(60, 90, 120);
    $span = (int) (($close - $open) / 30);
    $out = array();
    for ($i = 0; $i < $count; $i++) {
        $len = $lengths[($h >> ($i * 3)) % 3];
        $pos = ($h >> (8 + $i * 7)) % max(1, $span);
        $start = $open + $pos * 30;
        $end = min($close, $start + $len);
        if ($end > $start) {
            $out[] = array($start, $end);
        }
    }
    return $out;
}

/** Busy intervals for one detailer on one date (saved bookings + demo blocks). */
function cc_busy($date, $staff_id, $bookings, $open, $close)
{
    $busy = cc_demo_busy($date, $staff_id, $open, $close);
    foreach ($bookings as $b) {
        if ($b['date'] === $date && $b['staff'] === $staff_id && (!isset($b['status']) || $b['status'] !== 'cancelled')) {
            $busy[] = array((int) $b['start'], (int) $b['start'] + (int) $b['minutes']);
        }
    }
    return $busy;
}

/** Qualified detailers for a service on a weekday, optionally only one id. */
function cc_candidates($service_id, $staff_id, $weekday)
{
    $out = array();
    foreach (cc_data('staff') as $st) {
        if ($staff_id !== 'any' && $st['id'] !== $staff_id) {
            continue;
        }
        if (in_array($service_id, $st['services'], true) && in_array($weekday, $st['days'], true)) {
            $out[] = $st['id'];
        }
    }
    return $out;
}

/**
 * Available start times.
 *
 * @param string     $service_id Service id.
 * @param string     $staff_id   Staff id or "any".
 * @param string     $date       Y-m-d.
 * @param array      $bookings   Saved bookings.
 * @param int|null   $timestamp  Unix time for "now" (tests pass a fixed value).
 * @return array array('ok'=>bool, 'reason'=>string, 'slots'=>[ ['start'=>int,'label'=>str,'staff'=>[ids]] ])
 */
function cc_slots($service_id, $staff_id, $date, $bookings, $timestamp = null)
{
    $service = cc_service($service_id);
    if (!$service) {
        return array('ok' => false, 'reason' => 'Choose a service first.', 'slots' => array());
    }
    list($today, $now_min) = cc_now_local($timestamp);
    $hours = cc_day_hours($date, $today);
    if (!is_array($hours)) {
        return array('ok' => false, 'reason' => $hours, 'slots' => array());
    }
    list($open, $close) = $hours;
    $staff = cc_candidates($service_id, $staff_id, cc_weekday($date));
    if (!$staff) {
        return array('ok' => false, 'reason' => 'No detailer for this service on that day. Try another day or "Any detailer".', 'slots' => array());
    }
    $s = cc_settings();
    $step = (int) $s['slot_step'];
    $dur = (int) $service['minutes'];
    $earliest = ($date === $today) ? $now_min + (int) $s['lead_minutes'] : 0;

    $busy = array();
    foreach ($staff as $id) {
        $busy[$id] = cc_busy($date, $id, $bookings, $open, $close);
    }

    $slots = array();
    for ($t = $open; $t + $dur <= $close; $t += $step) {
        if ($t < $earliest) {
            continue;
        }
        $free = array();
        foreach ($staff as $id) {
            $clash = false;
            foreach ($busy[$id] as $iv) {
                if ($t < $iv[1] && $t + $dur > $iv[0]) {
                    $clash = true;
                    break;
                }
            }
            if (!$clash) {
                $free[] = $id;
            }
        }
        if ($free) {
            $slots[] = array('start' => $t, 'label' => cc_min_label($t), 'staff' => $free);
        }
    }
    $reason = $slots ? '' : 'Fully booked. Please pick another day.';
    return array('ok' => (bool) $slots, 'reason' => $reason, 'slots' => $slots);
}

/** Readable booking reference, e.g. CC-1107-K4TP. */
function cc_new_ref($date)
{
    $alphabet = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';
    $code = '';
    for ($i = 0; $i < 4; $i++) {
        $code .= $alphabet[random_int(0, strlen($alphabet) - 1)];
    }
    return 'CC-' . substr(str_replace('-', '', $date), 4) . '-' . $code;
}

/**
 * Validate and save a booking. Re-checks the slot INSIDE the storage lock, so
 * two people who pick the same time at once cannot both get it.
 *
 * @param array    $in        Raw input: service, vehicle, staff, date, start, name, mobile, email, plate, notes, consent.
 * @param int|null $timestamp Fixed "now" for tests.
 * @return array array('ok'=>true,'booking'=>[...]) or array('ok'=>false,'errors'=>[field=>message])
 */
function cc_create_booking($in, $timestamp = null)
{
    $errors = array();
    $service = cc_service(isset($in['service']) ? $in['service'] : '');
    $vehicle = isset($in['vehicle']) ? $in['vehicle'] : '';
    if (!$service) {
        $errors['service'] = 'Choose a service.';
    } elseif (!isset($service['prices'][$vehicle])) {
        $errors['vehicle'] = 'This service is not available for that vehicle type.';
    }
    $staff_id = isset($in['staff']) ? $in['staff'] : 'any';
    if ($staff_id !== 'any' && !cc_staff($staff_id)) {
        $errors['staff'] = 'Choose a detailer.';
    }
    $date = isset($in['date']) ? $in['date'] : '';
    if (!cc_valid_date($date)) {
        $errors['date'] = 'Choose a date.';
    }
    $start = isset($in['start']) && ctype_digit((string) $in['start']) ? (int) $in['start'] : -1;
    if ($start < 0) {
        $errors['start'] = 'Choose a time.';
    }
    $name = isset($in['name']) ? $in['name'] : '';
    if (strlen($name) < 2) {
        $errors['name'] = 'Enter your name.';
    }
    $mobile = isset($in['mobile']) ? $in['mobile'] : '';
    if (!cc_valid_mobile($mobile)) {
        $errors['mobile'] = 'Enter a mobile number like 0917 123 4567.';
    }
    $email = isset($in['email']) ? $in['email'] : '';
    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Check the e-mail address, or leave it blank.';
    }
    if (empty($in['consent'])) {
        $errors['consent'] = 'Please agree to the privacy notice so we can contact you.';
    }
    if ($errors) {
        return array('ok' => false, 'errors' => $errors);
    }

    return cc_with_store('bookings', function (&$bookings) use ($service, $vehicle, $staff_id, $date, $start, $name, $mobile, $email, $in, $timestamp) {
        $check = cc_slots($service['id'], $staff_id, $date, $bookings, $timestamp);
        $assigned = null;
        foreach ($check['slots'] as $slot) {
            if ($slot['start'] === $start) {
                $assigned = $slot['staff'][0];
                break;
            }
        }
        if ($assigned === null) {
            return array('ok' => false, 'errors' => array('start' => 'Sorry, that time was just taken. Please pick another slot.'));
        }
        $booking = array(
            'ref'      => cc_new_ref($date),
            'token'    => bin2hex(random_bytes(8)),
            'service'  => $service['id'],
            'vehicle'  => $vehicle,
            'price'    => (int) $service['prices'][$vehicle],
            'minutes'  => (int) $service['minutes'],
            'staff'    => $assigned,
            'date'     => $date,
            'start'    => $start,
            'name'     => $name,
            'mobile'   => $mobile,
            'email'    => $email,
            'plate'    => isset($in['plate']) ? strtoupper($in['plate']) : '',
            'notes'    => isset($in['notes']) ? $in['notes'] : '',
            'status'   => 'confirmed',
            'created'  => gmdate('c', $timestamp === null ? time() : $timestamp),
        );
        $bookings[] = $booking;
        return array('ok' => true, 'booking' => $booking);
    });
}

/** Find a booking by reference + token (the token keeps other people's bookings private). */
function cc_find_booking($ref, $token)
{
    foreach (cc_read_store('bookings') as $b) {
        if (hash_equals($b['ref'], (string) $ref) && hash_equals($b['token'], (string) $token)) {
            return $b;
        }
    }
    return null;
}

/** Build an iCalendar (.ics) file for a booking. */
function cc_ics($b)
{
    $service = cc_service($b['service']);
    $staff = cc_staff($b['staff']);
    $off = (int) cc_settings()['utc_offset_minutes'];
    $p = explode('-', $b['date']);
    $startTs = gmmktime(0, 0, 0, (int) $p[1], (int) $p[2], (int) $p[0]) + ($b['start'] - $off) * 60;
    $endTs = $startTs + $b['minutes'] * 60;
    $esc = function ($s) {
        return str_replace(array('\\', ';', ',', "\n"), array('\\\\', '\\;', '\\,', '\\n'), $s);
    };
    $loc = cc_biz('street') . ', ' . cc_biz('city') . ', ' . cc_biz('province');
    $lines = array(
        'BEGIN:VCALENDAR',
        'VERSION:2.0',
        'PRODID:-//Clearcoat Auto Spa//Booking//EN',
        'CALSCALE:GREGORIAN',
        'METHOD:PUBLISH',
        'BEGIN:VEVENT',
        'UID:' . $b['ref'] . '@clearcoat-auto-spa',
        'DTSTAMP:' . gmdate('Ymd\THis\Z'),
        'DTSTART:' . gmdate('Ymd\THis\Z', $startTs),
        'DTEND:' . gmdate('Ymd\THis\Z', $endTs),
        'SUMMARY:' . $esc($service['name'] . ' - ' . cc_biz('name')),
        'LOCATION:' . $esc($loc),
        'DESCRIPTION:' . $esc('Booking ' . $b['ref'] . ' with ' . $staff['name'] . '. Price ' . cc_peso($b['price']) . ' (pay at the shop). Call ' . cc_biz('phone') . ' to change.'),
        'BEGIN:VALARM',
        'TRIGGER:-PT1H',
        'ACTION:DISPLAY',
        'DESCRIPTION:Car wash appointment in 1 hour',
        'END:VALARM',
        'END:VEVENT',
        'END:VCALENDAR',
    );
    return implode("\r\n", $lines) . "\r\n";
}
