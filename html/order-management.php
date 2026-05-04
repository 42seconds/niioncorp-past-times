<?php

session_start();
if (!isset($_SESSION['userID'])) {
    header('Location: ../php/AuthSystem/login.php'); exit;
}
require_once '../php/BackendLogic/dbConn.php';

$sellerID = (int)$_SESSION['userID'];
$initials = strtoupper(substr($_SESSION['firstName'],0,1).substr($_SESSION['lastName'],0,1));

// ── Handle POST actions ───────────────────────────────────────────────────────
$msg = isset($_GET['msg']) ? 'success:'.urldecode($_GET['msg']) : '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['orderID'])) {
    $orderID = (int)$_POST['orderID'];
    $action  = $_POST['action'];

    if ($action === 'ship') {
        $tracking = htmlspecialchars(trim($_POST['tracking'] ?? ''));
        if (!$tracking) {
            $msg = 'error:Enter a tracking number before marking as shipped.';
        } else {
            $conn->query("UPDATE tblOrders SET status='shipped',
                deliveryAddress = CONCAT(deliveryAddress, CHAR(10), 'Tracking: $tracking')
                WHERE orderID=$orderID AND sellerID=$sellerID AND status='paid'");
            $conn->close();
            header('Location: order-management.php?status=shipped&msg=Order+%23'.$orderID.'+marked+as+shipped.');
            exit;
        }
    } elseif ($action === 'cancel') {
        $conn->query("UPDATE tblOrders SET status='cancelled' WHERE orderID=$orderID AND sellerID=$sellerID AND status IN ('pending','paid')");
        $conn->close();
        header('Location: order-management.php?status=cancelled&msg=Order+%23'.$orderID.'+cancelled.');
        exit;
    }
}

// ── Fetch all orders for this seller ─────────────────────────────────────────
$statusFilter = $_GET['status'] ?? 'all';
$validStatus  = ['all','pending','paid','shipped','delivered','cancelled'];
if (!in_array($statusFilter, $validStatus)) $statusFilter = 'all';

$where = $statusFilter !== 'all' ? "AND o.status = '$statusFilter'" : '';

$orders = $conn->query("
    SELECT o.orderID, o.status, o.totalPrice, o.deliveryMethod, o.deliveryAddress,
           o.createdAt, o.updatedAt,
           l.title, l.imagePath, l.category, l.listingID, l.price,
           u.username AS buyerName, u.firstName AS buyerFirst, u.lastName AS buyerLast,
           u.userID AS buyerUID
    FROM tblOrders o
    JOIN tblListings l ON o.listingID = l.listingID
    JOIN tblUser u     ON o.buyerID   = u.userID
    WHERE o.sellerID = $sellerID $where
    ORDER BY FIELD(o.status,'paid','pending','shipped','delivered','cancelled'), o.createdAt DESC
");

// ── Counts per status ─────────────────────────────────────────────────────────
$counts = ['all'=>0,'pending'=>0,'paid'=>0,'shipped'=>0,'delivered'=>0,'cancelled'=>0];
$cRes = $conn->query("SELECT status, COUNT(*) c FROM tblOrders WHERE sellerID=$sellerID GROUP BY status");
while ($r = $cRes->fetch_assoc()) {
    $counts[$r['status']] = (int)$r['c'];
    $counts['all'] += (int)$r['c'];
}

$conn->close();

$statusColour = [
    'pending'   => ['bg'=>'#fff3e0','c'=>'#7a4f00','b'=>'#ffe0b2'],
    'paid'      => ['bg'=>'#e8f0fe','c'=>'#1a3c8b','b'=>'#c5d5fb'],
    'shipped'   => ['bg'=>'#e6faf0','c'=>'#1a5c35','b'=>'#b2dbd7'],
    'delivered' => ['bg'=>'#e6faf0','c'=>'#1a5c35','b'=>'#b2dbd7'],
    'cancelled' => ['bg'=>'#fce8e8','c'=>'#8b1a14','b'=>'#f5b7b7'],
];

[$msgType, $msgText] = $msg ? explode(':', $msg, 2) : ['',''];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Order Management – Past Times</title>
  <link rel="stylesheet" href="../css/styles.css">
  <link rel="stylesheet" href="../css/dashboard.css">
  <link rel="stylesheet" href="../css/responsive.css">
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
  <style>
    body{background:var(--cream);font-family:var(--font-body);}
    .sb{display:inline-block;padding:3px 10px;border-radius:999px;font-size:11px;font-weight:700;letter-spacing:.04em;}
    .page-wrap{max-width:1000px;margin:36px auto;padding:0 28px;}
    h1{font-family:var(--font-display);font-size:28px;font-weight:700;margin:0 0 4px;}
    .filter-bar{display:flex;gap:8px;margin-bottom:24px;flex-wrap:wrap;}
    .filter-pill{padding:7px 16px;border-radius:999px;font-size:13px;font-weight:600;cursor:pointer;border:1.5px solid var(--border);color:var(--text-muted);text-decoration:none;}
    .filter-pill.active{background:var(--dark);color:white;border-color:var(--dark);}
    /* Order card */
    .order-card{background:white;border-radius:var(--radius-lg);border:1.5px solid var(--border-light);padding:22px 26px;margin-bottom:14px;box-shadow:var(--shadow-sm);}
    .order-card.urgent{border-left:4px solid var(--primary);}
    .order-top{display:grid;grid-template-columns:52px 1fr auto;gap:14px;align-items:start;margin-bottom:16px;}
    .order-thumb{width:52px;height:52px;border-radius:8px;background:var(--bg-warm);display:flex;align-items:center;justify-content:center;font-size:22px;overflow:hidden;}
    .order-thumb img{width:100%;height:100%;object-fit:cover;}
    .order-actions{display:flex;gap:8px;flex-wrap:wrap;padding-top:14px;border-top:1px solid var(--border-light);}
    .ship-form{display:flex;gap:8px;margin-top:12px;padding-top:12px;border-top:1px solid var(--border-light);}
    .ship-form input{flex:1;}
    .info-grid{display:grid;grid-template-columns:1fr 1fr;gap:8px;font-size:13px;margin-bottom:4px;}
    .info-item{display:flex;flex-direction:column;gap:2px;}
    .info-label{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--text-muted);}
    .info-val{font-weight:500;}
    .alert-success{background:#e6faf0;border:1px solid #b2dbd7;color:#1a5c35;border-radius:8px;padding:12px 16px;margin-bottom:16px;font-size:14px;}
    .alert-error{background:#fce8e8;border:1px solid #f5b7b7;color:#8b1a14;border-radius:8px;padding:12px 16px;margin-bottom:16px;font-size:14px;}
    .empty{text-align:center;padding:60px 20px;color:var(--text-muted);}
  </style>
</head>
<body>

<nav class="navbar">
  <div class="navbar-brand" onclick="location.href='../php/SellerArea/sellerDashboard.php'"><div class="logo-icon">P</div><span style="font-family:var(--font-display);font-size:16px;font-weight:700;">Past Times</span></div>
  <div class="navbar-nav">
    <a class="nav-link" href="../php/SellerArea/sellerDashboard.php">Dashboard</a>
    <a class="nav-link active">Order Management</a>
    <a class="nav-link" href="../php/SellerArea/create-listing.php">New Listing</a>
    <a class="nav-link" href="home.php">Shop</a>
  </div>
  <div class="navbar-actions">
    <div class="icon-btn">🔔</div>
    <div class="avatar-btn" onclick="location.href='settings.php'"><?= $initials ?></div>
    <a href="../php/AuthSystem/logout.php" style="font-size:13px;font-weight:600;color:var(--primary);text-decoration:none;margin-left:12px;">Log Out</a>
  </div>
</nav>

<div class="page-wrap">

  <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:20px;">
    <div>
      <h1>Order Management</h1>
      <div style="font-size:14px;color:var(--text-muted);">Accept orders, confirm shipment and track deliveries.</div>
    </div>
    <div style="display:flex;align-items:center;gap:6px;background:#e0f5f3;border:1px solid #b2dbd7;border-radius:var(--radius-pill);padding:8px 16px;font-size:13px;font-weight:600;color:var(--teal);">🛡 Ozow Escrow Protected</div>
  </div>

  <?php if ($msgType === 'success'): ?>
    <div class="alert-success">✔ <?= htmlspecialchars($msgText) ?></div>
  <?php elseif ($msgType === 'error'): ?>
    <div class="alert-error">⚠ <?= htmlspecialchars($msgText) ?></div>
  <?php endif; ?>

  <!-- FILTER PILLS -->
  <div class="filter-bar">
    <?php
    $labels = ['all'=>'All','paid'=>'New','pending'=>'Awaiting Payment','shipped'=>'Shipped','delivered'=>'Completed','cancelled'=>'Cancelled'];
    foreach ($labels as $s => $label): ?>
      <a href="order-management.php?status=<?= $s ?>"
         class="filter-pill <?= $statusFilter===$s?'active':'' ?>">
        <?= $label ?>
        <?php if ($counts[$s] > 0): ?>
          (<?= $counts[$s] ?>)
        <?php endif; ?>
      </a>
    <?php endforeach; ?>
  </div>

  <!-- ORDERS -->
  <?php if ($orders->num_rows > 0):
    while ($o = $orders->fetch_assoc()):
      $sc = $statusColour[$o['status']] ?? $statusColour['pending'];
      $buyerInitials = strtoupper(substr($o['buyerFirst'],0,1).substr($o['buyerLast'],0,1));
      $isUrgent = in_array($o['status'], ['paid','shipped']);
  ?>
  <div class="order-card <?= $isUrgent ? 'urgent' : '' ?>">

    <div class="order-top">
      <div class="order-thumb">
        <?php if (!empty($o['imagePath'])): ?>
          <img src="../<?= htmlspecialchars($o['imagePath']) ?>" alt="">
       <?php endif; ?>
      </div>
      <div>
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:4px;">
          <span style="font-size:12px;font-weight:700;color:var(--text-muted);">ORDER #<?= $o['orderID'] ?></span>
          <span class="sb" style="background:<?= $sc['bg'] ?>;color:<?= $sc['c'] ?>;border:1px solid <?= $sc['b'] ?>;"><?= strtoupper($o['status']) ?></span>
          <?php if ($o['status'] === 'paid'): ?>
            <span style="font-size:11px;font-weight:700;color:var(--primary);">ACTION REQUIRED</span>
          <?php endif; ?>
        </div>
        <div style="font-weight:600;font-size:16px;margin-bottom:6px;"><?= htmlspecialchars($o['title']) ?></div>
        <div class="info-grid">
          <div class="info-item"><span class="info-label">Buyer</span><span class="info-val">@<?= htmlspecialchars($o['buyerName']) ?></span></div>
          <div class="info-item"><span class="info-label">Delivery</span><span class="info-val"><?= htmlspecialchars($o['deliveryMethod']) ?></span></div>
          <div class="info-item"><span class="info-label">Order Date</span><span class="info-val"><?= date('d M Y · H:i', strtotime($o['createdAt'])) ?></span></div>
          <div class="info-item"><span class="info-label">Last Update</span><span class="info-val"><?= date('d M Y · H:i', strtotime($o['updatedAt'])) ?></span></div>
          <div class="info-item" style="grid-column:1/-1;"><span class="info-label">Delivery Address</span><span class="info-val"><?= nl2br(htmlspecialchars($o['deliveryAddress'])) ?></span></div>
        </div>
      </div>
      <div style="text-align:right;flex-shrink:0;">
        <div style="font-family:var(--font-display);font-size:22px;font-weight:700;color:var(--primary);">R <?= number_format($o['totalPrice'],2) ?></div>
        <?php if ($o['status'] === 'delivered'): ?>
          <div style="font-size:11px;color:#1a5c35;font-weight:600;margin-top:4px;">Earnings Released</div>
        <?php elseif ($o['status'] === 'shipped'): ?>
          <div style="font-size:11px;color:#1a3c8b;font-weight:600;margin-top:4px;">In Escrow</div>
        <?php endif; ?>
      </div>
    </div>

    <!-- ACTION BUTTONS -->
    <div class="order-actions">
      <?php if ($o['status'] === 'paid'): ?>
        <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
          <span style="font-size:13px;font-weight:700;color:#1a3c8b;">New order — enter tracking below to accept and ship.</span>
          <form method="POST" style="display:inline;">
            <input type="hidden" name="orderID" value="<?= $o['orderID'] ?>">
            <button class="btn btn-sm" name="action" value="cancel"
              style="background:#fce8e8;color:#8b1a14;border:1px solid #f5b7b7;"
              onclick="return confirm('Decline order #<?= $o['orderID'] ?>?')">✕ Decline</button>
          </form>
          <!--- <a href="messages.php?buyer=<?= $o['buyerUID'] ?? 0 ?>" class="btn btn-secondary btn-sm">💬 Message Buyer</a> -->
        </div>

      <?php elseif ($o['status'] === 'pending'): ?>
        <span style="font-size:13px;color:var(--text-muted);">Awaiting buyer payment.</span>
        <form method="POST">
          <input type="hidden" name="orderID" value="<?= $o['orderID'] ?>">
          <button class="btn btn-sm" name="action" value="cancel"
            style="background:#fce8e8;color:#8b1a14;border:1px solid #f5b7b7;">✕ Cancel</button>
        </form>

      <?php elseif ($o['status'] === 'shipped'): ?>
        <div style="font-size:13px;color:#1a5c35;font-weight:600;"> Item shipped — awaiting buyer confirmation to release escrow.</div>

      <?php elseif ($o['status'] === 'delivered'): ?>
        <div style="font-size:13px;color:#1a5c35;font-weight:600;"> Order complete — R <?= number_format($o['totalPrice'],2) ?> released to your wallet.</div>

      <?php elseif ($o['status'] === 'cancelled'): ?>
        <div style="font-size:13px;color:#8b1a14;">✕ This order was cancelled.</div>
      <?php endif; ?>
    </div>

    <!-- SHIP FORM — show on paid orders after acceptance (both paid states) -->
    <?php if ($o['status'] === 'paid'): ?>
    <form method="POST" class="ship-form">
      <input type="hidden" name="orderID" value="<?= $o['orderID'] ?>">
      <input class="form-input" name="tracking" placeholder="Waybill / Tracking number (PUDO, Aramex, Paxi...)"
        style="padding:8px 12px;font-size:13px;" required>
      <button class="btn btn-secondary btn-sm" name="action" value="ship"> Mark as Shipped</button>
    </form>
    <?php endif; ?>

  </div>
  <?php endwhile; else: ?>
  <div class="empty">
    <div style="font-size:48px;margin-bottom:16px;">📭</div>
    <div style="font-weight:600;font-size:18px;margin-bottom:8px;">No <?= $statusFilter !== 'all' ? $statusFilter : '' ?> orders</div>
    <div style="font-size:14px;margin-bottom:20px;">
      <?php if ($counts['all'] === 0): ?>
        You haven't received any orders yet. Make sure your listings are live and approved.
        <br><a href="../php/SellerArea/create-listing.php" style="color:var(--primary);font-weight:600;">Create a listing →</a>
      <?php else: ?>
        Switch the filter above to view other orders.
      <?php endif; ?>
    </div>
  </div>
  <?php endif; ?>

</div><!-- end page-wrap -->
<script src="../javascript/script.js"></script>
<script>
function toggleSidebar() {
  var s = document.querySelector(".sidebar, .settings-side");
  if (s) s.classList.toggle("open");
  var o = document.getElementById("sidebarOverlay");
  if (o) o.classList.toggle("active");
}
function closeSidebar() {
  var s = document.querySelector(".sidebar, .settings-side");
  if (s) s.classList.remove("open");
  var o = document.getElementById("sidebarOverlay");
  if (o) o && o.classList.remove("active");
}
</script>
</body>
</html>