<?php
session_start();
header('Content-Type: application/json');

require_once '../BackendLogic/dbConn.php';

$cartCount = 0;

if (isset($_SESSION['userID'])) {

    $userID = (int)$_SESSION['userID'];

    $cntRes = $conn->query("
        SELECT COALESCE(SUM(quantity),0) AS c
        FROM tblCart
        WHERE userID = $userID
    ");

    $cartCount = (int)$cntRes->fetch_assoc()['c'];
}
elseif (isset($_SESSION['guest_cart'])) {

    $cartCount = array_sum($_SESSION['guest_cart']);
}

$conn->close();

echo json_encode([
    'count' => $cartCount
]);