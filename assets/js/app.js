/**
 * TeraBox Video Downloader – Main Application JavaScript
 * Vanilla JS with Bootstrap 5 integration.
 * Features: API fetch, HLS playback, dark mode, clipboard, history, QR codes.
 */

'use strict';

// ─── DOM References ──────────────────────────────────────────────────────────
const DOM = {
  // Main inputs / buttons
  videoUrl:       () => document.getElementById('videoUrl'),
  downloadBtn:    () => document.getElementById('downloadBtn'),
  btnText:        () => document.getElementById('btn-text'),
  btnSpinner:     () => document.getElementById('btn-spinner'),
  csrfToken:      () => document.getElementById('csrf-token'),
  progressBar:    () => document.getElementById('progressBar'),
  progressFill:   () => document.getElementById('progressFill'),

  // Sections
  resultSection:  () => document.getElementById('result-section'),
  skeletonSection:() => document.getElementById('skeleton-section'),
  alertContainer: () => document.getElementById('alert-container'),
  historySection: () => document.getElementById('history-section'),
  historyList:    () => document.getElementById('history-list'),
  videoPlayerSec: () => document.getElementById('video-player-section'),
  videoPlayer:    () => document.getElementById('video-player'),

  // Result elements
  resultThumb:    () => document.getElementById('result-thumb'),
  resultName:     () => document.getElementById('result-name'),
  resultSize:     () => document.getElementById('result-size'),
  resultQuality:  () => document.getElementById('result-quality'),
  qualityDropdown:() => document.getElementById('quality-dropdown'),
  btnStream:      () => document.getElementById('btn-stream'),
  btnDl:          () => document.getElementById('btn-dl'),
  btnCopyDl:      () => document.getElementById('btn-copy-dl'),
  btnCopyStream:  () => document.getElementById('btn-copy-stream'),
  btnQr:          () => document.getElementById('btn-qr'),
  playOverlay:    () => document.getElementById('play-overlay'),

  // Share
  shareTwitter:   () => document.getElementById('share-twitter'),
  shareFacebook:  () => document.getElementById('share-facebook'),
  shareWhatsapp:  () => document.getElementById('share-whatsapp'),

  // Theme
  themeToggle:    () => document.getElementById('themeToggle'),
  themeIcon:      () => document.getElementById('themeIcon'),

  // Other
  scrollTop:      () => document.getElementById('scroll-top'),
  navbar:         () => document.getElementById('mainNavbar'),
  clearHistory:   () => document.getElementById('clear-history'),
  qrCanvas:       () => document.getElementById('qr-canvas'),
};

// ─── State ───────────────────────────────────────────────────────────────────
let currentData = null;   // Holds latest fetched video data
let hlsInstance = null;   // HLS.js instance

// ─── Constants ───────────────────────────────────────────────────────────────
const HISTORY_KEY     = 'tbdl_history';
const THEME_KEY       = 'tbdl_theme';
const MAX_HISTORY     = 10;
const API_ENDPOINT    = 'api/download.php';


// ─── Initialize on DOM Ready ─────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
  initTheme();
  initEventListeners();
  initScrollEffects();
  initCounterAnimation();
  renderHistory();
  initClipboardDetection();
});

// ═══════════════════════════════════════════════════════════════════════════════
// THEME (Dark/Light Mode)
// ═══════════════════════════════════════════════════════════════════════════════

function initTheme() {
  const saved = localStorage.getItem(THEME_KEY);
  const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
  const theme = saved || (prefersDark ? 'dark' : 'light');
  applyTheme(theme);
}

function applyTheme(theme) {
  document.documentElement.setAttribute('data-theme', theme);
  localStorage.setItem(THEME_KEY, theme);
  const icon = DOM.themeIcon();
  if (icon) {
    icon.className = theme === 'dark' ? 'bi bi-sun-fill' : 'bi bi-moon-fill';
  }
}

function toggleTheme() {
  const current = document.documentElement.getAttribute('data-theme');
  applyTheme(current === 'dark' ? 'light' : 'dark');
}

// ═══════════════════════════════════════════════════════════════════════════════
// EVENT LISTENERS
// ═══════════════════════════════════════════════════════════════════════════════

function initEventListeners() {
  // Theme toggle
  DOM.themeToggle()?.addEventListener('click', toggleTheme);

  // Download button click
  DOM.downloadBtn()?.addEventListener('click', handleDownload);

  // Enter key submits
  DOM.videoUrl()?.addEventListener('keydown', (e) => {
    if (e.key === 'Enter') {
      e.preventDefault();
      handleDownload();
    }
  });

  // Copy buttons
  DOM.btnCopyDl()?.addEventListener('click', () => {
    copyToClipboard(DOM.btnDl()?.href, 'Download link copied!');
  });
  DOM.btnCopyStream()?.addEventListener('click', () => {
    copyToClipboard(DOM.btnStream()?.href, 'Stream link copied!');
  });

  // Play overlay click → show video player
  DOM.playOverlay()?.addEventListener('click', () => showVideoPlayer());
  DOM.resultThumb()?.addEventListener('click', () => showVideoPlayer());

  // Quality change
  DOM.qualityDropdown()?.addEventListener('change', handleQualityChange);

  // Share buttons
  DOM.shareTwitter()?.addEventListener('click', () => shareOn('twitter'));
  DOM.shareFacebook()?.addEventListener('click', () => shareOn('facebook'));
  DOM.shareWhatsapp()?.addEventListener('click', () => shareOn('whatsapp'));

  // Clear history
  DOM.clearHistory()?.addEventListener('click', clearHistory);

  // Scroll to top
  DOM.scrollTop()?.addEventListener('click', () => {
    window.scrollTo({ top: 0, behavior: 'smooth' });
  });
}


// ═══════════════════════════════════════════════════════════════════════════════
// MAIN DOWNLOAD HANDLER
// ═══════════════════════════════════════════════════════════════════════════════

async function handleDownload() {
  const urlInput = DOM.videoUrl();
  const url = urlInput?.value.trim();

  // Clear previous results / errors
  hideAlert();
  hideResult();
  hideVideoPlayer();

  // Client-side validation (server also validates)
  if (!url) {
    showAlert('Please enter a TeraBox video URL.', 'warning');
    urlInput?.focus();
    return;
  }

  if (!isValidUrl(url)) {
    showAlert('Please enter a valid URL starting with http:// or https://', 'warning');
    urlInput?.focus();
    return;
  }

  // Show loading state
  setLoading(true);

  try {
    const csrfToken = DOM.csrfToken()?.value || '';

    const response = await fetch(API_ENDPOINT, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
      body: JSON.stringify({ url: url, csrf_token: csrfToken }),
      signal: AbortSignal.timeout(30000), // 30s timeout
    });

    const json = await response.json();

    if (!response.ok || !json.success) {
      throw new Error(json.error || `Server error (HTTP ${response.status})`);
    }

    // Success – display result
    currentData = json.data;
    displayResult(currentData);
    addToHistory(currentData, url);
    showToast('Success!', 'Video link fetched successfully.', 'success');

  } catch (err) {
    handleFetchError(err);
  } finally {
    setLoading(false);
  }
}

// ═══════════════════════════════════════════════════════════════════════════════
// DISPLAY RESULT
// ═══════════════════════════════════════════════════════════════════════════════

function displayResult(data) {
  const section = DOM.resultSection();
  if (!section) return;

  // Thumbnail
  const thumb = DOM.resultThumb();
  if (thumb && data.thumbnail) {
    thumb.src = sanitizeUrl(data.thumbnail);
    thumb.alt = escapeHtml(data.name || 'Video Thumbnail');
  }

  // Name
  const nameEl = DOM.resultName();
  if (nameEl) nameEl.textContent = data.name || 'Untitled Video';

  // Size
  const sizeEl = DOM.resultSize();
  if (sizeEl) sizeEl.textContent = data.size || 'Unknown';

  // Quality
  const qualityEl = DOM.resultQuality();
  if (qualityEl) qualityEl.textContent = data.quality || 'HD';

  // Quality dropdown
  populateQualityDropdown(data);

  // Buttons
  const dlUrl = getCurrentDownloadUrl(data);
  const streamUrl = data.stream || data.download || '#';

  const btnDl = DOM.btnDl();
  if (btnDl) btnDl.href = sanitizeUrl(dlUrl);

  const btnStream = DOM.btnStream();
  if (btnStream) btnStream.href = sanitizeUrl(streamUrl);

  // Show section
  section.style.display = 'block';
  section.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

function populateQualityDropdown(data) {
  const dropdown = DOM.qualityDropdown();
  if (!dropdown) return;

  dropdown.innerHTML = '';

  // Default quality option
  if (data.download) {
    const opt = document.createElement('option');
    opt.value = data.download;
    opt.textContent = `Default (${data.quality || 'HD'})`;
    opt.selected = true;
    dropdown.appendChild(opt);
  }

  // Additional streams
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
}

function handleQualityChange() {
  const dropdown = DOM.qualityDropdown();
  const selectedUrl = dropdown?.value;
  if (!selectedUrl) return;

  const btnDl = DOM.btnDl();
  if (btnDl) btnDl.href = sanitizeUrl(selectedUrl);
}

function getCurrentDownloadUrl(data) {
  if (data.download) return data.download;
  if (data.streams && typeof data.streams === 'object') {
    const keys = Object.keys(data.streams);
    if (keys.length > 0) return data.streams[keys[keys.length - 1]];
  }
  return data.stream || '#';
}

function hideResult() {
  const section = DOM.resultSection();
  if (section) section.style.display = 'none';
}


// ═══════════════════════════════════════════════════════════════════════════════
// VIDEO PLAYER (HLS.js + Fallback)
// ═══════════════════════════════════════════════════════════════════════════════

function showVideoPlayer() {
  if (!currentData) return;

  const streamUrl = currentData.stream || currentData.download;
  if (!streamUrl) return;

  const playerSection = DOM.videoPlayerSec();
  const video = DOM.videoPlayer();
  if (!playerSection || !video) return;

  // Destroy previous HLS instance
  if (hlsInstance) {
    hlsInstance.destroy();
    hlsInstance = null;
  }

  playerSection.style.display = 'block';

  // Determine if URL is HLS (.m3u8)
  const isHLS = streamUrl.includes('.m3u8') || streamUrl.includes('m3u8');

  if (isHLS && window.Hls && Hls.isSupported()) {
    // Use HLS.js
    hlsInstance = new Hls({
      maxBufferLength: 30,
      maxMaxBufferLength: 60,
      startLevel: -1,  // auto
    });
    hlsInstance.loadSource(streamUrl);
    hlsInstance.attachMedia(video);
    hlsInstance.on(Hls.Events.MANIFEST_PARSED, () => {
      // Don't autoplay
      video.pause();
    });
    hlsInstance.on(Hls.Events.ERROR, (_, data) => {
      if (data.fatal) {
        showToast('Player Error', 'Failed to load video stream.', 'danger');
      }
    });
  } else if (video.canPlayType('application/vnd.apple.mpegurl') && isHLS) {
    // Native HLS support (Safari)
    video.src = streamUrl;
    video.addEventListener('loadedmetadata', () => video.pause(), { once: true });
  } else {
    // Direct playback (mp4 etc.)
    video.src = sanitizeUrl(streamUrl);
  }

  // Scroll to player
  playerSection.scrollIntoView({ behavior: 'smooth', block: 'center' });
}

function hideVideoPlayer() {
  const playerSection = DOM.videoPlayerSec();
  const video = DOM.videoPlayer();
  if (playerSection) playerSection.style.display = 'none';
  if (video) {
    video.pause();
    video.src = '';
  }
  if (hlsInstance) {
    hlsInstance.destroy();
    hlsInstance = null;
  }
}

// ═══════════════════════════════════════════════════════════════════════════════
// LOADING STATE
// ═══════════════════════════════════════════════════════════════════════════════

function setLoading(loading) {
  const btn      = DOM.downloadBtn();
  const text     = DOM.btnText();
  const spinner  = DOM.btnSpinner();
  const skeleton = DOM.skeletonSection();
  const progress = DOM.progressBar();
  const fill     = DOM.progressFill();

  if (loading) {
    btn?.setAttribute('disabled', 'true');
    text?.classList.add('d-none');
    spinner?.classList.remove('d-none');
    if (skeleton) skeleton.style.display = 'block';
    if (progress) {
      progress.style.display = 'block';
      animateProgress(fill);
    }
  } else {
    btn?.removeAttribute('disabled');
    text?.classList.remove('d-none');
    spinner?.classList.add('d-none');
    if (skeleton) skeleton.style.display = 'none';
    if (progress) progress.style.display = 'none';
    if (fill) fill.style.width = '0%';
  }
}

function animateProgress(fill) {
  if (!fill) return;
  let width = 0;
  const interval = setInterval(() => {
    width += Math.random() * 12;
    if (width >= 90) {
      clearInterval(interval);
      fill.style.width = '90%';
    } else {
      fill.style.width = width + '%';
    }
  }, 300);

  // Store interval ref to clear if done early
  fill._interval = interval;
}

// ═══════════════════════════════════════════════════════════════════════════════
// ALERTS
// ═══════════════════════════════════════════════════════════════════════════════

function showAlert(message, type = 'danger') {
  const container = DOM.alertContainer();
  if (!container) return;

  const icons = {
    danger:  'bi-exclamation-triangle-fill',
    warning: 'bi-exclamation-circle-fill',
    success: 'bi-check-circle-fill',
    info:    'bi-info-circle-fill',
  };

  container.innerHTML = `
    <div class="alert alert-${escapeHtml(type)} alert-dismissible fade show d-flex align-items-center gap-2" role="alert">
      <i class="bi ${icons[type] || icons.danger} flex-shrink-0"></i>
      <div>${escapeHtml(message)}</div>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  `;

  container.scrollIntoView({ behavior: 'smooth', block: 'center' });
}

function hideAlert() {
  const container = DOM.alertContainer();
  if (container) container.innerHTML = '';
}

// ═══════════════════════════════════════════════════════════════════════════════
// ERROR HANDLING
// ═══════════════════════════════════════════════════════════════════════════════

function handleFetchError(err) {
  let message = 'An unexpected error occurred. Please try again.';

  if (err.name === 'TimeoutError' || err.name === 'AbortError') {
    message = 'Request timed out. The server may be busy — please try again in a moment.';
  } else if (err.message.includes('NetworkError') || err.message.includes('fetch')) {
    message = 'Network error. Please check your internet connection and try again.';
  } else if (err.message) {
    message = err.message;
  }

  showAlert(message, 'danger');
  showToast('Error', message, 'danger');
}


// ═══════════════════════════════════════════════════════════════════════════════
// TOAST NOTIFICATIONS
// ═══════════════════════════════════════════════════════════════════════════════

function showToast(title, body, type = 'success') {
  const toastEl = document.getElementById('app-toast');
  const titleEl = document.getElementById('toast-title');
  const bodyEl  = document.getElementById('toast-body');
  const iconEl  = document.getElementById('toast-icon');
  if (!toastEl) return;

  const icons = {
    success: 'bi-check-circle-fill text-success',
    danger:  'bi-x-circle-fill text-danger',
    warning: 'bi-exclamation-circle-fill text-warning',
    info:    'bi-info-circle-fill text-info',
  };

  if (iconEl)  iconEl.className = `bi ${icons[type] || icons.success} me-2`;
  if (titleEl) titleEl.textContent = title;
  if (bodyEl)  bodyEl.textContent = body;

  const toast = new bootstrap.Toast(toastEl, { delay: 4000 });
  toast.show();
}

// ═══════════════════════════════════════════════════════════════════════════════
// CLIPBOARD
// ═══════════════════════════════════════════════════════════════════════════════

async function copyToClipboard(text, successMsg) {
  if (!text || text === '#') {
    showToast('Error', 'No link available to copy.', 'warning');
    return;
  }

  try {
    await navigator.clipboard.writeText(text);
    showToast('Copied!', successMsg || 'Link copied to clipboard.', 'success');
  } catch (err) {
    // Fallback for older browsers
    const textarea = document.createElement('textarea');
    textarea.value = text;
    textarea.style.position = 'fixed';
    textarea.style.opacity = '0';
    document.body.appendChild(textarea);
    textarea.select();
    try {
      document.execCommand('copy');
      showToast('Copied!', successMsg || 'Link copied to clipboard.', 'success');
    } catch (e) {
      showToast('Error', 'Failed to copy. Please copy manually.', 'danger');
    }
    document.body.removeChild(textarea);
  }
}

// Clipboard paste detection – auto-fill if a TeraBox URL is pasted
function initClipboardDetection() {
  document.addEventListener('paste', (e) => {
    const input = DOM.videoUrl();
    if (!input || document.activeElement === input) return; // Let native paste work

    const pastedText = (e.clipboardData || window.clipboardData)?.getData('text')?.trim();
    if (pastedText && looksLikeTeraboxUrl(pastedText)) {
      input.value = pastedText;
      input.focus();
      showToast('Link Detected', 'TeraBox URL pasted automatically.', 'info');
    }
  });
}

function looksLikeTeraboxUrl(text) {
  const patterns = [
    /terabox\.com/i,
    /teraboxapp\.com/i,
    /1024terabox\.com/i,
    /terabox\.app/i,
    /dubox\.com/i,
    /4funbox\.com/i,
    /mirrobox\.com/i,
    /nephobox\.com/i,
    /tibibox\.com/i,
    /teraboxlink\.com/i,
    /momerybox\.com/i,
  ];
  return patterns.some(p => p.test(text));
}

// ═══════════════════════════════════════════════════════════════════════════════
// DOWNLOAD HISTORY (localStorage)
// ═══════════════════════════════════════════════════════════════════════════════

function getHistory() {
  try {
    const raw = localStorage.getItem(HISTORY_KEY);
    return raw ? JSON.parse(raw) : [];
  } catch { return []; }
}

function addToHistory(data, url) {
  const history = getHistory();
  const entry = {
    name:      data.name || 'Untitled',
    thumbnail: data.thumbnail || '',
    url:       url,
    time:      Date.now(),
  };

  // Remove duplicate
  const filtered = history.filter(h => h.url !== url);
  filtered.unshift(entry);

  // Keep max
  const trimmed = filtered.slice(0, MAX_HISTORY);
  localStorage.setItem(HISTORY_KEY, JSON.stringify(trimmed));
  renderHistory();
}

function renderHistory() {
  const section = DOM.historySection();
  const list    = DOM.historyList();
  const history = getHistory();

  if (!section || !list) return;

  if (history.length === 0) {
    section.style.display = 'none';
    return;
  }

  section.style.display = 'block';
  list.innerHTML = history.map(item => `
    <div class="history-item" data-url="${escapeAttr(item.url)}" title="Click to re-download">
      <img class="history-thumb" src="${escapeAttr(item.thumbnail)}" alt="" loading="lazy"
           onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNTYiIGhlaWdodD0iMzYiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PHJlY3Qgd2lkdGg9IjEwMCUiIGhlaWdodD0iMTAwJSIgZmlsbD0iIzMzMyIvPjwvc3ZnPg=='">
      <div class="history-info">
        <div class="history-title">${escapeHtml(item.name)}</div>
        <div class="history-time">${timeAgo(item.time)}</div>
      </div>
    </div>
  `).join('');

  // Click to load URL
  list.querySelectorAll('.history-item').forEach(el => {
    el.addEventListener('click', () => {
      const input = DOM.videoUrl();
      if (input) {
        input.value = el.dataset.url;
        input.focus();
        window.scrollTo({ top: 0, behavior: 'smooth' });
      }
    });
  });
}

function clearHistory() {
  localStorage.removeItem(HISTORY_KEY);
  renderHistory();
  showToast('Cleared', 'Download history has been cleared.', 'info');
}

function timeAgo(timestamp) {
  const diff = (Date.now() - timestamp) / 1000;
  if (diff < 60)    return 'Just now';
  if (diff < 3600)  return Math.floor(diff / 60) + 'm ago';
  if (diff < 86400) return Math.floor(diff / 3600) + 'h ago';
  return Math.floor(diff / 86400) + 'd ago';
}


// ═══════════════════════════════════════════════════════════════════════════════
// SHARE BUTTONS
// ═══════════════════════════════════════════════════════════════════════════════

function shareOn(platform) {
  const dlUrl = DOM.btnDl()?.href || window.location.href;
  const text  = `Download this TeraBox video in HD: ${currentData?.name || 'Video'}`;
  const encoded = encodeURIComponent(dlUrl);
  const encodedText = encodeURIComponent(text);

  let shareUrl = '';
  switch (platform) {
    case 'twitter':
      shareUrl = `https://twitter.com/intent/tweet?text=${encodedText}&url=${encoded}`;
      break;
    case 'facebook':
      shareUrl = `https://www.facebook.com/sharer/sharer.php?u=${encoded}`;
      break;
    case 'whatsapp':
      shareUrl = `https://wa.me/?text=${encodedText}%20${encoded}`;
      break;
  }

  if (shareUrl) window.open(shareUrl, '_blank', 'width=600,height=400');
}

// ═══════════════════════════════════════════════════════════════════════════════
// QR CODE
// ═══════════════════════════════════════════════════════════════════════════════

// Generate QR when modal opens
document.addEventListener('DOMContentLoaded', () => {
  const qrModal = document.getElementById('qrModal');
  if (qrModal) {
    qrModal.addEventListener('shown.bs.modal', generateQR);
  }
});

function generateQR() {
  const canvas = DOM.qrCanvas();
  const dlUrl  = DOM.btnDl()?.href;
  if (!canvas || !dlUrl || dlUrl === '#') return;

  // Use the QRCode library loaded from CDN
  if (typeof QRCode !== 'undefined') {
    QRCode.toCanvas(canvas, dlUrl, {
      width: 200,
      margin: 2,
      color: {
        dark:  '#0d6efd',
        light: '#ffffff',
      }
    }, (err) => {
      if (err) console.error('QR generation error:', err);
    });
  }
}

// ═══════════════════════════════════════════════════════════════════════════════
// SCROLL EFFECTS
// ═══════════════════════════════════════════════════════════════════════════════

function initScrollEffects() {
  let ticking = false;

  window.addEventListener('scroll', () => {
    if (!ticking) {
      requestAnimationFrame(() => {
        handleScroll();
        ticking = false;
      });
      ticking = true;
    }
  });
}

function handleScroll() {
  const scrollY = window.scrollY;

  // Navbar shadow on scroll
  const navbar = DOM.navbar();
  if (navbar) {
    navbar.classList.toggle('scrolled', scrollY > 50);
  }

  // Scroll-to-top button visibility
  const scrollBtn = DOM.scrollTop();
  if (scrollBtn) {
    scrollBtn.classList.toggle('visible', scrollY > 400);
  }
}

// ═══════════════════════════════════════════════════════════════════════════════
// ANIMATED COUNTERS
// ═══════════════════════════════════════════════════════════════════════════════

function initCounterAnimation() {
  const counters = document.querySelectorAll('.stat-number[data-count]');
  if (counters.length === 0) return;

  const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        animateCounter(entry.target);
        observer.unobserve(entry.target);
      }
    });
  }, { threshold: 0.5 });

  counters.forEach(counter => observer.observe(counter));
}

function animateCounter(el) {
  const target = parseInt(el.dataset.count, 10);
  if (isNaN(target)) return;

  const duration = 2000;
  const start    = performance.now();

  function update(now) {
    const elapsed = now - start;
    const progress = Math.min(elapsed / duration, 1);
    // Ease out cubic
    const eased = 1 - Math.pow(1 - progress, 3);
    const current = Math.floor(eased * target);
    el.textContent = formatNumber(current);

    if (progress < 1) {
      requestAnimationFrame(update);
    } else {
      el.textContent = formatNumber(target);
    }
  }

  requestAnimationFrame(update);
}

function formatNumber(num) {
  if (num >= 1000000) return (num / 1000000).toFixed(1) + 'M';
  if (num >= 1000) return (num / 1000).toFixed(0) + 'K';
  return num.toString();
}

// ═══════════════════════════════════════════════════════════════════════════════
// UTILITIES
// ═══════════════════════════════════════════════════════════════════════════════

function isValidUrl(str) {
  try {
    const url = new URL(str);
    return ['http:', 'https:'].includes(url.protocol);
  } catch { return false; }
}

function escapeHtml(str) {
  if (!str) return '';
  const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
  return String(str).replace(/[&<>"']/g, c => map[c]);
}

function escapeAttr(str) {
  if (!str) return '';
  return String(str).replace(/"/g, '&quot;').replace(/'/g, '&#039;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
}

function sanitizeUrl(url) {
  if (!url) return '#';
  // Only allow http/https URLs
  try {
    const parsed = new URL(url);
    if (['http:', 'https:'].includes(parsed.protocol)) return url;
    return '#';
  } catch {
    // Relative URL – allow
    if (url.startsWith('/') || url.startsWith('./')) return url;
    return '#';
  }
}
