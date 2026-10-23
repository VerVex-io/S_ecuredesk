<?php
declare(strict_types=1);

namespace App\Controllers;

use App\{Auth, Database, RateLimiter, Response, Validator};
use PDOException;

final class AuthController
{
    /** Pre-computed bcrypt hash: verified when the email is unknown so response time doesn't reveal valid accounts. */
    private const DUMMY_HASH = '$2y$10$GwiUsxHG7DzID8F8SVTr9.HyyHwr3khLBkGrd/xHspNlCioH.Kg/C';

    private const LOGIN_MAX_PER_ACCOUNT = 5;   // failures per IP+email
    private const LOGIN_MAX_PER_IP      = 30;  // failures per IP, any email
    private const LOGIN_WINDOW          = 900; // 15 minutes

    private static function ip(): string
    {
        // REMOTE_ADDR only: X-Forwarded-For is client-controlled. Behind a trusted proxy, configure Apache mod_remoteip.
        return (string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
    }

    public function register(array $in): void
    {
        $limiter = new RateLimiter('register', 10, 3600);
        $ip = self::ip();
        if (($wait = $limiter->retryAfter($ip)) > 0) {
            Response::json(['error' => 'Too many attempts, try again later'], 429, ['Retry-After' => (string) $wait]);
        }
        $limiter->hit($ip);

        $name  = trim((string) (\is_scalar($in['name'] ?? null) ? $in['name'] : ''));
        $email = strtolower(trim((string) (\is_scalar($in['email'] ?? null) ? $in['email'] : '')));
        $pass  = (string) (\is_scalar($in['password'] ?? null) ? $in['password'] : '');

        if ($name === '' || mb_strlen($name) > 100 || !Validator::email($email) || !Validator::password($pass)) {
            Response::json(['error' => 'Invalid input (password: 10-72 chars, letters and digits)'], 422);
        }

        $pdo = Database::pdo();
        // Role is NEVER taken from client input (prevents privilege escalation)
        try {
            $pdo->prepare('INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, "user")')
                ->execute([$name, $email, password_hash($pass, PASSWORD_DEFAULT)]);
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') { // duplicate key (also covers the check-then-insert race)
                Response::json(['error' => 'Email already registered'], 409);
            }
            throw $e;
        }

        Response::json(['message' => 'Account created'], 201);
    }

    public function login(array $in): void
    {
        $email = strtolower(trim((string) (\is_scalar($in['email'] ?? null) ? $in['email'] : '')));
        $pass  = (string) (\is_scalar($in['password'] ?? null) ? $in['password'] : '');
        $ip    = self::ip();

        $perAccount = new RateLimiter('login-account', self::LOGIN_MAX_PER_ACCOUNT, self::LOGIN_WINDOW);
        $perIp      = new RateLimiter('login-ip', self::LOGIN_MAX_PER_IP, self::LOGIN_WINDOW);
        $accountKey = $ip . '|' . $email;

        $wait = max($perAccount->retryAfter($accountKey), $perIp->retryAfter($ip));
        if ($wait > 0) {
            Response::json(['error' => 'Too many failed attempts, try again later'], 429, ['Retry-After' => (string) $wait]);
        }

        $stmt = Database::pdo()->prepare('SELECT id, name, email, role, password_hash FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $u = $stmt->fetch();

        // Always run one bcrypt verification so timing doesn't reveal whether the email exists
        $ok = password_verify($pass, $u ? $u['password_hash'] : self::DUMMY_HASH);

        // Same error for unknown email / wrong password (no user enumeration)
        if (!$u || !$ok) {
            $perAccount->hit($accountKey);
            $perIp->hit($ip);
            Response::json(['error' => 'Invalid credentials'], 401);
        }

        $perAccount->clear($accountKey);
        Auth::login($u);
        Response::json(['id' => (int) $u['id'], 'name' => $u['name'], 'email' => $u['email'], 'role' => $u['role']]);
    }

    public function logout(): void
    {
        Auth::destroy();
        Response::json(['message' => 'Logged out']);
    }

    public function me(): void
    {
        Response::json(Auth::require());
    }
}
