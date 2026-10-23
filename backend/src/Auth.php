<?php
declare(strict_types=1);

namespace App;

final class Auth
{
    public const IDLE_TIMEOUT     = 1800;  // 30 min without activity
    public const ABSOLUTE_TIMEOUT = 28800; // 8 h since login

    private static ?array $cached = null;

    /** Current user, always re-read from the DB so role changes / deletions apply immediately. */
    public static function user(): ?array
    {
        if (self::$cached !== null) {
            return self::$cached;
        }
        if (session_status() !== PHP_SESSION_ACTIVE || !isset($_SESSION['uid'])) {
            return null;
        }

        $now = time();
        if ($now - ($_SESSION['last'] ?? 0) > self::IDLE_TIMEOUT || $now - ($_SESSION['started'] ?? 0) > self::ABSOLUTE_TIMEOUT) {
            self::destroy();
            return null;
        }

        $stmt = Database::pdo()->prepare('SELECT id, name, email, role FROM users WHERE id = ?');
        $stmt->execute([$_SESSION['uid']]);
        $u = $stmt->fetch();
        if (!$u) {
            self::destroy();
            return null;
        }
        $_SESSION['last'] = $now;
        $u['id'] = (int) $u['id'];
        return self::$cached = $u;
    }

    public static function login(array $user): void
    {
        session_regenerate_id(true); // prevents session fixation
        $_SESSION = ['uid' => (int) $user['id'], 'started' => time(), 'last' => time()];
        self::$cached = null;
    }

    public static function destroy(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION = [];
            session_destroy();
        }
        self::$cached = null;
    }

    public static function require(): array
    {
        $user = self::user();
        if ($user === null) {
            Response::json(['error' => 'Authentication required'], 401);
        }
        return $user;
    }

    public static function isStaff(array $user): bool
    {
        return in_array($user['role'], ['agent', 'admin'], true);
    }
}
