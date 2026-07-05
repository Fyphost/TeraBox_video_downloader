<?php
/**
 * TeraBox Video Downloader - API Handler
 * Accepts a POST request with a TeraBox URL, validates it,
 * forwards it to the worker API via cURL, and returns the result as JSON.
 *
 * Endpoint : POST /api/download.php
 * Body     : { "url": "https://terabox.com/...", "csrf_token": "..." }
 * Response : JSON from worker API or { "error": "..." }
 */

// ─── Bootstrap ────────────────────────────────────────────────────────────────
require_once __DIR__ . '/../config.php';

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    json_error('Method not allowed.');
}

// Always return JSON
header('Content-Type: application/json; charset=utf-8');

// ─── Security headers ─────────────────────────────────────────────────────────
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('Cache-Control: no-store, no-cache, must-revalidate');

// ─── Read + decode request body ───────────────────────────────────────────────
$rawBody = file_get_contents('php://input');
if (empty($rawBody)) {
    json_error('Empty request body.', 400);
}

$body = json_decode($rawBody, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    json_error('Invalid JSON body.', 400);
}

// ─── CSRF validation ──────────────────────────────────────────────────────────
$submittedToken = trim((string)($body['csrf_token'] ?? ''));
if (!csrf_valid($submittedToken)) {
    json_error('Invalid or expired security token. Please refresh the page.', 403);
}

// ─── Extract + sanitize URL ───────────────────────────────────────────────────
$userUrl = trim((string)($body['url'] ?? ''));

if (empty($userUrl)) {
    json_error('Please enter a TeraBox video URL.', 422);
}

// Basic length guard
if (strlen($userUrl) > 2048) {
    json_error('URL is too long.', 422);
}

// Validate URL structure and that it belongs to TeraBox
if (!is_valid_terabox_url($userUrl)) {
    json_error('Invalid URL. Please enter a valid TeraBox video link (e.g. https://terabox.com/s/...).', 422);
}

// ─── Rate limiting ────────────────────────────────────────────────────────────
$clientIp = get_client_ip();
if (!rate_limit_check($clientIp)) {
    json_error('Too many requests. Please wait a moment and try again.', 429);
}

// ─── Forward to worker API via cURL ───────────────────────────────────────────
$apiPayload = json_encode(['url' => $userUrl]);

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL            => API_ENDPOINT,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => $apiPayload,
    CURLOPT_HTTPHEADER     => [
        'Content-Type: application/json',
        'Accept: application/json',
        'Content-Length: ' . strlen($apiPayload),
    ],
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => API_TIMEOUT,
    CURLOPT_CONNECTTIMEOUT => API_CONNECT_TIMEOUT,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_MAXREDIRS      => 3,
    CURLOPT_SSL_VERIFYPEER => true,
    CURLOPT_SSL_VERIFYHOST => 2,
    CURLOPT_USERAGENT      => 'TeraBoxDL/1.0 (+' . SITE_URL . ')',
    CURLOPT_ENCODING       => '',          // accept compressed responses
]);

$response   = curl_exec($ch);
$httpCode   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlErrNo  = curl_errno($ch);
$curlErrMsg = curl_error($ch);
curl_close($ch);

// ─── cURL / network error handling ───────────────────────────────────────────
if ($curlErrNo !== 0) {
    $friendlyMsg = match (true) {
        in_array($curlErrNo, [CURLE_OPERATION_TIMEDOUT, CURLE_OPERATION_TIMEOUTED])
            => 'The request timed out. The server may be busy — please try again.',
        in_array($curlErrNo, [CURLE_COULDNT_CONNECT, CURLE_COULDNT_RESOLVE_HOST])
            => 'Could not connect to the download service. Please check your internet connection.',
        default
            => 'A network error occurred. Please try again later.',
    };
    log_error("cURL error #{$curlErrNo}: {$curlErrMsg} | URL: {$userUrl}");
    json_error($friendlyMsg, 502);
}

// ─── HTTP status error handling ───────────────────────────────────────────────
if ($httpCode !== 200) {
    $friendlyMsg = match (true) {
        $httpCode === 429 => 'Too many requests to the download service. Please wait a moment.',
        $httpCode >= 500  => 'The download service is currently unavailable. Please try again later.',
        $httpCode === 404 => 'Video not found. Please check the link and try again.',
        $httpCode === 403 => 'Access denied. This video may be private or restricted.',
        default           => "The download service returned an unexpected response (HTTP {$httpCode}).",
    };
    log_error("API HTTP {$httpCode} | URL: {$userUrl}");
    json_error($friendlyMsg, 502);
}

// ─── Parse API JSON response ──────────────────────────────────────────────────
$data = json_decode($response, true);
if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
    log_error("Malformed API JSON | URL: {$userUrl} | Body: " . substr($response, 0, 500));
    json_error('Received an invalid response from the download service. Please try again.', 502);
}

// ─── Validate required fields ─────────────────────────────────────────────────
// At minimum we need either a download URL or a stream URL
if (empty($data['download']) && empty($data['stream'])) {
    // Check if the API returned its own error message
    if (!empty($data['error'])) {
        json_error(htmlspecialchars($data['error'], ENT_QUOTES, 'UTF-8'), 422);
    }
    log_error("API returned no download/stream URLs | URL: {$userUrl}");
    json_error('This video could not be processed. It may be private, deleted, or unsupported.', 422);
}

// ─── Sanitize output ─────────────────────────────────────────────────────────
// Only pass through known safe fields to the frontend
$safeFields = ['name', 'size', 'thumbnail', 'stream', 'download', 'quality', 'streams'];
$safeData   = [];
foreach ($safeFields as $field) {
    if (isset($data[$field])) {
        $safeData[$field] = $data[$field]; // JSON-encoded to frontend — no raw HTML output
    }
}

// Ensure streams is always an object/array
if (!isset($safeData['streams']) || !is_array($safeData['streams'])) {
    $safeData['streams'] = [];
}

// Emit success response
http_response_code(200);
echo json_encode(['success' => true, 'data' => $safeData], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
exit;

// ─── Helper functions ─────────────────────────────────────────────────────────

/**
 * Output a JSON error and halt.
 */
function json_error(string $message, int $code = 400): never {
    http_response_code($code);
    echo json_encode(['success' => false, 'error' => $message], JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Get the real client IP, respecting common proxy headers.
 */
function get_client_ip(): string {
    $headers = [
        'HTTP_CF_CONNECTING_IP',   // Cloudflare
        'HTTP_X_FORWARDED_FOR',
        'HTTP_X_REAL_IP',
        'REMOTE_ADDR',
    ];
    foreach ($headers as $header) {
        if (!empty($_SERVER[$header])) {
            // X-Forwarded-For can be a comma-separated list; take the first
            $ip = trim(explode(',', $_SERVER[$header])[0]);
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        }
    }
    return '0.0.0.0';
}

/**
 * Append an error entry to a simple log file (only in debug mode or always for errors).
 */
function log_error(string $message): void {
    $logFile = sys_get_temp_dir() . '/tbdl_errors.log';
    $entry   = '[' . date('Y-m-d H:i:s') . '] ' . $message . PHP_EOL;
    @file_put_contents($logFile, $entry, FILE_APPEND | LOCK_EX);
}
