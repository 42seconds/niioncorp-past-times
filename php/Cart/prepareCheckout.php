<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['userID'])) {
    echo json_encode(['ok' => false, 'error' => 'not_logged_in']);
    exit;
}

require_once '../BackendLogic/dbConn.php';

$userID = (int)$_SESSION['userID'];

// The cart sends 'selected_items[]' from checkbox name
// Also support 'listing_ids[]' as fallback
$listingIDs = isset($_POST['selected_items']) ? (array)$_POST['selected_items'] : [];
if (empty($listingIDs) && isset($_POST['listing_ids'])) {
    $listingIDs = (array)$_POST['listing_ids'];
}

// Get quantities from the cart's request
$quantities = isset($_POST['quantities']) ? (array)$_POST['quantities'] : [];
$notes = isset($_POST['notes']) ? (array)$_POST['notes'] : [];

// Debug logging
error_log("prepareCheckout: listingIDs = " . print_r($listingIDs, true));
error_log("prepareCheckout: quantities = " . print_r($quantities, true));

if (empty($listingIDs)) {
    echo json_encode(['ok' => false, 'error' => 'no_items_selected']);
    exit;
}

// Sanitize
$listingIDs = array_map('intval', $listingIDs);
$placeholders = implode(',', array_fill(0, count($listingIDs), '?'));

// Fetch items from cart with their quantities
$sql = "
    SELECT c.listingID, c.quantity AS cartQuantity, c.note AS cartNote,
           l.title, l.category, l.condition_, l.price, l.imagePath, l.delivery, l.status,
           u.username, u.userID AS sellerUID
    FROM tblCart c
    JOIN tblListings l ON c.listingID = l.listingID
    JOIN tblUser u     ON l.sellerID  = u.userID
    WHERE c.userID = ? AND c.listingID IN ($placeholders) AND l.status = 'approved'
";

$stmt = $conn->prepare($sql);
$types = "i" . str_repeat('i', count($listingIDs));
$params = array_merge([$userID], $listingIDs);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$conn->close();

error_log("prepareCheckout: items found = " . print_r($items, true));

if (empty($items)) {
    echo json_encode(['ok' => false, 'error' => 'no_valid_items']);
    exit;
}

// Override quantities from the request if provided
foreach ($items as &$item) {
    $listingID = $item['listingID'];
    if (isset($quantities[$listingID])) {
        $item['cartQuantity'] = (int)$quantities[$listingID];
    }
    // Merge notes
    if (isset($notes[$listingID])) {
        $item['cartNote'] = $notes[$listingID];
    }
}
unset($item);

// Store in session
$_SESSION['multi_checkout_items'] = $items;
$_SESSION['multi_checkout_notes'] = $notes;

error_log("prepareCheckout: stored " . count($items) . " items in session");

echo json_encode(['ok' => true]);
