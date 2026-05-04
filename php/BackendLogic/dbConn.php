<?php
/**
 * DBConn.php
 * Past Times – Database Connection File
 * Establishes a MySQLi connection to the past_times database.
 */

define('DB_HOST', 'localhost');
define('DB_USER', 'root');        // Change to your MySQL username
define('DB_PASSWORD', '');            // Change to your MySQL password
define('DB_NAME', 'PastTimes');
define('DB_PORT', 3306);           // Change if your MySQL runs on a different port

//mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT); // Enable error reporting for debugging

$conn = new mysqli(DB_HOST, DB_USER, DB_PASSWORD,'', DB_PORT);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$createDB = "create database if not exists ".DB_NAME ;//create database  PastTimes if it doesnt exists 

if (!$conn->query($createDB)) {
    die("Error creating database: " . $conn->error);
}

if (!$conn->select_db(DB_NAME)) {
    die("Error selecting database: " . $conn->error);
}

//$conn -> select_db(DB_NAME);
if (!$conn->set_charset("utf8mb4")) {
    die("charset error: " . $conn->error);

}
$conn->options(MYSQLI_OPT_LOCAL_INFILE, true); // Enable LOAD DATA LOCAL INFILE if needed
