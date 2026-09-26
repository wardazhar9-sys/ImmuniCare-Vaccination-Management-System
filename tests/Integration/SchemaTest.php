<?php

namespace ImmuniCare\Tests\Integration;

use PHPUnit\Framework\TestCase;

final class SchemaTest extends TestCase
{
    public function testCoreTablesAndExpansionExist(): void
    {
        global $conn;
        $tables = [];
        $result = $conn->query(
            "SELECT table_name FROM information_schema.tables
             WHERE table_schema = DATABASE()"
        );
        while ($row = $result->fetch_assoc()) {
            $tables[] = $row['table_name'];
        }

        foreach ([
            'users',
            'children',
            'hospitals',
            'bookings',
            'vaccination_schedules',
            'vaccination_records',
            'notifications',
            'vaccine_doses',
            'hospital_slots',
            'notification_outbox',
            'api_tokens',
            'audit_logs'
        ] as $table) {
            self::assertContains($table, $tables);
        }
    }

    public function testUserStatusAndBookingRelationsExist(): void
    {
        global $conn;
        $result = $conn->query(
            "SELECT table_name, column_name
             FROM information_schema.columns
             WHERE table_schema = DATABASE()
               AND ((table_name = 'users' AND column_name = 'status')
                OR (table_name = 'bookings' AND column_name = 'vaccine_dose_id')
                OR (table_name = 'vaccination_schedules' AND column_name = 'booking_id')
                OR (table_name = 'vaccination_records' AND column_name = 'booking_id'))"
        );

        $found = [];
        while ($row = $result->fetch_assoc()) {
            $found[] = $row['table_name'] . '.' . $row['column_name'];
        }

        self::assertContains('users.status', $found);
        self::assertContains('bookings.vaccine_dose_id', $found);
        self::assertContains('vaccination_schedules.booking_id', $found);
        self::assertContains('vaccination_records.booking_id', $found);
    }
}
