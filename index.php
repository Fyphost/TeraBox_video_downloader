<?php
/**
 * TeraBox Video Downloader – Homepage
 * Production-quality landing page + downloader UI.
 */
require_once __DIR__ . '/config.php';

$csrfToken   = csrf_token();
$siteUrl     = rtrim(SITE_URL, '/');
$canonicalUrl = $siteUrl . '/';
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">

  <!-- ── Primary SEO ──────────────────────────────────────────── -->
  <title><?= e(SITE_NAME) ?> – Download TeraBox Videos in HD for Free</title>
  <meta name="description" content="<?= e(SITE_DESCRIPTION) ?>">
  <meta name="keywords"    content="<?= e(SITE_KEYWORDS) ?>">
  <meta name="author"      content="<?= e(SITE_AUTHOR) ?>">
  <meta name="robots"      content="index, follow, max-snippet:-1, max-image-preview:large">
  <link rel="canonical"    href="<?= e($canonicalUrl) ?>">

  <!-- ── Open Graph ───────────────────────────────────────────── -->
  <meta property="og:type"        content="website">
  <meta property="og:url"         content="<?= e($canonicalUrl) ?>">
  <meta property="og:title"       content="<?= e(SITE_NAME) ?> – Download TeraBox Videos in HD for Free">
  <meta property="og:description" content="<?= e(SITE_DESCRIPTION) ?>">
  <meta property="og:image"       content="<?= e($siteUrl) ?>/assets/img/og-image.png">
  <meta property="og:site_name"   content="<?= e(SITE_NAME) ?>">
  <meta property="og:locale"      content="en_US">

  <!-- ── Twitter Card ─────────────────────────────────────────── -->
  <meta name="twitter:card"        content="summary_large_image">
  <meta name="twitter:site"        content="<?= e(SITE_TWITTER) ?>">
  <meta name="twitter:title"       content="<?= e(SITE_NAME) ?> – Download TeraBox Videos in HD for Free">
  <meta name="twitter:description" content="<?= e(SITE_DESCRIPTION) ?>">
  <meta name="twitter:image"       content="<?= e($siteUrl) ?>/assets/img/og-image.png">

  <!-- ── Performance hints ────────────────────────────────────── -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="preconnect" href="https://cdn.jsdelivr.net">
  <link rel="dns-prefetch" href="https://cdnjs.cloudflare.com">

  <!-- ── Bootstrap 5 CSS ──────────────────────────────────────── -->
  <link rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH"
        crossorigin="anonymous">

  <!-- ── Bootstrap Icons ──────────────────────────────────────── -->
  <link rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

  <!-- ── Custom CSS ───────────────────────────────────────────── -->
  <link rel="stylesheet" href="assets/css/style.css">

  <!-- ── Favicon ──────────────────────────────────────────────── -->
  <link rel="icon" type="image/svg+xml" href="assets/img/favicon.svg">
  <link rel="icon" type="image/x-icon"  href="assets/img/favicon.ico">

  <!-- ── JSON-LD Structured Data ──────────────────────────────── -->
  <script type="application/ld+json">
  {
    "@context": "https://schema.org",
    "@type": "WebApplication",
    "name": "<?= e(SITE_NAME) ?>",
    "url": "<?= e($canonicalUrl) ?>",
    "description": "<?= e(SITE_DESCRIPTION) ?>",
    "applicationCategory": "UtilitiesApplication",
    "operatingSystem": "Any",
    "offers": {
      "@type": "Offer",
      "price": "0",
      "priceCurrency": "USD"
    }
  }
  </script>
</head>

<body>

<!-- ════════════════════════════════════════════════════════════
     NAVBAR
════════════════════════════════════════════════════════════ -->
<nav class="navbar navbar-expand-lg fixed-top" id="mainNavbar" aria-label="Main navigation">
  <div class="container">
    <!-- Brand -->
    <a class="navbar-brand" href="/">
      <i class="bi bi-cloud-arrow-down-fill me-1"></i>Tera<span class="brand-dot">Box</span>DL
    </a>

    <!-- Mobile toggler -->
    <button class="navbar-toggler border-0" type="button"
            data-bs-toggle="collapse" data-bs-target="#navbarContent"
            aria-controls="navbarContent" aria-expanded="false"
            aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>

    <!-- Nav links -->
    <div class="collapse navbar-collapse" id="navbarContent">
      <ul class="navbar-nav ms-auto align-items-lg-center gap-1">
        <li class="nav-item"><a class="nav-link" href="#features">Features</a></li>
        <li class="nav-item"><a class="nav-link" href="#how-it-works">How it Works</a></li>
        <li class="nav-item"><a class="nav-link" href="#faq">FAQ</a></li>
        <li class="nav-item ms-lg-2">
          <button class="theme-toggle" id="themeToggle" title="Toggle dark mode" aria-label="Toggle dark mode">
            <i class="bi bi-moon-fill" id="themeIcon"></i>
          </button>
        </li>
      </ul>
    </div>
  </div>
</nav>

<!-- ════════════════════════════════════════════════════════════
     HERO SECTION
════════════════════════════════════════════════════════════ -->
<section class="hero text-center" style="margin-top: var(--navbar-h);" id="home">
  <div class="container">

    <div class="hero-badge">
      <i class="bi bi-lightning-charge-fill"></i>
      Fast · Free · No Registration
    </div>

    <h1 class="hero-title">
      Download <span class="highlight">TeraBox Videos</span><br>
      in HD for Free
    </h1>

    <p class="hero-subtitle mx-auto">
      Instantly download any TeraBox video in your preferred quality — 360p, 480p, 720p, or 1080p.
      No account needed. Works on any device.
    </p>

    <!-- ── Downloader Search Box ──────────────────────────────── -->
    <div class="search-box mx-auto" role="search">
      <i class="bi bi-link-45deg text-muted fs-5 flex-shrink-0"></i>
      <input type="url"
             id="videoUrl"
             placeholder="Paste your TeraBox link here…"
             autocomplete="off"
             spellcheck="false"
             aria-label="TeraBox video URL">
      <button class="btn-download-main" id="downloadBtn" type="button" aria-label="Get Download Link">
        <span id="btn-text"><i class="bi bi-cloud-download-fill"></i> Download</span>
        <span id="btn-spinner" class="d-none">
          <span class="spinner-border" role="status" aria-hidden="true"></span>
          Fetching…
        </span>
      </button>
    </div>

    <!-- Progress bar (shows while loading) -->
    <div class="progress-bar-custom mx-auto mt-3" style="max-width:680px; display:none;" id="progressBar">
      <div class="progress-bar-fill" id="progressFill"></div>
    </div>

    <!-- Paste hint -->
    <p class="mt-2 mb-0" style="font-size:.8rem; color:var(--text-light);">
      <i class="bi bi-clipboard"></i>
      Tip: Press <kbd>Ctrl+V</kbd> to paste, or we'll detect your clipboard automatically.
    </p>

    <!-- ── Stats Strip ────────────────────────────────────────── -->
    <div class="stats-strip">
      <div class="stat-item">
        <span class="stat-number" data-count="1200000">0</span><span class="stat-number">+</span>
        <span class="stat-label">Videos Downloaded</span>
      </div>
      <div class="stat-item">
        <span class="stat-number" data-count="99">0</span><span class="stat-number">%</span>
        <span class="stat-label">Uptime</span>
      </div>
      <div class="stat-item">
        <span class="stat-number" data-count="4">0</span>
        <span class="stat-label">Quality Options</span>
      </div>
      <div class="stat-item">
        <span class="stat-number">0</span>
        <span class="stat-label">Cost (Always Free)</span>
      </div>
    </div>

  </div>
</section>

<!-- ════════════════════════════════════════════════════════════
     ALERT SECTION (errors)
════════════════════════════════════════════════════════════ -->
<div class="container mt-3" id="alert-container" aria-live="polite"></div>

<!-- ════════════════════════════════════════════════════════════
     SKELETON LOADER
════════════════════════════════════════════════════════════ -->
<section class="container my-5" id="skeleton-section" aria-hidden="true">
  <div class="row g-4">
    <div class="col-lg-5">
      <div class="skeleton skeleton-thumb"></div>
    </div>
    <div class="col-lg-7 pt-2">
      <div class="skeleton skeleton-line w-90 mb-3"></div>
      <div class="skeleton skeleton-line w-50 mb-3"></div>
      <div class="skeleton skeleton-line w-75 mb-4"></div>
      <div class="skeleton skeleton-line" style="height:40px; border-radius:12px; width:100%;"></div>
      <div class="mt-3 d-flex gap-2">
        <div class="skeleton" style="height:38px; border-radius:10px; width:120px;"></div>
        <div class="skeleton" style="height:38px; border-radius:10px; width:120px;"></div>
        <div class="skeleton" style="height:38px; border-radius:10px; width:100px;"></div>
      </div>
    </div>
  </div>
</section>


<!-- ════════════════════════════════════════════════════════════
     RESULT SECTION
════════════════════════════════════════════════════════════ -->
<section class="container my-5" id="result-section">
  <div class="result-card">
    <div class="row g-0">

      <!-- Thumbnail -->
      <div class="col-lg-5">
        <div class="result-thumbnail-wrap" id="thumb-wrap">
          <img id="result-thumb"
               class="result-thumbnail"
               alt="Video Thumbnail"
               loading="lazy"
               src="data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMzIwIiBoZWlnaHQ9IjE4MCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjMjAyNjM1Ii8+PC9zdmc+">
          <!-- Clicking the thumbnail opens the dedicated player page (no inline playback on home) -->
          <div class="thumbnail-overlay" id="play-overlay" title="Watch online">
            <div class="play-btn-overlay"><i class="bi bi-play-fill"></i></div>
          </div>
        </div>
      </div>

      <!-- Video Info -->
      <div class="col-lg-7">
        <div class="result-body">
          <h3 class="video-title" id="result-name">Video Title</h3>

          <div class="video-meta">
            <span class="meta-badge"><i class="bi bi-hdd"></i> <span id="result-size">Unknown</span></span>
            <span class="meta-badge"><i class="bi bi-film"></i> <span id="result-quality">HD</span></span>
          </div>

          <!-- Quality selector -->
          <div class="quality-selector">
            <label for="quality-dropdown"><i class="bi bi-sliders me-1"></i>Quality:</label>
            <select id="quality-dropdown" class="quality-select" aria-label="Select quality"></select>
          </div>

          <!-- Action buttons -->
          <div class="action-buttons">
            <a id="btn-stream" class="btn-action btn-secondary-action" href="player.php" rel="noopener">
              <i class="bi bi-play-circle-fill"></i> Watch Online
            </a>
            <a id="btn-dl" class="btn-action btn-primary-action" href="#" target="_blank" rel="noopener" download>
              <i class="bi bi-cloud-arrow-down-fill"></i> Download
            </a>
            <button class="btn-action btn-outline-action" id="btn-copy-dl">
              <i class="bi bi-clipboard"></i> Copy Link
            </button>
            <button class="btn-action btn-outline-action" id="btn-copy-stream">
              <i class="bi bi-clipboard2-pulse"></i> Copy Stream
            </button>
            <button class="btn-action btn-outline-action" id="btn-qr" data-bs-toggle="modal" data-bs-target="#qrModal">
              <i class="bi bi-qr-code"></i> QR
            </button>
          </div>

          <!-- Share buttons -->
          <div class="share-buttons" id="share-buttons">
            <button class="btn-share btn-share-twitter" id="share-twitter"><i class="bi bi-twitter-x"></i> Tweet</button>
            <button class="btn-share btn-share-facebook" id="share-facebook"><i class="bi bi-facebook"></i> Share</button>
            <button class="btn-share btn-share-whatsapp" id="share-whatsapp"><i class="bi bi-whatsapp"></i> Send</button>
          </div>
        </div>
      </div>

    </div>
  </div>
</section>

<!-- ════════════════════════════════════════════════════════════
     RECENT DOWNLOADS (local storage)
════════════════════════════════════════════════════════════ -->
<section class="container mb-5" id="history-section">
  <h5 class="fw-bold mb-3"><i class="bi bi-clock-history me-1"></i> Recent Downloads</h5>
  <div id="history-list"></div>
  <button class="btn btn-sm btn-outline-danger mt-2" id="clear-history">
    <i class="bi bi-trash3"></i> Clear History
  </button>
</section>


<!-- ════════════════════════════════════════════════════════════
     FEATURES SECTION
════════════════════════════════════════════════════════════ -->
<section class="section-pad" id="features">
  <div class="container">
    <div class="text-center mb-5">
      <h2 class="section-title">Why Choose <span class="text-primary-custom">TeraBoxDL</span>?</h2>
      <p class="section-subtitle">Everything you need for seamless TeraBox video downloads — fast, free, and private.</p>
    </div>

    <div class="row g-4">
      <div class="col-sm-6 col-lg-3 animate-fade-in-up animate-delay-1">
        <div class="feature-card">
          <div class="feature-icon"><i class="bi bi-person-x-fill"></i></div>
          <h5>No Registration</h5>
          <p>Start downloading immediately. No account creation or login required.</p>
        </div>
      </div>
      <div class="col-sm-6 col-lg-3 animate-fade-in-up animate-delay-2">
        <div class="feature-card">
          <div class="feature-icon"><i class="bi bi-rocket-takeoff-fill"></i></div>
          <h5>Ultra-Fast</h5>
          <p>Lightning-fast processing. Get your download link in seconds, not minutes.</p>
        </div>
      </div>
      <div class="col-sm-6 col-lg-3 animate-fade-in-up animate-delay-3">
        <div class="feature-card">
          <div class="feature-icon"><i class="bi bi-badge-hd-fill"></i></div>
          <h5>Multiple Qualities</h5>
          <p>Choose 360p, 480p, 720p, or 1080p based on your needs and bandwidth.</p>
        </div>
      </div>
      <div class="col-sm-6 col-lg-3 animate-fade-in-up animate-delay-4">
        <div class="feature-card">
          <div class="feature-icon"><i class="bi bi-infinity"></i></div>
          <h5>Unlimited Downloads</h5>
          <p>No daily limits. Download as many videos as you want, completely free.</p>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ════════════════════════════════════════════════════════════
     HOW IT WORKS SECTION
════════════════════════════════════════════════════════════ -->
<section class="section-pad bg-alt" id="how-it-works">
  <div class="container">
    <div class="text-center mb-5">
      <h2 class="section-title">How It Works</h2>
      <p class="section-subtitle">Just three simple steps to download any TeraBox video.</p>
    </div>

    <div class="row g-4 text-center">
      <div class="col-md-4 animate-fade-in-up animate-delay-1">
        <div class="step-badge">1</div>
        <h5 class="fw-bold mb-2">Paste the Link</h5>
        <p>Copy the TeraBox video URL from your browser and paste it in the input box above.</p>
      </div>
      <div class="col-md-4 animate-fade-in-up animate-delay-2">
        <div class="step-badge">2</div>
        <h5 class="fw-bold mb-2">Choose Quality</h5>
        <p>Select your preferred video quality from the dropdown — up to 1080p HD.</p>
      </div>
      <div class="col-md-4 animate-fade-in-up animate-delay-3">
        <div class="step-badge">3</div>
        <h5 class="fw-bold mb-2">Download</h5>
        <p>Click the Download button or watch online. Your file starts immediately.</p>
      </div>
    </div>
  </div>
</section>

<!-- ════════════════════════════════════════════════════════════
     FAQ SECTION
════════════════════════════════════════════════════════════ -->
<section class="section-pad faq-section" id="faq">
  <div class="container">
    <div class="text-center mb-5">
      <h2 class="section-title">Frequently Asked Questions</h2>
      <p class="section-subtitle">Get answers to the most common questions about TeraBox Downloader.</p>
    </div>

    <div class="row justify-content-center">
      <div class="col-lg-8">
        <div class="accordion" id="faqAccordion">

          <div class="accordion-item">
            <h3 class="accordion-header">
              <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#faq-1" aria-expanded="true" aria-controls="faq-1">
                Is TeraBox Downloader free to use?
              </button>
            </h3>
            <div id="faq-1" class="accordion-collapse collapse show" data-bs-parent="#faqAccordion">
              <div class="accordion-body">Yes, completely free. There are no hidden charges, no premium plans, and no limitations on the number of downloads.</div>
            </div>
          </div>

          <div class="accordion-item">
            <h3 class="accordion-header">
              <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq-2" aria-expanded="false" aria-controls="faq-2">
                Do I need to create an account?
              </button>
            </h3>
            <div id="faq-2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
              <div class="accordion-body">No, you do not need to register or sign in. Just paste your link and start downloading immediately.</div>
            </div>
          </div>

          <div class="accordion-item">
            <h3 class="accordion-header">
              <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq-3" aria-expanded="false" aria-controls="faq-3">
                What video qualities are supported?
              </button>
            </h3>
            <div id="faq-3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
              <div class="accordion-body">We support multiple quality options depending on what's available: 360p, 480p, 720p, and 1080p. You can choose your preferred quality before downloading.</div>
            </div>
          </div>

          <div class="accordion-item">
            <h3 class="accordion-header">
              <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq-4" aria-expanded="false" aria-controls="faq-4">
                Is it safe to use?
              </button>
            </h3>
            <div id="faq-4" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
              <div class="accordion-body">Yes. We don't store your personal data, your links, or your videos. All processing happens in real-time and nothing is saved on our servers.</div>
            </div>
          </div>

          <div class="accordion-item">
            <h3 class="accordion-header">
              <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq-5" aria-expanded="false" aria-controls="faq-5">
                Does it work on mobile devices?
              </button>
            </h3>
            <div id="faq-5" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
              <div class="accordion-body">Absolutely! Our site is fully responsive and works on all smartphones, tablets, and desktop computers.</div>
            </div>
          </div>

          <div class="accordion-item">
            <h3 class="accordion-header">
              <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq-6" aria-expanded="false" aria-controls="faq-6">
                Why is my download link not working?
              </button>
            </h3>
            <div id="faq-6" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
              <div class="accordion-body">Download links may expire after some time. If your link doesn't work, simply paste the original TeraBox URL again to get a fresh link. Also ensure the original video hasn't been deleted or made private.</div>
            </div>
          </div>

        </div>
      </div>
    </div>
  </div>
</section>


<!-- ════════════════════════════════════════════════════════════
     FOOTER
════════════════════════════════════════════════════════════ -->
<footer class="section-pad pb-4">
  <div class="container">
    <div class="row g-4">

      <div class="col-lg-4 mb-3">
        <div class="footer-brand mb-2">
          <i class="bi bi-cloud-arrow-down-fill me-1"></i>TeraBoxDL
        </div>
        <p style="font-size:.875rem; max-width:300px;">
          The fastest and safest way to download TeraBox videos for free. No limits, no registration, just paste and download.
        </p>
      </div>

      <div class="col-6 col-lg-2 footer-links">
        <h6 class="fw-bold mb-3">Product</h6>
        <ul class="list-unstyled d-grid gap-2">
          <li><a href="#home">Download</a></li>
          <li><a href="#features">Features</a></li>
          <li><a href="#how-it-works">How it Works</a></li>
          <li><a href="#faq">FAQ</a></li>
        </ul>
      </div>

      <div class="col-6 col-lg-2 footer-links">
        <h6 class="fw-bold mb-3">Legal</h6>
        <ul class="list-unstyled d-grid gap-2">
          <li><a href="#">Privacy Policy</a></li>
          <li><a href="#">Terms of Service</a></li>
          <li><a href="#">DMCA</a></li>
          <li><a href="#">Contact</a></li>
        </ul>
      </div>

      <div class="col-lg-4 footer-links">
        <h6 class="fw-bold mb-3">Connect</h6>
        <p style="font-size:.875rem;">Have questions or feedback? Reach out to us:</p>
        <a href="mailto:<?= e(SITE_EMAIL) ?>" class="d-inline-flex align-items-center gap-2" style="font-size:.875rem;">
          <i class="bi bi-envelope-fill text-primary-custom"></i> <?= e(SITE_EMAIL) ?>
        </a>
      </div>

    </div>

    <hr class="footer-divider">

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
      <p class="footer-copy mb-0">&copy; <?= date('Y') ?> <?= e(SITE_NAME) ?>. All rights reserved.</p>
      <p class="footer-copy mb-0">Made with <i class="bi bi-heart-fill text-danger"></i> for the community.</p>
    </div>
  </div>
</footer>

<!-- ════════════════════════════════════════════════════════════
     QR CODE MODAL
════════════════════════════════════════════════════════════ -->
<div class="modal fade" id="qrModal" tabindex="-1" aria-labelledby="qrModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-sm modal-dialog-centered">
    <div class="modal-content text-center">
      <div class="modal-header border-0 pb-0">
        <h5 class="modal-title fw-bold" id="qrModalLabel"><i class="bi bi-qr-code me-1"></i> QR Code</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body py-4">
        <canvas id="qr-canvas" width="200" height="200" style="max-width:100%;"></canvas>
        <p class="mt-2 text-muted" style="font-size:.8rem;">Scan to open this video on your phone</p>
      </div>
    </div>
  </div>
</div>

<!-- ════════════════════════════════════════════════════════════
     TOAST NOTIFICATIONS
════════════════════════════════════════════════════════════ -->
<div class="toast-container position-fixed top-0 end-0 p-3" id="toast-container">
  <div class="toast" id="app-toast" role="alert" aria-live="assertive" aria-atomic="true">
    <div class="toast-header">
      <i class="bi bi-check-circle-fill text-success me-2" id="toast-icon"></i>
      <strong class="me-auto" id="toast-title">Success</strong>
      <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
    </div>
    <div class="toast-body" id="toast-body">Action completed successfully.</div>
  </div>
</div>

<!-- ── Scroll to top button ───────────────────────────────────── -->
<button id="scroll-top" aria-label="Scroll to top"><i class="bi bi-chevron-up"></i></button>

<!-- ── CSRF token (hidden, used by JS) ────────────────────────── -->
<input type="hidden" id="csrf-token" value="<?= e($csrfToken) ?>">

<!-- ════════════════════════════════════════════════════════════
     SCRIPTS
════════════════════════════════════════════════════════════ -->
<!-- Bootstrap 5 JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz"
        crossorigin="anonymous" defer></script>

<!-- QRCode library (lightweight) -->
<script src="https://cdn.jsdelivr.net/npm/qrcode@1.5.3/build/qrcode.min.js" defer></script>

<!-- App JS -->
<script src="assets/js/app.js" defer></script>

</body>
</html>
