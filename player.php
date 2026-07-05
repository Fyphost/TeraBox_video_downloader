<?php
/**
 * TeraBox Video Downloader – Dedicated Player Page
 * A standalone page for watching a TeraBox video with full controls.
 *
 * Data handoff (in priority order):
 *   1. sessionStorage key "tbdl_player" (instant playback from index.php)
 *   2. ?v=<terabox_url> query param (re-fetches via api/download.php)
 */
require_once __DIR__ . '/config.php';

$csrfToken    = csrf_token();
$siteUrl      = rtrim(SITE_URL, '/');

// Accept either a friendly /video/{id} route (?id=) or a legacy ?v=<url>.
$videoId   = isset($_GET['id']) ? preg_replace('/[^A-Za-z0-9_-]/', '', (string) $_GET['id']) : '';
$sourceUrl = isset($_GET['v'])  ? trim((string) $_GET['v']) : '';

if ($videoId !== '' && $sourceUrl === '') {
    // Reconstruct the TeraBox URL from the clean id so the API can be queried.
    $sourceUrl = terabox_url_from_id($videoId);
} elseif ($videoId === '' && $sourceUrl !== '') {
    // Derive a clean id from the raw URL so share/QR links stay pretty.
    $videoId = terabox_id_from_url($sourceUrl);
}

$canonicalUrl = $videoId !== '' ? ($siteUrl . '/video/' . $videoId) : ($siteUrl . '/player.php');
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">

  <!-- ── SEO (player is dynamic, keep it out of the index) ────── -->
  <title>Video Player – <?= e(SITE_NAME) ?></title>
  <meta name="description" content="Watch your TeraBox video online in HD with our free streaming player.">
  <meta name="robots" content="noindex, follow">
  <link rel="canonical" href="<?= e($canonicalUrl) ?>">

  <!-- ── Open Graph ───────────────────────────────────────────── -->
  <meta property="og:type"        content="video.other">
  <meta property="og:title"       content="Video Player – <?= e(SITE_NAME) ?>">
  <meta property="og:description" content="Watch your TeraBox video online in HD.">
  <meta property="og:site_name"   content="<?= e(SITE_NAME) ?>">

  <!-- ── Performance hints ────────────────────────────────────── -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="preconnect" href="https://cdn.jsdelivr.net">

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
</head>

<body class="player-page">

<!-- ════════════════════════════════════════════════════════════
     NAVBAR
════════════════════════════════════════════════════════════ -->
<nav class="navbar navbar-expand-lg fixed-top" id="mainNavbar" aria-label="Main navigation">
  <div class="container">
    <a class="navbar-brand" href="index.php">
      <i class="bi bi-cloud-arrow-down-fill me-1"></i>Tera<span class="brand-dot">Box</span>DL
    </a>
    <div class="d-flex align-items-center gap-2 ms-auto">
      <a href="index.php" class="btn-action btn-secondary-action d-none d-sm-inline-flex">
        <i class="bi bi-arrow-left"></i> Back to Downloader
      </a>
      <a href="index.php" class="theme-toggle d-inline-flex d-sm-none" title="Back" aria-label="Back to downloader">
        <i class="bi bi-arrow-left"></i>
      </a>
      <button class="theme-toggle" id="themeToggle" title="Toggle dark mode" aria-label="Toggle dark mode">
        <i class="bi bi-moon-fill" id="themeIcon"></i>
      </button>
    </div>
  </div>
</nav>

<!-- Hidden state for JS -->
<input type="hidden" id="csrf-token" value="<?= e($csrfToken) ?>">
<input type="hidden" id="source-url" value="<?= e($sourceUrl) ?>">
<input type="hidden" id="video-id"   value="<?= e($videoId) ?>">


<!-- ════════════════════════════════════════════════════════════
     PLAYER MAIN
════════════════════════════════════════════════════════════ -->
<main class="player-main" style="margin-top: var(--navbar-h);">
  <div class="container py-4">

    <!-- ── Loading skeleton ──────────────────────────────────── -->
    <div id="player-skeleton">
      <div class="skeleton" style="aspect-ratio:16/9; width:100%; border-radius:var(--radius-lg);"></div>
      <div class="skeleton skeleton-line w-50 mt-4"></div>
      <div class="skeleton skeleton-line w-25 mt-2"></div>
    </div>

    <!-- ── Error state ───────────────────────────────────────── -->
    <div id="player-error" class="text-center py-5" style="display:none;">
      <div class="player-error-icon"><i class="bi bi-exclamation-triangle-fill"></i></div>
      <h3 class="fw-bold mt-3 mb-2" id="player-error-title">Video Unavailable</h3>
      <p class="text-muted mb-4" id="player-error-msg">We couldn't load this video. It may be private, deleted, or the link has expired.</p>
      <a href="index.php" class="btn-action btn-primary-action d-inline-flex">
        <i class="bi bi-house-door-fill"></i> Back to Downloader
      </a>
    </div>

    <!-- ── Player content ────────────────────────────────────── -->
    <div id="player-content" style="display:none;">

      <!-- Video player -->
      <div class="player-stage">
        <video id="player-video" controls preload="metadata" playsinline
               poster="data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIxNjAwIiBoZWlnaHQ9IjkwMCI+PHJlY3Qgd2lkdGg9IjEwMCUiIGhlaWdodD0iMTAwJSIgZmlsbD0iIzAwMCIvPjwvc3ZnPg==">
          Your browser does not support the video element.
        </video>
      </div>

      <!-- Info + actions -->
      <div class="player-info-bar">
        <div class="row g-4 align-items-start">

          <!-- Title + meta -->
          <div class="col-lg-7">
            <h1 class="player-title" id="player-title">Loading…</h1>
            <div class="video-meta mt-2">
              <span class="meta-badge"><i class="bi bi-hdd"></i> <span id="player-size">Unknown</span></span>
              <span class="meta-badge"><i class="bi bi-film"></i> <span id="player-quality">HD</span></span>
              <span class="meta-badge"><i class="bi bi-broadcast"></i> Streaming</span>
            </div>

            <!-- Share -->
            <div class="share-buttons" id="share-buttons">
              <button class="btn-share btn-share-twitter" id="share-twitter"><i class="bi bi-twitter-x"></i> Tweet</button>
              <button class="btn-share btn-share-facebook" id="share-facebook"><i class="bi bi-facebook"></i> Share</button>
              <button class="btn-share btn-share-whatsapp" id="share-whatsapp"><i class="bi bi-whatsapp"></i> Send</button>
            </div>
          </div>

          <!-- Controls -->
          <div class="col-lg-5">
            <div class="player-controls-card">
              <!-- Quality selector -->
              <div class="quality-selector mb-3">
                <label for="quality-dropdown"><i class="bi bi-sliders me-1"></i>Quality:</label>
                <select id="quality-dropdown" class="quality-select" aria-label="Select quality"></select>
              </div>

              <!-- Actions -->
              <div class="action-buttons">
                <a id="btn-dl" class="btn-action btn-primary-action" href="#" target="_blank" rel="noopener" download>
                  <i class="bi bi-cloud-arrow-down-fill"></i> Download
                </a>
                <button class="btn-action btn-secondary-action" id="btn-copy-dl">
                  <i class="bi bi-clipboard"></i> Copy Link
                </button>
                <button class="btn-action btn-outline-action" id="btn-copy-stream">
                  <i class="bi bi-clipboard2-pulse"></i> Copy Stream
                </button>
                <button class="btn-action btn-outline-action" id="btn-qr" data-bs-toggle="modal" data-bs-target="#qrModal">
                  <i class="bi bi-qr-code"></i> QR
                </button>
              </div>
            </div>
          </div>

        </div>
      </div>
    </div>

  </div>
</main>


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
        <div id="qr-code" class="d-inline-block"></div>
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

<!-- ════════════════════════════════════════════════════════════
     FOOTER
════════════════════════════════════════════════════════════ -->
<footer class="py-4 mt-5">
  <div class="container">
    <hr class="footer-divider">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
      <p class="footer-copy mb-0">&copy; <?= date('Y') ?> <?= e(SITE_NAME) ?>. All rights reserved.</p>
      <a href="index.php" class="footer-copy mb-0"><i class="bi bi-arrow-left me-1"></i>Back to Downloader</a>
    </div>
  </div>
</footer>

<!-- ════════════════════════════════════════════════════════════
     SCRIPTS
════════════════════════════════════════════════════════════ -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz"
        crossorigin="anonymous" defer></script>
<script src="https://cdn.jsdelivr.net/npm/hls.js@1.5.7/dist/hls.min.js" defer></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js" defer></script>
<script src="assets/js/player.js" defer></script>

</body>
</html>
