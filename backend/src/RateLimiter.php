<?php
declare(strict_types=1);

namespace App;

/**
 * Small file-based sliding-window limiter (no extra infrastructure needed).
 * For multi-server deployments swap the storage for Redis/DB.
 */
final class RateLimiter
{
    public function __construct(
        private readonly string $bucket,
        private readonly int $maxAttempts,
        private readonly int $windowSeconds,
        private ?string $dir = null,
    ) {
        $this->dir ??= sys_get_temp_dir() . '/securedesk-ratelimit';
        if (!is_dir($this->dir)) {
            @mkdir($this->dir, 0700, true);
        }
    }

    /** Seconds the caller must wait, or 0 if allowed. Does not record an attempt. */
    public function retryAfter(string $key): int
    {
        $hits = $this->read($key);
        if (count($hits) < $this->maxAttempts) {
            return 0;
        }
        return max(1, $hits[0] + $this->windowSeconds - time());
    }

    public function hit(string $key): void
    {
        $this->mutate($key, function (array $hits): array {
            $hits[] = time();
            return $hits;
        });
    }

    public function clear(string $key): void
    {
        @unlink($this->file($key));
    }

    private function file(string $key): string
    {
        return $this->dir . '/' . hash('sha256', $this->bucket . '|' . $key) . '.json';
    }

    /** @return int[] timestamps inside the window, oldest first */
    private function read(string $key): array
    {
        $raw = @file_get_contents($this->file($key));
        $hits = $raw ? (json_decode($raw, true) ?: []) : [];
        $min = time() - $this->windowSeconds;
        return array_values(array_filter($hits, fn ($t) => is_int($t) && $t > $min));
    }

    private function mutate(string $key, callable $fn): void
    {
        $fh = @fopen($this->file($key), 'c+');
        if (!$fh) {
            return;
        }
        flock($fh, LOCK_EX);
        $raw = stream_get_contents($fh);
        $hits = $raw ? (json_decode($raw, true) ?: []) : [];
        $min = time() - $this->windowSeconds;
        $hits = array_values(array_filter($hits, fn ($t) => is_int($t) && $t > $min));
        $hits = $fn($hits);
        ftruncate($fh, 0);
        rewind($fh);
        fwrite($fh, json_encode($hits));
        flock($fh, LOCK_UN);
        fclose($fh);
    }
}
