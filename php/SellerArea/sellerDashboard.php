<?php
session_start();
if (!isset($_SESSION['userID']) || $_SESSION['role'] !== 'seller') {
    header('Location: ../AuthSystem/login.php'); exit;
}
require_once '../BackendLogic/dbConn.php';

$sellerID = (int)$_SESSION['userID'];
$initials = strtoupper(substr($_SESSION['firstName'],0,1).substr($_SESSION['lastName'],0,1));

// ── Handle order status updates ───────────────────────────────────────────────
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['orderID'])) {
    $orderID = (int)$_POST['orderID'];
    $action  = $_POST['action'];
 
    $allowed = ['ship'=>'shipped', 'cancel'=>'cancelled'];
    if (isset($allowed[$action])) {
        $newStatus = $allowed[$action];
 
        // For ship: require tracking number
        if ($action === 'ship') {
            $tracking = htmlspecialchars(trim($_POST['tracking'] ?? ''));
            if (!$tracking) {
                $msg = 'error:Please enter a tracking number before marking as shipped.';
            } else {
                // Store tracking in deliveryAddress field (appended)
                $conn->query("UPDATE tblOrders SET status='shipped',
                    deliveryAddress = CONCAT(deliveryAddress, '\nTracking: $tracking')
                    WHERE orderID=$orderID AND sellerID=$sellerID");
                $conn->close();
                header('Location: sellerDashboard.php?tab=shipped&msg=shipped:'.$orderID);
                exit;
            }
        } else {
            $conn->query("UPDATE tblOrders SET status='$newStatus' WHERE orderID=$orderID AND sellerID=$sellerID");
            $conn->close();
            header('Location: sellerDashboard.php?tab='.$newStatus.'&msg='.$newStatus.':'.$orderID);
            exit;
        }
    }
}
// ── Listing stats ─────────────────────────────────────────────────────────────
$listingStats = ['total'=>0,'pending'=>0,'approved'=>0,'rejected'=>0];
$res = $conn->query("SELECT status, COUNT(*) c FROM tblListings WHERE sellerID=$sellerID GROUP BY status");
while ($r = $res->fetch_assoc()) {
    $listingStats[$r['status']] = (int)$r['c'];
    $listingStats['total'] += (int)$r['c'];
}
 

// ── Order stats ───────────────────────────────────────────────────────────────
$orderStats = ['total'=>0,'pending'=>0,'paid'=>0,'shipped'=>0,'delivered'=>0];
$res2 = $conn->query("SELECT status, COUNT(*) c FROM tblOrders WHERE sellerID=$sellerID GROUP BY status");
while ($r = $res2->fetch_assoc()) {
    $orderStats[$r['status']] = (int)$r['c'];
    $orderStats['total'] += (int)$r['c'];
}

// ── Earnings (delivered orders only — escrow released) ───────────────────────
$earnRow = $conn->query("SELECT COALESCE(SUM(totalPrice),0) e FROM tblOrders WHERE sellerID=$sellerID AND status='delivered'")->fetch_assoc();
$earnings = (float)$earnRow['e'];

// ── Incoming orders (newest first, grouped by status tab) ────────────────────
$orderFilter = $_GET['tab'] ?? 'paid'; // default to orders needing action
$validTabs   = ['paid','pending','shipped','delivered','cancelled'];
if (!in_array($orderFilter, $validTabs)) $orderFilter = 'paid';

$ordersStmt = $conn->prepare("
    SELECT o.orderID, o.status, o.totalPrice, o.deliveryMethod, o.deliveryAddress, o.createdAt,
           l.title, l.imagePath, l.category, l.listingID,
           u.username AS buyerName, u.firstName AS buyerFirst, u.lastName AS buyerLast
    FROM tblOrders o
    JOIN tblListings l ON o.listingID = l.listingID
    JOIN tblUser u     ON o.buyerID   = u.userID
    WHERE o.sellerID = ? AND o.status = ?
    ORDER BY o.createdAt DESC
");
$ordersStmt->bind_param("is", $sellerID, $orderFilter);
$ordersStmt->execute();
$orders = $ordersStmt->get_result();
$ordersStmt->close();

// ── Recent listings (last 4) ──────────────────────────────────────────────────
$listStmt = $conn->prepare("SELECT listingID, title, category, price, status, imagePath FROM tblListings WHERE sellerID=? ORDER BY createdAt DESC LIMIT 4");
$listStmt->bind_param("i", $sellerID);
$listStmt->execute();
$recentListings = $listStmt->get_result();
$listStmt->close();
 
$conn->close();
 
$statusColour = [
    'pending'   => ['bg'=>'#fff3e0','c'=>'#7a4f00','b'=>'#ffe0b2'],
    'paid'      => ['bg'=>'#e8f0fe','c'=>'#1a3c8b','b'=>'#c5d5fb'],
    'shipped'   => ['bg'=>'#e6faf0','c'=>'#1a5c35','b'=>'#b2dbd7'],
    'delivered' => ['bg'=>'#e6faf0','c'=>'#1a5c35','b'=>'#b2dbd7'],
    'cancelled' => ['bg'=>'#fce8e8','c'=>'#8b1a14','b'=>'#f5b7b7'],
];

$redirectMsg = '';
if (isset($_GET['msg'])) {
    $parts = explode(':', $_GET['msg'], 2);
    $type  = $parts[0] === 'shipped' ? 'success' : 'success';
    $redirectMsg = $type . ':Order #' . ($parts[1] ?? '') . ' marked as ' . $parts[0] . '.';
}
[$msgType, $msgText] = $msg ? explode(':', $msg, 2) : ($redirectMsg ? explode(':', $redirectMsg, 2) : ['','']);

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Seller Dashboard – Past Times</title>
  <link rel="stylesheet" href="../../css/styles.css">
  <link rel="stylesheet" href="../../css/dashboard.css">
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
  <style>
    .sb{display:inline-block;padding:3px 10px;border-radius:999px;font-size:11px;font-weight:700;letter-spacing:.04em;}
    .sec{background:white;border-radius:var(--radius-lg);border:1px solid var(--border-light);padding:24px;box-shadow:var(--shadow-sm);margin-bottom:20px;}
    .sec-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;}
    .sec-title{font-family:var(--font-display);font-size:18px;font-weight:700;margin:0;}
    /* Order card */
    .order-card{border:1.5px solid var(--border-light);border-radius:var(--radius-lg);padding:20px;margin-bottom:14px;transition:.15s;}
    .order-card:hover{border-color:var(--primary);}
    .order-card-header{display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:14px;}
    .order-item-row{display:flex;gap:12px;align-items:center;margin-bottom:14px;}
    .order-thumb{width:52px;height:52px;border-radius:8px;background:var(--bg-warm);display:flex;align-items:center;justify-content:center;font-size:22px;overflow:hidden;flex-shrink:0;}
    .order-thumb img{width:100%;height:100%;object-fit:cover;}
    .action-bar{display:flex;gap:10px;flex-wrap:wrap;align-items:center;padding-top:14px;border-top:1px solid var(--border-light);}
    .tracking-form{display:flex;gap:8px;flex:1;min-width:220px;}
    .tracking-form input{flex:1;}
    /* Stat card */
    .stat-card{background:white;border-radius:var(--radius-lg);border:1px solid var(--border-light);padding:20px;display:flex;justify-content:space-between;align-items:center;box-shadow:var(--shadow-sm);}
    .stat-label{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--text-muted);margin-bottom:4px;}
    .stat-value{font-family:var(--font-display);font-size:30px;font-weight:700;}
    .stat-icon{width:40px;height:40px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:18px;}
    /* Tab pills */
    .tab-pill{padding:7px 16px;border-radius:999px;font-size:13px;font-weight:600;cursor:pointer;border:1.5px solid var(--border);color:var(--text-muted);text-decoration:none;}
    .tab-pill.active{background:var(--dark);color:white;border-color:var(--dark);}
    /* Alert */
    .alert-success{background:#e6faf0;border:1px solid #b2dbd7;color:#1a5c35;border-radius:8px;padding:12px 16px;margin-bottom:16px;font-size:14px;}
    .alert-error{background:#fce8e8;border:1px solid #f5b7b7;color:#8b1a14;border-radius:8px;padding:12px 16px;margin-bottom:16px;font-size:14px;}
    /* Listing mini card */
    .listing-mini{display:flex;align-items:center;gap:12px;padding:10px 0;border-bottom:1px solid var(--border-light);}
    .listing-mini:last-child{border-bottom:none;}
    .listing-mini-thumb{width:40px;height:40px;border-radius:6px;background:var(--bg-warm);display:flex;align-items:center;justify-content:center;font-size:18px;overflow:hidden;flex-shrink:0;}
    .listing-mini-thumb img{width:100%;height:100%;object-fit:cover;}
    /* Empty */
    .empty{text-align:center;padding:40px 20px;color:var(--text-muted);}
  </style>
</head>
<body>
<div class="dashboard-layout">

  <!-- SIDEBAR -->
   <div class="sidebar">
    <div class="sidebar-header">
      <div style="width:48px;height:48px;border-radius:50%;background:var(--primary);color:white;display:flex;align-items:center;justify-content:center;font-size:18px;font-weight:700;margin-bottom:10px;"><?= $initials ?></div>
      <div class="sidebar-title"><?= htmlspecialchars($_SESSION['firstName'].' '.$_SESSION['lastName']) ?></div>
      <div class="sidebar-subtitle"><?= htmlspecialchars($_SESSION['email'] ?? '') ?></div>
      <div style="margin-top:6px;"><span style="background:#e6faf0;color:#1a5c35;border:1px solid #b2dbd7;font-size:10px;font-weight:700;padding:2px 8px;border-radius:999px;text-transform:uppercase;">Seller</span></div>
    </div>
    <div class="sidebar-nav">
      <div class="sidebar-link active">🏠︎ Seller Dashboard</div>
      <div class="sidebar-link" onclick="location.href='sellerDashboard.php?tab=paid'">
         New Orders
        <?php if ($orderStats['paid'] > 0): ?>
          <span style="margin-left:auto;background:var(--primary);color:white;font-size:10px;padding:1px 7px;border-radius:999px;"><?= $orderStats['paid'] ?></span>
        <?php endif; ?>
      </div>
      <div class="sidebar-link" onclick="location.href='sellerDashboard.php?tab=shipped'"> To Ship
        <?php if ($orderStats['shipped'] > 0): ?>
          <span style="margin-left:auto;background:#7a4f00;color:white;font-size:10px;padding:1px 7px;border-radius:999px;"><?= $orderStats['shipped'] ?></span>
        <?php endif; ?>
      </div>

      <div class="sidebar-link" onclick="location.href='create-listing.php'"> New Listing</div>
      <!-- <div class="sidebar-link" onclick="location.href='../../html/messages.php'"> Messages</div> -->
      <div class="sidebar-link" onclick="location.href='../../html/settings.php'"> Settings</div>
    </div>
    <div class="sidebar-footer">
      <a href="../../html/home.php">🏠︎ Back to Shop</a>
      <a href="../AuthSystem/logout.php">⚠ Log Out</a>
    </div>
  </div>

  <!-- MAIN -->
  <div class="dashboard-main">

    <div style="margin-bottom:24px;">
      <h1 style="font-family:var(--font-display);font-size:26px;font-weight:700;margin:0 0 4px;">Seller Dashboard</h1>
      <div style="font-size:14px;color:var(--text-muted);">Manage your orders, listings and earnings.</div>
    </div>
 
    <?php if ($msgType === 'success'): ?>
      <div class="alert-success">✔ <?= htmlspecialchars($msgText) ?></div>
    <?php elseif ($msgType === 'error'): ?>
      <div class="alert-error">⚠ <?= htmlspecialchars($msgText) ?></div>
    <?php endif; ?>

    <!-- STAT CARDS -->
     <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:24px;">
      <div class="stat-card">
            <div><div class="stat-label">Earnings</div><div class="stat-value" style="font-size:22px;">R <?= number_format($earnings,2) ?></div></div>
       </div>
      
       <div class="stat-card">
            <div><div class="stat-label">New Orders</div><div class="stat-value"><?= $orderStats['paid'] ?></div></div>
        </div>
 
      <div class="stat-card">
            <div><div class="stat-label">Shipped</div><div class="stat-value"><?= $orderStats['shipped'] ?></div></div>
      </div>
 
      <div class="stat-card">
            <div><div class="stat-label">Completed</div><div class="stat-value"><?= $orderStats['delivered'] ?></div></div>
        </div>
    </div>

    <!-- ORDER MANAGEMENT -->
    <div class="sec">
      <div class="sec-header">
        <div class="sec-title">Incoming Orders</div>
        <a href="create-listing.php" class="btn btn-primary btn-sm">+ New Listing</a>
      </div>

      <!-- STATUS TABS -->
      <div style="display:flex;gap:8px;margin-bottom:20px;flex-wrap:wrap;">
           <?php
        $tabLabels = ['paid'=>'New ('.$orderStats['paid'].')','pending'=>'Pending','shipped'=>'Shipped ('.$orderStats['shipped'].')','delivered'=>'Completed','cancelled'=>'Cancelled'];
        foreach ($tabLabels as $tab => $label): ?>
          <a href="sellerDashboard.php?tab=<?= $tab ?>"
             class="tab-pill <?= $orderFilter===$tab?'active':'' ?>"><?= $label ?></a>
        <?php endforeach; ?>
      </div>
 
      <?php if ($orders->num_rows > 0):
        while ($o = $orders->fetch_assoc()):
          $sc = $statusColour[$o['status']] ?? $statusColour['pending'];
          $buyerInitials = strtoupper(substr($o['buyerFirst'],0,1).substr($o['buyerLast'],0,1));
      ?>
      <div class="order-card">


        <!-- HEADER: order ID + status + buyer -->
        <div class="order-card-header">
          <div>
            <div style="font-size:11px;color:var(--text-muted);font-weight:600;text-transform:uppercase;letter-spacing:.06em;">Order #<?= $o['orderID'] ?></div>
            <div style="font-size:13px;color:var(--text-muted);margin-top:2px;">
              <?= date('d M Y · H:i', strtotime($o['createdAt'])) ?>
            </div>
          </div>
          <div style="display:flex;align-items:center;gap:10px;">
            <div style="display:flex;align-items:center;gap:6px;">
              <div style="width:30px;height:30px;border-radius:50%;background:var(--dark);color:white;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;"><?= $buyerInitials ?></div>
              <div>
                <div style="font-size:13px;font-weight:600;">@<?= htmlspecialchars($o['buyerName']) ?></div>
                <div style="font-size:11px;color:var(--text-muted);">Buyer</div>
              </div>
            </div>
            <span class="sb" style="background:<?= $sc['bg'] ?>;color:<?= $sc['c'] ?>;border:1px solid <?= $sc['b'] ?>;"><?= strtoupper($o['status']) ?></span>
          </div>
        </div>
 

        <!-- ITEM ROW -->
        <div class="order-item-row">
 
          <div class="order-thumb">
            <?php if (!empty($o['imagePath'])): ?>
              <img src="../../<?= htmlspecialchars($o['imagePath']) ?>"  alt=""><!-- ✅ IMAGE: ../../php/uploads/listings/file -->
            <?php endif; ?>
          </div>
          <div style="flex:1;">
                <div style="font-weight:600;font-size:15px;">
                    <?= htmlspecialchars($o['title']) ?>
                </div>
 
                <div style="font-size:13px;color:var(--text-muted);">
                    <?= htmlspecialchars($o['category']) ?> · <?= htmlspecialchars($o['deliveryMethod']) ?>
                </div>
            
                <div style="font-size:12px;color:var(--text-muted);margin-top:2px;">
                    ⚲ <?= nl2br(htmlspecialchars(explode("\n", $o['deliveryAddress'])[0])) ?>
                </div>
            </div>
 
          <div style="font-family:var(--font-display);font-size:20px;font-weight:700;color:var(--primary);">R 
                <?= number_format($o['totalPrice'],2) ?>
            </div>
 
        </div>

        <!-- ACTION BAR based on current status -->
        <div class="action-bar">
          <?php if ($o['status'] === 'paid'): ?>
            <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
              <span style="font-size:13px;font-weight:600;color:#1a3c8b;">⚡ New order — enter tracking and ship to accept.</span>
              <form method="POST" style="display:inline;">
                <input type="hidden" name="orderID" value="<?= $o['orderID'] ?>">
                <button class="btn btn-sm" name="action" value="cancel"
                  style="background:#fce8e8;color:#8b1a14;border:1px solid #f5b7b7;"
                  onclick="return confirm('Decline and cancel order #<?= $o['orderID'] ?>?')">✕ Decline</button>
              </form>
            <!--  <a href="../../html/messages.php" class="btn btn-secondary btn-sm">💬 Message Buyer</a> -->
            </div>
 
          <?php elseif ($o['status'] === 'pending'): ?>
            <!-- Awaiting payment — just show info -->
            <div style="font-size:13px;color:var(--text-muted);">Awaiting payment from buyer.</div>
            <a href="../../html/messages.php" class="btn btn-secondary btn-sm">Message Buyer</a>
 
          <?php elseif ($o['status'] === 'shipped'): ?>
            <!-- Already shipped — awaiting buyer confirmation -->
            <div style="font-size:13px;color:#1a5c35;font-weight:600;">Item shipped. Waiting for buyer to confirm delivery.</div>
            <div style="font-size:12px;color:var(--text-muted);">Delivery: <?= nl2br(htmlspecialchars($o['deliveryAddress'])) ?></div>
 
          <?php elseif ($o['status'] === 'delivered'): ?>
            <!-- Complete -->
            <div style="font-size:13px;color:#1a5c35;font-weight:600;"> Order complete. Earnings released to your wallet.</div>
 
          <?php elseif ($o['status'] === 'cancelled'): ?>
            <div style="font-size:13px;color:#8b1a14;">✕ Order cancelled.</div>
          <?php endif; ?>
        </div>

        <!-- SHIP FORM — shown when order is accepted (status = paid → after accept it becomes paid, use separate tab) -->
          <?php if ($o['status'] === 'paid'): ?>
        <div style="margin-top:12px;padding-top:12px;border-top:1px solid var(--border-light);">
          <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--primary);margin-bottom:8px;">✔ ACCEPT ORDER & ENTER TRACKING TO SHIP</div>
          <form method="POST" style="display:flex;gap:8px;flex-wrap:wrap;">
            <input type="hidden" name="orderID" value="<?= $o['orderID'] ?>">
            <input class="form-input" name="tracking" placeholder="Tracking Number/ Waybill number" style="flex:1;min-width:180px;padding:8px 12px;font-size:13px;" required>
            <button class="btn btn-secondary btn-sm" name="action" value="ship">✔ Mark Shipped</button>
          </form>
        </div>
        <?php endif; ?>
 
      </div>
      <?php endwhile; else: ?>
      <div class="empty">
        <div style="font-size:44px;margin-bottom:12px;">📭</div>
        <div style="font-weight:600;font-size:16px;margin-bottom:6px;">No <?= $orderFilter ?> orders</div>
        <div style="font-size:13px;">Switch tabs to view other orders, or list new items to start selling.</div>
      </div>
      <?php endif; ?>
    </div>

    <!-- MY LISTINGS (mini) -->
     <div class="sec">
      <div class="sec-header">
        <div class="sec-title">My Listings</div>
        <a href="create-listing.php" style="font-size:13px;color:var(--primary);font-weight:600;text-decoration:none;">+ Add New</a>
      </div>
      <?php
      $listingEmoji = fn($c) => match(strtolower($c)) {
        'clothing'=>'🧥','accessories'=>'👜','footwear'=>'👟',
        'outerwear'=>'🧣','jewelry'=>'⌚','vintage'=>'🎩',default=>'📦'
      };
      $statusCol = ['pending'=>['#fff3e0','#7a4f00','#ffe0b2'],'approved'=>['#e6faf0','#1a5c35','#b2dbd7'],'rejected'=>['#fce8e8','#8b1a14','#f5b7b7']];
      if ($recentListings->num_rows > 0):
        while ($l = $recentListings->fetch_assoc()):
          [$bg,$c,$b] = $statusCol[$l['status']] ?? $statusCol['pending'];
      ?>
      <div class="listing-mini">
        <div class="listing-mini-thumb">
          <?php if (!empty($l['imagePath'])): ?>
            <img src="../../<?= htmlspecialchars($l['imagePath']) ?>"  alt=""><!-- ✅ IMAGE: ../../php/uploads/listings/file -->
          <?php else: ?><?= $listingEmoji($l['category']) ?><?php endif; ?>
        </div>
        <div style="flex:1;">
          <div style="font-weight:600;font-size:14px;"><?= htmlspecialchars($l['title']) ?></div>
          <div style="font-size:12px;color:var(--text-muted);"><?= htmlspecialchars($l['category']) ?> · R <?= number_format($l['price'],2) ?></div>
        </div>
        <span class="sb" style="background:<?= $bg ?>;color:<?= $c ?>;border:1px solid <?= $b ?>;"><?= strtoupper($l['status']) ?></span>
        <?php if ($l['status'] === 'approved'): ?>
          <a href="../../html/product-detail.php?id=<?= $l['listingID'] ?>" style="font-size:12px;color:var(--primary);font-weight:600;text-decoration:none;margin-left:8px;">View →</a>
        <?php endif; ?>
      </div>
      <?php endwhile; else: ?>
      <div class="empty" style="padding:24px;">
        <div>No listings yet. <a href="create-listing.php" style="color:var(--primary);font-weight:600;">Create one →</a></div>
      </div>
      <?php endif; ?>
    </div>

  </div>
</div>
<script src="../../javascript/script.js" ></script><!-- ✓ php/SellerArea → root/javascript -->
</body>
</html>
