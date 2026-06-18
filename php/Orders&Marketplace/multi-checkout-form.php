<?php
session_start();
if (!isset($_SESSION['userID']) || !isset($_SESSION['multi_checkout_items'])) {
    header('Location: ../../html/cart.php');
    exit;
}

$items   = $_SESSION['multi_checkout_items'];
$notes   = $_SESSION['multi_checkout_notes'] ?? [];
$buyerID = (int)$_SESSION['userID'];
$initials = strtoupper(substr($_SESSION['firstName'] ?? '', 0, 1) . substr($_SESSION['lastName'] ?? '', 0, 1));

// Calculate totals — quantity is stored as cartQuantity by prepareCheckout.php
$subtotal   = 0;
$totalItems = 0;
foreach ($items as $item) {
    $qty        = (int)($item['cartQuantity'] ?? 1);
    $subtotal   += $item['price'] * $qty;
    $totalItems += $qty;
}

// Handle form submission
$error    = '';
$success  = false;
$orderIDs = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once '../BackendLogic/dbConn.php';

    $deliveryMethod  = htmlspecialchars(trim($_POST['deliveryMethod']  ?? ''));
    $deliveryAddress = htmlspecialchars(trim($_POST['deliveryAddress'] ?? ''));

    if (!$deliveryMethod) {
        $error = "Please select a delivery method.";
    } elseif (!$deliveryAddress) {
        $error = "Please enter a delivery address.";
    } else {
        foreach ($items as $item) {
            $listingID = (int)$item['listingID'];
            $sellerID  = (int)$item['sellerUID'];
            $quantity  = (int)($item['cartQuantity'] ?? 1);   // ← uses actual qty
            $unitPrice = (float)$item['price'];
            $total     = $unitPrice * $quantity;

            $ins = $conn->prepare("
                INSERT INTO tblOrders
                  (buyerID, listingID, sellerID, quantity, totalPrice, deliveryMethod, deliveryAddress, status, createdAt)
                VALUES (?, ?, ?, ?, ?, ?, ?, 'paid', NOW())
            ");
            $ins->bind_param("iiidiss", $buyerID, $listingID, $sellerID, $quantity, $total, $deliveryMethod, $deliveryAddress);

            if ($ins->execute()) {
                $orderIDs[] = $conn->insert_id;
                $dc = $conn->prepare("DELETE FROM tblCart WHERE userID=? AND listingID=?");
                $dc->bind_param('ii', $buyerID, $listingID);
                $dc->execute();
                $dc->close();
            } else {
                $error = "Error processing order for: " . htmlspecialchars($item['title']);
            }
            $ins->close();
        }

        if (empty($error) && !empty($orderIDs)) {
            $success = true;
            unset($_SESSION['multi_checkout_items'], $_SESSION['multi_checkout_notes']);
        }
    }
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout – Past Times</title>
    <link rel="stylesheet" href="../../css/styles.css">
    <link rel="stylesheet" href="../../css/responsive.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        body { background: var(--cream); font-family: var(--font-body); }
        .checkout-wrap { max-width: 900px; margin: 40px auto; padding: 0 24px; display: grid; grid-template-columns: 1fr 320px; gap: 24px; align-items: start; }
        @media(max-width: 700px) { .checkout-wrap { grid-template-columns: 1fr; } }
        .checkout-card { background: white; border-radius: var(--radius-lg); border: 1px solid var(--border-light); padding: 28px; box-shadow: var(--shadow-sm); margin-bottom: 20px; }
        .checkout-title { font-family: var(--font-display); font-size: 22px; font-weight: 700; margin-bottom: 20px; }
        .order-summary-item { display: flex; gap: 14px; align-items: center; padding: 12px 0; border-bottom: 1px solid var(--border-light); }
        .order-summary-item:last-child { border-bottom: none; }
        .order-thumb { width: 52px; height: 52px; border-radius: 8px; background: var(--bg-warm); display: flex; align-items: center; justify-content: center; font-size: 22px; overflow: hidden; flex-shrink: 0; }
        .order-thumb img { width: 100%; height: 100%; object-fit: cover; }
        .delivery-options { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin: 10px 0 16px; }
        @media(max-width:500px) { .delivery-options { grid-template-columns: 1fr; } }
        .delivery-opt { border: 2px solid var(--border); border-radius: var(--radius); padding: 14px; cursor: pointer; transition: .15s; }
        .delivery-opt:hover { border-color: var(--primary); }
        .delivery-opt input[type=radio] { accent-color: var(--primary); margin-right: 8px; }
        .delivery-opt.selected { border-color: var(--primary); background: #fff8f7; }
        .price-row { display: flex; justify-content: space-between; font-size: 14px; padding: 8px 0; border-bottom: 1px solid var(--border-light); }
        .price-row:last-child { border-bottom: none; font-weight: 700; font-size: 16px; }
        .escrow-badge { background: #e6faf0; border: 1px solid #b2dbd7; color: #1a5c35; border-radius: 8px; padding: 10px 14px; font-size: 13px; margin-bottom: 16px; }
        .alert-error { background: #fce8e8; border: 1px solid #f5b7b7; color: #8b1a14; border-radius: 8px; padding: 12px 16px; margin-bottom: 16px; font-size: 14px; }
        .success-wrap { max-width: 500px; margin: 80px auto; text-align: center; padding: 0 24px; }
        .success-title { font-family: var(--font-display); font-size: 28px; font-weight: 700; margin-bottom: 8px; }
        .success-sub { color: var(--text-muted); font-size: 15px; margin-bottom: 28px; line-height: 1.6; }
        .order-numbers { background: #e6faf0; border: 1px solid #b2dbd7; border-radius: 8px; padding: 14px 18px; margin-bottom: 24px; font-size: 14px; color: #1a5c35; }
        .qty-badge { display: inline-block; background: var(--bg-warm); border: 1px solid var(--border-light); padding: 2px 10px; border-radius: 10px; font-size: 12px; font-weight: 700; color: var(--dark); margin-left: 6px; }
        .item-note-display { font-size: 11px; color: var(--text-muted); margin-top: 2px; font-style: italic; }
    </style>
</head>
<body>

<nav class="navbar">
    <div class="navbar-brand" onclick="location.href='../../html/home.php'">
        <div class="logo-icon">P</div>
        <span style="font-family:var(--font-display);font-size:16px;font-weight:700;">Past Times</span>
    </div>
    <div class="navbar-nav">
        <a class="nav-link" href="../../html/home.php">Explore</a>
        <a class="nav-link" href="../../html/favorites.php">Favourites</a>
    </div>
    <div class="navbar-actions">
        <div class="avatar-btn" onclick="location.href='../../html/dashboard.php'"><?= $initials ?></div>
    </div>
</nav>

<?php if ($success): ?>
<!-- SUCCESS -->
<div class="success-wrap">
    <div style="font-size:64px;margin-bottom:16px;">🎉</div>
    <div class="success-title">Orders Placed!</div>
    <div class="success-sub">
        Your <?= count($orderIDs) ?> order<?= count($orderIDs) > 1 ? 's' : '' ?> have been placed successfully.
        Payments are held securely in escrow until you confirm delivery.
    </div>
    <div class="order-numbers">
        🛡 Order #<?= implode(', #', $orderIDs) ?><br>
        Sellers have been notified and will ship within 48 hours.
    </div>
    <div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap;">
        <a href="orders.php" class="btn btn-primary">View My Orders</a>
        <a href="../../html/home.php" class="btn btn-secondary">Keep Shopping</a>
    </div>
</div>

<?php else: ?>
<!-- BREADCRUMB -->
<div style="max-width:900px;margin:24px auto;padding:0 24px;font-size:14px;color:var(--text-muted);">
    <a href="../../html/home.php" style="color:var(--text-muted);text-decoration:none;">Home</a> ›
    <a href="../../html/cart.php" style="color:var(--text-muted);text-decoration:none;">Cart</a> ›
    <span style="color:var(--dark);font-weight:600;">Checkout</span>
</div>

<div class="checkout-wrap">

    <!-- LEFT: FORM -->
    <div>
        <div class="checkout-card">
            <div class="checkout-title">Complete Your Order</div>

            <?php if ($error): ?>
                <div class="alert-error"><?= $error ?></div>
            <?php endif; ?>

            <form method="POST">

                <!-- Items -->
                <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:var(--text-muted);margin-bottom:8px;">
                    ITEMS (<?= $totalItems ?> total)
                </div>

                <?php foreach ($items as $item):
                    $qty       = (int)($item['cartQuantity'] ?? 1);
                    $itemTotal = $item['price'] * $qty;
                    $note      = $notes[$item['listingID']] ?? $item['cartNote'] ?? '';
                ?>
                    <div class="order-summary-item">
                        <div class="order-thumb">
                            <?php if (!empty($item['imagePath'])): ?>
                                <img src="../../<?= htmlspecialchars($item['imagePath']) ?>" alt="">
                            <?php else: ?>📦<?php endif; ?>
                        </div>
                        <div style="flex:1;min-width:0;">
                            <div style="font-weight:600;font-size:14px;display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
                                <?= htmlspecialchars($item['title']) ?>
                                <span class="qty-badge">×<?= $qty ?></span>
                            </div>
                            <div style="font-size:12px;color:var(--text-muted);margin-top:2px;">
                                <?= htmlspecialchars($item['category']) ?> · @<?= htmlspecialchars($item['username']) ?>
                            </div>
                            <?php if (!empty($note)): ?>
                                <div class="item-note-display">📝 <?= htmlspecialchars($note) ?></div>
                            <?php endif; ?>
                        </div>
                        <div style="text-align:right;flex-shrink:0;">
                            <div style="font-weight:700;color:var(--primary);font-size:15px;">R <?= number_format($itemTotal, 2) ?></div>
                            <div style="font-size:11px;color:var(--text-muted);">R <?= number_format($item['price'], 2) ?> × <?= $qty ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>

                <!-- Delivery Method -->
                <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:var(--text-muted);margin:20px 0 8px;">DELIVERY METHOD</div>
                <div class="delivery-options">
                    <?php
                    $icons = ['Paxi' => '📦', 'PUDO' => '🔒', 'Aramex' => '⚡', 'Collection' => '🤝'];
                    $desc  = ['Paxi' => 'Pep to Pep · 5-7 days', 'PUDO' => 'Locker-to-Locker · 3-5 days', 'Aramex' => 'Express · 2-3 days', 'Collection' => 'Meet in person'];
                    foreach (['Paxi', 'PUDO', 'Aramex', 'Collection'] as $opt):
                    ?>
                    <label class="delivery-opt" onclick="this.classList.add('selected');document.querySelectorAll('.delivery-opt').forEach(o=>o!==this&&o.classList.remove('selected'))">
                        <input type="radio" name="deliveryMethod" value="<?= $opt ?>" required>
                        <?= $icons[$opt] ?> <strong><?= $opt ?></strong>
                        <div style="font-size:12px;color:var(--text-muted);margin-top:4px;"><?= $desc[$opt] ?></div>
                    </label>
                    <?php endforeach; ?>
                </div>

                <!-- Delivery Address -->
                <div class="form-group">
                    <label class="form-label">DELIVERY ADDRESS / PUDO LOCKER</label>
                    <textarea class="form-input" name="deliveryAddress" rows="3"
                        placeholder="e.g. PUDO Locker — The Paddocks, Milnerton&#10;or: 12 Loop Street, Cape Town CBD, 8001"
                        required></textarea>
                </div>

                <div class="escrow-badge">
                    🛡 Your payment is held in <strong>secure escrow</strong> and only released to sellers once you confirm receipt.
                </div>

                <button class="btn btn-primary btn-lg" type="submit" style="width:100%;">
                    Place All Orders — R <?= number_format($subtotal, 2) ?>
                </button>
                <div style="font-size:12px;color:var(--text-muted);text-align:center;margin-top:8px;">
                    By placing this order you agree to our Terms of Service.
                </div>
            </form>
        </div>
    </div>

    <!-- RIGHT: SUMMARY -->
    <div>
        <div class="checkout-card">
            <div class="checkout-title" style="font-size:18px;">Order Summary</div>

            <?php foreach ($items as $item):
                $qty       = (int)($item['cartQuantity'] ?? 1);
                $itemTotal = $item['price'] * $qty;
            ?>
                <div class="price-row">
                    <span style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:160px;">
                        <?= htmlspecialchars($item['title']) ?>
                        <span style="font-size:11px;color:var(--text-muted);">×<?= $qty ?></span>
                    </span>
                    <span>R <?= number_format($itemTotal, 2) ?></span>
                </div>
            <?php endforeach; ?>

            <div class="price-row"><span>Platform fee</span><span style="color:#1a5c35;">Free 🌿</span></div>
            <div class="price-row"><span>Delivery</span><span>At delivery</span></div>
            <div class="price-row" style="margin-top:4px;">
                <span><strong>Total</strong></span>
                <span><strong>R <?= number_format($subtotal, 2) ?></strong></span>
            </div>

            <div style="margin-top:16px;background:var(--bg-warm);border-radius:var(--radius);padding:14px;font-size:12px;color:var(--text-muted);text-align:center;line-height:1.8;">
                🔒 SSL Secured<br>
                🛡 Ozow Escrow Protected<br>
                📦 PUDO / Paxi / Aramex<br>
                🌿 Zero seller fees
            </div>
        </div>
        <div style="margin-top:12px;">
            <a href="../../html/cart.php" class="btn btn-secondary" style="width:100%;text-align:center;">← Back to Cart</a>
        </div>
    </div>

</div>
<?php endif; ?>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const firstRadio = document.querySelector('.delivery-opt input[type="radio"]');
        if (firstRadio) {
            firstRadio.checked = true;
            firstRadio.closest('.delivery-opt').classList.add('selected');
        }
    });
</script>
</body>
</html>