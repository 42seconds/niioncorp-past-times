<?php
session_start();
require_once '../php/BackendLogic/dbConn.php';

$sellerID = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($sellerID <= 0) {
    header('Location: home.php');
    exit;
}

// Fetch seller
$stmt = $conn->prepare("SELECT userID, username, firstName, lastName, status FROM tblUser WHERE userID=? AND role IN ('customer','seller') LIMIT 1");
$stmt->bind_param("i", $sellerID);
$stmt->execute();
$seller = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$seller) {
    header('Location: home.php');
    exit;
}

// Fetch their approved listings
$stmt2 = $conn->prepare("SELECT listingID, title, category, price, imagePath, condition_ FROM tblListings WHERE sellerID=? AND status='approved' ORDER BY createdAt DESC");
$stmt2->bind_param("i", $sellerID);
$stmt2->execute();
$listings = $stmt2->get_result();
$stmt2->close();
$conn->close();

$loggedIn       = isset($_SESSION['userID']);
$initials       = $loggedIn ? strtoupper(substr($_SESSION['firstName'], 0, 1) . substr($_SESSION['lastName'], 0, 1)) : '';
$sellerInitials = strtoupper(substr($seller['firstName'], 0, 1) . substr($seller['lastName'], 0, 1));
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@<?= htmlspecialchars($seller['username']) ?> – Past Times</title>
    <link rel="stylesheet" href="../css/styles.css">
        <link rel="stylesheet" href="../css/responsive.css">

    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
</head>

<body>
    <nav class="navbar">
        <div class="navbar-brand" onclick="location.href='home.php'">
            <div class="logo-icon">P</div><span style="font-family:var(--font-display);font-size:16px;font-weight:700;">Past Times</span>
        </div>

          <button class="navbar-toggle" aria-label="Toggle menu" aria-expanded="false"
          onclick="
            var nav = this.parentElement.querySelector('.navbar-nav');
            var open = nav.classList.toggle('open');
            this.setAttribute('aria-expanded', open);">
                <span></span>
                <span></span>
                <span></span>
            </button>


        <div class="navbar-nav">
            <a class="nav-link" href="home.php">Explore</a>
            <a class="nav-link" href="about.php">About</a>
            <?php if ($loggedIn): ?>
                <a class="nav-link" href="favorites.php">Favourites</a>
            <?php endif; ?>
        </div>
        <div class="navbar-actions">
            <?php if ($loggedIn): ?>
                <div class="icon-btn" onclick="location.href='favorites.php'">🛒</div>
                <div class="avatar-btn" onclick="location.href='dashboard.php'"><?= $initials ?></div>
            <?php else: ?>
                <a href="../php/AuthSystem/login.php" class="btn btn-secondary btn-sm" style="margin-right:8px;">Log In</a>
                <a href="../php/AuthSystem/register.php" class="btn btn-primary btn-sm">Sign Up</a>
            <?php endif; ?>
        </div>
    </nav>


    <div class="profile-layout">
        <div class="profile-header-card">
            <div class="profile-avatar"><?= $sellerInitials ?><div class="verified-badge">✓</div>
            </div>
            <div>
                <h1 class="profile-name">@<?= htmlspecialchars($seller['username']) ?></h1>
                <div class="profile-location">📍 South Africa</div>
            </div>

            <div class="profile-actions">
                <?php if ($loggedIn && (int)$_SESSION['userID'] !== $sellerID): ?>
                    <button class="btn btn-primary">Follow</button>
                 <button class="btn btn-secondary" onclick="location.href='messages.php?seller=<?= $sellerID ?>'"> Message</button> 
                <?php elseif (!$loggedIn): ?>
                    <button class="btn btn-primary" onclick="location.href='../php/AuthSystem/register.php'">Sign Up to Follow</button>
                <?php endif; ?>
            </div>

            <div class="sustainability-card">
                <div class="sus-title"> Sustainability Impact</div>
                <div class="sus-stats">
                    <div>
                        <div class="sus-stat-val"><?= $listings->num_rows ?></div>
                        <div class="sus-stat-label">ITEMS LISTED</div>
                    </div>
                    <div>
                        <div class="sus-stat-val">SA</div>
                        <div class="sus-stat-label">BASED</div>
                    </div>
                </div>
            </div>
        </div>

        <div style="margin-bottom:16px;">
            <h2 style="font-family:var(--font-display);font-size:22px;font-weight:700;margin-bottom:8px;">Active Listings</h2>
        </div>

        <?php $listings->data_seek(0); ?>
        <div class="fav-grid">
            <?php if ($listings->num_rows > 0):
                while ($item = $listings->fetch_assoc()):
                    $emoji = match (strtolower($item['category'])) {
                        'clothing' => '🧥',
                        'accessories' => '👜',
                        'footwear' => '👟',
                        'outerwear' => '🧣',
                        'jewelry' => '⌚',
                        'vintage' => '🎩',
                        default => '📦'
                    };
            ?>
                    <div class="product-card" onclick="location.href='product-detail.php?id=<?= $item['listingID'] ?>'">
                        <button class="heart-btn" onclick="event.stopPropagation();">🤍</button>
                        <?php if (!empty($item['imagePath'])): ?>
                            <img src="../<?= htmlspecialchars($item['imagePath']) ?>" style="width:100%;height:200px;object-fit:cover;" alt="">
                        <?php else: ?>
                            <div class="product-img-placeholder"><?= $emoji ?></div>
                        <?php endif; ?>
                        <div class="product-info">
                            <div class="product-name"><?= htmlspecialchars($item['title']) ?></div>
                            <div class="product-meta"><?= htmlspecialchars($item['category']) ?> • <?= htmlspecialchars($item['condition_']) ?></div>
                            <div class="product-price">R <?= number_format($item['price'], 2) ?></div>
                        </div>
                    </div>
                <?php endwhile;
            else: ?>
                <div style="grid-column:1/-1;text-align:center;padding:40px;color:var(--text-muted);">This seller has no active listings yet.</div>
            <?php endif; ?>
        </div>
    </div>
    <script src="../javascript/script.js"></script>
</body>

</html>