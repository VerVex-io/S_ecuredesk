<?php
declare(strict_types=1);

namespace App;

final class Auth
{
    public static function user(): ?array
    {
        return $_SESSION['user'] ?? null;
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
