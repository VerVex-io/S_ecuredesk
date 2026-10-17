<?php
declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use App\Controllers\AuthController;
use App\Controllers\TicketController;
use App\Response;

// --- Secure defaults -------------------------------------------------------
session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'httponly' => true,
    'samesite' => 'Lax',
    'secure'   => false, // set to true behind HTTPS in production
]);
session_start();

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: no-referrer');
header("Content-Security-Policy: default-src 'none'; frame-ancestors 'none'");

$method = $_SERVER['REQUEST_METHOD'];
$path   = rtrim((string) parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/') ?: '/';

// CSRF mitigation: state-changing requests must be JSON (cannot be sent by a plain HTML form)
$input = [];
if (in_array($method, ['POST', 'PATCH', 'PUT', 'DELETE'], true)) {
    $ctype = $_SERVER['CONTENT_TYPE'] ?? '';
    if (!str_starts_with($ctype, 'application/json')) {
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
