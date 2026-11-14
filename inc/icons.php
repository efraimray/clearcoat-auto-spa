<?php
/**
 * Clearcoat Auto Spa - inline SVG logo and icons (no icon font, no requests).
 *
 * @package ClearcoatAutoSpa
 */

defined('CC_ROOT') || exit;

/** Logo: droplet monogram + wordmark. */
function cc_logo($class = 'logo')
{
    return '<span class="' . e($class) . '">'
        . '<svg class="logo-mark" viewBox="0 0 40 40" width="36" height="36" aria-hidden="true" focusable="false">'
        . '<path d="M20 3C20 3 7 17.5 7 26a13 13 0 0 0 26 0C33 17.5 20 3 20 3z" fill="var(--aqua)"/>'
        . '<path d="M24.5 21.5a6.2 6.2 0 1 0 0 9" fill="none" stroke="var(--on-aqua)" stroke-width="3.2" stroke-linecap="round"/>'
        . '<path d="M12.5 21.5c1.2-2.6 2.8-4.6 4.2-6" fill="none" stroke="var(--on-aqua)" stroke-width="2" stroke-linecap="round" opacity=".55"/>'
        . '</svg>'
        . '<span class="logo-text"><span class="logo-name">Clearcoat</span><span class="logo-sub">Auto Spa</span></span>'
        . '</span>';
}

/** Small line icons, 24px grid, stroke = currentColor. */
function cc_icon($name)
{
    $p = array(
        'drop'    => '<path d="M12 3s-6 6.7-6 11a6 6 0 0 0 12 0c0-4.3-6-11-6-11z"/>',
        'vacuum'  => '<path d="M4 20h9M8 20V9a4 4 0 0 1 8 0v2"/><path d="M16 11l4 7h-6z"/>',
        'engine'  => '<rect x="4" y="8" width="14" height="9" rx="1.5"/><path d="M8 8V5h6v3M18 11h2v3h-2M4 12H2"/>',
        'glass'   => '<path d="M3 17c3-7 15-7 18 0z"/><path d="M8 14l3-3M12 14l2-2"/>',
        'shine'   => '<path d="M12 2v5M12 17v5M2 12h5M17 12h5M5 5l3 3M16 16l3 3M19 5l-3 3M8 16l-3 3"/>',
        'seat'    => '<path d="M7 3h5l1 9h5l1 7H8z"/><path d="M8 19v2M18 19v2"/>',
        'star'    => '<path d="M12 3l2.7 5.6 6.1.9-4.4 4.3 1 6.1L12 17l-5.4 2.9 1-6.1L3.2 9.5l6.1-.9z"/>',
        'moto'    => '<circle cx="5.5" cy="16.5" r="3"/><circle cx="18.5" cy="16.5" r="3"/><path d="M5.5 16.5l4-6h5l4 6M12 10.5L10 6H7"/>',
        'clock'   => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        'pin'     => '<path d="M12 21s-7-6.2-7-11a7 7 0 0 1 14 0c0 4.8-7 11-7 11z"/><circle cx="12" cy="10" r="2.5"/>',
        'phone'   => '<path d="M5 3h4l2 5-2.5 1.5a11 11 0 0 0 6 6L16 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 5a2 2 0 0 1 2-2z"/>',
        'chat'    => '<path d="M4 5h16v11H9l-5 4z"/>',
        'calendar'=> '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 10h18M8 3v4M16 3v4"/>',
        'check'   => '<path d="M5 12.5l4.5 4.5L19 7.5"/>',
        'leaf'    => '<path d="M5 19c0-9 6-14 15-14 0 9-5 15-14 15"/><path d="M5 19l7-7"/>',
        'shield'  => '<path d="M12 3l8 3v6c0 5-3.5 8-8 9-4.5-1-8-4-8-9V6z"/><path d="M8.5 12l2.5 2.5 4.5-4.5"/>',
        'cup'     => '<path d="M4 8h13v6a5 5 0 0 1-5 5H9a5 5 0 0 1-5-5z"/><path d="M17 10h2a2 2 0 0 1 0 4h-2M8 3v2M12 3v2"/>',
        'menu'    => '<path d="M4 7h16M4 12h16M4 17h16"/>',
        'close'   => '<path d="M6 6l12 12M18 6L6 18"/>',
        'sun'     => '<circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M2 12h2M20 12h2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M19.1 4.9l-1.4 1.4M6.3 17.7l-1.4 1.4"/>',
        'moon'    => '<path d="M20 14.5A8 8 0 0 1 9.5 4a8 8 0 1 0 10.5 10.5z"/>',
        'arrow'   => '<path d="M5 12h14M13 6l6 6-6 6"/>',
        'mail'    => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 7l9 6 9-6"/>',
    );
    $d = isset($p[$name]) ? $p[$name] : $p['drop'];
    return '<svg class="icon" viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . $d . '</svg>';
}

/** Hero illustration: car silhouette, foam and a shine sweep (pure SVG). */
function cc_hero_art()
{
    return '<svg class="hero-art" viewBox="0 0 560 360" role="img" aria-labelledby="hero-art-title">'
        . '<title id="hero-art-title">Illustration of a freshly washed car with foam bubbles and a shine sweep</title>'
        . '<defs>'
        . '<linearGradient id="ccBody" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="var(--car-hi)"/><stop offset="1" stop-color="var(--car)"/></linearGradient>'
        . '<linearGradient id="ccSweep" x1="0" y1="0" x2="1" y2="0"><stop offset="0" stop-color="#fff" stop-opacity="0"/><stop offset=".5" stop-color="#fff" stop-opacity=".75"/><stop offset="1" stop-color="#fff" stop-opacity="0"/></linearGradient>'
        . '<clipPath id="ccCarClip"><path d="M60 236c0-22 14-36 40-42l58-12 52-46c12-10 26-16 44-16h112c18 0 32 6 44 18l46 44 46 10c22 5 34 18 34 40v22c0 8-6 14-14 14H74c-8 0-14-6-14-14z"/></clipPath>'
        . '</defs>'
        . '<ellipse cx="280" cy="300" rx="250" ry="18" fill="var(--shadow-ground)"/>'
        . '<path d="M60 236c0-22 14-36 40-42l58-12 52-46c12-10 26-16 44-16h112c18 0 32 6 44 18l46 44 46 10c22 5 34 18 34 40v22c0 8-6 14-14 14H74c-8 0-14-6-14-14z" fill="url(#ccBody)"/>'
        . '<path d="M186 180l40-36c8-7 17-10 28-10h50v46z" fill="var(--glass)"/>'
        . '<path d="M318 134h48c11 0 20 4 28 11l36 35H318z" fill="var(--glass)"/>'
        . '<g clip-path="url(#ccCarClip)"><rect class="sweep" x="-160" y="100" width="120" height="220" fill="url(#ccSweep)" transform="skewX(-18)"/></g>'
        . '<path d="M96 222h380" stroke="var(--car-hi)" stroke-width="3" opacity=".6"/>'
        . '<circle cx="160" cy="276" r="36" fill="var(--tire)"/><circle cx="160" cy="276" r="17" fill="var(--rim)"/>'
        . '<circle cx="410" cy="276" r="36" fill="var(--tire)"/><circle cx="410" cy="276" r="17" fill="var(--rim)"/>'
        . '<g class="bubbles" fill="var(--foam)" stroke="var(--foam-edge)" stroke-width="1.5">'
        . '<circle cx="82" cy="200" r="16"/><circle cx="104" cy="184" r="11"/><circle cx="62" cy="182" r="9"/>'
        . '<circle cx="486" cy="206" r="14"/><circle cx="506" cy="186" r="9"/><circle cx="470" cy="186" r="7"/>'
        . '<circle cx="300" cy="96" r="8"/><circle cx="318" cy="80" r="5"/><circle cx="250" cy="88" r="6"/>'
        . '</g>'
        . '<g fill="var(--aqua)" opacity=".9"><path d="M520 70s-10 11-10 18a10 10 0 0 0 20 0c0-7-10-18-10-18z"/><path d="M40 92s-7 8-7 13a7 7 0 0 0 14 0c0-5-7-13-7-13z"/></g>'
        . '<g stroke="var(--signal)" stroke-width="4" stroke-linecap="round"><path d="M440 40v16M432 48h16"/><path d="M120 120v12M114 126h12"/></g>'
        . '</svg>';
}
