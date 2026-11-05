
<?php

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$host = "localhost";
$username = "root";
$password = ""; // Default local XAMPP setup
$database = "shopease_db";

try {
    $conn = new mysqli(
        $host,
        $username,
        $password,
        $database
    );

    $conn->set_charset("utf8mb4");

} catch (mysqli_sql_exception $e) {
    error_log("ShopEase database connection failed: " . $e->getMessage());

    http_response_code(500);
    exit("The store is temporarily unavailable. Please try again later.");
}