<?php
session_start();
$loggedIn = isset($_SESSION['userID']);
$userID   = $loggedIn ? (int)$_SESSION['userID'] : 0;
$initials = $loggedIn
    ? strtoupper(substr($_SESSION['firstName'] ?? '', 0, 1) . substr($_SESSION['lastName'] ?? '', 0, 1))
    : '';

require_once '../php/BackendLogic/dbConn.php';

// MIGRATE GUEST FAVOURITES TO DATABASE WHEN USER LOGS IN
if ($loggedIn && isset($_SESSION['guest_favs']) && !empty($_SESSION['guest_favs'])) {
    foreach ($_SESSION['guest_favs'] as $listingID) {
        $ins = $conn->prepare("INSERT IGNORE INTO tblFavourites (userID, listingID) VALUES (?, ?)");
        $ins->bind_param("ii", $userID, $listingID);
        $ins->execute();
        $ins->close();
    }
    unset($_SESSION['guest_favs']);
}

// Fetch favourite listings
$favItems = [];
if ($loggedIn) {
    $favStmt = $conn->prepare("
        SELECT l.listingID, l.title, l.category, l.price, l.imagePath, l.condition_,
               u.username, f.createdAt AS savedAt
        FROM tblFavourites f
        JOIN tblListings l ON f.listingID = l.listingID
        JOIN tblUser u     ON l.sellerID  = u.userID
        WHERE f.userID = ?
        ORDER BY f.createdAt DESC
    ");
    $favStmt->bind_param("i", $userID);
    $favStmt->execute();
    $favItems = $favStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $favStmt->close();
} elseif (isset($_SESSION['guest_favs']) && !empty($_SESSION['guest_favs'])) {
    $placeholders = implode(',', array_fill(0, count($_SESSION['guest_favs']), '?'));
    $stmt = $conn->prepare("
        SELECT l.listingID, l.title, l.category, l.price, l.imagePath, l.condition_,
               u.username
        FROM tblListings l
        JOIN tblUser u ON l.sellerID = u.userID
        WHERE l.listingID IN ($placeholders) AND l.status = 'approved'
    ");
    $stmt->bind_param(str_repeat('i', count($_SESSION['guest_favs'])), ...$_SESSION['guest_favs']);
    $stmt->execute();
    $favItems = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Favourites – Past Times</title>
    <link rel="stylesheet" href="../css/styles.css">
    <link rel="stylesheet" href="../css/responsive.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        .heart-btn { transition: transform .15s; }
        .heart-btn.saved { color: #e74c3c; }
        .heart-btn:active { transform: scale(1.3); }
        .cart-icon-wrapper { position: relative; cursor: pointer; }
        .cart-badge { position: absolute; top: -4px; right: -4px; background: #C0392B; color: white; border-radius: 999px; font-size: 9px; padding: 1px 5px; font-weight: 700; min-width: 16px; text-align: center; }
        .navbar-toggle { display: none; flex-direction: column; gap: 5px; cursor: pointer; padding: 6px; background: none; border: none; }
        .navbar-toggle span { display: block; width: 22px; height: 2px; background: #1A1A1A; border-radius: 2px; }
        @media (max-width: 768px) { .navbar-toggle { display: flex; } .navbar-nav { display: none; flex-direction: column; width: 100%; background: #fff; border-top: 1px solid #E8E2DA; order: 3; } .navbar-nav.open { display: flex; } .navbar { flex-wrap: wrap; padding: 10px 16px; } .navbar-brand { flex: 1; } }
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
        <a class="nav-link" href="about.php">About</a>
        <a class="nav-link active" href="favorites.php">Favourites</a>
        <a class="nav-link" href="contactUs.php">Contact</a>
    </div>
    <div class="navbar-actions">
        <div class="cart-icon-wrapper" onclick="location.href='cart.php'" title="Cart">
            <div class="icon-btn">🛒</div>
            <span id="cartBadge" class="cart-badge" style="display: none;"></span>
        </div>
        <?php if ($loggedIn): ?>
            <div class="avatar-btn" onclick="location.href='dashboard.php'" title="My Dashboard"><?= $initials ?></div>
        <?php else: ?>
            <a href="../php/AuthSystem/login.php" class="btn btn-secondary btn-sm" style="margin-right:8px;">Log In</a>
            <a href="../php/AuthSystem/register.php" class="btn btn-primary btn-sm">Sign Up</a>
        <?php endif; ?>
    </div>
</nav>

<div class="favorites-layout">
    <h1 class="favorites-title">My Favourites</h1>
    <div class="favorites-subtitle">A personal gallery of unique pieces you've curated from across South Africa.</div>

    <?php if (!empty($favItems)): ?>
    <div class="favorites-toolbar">
        <div class="fav-search">
            <span>🔍︎</span>
            <input id="favSearch" placeholder="Search your wishlist..." oninput="filterFavs(this.value)">
        </div>
        <div style="display:flex;align-items:center;gap:8px;">
            <span style="font-size:13px;color:var(--text-muted);">SORT BY</span>
            <div class="sort-btns">
                <button class="sort-btn active" onclick="switchSort(this)">Recently Added</button>
                <button class="sort-btn" onclick="switchSort(this)">Price: Low to High</button>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="fav-grid" id="favGrid">
    <?php if (!empty($favItems)): ?>
        <?php foreach ($favItems as $item):
            $emoji = match(strtolower($item['category'])) {
                'clothing' => '🧥', 'accessories' => '👜', 'footwear' => '👟',
                'outerwear' => '🧣', 'jewelry' => '⌚', 'vintage' => '🎩', default => '📦'
            };
        ?>
        <div class="product-card" data-id="<?= $item['listingID'] ?>"
             data-title="<?= htmlspecialchars(strtolower($item['title'])) ?>"
             data-price="<?= $item['price'] ?>"
             onclick="location.href='product-detail.php?id=<?= $item['listingID'] ?>'">

            <button class="heart-btn saved"
                    data-id="<?= $item['listingID'] ?>"
                    onclick="event.stopPropagation(); handleFav(this, <?= $item['listingID'] ?>)"
                    title="Remove from Favourites">
                ❤️
            </button>

            <?php if (!empty($item['imagePath'])): ?>
                <img src="../<?= htmlspecialchars($item['imagePath']) ?>" style="width:100%;height:200px;object-fit:cover;" alt="">
            <?php else: ?>
                <div class="product-img-placeholder"><?= $emoji ?></div>
            <?php endif; ?>

            <div class="product-info">
                <div class="product-name"><?= htmlspecialchars($item['title']) ?></div>
                <div class="product-meta"><?= htmlspecialchars($item['category']) ?> • <?= htmlspecialchars($item['condition_']) ?></div>
                <div class="product-price">R <?= number_format($item['price'], 2) ?></div>
                <div style="font-size:12px;color:var(--text-muted);margin-top:4px;">@<?= htmlspecialchars($item['username']) ?></div>
                <div style="margin-top:10px;display:flex;gap:6px;" onclick="event.stopPropagation()">
                    <button class="btn btn-primary btn-sm"
                            style="font-size:12px;padding:6px 14px;"
                            onclick="addToCart(this, <?= $item['listingID'] ?>)">
                        🛒 Add to Cart
                    </button>
                    <a href="../php/Orders&Marketplace/checkout.php?id=<?= $item['listingID'] ?>"
                       class="btn btn-secondary btn-sm"
                       style="font-size:12px;padding:6px 14px;"
                       onclick="event.stopPropagation()">
                        Buy Now
                    </a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div id="emptyState" style="grid-column:1/-1;text-align:center;padding:60px 20px;color:var(--text-muted);">
            <div style="font-size:48px;margin-bottom:16px;">🤍</div>
            <div style="font-weight:600;font-size:18px;margin-bottom:8px;">No favourites yet</div>
            <div style="font-size:14px;margin-bottom:16px;">Browse the shop and tap the heart on items you love.</div>
            <a href="home.php" class="btn btn-primary">Browse Listings</a>
        </div>
    <?php endif; ?>
    </div>

    <div class="peace-of-mind">
        <div class="pom-text">
            <h3>Shop with Peace of Mind</h3>
            <p>Every purchase is protected by our secure Ozow Escrow system.</p>
        </div>
        <div class="pom-badges">
            <span class="badge badge-teal">SECURE ESCROW</span>
            <span class="badge badge-teal">PUDO SECURED</span>
        </div>
    </div>
</div>

<script>
function addToCart(btn, listingID) {
    btn.disabled = true;
    btn.textContent = '...';
    const fd = new FormData();
    fd.append('action','add');
    fd.append('listingID', listingID);
    fetch('../php/Cart/cartAction.php', {method:'POST', body:fd})
        .then(r => r.json())
        .then(data => {
            if (data.action === 'added' || data.action === 'guest_added') {
                btn.textContent = '✔ In Cart';
                btn.style.background = '#e6faf0';
                btn.style.color = '#1a5c35';
                btn.style.border = '1px solid #b2dbd7';
                btn.disabled = false;
                // Update cart badge
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
            } else if (data.error === 'not_logged_in') {
                btn.textContent = '✔ In Cart';
                btn.style.background = '#e6faf0';
                btn.style.color = '#1a5c35';
                btn.style.border = '1px solid #b2dbd7';
                btn.disabled = false;
                if (data.guestCount !== undefined) {
                    const badge = document.getElementById('cartBadge');
                    if (badge && data.guestCount > 0) {
                        badge.textContent = data.guestCount;
                        badge.style.display = 'inline-block';
                    }
                }
            } else {
                btn.textContent = '🛒 Add to Cart';
                btn.disabled = false;
            }
        })
        .catch(() => { btn.textContent = '🛒 Add to Cart'; btn.disabled = false; });
}

function handleFav(btn, id) {
    const card = btn.closest('.product-card');
    const fd = new FormData();
    fd.append('listingID', id);

    fetch('../php/Favourites/toggleFav.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            if (data.error === 'not_logged_in') {
                // Guest mode - just remove from UI
                card.style.transition = 'opacity .3s, transform .3s';
                card.style.opacity = '0';
                card.style.transform = 'scale(0.9)';
                setTimeout(() => {
                    card.remove();
                    const remaining = document.querySelectorAll('#favGrid .product-card');
                    if (remaining.length === 0) {
                        document.getElementById('favGrid').innerHTML = `
                            <div style="grid-column:1/-1;text-align:center;padding:60px 20px;color:var(--text-muted);">
                                <div style="font-size:48px;margin-bottom:16px;">🤍</div>
                                <div style="font-weight:600;font-size:18px;margin-bottom:8px;">No favourites yet</div>
                                <div style="font-size:14px;margin-bottom:16px;">Browse the shop and tap the heart on items you love.</div>
                                <a href="home.php" class="btn btn-primary">Browse Listings</a>
                            </div>`;
                    }
                }, 300);
                return;
            }
            if (data.error) {
                if (data.error === 'not_logged_in') location.href = '../php/AuthSystem/login.php';
                return;
            }
            if (data.action === 'removed') {
                card.style.transition = 'opacity .3s, transform .3s';
                card.style.opacity = '0';
                card.style.transform = 'scale(0.9)';
                setTimeout(() => {
                    card.remove();
                    const remaining = document.querySelectorAll('#favGrid .product-card');
                    if (remaining.length === 0) {
                        document.getElementById('favGrid').innerHTML = `
                            <div style="grid-column:1/-1;text-align:center;padding:60px 20px;color:var(--text-muted);">
                                <div style="font-size:48px;margin-bottom:16px;">🤍</div>
                                <div style="font-weight:600;font-size:18px;margin-bottom:8px;">No favourites yet</div>
                                <div style="font-size:14px;margin-bottom:16px;">Browse the shop and tap the heart on items you love.</div>
                                <a href="home.php" class="btn btn-primary">Browse Listings</a>
                            </div>`;
                    }
                }, 300);
            }
        })
        .catch(err => console.error('Toggle fav error:', err));
}

function filterFavs(query) {
    const q = query.toLowerCase();
    document.querySelectorAll('#favGrid .product-card').forEach(card => {
        const title = card.dataset.title || '';
        card.style.display = title.includes(q) ? '' : 'none';
    });
}

function switchSort(btn) {
    document.querySelectorAll('.sort-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');

    const grid = document.getElementById('favGrid');
    const cards = [...grid.querySelectorAll('.product-card')];

    if (btn.textContent.includes('Price')) {
        cards.sort((a, b) => parseFloat(a.dataset.price) - parseFloat(b.dataset.price));
    } else {
        cards.sort((a, b) => parseInt(b.dataset.id) - parseInt(a.dataset.id));
    }
    cards.forEach(c => grid.appendChild(c));
}

// Get cart count on load
fetch('../php/Cart/getCartCount.php')
    .then(r => r.json())
    .then(data => {
        const badge = document.getElementById('cartBadge');
        if (badge && data.count > 0) {
            badge.textContent = data.count;
            badge.style.display = 'inline-block';
        }
    })
    .catch(() => {});
</script>
</body>
</html>