/*!
 * Clearcoat Auto Spa - main.js
 * Mobile menu, light/dark theme toggle, price switcher, form validation and
 * the "open now" badge. No libraries, no build step.
 */
(function () {
  'use strict';

  var root = document.documentElement;

  /* ---------- Safe storage (private windows can throw) ---------- */
  var store = {
    get: function (k) { try { return window.localStorage.getItem(k); } catch (e) { return null; } },
    set: function (k, v) { try { window.localStorage.setItem(k, v); } catch (e) { /* ignore */ } }
  };

  /* ---------- Theme toggle ---------- */
  function currentTheme() {
    var t = root.getAttribute('data-theme');
    if (t) { return t; }
    return window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
  }
  function syncThemeButtons() {
    var dark = currentTheme() === 'dark';
    document.querySelectorAll('[data-theme-toggle]').forEach(function (btn) {
      btn.setAttribute('aria-label', dark ? 'Switch to light theme' : 'Switch to dark theme');
      btn.classList.toggle('is-dark', dark);
    });
  }
  document.addEventListener('click', function (ev) {
    var btn = ev.target.closest('[data-theme-toggle]');
    if (!btn) { return; }
    var next = currentTheme() === 'dark' ? 'light' : 'dark';
    root.setAttribute('data-theme', next);
    store.set('cc-theme', next);
    syncThemeButtons();
  });
  syncThemeButtons();

  /* ---------- Mobile menu ---------- */
  var menuBtn = document.querySelector('[data-menu-toggle]');
  var nav = document.getElementById('main-nav');
  function setMenu(open) {
    if (!menuBtn || !nav) { return; }
    menuBtn.setAttribute('aria-expanded', open ? 'true' : 'false');
    menuBtn.setAttribute('aria-label', open ? 'Close menu' : 'Open menu');
    nav.classList.toggle('is-open', open);
    document.body.classList.toggle('menu-open', open);
  }
  if (menuBtn) {
    menuBtn.addEventListener('click', function () {
      setMenu(menuBtn.getAttribute('aria-expanded') !== 'true');
    });
    document.addEventListener('keydown', function (ev) {
      if (ev.key === 'Escape' && nav.classList.contains('is-open')) { setMenu(false); menuBtn.focus(); }
    });
    window.addEventListener('resize', function () { if (window.innerWidth > 900) { setMenu(false); } });
  }

  /* ---------- Open / closed badge and today's row in the hours table ---------- */
  var HOURS = { 0: [480, 840], 1: [480, 1080], 2: [480, 1080], 3: [480, 1080], 4: [480, 1080], 5: [480, 1080], 6: [480, 1080] };
  var dataEl = document.getElementById('cc-data');
  if (dataEl) {
    try {
      var d = JSON.parse(dataEl.textContent);
      Object.keys(d.settings.hours).forEach(function (k) {
        var h = d.settings.hours[k];
        HOURS[k] = [toMin(h[0]), toMin(h[1])];
      });
    } catch (e) { /* keep defaults */ }
  }
  function toMin(s) { var p = s.split(':'); return (+p[0]) * 60 + (+p[1]); }
  function manilaNow() {
    var t = new Date(Date.now() + 480 * 60000); // UTC+8, independent of the visitor's time zone
    return { day: t.getUTCDay(), min: t.getUTCHours() * 60 + t.getUTCMinutes() };
  }
  var now = manilaNow();
  var row = document.querySelector('.hours-table tr[data-day="' + now.day + '"]');
  if (row) { row.classList.add('is-today'); }
  document.querySelectorAll('[data-open-status]').forEach(function (el) {
    var h = HOURS[now.day];
    var open = h && now.min >= h[0] && now.min < h[1];
    el.textContent = open ? 'Open now' : 'Closed now';
    el.classList.toggle('is-closed', !open);
  });

  /* ---------- Services page: switch prices by vehicle size ---------- */
  var sw = document.querySelector('[data-price-switch]');
  if (sw) {
    var labels = { sedan: 'Sedan / Hatchback', suv: 'SUV / Pickup / AUV', van: 'Van / Large' };
    sw.addEventListener('click', function (ev) {
      var b = ev.target.closest('[data-vehicle]');
      if (!b) { return; }
      var v = b.getAttribute('data-vehicle');
      sw.querySelectorAll('[data-vehicle]').forEach(function (x) { x.setAttribute('aria-checked', x === b ? 'true' : 'false'); });
      document.querySelectorAll('[data-prices]').forEach(function (p) {
        var prices = JSON.parse(p.getAttribute('data-prices'));
        if (prices[v] != null) {
          p.textContent = '₱' + Number(prices[v]).toLocaleString('en-PH');
          p.classList.remove('flash'); void p.offsetWidth; p.classList.add('flash');
        }
        var lab = p.parentNode.querySelector('[data-vehicle-label]');
        if (lab) { lab.textContent = labels[v]; }
      });
    });
    sw.addEventListener('keydown', function (ev) {
      if (ev.key !== 'ArrowRight' && ev.key !== 'ArrowLeft') { return; }
      var btns = Array.prototype.slice.call(sw.querySelectorAll('[data-vehicle]'));
      var i = btns.indexOf(document.activeElement);
      if (i < 0) { return; }
      var n = btns[(i + (ev.key === 'ArrowRight' ? 1 : btns.length - 1)) % btns.length];
      n.focus(); n.click(); ev.preventDefault();
    });
  }

  /* ---------- Form validation (contact form; booking uses booking.js) ---------- */
  var MOBILE = /^(09\d{9}|\+639\d{9})$/;
  var EMAIL = /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/;
  function fieldError(input) {
    var v = input.value.trim();
    var rule = input.getAttribute('data-rule');
    if (input.required && !v) { return 'This field is required.'; }
    if (input.minLength > 0 && v && v.length < input.minLength) { return 'Please write at least ' + input.minLength + ' characters.'; }
    if (rule === 'mobile' && v && !MOBILE.test(v.replace(/[\s\-()]/g, ''))) { return 'Enter a mobile number like 0917 123 4567.'; }
    if (rule === 'mobile-or-email' && v && !MOBILE.test(v.replace(/[\s\-()]/g, '')) && !EMAIL.test(v)) { return 'Enter a mobile number (0917 123 4567) or an e-mail address.'; }
    if (input.type === 'email' && v && !EMAIL.test(v)) { return 'Check the e-mail address.'; }
    return '';
  }
  function showError(input, msg) {
    var id = input.getAttribute('aria-describedby');
    var box = id ? document.getElementById(id) : null;
    input.setAttribute('aria-invalid', msg ? 'true' : 'false');
    if (box) { box.textContent = msg; box.hidden = !msg; }
  }
  document.querySelectorAll('form[data-validate]').forEach(function (form) {
    form.addEventListener('submit', function (ev) {
      var first = null;
      form.querySelectorAll('input[required], textarea[required], input[data-rule]').forEach(function (inp) {
        var msg = fieldError(inp);
        showError(inp, msg);
        if (msg && !first) { first = inp; }
      });
      if (first) { ev.preventDefault(); first.focus(); return; }
      if (form.getAttribute('data-static-form') === '1') {
        ev.preventDefault(); // Static demo: show the success state instead of posting.
        var ok = form.parentNode.querySelector('[data-form-success]');
        form.hidden = true;
        if (ok) { ok.hidden = false; ok.focus(); }
      }
    });
    form.addEventListener('input', function (ev) {
      if (ev.target.getAttribute('aria-invalid') === 'true') { showError(ev.target, fieldError(ev.target)); }
    });
  });

  /* ---------- Hide the floating buttons when the footer is visible ---------- */
  var fab = document.querySelector('[data-fab]');
  var footer = document.querySelector('.site-footer');
  if (fab && footer && 'IntersectionObserver' in window) {
    new IntersectionObserver(function (entries) {
      fab.classList.toggle('is-hidden', entries[0].isIntersecting);
    }).observe(footer);
  }
}());
