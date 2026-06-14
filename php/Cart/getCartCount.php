<?php
session_start();
header('Content-Type: application/json');

require_once '../BackendLogic/dbConn.php';

$cartCount = 0;

if (isset($_SESSION['userID'])) {
    $userID = (int)$_SESSION['userID'];
    $cntRes = $conn->query("SELECT COUNT(*) as c FROM tblCart WHERE userID=$userID");
    $cartCount = (int)$cntRes->fetch_assoc()['c'];
} elseif (isset($_SESSION['guest_cart'])) {
    $cartCount = count($_SESSION['guest_cart']);
}

$conn->close();
echo json_encode(['count' => $cartCount]);
?>