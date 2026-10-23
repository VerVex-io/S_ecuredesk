<?php
declare(strict_types=1);

namespace Tests;

use App\RateLimiter;
use PHPUnit\Framework\TestCase;

final class RateLimiterTest extends TestCase
{
    private string $dir;

    protected function setUp(): void
    {
        $this->dir = sys_get_temp_dir() . '/sd-rl-test-' . bin2hex(random_bytes(4));
    }

    protected function tearDown(): void
    {
        foreach (glob($this->dir . '/*') ?: [] as $f) {
            unlink($f);
        }
        @rmdir($this->dir);
    }

    public function testBlocksAfterMaxAttempts(): void
    {
        $rl = new RateLimiter('t', 3, 60, $this->dir);
        for ($i = 0; $i < 3; $i++) {
            $this->assertSame(0, $rl->retryAfter('k'));
            $rl->hit('k');
        }
        $this->assertGreaterThan(0, $rl->retryAfter('k'));
        $this->assertLessThanOrEqual(60, $rl->retryAfter('k'));
    }

    public function testKeysAreIndependentAndClearResets(): void
    {
        $rl = new RateLimiter('t', 1, 60, $this->dir);
        $rl->hit('a');
        $this->assertGreaterThan(0, $rl->retryAfter('a'));
        $this->assertSame(0, $rl->retryAfter('b'));
        $rl->clear('a');
        $this->assertSame(0, $rl->retryAfter('a'));
    }
}
