<?php
session_start();

if (!isset($_SESSION['adminID']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../admin_login.php');
    exit;
}

require_once('../BackendLogic/dbConn.php');

$action = $_POST['action'] ?? '';
$listingID = (int)($_POST['listingID'] ?? 0);

if ($listingID <= 0) {
    header('Location: listingManagement.php?err=Invalid+listing');
    exit;
}

if ($action === 'approve') {

    $stmt = $conn->prepare("UPDATE tblListings SET status='approved' WHERE listingID=?");
    $stmt->bind_param("i", $listingID);
    $stmt->execute();

    header("Location: listingManagement.php?msg=Listing+approved");
    exit;

} elseif ($action === 'reject') {

    $stmt = $conn->prepare("UPDATE tblListings SET status='rejected' WHERE listingID=?");
    $stmt->bind_param("i", $listingID);
    $stmt->execute();

    header("Location: listingManagement.php?msg=Listing+rejected");
    exit;

} else {
    header("Location: listingManagement.php?err=Unknown+action");
    exit;
}