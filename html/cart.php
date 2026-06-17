<?php
session_start();

// Check if user is logged in, if not, redirect to login (but we'll show guest cart first)
$loggedIn = isset($_SESSION['userID']);
$userID = $loggedIn ? (int)$_SESSION['userID'] : 0;
$initials = '';

require_once '../php/BackendLogic/dbConn.php';

// MIGRATE GUEST CART TO DATABASE WHEN USER LOGS IN
if ($loggedIn && isset($_SESSION['guest_cart']) && !empty($_SESSION['guest_cart'])) {
    foreach ($_SESSION['guest_cart'] as $listingID) {
        $ins = $conn->prepare("INSERT IGNORE INTO tblCart (userID, listingID) VALUES (?, ?)");
        $ins->bind_param("ii", $userID, $listingID);
        $ins->execute();
        $ins->close();
    }
    unset($_SESSION['guest_cart']);
}

if ($loggedIn) {
    $initials = strtoupper(substr($_SESSION['firstName'] ?? '', 0, 1) . substr($_SESSION['lastName'] ?? '', 0, 1));

    // Fetch cart items with listing details
    $stmt = $conn->prepare("
        SELECT c.cartID, c.listingID, c.note, c.addedAt,
               l.title, l.category, l.condition_, l.price, l.imagePath, l.delivery, l.status,
               u.username AS sellerName, u.userID AS sellerUID
        FROM tblCart c
        JOIN tblListings l ON c.listingID = l.listingID
        JOIN tblUser u     ON l.sellerID  = u.userID
        WHERE c.userID = ?
        ORDER BY c.addedAt DESC
    ");
    $stmt->bind_param("i", $userID);
    $stmt->execute();
    $items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} else {
    // Guest cart from session
    $items = [];
    if (isset($_SESSION['guest_cart']) && !empty($_SESSION['guest_cart'])) {
        $placeholders = implode(',', array_fill(0, count($_SESSION['guest_cart']), '?'));
        $stmt = $conn->prepare("
            SELECT l.listingID, l.title, l.category, l.condition_, l.price, l.imagePath, l.delivery, l.status,
                   u.username AS sellerName, u.userID AS sellerUID
            FROM tblListings l
            JOIN tblUser u ON l.sellerID = u.userID
            WHERE l.listingID IN ($placeholders) AND l.status = 'approved'
        ");
        $stmt->bind_param(str_repeat('i', count($_SESSION['guest_cart'])), ...$_SESSION['guest_cart']);
        $stmt->execute();
        $items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    }
}

$total = array_sum(array_column($items, 'price'));
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Cart – Past Times</title>
    <link rel="stylesheet" href="../css/styles.css">
    <link rel="stylesheet" href="../css/responsive.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        body {
            background: var(--cream);
            font-family: var(--font-body);
        }

        .cart-layout {
            max-width: 1000px;
            margin: 36px auto;
            padding: 0 24px;
            display: grid;
            grid-template-columns: 1fr 320px;
            gap: 24px;
            align-items: start;
        }

        @media(max-width:720px) {
            .cart-layout {
                grid-template-columns: 1fr;
            }
        }

        .cart-card {
            background: white;
            border-radius: var(--radius-lg);
            border: 1px solid var(--border-light);
            padding: 24px;
            box-shadow: var(--shadow-sm);
        }

        .cart-title {
            font-family: var(--font-display);
            font-size: 22px;
            font-weight: 700;
            margin-bottom: 20px;
        }

        .cart-item {
            display: grid;
            grid-template-columns: 60px 1fr auto;
            gap: 14px;
            align-items: start;
            padding: 16px 0;
            border-bottom: 1px solid var(--border-light);
        }

        .cart-item:last-child {
            border-bottom: none;
        }

        .item-thumb {
            width: 60px;
            height: 60px;
            border-radius: 8px;
            object-fit: cover;
            background: var(--bg-warm);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            overflow: hidden;
        }

        .item-thumb img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .item-title {
            font-weight: 600;
            font-size: 15px;
            margin-bottom: 2px;
        }

        .item-meta {
            font-size: 12px;
            color: var(--text-muted);
        }

        .item-price {
            font-family: var(--font-display);
            font-size: 18px;
            font-weight: 700;
            color: var(--primary);
            white-space: nowrap;
        }

        .item-note {
            margin-top: 8px;
        }

        .item-note textarea {
            width: 100%;
            border: 1.5px solid var(--border);
            border-radius: 8px;
            padding: 8px 12px;
            font-size: 13px;
            font-family: var(--font-body);
            resize: none;
            outline: none;
        }

        .item-note textarea:focus {
            border-color: var(--primary);
        }

        .remove-btn {
            background: none;
            border: none;
            font-size: 16px;
            color: var(--text-muted);
            cursor: pointer;
            padding: 4px;
        }

        .remove-btn:hover {
            color: #8b1a14;
        }

        .save-note-btn {
            font-size: 12px;
            color: var(--primary);
            font-weight: 600;
            background: none;
            border: none;
            cursor: pointer;
            padding: 2px 0;
            margin-top: 4px;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            font-size: 14px;
            padding: 8px 0;
            border-bottom: 1px solid var(--border-light);
        }

        .summary-row:last-of-type {
            border-bottom: none;
            font-weight: 700;
            font-size: 16px;
        }

        .badge-unavailable {
            background: #fce8e8;
            color: #8b1a14;
            border: 1px solid #f5b7b7;
            padding: 2px 8px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 700;
            margin-left: 6px;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: var(--text-muted);
        }

        .cart-icon-wrapper {
            position: relative;
            cursor: pointer;
        }

        .cart-badge {
            position: absolute;
            top: -4px;
            right: -4px;
            background: #C0392B;
            color: white;
            border-radius: 999px;
            font-size: 9px;
            padding: 1px 5px;
            font-weight: 700;
            min-width: 16px;
            text-align: center;
        }

        .navbar-toggle {
            display: none;
            flex-direction: column;
            gap: 5px;
            cursor: pointer;
            padding: 6px;
            background: none;
            border: none;
        }

        .navbar-toggle span {
            display: block;
            width: 22px;
            height: 2px;
            background: #1A1A1A;
            border-radius: 2px;
        }

        @media (max-width: 768px) {
            .navbar-toggle {
                display: flex;
            }

            .navbar-nav {
                display: none;
                flex-direction: column;
                width: 100%;
                background: #fff;
                border-top: 1px solid #E8E2DA;
                order: 3;
            }

            .navbar-nav.open {
                display: flex;
            }

            .navbar {
                flex-wrap: wrap;
                padding: 10px 16px;
            }

            .navbar-brand {
                flex: 1;
            }
        }
    </style>
</head>

<body>

    <nav class="navbar">
        <div class="navbar-brand" onclick="location.href='home.php'">
            <div class="logo-icon">P</div>
            <span style="font-family:var(--font-display);font-size:16px;font-weight:700;">Past Times</span>
        </div>
        <button class="navbar-toggle" aria-label="Toggle menu" aria-expanded="false" onclick="this.parentElement.querySelector('.navbar-nav').classList.toggle('open'); this.setAttribute('aria-expanded', this.parentElement.querySelector('.navbar-nav').classList.contains('open'));">
            <span></span><span></span><span></span>
        </button>
        <div class="navbar-nav">
            <a class="nav-link" href="home.php">Explore</a>
            <a class="nav-link" href="favorites.php">Favourites</a>
            <a class="nav-link active" href="cart.php">Cart</a>
        </div>
        <div class="navbar-actions">
            <div class="cart-icon-wrapper" onclick="location.href='cart.php'" title="Cart">
                <div class="icon-btn">🛒</div>
                <span id="cartBadge" class="cart-badge" style="display: <?= count($items) > 0 ? 'inline-block' : 'none' ?>;"><?= count($items) ?></span>
            </div>

            <?php if ($loggedIn): ?>
                <div class="avatar-btn" onclick="location.href='dashboard.php'"><?= $initials ?></div>
            <?php else: ?>
                <a href="../php/AuthSystem/login.php" class="btn btn-secondary btn-sm" style="margin-right:8px;">Log In</a>
                <a href="../php/AuthSystem/register.php" class="btn btn-primary btn-sm">Sign Up</a>
            <?php endif; ?>
        </div>
    </nav>

    <div style="max-width:1000px;margin:24px auto;padding:0 24px;">
        <div style="font-size:14px;color:var(--text-muted);">
            <a href="home.php" style="color:var(--text-muted);">Home</a> › <span style="color:var(--dark);font-weight:600;">My Cart</span>
        </div>
    </div>

    <div class="cart-layout">
        <div class="cart-card">
            <div class="cart-title">My Cart <span style="font-size:16px;color:var(--text-muted);font-family:var(--font-body);font-weight:400;">(<?= count($items) ?> item<?= count($items) !== 1 ? 's' : '' ?>)</span></div>

            <?php if (!empty($items)): foreach ($items as $item):
                    $unavailable = $item['status'] !== 'approved';
                    $emoji = match (strtolower($item['category'] ?? '')) {
                        'clothing' => '🧥',
                        'accessories' => '👜',
                        'footwear' => '👟',
                        'outerwear' => '🧣',
                        'jewelry' => '⌚',
                        'vintage' => '🎩',
                        default => '📦'
                    };
            ?>
                    <div class="cart-item" id="cartItem<?= $item['listingID'] ?>" <?= $unavailable ? 'style="opacity:.6;"' : '' ?>>
                        <div class="item-thumb">
                            <?php if (!empty($item['imagePath'])): ?>
                                <img src="../<?= htmlspecialchars($item['imagePath']) ?>" alt="">
                                <?php else: ?><?= $emoji ?><?php endif; ?>
                        </div>
                        <div>
                            <div class="item-title">
                                <a href="product-detail.php?id=<?= $item['listingID'] ?>" style="color:inherit;text-decoration:none;">
                                    <?= htmlspecialchars($item['title']) ?>
                                </a>
                                <?php if ($unavailable): ?><span class="badge-unavailable">UNAVAILABLE</span><?php endif; ?>
                            </div>
                            <div class="item-meta"><?= htmlspecialchars($item['category']) ?> · <?= htmlspecialchars($item['condition_']) ?> · @<?= htmlspecialchars($item['sellerName']) ?></div>
                            <div class="item-note">
                                <textarea id="note<?= $item['listingID'] ?>" rows="2" placeholder="Add a note for the seller (optional)…"><?= htmlspecialchars($item['note'] ?? '') ?></textarea>
                                <button class="save-note-btn" onclick="saveNote(<?= $item['listingID'] ?>)">💾 Save note</button>
                            </div>
                        </div>
                        <div style="display:flex;flex-direction:column;align-items:flex-end;gap:8px;">
                            <div class="item-price">R <?= number_format($item['price'], 2) ?></div>
                            <button class="remove-btn" onclick="removeItem(<?= $item['listingID'] ?>)" title="Remove">🗑</button>
                            <?php if (!$unavailable): ?>
                                <a href="../php/Orders&Marketplace/checkout.php?id=<?= $item['listingID'] ?>"
                                    class="btn btn-primary btn-sm" style="font-size:12px;white-space:nowrap;">→ Checkout</a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach;
            else: ?>
                <div class="empty-state">
                    <div style="font-size:48px;margin-bottom:16px;">🛒</div>
                    <div style="font-weight:600;font-size:18px;margin-bottom:8px;">Your cart is empty</div>
                    <div style="font-size:14px;margin-bottom:16px;">Browse listings and add items you love.</div>
                    <a href="home.php" class="btn btn-primary">Explore Listings</a>
                </div>
            <?php endif; ?>
        </div>

        <?php if (!empty($items)): ?>
            <div>
                <div class="cart-card">
                    <div class="cart-title" style="font-size:18px;">Order Summary</div>
                    <?php $available = array_filter($items, fn($i) => $i['status'] === 'approved'); ?>
                    <?php foreach ($available as $item): ?>
                        <div class="summary-row">
                            <span style="white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:180px;"><?= htmlspecialchars($item['title']) ?></span>
                            <span>R&nbsp;<?= number_format($item['price'], 2) ?></span>
                        </div>
                    <?php endforeach; ?>
                    <div class="summary-row" style="margin-top:8px;"><span>Platform fee</span><span style="color:#1a5c35;">Free 🌿</span></div>
                    <div class="summary-row" style="margin-top:4px;"><span>Total</span><span>R&nbsp;<?= number_format(array_sum(array_column(array_values($available), 'price')), 2) ?></span></div>

                    <?php if (!$loggedIn): ?>
                        <div style="margin-top:16px;background:#fff3e0;border:1px solid #ffe0b2;border-radius:8px;padding:12px;font-size:13px;color:#7a4f00;text-align:center;">
                            🔐 <a href="../php/AuthSystem/register.php" style="color:var(--primary);font-weight:600;">Sign up</a> or
                            <a href="../php/AuthSystem/login.php" style="color:var(--primary);font-weight:600;">log in</a> to complete checkout
                        </div>
                    <?php elseif (count($available) === 1): ?>
                        <a href="../php/Orders&Marketplace/checkout.php?id=<?= $available[array_key_first($available)]['listingID'] ?>"
                            class="btn btn-primary" style="width:100%;margin-top:16px;text-align:center;">Checkout →</a>
                    <?php elseif (count($available) > 1): ?>
                        <div style="margin-top:16px;background:#fff3e0;border:1px solid #ffe0b2;border-radius:8px;padding:12px;font-size:13px;color:#7a4f00;">
                            ⚠ Each item is purchased separately. Click <strong>Buy Now</strong> on each item above to checkout individually.
                        </div>
                    <?php endif; ?>

                    <div style="margin-top:14px;font-size:12px;color:var(--text-muted);line-height:1.7;">
                        🛡 Ozow Escrow Protected<br>
                        📦 PUDO / Paxi / Aramex<br>
                        🌿 Zero platform fees
                    </div>
                </div>
                <div style="margin-top:12px;">
                    <a href="home.php" class="btn btn-primary" style="background: #076c44;">← Continue Shopping</a>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script>
        function removeItem(listingID) {
            if (!confirm('Remove this item from your cart?')) return;
            const fd = new FormData();
            fd.append('action', 'remove');
            fd.append('listingID', listingID);
            fetch('../php/Cart/cartAction.php', {
                    method: 'POST',
                    body: fd
                })
                .then(r => r.json()).then(data => {
                    if (data.action === 'removed' || data.action === 'guest_removed') {
                        const el = document.getElementById('cartItem' + listingID);
                        if (el) {
                            el.style.transition = 'opacity .3s';
                            el.style.opacity = '0';
                            setTimeout(() => location.reload(), 300);
                        }
                        if (data.cartCount !== undefined || data.guestCount !== undefined) {
                            const badge = document.getElementById('cartBadge');
                            const count = data.cartCount || data.guestCount || 0;
                            if (badge) {
                                if (count > 0) {
                                    badge.textContent = count;
                                    badge.style.display = 'inline-block';
                                } else {
                                    badge.style.display = 'none';
                                }
                            }
                        }
                    }
                });
        }

        function saveNote(listingID) {
            const note = document.getElementById('note' + listingID).value;
            const fd = new FormData();
            fd.append('action', 'update');
            fd.append('listingID', listingID);
            fd.append('note', note);
            fetch('../php/Cart/cartAction.php', {
                    method: 'POST',
                    body: fd
                })
                .then(r => r.json()).then(() => {
                    const btn = event.target;
                    btn.textContent = '✔ Saved!';
                    setTimeout(() => btn.textContent = '💾 Save note', 1500);
                });
        }
    </script>
</body>

</html>