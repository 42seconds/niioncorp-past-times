<?php
session_start();
require_once '../php/BackendLogic/dbConn.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) { header('Location: home.php'); exit; }

$stmt = $conn->prepare("
    SELECT l.*, u.userID as ownerID, u.username, u.firstName, u.lastName
    FROM tblListings l
    JOIN tblUser u ON l.sellerID = u.userID
    WHERE l.listingID = ? AND l.status = 'approved'
");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) { header('Location: home.php'); exit; }
$p = $result->fetch_assoc();
$stmt->close();

$isAdmin       = isset($_SESSION['adminID']) && ($_SESSION['role'] ?? '') === 'admin';
$loggedIn      = isset($_SESSION['userID']) || $isAdmin;
$activeUID     = $isAdmin ? (int)$_SESSION['adminID'] : (int)($_SESSION['userID'] ?? 0);
$isOwner       = !$isAdmin && $loggedIn && (int)($_SESSION['userID'] ?? 0) === (int)$p['ownerID'];
$isSeller      = $loggedIn && ($_SESSION['role'] ?? '') === 'seller';
$initials      = $loggedIn ? strtoupper(substr($_SESSION['firstName'] ?? '',0,1).substr($_SESSION['lastName'] ?? '',0,1)) : '';
$sellerInitials= strtoupper(substr($p['firstName'] ?? '',0,1).substr($p['lastName'] ?? '',0,1));

// Get cart count for badge
$cartCount = 0;
if ($loggedIn && !$isSeller && !$isAdmin) {
    $cntRes = $conn->query("SELECT COUNT(*) as c FROM tblCart WHERE userID=$activeUID");
    $cartCount = (int)$cntRes->fetch_assoc()['c'];
} elseif (!$loggedIn && isset($_SESSION['guest_cart'])) {
    $cartCount = count($_SESSION['guest_cart']);
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($p['title']) ?> – Past Times</title>
  <link rel="stylesheet" href="../css/styles.css">
  <link rel="stylesheet" href="../css/responsive.css">
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
  <style>
    .product-img{width:100%;height:420px;object-fit:cover;border-radius:12px;}
    .product-img-placeholder{height:420px;display:flex;align-items:center;justify-content:center;font-size:72px;background:var(--bg-warm);border-radius:12px;}
    .cart-icon-wrapper { position: relative; cursor: pointer; }
    .cart-badge { position: absolute; top: -4px; right: -4px; background: #C0392B; color: white; border-radius: 999px; font-size: 9px; padding: 1px 5px; font-weight: 700; min-width: 16px; text-align: center; }
    .navbar-toggle { display: none; flex-direction: column; gap: 5px; cursor: pointer; padding: 6px; background: none; border: none; }
    .navbar-toggle span { display: block; width: 22px; height: 2px; background: #1A1A1A; border-radius: 2px; }
    @media (max-width: 768px) { .navbar-toggle { display: flex; } .navbar-nav { display: none; flex-direction: column; width: 100%; background: #fff; border-top: 1px solid #E8E2DA; order: 3; } .navbar-nav.open { display: flex; } .navbar { flex-wrap: wrap; padding: 10px 16px; } .navbar-brand { flex: 1; } }
  </style>
</head>
<body>
<nav class="navbar">
  <div class="navbar-brand" onclick="location.href='home.php'"><div class="logo-icon">P</div><span style="font-family:var(--font-display);font-size:16px;font-weight:700;">Past Times</span></div>
  <button class="navbar-toggle" aria-label="Toggle menu" aria-expanded="false"
        onclick="this.parentElement.querySelector('.navbar-nav').classList.toggle('open'); this.setAttribute('aria-expanded', this.parentElement.querySelector('.navbar-nav').classList.contains('open'));">
      <span></span><span></span><span></span>
  </button>
  <div class="navbar-nav">
    <a class="nav-link" href="home.php">Explore</a>
    <a class="nav-link" href="about.php">About</a>
    <?php if ($loggedIn && !$isSeller && !$isAdmin): ?>
      <a class="nav-link" href="favorites.php">Favourites</a>
    <?php endif; ?>
    <a class="nav-link" href="contactUs.php">Contact</a>
  </div>
  <div class="navbar-actions">
    <?php if ($loggedIn):
        $dashUrl = $isAdmin ? '../php/AdminArea/adminDashboard.php' : ($isSeller ? '../php/SellerArea/sellerDashboard.php' : 'dashboard.php');
      ?>
      <?php if (!$isSeller && !$isAdmin): ?>
      <div class="cart-icon-wrapper" onclick="location.href='cart.php'" title="Cart">
        <div class="icon-btn">🛒</div>
        <span id="cartBadge" class="cart-badge" style="display: <?= $cartCount > 0 ? 'inline-block' : 'none' ?>;"><?= $cartCount ?></span>
      </div>
      <?php endif; ?>
      <div class="avatar-btn" onclick="location.href='<?= $dashUrl ?>'" title="Dashboard"><?= $initials ?></div>
    <?php else: ?>
      <div class="cart-icon-wrapper" onclick="location.href='cart.php'" title="Cart">
        <div class="icon-btn">🛒</div>
        <span id="cartBadge" class="cart-badge" style="display: <?= $cartCount > 0 ? 'inline-block' : 'none' ?>;"><?= $cartCount ?></span>
      </div>
      <a href="../php/AuthSystem/login.php" class="btn btn-secondary btn-sm" style="margin-right:8px;">Log In</a>
      <a href="../php/AuthSystem/register.php" class="btn btn-primary btn-sm">Sign Up</a>
    <?php endif; ?>
  </div>
</nav>

<div class="product-detail-layout">
  <!-- IMAGE -->
  <div>
    <?php if (!empty($p['imagePath'])): ?>
      <img src="../<?= htmlspecialchars($p['imagePath']) ?>" class="product-img" alt="">
    <?php else: ?>
      <div class="product-img-placeholder">📦</div>
    <?php endif; ?>
  </div>

  <!-- INFO -->
  <div>
    <div class="escrow-note">🛡 ESCROW PROTECTED</div>
    <div class="product-detail-brand"><?= htmlspecialchars($p['category']) ?></div>
    <h1 class="product-detail-title"><?= htmlspecialchars($p['title']) ?></h1>
    <div class="product-detail-price">R <?= number_format($p['price'], 2) ?></div>

    <div class="condition-box">
      <div class="condition-row">
        <span style="font-weight:600;">Condition</span>
        <span class="badge badge-teal"><?= htmlspecialchars($p['condition_']) ?></span>
      </div>
      <div class="condition-desc"><?= htmlspecialchars($p['description'] ?: 'No description provided.') ?></div>
    </div>

    <!-- SELLER -->
    <div class="seller-box" onclick="location.href='seller-profile.php?id=<?= $p['ownerID'] ?>'">
      <div class="seller-avatar"><?= $sellerInitials ?></div>
      <div style="flex:1;">
        <div class="seller-name">@<?= htmlspecialchars($p['username']) ?></div>
        <div class="seller-stats">★★★★★ Trusted Seller</div>
        <div class="seller-response">View full profile →</div>
      </div>
    </div>

    <!-- DELIVERY -->
    <?php if (!empty($p['delivery'])): ?>
    <div class="shipping-section">
      <h4>Delivery Options</h4>
      <div class="shipping-grid">
        <?php foreach (explode(',', $p['delivery']) as $d):
          $d = trim($d); if (!$d) continue;
          $icon = match($d) { 'Paxi'=>'📦','PUDO'=>'🔒','Aramex'=>'⚡','Collection'=>'🤝', default=>'📦' };
        ?>
          <div class="shipping-option active"><div><?= $icon ?></div><div class="shipping-name"><?= htmlspecialchars($d) ?></div></div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

    <!-- ACTIONS -->
    <div class="action-btns">
      <?php if ($isOwner): ?>
        <div style="background:#fff3e0;border:1px solid #ffe0b2;border-radius:8px;padding:12px 16px;font-size:13px;color:#7a4f00;">
          This is your listing. Manage it from your 
          <a href="../php/SellerArea/sellerDashboard.php" style="color:var(--primary);font-weight:600;">dashboard</a>.
        </div>

      <?php elseif ($isSeller || $isAdmin): ?>
        <div style="background:#e8f0fe;border:1px solid #c5d5fb;border-radius:8px;padding:14px 16px;font-size:13px;color:#1a3c8b;">
          <?= $isSeller ? '🏪 As a seller, you cannot purchase items on the platform.' : '🔒 Admins cannot place orders.' ?>
          <?php if ($isSeller): ?>
          <div style="margin-top:8px;"><a href="messages.php?listing=<?= $p['listingID'] ?>&seller=<?= $p['ownerID'] ?>" style="color:var(--primary);font-weight:600;">Message Seller →</a></div>
          <?php endif; ?>
        </div>

      <?php elseif ($loggedIn): ?>
        <button class="btn btn-primary btn-lg" onclick="location.href='../php/Orders&Marketplace/checkout.php?id=<?= $p['listingID'] ?>'">Buy Now — R <?= number_format($p['price'],2) ?></button>
        <div class="action-row">
          <button class="btn btn-secondary" id="favBtn" onclick="addToFav(<?= $p['listingID'] ?>, this)">❤️ Save</button>
          <button class="btn btn-teal" onclick="location.href='messages.php?listing=<?= $p['listingID'] ?>&seller=<?= $p['ownerID'] ?>'">Message Seller</button>
        </div>
      <?php else: ?>
        <div style="background:var(--bg-warm);border:1px solid var(--border);border-radius:12px;padding:20px;text-align:center;">
          <div style="font-size:13px;color:var(--text-muted);margin-bottom:16px;">Create a free account to place orders and find out more about the seller and products.</div>
          <div style="display:flex;gap:10px;justify-content:center;">
            <a href="../php/AuthSystem/register.php" class="btn btn-primary">Sign Up Free</a>
            <a href="../php/AuthSystem/login.php" class="btn btn-secondary">Log In</a>
          </div>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<footer class="footer" style="margin-top:40px;">
  <div class="footer-bottom">
    <span class="footer-brand-name">Past Times</span>
    <div style="display:flex;gap:24px;font-size:13px;color:var(--text-muted);">
      <span>Secure Escrow via Ozow</span><span>Paxi Points</span><span>PUDO Lockers</span>
    </div>
    <div class="footer-badges">🔒 🚐 🌿 PROUDLY SOUTH AFRICAN</div>
  </div>
</footer>

<script>
function updateCartBadge(count) {
    const badge = document.getElementById('cartBadge');
    if (badge) {
        if (count > 0) {
            badge.textContent = count;
            badge.style.display = 'inline-block';
        } else {
            badge.style.display = 'none';
        }
    }
}

function addToFav(id, btn) {
    const fd = new FormData();
    fd.append('listingID', id);
    fetch('../php/Favourites/toggleFav.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            if (data.error === 'not_logged_in') {
                // Guest mode
                btn.textContent = '❤️ Saved!';
                btn.disabled = true;
                setTimeout(() => { btn.disabled = false; btn.textContent = '❤️ Save'; }, 2000);
            } else if (data.action === 'added') {
                btn.textContent = '❤️ Saved!';
                btn.disabled = true;
                setTimeout(() => { btn.disabled = false; btn.textContent = '❤️ Save'; }, 2000);
            } else if (data.action === 'removed') {
                btn.textContent = '❤️ Save';
            }
        })
        .catch(() => {});
}
</script>
</body>
</html>