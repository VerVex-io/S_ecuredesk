<?php
declare(strict_types=1);

namespace App;

final class Validator
{
    public static function email(string $email): bool
    {
        return strlen($email) <= 190 && filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /** At least 10 chars, one letter and one digit. */
    public static function password(string $password): bool
    {
        return strlen($password) >= 10
            && preg_match('/[A-Za-z]/', $password) === 1
            && preg_match('/\d/', $password) === 1;
    }

    public static function ticketTitle(string $title): bool
    {
        $len = mb_strlen(trim($title));
        return $len >= 3 && $len <= 150;
    }

    public static function priority(string $p): bool
    {
        return in_array($p, ['low', 'medium', 'high'], true);
    }

    public static function status(string $s): bool
    {
        return in_array($s, ['open', 'in_progress', 'closed'], true);
    }
}
