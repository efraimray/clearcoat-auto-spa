/*!
 * Clearcoat Auto Spa - booking.js
 * Drives the booking wizard, the home page "next free slot" line and, in the
 * static demo, the confirmation page. Needs engine.js loaded first.
 *
 * PHP site:    open times come from api/slots.php and the form posts to book.php.
 * Static demo: open times come from engine.js and bookings are kept in
 *              localStorage (wrapped in try/catch), so the flow can be tried
 *              on GitHub Pages without a server.
 */
(function () {
  'use strict';

  var dataEl = document.getElementById('cc-data');
  if (!dataEl || !window.CCEngine) { return; }
  var D = JSON.parse(dataEl.textContent);
  var E = window.CCEngine;
  var engine = E.create(D);
  var STATIC = D.mode === 'static';
  var KEY = 'cc-demo-bookings';

  /* ---------- Demo storage ---------- */
  var memory = [];
  function loadBookings() {
    try { var raw = window.localStorage.getItem(KEY); return raw ? JSON.parse(raw) : memory; } catch (e) { return memory; }
  }
  function saveBookings(list) {
    memory = list;
    try { window.localStorage.setItem(KEY, JSON.stringify(list)); } catch (e) { /* in-memory only */ }
  }

  /** Open slots: API on the PHP site, local engine in the static demo. */
  function getSlots(serviceId, staffId, date) {
    if (STATIC) { return Promise.resolve(engine.slots(serviceId, staffId, date, loadBookings())); }
    var url = D.api + '?service=' + encodeURIComponent(serviceId) + '&staff=' + encodeURIComponent(staffId) + '&date=' + encodeURIComponent(date);
    return fetch(url, { headers: { Accept: 'application/json' } })
      .then(function (r) { if (!r.ok) { throw new Error('HTTP ' + r.status); } return r.json(); })
      .catch(function () { return { ok: false, reason: 'Could not load times. Please call ' + D.business.phone + '.', slots: [] }; });
  }

  var MONTHS = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
  var DAYS = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
  var LONG_DAYS = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
  var LONG_MONTHS = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
  function longDate(s) { var d = E.parseYmd(s); return LONG_DAYS[d.getUTCDay()] + ', ' + d.getUTCDate() + ' ' + LONG_MONTHS[d.getUTCMonth()] + ' ' + d.getUTCFullYear(); }
  function shortDate(s, today) {
    if (s === today) { return 'Today'; }
    if (s === E.addDays(today, 1)) { return 'Tomorrow'; }
    var d = E.parseYmd(s); return DAYS[d.getUTCDay()] + ' ' + d.getUTCDate() + ' ' + MONTHS[d.getUTCMonth()];
  }

  /* =====================================================================
   * Home page: "Next free Wash & Vacuum slot"
   * ===================================================================== */
  var next = document.querySelector('[data-next-slot-value]');
  if (next) {
    var today = engine.nowLocal().date;
    var tries = 0;
    (function look(date) {
      getSlots('wash-vacuum', 'any', date).then(function (res) {
        if (res.slots && res.slots.length) {
          var who = engine.member(res.slots[0].staff[0]);
          next.textContent = shortDate(date, today) + ', ' + res.slots[0].label + (who ? ' with ' + who.name.split(' ')[0] : '');
          return;
        }
        if (++tries < 8) { look(E.addDays(date, 1)); } else { next.textContent = 'Call us for the next opening'; }
      });
    }(today));
  }

  /* =====================================================================
   * Booking wizard
   * ===================================================================== */
  var form = document.querySelector('[data-wizard]');
  if (form) { initWizard(form); }

  function initWizard(form) {
    var steps = form.querySelectorAll('[data-step]');
    var prev = form.querySelector('[data-prev]');
    var nextBtn = form.querySelector('[data-next]');
    var submit = form.querySelector('[data-submit]');
    var dateInput = form.querySelector('[data-date-input]');
    var strip = form.querySelector('[data-date-strip]');
    var grid = form.querySelector('[data-slot-grid]');
    var status = form.querySelector('[data-slot-status]');
    var current = 1;
    var lastSlots = [];
    var today = engine.nowLocal().date;

    function val(name) { var el = form.querySelector('[name="' + name + '"]:checked') || form.querySelector('[name="' + name + '"]:not([type=radio]):not([type=checkbox])'); return el ? el.value : ''; }
    function setRadio(name, value) { var el = form.querySelector('[name="' + name + '"][value="' + value + '"]'); if (el) { el.checked = true; } return !!el; }
    function err(name, msg) {
      var box = form.querySelector('[data-error-for="' + name + '"]');
      var input = form.querySelector('[name="' + name + '"]');
      if (box) { box.textContent = msg || ''; box.hidden = !msg; }
      if (input && input.type !== 'radio' && input.type !== 'hidden') {
        input.setAttribute('aria-invalid', msg ? 'true' : 'false');
        if (box) { box.id = 'err-' + name; input.setAttribute('aria-describedby', box.id); }
      }
      return !msg;
    }

    /* ----- Step 1: vehicle filters services and prices ----- */
    function applyVehicle() {
      var v = val('vehicle');
      form.querySelectorAll('[data-service-card]').forEach(function (card) {
        var ok = card.getAttribute('data-vehicles').split(' ').indexOf(v) > -1;
        card.hidden = !ok;
        var input = card.querySelector('input');
        if (!ok && input.checked) { input.checked = false; }
        var svc = engine.service(card.getAttribute('data-service-card'));
        var priceEl = card.querySelector('[data-price-for]');
        if (ok && priceEl) { priceEl.textContent = E.peso(svc.prices[v]); }
      });
      update();
    }

    /* ----- Step 2: only detailers who do the chosen service ----- */
    function applyService() {
      var sid = val('service');
      form.querySelectorAll('[data-staff-card]').forEach(function (card) {
        var id = card.getAttribute('data-staff-card');
        if (id === 'any') { return; }
        var st = engine.member(id);
        var can = !sid || st.services.indexOf(sid) > -1;
        var input = card.querySelector('input');
        input.disabled = !can;
        card.classList.toggle('is-disabled', !can);
        var note = card.querySelector('[data-staff-note]');
        if (note) { note.textContent = can ? 'Works ' + st.days.slice().sort().map(function (d) { return DAYS[d]; }).join(', ') : 'Does not do this service'; }
        if (!can && input.checked) { setRadio('staff', 'any'); }
      });
      update();
    }

    /* ----- Step 3: date strip and time slots ----- */
    function buildStrip() {
      strip.innerHTML = '';
      for (var i = 0; i <= D.settings.days_ahead; i++) {
        var date = E.addDays(today, i);
        var hours = engine.dayHours(date, today);
        // Today is greyed out once the last possible start (closing minus 30 min) is inside the lead time.
        if (i === 0 && typeof hours !== 'string' && engine.nowLocal().min + D.settings.lead_minutes > hours[1] - D.settings.slot_step) { hours = 'No more times today.'; }
        var d = E.parseYmd(date);
        var b = document.createElement('button');
        b.type = 'button';
        b.className = 'date-pill';
        b.setAttribute('role', 'option');
        b.setAttribute('data-date', date);
        b.setAttribute('aria-selected', date === dateInput.value ? 'true' : 'false');
        b.innerHTML = '<span class="dp-day">' + (i === 0 ? 'Today' : DAYS[d.getUTCDay()]) + '</span><span class="dp-num">' + d.getUTCDate() + '</span><span class="dp-mon">' + MONTHS[d.getUTCMonth()] + '</span>';
        if (typeof hours === 'string') { b.disabled = true; b.title = hours; b.setAttribute('aria-label', longDate(date) + ': ' + hours); }
        else { b.setAttribute('aria-label', longDate(date)); }
        strip.appendChild(b);
      }
    }
    strip.addEventListener('click', function (ev) {
      var b = ev.target.closest('[data-date]');
      if (!b || b.disabled) { return; }
      pickDate(b.getAttribute('data-date'));
    });
    strip.addEventListener('keydown', function (ev) {
      if (ev.key !== 'ArrowRight' && ev.key !== 'ArrowLeft') { return; }
      var pills = Array.prototype.slice.call(strip.querySelectorAll('[data-date]:not([disabled])'));
      var i = pills.indexOf(document.activeElement);
      if (i < 0) { return; }
      var n = pills[Math.min(pills.length - 1, Math.max(0, i + (ev.key === 'ArrowRight' ? 1 : -1)))];
      n.focus(); ev.preventDefault();
    });

    function pickDate(date, keepStart) {
      dateInput.value = date;
      strip.querySelectorAll('[data-date]').forEach(function (b) {
        var on = b.getAttribute('data-date') === date;
        b.setAttribute('aria-selected', on ? 'true' : 'false');
        if (on && b.scrollIntoView) { try { b.scrollIntoView({ block: 'nearest', inline: 'center', behavior: 'smooth' }); } catch (e) { /* old browsers */ } }
      });
      var keep = keepStart ? String(keepStart) : '';
      grid.innerHTML = '';
      status.textContent = 'Loading open times…';
      return getSlots(val('service'), val('staff') || 'any', date).then(function (res) {
        lastSlots = res.slots || [];
        grid.innerHTML = '';
        if (!lastSlots.length) { status.textContent = res.reason || 'No open times.'; update(); return res; }
        status.textContent = lastSlots.length + ' open start time' + (lastSlots.length === 1 ? '' : 's') + ' on ' + longDate(date) + '.';
        lastSlots.forEach(function (s) {
          var id = 'slot-' + s.start;
          var lab = document.createElement('label');
          lab.className = 'slot';
          lab.innerHTML = '<input type="radio" name="start" id="' + id + '" value="' + s.start + '"' + (keep === String(s.start) ? ' checked' : '') + '><span>' + s.label + '</span>';
          grid.appendChild(lab);
        });
        update();
        return res;
      });
    }

    /** First bookable date that has open times (used when entering step 3). */
    function firstOpenDate(i) {
      i = i || 0;
      if (i > D.settings.days_ahead) { return Promise.resolve(null); }
      var date = E.addDays(today, i);
      if (typeof engine.dayHours(date, today) === 'string') { return firstOpenDate(i + 1); }
      return getSlots(val('service'), val('staff') || 'any', date).then(function (r) { return r.slots && r.slots.length ? date : firstOpenDate(i + 1); });
    }

    /* ----- Validation per step ----- */
    var MOBILE = /^(09\d{9}|\+639\d{9})$/;
    var EMAIL = /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/;
    function validate(step) {
      if (step === 1) { return err('service', val('service') ? '' : 'Choose a service to continue.'); }
      if (step === 3) { return err('start', val('start') ? '' : 'Choose a start time to continue.'); }
      if (step === 4) {
        var ok = true;
        var name = form.querySelector('[name=name]').value.trim();
        var mobile = form.querySelector('[name=mobile]').value.replace(/[\s\-()]/g, '');
        var email = form.querySelector('[name=email]').value.trim();
        ok = err('name', name.length >= 2 ? '' : 'Enter your name.') && ok;
        ok = err('mobile', MOBILE.test(mobile) ? '' : 'Enter a mobile number like 0917 123 4567.') && ok;
        ok = err('email', !email || EMAIL.test(email) ? '' : 'Check the e-mail address, or leave it blank.') && ok;
        ok = err('consent', form.querySelector('[name=consent]').checked ? '' : 'Please agree to the privacy notice so we can contact you.') && ok;
        if (!ok) { var bad = form.querySelector('[aria-invalid="true"]') || form.querySelector('[name=consent]'); if (bad) { bad.focus(); } }
        return ok;
      }
      return true;
    }

    /* ----- Summary + review ----- */
    function summary() {
      var v = val('vehicle'), svc = engine.service(val('service')), stId = val('staff') || 'any';
      var start = val('start');
      var slot = lastSlots.filter(function (s) { return String(s.start) === start; })[0];
      var assigned = stId === 'any' ? (slot ? slot.staff[0] : null) : stId;
      return {
        vehicle: D.settings.vehicles[v] || '',
        service: svc ? svc.name + ' (' + E.duration(svc.minutes) + ')' : 'Not chosen',
        staff: stId === 'any' ? (assigned ? 'Any detailer (' + engine.member(assigned).name + ')' : 'Any detailer') : engine.member(stId).name,
        date: dateInput.value ? longDate(dateInput.value) : 'Not chosen',
        time: slot && svc ? slot.label + ' – ' + E.minLabel(slot.start + svc.minutes) : 'Not chosen',
        price: svc && svc.prices[v] != null ? E.peso(svc.prices[v]) : '₱0'
      };
    }
    function update() {
      var s = summary();
      document.querySelectorAll('[data-sum]').forEach(function (el) { el.textContent = s[el.getAttribute('data-sum')]; });
    }
    function renderReview() {
      var s = summary();
      var name = form.querySelector('[name=name]').value.trim();
      var rows = [['Service', s.service], ['Vehicle', s.vehicle + (form.querySelector('[name=plate]').value ? ' · ' + form.querySelector('[name=plate]').value.toUpperCase() : '')], ['Detailer', s.staff], ['Date', s.date], ['Time', s.time], ['Name', name], ['Mobile', form.querySelector('[name=mobile]').value], ['Price', s.price + ' (pay at the shop)']];
      var dl = form.querySelector('[data-review]');
      dl.innerHTML = '';
      rows.forEach(function (r) {
        var div = document.createElement('div');
        var dt = document.createElement('dt'); dt.textContent = r[0];
        var dd = document.createElement('dd'); dd.textContent = r[1];
        div.appendChild(dt); div.appendChild(dd); dl.appendChild(div);
      });
    }

    /* ----- Step navigation ----- */
    function show(step, focus) {
      current = step;
      steps.forEach(function (fs) { fs.hidden = +fs.getAttribute('data-step') !== step; });
      form.querySelectorAll('[data-progress]').forEach(function (li) {
        var n = +li.getAttribute('data-progress');
        li.classList.toggle('is-done', n < step);
        if (n === step) { li.setAttribute('aria-current', 'step'); } else { li.removeAttribute('aria-current'); }
      });
      prev.hidden = step === 1;
      nextBtn.hidden = step === 5;
      submit.hidden = step !== 5;
      if (step === 2) { applyService(); }
      if (step === 3) {
        buildStrip();
        if (dateInput.value && typeof engine.dayHours(dateInput.value, today) !== 'string') { pickDate(dateInput.value, val('start')); }
        else {
          status.textContent = 'Finding the first open day…';
          firstOpenDate().then(function (d) { if (d) { pickDate(d); } else { status.textContent = 'No open times in the next ' + D.settings.days_ahead + ' days. Please call us.'; } });
        }
      }
      if (step === 5) { renderReview(); }
      update();
      if (focus !== false) {
        var legend = form.querySelector('[data-step="' + step + '"] .step-title');
        if (legend) { legend.setAttribute('tabindex', '-1'); legend.focus({ preventScroll: true }); }
        var top = form.getBoundingClientRect().top + window.pageYOffset - 90;
        if (window.pageYOffset > top) { window.scrollTo({ top: top, behavior: 'smooth' }); }
      }
    }
    nextBtn.addEventListener('click', function () { if (validate(current)) { show(current + 1); } });
    prev.addEventListener('click', function () { show(current - 1); });

    form.addEventListener('change', function (ev) {
      var n = ev.target.name;
      if (n === 'vehicle') { applyVehicle(); }
      if (n === 'service') { err('service', ''); applyService(); }
      if (n === 'staff' && current === 3 && dateInput.value) { pickDate(dateInput.value); }
      if (n === 'start') { err('start', ''); update(); }
      if (n === 'consent') { err('consent', ''); }
    });

    /* ----- Submit ----- */
    form.addEventListener('submit', function (ev) {
      if (current !== 5) { ev.preventDefault(); if (validate(current)) { show(current + 1); } return; }
      if (!STATIC) { submit.disabled = true; return; } // PHP posts the form; the server re-checks the slot.
      ev.preventDefault();
      var input = {
        service: val('service'), vehicle: val('vehicle'), staff: val('staff') || 'any', date: dateInput.value, start: +val('start'),
        name: form.querySelector('[name=name]').value.trim(), mobile: form.querySelector('[name=mobile]').value.trim(),
        email: form.querySelector('[name=email]').value.trim(), plate: form.querySelector('[name=plate]').value.trim().toUpperCase(),
        notes: form.querySelector('[name=notes]').value.trim()
      };
      // Same double-booking check the server does inside its file lock.
      var bookings = loadBookings();
      var check = engine.slots(input.service, input.staff, input.date, bookings);
      var slot = check.slots.filter(function (s) { return s.start === input.start; })[0];
      if (!slot) {
        show(3);
        err('start', 'Sorry, that time was just taken. Please pick another slot.');
        return;
      }
      var svc = engine.service(input.service);
      var booking = {
        ref: newRef(input.date), service: svc.id, vehicle: input.vehicle, price: svc.prices[input.vehicle], minutes: svc.minutes,
        staff: slot.staff[0], date: input.date, start: input.start, name: input.name, mobile: input.mobile, email: input.email,
        plate: input.plate, notes: input.notes, status: 'confirmed', created: new Date().toISOString()
      };
      bookings.push(booking);
      saveBookings(bookings);
      try { window.sessionStorage.setItem('cc-last-booking', JSON.stringify(booking)); } catch (e) { /* ignore */ }
      window.location.href = D.links.confirmation + '#' + booking.ref;
    });

    function newRef(date) {
      var abc = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789', code = '';
      var buf = new Uint32Array(4);
      if (window.crypto && window.crypto.getRandomValues) { window.crypto.getRandomValues(buf); } else { for (var i = 0; i < 4; i++) { buf[i] = Math.floor(Math.random() * 1e9); } }
      for (var j = 0; j < 4; j++) { code += abc[buf[j] % abc.length]; }
      return 'CC-' + date.replace(/-/g, '').slice(4) + '-' + code;
    }

    /* ----- Start: query string (?service=&staff=) or values after a server error ----- */
    var params = {};
    try { new URLSearchParams(window.location.search).forEach(function (v, k) { params[k] = v; }); } catch (e) { /* ignore */ }
    var old = {};
    try { old = JSON.parse(form.getAttribute('data-old') || '{}') || {}; } catch (e) { old = {}; }
    if (Array.isArray(old)) { old = {}; }
    if (old.vehicle) { setRadio('vehicle', old.vehicle); }
    applyVehicle();
    var svcId = old.service || params.service;
    if (svcId && engine.service(svcId)) {
      var s = engine.service(svcId);
      if (s.prices[val('vehicle')] == null) { setRadio('vehicle', Object.keys(s.prices)[0]); applyVehicle(); }
      setRadio('service', svcId);
    }
    applyService();
    var stId = old.staff || params.staff;
    if (stId) { var r = form.querySelector('[name=staff][value="' + stId + '"]'); if (r && !r.disabled) { r.checked = true; } }
    ['name', 'mobile', 'email', 'plate', 'notes'].forEach(function (k) { if (old[k]) { form.querySelector('[name=' + k + ']').value = old[k]; } });
    if (old.consent) { form.querySelector('[name=consent]').checked = true; }
    if (old.date) { dateInput.value = old.date; }
    var errorsBox = document.querySelector('[data-server-errors]');
    if (old.service && errorsBox) {
      show(/taken|time/i.test(errorsBox.textContent) ? 3 : 4, false);
      errorsBox.focus();
    } else {
      show(1, false);
    }
  }

  /* =====================================================================
   * Static demo confirmation page
   * ===================================================================== */
  var conf = document.querySelector('[data-confirm][data-static]');
  if (conf) {
    var ref = (window.location.hash || '').replace('#', '');
    var list = loadBookings();
    var b = list.filter(function (x) { return x.ref === ref; })[0];
    if (!b) { try { b = JSON.parse(window.sessionStorage.getItem('cc-last-booking') || 'null'); } catch (e) { b = null; } }
    if (!b && list.length) { b = list[list.length - 1]; }
    if (!b) {
      conf.hidden = true;
      var miss = document.querySelector('[data-confirm-missing]');
      if (miss) { miss.hidden = false; }
    } else {
      var svc = engine.service(b.service), st = engine.member(b.staff);
      var fill = {
        first: b.name.split(' ')[0], ref: b.ref, service: svc.name,
        vehicle: D.settings.vehicles[b.vehicle] + (b.plate ? ' · ' + b.plate : ''),
        date: longDate(b.date), time: E.minLabel(b.start) + ' – ' + E.minLabel(b.start + b.minutes),
        staff: st.name, price: E.peso(b.price) + ' (pay at the shop)'
      };
      conf.querySelectorAll('[data-c]').forEach(function (el) { el.textContent = fill[el.getAttribute('data-c')]; });
      var icsBtn = conf.querySelector('[data-ics]');
      if (icsBtn) {
        icsBtn.addEventListener('click', function () {
          var blob = new Blob([engine.ics(b)], { type: 'text/calendar' });
          var a = document.createElement('a');
          a.href = URL.createObjectURL(blob);
          a.download = 'clearcoat-' + b.ref + '.ics';
          document.body.appendChild(a); a.click();
          setTimeout(function () { URL.revokeObjectURL(a.href); a.remove(); }, 500);
        });
      }
    }
  }
}());
