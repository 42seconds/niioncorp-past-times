<?php
/**
 * checkout.php
 * Customer selects delivery method, confirms order, order is saved to tblOrders.
 * URL: checkout.php?id=LISTING_ID
 */
session_start();
if (!isset($_SESSION['userID'])) {
    header('Location: ../../html/home.php'); exit;
}
require_once '../BackendLogic/dbConn.php';

$listingID = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($listingID <= 0) { header('Location: ../../html/home.php'); exit; }

// Fetch listing
$stmt = $conn->prepare("
    SELECT l.*, u.username, u.firstName AS sellerFirst, u.lastName AS sellerLast, u.userID AS sellerUID
    FROM tblListings l
    JOIN tblUser u ON l.sellerID = u.userID
    WHERE l.listingID = ? AND l.status = 'approved'
    LIMIT 1
");
$stmt->bind_param("i", $listingID);
$stmt->execute();
$listing = $stmt->get_result()->fetch_assoc();
$stmt->close();


if (!$listing) { header('Location: ../../html/home.php'); exit; }

// Block seller buying own item
if ((int)$_SESSION['userID'] === (int)$listing['sellerUID']) {
    header('Location: ../../html/product-detail.php?id=' . $listingID); exit;
}

$error   = '';
$success = false;
$orderID = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $deliveryMethod  = htmlspecialchars(trim($_POST['deliveryMethod']  ?? ''));
    $deliveryAddress = htmlspecialchars(trim($_POST['deliveryAddress'] ?? ''));
    $buyerID         = (int)$_SESSION['userID'];
    $sellerID        = (int)$listing['sellerUID'];
    $total           = (float)$listing['price'];

    if (!$deliveryMethod) {
        $error = "Please select a delivery method.";
    } elseif (!$deliveryAddress) {
        $error = "Please enter a delivery address or PUDO locker number.";
    } else {
        $ins = $conn->prepare("
            INSERT INTO tblOrders
              (buyerID, listingID, sellerID, quantity, totalPrice, deliveryMethod, deliveryAddress, status, createdAt)
            VALUES (?, ?, ?, 1, ?, ?, ?, 'paid', NOW())
        ");
        $ins->bind_param("iiidss", $buyerID, $listingID, $sellerID, $total, $deliveryMethod, $deliveryAddress);
        if ($ins->execute()) {
            $orderID = $conn->insert_id;
            $success = true;
        } else {
            $error = "Could not place order: " . $conn->error;
        }
        $ins->close();
    }
}
$conn->close();

$initials = strtoupper(substr($_SESSION['firstName'],0,1).substr($_SESSION['lastName'],0,1));
$deliveryOptions = array_filter(array_map('trim', explode(',', $listing['delivery'] ?? '')));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Checkout – Past Times</title>
  <link rel="stylesheet" href="../../css/styles.css">
      <link rel="stylesheet" href="../css/responsive.css">

  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
  <style>
    body{background:var(--cream);font-family:var(--font-body);}
    .checkout-wrap{max-width:860px;margin:40px auto;padding:0 24px;display:grid;grid-template-columns:1fr 360px;gap:24px;align-items:start;}
    .checkout-card{background:white;border-radius:var(--radius-lg);border:1px solid var(--border-light);padding:28px;box-shadow:var(--shadow-sm);}
    .checkout-title{font-family:var(--font-display);font-size:22px;font-weight:700;margin-bottom:20px;}
    .order-summary{background:var(--bg-warm);border-radius:var(--radius);padding:16px;margin-bottom:20px;display:flex;gap:14px;align-items:center;}
    .order-thumb{width:64px;height:64px;border-radius:8px;background:var(--border);display:flex;align-items:center;justify-content:center;font-size:28px;overflow:hidden;flex-shrink:0;}
    .order-thumb img{width:100%;height:100%;object-fit:cover;}
    .delivery-options{display:grid;grid-template-columns:1fr 1fr;gap:10px;margin:10px 0 16px;}
    .delivery-opt{border:2px solid var(--border);border-radius:var(--radius);padding:14px;cursor:pointer;transition:.15s;}
    .delivery-opt:hover{border-color:var(--primary);}
    .delivery-opt input[type=radio]{accent-color:var(--primary);margin-right:8px;}
    .delivery-opt.selected{border-color:var(--primary);background:#fff8f7;}
    .price-row{display:flex;justify-content:space-between;font-size:14px;padding:8px 0;border-bottom:1px solid var(--border-light);}
    .price-row:last-child{border-bottom:none;font-weight:700;font-size:16px;}
    .escrow-badge{background:#e6faf0;border:1px solid #b2dbd7;color:#1a5c35;border-radius:8px;padding:10px 14px;font-size:13px;margin-bottom:16px;}
    .alert-error{background:#fce8e8;border:1px solid #f5b7b7;color:#8b1a14;border-radius:8px;padding:12px 16px;margin-bottom:16px;font-size:14px;}
    .success-wrap{max-width:500px;margin:80px auto;text-align:center;padding:0 24px;}
    .success-icon{font-size:64px;margin-bottom:20px;}
    .success-title{font-family:var(--font-display);font-size:28px;font-weight:700;margin-bottom:8px;}
    .success-sub{color:var(--text-muted);font-size:15px;margin-bottom:28px;line-height:1.6;}
    @media(max-width:700px){.checkout-wrap{grid-template-columns:1fr;}}
  </style>
</head>
<body>
<nav class="navbar">
  <div class="navbar-brand" onclick="location.href='../../html/home.php'"><div class="logo-icon">🏠︎</div><span style="font-family:var(--font-display);font-size:16px;font-weight:700;">Past Times</span></div>
  <div class="navbar-nav">
    <a class="nav-link" href="../../html/home.php">Explore</a>
    <a class="nav-link" href="../../html/favorites.php">Favourites</a>
  </div>
  <div class="navbar-actions">
    <div class="icon-btn">🔔</div>
    <div class="avatar-btn" onclick="location.href='../../html/dashboard.php'"><?= $initials ?></div>
  </div>
</nav>

<?php if ($success): ?>
<!-- SUCCESS STATE -->
<div class="success-wrap">
  <!-- <div class="success-icon">🎉</div> -->
  <div class="success-title">Order Placed!</div>
  <div class="success-sub">
    Your order for <strong><?= htmlspecialchars($listing['title']) ?></strong> has been placed successfully.
    Your payment is held securely in escrow until you confirm delivery.
  </div>
  <div style="background:#e6faf0;border:1px solid #b2dbd7;border-radius:8px;padding:14px 18px;margin-bottom:24px;font-size:14px;color:#1a5c35;">
    🛡 Order #<?= $orderID ?> — Funds held in escrow via Ozow.<br>
    The seller has been notified and will ship within 48 hours.
  </div>
  <div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap;">
    <a href="order-details.php?id=<?= $orderID ?>" class="btn btn-primary">View My Order</a>
    <a href="orders.php" class="btn btn-secondary">All My Orders</a>
    <a href="../../html/home.php" class="btn btn-secondary">Keep Shopping</a>
  </div>
</div>


<?php else: ?>
<!-- CHECKOUT FORM -->
<div style="max-width:860px;margin:24px auto;padding:0 24px;">
  <div style="display:flex;align-items:center;gap:8px;margin-bottom:24px;font-size:14px;color:var(--text-muted);">
    <a href="../../html/home.php" style="color:var(--text-muted);text-decoration:none;">Home</a> ›
    <a href="../../html/product-detail.php?id=<?= $listingID ?>" style="color:var(--text-muted);text-decoration:none;">
        <?= htmlspecialchars($listing['title']) ?>
    </a> ›
    <span style="color:var(--dark);font-weight:600;">Checkout</span>
  </div>
</div>

<div class="checkout-wrap">

  <!-- LEFT: FORM -->
 <div>
    <div class="checkout-card">
      <div class="checkout-title">Complete Your Order</div>

      <?php if ($error): ?>
        <div class="alert-error"><?= $error ?></div>
      <?php endif; ?>


       <form method="POST" action="checkout.php?id=<?= $listingID ?>">

        <!-- Order Summary -->
        <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:var(--text-muted);margin-bottom:8px;">ITEM</div>
        <div class="order-summary">
          <div class="order-thumb">
            <?php if (!empty($listing['imagePath'])): ?>
              <img src="../../<?= htmlspecialchars($listing['imagePath']) ?>" alt="">
            <?php else: ?>
              📦
            <?php endif; ?>
          </div>
          <div style="flex:1;">
            <div style="font-weight:600;font-size:15px;"><?= htmlspecialchars($listing['title']) ?></div>
            <div style="font-size:13px;color:var(--text-muted);margin-top:2px;"><?= htmlspecialchars($listing['category']) ?> • <?= htmlspecialchars($listing['condition_']) ?></div>
            <div style="font-size:13px;color:var(--text-muted);">by @<?= htmlspecialchars($listing['username']) ?></div>
          </div>
          <div style="font-family:var(--font-display);font-size:20px;font-weight:700;color:var(--primary);">R <?= number_format($listing['price'],2) ?></div>
        </div>

        <!-- Delivery Method -->
        <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:var(--text-muted);margin-bottom:8px;">DELIVERY METHOD</div>
        <div class="delivery-options">
          <?php
          $icons = ['Paxi'=>'📦','PUDO'=>'🔒','Aramex'=>'⚡','Collection'=>'🤝'];
          $desc  = ['Paxi'=>'Pep to Pep · 5-7 days','PUDO'=>'Locker-to-Locker · 3-5 days','Aramex'=>'Express · 2-3 days','Collection'=>'Meet in person'];
          foreach ($deliveryOptions as $opt):
            $icon = $icons[$opt] ?? '📦';
            $d    = $desc[$opt]  ?? '';
          ?>
          <label class="delivery-opt" onclick="this.classList.add('selected');document.querySelectorAll('.delivery-opt').forEach(o=>o!==this&&o.classList.remove('selected'))">
            <input type="radio" name="deliveryMethod" value="<?= htmlspecialchars($opt) ?>" required>
            <?= $icon ?> <strong><?= htmlspecialchars($opt) ?></strong>
            <div style="font-size:12px;color:var(--text-muted);margin-top:4px;"><?= $d ?></div>
          </label>
          <?php endforeach; ?>
          <?php if (empty($deliveryOptions)): ?>
            <label class="delivery-opt selected">
              <input type="radio" name="deliveryMethod" value="PUDO" checked>
              <strong>PUDO Locker</strong>
              <div style="font-size:12px;color:var(--text-muted);margin-top:4px;">Locker-to-Locker · 3-5 days</div>
            </label>
          <?php endif; ?>
        </div>

        <!-- Delivery Address -->
        <div class="form-group">
          <label class="form-label">DELIVERY ADDRESS / PUDO LOCKER</label>
          <textarea class="form-input" name="deliveryAddress" rows="3"
            placeholder="e.g. PUDO Locker — The Paddocks, Milnerton, Cape Town&#10;or: 12 Loop Street, Cape Town CBD, 8001"
            required></textarea>
        </div>

        <div class="escrow-badge">🛡 Your payment is held in <strong>secure escrow</strong> and only released to the seller once you confirm receipt of your item.</div>

        <button class="btn btn-primary btn-lg" type="submit" style="width:100%;">
          Place Order — R <?= number_format($listing['price'],2) ?>
        </button>
        <div style="font-size:12px;color:var(--text-muted);text-align:center;margin-top:8px;">By placing this order you agree to our Terms of Service.</div>
      </form>
    </div>
  </div>



  <!-- RIGHT: PRICE SUMMARY -->
 <div>
    <div class="checkout-card">
      <div class="checkout-title" style="font-size:18px;">Order Summary</div>
      <div class="price-row"><span>Item price</span><span>R <?= number_format($listing['price'],2) ?></span></div>
      <div class="price-row"><span>Platform fee</span><span style="color:#1a5c35;">Free 🌿</span></div>
      <div class="price-row"><span>Delivery</span><span>Calculated at delivery</span></div>
      <div class="price-row" style="margin-top:8px;"><span>Total</span><span>R <?= number_format($listing['price'],2) ?></span></div>

      <div style="margin-top:20px;background:var(--bg-warm);border-radius:var(--radius);padding:14px;">
        <div style="font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.07em;margin-bottom:8px;">Seller</div>
        <div style="display:flex;align-items:center;gap:10px;">
          <div style="width:36px;height:36px;border-radius:50%;background:var(--primary);color:white;display:flex;align-items:center;justify-content:center;font-size:14px;font-weight:700;">
            <?= strtoupper(substr($listing['sellerFirst'],0,1).substr($listing['sellerLast'],0,1)) ?>
          </div>
          <div>
            <div style="font-weight:600;font-size:14px;">@<?= htmlspecialchars($listing['username']) ?></div>
            <div style="font-size:12px;color:var(--text-muted);">Verified Seller</div>
          </div>
        </div>
      </div>

      <div style="margin-top:16px;font-size:12px;color:var(--text-muted);line-height:1.7;">
        🔒 SSL Secured<br>
        🛡 Ozow Escrow Protected<br>
        📦 PUDO / Paxi / Aramex Supported<br>
        🌿 Zero seller fees
      </div>
    </div>
  </div>

</div>
<?php endif; ?>


<script src="../../javascript/script.js"></script>
</body>
</html>
