/**
 * TeraBox Video Downloader – Dedicated Player Page JavaScript
 * Loads video data via sessionStorage handoff (from index.php) or by
 * re-fetching from api/download.php using the ?v= query parameter.
 *
 * Vanilla JS + Bootstrap 5 + HLS.js.
 */

'use strict';

// ─── Constants ───────────────────────────────────────────────────────────────
const THEME_KEY     = 'tbdl_theme';
const HANDOFF_KEY   = 'tbdl_player';
const API_ENDPOINT  = 'api/download.php';

// ─── State ───────────────────────────────────────────────────────────────────
let currentData = null;
let hlsInstance = null;

// ─── DOM helpers ─────────────────────────────────────────────────────────────
const $ = (id) => document.getElementById(id);

// ─── Init ────────────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
  initTheme();
  initEventListeners();
  initPlayerData();

  // Generate QR when the modal opens
  const qrModal = $('qrModal');
  if (qrModal) qrModal.addEventListener('shown.bs.modal', generateQR);
});

// ═══════════════════════════════════════════════════════════════════════════════
// THEME
// ═══════════════════════════════════════════════════════════════════════════════

function initTheme() {
  const saved = localStorage.getItem(THEME_KEY);
  const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
  applyTheme(saved || (prefersDark ? 'dark' : 'light'));
}

function applyTheme(theme) {
  document.documentElement.setAttribute('data-theme', theme);
  localStorage.setItem(THEME_KEY, theme);
  const icon = $('themeIcon');
  if (icon) icon.className = theme === 'dark' ? 'bi bi-sun-fill' : 'bi bi-moon-fill';
}

function toggleTheme() {
  const current = document.documentElement.getAttribute('data-theme');
  applyTheme(current === 'dark' ? 'light' : 'dark');
}

// ═══════════════════════════════════════════════════════════════════════════════
// EVENT LISTENERS
// ═══════════════════════════════════════════════════════════════════════════════

function initEventListeners() {
  $('themeToggle')?.addEventListener('click', toggleTheme);

  $('quality-dropdown')?.addEventListener('change', handleQualityChange);

  // Friendly feedback if a source can't be played inline (CORS, format, expiry)
  const video = $('player-video');
  if (video) {
    video.addEventListener('error', () => {
      // Ignore the transient error fired when the src is reset between sources
      if (!video.currentSrc) return;
      showToast('Playback Error',
        'This source could not be played here. Try another quality or use Download.',
        'danger');
    });
  }

  $('btn-copy-dl')?.addEventListener('click', () => {
    copyToClipboard($('btn-dl')?.href, 'Download link copied!');
  });
  $('btn-copy-stream')?.addEventListener('click', () => {
    copyToClipboard(playerStreamUrl(), 'Stream link copied!');
  });

  $('share-twitter')?.addEventListener('click', () => shareOn('twitter'));
  $('share-facebook')?.addEventListener('click', () => shareOn('facebook'));
  $('share-whatsapp')?.addEventListener('click', () => shareOn('whatsapp'));
}


// ═══════════════════════════════════════════════════════════════════════════════
// DATA LOADING (handoff or re-fetch)
// ═══════════════════════════════════════════════════════════════════════════════

async function initPlayerData() {
  // 1. Try instant handoff from index.php via sessionStorage
  const handoff = readHandoff();
  if (handoff && (handoff.stream || handoff.download)) {
    currentData = handoff;
    renderPlayer(currentData);
    return;
  }

  // 2. Fall back to re-fetching from the API using ?v= param
  const sourceUrl = $('source-url')?.value.trim();
  if (!sourceUrl) {
    showError('No Video Selected', 'Open a video from the downloader page to start watching.');
    return;
  }

  await fetchVideo(sourceUrl);
}

function readHandoff() {
  try {
    const raw = sessionStorage.getItem(HANDOFF_KEY);
    if (!raw) return null;
    // Handoff is single-use – clear after reading
    sessionStorage.removeItem(HANDOFF_KEY);
    const parsed = JSON.parse(raw);
    return parsed && parsed.data ? parsed.data : null;
  } catch { return null; }
}

async function fetchVideo(sourceUrl) {
  showSkeleton();

  try {
    const csrfToken = $('csrf-token')?.value || '';

    const response = await fetch(API_ENDPOINT, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
      body: JSON.stringify({ url: sourceUrl, csrf_token: csrfToken }),
      signal: AbortSignal.timeout(30000),
    });

    const json = await response.json();

    if (!response.ok || !json.success) {
      throw new Error(json.error || `Server error (HTTP ${response.status})`);
    }

    currentData = json.data;
    renderPlayer(currentData);

  } catch (err) {
    let msg = 'We couldn\'t load this video. It may be private, deleted, or the link has expired.';
    if (err.name === 'TimeoutError' || err.name === 'AbortError') {
      msg = 'The request timed out. The server may be busy — please try again.';
    } else if (err.message) {
      msg = err.message;
    }
    showError('Video Unavailable', msg);
  }
}

// ═══════════════════════════════════════════════════════════════════════════════
// RENDER
// ═══════════════════════════════════════════════════════════════════════════════

function renderPlayer(data) {
  // Title / meta
  const title = data.name || 'Untitled Video';
  $('player-title').textContent = title;
  $('player-size').textContent  = data.size || 'Unknown';
  $('player-quality').textContent = data.quality || 'HD';
  document.title = title + ' – Player';

  // Quality dropdown
  populateQualityDropdown(data);

  // Buttons
  const dlUrl = getCurrentDownloadUrl(data);
  const btnDl = $('btn-dl');
  if (btnDl) {
    btnDl.href = sanitizeUrl(dlUrl);
    if (data.name) btnDl.setAttribute('download', data.name);
  }

  // Show content, hide skeleton/error
  $('player-skeleton').style.display = 'none';
  $('player-error').style.display    = 'none';
  $('player-content').style.display  = 'block';

  // Start the stream
  loadStream(playerStreamUrl());
}

function populateQualityDropdown(data) {
  const dropdown = $('quality-dropdown');
  if (!dropdown) return;
  dropdown.innerHTML = '';

  // "Auto" — the main streaming URL. This is the source best suited for inline
  // playback (the `download` link is often not playable inline), so it is the
  // default selection and what the player loads first.
  const autoUrl = data.stream || data.download;
  if (autoUrl) {
    const opt = document.createElement('option');
    opt.value = autoUrl;
    opt.textContent = `Auto (${data.quality || 'HD'})`;
    dropdown.appendChild(opt);
  }

  // Per-quality streaming sources
  if (data.streams && typeof data.streams === 'object') {
    Object.entries(data.streams).forEach(([quality, url]) => {
      if (url) {
        const opt = document.createElement('option');
        opt.value = url;
        opt.textContent = quality;
        dropdown.appendChild(opt);
      }
    });
  }

  // Absolute fallback if nothing else is available
  if (!dropdown.options.length && autoUrl) {
    const opt = document.createElement('option');
    opt.value = autoUrl;
    opt.textContent = data.quality || 'Default';
    dropdown.appendChild(opt);
  }

  dropdown.selectedIndex = 0;
}

function handleQualityChange() {
  const selectedUrl = $('quality-dropdown')?.value;
  if (!selectedUrl) return;

  // Changing quality only swaps the playback source. The Download button keeps
  // pointing at the direct download link so users always get a saveable file.
  loadStream(selectedUrl);
}

function getCurrentDownloadUrl(data) {
  if (data.download) return data.download;
  if (data.streams && typeof data.streams === 'object') {
    const keys = Object.keys(data.streams);
    if (keys.length) return data.streams[keys[keys.length - 1]];
  }
  return data.stream || '#';
}

// Currently selected stream/source URL for the player
function playerStreamUrl() {
  const selected = $('quality-dropdown')?.value;
  if (selected) return selected;
  return currentData?.stream || currentData?.download || '';
}


// ═══════════════════════════════════════════════════════════════════════════════
// VIDEO PLAYBACK (HLS.js + native fallback)
// ═══════════════════════════════════════════════════════════════════════════════

function loadStream(streamUrl) {
  const video = $('player-video');
  if (!video || !streamUrl) return;

  // Preserve current playback position when switching quality
  const resumeAt = video.currentTime || 0;
  const wasPlaying = !video.paused && !video.ended;

  // Tear down any previous HLS instance
  if (hlsInstance) {
    hlsInstance.destroy();
    hlsInstance = null;
  }

  const isHLS = /\.m3u8($|\?)/i.test(streamUrl) || /m3u8/i.test(streamUrl);

  if (isHLS && window.Hls && Hls.isSupported()) {
    // HLS.js path
    hlsInstance = new Hls({ maxBufferLength: 30, maxMaxBufferLength: 60, startLevel: -1 });
    hlsInstance.loadSource(streamUrl);
    hlsInstance.attachMedia(video);
    hlsInstance.on(Hls.Events.MANIFEST_PARSED, () => restorePosition(video, resumeAt, wasPlaying));
    hlsInstance.on(Hls.Events.ERROR, (_, data) => {
      if (data.fatal) {
        switch (data.type) {
          case Hls.ErrorTypes.NETWORK_ERROR: hlsInstance.startLoad(); break;
          case Hls.ErrorTypes.MEDIA_ERROR:   hlsInstance.recoverMediaError(); break;
          default:
            showToast('Player Error', 'Failed to load the video stream.', 'danger');
            break;
        }
      }
    });
  } else if (isHLS && video.canPlayType('application/vnd.apple.mpegurl')) {
    // Native HLS (Safari / iOS)
    video.src = streamUrl;
    video.addEventListener('loadedmetadata', () => restorePosition(video, resumeAt, wasPlaying), { once: true });
  } else {
    // Direct playback (mp4 etc.)
    video.src = sanitizeUrl(streamUrl);
    video.addEventListener('loadedmetadata', () => restorePosition(video, resumeAt, wasPlaying), { once: true });
  }
}

function restorePosition(video, resumeAt, wasPlaying) {
  // Autoplay stays disabled on first load; only resume if user was already playing
  if (resumeAt > 0 && resumeAt < (video.duration || Infinity)) {
    try { video.currentTime = resumeAt; } catch { /* ignore */ }
  }
  if (wasPlaying) {
    video.play().catch(() => { /* autoplay blocked – ignore */ });
  }
}

// ═══════════════════════════════════════════════════════════════════════════════
// STATE VIEWS
// ═══════════════════════════════════════════════════════════════════════════════

function showSkeleton() {
  $('player-skeleton').style.display = 'block';
  $('player-error').style.display    = 'none';
  $('player-content').style.display  = 'none';
}

function showError(title, message) {
  $('player-skeleton').style.display = 'none';
  $('player-content').style.display  = 'none';
  const errEl = $('player-error');
  if (errEl) {
    $('player-error-title').textContent = title;
    $('player-error-msg').textContent   = message;
    errEl.style.display = 'block';
  }
}

// ═══════════════════════════════════════════════════════════════════════════════
// SHARE / QR / CLIPBOARD / TOAST / UTILS
// ═══════════════════════════════════════════════════════════════════════════════

// Build a shareable link that points at OUR system (this player page for the
// current video) — never the raw third-party download link.
function getSystemShareUrl() {
  const origin = window.location.origin;
  const path   = window.location.pathname;          // e.g. /player.php
  const source = $('source-url')?.value?.trim() || '';
  if (source) return origin + path + '?v=' + encodeURIComponent(source);
  // The current URL already contains ?v= when opened from the downloader
  return window.location.href;
}

function shareOn(platform) {
  const shareTarget = getSystemShareUrl();           // our system URL, not the direct link
  const text  = `Watch & download this TeraBox video in HD: ${currentData?.name || 'Video'}`;
  const encoded = encodeURIComponent(shareTarget);
  const encodedText = encodeURIComponent(text);

  let shareUrl = '';
  switch (platform) {
    case 'twitter':  shareUrl = `https://twitter.com/intent/tweet?text=${encodedText}&url=${encoded}`; break;
    case 'facebook': shareUrl = `https://www.facebook.com/sharer/sharer.php?u=${encoded}`; break;
    case 'whatsapp': shareUrl = `https://wa.me/?text=${encodedText}%20${encoded}`; break;
  }
  if (shareUrl) window.open(shareUrl, '_blank', 'width=600,height=400');
}

function generateQR() {
  const canvas = $('qr-canvas');
  if (!canvas) return;

  // Encode the short system URL (NOT the long direct download link, which can
  // overflow QR capacity and cause generation to fail silently).
  const qrTarget = getSystemShareUrl();

  if (typeof QRCode === 'undefined') {
    console.error('QRCode library not loaded.');
    showToast('QR Unavailable', 'Could not load the QR generator. Please try again.', 'warning');
    return;
  }

  QRCode.toCanvas(canvas, qrTarget, {
    width: 200,
    margin: 2,
    errorCorrectionLevel: 'M',
    color: { dark: '#0d6efd', light: '#ffffff' },
  }, (err) => {
    if (err) {
      console.error('QR error:', err);
      showToast('QR Error', 'Failed to generate the QR code.', 'danger');
    }
  });
}

async function copyToClipboard(text, successMsg) {
  if (!text || text === '#') {
    showToast('Error', 'No link available to copy.', 'warning');
    return;
  }
  try {
    await navigator.clipboard.writeText(text);
    showToast('Copied!', successMsg || 'Link copied to clipboard.', 'success');
  } catch {
    // Fallback for older browsers
    const ta = document.createElement('textarea');
    ta.value = text;
    ta.style.position = 'fixed';
    ta.style.opacity = '0';
    document.body.appendChild(ta);
    ta.select();
    try {
      document.execCommand('copy');
      showToast('Copied!', successMsg || 'Link copied to clipboard.', 'success');
    } catch {
      showToast('Error', 'Failed to copy. Please copy manually.', 'danger');
    }
    document.body.removeChild(ta);
  }
}

function showToast(title, body, type = 'success') {
  const toastEl = $('app-toast');
  if (!toastEl) return;
  const icons = {
    success: 'bi-check-circle-fill text-success',
    danger:  'bi-x-circle-fill text-danger',
    warning: 'bi-exclamation-circle-fill text-warning',
    info:    'bi-info-circle-fill text-info',
  };
  const iconEl = $('toast-icon');
  if (iconEl)      iconEl.className = `bi ${icons[type] || icons.success} me-2`;
  if ($('toast-title')) $('toast-title').textContent = title;
  if ($('toast-body'))  $('toast-body').textContent  = body;
  new bootstrap.Toast(toastEl, { delay: 4000 }).show();
}

function sanitizeUrl(url) {
  if (!url) return '#';
  try {
    const parsed = new URL(url, window.location.origin);
    if (['http:', 'https:'].includes(parsed.protocol)) return url;
    return '#';
  } catch {
    if (url.startsWith('/') || url.startsWith('./')) return url;
    return '#';
  }
}
