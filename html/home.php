<?php
session_start();
$loggedIn  = isset($_SESSION['userID']);
$initials  = $loggedIn ? strtoupper(substr($_SESSION['firstName'], 0, 1) . substr($_SESSION['lastName'], 0, 1)) : '';

// Pull approved listings from DB (public — no login required)
require_once '../php/BackendLogic/dbConn.php';
$listings = $conn->query("
    SELECT l.listingID, l.title, l.category, l.price, l.imagePath, l.condition_,
           u.username
    FROM tblListings l
    JOIN tblUser u ON l.sellerID = u.userID
    WHERE l.status = 'approved'
    ORDER BY l.createdAt DESC
");
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Explore – Past Times</title>
    <link rel="stylesheet" href="../css/styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,600;0,700;1,400;1,600&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
</head>

<body>

    <!-- NAVBAR — adapts to guest vs logged-in -->
    <nav class="navbar">
        <div class="navbar-brand" onclick="location.href='home.php'">
            <div class="logo-icon">🏠︎</div>
            <span style="font-family:var(--font-display);font-size:16px;font-weight:700;">Past Times</span>
        </div>
        <div class="navbar-nav">
            <a class="nav-link active" href="home.php">Explore</a>
            <a class="nav-link" href="about.php">About</a>
            <?php if ($loggedIn): ?>
                <a class="nav-link" href="favorites.php">Favourites</a>
            <?php endif; ?>
            <a class="nav-link" href="contactUs.php">Contact</a>

        </div>
        <div class="navbar-actions">
            <?php if ($loggedIn): ?>
                <div class="icon-btn" onclick="location.href='favorites.php'" title="Favourites">🛒</div>
                <div class="icon-btn">🔔</div>
                <div class="avatar-btn" onclick="location.href='dashboard.php'" title="My Dashboard"><?= $initials ?></div>
            <?php else: ?>
                <a href="../php/AuthSystem/login.php" class="btn btn-secondary btn-sm" style="margin-right:8px;">Log In</a>
                <a href="../php/AuthSystem/register.php" class="btn btn-primary btn-sm">Sign Up</a>
            <?php endif; ?>
        </div>
    </nav>



    <div class="hero">
        <div class="hero-inner">
            <div class="hero-overline">South Africa's Premium Pre-loved Boutique</div>
            <h1 class="hero-title">Fashion that tells a <em>story</em></h1>
            <p class="hero-subtitle">Discover curated pre-loved pieces from South Africa's most conscious fashion community. Every purchase is escrow protected.</p>
            <div class="hero-btns">
                <button class="btn btn-primary btn-lg" style="width:auto;" onclick="document.querySelector('.explore-section').scrollIntoView({behavior:'smooth'})">Shop Now</button>
                <?php if ($loggedIn): ?>
              <!--      <button class="btn btn-secondary btn-lg" style="width:auto;border-color:rgba(255,255,255,0.3);color:white;" onclick="location.href='../php/SellerArea/create-listing.php'">Start Selling</button>
              --->  <?php else: ?>
                    <button class="btn btn-secondary btn-lg" style="width:auto;border-color:rgba(255,255,255,0.3);color:white;" onclick="location.href='../php/AuthSystem/register.php'">Join Free</button>
                <?php endif; ?>
            </div>
        </div>
    </div>


    <div class="trust-strip">
        <div class="trust-item-strip"> Secure Escrow via Ozow</div>
        <div class="trust-item-strip"> PUDO Locker Delivery</div>
        <div class="trust-item-strip"> Aramex Express Available</div>
        <div class="trust-item-strip"> Proudly South African</div>
    </div>

    <!-- LISTINGS GRID -->
    <div class="explore-section">
        <div class="explore-section-title">Explore Curated Finds</div>
        <div class="explore-section-sub">Handpicked pre-loved treasures from verified sellers across South Africa</div>

        <div class="category-chips">
            <div class="category-chip active" onclick="filterCategory(this,'all')">All Items</div>
            <div class="category-chip" onclick="filterCategory(this,'Clothing')">Clothing</div>
            <div class="category-chip" onclick="filterCategory(this,'Accessories')">Accessories</div>
            <div class="category-chip" onclick="filterCategory(this,'Footwear')">Footwear</div>
            <div class="category-chip" onclick="filterCategory(this,'Outerwear')">Outerwear</div>
            <div class="category-chip" onclick="filterCategory(this,'Jewelry')">Jewelry</div>
            <div class="category-chip" onclick="filterCategory(this,'Vintage')">Vintage</div>
        </div>



        <div class="product-grid" id="productGrid">
            <?php if ($listings && $listings->num_rows > 0):
                while ($item = $listings->fetch_assoc()):
                    $emoji = match (strtolower($item['category'])) {
                        'clothing'    => '🧥',
                        'accessories' => '👜',
                        'footwear'    => '👟',
                        'outerwear'   => '🧣',
                        'jewelry'     => '⌚',
                        'vintage'     => '🎩',
                        default       => '📦'
                    };
            ?>

                    <div class="product-card" data-category="<?= htmlspecialchars($item['category']) ?>"
                        onclick="location.href='product-detail.php?id=<?= $item['listingID'] ?>'">
                        <button class="heart-btn" onclick="event.stopPropagation();handleFav(this,<?= $item['listingID'] ?>)">𖹭</button>
                        <?php if (!empty($item['imagePath'])): ?>
                            <img src="../<?= htmlspecialchars($item['imagePath']) ?>" 
                            style="width:100%;height:200px;object-fit:cover;" alt="">
                             <!-- ✅ IMAGE: DB stores "php/uploads/listings/file" → resolves to root/php/uploads/listings/ --> 
                           
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

                <?php endwhile;
            else: ?>

                <div style="grid-column:1/-1;text-align:center;padding:60px 20px;color:var(--text-muted);">
                    <div style="font-size:48px;margin-bottom:16px;">🛍</div>
                    <div style="font-size:18px;font-weight:600;margin-bottom:8px;">No listings yet</div>
                    <div style="font-size:14px;">Be the first to list something amazing.</div>
                    <?php if ($loggedIn): ?>
                        <a href="../php/SellerArea/create-listing.php" class="btn btn-primary" style="display:inline-block;margin-top:16px;">Create a Listing</a>
                    <?php endif; ?>
                </div>

            <?php endif; ?>
        </div>



        <footer class="footer">
            <div class="footer-grid">
                <div>
                    <div class="footer-brand-name">Past Times</div>
                    <div class="footer-tagline">South Africa's Premium Pre-loved Boutique.</div>
                </div>
                <div class="footer-col">
                    <h4>Marketplace</h4><span>Secure Escrow via Ozow</span><span>Paxi Points</span><span>PUDO Lockers</span>
                </div>
                <div class="footer-col">
                    <h4>Company</h4><a href="about.php">About Us</a><a href="contactUs.php">Contact</a>
                </div>
            </div>
            <div class="footer-bottom"><span>© 2026 Past Times Marketplace</span>
                <div class="footer-badges"><span>SECURE</span><span>PUDO ASSURED</span><span>& PROUDLY SA</span></div>
            </div>
        </footer>

        <script src="../javascript/script.js"></script>
        <script>
            const isLoggedIn = <?= $loggedIn ? 'true' : 'false' ?>;


            function filterCategory(el, cat) {
                document.querySelectorAll('.category-chip').forEach(c => c.classList.remove('active'));
                el.classList.add('active');
                document.querySelectorAll('#productGrid .product-card').forEach(card => {
                    card.style.display = (cat === 'all' || card.dataset.category === cat) ? '' : 'none';
                });
            }

            function handleFav(btn, id) {
                if (!isLoggedIn) {
                    if (confirm('You need an account to save favourites. Sign up now?')) {
                        location.href = '../php/AuthSystem/register.php';
                    }
                    return;
                }
                btn.textContent = btn.textContent === '𖹭' ? '♥️' : '𖹭';
            }
        </script>
</body>

</html>