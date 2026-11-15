<?php /* Shared <head> extras: phone icon + app info, and the mobile layout. Included by every page after style.css. */ ?>
  <link rel="manifest" href="manifest.webmanifest">
  <meta name="theme-color" content="#0e2a47">
  <meta name="mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-title" content="AIOS">
  <link rel="apple-touch-icon" href="images/apple-touch-icon.png">
  <link rel="icon" type="image/png" href="images/icon-192.png">
  <style>
    /* ---------- Phone and tablet layout ---------- */
    .menu-toggle { display: none; }
    html { -webkit-text-size-adjust: 100%; }
    img, svg { max-width: 100%; }
    @media (max-width: 960px) {
      .site-header { flex-wrap: wrap; height: auto; min-height: var(--header-h); padding-top: .55rem; padding-bottom: .55rem; gap: .5rem; }
      .site-header .brand { order: 0; margin-right: auto; }
      .site-header .header-actions { order: 1; gap: .4rem; }
      .menu-toggle { order: 2; display: grid; place-items: center; width: 44px; height: 44px; border: 1px solid var(--line); border-radius: 12px; background: #fff; color: var(--navy); font-size: 1.4rem; line-height: 1; cursor: pointer; }
      .site-header .main-nav { order: 5; display: none; width: 100%; flex-direction: column; gap: 0; border-top: 1px solid var(--line); margin-top: .2rem; }
      .site-header .main-nav a { padding: .85rem .2rem; border-bottom: 1px solid var(--line); }
      .site-header .m-hide { display: none; }
      .site-header.open .main-nav { display: flex; }
      .site-header.open .header-actions { order: 6; width: 100%; flex-wrap: wrap; padding: .5rem 0 .2rem; }
      .site-header.open .m-hide { display: inline-block; }
      .site-header.open .greeting.m-hide { flex-basis: 100%; }
    }
    @media (max-width: 860px) {
      .sidebar { padding: .6rem .8rem; }
      .sidebar h2 { display: none; }
      .sidebar nav { display: flex; flex-wrap: nowrap; overflow-x: auto; gap: .35rem; padding-bottom: .3rem; -webkit-overflow-scrolling: touch; }
      .sidebar nav a { flex: 0 0 auto; white-space: nowrap; background: var(--navy-2); }
      .dash-main { padding: 1.2rem 1rem 2.5rem; }
    }
    @media (max-width: 640px) {
      .btn:not(.btn-small) { min-height: 44px; }
      input, select, textarea { font-size: 16px; }
      .hero { padding: 1.2rem 1rem 2rem; gap: 1.5rem; }
      .hero-cta .btn { flex: 1 1 100%; }
      .section { padding-left: 1rem; padding-right: 1rem; }
      .auth-card { padding: 1.4rem 1.1rem; }
      .shop-foot, .co-actions { justify-content: stretch; }
      .shop-foot form, .shop-foot .place-btn, .co-actions .btn { width: 100%; }
    }
  </style>
  <script>
    document.addEventListener('DOMContentLoaded', function () {
      var header = document.querySelector('.site-header'), btn = document.querySelector('.menu-toggle');
      if (!header || !btn) return;
      btn.addEventListener('click', function () {
        var open = header.classList.toggle('open');
        btn.setAttribute('aria-expanded', open ? 'true' : 'false');
        btn.innerHTML = open ? '&#10005;' : '&#9776;';
      });
    });
  </script>
