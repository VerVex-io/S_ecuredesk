<?php
declare(strict_types=1);

namespace App\Controllers;

use App\{Auth, Database, Response, Validator};

final class TicketController
{
    public function index(): void
    {
        $user = Auth::require();
        $sql  = 'SELECT t.id, t.title, t.status, t.priority, t.created_at, u.name AS author
                 FROM tickets t JOIN users u ON u.id = t.author_id';
        $params = [];
        $where  = [];

        if (!Auth::isStaff($user)) {            // users only see their own tickets
            $where[]  = 't.author_id = ?';
            $params[] = $user['id'];
        }
        if (isset($_GET['status']) && Validator::status((string) $_GET['status'])) {
            $where[]  = 't.status = ?';
            $params[] = $_GET['status'];
        }
        if (!empty($_GET['q'])) {
            $where[]  = 't.title LIKE ?';
            $params[] = '%' . str_replace(['%', '_'], ['\%', '\_'], (string) $_GET['q']) . '%';
        }
        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $stmt = Database::pdo()->prepare($sql . ' ORDER BY t.created_at DESC LIMIT 100');
        $stmt->execute($params);
        Response::json($stmt->fetchAll());
    }

    public function create(array $in): void
    {
        $user  = Auth::require();
        $title = trim((string) ($in['title'] ?? ''));
        $desc  = trim((string) ($in['description'] ?? ''));
        $prio  = (string) ($in['priority'] ?? 'medium');

        if (!Validator::ticketTitle($title) || $desc === '' || !Validator::priority($prio)) {
            Response::json(['error' => 'Invalid ticket data'], 422);
        }
        $pdo = Database::pdo();
        $pdo->prepare('INSERT INTO tickets (title, description, priority, author_id) VALUES (?, ?, ?, ?)')
            ->execute([$title, $desc, $prio, $user['id']]);
        Response::json(['id' => (int) $pdo->lastInsertId()], 201);
    }

    public function show(array $in, int $id): void
    {
        $user   = Auth::require();
        $ticket = $this->findAuthorized($user, $id);

        $stmt = Database::pdo()->prepare(
            'SELECT c.id, c.body, c.created_at, u.name AS author
             FROM comments c JOIN users u ON u.id = c.user_id
             WHERE c.ticket_id = ? ORDER BY c.created_at');
        $stmt->execute([$id]);
        $ticket['comments'] = $stmt->fetchAll();
        Response::json($ticket);
    }

    public function update(array $in, int $id): void
    {
        $user = Auth::require();
        if (!Auth::isStaff($user)) {
            Response::json(['error' => 'Forbidden'], 403);
        }
        $this->findAuthorized($user, $id);

        $status = (string) ($in['status'] ?? '');
        if (!Validator::status($status)) {
            Response::json(['error' => 'Invalid status'], 422);
        }
        Database::pdo()->prepare('UPDATE tickets SET status = ? WHERE id = ?')->execute([$status, $id]);
        Response::json(['message' => 'Updated']);
    }

    public function comment(array $in, int $id): void
    {
        $user = Auth::require();
        $this->findAuthorized($user, $id);

        $body = trim((string) ($in['body'] ?? ''));
        if ($body === '' || mb_strlen($body) > 5000) {
            Response::json(['error' => 'Invalid comment'], 422);
        }
        Database::pdo()->prepare('INSERT INTO comments (ticket_id, user_id, body) VALUES (?, ?, ?)')
            ->execute([$id, $user['id'], $body]);
        Response::json(['message' => 'Comment added'], 201);
    }

    /** Object-level authorization: prevents IDOR. 404 (not 403) avoids leaking existence. */
    private function findAuthorized(array $user, int $id): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT t.*, u.name AS author FROM tickets t JOIN users u ON u.id = t.author_id WHERE t.id = ?');
        $stmt->execute([$id]);
        $t = $stmt->fetch();
        if (!$t || (!Auth::isStaff($user) && (int) $t['author_id'] !== $user['id'])) {
            Response::json(['error' => 'Ticket not found'], 404);
        }
        return $t;
    }
}
