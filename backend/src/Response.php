<?php
declare(strict_types=1);

namespace App;

final class Response
{
    public static function json(mixed $data, int $status = 200, array $headers = []): never
    {
        http_response_code($status);
        foreach ($headers as $name => $value) {
            header("$name: $value");
        }
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        exit;
    }
}
