<?php
declare(strict_types=1);

namespace App\Controllers;

use App\{Auth, Database, Response, Validator};

final class AuthController
{
    public function register(array $in): void
    {
        $name  = trim((string) ($in['name'] ?? ''));
        $email = strtolower(trim((string) ($in['email'] ?? '')));
        $pass  = (string) ($in['password'] ?? '');

        if ($name === '' || mb_strlen($name) > 100 || !Validator::email($email) || !Validator::password($pass)) {
            Response::json(['error' => 'Invalid input (password: 10+ chars, letters and digits)'], 422);
        }

        $pdo = Database::pdo();
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            Response::json(['error' => 'Email already registered'], 409);
        }

        // Role is NEVER taken from client input (prevents privilege escalation)
        $pdo->prepare('INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, "user")')
            ->execute([$name, $email, password_hash($pass, PASSWORD_DEFAULT)]);

        Response::json(['message' => 'Account created'], 201);
    }

    public function login(array $in): void
    {
        $email = strtolower(trim((string) ($in['email'] ?? '')));
        $pass  = (string) ($in['password'] ?? '');

        $stmt = Database::pdo()->prepare('SELECT id, name, email, role, password_hash FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $u = $stmt->fetch();

        // Same error for unknown email / wrong password (no user enumeration)
        if (!$u || !password_verify($pass, $u['password_hash'])) {
            Response::json(['error' => 'Invalid credentials'], 401);
        }

        session_regenerate_id(true); // prevents session fixation
        $_SESSION['user'] = ['id' => (int) $u['id'], 'name' => $u['name'], 'email' => $u['email'], 'role' => $u['role']];
        Response::json($_SESSION['user']);
    }

    public function logout(): void
    {
        $_SESSION = [];
        session_destroy();
        Response::json(['message' => 'Logged out']);
    }

    public function me(): void
    {
        Response::json(Auth::require());
    }
}
