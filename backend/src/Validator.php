<?php
declare(strict_types=1);

namespace App;

final class Validator
{
    public static function email(string $email): bool
    {
        return strlen($email) <= 190 && filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /** 10 to 72 bytes (bcrypt ignores everything after 72), at least one letter and one digit. */
    public static function password(string $password): bool
    {
        $len = strlen($password);
        return $len >= 10 && $len <= 72
            && preg_match('/\p{L}/u', $password) === 1
            && preg_match('/\d/', $password) === 1;
    }

    public static function ticketTitle(string $title): bool
    {
        $len = mb_strlen(trim($title));
        return $len >= 3 && $len <= 150;
    }

    public static function ticketDescription(string $desc): bool
    {
        $len = mb_strlen(trim($desc));
        return $len >= 1 && $len <= 10000;
    }

    public static function priority(string $p): bool
    {
        return in_array($p, ['low', 'medium', 'high'], true);
    }

    public static function status(string $s): bool
    {
        return in_array($s, ['open', 'in_progress', 'closed'], true);
    }

    /** True only for exactly application/json (optionally with parameters like charset). */
    public static function isJsonContentType(string $header): bool
    {
        $media = strtolower(trim(explode(';', $header, 2)[0]));
        return $media === 'application/json';
    }
}
