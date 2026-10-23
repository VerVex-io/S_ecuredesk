<?php
declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use App\Controllers\AuthController;
use App\Controllers\TicketController;
use App\Response;
use App\Validator;

// --- Environment -----------------------------------------------------------
$isProd  = (getenv('APP_ENV') ?: 'development') === 'production';
$isHttps = ($_SERVER['HTTPS'] ?? '') === 'on' || strtolower($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
// Secure cookies are mandatory in production; in dev (plain http://localhost) they would never be sent.
$secureCookie = $isProd || $isHttps;

header_remove('X-Powered-By');
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: no-referrer');
header("Content-Security-Policy: default-src 'none'; frame-ancestors 'none'");
header('Permissions-Policy: geolocation=(), camera=(), microphone=()');
header('Cross-Origin-Resource-Policy: same-origin');
header('Cache-Control: no-store');
if ($secureCookie) {
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
}

$method = $_SERVER['REQUEST_METHOD'];
$path   = rtrim((string) parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/') ?: '/';

// --- Sessions --------------------------------------------------------------
// Only start a session when the client already has one, or is logging in.
// Anonymous traffic no longer creates a session file per request.
session_name('SDSESSID');
session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'httponly' => true,
    'samesite' => 'Lax',
    'secure'   => $secureCookie,
]);
if (isset($_COOKIE[session_name()]) || ($method === 'POST' && $path === '/api/login')) {
    session_start();
}

// --- CSRF mitigation + body parsing ------------------------------------------
// State-changing requests must be exactly application/json (cannot be sent by a plain HTML form,
// and cross-site JSON requires a CORS preflight, which this API never grants).
$input = [];
if (in_array($method, ['POST', 'PATCH', 'PUT', 'DELETE'], true)) {
    if (!Validator::isJsonContentType($_SERVER['CONTENT_TYPE'] ?? '')) {
        Response::json(['error' => 'Content-Type must be application/json'], 415);
    }
    $input = json_decode(file_get_contents('php://input') ?: '{}', true);
    if (!is_array($input)) {
        Response::json(['error' => 'Invalid JSON body'], 400);
    }
}

$routes = [
    ['POST',  '#^/api/register$#',               [AuthController::class, 'register']],
    ['POST',  '#^/api/login$#',                  [AuthController::class, 'login']],
    ['POST',  '#^/api/logout$#',                 [AuthController::class, 'logout']],
    ['GET',   '#^/api/me$#',                     [AuthController::class, 'me']],
    ['GET',   '#^/api/tickets$#',                [TicketController::class, 'index']],
    ['POST',  '#^/api/tickets$#',                [TicketController::class, 'create']],
    ['GET',   '#^/api/tickets/(\d+)$#',          [TicketController::class, 'show']],
    ['PATCH', '#^/api/tickets/(\d+)$#',          [TicketController::class, 'update']],
    ['POST',  '#^/api/tickets/(\d+)/comments$#', [TicketController::class, 'comment']],
];

try {
    foreach ($routes as [$m, $pattern, $handler]) {
        if ($m === $method && preg_match($pattern, $path, $matches)) {
            array_shift($matches);
            [$class, $action] = $handler;
            (new $class())->$action($input, ...array_map('intval', $matches));
            exit;
        }
    }
    Response::json(['error' => 'Not found'], 404);
} catch (\Throwable $e) {
    error_log($e->getMessage());
    Response::json(['error' => 'Internal server error'], 500); // never leak internals
}
