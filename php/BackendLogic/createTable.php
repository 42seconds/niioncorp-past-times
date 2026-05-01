<?php
/**
 * createTable.php
 * Past Times – Table Setup Script
 *
 * Each time this script runs it will:
 *   1. Drop tblUser if it exists
 *   2. Re-create tblUser
 *   3. Load data from userData.txt using LOAD DATA LOCAL INFILE
 *
 * Usage: run once via browser or CLI after setting up your DB.
 * CLI:  php createTable.php
 */

// Pull in the database connection
include ('../php/BackendLogic/dbConn.php');

// ── Step 1: Drop table if it exists ──────────────────────────────────────────
$dropSQL = "DROP TABLE IF EXISTS tblUser";
if (!$conn->query($dropSQL)) {
    die("Error dropping table: " . $conn->error);
}
echo "✔ tblUser dropped (if it existed).<br>\n";

// ── Step 2: (Re)create tblUser ───────────────────────────────────────────────
$createSQL = "
CREATE TABLE tblUser (
    userID   INT auto_increment  NOT NULL primary key,
    username     VARCHAR(50)    NOT NULL,
    firstName    VARCHAR(50)    NOT NULL,
    lastName     VARCHAR(50)    NOT NULL,
    email        VARCHAR(100)   NOT NULL UNIQUE,
    passwordHash VARCHAR(255)   NOT NULL,
    role         ENUM('admin','customer') NOT NULL DEFAULT 'customer',
    status       ENUM('pending','verified') NOT NULL DEFAULT 'pending',
    createdAt    DATE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";

if (!$conn->query($createSQL)) {
    die("Error creating table: " . $conn->error);
}
echo " tblUser created successfully.<br>\n";

// ── Step 3: Load data from userData.txt ──────────────────────────────────────
// The file uses pipe ( | ) as delimiter and Unix line endings.
// Adjust the path below to match where userData.txt lives on your server.
$dataFile = realpath(__DIR__ . '/../userData.txt');

if (!$dataFile || !file_exists($dataFile)) {
    die("Error: userData.txt not found at expected path. Checked: " . __DIR__ . '/../userData.txt');
}

// Enable LOCAL INFILE on this connection
$conn->options(MYSQLI_OPT_LOCAL_INFILE, true);

$loadSQL = "
LOAD DATA LOCAL INFILE '$dataFile'
INTO TABLE tblUser
FIELDS TERMINATED BY '|'
LINES TERMINATED BY '\n'
(userID, username, firstName, lastName, email, passwordHash, role, status, createdAt)
";

try{

$conn->query($loadSQL);

echo " Data loaded from userData.txt successfully.<br>\n";
echo " Rows affected: " . $conn->affected_rows . "<br>\n";
} catch (mysqli_sql_exception $e) {
    // Fallback: manual INSERT if LOAD DATA LOCAL INFILE is disabled on the server
    echo "LOAD DATA LOCAL INFILE not available – falling back to manual INSERT.<br>\n";

    $lines = file($dataFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $stmt  = $conn->prepare(
        "INSERT INTO tblUser (userID, username, firstName, lastName, email, passwordHash, role, status, createdAt)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );

    foreach ($lines as $line) {
        $cols = explode('|', trim($line));
        if (count($cols) < 9) continue;
        [$id, $uname, $fn, $ln, $em, $pw, $role, $status, $created] = $cols;
        $stmt->bind_param("issssssss", $id, $uname, $fn, $ln, $em, $pw, $role, $status, $created);
        $stmt->execute();
    }
    $stmt->close();
    echo " Data inserted via prepared statements.<br>\n";
}