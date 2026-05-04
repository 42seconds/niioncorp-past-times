<?php
session_start();
$loggedIn = isset($_SESSION['userID']);
$initials = $loggedIn
    ? strtoupper(substr($_SESSION['firstName'], 0, 1) . substr($_SESSION['lastName'], 0, 1))
    : '';
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Favourites – Past Times</title>
    <link rel="stylesheet" href="../css/styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
</head>

<body>

    <nav class="navbar">
        <div class="navbar-brand" onclick="location.href='home.php'">
            <div class="logo-icon">🏠︎</div>
            <span style="font-family:var(--font-display);font-size:16px;font-weight:700;">Past Times</span>
        </div>

        <div class="navbar-nav">
            <a class="nav-link" href="home.php">Explore</a>
            <a class="nav-link" href="about.php">About</a>
            <?php if ($loggedIn): ?>
                <a class="nav-link" href="favorites.php">Favourites</a>
            <?php endif; ?>
            <a class="nav-link active" href="contactUs.php">Contact</a>

        </div>

        <div class="navbar-actions">
            <?php if ($loggedIn): ?>

                <div class="icon-btn" onclick="location.href='favorites.php'">🛒</div>
                <div class="icon-btn">🔔</div>
                <div class="avatar-btn" onclick="location.href='dashboard.php'" title="My Dashboard"><?= $initials ?></div>


            <?php else: ?>

                <a href="../php/AuthSystem/login.php" class="btn btn-secondary btn-sm" style="margin-right:8px;">
                    Log In
                </a>
                <a href="../php/AuthSystem/register.php" class="btn btn-primary btn-sm">
                    Sign Up
                </a>

            <?php endif; ?>
        </div>
    </nav>


    <div class="favorites-layout">
        <h1 class="favorites-title">My Favourites</h1>
        <div class="favorites-subtitle">A personal gallery of unique pieces you've curated from across South Africa.</div>
        <div class="favorites-toolbar">
            <div class="fav-search"><span>🔍︎</span><input placeholder="Search your wishlist..."></div>
            <div style="display:flex;align-items:center;gap:8px;">
                <span style="font-size:13px;color:var(--text-muted);">SORT BY</span>
                <div class="sort-btns">
                    <button class="sort-btn active" onclick="switchSort(this)">Recently Added</button>
                    <button class="sort-btn" onclick="switchSort(this)">Price: Low to High</button>
                </div>
            </div>
        </div>
        <!-- Favourites will be populated from DB in a future iteration -->
        <div class="fav-grid">
            <div style="grid-column:1/-1;text-align:center;padding:60px 20px;color:var(--text-muted);">
                <div style="font-size:48px;margin-bottom:16px;">𖹭</div>
                <div style="font-weight:600;font-size:18px;margin-bottom:8px;">No favourites yet</div>
                <div style="font-size:14px;margin-bottom:16px;">Browse the shop and save items you love.</div>
                <a href="home.php" class="btn btn-primary">Browse Listings</a>
            </div>
        </div>
        <div class="peace-of-mind">
            <div class="pom-text">
                <h3>Shop with Peace of Mind</h3>
                <p>Every purchase is protected by our secure Ozow Escrow system.</p>
            </div>
            <div class="pom-badges"><span class="badge badge-teal">SECURE ESCROW</span><span class="badge badge-teal">PUDO SECURED</span></div>
        </div>
    </div>
    <script src="../javascript/script.js"></script>
</body>

</html>