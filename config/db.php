<?php

mysqli_report(MYSQLI_REPORT_OFF);

$host = getenv('DB_HOST') ?: 'localhost';
$username = getenv('DB_USER') ?: 'root';
$password = getenv('DB_PASSWORD') ?: '';
$database = getenv('DB_NAME') ?: 'vaccination_management_system';
$port = (int)(getenv('DB_PORT') ?: 3306);

$conn = mysqli_init();
$connected = mysqli_real_connect($conn, $host, $username, $password, $database, $port);

if (!$connected) {
    error_log('Database connection failed: ' . mysqli_connect_error());
    http_response_code(503);
    exit('The service is temporarily unavailable.');
}

if (!mysqli_set_charset($conn, 'utf8mb4')) {
    error_log('Database charset setup failed: ' . mysqli_error($conn));
}