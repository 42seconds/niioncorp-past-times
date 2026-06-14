<?php
session_start();
header('Content-Type: application/json');

require_once '../BackendLogic/dbConn.php';

$action = $_POST['action'] ?? '';
$listingID = (int)($_POST['listingID'] ?? 0);

if (!$listingID) {
    echo json_encode(['error' => 'invalid']);
    exit;
}

// Sellers cannot add to cart
if (isset($_SESSION['role']) && in_array($_SESSION['role'], ['seller', 'admin'])) {
    echo json_encode(['error' => 'sellers_cannot_buy']);
    exit;
}

// Check if listing exists and is available
$s = $conn->prepare("SELECT sellerID FROM tblListings WHERE listingID=? AND status='approved'");
$s->bind_param("i", $listingID);
$s->execute();
$listing = $s->get_result()->fetch_assoc();
$s->close();

if (!$listing) {
    echo json_encode(['error' => 'not_available']);
    exit;
}

// Check if user is trying to buy their own listing
if (isset($_SESSION['userID']) && (int)$listing['sellerID'] === (int)$_SESSION['userID']) {
    echo json_encode(['error' => 'own_listing']);
    exit;
}

// LOGGED IN USER
if (isset($_SESSION['userID'])) {
    $userID = (int)$_SESSION['userID'];
    
    if ($action === 'add') {
        $ins = $conn->prepare("INSERT INTO tblCart (userID, listingID) VALUES (?, ?) ON DUPLICATE KEY UPDATE addedAt=NOW()");
        $ins->bind_param("ii", $userID, $listingID);
        $ins->execute();
        $ins->close();
        
        $cnt = $conn->query("SELECT COUNT(*) c FROM tblCart WHERE userID=$userID")->fetch_assoc()['c'];
        echo json_encode(['action' => 'added', 'cartCount' => (int)$cnt]);
    } elseif ($action === 'remove') {
        $d = $conn->prepare("DELETE FROM tblCart WHERE userID=? AND listingID=?");
        $d->bind_param("ii", $userID, $listingID);
        $d->execute();
        $d->close();
        
        $cnt = $conn->query("SELECT COUNT(*) c FROM tblCart WHERE userID=$userID")->fetch_assoc()['c'];
        echo json_encode(['action' => 'removed', 'cartCount' => (int)$cnt]);
    } elseif ($action === 'update') {
        $note = htmlspecialchars(trim($_POST['note'] ?? ''));
        $u = $conn->prepare("UPDATE tblCart SET note=? WHERE userID=? AND listingID=?");
        $u->bind_param("sii", $note, $userID, $listingID);
        $u->execute();
        $u->close();
        echo json_encode(['action' => 'updated']);
    }
    
    $conn->close();
    exit;
}

// GUEST USER - Store in session
if (!isset($_SESSION['guest_cart'])) {
    $_SESSION['guest_cart'] = [];
}

if ($action === 'add') {
    if (!in_array($listingID, $_SESSION['guest_cart'])) {
        $_SESSION['guest_cart'][] = $listingID;
    }
    echo json_encode(['action' => 'guest_added', 'guestCount' => count($_SESSION['guest_cart'])]);
} elseif ($action === 'remove') {
    $_SESSION['guest_cart'] = array_diff($_SESSION['guest_cart'], [$listingID]);
    echo json_encode(['action' => 'guest_removed', 'guestCount' => count($_SESSION['guest_cart'])]);
} else {
    echo json_encode(['error' => 'unknown_action']);
}

$conn->close();
?>