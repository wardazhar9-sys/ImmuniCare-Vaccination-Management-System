<?php

$host = getenv('DB_HOST') ?: 'db';
$port = (int)(getenv('DB_PORT') ?: 3306);
$user = getenv('DB_USER') ?: 'tester';
$password = getenv('DB_PASSWORD') ?: 'tester';
$name = getenv('DB_NAME') ?: 'vaccination_test';

$conn = new mysqli($host, $user, $password, $name, $port);
if (getenv('RESET_TEST_DB') !== '0') {
    $safe_name = str_replace('`', '``', $name);
    $root_conn = new mysqli(
        $host,
        getenv('DB_ROOT_USER') ?: 'root',
        getenv('DB_ROOT_PASSWORD') ?: 'root',
        '',
        $port
    );
    $root_conn->query("DROP DATABASE IF EXISTS `{$safe_name}`");
    $root_conn->query(
        "CREATE DATABASE `{$safe_name}` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci"
    );
    $root_conn->close();
    $conn->close();
    $conn = new mysqli($host, $user, $password, $name, $port);
}
$conn->set_charset('utf8mb4');

$exists = $conn->query(
    "SELECT 1 FROM information_schema.tables
     WHERE table_schema = DATABASE() AND table_name = 'schema_migrations'"
)->fetch_row();

if (!$exists) {
    $sql = file_get_contents(__DIR__ . '/../database/install.sql');
    if ($sql === false || !$conn->multi_query($sql)) {
        throw new RuntimeException($conn->error);
    }

    do {
        if ($result = $conn->store_result()) {
            $result->free();
        }
    } while ($conn->more_results() && $conn->next_result());

    if ($conn->errno) {
        throw new RuntimeException($conn->error);
    }
}

$seed = file_get_contents(__DIR__ . '/../tests/fixtures/seed.sql');
if ($seed === false || !$conn->multi_query($seed)) {
    throw new RuntimeException($conn->error);
}

do {
    if ($result = $conn->store_result()) {
        $result->free();
    }
} while ($conn->more_results() && $conn->next_result());

if ($conn->errno) {
    throw new RuntimeException($conn->error);
}

echo "Database initialized\n";
