<?php
declare(strict_types=1);

namespace Tests;

use App\Validator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ValidatorTest extends TestCase
{
    #[DataProvider('passwords')]
    public function testPassword(string $pw, bool $expected): void
    {
        $this->assertSame($expected, Validator::password($pw));
    }

    public static function passwords(): array
    {
        return [
            'too short' => ['abc123', false],
            'no digit'  => ['abcdefghijkl', false],
            'no letter' => ['1234567890123', false],
            'valid'     => ['correct-horse-42', true],
        ];
    }

    public function testEmail(): void
    {
        $this->assertTrue(Validator::email('a@b.com'));
        $this->assertFalse(Validator::email('not-an-email'));
        $this->assertFalse(Validator::email("a@b.com\r\nBcc: x@y.z"));
    }

    public function testTicketTitleBounds(): void
    {
        $this->assertFalse(Validator::ticketTitle('ab'));
        $this->assertTrue(Validator::ticketTitle('Printer is broken'));
        $this->assertFalse(Validator::ticketTitle(str_repeat('x', 151)));
    }

    public function testEnums(): void
    {
        $this->assertTrue(Validator::priority('high'));
        $this->assertFalse(Validator::priority('urgent'));
        $this->assertTrue(Validator::status('closed'));
        $this->assertFalse(Validator::status("closed' OR 1=1 --"));
    }
}
