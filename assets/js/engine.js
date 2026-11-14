/*!
 * Clearcoat Auto Spa - engine.js
 * The booking rules in JavaScript. A line-by-line twin of inc/booking.php so
 * the static demo behaves exactly like the PHP site (tools/test-engine.js
 * compares both). Works in the browser (window.CCEngine) and in Node.
 */
(function (factory) {
  'use strict';
  if (typeof module === 'object' && module.exports) { module.exports = factory(); }
  else { window.CCEngine = factory(); }
}(function () {
  'use strict';

  function toMin(hhmm) { var p = String(hhmm).split(':'); return (+p[0]) * 60 + (+(p[1] || 0)); }

  function minLabel(min) {
    var h = Math.floor(min / 60), m = min % 60, h12 = h % 12 || 12;
    return h12 + ':' + (m < 10 ? '0' : '') + m + ' ' + (h >= 12 ? 'PM' : 'AM');
  }

  function peso(n) { return '₱' + String(Math.round(n)).replace(/\B(?=(\d{3})+(?!\d))/g, ','); }

  function duration(min) {
    var h = Math.floor(min / 60), m = min % 60, out = [];
    if (h) { out.push(h + ' hr'); }
    if (m) { out.push(m + ' min'); }
    return out.join(' ');
  }

  function pad(n) { return (n < 10 ? '0' : '') + n; }
  function ymd(d) { return d.getUTCFullYear() + '-' + pad(d.getUTCMonth() + 1) + '-' + pad(d.getUTCDate()); }
  function parseYmd(s) { var p = s.split('-'); return new Date(Date.UTC(+p[0], +p[1] - 1, +p[2])); }
  function validDate(s) {
    if (typeof s !== 'string' || !/^\d{4}-\d{2}-\d{2}$/.test(s)) { return false; }
    return ymd(parseYmd(s)) === s;
  }
  function weekday(s) { return parseYmd(s).getUTCDay(); }
  function daysBetween(a, b) { return Math.round((parseYmd(b) - parseYmd(a)) / 86400000); }
  function addDays(s, n) { var d = parseYmd(s); d.setUTCDate(d.getUTCDate() + n); return ymd(d); }

  /** 32-bit FNV-1a, same as cc_fnv() in PHP. */
  function fnv(str) {
    var h = 0x811c9dc5;
    for (var i = 0; i < str.length; i++) {
      h ^= str.charCodeAt(i);
      h = Math.imul(h, 0x01000193) >>> 0;
    }
    return h >>> 0;
  }

  function create(data) {
    var S = data.settings;
    var services = data.services, staff = data.staff;

    function service(id) { for (var i = 0; i < services.length; i++) { if (services[i].id === id) { return services[i]; } } return null; }
    function member(id) { for (var i = 0; i < staff.length; i++) { if (staff[i].id === id) { return staff[i]; } } return null; }

    /** "Now" in the shop's time zone: { date, min }. */
    function nowLocal(ts) {
      var t = new Date((ts == null ? Date.now() : ts * 1000) + S.utc_offset_minutes * 60000);
      return { date: ymd(t), min: t.getUTCHours() * 60 + t.getUTCMinutes() };
    }

    /** [open, close] in minutes, or a string reason. */
    function dayHours(date, today) {
      if (!validDate(date)) { return 'Invalid date.'; }
      var diff = daysBetween(today, date);
      if (diff < 0) { return 'That date has passed.'; }
      if (diff > S.days_ahead) { return 'Bookings open ' + S.days_ahead + ' days ahead.'; }
      if (S.blocked_dates[date]) { return 'Closed: ' + S.blocked_dates[date] + '.'; }
      var h = S.hours[String(weekday(date))];
      if (!h) { return 'Closed on this day.'; }
      return [toMin(h[0]), toMin(h[1])];
    }

    function demoBusy(date, staffId, open, close) {
      if (!S.demo_busy) { return []; }
      var h = fnv(date + '|' + staffId);
      var count = h % 3, lengths = [60, 90, 120];
      var span = Math.floor((close - open) / 30), out = [];
      for (var i = 0; i < count; i++) {
        var len = lengths[(h >>> (i * 3)) % 3];
        var pos = (h >>> (8 + i * 7)) % Math.max(1, span);
        var start = open + pos * 30, end = Math.min(close, start + len);
        if (end > start) { out.push([start, end]); }
      }
      return out;
    }

    function busy(date, staffId, bookings, open, close) {
      var list = demoBusy(date, staffId, open, close);
      bookings.forEach(function (b) {
        if (b.date === date && b.staff === staffId && b.status !== 'cancelled') { list.push([+b.start, +b.start + (+b.minutes)]); }
      });
      return list;
    }

    function candidates(serviceId, staffId, wd) {
      return staff.filter(function (st) {
        return (staffId === 'any' || st.id === staffId) && st.services.indexOf(serviceId) > -1 && st.days.indexOf(wd) > -1;
      }).map(function (st) { return st.id; });
    }

    /** Open start times, same shape as cc_slots() in PHP. */
    function slots(serviceId, staffId, date, bookings, ts) {
      var svc = service(serviceId);
      if (!svc) { return { ok: false, reason: 'Choose a service first.', slots: [] }; }
      var now = nowLocal(ts);
      var hours = dayHours(date, now.date);
      if (typeof hours === 'string') { return { ok: false, reason: hours, slots: [] }; }
      var open = hours[0], close = hours[1];
      var ids = candidates(serviceId, staffId, weekday(date));
      if (!ids.length) { return { ok: false, reason: 'No detailer for this service on that day. Try another day or "Any detailer".', slots: [] }; }
      var dur = svc.minutes, step = S.slot_step;
      var earliest = date === now.date ? now.min + S.lead_minutes : 0;
      var busyMap = {};
      ids.forEach(function (id) { busyMap[id] = busy(date, id, bookings || [], open, close); });
      var out = [];
      for (var t = open; t + dur <= close; t += step) {
        if (t < earliest) { continue; }
        var free = ids.filter(function (id) {
          return !busyMap[id].some(function (iv) { return t < iv[1] && t + dur > iv[0]; });
        });
        if (free.length) { out.push({ start: t, label: minLabel(t), staff: free }); }
      }
      return { ok: out.length > 0, reason: out.length ? '' : 'Fully booked. Please pick another day.', slots: out };
    }

    /** iCalendar text for a booking (UTC times). */
    function ics(b) {
      var svc = service(b.service), st = member(b.staff);
      var startMs = parseYmd(b.date).getTime() + (b.start - S.utc_offset_minutes) * 60000;
      var endMs = startMs + b.minutes * 60000;
      function stamp(ms) { return new Date(ms).toISOString().replace(/[-:]/g, '').replace(/\.\d{3}/, ''); }
      function esc(s) { return String(s).replace(/\\/g, '\\\\').replace(/;/g, '\\;').replace(/,/g, '\\,').replace(/\n/g, '\\n'); }
      return [
        'BEGIN:VCALENDAR', 'VERSION:2.0', 'PRODID:-//Clearcoat Auto Spa//Booking//EN', 'CALSCALE:GREGORIAN', 'METHOD:PUBLISH',
        'BEGIN:VEVENT',
        'UID:' + b.ref + '@clearcoat-auto-spa',
        'DTSTAMP:' + stamp(Date.now()),
        'DTSTART:' + stamp(startMs),
        'DTEND:' + stamp(endMs),
        'SUMMARY:' + esc(svc.name + ' - ' + data.business.name),
        'LOCATION:' + esc(data.business.location),
        'DESCRIPTION:' + esc('Booking ' + b.ref + ' with ' + st.name + '. Price ' + peso(b.price) + ' (pay at the shop). Call ' + data.business.phone + ' to change.'),
        'BEGIN:VALARM', 'TRIGGER:-PT1H', 'ACTION:DISPLAY', 'DESCRIPTION:Car wash appointment in 1 hour', 'END:VALARM',
        'END:VEVENT', 'END:VCALENDAR'
      ].join('\r\n') + '\r\n';
    }

    return {
      service: service, member: member, nowLocal: nowLocal, dayHours: dayHours,
      demoBusy: demoBusy, candidates: candidates, slots: slots, ics: ics
    };
  }

  return {
    create: create, toMin: toMin, minLabel: minLabel, peso: peso, duration: duration,
    fnv: fnv, weekday: weekday, addDays: addDays, validDate: validDate, parseYmd: parseYmd
  };
}));
