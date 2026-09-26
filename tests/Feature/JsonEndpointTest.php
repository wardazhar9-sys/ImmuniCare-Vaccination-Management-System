<?php

namespace ImmuniCare\Tests\Feature;

use PHPUnit\Framework\TestCase;

final class JsonEndpointTest extends TestCase
{
    public function testHealthEndpointReturnsJson(): void
    {
        $body = @file_get_contents('http://app:8080/api/v1/health.php');
        self::assertNotFalse($body);
        $data = json_decode($body, true);
        self::assertIsArray($data);
        self::assertTrue($data['ok']);
    }
}
