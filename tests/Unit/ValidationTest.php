<?php

namespace ImmuniCare\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class ValidationTest extends TestCase
{
    public function testValidDateBoundaries(): void
    {
        self::assertTrue(valid_date('2026-01-01'));
        self::assertFalse(valid_date('2026-02-30'));
        self::assertFalse(valid_date('01-01-2026'));
    }

    public function testValidTimeBoundaries(): void
    {
        self::assertTrue(valid_time('09:30'));
        self::assertFalse(valid_time('25:30'));
        self::assertFalse(valid_time('9:30'));
    }

    public function testHtmlEscaping(): void
    {
        self::assertSame('&lt;script&gt;', e('<script>'));
    }
}
