/* Parity test: engine.js must give the same slots as inc/booking.php.
 * Run: node tools/test-engine.js   (needs php on PATH) */
'use strict';
const { execFileSync } = require('child_process');
const path = require('path');
const fs = require('fs');
const root = path.join(__dirname, '..');
const E = require(path.join(root, 'assets/js/engine.js'));
const php = JSON.parse(execFileSync('php', [path.join(root, 'tools/test-engine.php'), '--json']).toString());
const data = {
  services: JSON.parse(fs.readFileSync(path.join(root, 'data/services.json'))),
  staff: JSON.parse(fs.readFileSync(path.join(root, 'data/staff.json'))),
  settings: JSON.parse(fs.readFileSync(path.join(root, 'data/settings.json'))),
  business: { name: 'Clearcoat Auto Spa', phone: '0900 123 4567', location: 'Valencia City' }
};
const eng = E.create(data);
let pass = 0, fail = 0;
php.results.forEach(r => {
  const js = eng.slots(r.case[0], r.case[1], r.case[2], [], php.now);
  const same = JSON.stringify(js) === JSON.stringify({ ok: r.ok, reason: r.reason, slots: r.slots });
  console.log((same ? '  PASS ' : '  FAIL ') + r.case.join(' / ') + ' -> ' + js.slots.length + ' slots');
  same ? pass++ : fail++;
});
// Extra: FNV hash matches for a few strings.
['2026-11-07|rico', 'abc', ''].forEach(s => {
  const h = parseInt(execFileSync('php', ['-r', 'define("CC_ROOT","' + root + '");require "' + root + '/inc/bootstrap.php";echo cc_fnv($argv[1]);', '--', s]).toString(), 10);
  const ok = h === E.fnv(s);
  console.log((ok ? '  PASS ' : '  FAIL ') + 'fnv("' + s + '") = ' + E.fnv(s));
  ok ? pass++ : fail++;
});
console.log('\n' + pass + ' passed, ' + fail + ' failed');
process.exit(fail ? 1 : 0);
