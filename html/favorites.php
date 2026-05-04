<?php
session_start();
$loggedIn = isset($_SESSION['userID']);
$userID   = $loggedIn ? (int)$_SESSION['userID'] : 0;
$initials = $loggedIn
    ? strtoupper(substr($_SESSION['firstName'], 0, 1) . substr($_SESSION['lastName'], 0, 1))
    : '';

// Redirect guests to login
if (!$loggedIn) {
    header('Location: ../php/AuthSystem/login.php');
    exit;
}

require_once '../php/BackendLogic/dbConn.php';

// Fetch this user's favourited listings
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
$favResults = $favStmt->get_result();
$favItems   = $favResults->fetch_all(MYSQLI_ASSOC);
$favStmt->close();
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
    </style>
</head>

<body>

    <nav class="navbar">
        <div class="navbar-brand" onclick="location.href='home.php'">
            <div class="logo-icon">P</div>
            <span style="font-family:var(--font-display);font-size:16px;font-weight:700;">Past Times</span>
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
            <a class="nav-link active" href="favorites.php">Favourites</a>
            <a class="nav-link" href="contactUs.php">Contact</a>
        </div>

        <div class="navbar-actions">
            <div class="icon-btn" onclick="location.href='favorites.php'" title="Favourites">🛒</div>
            <div class="icon-btn">🔔</div>
            <div class="avatar-btn" onclick="location.href='dashboard.php'" title="My Dashboard"><?= $initials ?></div>
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
                    'clothing'    => '🧥',
                    'accessories' => '👜',
                    'footwear'    => '👟',
                    'outerwear'   => '🧣',
                    'jewelry'     => '⌚',
                    'vintage'     => '🎩',
                    default       => '📦'
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

    <script src="../javascript/script.js"></script>
    <script>
        // Remove a favourite in the database and the favorite page
        function handleFav(btn, id) {
            const card = btn.closest('.product-card');

            const fd = new FormData();
            fd.append('listingID', id);

            fetch('../php/Favourites/toggleFav.php', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(data => {
                    if (data.error) {
                        if (data.error === 'not_logged_in') location.href = '../php/AuthSystem/login.php';
                        return;
                    }
                    if (data.action === 'removed') {
                        // Animate removal
                        card.style.transition = 'opacity .3s, transform .3s';
                        card.style.opacity = '0';
                        card.style.transform = 'scale(0.9)';
                        setTimeout(() => {
                            card.remove();
                            // Show empty if empty
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

        // search filter
        function filterFavs(query) {
            const q = query.toLowerCase();
            document.querySelectorAll('#favGrid .product-card').forEach(card => {
                const title = card.dataset.title || '';
                card.style.display = title.includes(q) ? '' : 'none';
            });
        }

        // Sort 
        function switchSort(btn) {
            document.querySelectorAll('.sort-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');

            const grid = document.getElementById('favGrid');
            const cards = [...grid.querySelectorAll('.product-card')];

            if (btn.textContent.includes('Price')) {
                cards.sort((a, b) => parseFloat(a.dataset.price) - parseFloat(b.dataset.price));
            } else {
                // Recently Added: 
                cards.sort((a, b) => parseInt(b.dataset.id) - parseInt(a.dataset.id));
            }
            cards.forEach(c => grid.appendChild(c));
        }
    </script>
</body>

</html>
