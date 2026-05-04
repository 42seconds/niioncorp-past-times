<?php
session_start();
if (!isset($_SESSION['adminID']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../admin_login.php'); exit;
}
require_once('../BackendLogic/dbConn.php');

$action = $_POST['action'] ?? '';
$userID = (int)($_POST['userID'] ?? 0);

if ($userID <= 0) {
    header('Location: userManagement.php?err=Invalid+user'); exit;
}

if ($action === 'verify') {
    $stmt = $conn->prepare("UPDATE tblUser SET status='verified' WHERE userID=?");
    $stmt->bind_param("i", $userID);
    $stmt->execute();
    $msg = urlencode('User verified successfully.');
    header("Location: userManagement.php?msg=$msg"); exit;

} elseif ($action === 'delete') {
    $stmt = $conn->prepare("DELETE FROM tblUser WHERE userID=? AND role != 'admin'");
    $stmt->bind_param("i", $userID);
    $stmt->execute();
    $msg = urlencode('User deleted.');
    header("Location: userManagement.php?msg=$msg"); exit;

} else {
    header('Location: userManagement.php?err=Unknown+action'); exit;
}
