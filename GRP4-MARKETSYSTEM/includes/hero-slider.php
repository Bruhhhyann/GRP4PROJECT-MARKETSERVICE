<?php
/* Promo slider for the home page (replaces the single worker picture).
   Uses $featured (latest products) from index.php. To use your own worker photo on the first slide,
   put it in the images folder as worker.jpg / worker.png / worker.webp. */
$sliderPhoto = null;
foreach (['jpg', 'jpeg', 'png', 'webp', 'JPG', 'JPEG', 'PNG'] as $ext) {
    if (file_exists(__DIR__ . "/../images/worker.$ext")) { $sliderPhoto = "images/worker.$ext?v=" . filemtime(__DIR__ . "/../images/worker.$ext"); break; }
}
$role = $_SESSION['role'] ?? '';
$voucherLink  = is_signed_in() ? 'vouchers.php' : 'register.php';
$providerLink = $role === 'client' ? 'apply.php' : (is_signed_in() ? 'dashboard.php' : 'register.php');
$newItems     = array_slice($featured ?? [], 0, 3);
?>
<style>
  .hero-slider { position: relative; width: 100%; max-width: 560px; margin: 0 auto; aspect-ratio: 1 / 1.04; border-radius: 26px; overflow: hidden; box-shadow: 0 24px 60px rgba(0,0,0,.32); background: #fff; outline-offset: 4px; }
  .hero-slider .slide { position: absolute; inset: 0; display: flex; flex-direction: column; align-items: center; padding: 1.4rem 1.6rem 3.6rem; text-align: center; opacity: 0; visibility: hidden; transition: opacity .7s ease, visibility .7s; }
  .hero-slider .slide.active { opacity: 1; visibility: visible; }
  .hero-slider .slide-art { flex: 1; min-height: 0; width: 100%; display: grid; place-items: center; }
  .hero-slider .slide-art svg, .hero-slider .slide-art > img { height: 100%; width: auto; max-width: 100%; object-fit: contain; }
  .hero-slider .slide h2 { font: 800 clamp(1.35rem, 2.6vw, 1.9rem)/1.15 var(--font-head, 'Segoe UI', sans-serif); margin: .5rem 0 .3rem; }
  .hero-slider .slide p { margin: 0 0 .9rem; font-size: 1rem; max-width: 36ch; }
  .hero-slider .slide .btn { padding: .65rem 1.5rem; }
  .hero-slider .s1 { background: linear-gradient(180deg, #fff3cf, #ffe08f); color: #0e2a47; }
  .hero-slider .s2 { background: linear-gradient(180deg, #1f8a8a, #167070); color: #fff; }
  .hero-slider .s3 { background: linear-gradient(180deg, #fff7e0, #fbe4a6); color: #0e2a47; }
  .hero-slider .s4 { background: linear-gradient(180deg, #163a5f, #0e2a47); color: #fff; }
  .hero-slider .s5 { background: linear-gradient(180deg, #eef3f7, #d9e5ef); color: #0e2a47; }
  .hero-slider .s2 .btn-primary, .hero-slider .s4 .btn-primary { background: #f5b83d; color: #0e2a47; }
  .hero-slider .nav { position: absolute; top: 42%; z-index: 3; width: 42px; height: 42px; border: 0; border-radius: 50%; background: rgba(255,255,255,.92); color: #0e2a47; font-size: 1.5rem; line-height: 1; cursor: pointer; box-shadow: 0 4px 14px rgba(0,0,0,.2); display: grid; place-items: center; }
  .hero-slider .nav:hover { background: #fff; transform: scale(1.06); }
  .hero-slider .prev { left: .7rem; } .hero-slider .next { right: .7rem; }
  .hero-slider .dots { position: absolute; bottom: 1rem; left: 0; right: 0; display: flex; justify-content: center; gap: .5rem; z-index: 3; }
  .hero-slider .dot { width: 11px; height: 11px; padding: 0; border: 0; border-radius: 999px; background: rgba(14,42,71,.3); cursor: pointer; transition: width .25s ease, background .25s ease; }
  .hero-slider .dot.on { width: 30px; background: #f5b83d; }
  .hero-slider .nav:focus-visible, .hero-slider .dot:focus-visible { outline: 3px solid #f5b83d; outline-offset: 2px; }
  .mini-products { display: flex; gap: .8rem; justify-content: center; align-items: stretch; width: 100%; }
  .mini-product { flex: 1; max-width: 150px; background: #fff; border-radius: 14px; padding: .55rem; box-shadow: 0 6px 18px rgba(14,42,71,.15); text-decoration: none; color: inherit; display: flex; flex-direction: column; gap: .25rem; }
  .mini-product .mp-art { aspect-ratio: 1; border-radius: 10px; background: #eef3f7; display: grid; place-items: center; font-size: 2.8rem; overflow: hidden; }
  .mini-product .mp-art img { width: 100%; height: 100%; object-fit: cover; }
  .mini-product strong { font-size: .82rem; line-height: 1.2; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
  .mini-product span.mp-price { font-weight: 800; font-size: .9rem; }
  .emoji-row { font-size: 3rem; display: flex; gap: .8rem; flex-wrap: wrap; justify-content: center; }
  @media (max-width: 960px) { .hero-slider { max-width: 480px; } }
  @media (max-width: 520px) { .hero-slider .slide { padding: 1rem 1rem 3.4rem; } .hero-slider .nav { width: 36px; height: 36px; } }
  @media (prefers-reduced-motion: reduce) { .hero-slider .slide, .hero-slider .dot { transition: none; } }
</style>

<div class="hero-slider" id="heroSlider" role="region" aria-roledescription="carousel" aria-label="Promotions" tabindex="0">

  <!-- 1. Promo worker -->
  <section class="slide s1 active" aria-roledescription="slide" aria-label="1 of 5">
    <div class="slide-art">
      <?php if ($sliderPhoto): ?><img src="<?= htmlspecialchars($sliderPhoto) ?>" alt="Our service worker"><?php else: include __DIR__ . '/worker-art.php'; endif; ?>
    </div>
    <h2>Trusted workers, booked in minutes</h2>
    <p>Cleaning, repairs, design, tutoring and more from verified providers.</p>
    <a class="btn btn-primary" href="providers.php">Book a service</a>
  </section>

  <!-- 2. Free shipping -->
  <section class="slide s2" aria-roledescription="slide" aria-label="2 of 5" aria-hidden="true">
    <div class="slide-art">
      <svg viewBox="0 0 420 300" role="img" aria-label="Delivery truck full of boxes">
        <ellipse cx="205" cy="262" rx="175" ry="13" fill="rgba(0,0,0,.18)"/>
        <path d="M6 120h34M0 154h40M14 188h26" stroke="#fff" stroke-width="7" stroke-linecap="round" opacity=".55"/>
        <rect x="56" y="84" width="184" height="138" rx="12" fill="#fff"/>
        <rect x="74" y="106" width="52" height="42" rx="6" fill="#f5b83d"/>
        <rect x="134" y="106" width="52" height="42" rx="6" fill="#e3d3b8"/>
        <rect x="88" y="156" width="64" height="44" rx="6" fill="#d8a496"/>
        <rect x="160" y="156" width="56" height="44" rx="6" fill="#f5b83d"/>
        <path d="M240 128h72l44 46v48H240z" fill="#f5b83d"/>
        <path d="M256 142h46l28 32h-74z" fill="#d6eef0"/>
        <circle cx="116" cy="228" r="27" fill="#163a5f"/><circle cx="116" cy="228" r="11" fill="#c0c7cf"/>
        <circle cx="306" cy="228" r="27" fill="#163a5f"/><circle cx="306" cy="228" r="11" fill="#c0c7cf"/>
        <circle cx="338" cy="62" r="50" fill="#163a5f"/>
        <text x="338" y="58" text-anchor="middle" fill="#f5b83d" font-size="27" font-weight="800" font-family="DM Sans, Segoe UI, sans-serif">FREE</text>
        <text x="338" y="80" text-anchor="middle" fill="#fff" font-size="13" font-weight="700" font-family="DM Sans, Segoe UI, sans-serif">SHIPPING</text>
      </svg>
    </div>
    <h2>Free shipping on <?= htmlspecialchars(money((float)FREE_SHIPPING_MIN)) ?>+ orders</h2>
    <p>Spend <?= htmlspecialchars(money((float)FREE_SHIPPING_MIN)) ?> or more from one shop and we ship it free. Pay on delivery.</p>
    <a class="btn btn-primary" href="shop.php">Shop now</a>
  </section>

  <!-- 3. Vouchers -->
  <section class="slide s3" aria-roledescription="slide" aria-label="3 of 5" aria-hidden="true">
    <div class="slide-art">
      <svg viewBox="0 0 400 300" role="img" aria-label="Discount vouchers">
        <g transform="rotate(-5 200 120)">
          <rect x="40" y="46" width="320" height="112" rx="18" fill="#f5b83d"/>
          <circle cx="40" cy="102" r="15" fill="#fff3cf"/><circle cx="360" cy="102" r="15" fill="#fff3cf"/>
          <path d="M268 58v88" stroke="#0e2a47" stroke-width="3" stroke-dasharray="7 8" opacity=".5"/>
          <text x="68" y="96" fill="#0e2a47" font-size="46" font-weight="800" font-family="DM Sans, Segoe UI, sans-serif">10% OFF</text>
          <text x="70" y="128" fill="#0e2a47" font-size="16" font-weight="700" font-family="DM Sans, Segoe UI, sans-serif">after your 3rd order</text>
          <circle cx="314" cy="102" r="30" fill="#0e2a47"/>
          <text x="314" y="113" text-anchor="middle" fill="#f5b83d" font-size="32" font-weight="800" font-family="DM Sans, Segoe UI, sans-serif">%</text>
        </g>
        <g transform="rotate(4 200 220)">
          <rect x="60" y="172" width="290" height="92" rx="18" fill="#1f8a8a"/>
          <circle cx="60" cy="218" r="14" fill="#fff3cf"/><circle cx="350" cy="218" r="14" fill="#fff3cf"/>
          <path d="M262 182v72" stroke="#fff" stroke-width="3" stroke-dasharray="7 8" opacity=".6"/>
          <text x="84" y="226" fill="#fff" font-size="30" font-weight="800" font-family="DM Sans, Segoe UI, sans-serif">FREE SHIPPING</text>
          <text x="306" y="228" text-anchor="middle" font-size="34">&#128666;</text>
        </g>
      </svg>
    </div>
    <h2>Buy more, save more</h2>
    <p>Earn vouchers when you buy a lot or spend a lot. Plus a welcome gift for new members.</p>
    <a class="btn btn-primary" href="<?= $voucherLink ?>"><?= is_signed_in() ? 'See my vouchers' : 'Join and get a gift' ?></a>
  </section>

  <!-- 4. Become a provider -->
  <section class="slide s4" aria-roledescription="slide" aria-label="4 of 5" aria-hidden="true">
    <div class="slide-art">
      <svg viewBox="0 0 400 300" role="img" aria-label="A small shop with an open sign">
        <ellipse cx="200" cy="268" rx="150" ry="12" fill="rgba(0,0,0,.25)"/>
        <rect x="76" y="116" width="248" height="146" rx="8" fill="#fff"/>
        <?php for ($k = 0; $k < 6; $k++): ?><rect x="<?= 66 + $k * 44.7 ?>" y="62" width="44.7" height="58" fill="<?= $k % 2 ? '#f5b83d' : '#d64545' ?>"/><?php endfor; ?>
        <?php for ($k = 0; $k < 6; $k++): ?><circle cx="<?= 88.3 + $k * 44.7 ?>" cy="120" r="22.3" fill="<?= $k % 2 ? '#f5b83d' : '#d64545' ?>"/><?php endfor; ?>
        <rect x="96" y="158" width="96" height="62" rx="6" fill="#d6eef0"/>
        <rect x="214" y="150" width="70" height="112" rx="6" fill="#163a5f"/>
        <circle cx="270" cy="208" r="4" fill="#f5b83d"/>
        <rect x="104" y="228" width="80" height="24" rx="12" fill="#1f8a8a"/>
        <text x="144" y="245" text-anchor="middle" fill="#fff" font-size="14" font-weight="800" font-family="DM Sans, Segoe UI, sans-serif">OPEN</text>
        <text x="348" y="70" font-size="34">&#10024;</text><text x="30" y="110" font-size="26">&#10024;</text>
      </svg>
    </div>
    <h2>Sell with us. Grow with us.</h2>
    <p>Open your shop or offer your services. Reach new customers and get paid on delivery.</p>
    <a class="btn btn-primary" href="<?= $providerLink ?>"><?= is_signed_in() ? ($role === 'client' ? 'Become a provider' : 'Go to my dashboard') : 'Register and start' ?></a>
  </section>

  <!-- 5. New arrivals -->
  <section class="slide s5" aria-roledescription="slide" aria-label="5 of 5" aria-hidden="true">
    <div class="slide-art">
      <?php if ($newItems): ?>
        <div class="mini-products">
          <?php foreach ($newItems as $mp): ?>
            <a class="mini-product" href="product.php?id=<?= (int)$mp['id'] ?>">
              <span class="mp-art"><?= product_art($mp) ?></span>
              <strong><?= htmlspecialchars($mp['name']) ?></strong>
              <span class="mp-price"><?= money((float)$mp['price']) ?></span>
            </a>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <div class="emoji-row" aria-hidden="true"><span>&#128095;</span><span>&#128085;</span><span>&#128092;</span><span>&#8987;</span><span>&#129506;</span><span>&#128087;</span></div>
      <?php endif; ?>
    </div>
    <h2>Fresh from our shops</h2>
    <p>Shoes, shirts, bags, watches and more. Add to cart and pay on delivery.</p>
    <a class="btn btn-primary" href="shop.php">Browse products</a>
  </section>

  <button class="nav prev" type="button" aria-label="Previous slide">&#8249;</button>
  <button class="nav next" type="button" aria-label="Next slide">&#8250;</button>
  <div class="dots" role="tablist" aria-label="Choose a slide">
    <?php for ($k = 0; $k < 5; $k++): ?><button class="dot<?= $k === 0 ? ' on' : '' ?>" type="button" role="tab" aria-label="Go to slide <?= $k + 1 ?>"></button><?php endfor; ?>
  </div>
</div>

<script>
  (function () {
    const root = document.getElementById('heroSlider');
    if (!root) return;
    const slides = [...root.querySelectorAll('.slide')], dots = [...root.querySelectorAll('.dot')];
    const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    let i = 0, timer = null;
    function show(n) {
      i = (n + slides.length) % slides.length;
      slides.forEach((el, k) => {
        const on = k === i;
        el.classList.toggle('active', on);
        el.setAttribute('aria-hidden', on ? 'false' : 'true');
        el.querySelectorAll('a').forEach(a => a.tabIndex = on ? 0 : -1);
      });
      dots.forEach((d, k) => { d.classList.toggle('on', k === i); d.setAttribute('aria-selected', k === i ? 'true' : 'false'); });
    }
    const stop = () => { clearInterval(timer); timer = null; };
    const play = () => { if (reduce || timer) return; timer = setInterval(() => show(i + 1), 5500); };
    root.querySelector('.prev').addEventListener('click', () => { show(i - 1); stop(); play(); });
    root.querySelector('.next').addEventListener('click', () => { show(i + 1); stop(); play(); });
    dots.forEach((d, k) => d.addEventListener('click', () => { show(k); stop(); play(); }));
    root.addEventListener('mouseenter', stop); root.addEventListener('mouseleave', play);
    root.addEventListener('focusin', stop);    root.addEventListener('focusout', play);
    root.addEventListener('keydown', e => { if (e.key === 'ArrowLeft') show(i - 1); if (e.key === 'ArrowRight') show(i + 1); });
    let x0 = null;                                   // swipe on phones
    root.addEventListener('touchstart', e => { x0 = e.touches[0].clientX; stop(); }, { passive: true });
    root.addEventListener('touchend', e => { if (x0 !== null) { const dx = e.changedTouches[0].clientX - x0; if (Math.abs(dx) > 40) show(i + (dx < 0 ? 1 : -1)); } x0 = null; play(); });
    document.addEventListener('visibilitychange', () => document.hidden ? stop() : play());
    show(0); play();
  })();
</script>
