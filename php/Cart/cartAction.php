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
$s = $conn->prepare("
    SELECT sellerID
    FROM tblListings
    WHERE listingID = ?
      AND status = 'approved'
");
$s->bind_param("i", $listingID);
$s->execute();
$listing = $s->get_result()->fetch_assoc();
$s->close();

if (!$listing) {
    echo json_encode(['error' => 'not_available']);
    exit;
}

// Prevent users buying their own listings
if (
    isset($_SESSION['userID']) &&
    (int)$listing['sellerID'] === (int)$_SESSION['userID']
) {
    echo json_encode(['error' => 'own_listing']);
    exit;
}

// =========================
// LOGGED-IN USER
// =========================
if (isset($_SESSION['userID'])) {

    $userID = (int)$_SESSION['userID'];

    if ($action === 'add') {

        $ins = $conn->prepare("
            INSERT INTO tblCart
                (userID, listingID, quantity)
            VALUES
                (?, ?, 1)
            ON DUPLICATE KEY UPDATE
                quantity = quantity + 1,
                addedAt = NOW()
        ");

        $ins->bind_param("ii", $userID, $listingID);
        $ins->execute();
        $ins->close();

        $cnt = $conn->query("
            SELECT COALESCE(SUM(quantity),0) AS c
            FROM tblCart
            WHERE userID = $userID
        ")->fetch_assoc()['c'];

        echo json_encode([
            'action' => 'added',
            'cartCount' => (int)$cnt
        ]);
    } elseif ($action === 'remove') {

        $d = $conn->prepare("
            DELETE FROM tblCart
            WHERE userID = ?
              AND listingID = ?
        ");

        $d->bind_param("ii", $userID, $listingID);
        $d->execute();
        $d->close();

        $cnt = $conn->query("
            SELECT COALESCE(SUM(quantity),0) AS c
            FROM tblCart
            WHERE userID = $userID
        ")->fetch_assoc()['c'];

        echo json_encode([
            'action' => 'removed',
            'cartCount' => (int)$cnt
        ]);
    } elseif ($action === 'update') {

        $note = htmlspecialchars(trim($_POST['note'] ?? ''));

        $u = $conn->prepare("
            UPDATE tblCart
            SET note = ?
            WHERE userID = ?
              AND listingID = ?
        ");

        $u->bind_param("sii", $note, $userID, $listingID);
        $u->execute();
        $u->close();

        echo json_encode([
            'action' => 'updated'
        ]);
    } else {
        echo json_encode(['error' => 'unknown_action']);
    }

    $conn->close();
    exit;
}

// =========================
// GUEST USER
// =========================

if (!isset($_SESSION['guest_cart'])) {
    $_SESSION['guest_cart'] = [];
}

if ($action === 'add') {

    if (isset($_SESSION['guest_cart'][$listingID])) {
        $_SESSION['guest_cart'][$listingID]++;
    } else {
        $_SESSION['guest_cart'][$listingID] = 1;
    }

    echo json_encode([
        'action' => 'guest_added',
        'guestCount' => array_sum($_SESSION['guest_cart'])
    ]);
} elseif ($action === 'remove') {

    unset($_SESSION['guest_cart'][$listingID]);

    echo json_encode([
        'action' => 'guest_removed',
        'guestCount' => array_sum($_SESSION['guest_cart'])
    ]);
} else {
    echo json_encode(['error' => 'unknown_action']);
}

$conn->close();
