<?php
declare(strict_types=1);

namespace App;

use PDO;

final class Database
{
    private static ?PDO $pdo = null;

    public static function pdo(): PDO
    {
        if (self::$pdo === null) {
            $dsn = sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4',
                getenv('DB_HOST') ?: 'localhost', getenv('DB_NAME') ?: 'securedesk');
            self::$pdo = new PDO($dsn, getenv('DB_USER') ?: 'securedesk', getenv('DB_PASS') ?: '', [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false, // real prepared statements
            ]);
        }
        return self::$pdo;
    }
}
