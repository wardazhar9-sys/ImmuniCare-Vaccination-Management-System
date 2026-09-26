<?php

namespace ImmuniCare\Tests\Feature;

use PHPUnit\Framework\TestCase;

final class RouteInventoryTest extends TestCase
{
    public function testRequiredRoutesExist(): void
    {
        $root = dirname(__DIR__, 2);
        foreach ([
            'index.php',
            'login.php',
            'register.php',
            'notifications.php',
            'Admin/profile.php',
            'Admin/logout.php',
            'Admin/vaccination_records.php',
            'Parent/children.php',
            'Hospital/appointments.php'
        ] as $route) {
            self::assertFileExists($root . '/' . $route);
        }
    }
}
