<?php
session_start();

// Check if user is logged in
$loggedIn = isset($_SESSION['userID']);
$userID = $loggedIn ? (int)$_SESSION['userID'] : 0;
$initials = '';

require_once '../php/BackendLogic/dbConn.php';

// MIGRATE GUEST CART TO DATABASE WHEN USER LOGS IN
if ($loggedIn && isset($_SESSION['guest_cart']) && !empty($_SESSION['guest_cart'])) {
    foreach ($_SESSION['guest_cart'] as $listingID => $qty) {
        $ins = $conn->prepare("
            INSERT INTO tblCart (userID, listingID, quantity)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE quantity = quantity + VALUES(quantity), addedAt = NOW()
        ");
        $ins->bind_param("iii", $userID, $listingID, $qty);
        $ins->execute();
        $ins->close();
    }
    unset($_SESSION['guest_cart']);
}

if ($loggedIn) {
    $initials = strtoupper(substr($_SESSION['firstName'] ?? '', 0, 1) . substr($_SESSION['lastName'] ?? '', 0, 1));

    $stmt = $conn->prepare("
        SELECT c.cartID,
               c.listingID,
               c.quantity,
               c.note,
               c.addedAt,
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
    $items = [];
    if (isset($_SESSION['guest_cart']) && !empty($_SESSION['guest_cart'])) {
        $guestIDs = array_keys($_SESSION['guest_cart']);
        $placeholders = implode(',', array_fill(0, count($guestIDs), '?'));
        $stmt = $conn->prepare("
            SELECT l.listingID, l.title, l.category, l.condition_, l.price, l.imagePath, l.delivery, l.status,
                   u.username AS sellerName, u.userID AS sellerUID
            FROM tblListings l
            JOIN tblUser u ON l.sellerID = u.userID
            WHERE l.listingID IN ($placeholders) AND l.status = 'approved'
        ");
        $stmt->bind_param(str_repeat('i', count($guestIDs)), ...$guestIDs);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        foreach ($rows as &$row) {
            $row['quantity'] = $_SESSION['guest_cart'][$row['listingID']] ?? 1;
        }
        unset($row);
        $items = $rows;
    }
}

$total = array_sum(array_map(fn($i) => $i['price'] * ($i['quantity'] ?? 1), $items));
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
        body { background: var(--cream); font-family: var(--font-body); }
        .cart-layout { max-width: 1000px; margin: 36px auto; padding: 0 24px; display: grid; grid-template-columns: 1fr 320px; gap: 24px; align-items: start; }
        @media(max-width:720px) { .cart-layout { grid-template-columns: 1fr; } }
        .cart-card { background: white; border-radius: var(--radius-lg); border: 1px solid var(--border-light); padding: 24px; box-shadow: var(--shadow-sm); }
        .cart-title { font-family: var(--font-display); font-size: 22px; font-weight: 700; margin-bottom: 20px; }
        .cart-item { display: grid; grid-template-columns: 40px 60px 1fr auto; gap: 14px; align-items: start; padding: 16px 0; border-bottom: 1px solid var(--border-light); }
        .cart-item:last-child { border-bottom: none; }
        .item-checkbox { margin-top: 4px; width: 18px; height: 18px; cursor: pointer; accent-color: var(--primary); }
        .item-thumb { width: 60px; height: 60px; border-radius: 8px; object-fit: cover; background: var(--bg-warm); display: flex; align-items: center; justify-content: center; font-size: 24px; overflow: hidden; }
        .item-thumb img { width: 100%; height: 100%; object-fit: cover; }
        .item-title { font-weight: 600; font-size: 15px; margin-bottom: 2px; }
        .item-meta { font-size: 12px; color: var(--text-muted); }
        .item-price { font-family: var(--font-display); font-size: 18px; font-weight: 700; color: var(--primary); white-space: nowrap; }
        .item-note { margin-top: 8px; }
        .item-note textarea { width: 100%; border: 1.5px solid var(--border); border-radius: 8px; padding: 8px 12px; font-size: 13px; font-family: var(--font-body); resize: none; outline: none; box-sizing: border-box; }
        .item-note textarea:focus { border-color: var(--primary); }
        .remove-btn { background: none; border: none; font-size: 16px; color: var(--text-muted); cursor: pointer; padding: 4px; }
        .remove-btn:hover { color: #8b1a14; }
        .save-note-btn { font-size: 12px; color: var(--primary); font-weight: 600; background: none; border: none; cursor: pointer; padding: 2px 0; margin-top: 4px; }
        .summary-row { display: flex; justify-content: space-between; font-size: 14px; padding: 8px 0; border-bottom: 1px solid var(--border-light); }
        .summary-row:last-of-type { border-bottom: none; font-weight: 700; font-size: 16px; }
        .badge-unavailable { background: #fce8e8; color: #8b1a14; border: 1px solid #f5b7b7; padding: 2px 8px; border-radius: 999px; font-size: 11px; font-weight: 700; margin-left: 6px; }
        .empty-state { text-align: center; padding: 60px 20px; color: var(--text-muted); }
        .cart-icon-wrapper { position: relative; cursor: pointer; }
        .cart-badge { position: absolute; top: -4px; right: -4px; background: #C0392B; color: white; border-radius: 999px; font-size: 9px; padding: 1px 5px; font-weight: 700; min-width: 16px; text-align: center; }
        .navbar-toggle { display: none; flex-direction: column; gap: 5px; cursor: pointer; padding: 6px; background: none; border: none; }
        .navbar-toggle span { display: block; width: 22px; height: 2px; background: #1A1A1A; border-radius: 2px; }
        @media (max-width: 768px) {
            .navbar-toggle { display: flex; }
            .navbar-nav { display: none; flex-direction: column; width: 100%; background: #fff; border-top: 1px solid #E8E2DA; order: 3; }
            .navbar-nav.open { display: flex; }
            .navbar { flex-wrap: wrap; padding: 10px 16px; }
            .navbar-brand { flex: 1; }
            .cart-item { grid-template-columns: 30px 50px 1fr auto; gap: 10px; }
        }
        .select-all-wrapper { display: flex; align-items: center; gap: 10px; margin-bottom: 16px; padding: 12px 0; border-bottom: 1px solid var(--border-light); }
        .select-all-wrapper input[type="checkbox"] { width: 18px; height: 18px; cursor: pointer; accent-color: var(--primary); }
        .select-all-wrapper label { font-size: 14px; font-weight: 600; cursor: pointer; color: var(--dark); }
        .checkout-btn { width: 100%; margin-top: 16px; text-align: center; }
        .checkout-btn:disabled { opacity: 0.5; cursor: not-allowed; pointer-events: none; }
        .selected-count { font-size: 13px; color: var(--text-muted); font-weight: 400; margin-left: 8px; }
        .qty-controls { display: flex; align-items: center; gap: 6px; margin-top: 6px; }
        .qty-btn { background: none; border: 1px solid var(--border); border-radius: 4px; width: 24px; height: 24px; cursor: pointer; font-weight: 600; font-size: 14px; display: flex; align-items: center; justify-content: center; transition: all 0.2s; line-height: 1; }
        .qty-btn:hover { background: var(--primary); color: white; border-color: var(--primary); }
        .qty-display { min-width: 24px; text-align: center; font-weight: 600; font-size: 14px; }
    </style>
</head>

<body>
    <nav class="navbar">
        <div class="navbar-brand" onclick="location.href='home.php'">
            <div class="logo-icon">P</div>
            <span style="font-family:var(--font-display);font-size:16px;font-weight:700;">Past Times</span>
        </div>
        <button class="navbar-toggle" aria-label="Toggle menu" aria-expanded="false"
            onclick="this.parentElement.querySelector('.navbar-nav').classList.toggle('open'); this.setAttribute('aria-expanded', this.parentElement.querySelector('.navbar-nav').classList.contains('open'));">
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
                <span id="cartBadge" class="cart-badge" style="display:<?= count($items) > 0 ? 'inline-block' : 'none' ?>;"><?= count($items) ?></span>
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
            <div class="cart-title">
                My Cart
                <span style="font-size:16px;color:var(--text-muted);font-family:var(--font-body);font-weight:400;">
                    (<?= count($items) ?> item<?= count($items) !== 1 ? 's' : '' ?>)
                </span>
                <span id="selectedCount" class="selected-count"></span>
            </div>

            <?php if (!empty($items)): ?>
                <div class="select-all-wrapper">
                    <input type="checkbox" id="selectAll" onchange="toggleAllItems()">
                    <label for="selectAll">Select All Items</label>
                    <span style="font-size:13px;color:var(--text-muted);margin-left:auto;">
                        <span id="selectedTotal">0</span> items selected
                    </span>
                </div>

                <?php foreach ($items as $item):
                    $unavailable = $item['status'] !== 'approved';
                    $emoji = match (strtolower($item['category'] ?? '')) {
                        'clothing'    => '🧥',
                        'accessories' => '👜',
                        'footwear'    => '👟',
                        'outerwear'   => '🧣',
                        'jewelry'     => '⌚',
                        'vintage'     => '🎩',
                        default       => '📦'
                    };
                    $itemTotal = $item['price'] * ($item['quantity'] ?? 1);
                ?>
                    <div class="cart-item" id="cartItem<?= $item['listingID'] ?>" <?= $unavailable ? 'style="opacity:.6;"' : '' ?>>
                        <div>
                            <input type="checkbox"
                                   class="item-checkbox"
                                   value="<?= $item['listingID'] ?>"
                                   data-price="<?= $item['price'] ?>"
                                   data-quantity="<?= $item['quantity'] ?? 1 ?>"
                                   <?= $unavailable ? 'disabled' : '' ?>
                                   onchange="updateSummary()">
                        </div>
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
                            <?php if (!$unavailable): ?>
                            <div class="qty-controls">
                                <button type="button" class="qty-btn" onclick="updateQuantity(<?= $item['listingID'] ?>, -1)">−</button>
                                <span class="qty-display" id="qtyDisplay<?= $item['listingID'] ?>"><?= $item['quantity'] ?? 1 ?></span>
                                <button type="button" class="qty-btn" onclick="updateQuantity(<?= $item['listingID'] ?>, 1)">+</button>
                                <span style="font-size:12px;color:var(--text-muted);margin-left:4px;">× R <?= number_format($item['price'], 2) ?></span>
                            </div>
                            <?php endif; ?>
                            <div class="item-note">
                                <textarea id="note<?= $item['listingID'] ?>" rows="2" placeholder="Add a note for the seller (optional)…"><?= htmlspecialchars($item['note'] ?? '') ?></textarea>
                                <button class="save-note-btn" onclick="saveNote(<?= $item['listingID'] ?>, this)">💾 Save note</button>
                            </div>
                        </div>
                        <div style="display:flex;flex-direction:column;align-items:flex-end;gap:8px;">
                            <div class="item-price" id="itemPrice<?= $item['listingID'] ?>">R <?= number_format($itemTotal, 2) ?></div>
                            <button class="remove-btn" onclick="removeItem(<?= $item['listingID'] ?>)" title="Remove">🗑</button>
                        </div>
                    </div>
                <?php endforeach; ?>

            <?php else: ?>
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
                    <div id="summaryItems">
                        <?php foreach ($items as $item):
                            if ($item['status'] !== 'approved') continue; ?>
                            <div class="summary-row" id="summaryRow<?= $item['listingID'] ?>" style="display:none;">
                                <span style="white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:160px;">
                                    <?= htmlspecialchars($item['title']) ?>
                                    <span id="summaryQtyLabel<?= $item['listingID'] ?>" style="font-size:11px;color:var(--text-muted);">×<?= $item['quantity'] ?? 1 ?></span>
                                </span>
                                <span id="summaryRowPrice<?= $item['listingID'] ?>">R&nbsp;<?= number_format($item['price'] * ($item['quantity'] ?? 1), 2) ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="summary-row" style="margin-top:8px;"><span>Platform fee</span><span style="color:#1a5c35;">Free 🌿</span></div>
                    <div class="summary-row" style="margin-top:4px;font-weight:700;font-size:16px;">
                        <span>Total</span>
                        <span id="summaryTotal">R&nbsp;0.00</span>
                    </div>

                    <?php if (!$loggedIn): ?>
                        <div style="margin-top:16px;background:#fff3e0;border:1px solid #ffe0b2;border-radius:8px;padding:12px;font-size:13px;color:#7a4f00;text-align:center;">
                            🔐 <a href="../php/AuthSystem/register.php" style="color:var(--primary);font-weight:600;">Sign up</a> or
                            <a href="../php/AuthSystem/login.php" style="color:var(--primary);font-weight:600;">log in</a> to complete checkout
                        </div>
                    <?php else: ?>
                        <button onclick="checkoutSelected()"
                                id="checkoutBtn"
                                class="btn btn-primary checkout-btn"
                                disabled>
                            Checkout Selected (0 items)
                        </button>
                    <?php endif; ?>

                    <div style="margin-top:14px;font-size:12px;color:var(--text-muted);line-height:1.7;">
                        🛡 Ozow Escrow Protected<br>
                        📦 PUDO / Paxi / Aramex<br>
                        🌿 Zero platform fees
                    </div>
                </div>
                <div style="margin-top:12px;">
                    <a href="home.php" class="btn btn-primary" style="background:#076c44;">← Continue Shopping</a>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // ── Summary ───────────────────────────────────────────────────────────
        function updateSummary() {
            let total = 0;
            let count = 0;

            document.querySelectorAll('.item-checkbox').forEach(cb => {
                const id      = cb.value;
                const row     = document.getElementById('summaryRow' + id);
                const checked = cb.checked && !cb.disabled;

                if (row) row.style.display = checked ? 'flex' : 'none';

                if (checked) {
                    const price     = parseFloat(cb.dataset.price)     || 0;
                    const qty       = parseInt(cb.dataset.quantity, 10) || 1;
                    const lineTotal = price * qty;
                    total += lineTotal;
                    count++;

                    const rowPrice = document.getElementById('summaryRowPrice' + id);
                    if (rowPrice) rowPrice.textContent = 'R\u00a0' + lineTotal.toFixed(2);

                    const rowQty = document.getElementById('summaryQtyLabel' + id);
                    if (rowQty) rowQty.textContent = '×' + qty;
                }
            });

            document.getElementById('summaryTotal').textContent  = 'R\u00a0' + total.toFixed(2);
            document.getElementById('selectedTotal').textContent = count;

            const countSpan = document.getElementById('selectedCount');
            if (countSpan) countSpan.textContent = count > 0 ? '(' + count + ' selected)' : '';

            const btn = document.getElementById('checkoutBtn');
            if (btn) {
                btn.disabled    = count === 0;
                btn.textContent = count > 0
                    ? 'Checkout Selected (' + count + ' item' + (count > 1 ? 's' : '') + ') — R ' + total.toFixed(2)
                    : 'Checkout Selected (0 items)';
            }

            // Keep select-all in sync
            const allEnabled = document.querySelectorAll('.item-checkbox:not(:disabled)');
            const allChecked = document.querySelectorAll('.item-checkbox:not(:disabled):checked');
            const selectAll  = document.getElementById('selectAll');
            if (selectAll && allEnabled.length > 0) {
                selectAll.indeterminate = allChecked.length > 0 && allChecked.length < allEnabled.length;
                selectAll.checked       = allChecked.length === allEnabled.length;
            }
        }

        function toggleAllItems() {
            const checked    = document.getElementById('selectAll').checked;
            document.querySelectorAll('.item-checkbox:not(:disabled)').forEach(cb => cb.checked = checked);
            updateSummary();
        }

        // ── Checkout ──────────────────────────────────────────────────────────
        function checkoutSelected() {
            const checked = document.querySelectorAll('.item-checkbox:checked');
            if (checked.length === 0) { alert('Please select at least one item to checkout.'); return; }

            const btn = document.getElementById('checkoutBtn');
            btn.disabled     = true;
            btn.textContent  = 'Preparing checkout…';

            const fd = new FormData();
            checked.forEach(cb => {
                const id  = cb.value;
                fd.append('listing_ids[]', id);
                // Send the current DOM quantity — avoids race condition where
                // the DB write from updateQuantity may not have finished yet
                const qtyEl = document.getElementById('qtyDisplay' + id);
                fd.append('quantities[' + id + ']', qtyEl ? qtyEl.textContent.trim() : cb.dataset.quantity || '1');
                const noteEl = document.getElementById('note' + id);
                if (noteEl) fd.append('notes[' + id + ']', noteEl.value);
            });

            fetch('../php/Cart/prepareCheckout.php', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(data => {
                    if (data.ok) {
                        window.location.href = '../php/Orders&Marketplace/multi-checkout-form.php';
                    } else {
                        alert(data.error || 'Could not start checkout. Please try again.');
                        updateSummary(); // re-enable button
                    }
                })
                .catch(() => {
                    alert('Network error. Please try again.');
                    updateSummary();
                });
        }

        // ── Quantity ──────────────────────────────────────────────────────────
        function updateQuantity(listingID, change) {
            const display  = document.getElementById('qtyDisplay' + listingID);
            const checkbox = document.querySelector('.item-checkbox[value="' + listingID + '"]');
            let qty = parseInt(display.textContent, 10) || 1;
            qty += change;

            if (qty < 1) {
                if (confirm('Remove this item from your cart?')) removeItem(listingID);
                return;
            }

            display.textContent = qty;
            if (checkbox) checkbox.dataset.quantity = qty;

            const price     = parseFloat(checkbox ? checkbox.dataset.price : 0) || 0;
            const lineTotal = price * qty;
            const priceEl   = document.getElementById('itemPrice' + listingID);
            if (priceEl) priceEl.textContent = 'R ' + lineTotal.toFixed(2);

            updateSummary();

            const fd = new FormData();
            fd.append('action',    'update_quantity');
            fd.append('listingID', listingID);
            fd.append('quantity',  qty);
            fetch('../php/Cart/cartAction.php', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(data => { if (data.cartCount !== undefined) updateCartBadge(data.cartCount); });
        }

        // ── Remove ────────────────────────────────────────────────────────────
        function removeItem(listingID) {
            if (!confirm('Remove this item from your cart?')) return;
            const fd = new FormData();
            fd.append('action',    'remove');
            fd.append('listingID', listingID);
            fetch('../php/Cart/cartAction.php', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(data => {
                    if (data.action === 'removed' || data.action === 'guest_removed') {
                        const el = document.getElementById('cartItem' + listingID);
                        if (el) { el.style.transition = 'opacity .3s'; el.style.opacity = '0'; setTimeout(() => location.reload(), 320); }
                        updateCartBadge(data.cartCount ?? data.guestCount ?? 0);
                    }
                });
        }

        // ── Save note ─────────────────────────────────────────────────────────
        function saveNote(listingID, btn) {
            const note = document.getElementById('note' + listingID).value;
            const fd   = new FormData();
            fd.append('action',    'update_note');
            fd.append('listingID', listingID);
            fd.append('note',      note);
            fetch('../php/Cart/cartAction.php', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(() => {
                    btn.textContent = '✔ Saved!';
                    setTimeout(() => btn.textContent = '💾 Save note', 1500);
                });
        }

        // ── Badge ─────────────────────────────────────────────────────────────
        function updateCartBadge(count) {
            const badge = document.getElementById('cartBadge');
            if (!badge) return;
            badge.textContent   = count;
            badge.style.display = count > 0 ? 'inline-block' : 'none';
        }

        // ── Init ──────────────────────────────────────────────────────────────
        document.addEventListener('DOMContentLoaded', updateSummary);
    </script>
</body>
</html>