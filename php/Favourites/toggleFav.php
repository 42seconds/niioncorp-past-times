<?php
/**
 * toggleFav.php
 * AJAX endpoint — adds or removes a listing from tblFavourites.
 * Called by the heart button on home.php and product-detail.php.
 *
 * POST body: { listingID: int }
 * Returns JSON: { action: "added"|"removed", total: int }
 */
session_start();
header('Content-Type: application/json');

// Must be logged in
if (!isset($_SESSION['userID'])) {
    echo json_encode(['error' => 'not_logged_in']);
    exit;
}

require_once '../BackendLogic/dbConn.php';

$userID    = (int)$_SESSION['userID'];
$listingID = (int)($_POST['listingID'] ?? 0);

if ($listingID <= 0) {
    echo json_encode(['error' => 'invalid_listing']);
    exit;
}

// Check if it's already saved
$check = $conn->prepare("SELECT favID FROM tblFavourites WHERE userID=? AND listingID=? LIMIT 1");
$check->bind_param("ii", $userID, $listingID);
$check->execute();
$exists = $check->get_result()->num_rows > 0;
$check->close();

if ($exists) {
    // Remove
    $del = $conn->prepare("DELETE FROM tblFavourites WHERE userID=? AND listingID=?");
    $del->bind_param("ii", $userID, $listingID);
    $del->execute();
    $del->close();
    $action = 'removed';
} else {
    // Add
    $ins = $conn->prepare("INSERT IGNORE INTO tblFavourites (userID, listingID) VALUES (?,?)");
    $ins->bind_param("ii", $userID, $listingID);
    $ins->execute();
    $ins->close();
    $action = 'added';
}

// Return updated count for this user
$total = (int)$conn->query("SELECT COUNT(*) c FROM tblFavourites WHERE userID=$userID")->fetch_assoc()['c'];
$conn->close();

echo json_encode(['action' => $action, 'total' => $total]);
