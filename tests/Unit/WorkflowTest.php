<?php

namespace ImmuniCare\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class WorkflowTest extends TestCase
{
    public function testBookingRejectsPastOrMalformedInputBeforeDatabaseWork(): void
    {
        global $conn;
        $result = create_booking_workflow(
            $conn,
            1,
            1,
            1,
            1,
            'not-a-date',
            '25:00'
        );

        self::assertFalse($result['ok']);
        self::assertSame(422, $result['status']);
    }

    public function testBookingRejectsUnknownOwner(): void
    {
        global $conn;
        $result = create_booking_workflow(
            $conn,
            1,
            999999,
            1,
            1,
            '2099-01-01',
            '12:00'
        );

        self::assertFalse($result['ok']);
        self::assertSame(422, $result['status']);
    }

    public function testBookingSuccessAndConflictAreDeterministic(): void
    {
        global $conn;
        $user = $conn->query(
            "SELECT id FROM users WHERE email = 'parent@test.local'"
        )->fetch_assoc();
        $child = $conn->query(
            "SELECT id FROM children WHERE parent_id = " . (int)$user['id'] . " LIMIT 1"
        )->fetch_assoc();
        $hospital = $conn->query(
            "SELECT id FROM hospitals WHERE status = 'Active' LIMIT 1"
        )->fetch_assoc();
        $vaccine = $conn->query(
            "SELECT id FROM vaccines WHERE availability = 'Available' LIMIT 1"
        )->fetch_assoc();

        $first = create_booking_workflow(
            $conn,
            (int)$user['id'],
            (int)$child['id'],
            (int)$vaccine['id'],
            (int)$hospital['id'],
            '2099-01-03',
            '12:00'
        );
        self::assertTrue($first['ok']);

        $second = create_booking_workflow(
            $conn,
            (int)$user['id'],
            (int)$child['id'],
            (int)$vaccine['id'],
            (int)$hospital['id'],
            '2099-01-03',
            '12:00'
        );
        self::assertFalse($second['ok']);
        self::assertSame(409, $second['status']);
    }

    public function testFullSlotIsRejected(): void
    {
        global $conn;
        $user = $conn->query(
            "SELECT id FROM users WHERE email = 'parent@test.local'"
        )->fetch_assoc();
        $child = $conn->query(
            "SELECT id FROM children WHERE parent_id = " . (int)$user['id'] . " LIMIT 1"
        )->fetch_assoc();
        $hospital = $conn->query(
            "SELECT id FROM hospitals WHERE status = 'Active' LIMIT 1"
        )->fetch_assoc();
        $vaccine = $conn->query(
            "SELECT id FROM vaccines WHERE availability = 'Available' LIMIT 1"
        )->fetch_assoc();

        $stmt = $conn->prepare(
            "INSERT INTO hospital_slots
             (hospital_id, slot_date, slot_time, capacity, booked_count)
             VALUES (?, '2099-01-04', '12:00', 1, 1)
             ON DUPLICATE KEY UPDATE booked_count = 1, status = 'Open'"
        );
        $stmt->bind_param('i', $hospital['id']);
        $stmt->execute();
        $stmt->close();

        $result = create_booking_workflow(
            $conn,
            (int)$user['id'],
            (int)$child['id'],
            (int)$vaccine['id'],
            (int)$hospital['id'],
            '2099-01-04',
            '12:00'
        );

        self::assertFalse($result['ok']);
        self::assertSame(409, $result['status']);
    }

    public function testAvailableSlotIsIncremented(): void
    {
        global $conn;
        $user = $conn->query("SELECT id FROM users WHERE email = 'parent@test.local'")->fetch_assoc();
        $child = $conn->query("SELECT id FROM children WHERE parent_id = " . (int)$user['id'] . " LIMIT 1")->fetch_assoc();
        $hospital = $conn->query("SELECT id FROM hospitals WHERE status = 'Active' LIMIT 1")->fetch_assoc();
        $vaccine = $conn->query("SELECT id FROM vaccines WHERE availability = 'Available' LIMIT 1")->fetch_assoc();

        $stmt = $conn->prepare(
            "INSERT INTO hospital_slots (hospital_id, slot_date, slot_time, capacity, booked_count)
             VALUES (?, '2099-01-05', '12:00', 2, 0)
             ON DUPLICATE KEY UPDATE capacity = 2, booked_count = 0, status = 'Open'"
        );
        $stmt->bind_param('i', $hospital['id']);
        $stmt->execute();
        $stmt->close();

        $result = create_booking_workflow(
            $conn,
            (int)$user['id'],
            (int)$child['id'],
            (int)$vaccine['id'],
            (int)$hospital['id'],
            '2099-01-05',
            '12:00'
        );

        self::assertTrue($result['ok']);
        $stmt = $conn->prepare(
            "SELECT booked_count FROM hospital_slots
             WHERE hospital_id = ? AND slot_date = '2099-01-05' AND slot_time = '12:00'"
        );
        $stmt->bind_param('i', $hospital['id']);
        $stmt->execute();
        self::assertSame(1, (int)$stmt->get_result()->fetch_assoc()['booked_count']);
        $stmt->close();
    }

    public function testNotificationFailureRollsBookingBack(): void
    {
        global $conn;
        $user = $conn->query("SELECT id FROM users WHERE email = 'parent@test.local'")->fetch_assoc();
        $child = $conn->query("SELECT id FROM children WHERE parent_id = " . (int)$user['id'] . " LIMIT 1")->fetch_assoc();
        $hospital = $conn->query("SELECT id FROM hospitals WHERE status = 'Active' LIMIT 1")->fetch_assoc();
        $vaccine = $conn->query("SELECT id FROM vaccines WHERE availability = 'Available' LIMIT 1")->fetch_assoc();

        putenv('TEST_FAIL_NOTIFICATIONS=1');
        $result = create_booking_workflow(
            $conn,
            (int)$user['id'],
            (int)$child['id'],
            (int)$vaccine['id'],
            (int)$hospital['id'],
            '2099-01-06',
            '12:00'
        );
        putenv('TEST_FAIL_NOTIFICATIONS');

        self::assertFalse($result['ok']);
        self::assertSame(500, $result['status']);
    }
}
