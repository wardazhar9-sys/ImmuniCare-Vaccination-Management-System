<?php

mysqli_report(MYSQLI_REPORT_OFF);

$host = getenv('DB_HOST') ?: 'localhost';
$port = (int)(getenv('DB_PORT') ?: 3306);
$user = getenv('DB_USER') ?: 'root';
$password = getenv('DB_PASSWORD') ?: '';
$database = getenv('DB_NAME') ?: 'vaccination_management_system';

if (!preg_match('/^[A-Za-z0-9_$]+$/', $database)) {
    throw new RuntimeException('Invalid database name.');
}

$server = mysqli_init();
if (!mysqli_real_connect($server, $host, $user, $password, '', $port)) {
    throw new RuntimeException(mysqli_connect_error());
}

$safe_database = str_replace('`', '``', $database);
$db_check = $server->prepare(
    "SELECT SCHEMA_NAME FROM information_schema.schemata WHERE SCHEMA_NAME = ?"
);
$db_check->bind_param('s', $database);
$db_check->execute();
$exists = (bool)$db_check->get_result()->fetch_row();
$db_check->close();

if (!$exists && !$server->query(
    "CREATE DATABASE `{$safe_database}`
     CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci"
)) {
    throw new RuntimeException($server->error);
}
$server->close();

$conn = new mysqli($host, $user, $password, $database, $port);
$conn->set_charset('utf8mb4');

$table = $conn->query(
    "SELECT 1 FROM information_schema.tables
     WHERE table_schema = DATABASE() AND table_name = 'users'"
)->fetch_row();

if (!$table) {
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

$conn->close();
require __DIR__ . '/migrate.php';
