<?php

session_start();
if (!isset($_SESSION['userID'])) {
    header('Location: ../../html/home.php'); exit;
}
require_once '../BackendLogic/dbConn.php';

$orderID  = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$buyerID  = (int)$_SESSION['userID'];
$initials = strtoupper(substr($_SESSION['firstName'],0,1).substr($_SESSION['lastName'],0,1));

$stmt = $conn->prepare("
    SELECT o.*, l.title, l.imagePath, l.category, l.condition_, l.description,
           l.listingID, u.username AS sellerName, u.firstName AS sellerFirst, u.lastName AS sellerLast
    FROM tblOrders o
    JOIN tblListings l ON o.listingID = l.listingID
    JOIN tblUser u     ON o.sellerID  = u.userID
    WHERE o.orderID = ? AND o.buyerID = ?
    LIMIT 1
");
$stmt->bind_param("ii", $orderID, $buyerID);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$order) { header('Location: orders.php'); exit; }

// Handle confirm delivery
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    if ($action === 'confirm_delivery' && $order['status'] === 'shipped') {
        $conn->query("UPDATE tblOrders SET status='delivered' WHERE orderID=$orderID AND buyerID=$buyerID");
        $order['status'] = 'delivered';
        $msg = 'delivery_confirmed';
    } elseif ($action === 'cancel' && in_array($order['status'], ['pending','paid'])) {
        $conn->query("UPDATE tblOrders SET status='cancelled' WHERE orderID=$orderID AND buyerID=$buyerID");
        $order['status'] = 'cancelled';
        $msg = 'cancelled';
    }

    if ($action === 'pay' && $order['status'] === 'pending') {
    $conn->query("UPDATE tblOrders 
                  SET status='paid' 
                  WHERE orderID=$orderID AND buyerID=$buyerID");

    $order['status'] = 'paid';
    $msg = 'paid';
}
}
$conn->close();

$statusColour = [
    'pending'   => ['bg'=>'#fff3e0','c'=>'#7a4f00','b'=>'#ffe0b2','label'=>'⏳ Pending Payment'],
    'paid'      => ['bg'=>'#e8f0fe','c'=>'#1a3c8b','b'=>'#c5d5fb','label'=>'💳 Paid — Awaiting Shipment'],
    'shipped'   => ['bg'=>'#e6faf0','c'=>'#1a5c35','b'=>'#b2dbd7','label'=>'🚐 Shipped'],
    'delivered' => ['bg'=>'#e6faf0','c'=>'#1a5c35','b'=>'#b2dbd7','label'=>'✅ Delivered'],
    'cancelled' => ['bg'=>'#fce8e8','c'=>'#8b1a14','b'=>'#f5b7b7','label'=>'✕ Cancelled'],
    'refunded'  => ['bg'=>'#fce8e8','c'=>'#8b1a14','b'=>'#f5b7b7','label'=>'↩ Refunded'],
];
$sc = $statusColour[$order['status']] ?? $statusColour['pending'];

// Timeline steps
$timeline = [
    ['key'=>'pending',    'label'=>'Order Placed'],
    ['key'=>'paid',      'label'=>'Payment Confirmed'],
    ['key'=>'shipped',   'label'=>'Shipped'],
    ['key'=>'delivered', 'label'=>'Delivered'],
];
$statusOrder = ['pending','paid','shipped','delivered','cancelled','refunded'];
$currentIdx  = array_search($order['status'], $statusOrder);




?>


<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Order #<?= $orderID ?> – Past Times</title>
  <link rel="stylesheet" href="../../css/styles.css">
      <link rel="stylesheet" href="../css/responsive.css">

  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
  <style>
    body{background:var(--cream);font-family:var(--font-body);}
    .detail-wrap{max-width:860px;margin:36px auto;padding:0 24px;display:grid;grid-template-columns:1fr 320px;gap:24px;align-items:start;}
    .detail-card{background:white;border-radius:var(--radius-lg);border:1px solid var(--border-light);padding:28px;box-shadow:var(--shadow-sm);margin-bottom:20px;}
    .detail-title{font-family:var(--font-display);font-size:20px;font-weight:700;margin-bottom:16px;}
    .status-pill{display:inline-block;padding:5px 14px;border-radius:999px;font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;}
    /* Timeline */
    .timeline{display:flex;align-items:flex-start;justify-content:space-between;position:relative;padding:0 0 8px;}
    .timeline::before{content:'';position:absolute;top:20px;left:0;right:0;height:3px;background:var(--border);z-index:0;}
    .timeline-step{display:flex;flex-direction:column;align-items:center;z-index:1;flex:1;}
    .timeline-icon{width:40px;height:40px;border-radius:50%;background:var(--border);color:var(--text-muted);display:flex;align-items:center;justify-content:center;font-size:16px;margin-bottom:8px;border:3px solid white;position:relative;}
    .timeline-icon.done{background:var(--primary);color:white;box-shadow:0 0 0 3px rgba(0,0,0,.06);}
    .timeline-icon.active{background:var(--dark);color:white;}
    .timeline-label{font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:var(--text-muted);text-align:center;}
    .timeline-label.done,.timeline-label.active{color:var(--dark);}
    /* Item row */
    .item-row{display:flex;gap:14px;align-items:center;padding:16px 0;border-bottom:1px solid var(--border-light);}
    .item-thumb{width:64px;height:64px;border-radius:8px;background:var(--bg-warm);display:flex;align-items:center;justify-content:center;font-size:28px;overflow:hidden;flex-shrink:0;}
    .item-thumb img{width:100%;height:100%;object-fit:cover;}
    .info-row{display:flex;justify-content:space-between;font-size:14px;padding:8px 0;border-bottom:1px solid var(--border-light);}
    .info-row:last-child{border-bottom:none;}
    .alert-success{background:#e6faf0;border:1px solid #b2dbd7;color:#1a5c35;border-radius:8px;padding:12px 16px;margin-bottom:16px;font-size:14px;}
    @media(max-width:700px){.detail-wrap{grid-template-columns:1fr;}}
  </style>
</head>
<body>
<nav class="navbar">
  <div class="navbar-brand" onclick="location.href='../../html/home.php'"><div class="logo-icon">🏠︎</div><span style="font-family:var(--font-display);font-size:16px;font-weight:700;">Past Times</span></div>
  <div class="navbar-nav">
    <a class="nav-link" href="../../html/home.php">Explore</a>
    <a class="nav-link" href="orders.php">My Orders</a>
  </div>
  <div class="navbar-actions">
    <div class="icon-btn">🔔</div>
    <div class="avatar-btn" onclick="location.href='../../html/dashboard.php'"><?= $initials ?></div>
  </div>
</nav>

<div style="max-width:860px;margin:20px auto;padding:0 24px;font-size:14px;color:var(--text-muted);">
  <a href="../../html/home.php" style="color:var(--text-muted);text-decoration:none;">Home</a> ›
  <a href="orders.php" style="color:var(--text-muted);text-decoration:none;">My Orders</a> ›
  <span style="color:var(--dark);font-weight:600;">Order #<?= $orderID ?></span>
</div>

<div class="detail-wrap">
  <!-- LEFT -->
  <div>

    <?php if ($msg === 'delivery_confirmed'): ?>
      <div class="alert-success"> Delivery confirmed! Your escrow funds have been released to the seller. Thank you for shopping on Past Times.</div>
    <?php elseif ($msg === 'cancelled'): ?>
      <div class="alert-success" style="background:#fce8e8;border-color:#f5b7b7;color:#8b1a14;">Order cancelled. A refund will be processed within 3–5 business days if payment was made.</div>
    <?php endif; ?>

    <!-- STATUS + TIMELINE -->
    <div class="detail-card">
      <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
        <div>
          <div style="font-size:12px;color:var(--text-muted);margin-bottom:4px;">ORDER #<?= $orderID ?></div>
          <div style="font-family:var(--font-display);font-size:22px;font-weight:700;">Order Status</div>
        </div>
        <span class="status-pill" style="background:<?= $sc['bg'] ?>;color:<?= $sc['c'] ?>;border:1px solid <?= $sc['b'] ?>;">
          <?= $sc['label'] ?>
        </span>
      </div>

      <?php if (!in_array($order['status'], ['cancelled','refunded'])): ?>
      <div class="timeline">
        <?php foreach ($timeline as $step):
          $stepIdx = array_search($step['key'], $statusOrder);
          $isDone   = $stepIdx < $currentIdx;
          $isActive = $stepIdx === $currentIdx;
          $cls = $isDone ? 'done' : ($isActive ? 'active' : '');
        ?>
        <div class="timeline-step">
          <div class="timeline-icon <?= $cls ?>"><?= $isDone ? '✓' : '' ?></div>
          <div class="timeline-label <?= $cls ?>"><?= $step['label'] ?></div>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>

    <!-- ITEM -->
    <div class="detail-card">
      <div class="detail-title">Item</div>
      <div class="item-row">
        <div class="item-thumb">
          <?php if (!empty($order['imagePath'])): ?>
            <img src="../../<?= htmlspecialchars($order['imagePath']) ?>" alt="">
          <?php endif; ?>
        </div>
        <div style="flex:1;">
          <div style="font-weight:600;font-size:16px;"><?= htmlspecialchars($order['title']) ?></div>
          <div style="font-size:13px;color:var(--text-muted);margin-top:2px;"><?= htmlspecialchars($order['category']) ?> • <?= htmlspecialchars($order['condition_']) ?></div>
          <a href="../../html/product-detail.php?id=<?= $order['listingID'] ?>" style="font-size:12px;color:var(--primary);font-weight:600;text-decoration:none;">View Listing →</a>
        </div>
        <div style="font-family:var(--font-display);font-size:20px;font-weight:700;color:var(--primary);">R <?= number_format($order['totalPrice'],2) ?></div>
      </div>
    </div>

    <!-- DELIVERY INFO -->
    <div class="detail-card">
      <div class="detail-title">Delivery Details</div>
      <div class="info-row"><span style="color:var(--text-muted);">Method</span><span style="font-weight:600;"><?= htmlspecialchars($order['deliveryMethod']) ?></span></div>
      <div class="info-row"><span style="color:var(--text-muted);">Address</span><span style="font-weight:500;max-width:320px;text-align:right;"><?= nl2br(htmlspecialchars($order['deliveryAddress'])) ?></span></div>
      <div class="info-row"><span style="color:var(--text-muted);">Ordered on</span><span><?= date('d M Y, H:i', strtotime($order['createdAt'])) ?></span></div>
      <div class="info-row"><span style="color:var(--text-muted);">Last updated</span><span><?= date('d M Y, H:i', strtotime($order['updatedAt'])) ?></span></div>
    </div>

    <!-- ACTIONS -->
    <div class="detail-card">
      <div class="detail-title">Actions</div>
      <div style="display:flex;gap:12px;flex-wrap:wrap;">

        <?php if ($order['status'] === 'shipped'): ?>
        <form method="POST">
          <input type="hidden" name="action" value="confirm_delivery">
          <button class="btn btn-primary" type="submit"
            onclick="return confirm('Confirm you have received the item? This will release escrow funds to the seller.')">
            Confirm Delivery
          </button>
        </form>
        
        <?php endif; ?>
        <?php if ($order['status'] === 'pending'): ?>
            <form method="POST">
            <input type="hidden" name="action" value="pay">
            <button class="btn btn-primary" type="submit">
            Pay Now
            </button>
            </form>
        <?php endif; ?>

        <?php if (in_array($order['status'], ['pending','paid'])): ?>
        <form method="POST">
          <input type="hidden" name="action" value="cancel">
          <button class="btn" style="background:#fce8e8;color:#8b1a14;border:1px solid #f5b7b7;" type="submit"
            onclick="return confirm('Cancel this order?')">
            ✕ Cancel Order
          </button>
        </form>
        <?php endif; ?>

       <!-- <a href="../../html/messages.php?seller=<?= $order['sellerID'] ?>" class="btn btn-secondary"> Message Seller</a> -->
        <a href="orders.php" class="btn btn-secondary">← Back to Orders</a>
      </div>
    </div>

  </div>

  <!-- RIGHT: SUMMARY CARD -->
  <div>
    <div class="detail-card">
      <div class="detail-title" style="font-size:16px;">Order Summary</div>
      <div class="info-row"><span style="color:var(--text-muted);">Item</span><span>R <?= number_format($order['totalPrice'],2) ?></span></div>
      <div class="info-row"><span style="color:var(--text-muted);">Platform fee</span><span style="color:#1a5c35;">Free</span></div>
      <div class="info-row" style="font-weight:700;font-size:15px;"><span>Total</span><span>R <?= number_format($order['totalPrice'],2) ?></span></div>

      <div style="margin-top:16px;background:#e6faf0;border:1px solid #b2dbd7;border-radius:8px;padding:12px;font-size:13px;color:#1a5c35;">
        Funds are being held in secure escrow until you confirm the delivery.
      </div>
    </div>

    <div class="detail-card">
      <div class="detail-title" style="font-size:16px;">Seller</div>
      <div style="display:flex;align-items:center;gap:12px;">
        <div style="width:44px;height:44px;border-radius:50%;background:var(--primary);color:white;display:flex;align-items:center;justify-content:center;font-size:16px;font-weight:700;flex-shrink:0;">
          <?= strtoupper(substr($order['sellerFirst'],0,1).substr($order['sellerLast'],0,1)) ?>
        </div>
        <div>
          <div style="font-weight:600;">@<?= htmlspecialchars($order['sellerName']) ?></div>
          <a href="../../html/seller-profile.php?id=<?= $order['sellerID'] ?>" style="font-size:12px;color:var(--primary);text-decoration:none;font-weight:600;">View Profile →</a>
        </div>
      </div>
    </div>
  </div>
</div>

<script src="../../javascript/script.js"></script>
</body>
</html>

