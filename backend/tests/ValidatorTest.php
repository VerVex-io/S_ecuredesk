<?php
declare(strict_types=1);

namespace Tests;

use App\Validator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ValidatorTest extends TestCase
{
    /** @dataProvider passwords */
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
            'too long (bcrypt 72 byte limit)' => [str_repeat('a', 72) . '1', false],
            'max length' => [str_repeat('a', 71) . '1', true],
            'unicode letter' => ['éééééééééé1', true],
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

    public function testDescriptionBounds(): void
    {
        $this->assertFalse(Validator::ticketDescription('   '));
        $this->assertTrue(Validator::ticketDescription('It does not print'));
        $this->assertTrue(Validator::ticketDescription(str_repeat('x', 10000)));
        $this->assertFalse(Validator::ticketDescription(str_repeat('x', 10001)));
    }

    public function testJsonContentTypeIsStrict(): void
    {
        $this->assertTrue(Validator::isJsonContentType('application/json'));
        $this->assertTrue(Validator::isJsonContentType('Application/JSON; charset=utf-8'));
        $this->assertFalse(Validator::isJsonContentType('application/jsonp'));
        $this->assertFalse(Validator::isJsonContentType('application/json-evil'));
        $this->assertFalse(Validator::isJsonContentType('text/plain'));
        $this->assertFalse(Validator::isJsonContentType('multipart/form-data; boundary=x'));
        $this->assertFalse(Validator::isJsonContentType(''));
    }
}
