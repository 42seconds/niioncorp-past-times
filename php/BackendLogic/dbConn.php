<?php
/**
 * DBConn.php
 * Past Times – Database Connection File
 * Establishes a MySQLi connection to the past_times database.
 */

define('DB_HOST', 'localhost');
define('DB_USER', 'root');        
define('DB_PASSWORD', '');        
define('DB_NAME', 'PastTimes');
define('DB_PORT', 3306);          


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
$conn->options(MYSQLI_OPT_LOCAL_INFILE, true); // Enables LOAD DATA LOCAL INFILE 
