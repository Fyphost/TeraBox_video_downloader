<?php
/**
 * TeraBox Video Downloader - Configuration
 * Central configuration file for site settings, API, and security options.
 */

// ─── Site Settings ────────────────────────────────────────────────────────────
define('SITE_NAME',        'TeraBox Downloader');
define('SITE_TAGLINE',     'Download TeraBox Videos in HD for Free');
define('SITE_URL',         'https://yourdomain.com');          // ← change to your domain
define('SITE_DESCRIPTION', 'Download TeraBox videos in HD quality for free. No registration required. Supports 360p, 480p, 720p, and 1080p. Fast, free, and unlimited.');
define('SITE_KEYWORDS',    'terabox downloader, terabox video download, terabox hd, free terabox, download terabox link');
define('SITE_AUTHOR',      'TeraBox Downloader');
define('SITE_TWITTER',     '@teraboxdl');                       // ← optional
define('SITE_EMAIL',       'support@yourdomain.com');

// ─── API Settings ─────────────────────────────────────────────────────────────
define('API_ENDPOINT',     'https://throbbing-disk-7a68.ftcweb.workers.dev');
define('API_TIMEOUT',      20);     // cURL timeout in seconds
define('API_CONNECT_TIMEOUT', 10);  // cURL connect timeout in seconds

// ─── Rate Limiting ────────────────────────────────────────────────────────────
define('RATE_LIMIT_ENABLED',    true);
define('RATE_LIMIT_MAX',        30);    // max requests per window
define('RATE_LIMIT_WINDOW',     3600);  // window in seconds (1 hour)

// ─── Security ────────────────────────────────────────────────────────────────
define('CSRF_TOKEN_NAME', 'tbdl_csrf_token');
define('CSRF_SECRET',     'change-this-to-a-random-secret-string-32chars'); // ← CHANGE THIS

// ─── Allowed TeraBox URL patterns ────────────────────────────────────────────
define('TERABOX_URL_PATTERNS', serialize([
    '#^https?://(www\.)?terabox\.com/#i',
    '#^https?://(www\.)?teraboxapp\.com/#i',
    '#^https?://(www\.)?1024terabox\.com/#i',
    '#^https?://(www\.)?terabox\.app/#i',
    '#^https?://(www\.)?dubox\.com/#i',
    '#^https?://(www\.)?tibibox\.com/#i',
    '#^https?://(www\.)?teraboxlink\.com/#i',
    '#^https?://(www\.)?mirrobox\.com/#i',
    '#^https?://(www\.)?nephobox\.com/#i',
    '#^https?://(www\.)?4funbox\.com/#i',
    '#^https?://(www\.)?momerybox\.com/#i',
    '#^https?://(www\.)?ibomma\.com/#i',
    '#^https?://tb-video\..+/#i',
]));

// ─── PHP Error Display ───────────────────────────────────────────────────────
// Set to false in production
define('DEBUG_MODE', false);

if (!DEBUG_MODE) {
    error_reporting(0);
    ini_set('display_errors', '0');
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
}

// ─── Session Config ───────────────────────────────────────────────────────────
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_samesite', 'Strict');
    ini_set('session.use_strict_mode', '1');
    session_start();
}

// ─── CSRF Helpers ─────────────────────────────────────────────────────────────

/**
 * Generate (or retrieve) a CSRF token for the current session.
 */
function csrf_token(): string {
    if (empty($_SESSION[CSRF_TOKEN_NAME])) {
        $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
    }
    return $_SESSION[CSRF_TOKEN_NAME];
}

/**
 * Validate a submitted CSRF token.
 */
function csrf_valid(string $token): bool {
    return isset($_SESSION[CSRF_TOKEN_NAME])
        && hash_equals($_SESSION[CSRF_TOKEN_NAME], $token);
}

/**
 * Safely escape output for HTML contexts.
 */
function e(string $str): string {
    return htmlspecialchars($str, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

/**
 * Validate that a URL matches one of the allowed TeraBox patterns.
 */
function is_valid_terabox_url(string $url): bool {
    if (filter_var($url, FILTER_VALIDATE_URL) === false) {
        return false;
    }
    $patterns = unserialize(TERABOX_URL_PATTERNS);
    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $url)) {
            return true;
        }
    }
    return false;
}

/**
 * Simple file-based rate limiter.
 * Returns true if the request is allowed, false if rate-limited.
 */
function rate_limit_check(string $identifier): bool {
    if (!RATE_LIMIT_ENABLED) return true;

    $cacheDir = sys_get_temp_dir() . '/tbdl_rl/';
    if (!is_dir($cacheDir)) {
        @mkdir($cacheDir, 0700, true);
    }

    $file  = $cacheDir . md5($identifier) . '.json';
    $now   = time();
    $data  = ['count' => 0, 'window_start' => $now];

    if (file_exists($file)) {
        $raw = @file_get_contents($file);
        if ($raw) {
            $decoded = json_decode($raw, true);
            if ($decoded && ($now - $decoded['window_start']) < RATE_LIMIT_WINDOW) {
                $data = $decoded;
            }
        }
    }

    if ($data['count'] >= RATE_LIMIT_MAX) {
        return false;
    }

    $data['count']++;
    @file_put_contents($file, json_encode($data), LOCK_EX);
    return true;
}
