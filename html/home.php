<?php
session_start();
$loggedIn  = isset($_SESSION['userID']);
$userID    = $loggedIn ? (int)$_SESSION['userID'] : 0;
$initials  = $loggedIn ? strtoupper(substr($_SESSION['firstName'],0,1).substr($_SESSION['lastName'],0,1)) : '';

require_once '../php/BackendLogic/dbConn.php';

// Approved listings
$listings = $conn->query("
    SELECT l.listingID, l.title, l.category, l.price, l.imagePath, l.condition_,
           u.username
    FROM tblListings l
    JOIN tblUser u ON l.sellerID = u.userID
    WHERE l.status = 'approved'
    ORDER BY l.createdAt DESC
");

// Pre-load this user's saved favourites so hearts render filled on load
$savedIDs = [];
if ($loggedIn) {
    $favRes = $conn->query("SELECT listingID FROM tblFavourites WHERE userID=$userID");
    while ($r = $favRes->fetch_assoc()) $savedIDs[] = (int)$r['listingID'];
}

$conn->close();
?>


<!DOCTYPE html>
<html lang="en">

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Explore – Past Times</title>
  <link rel="stylesheet" href="../css/styles.css">
  <link rel="stylesheet" href="../css/responsive.css">
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,600;0,700;1,400;1,600&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
  <style>
    /* Heart button states */
    .heart-btn { transition: transform .15s; }
    .heart-btn.saved { color: #e74c3c; }
    .heart-btn:active { transform: scale(1.3); }
  </style>
</head>
<body>

    <!-- NAVBAR —-->
  <nav class="navbar">
  <div class="navbar-brand" onclick="location.href='home.php'">
    <div class="logo-icon">P</div>
    <span style="font-family:var(--font-display);font-size:16px;font-weight:700;">Past Times</span>
  </div>
  <button class="navbar-toggle" aria-label="Toggle menu" aria-expanded="false"
    onclick="var nav=this.parentElement.querySelector('.navbar-nav');var open=nav.classList.toggle('open');this.setAttribute('aria-expanded',open);">
    <span></span><span></span><span></span>
  </button>
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
      <?php if (!$loggedIn): ?>
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
        $emoji = match(strtolower($item['category'])) {
          'clothing'=>'🧥','accessories'=>'👜','footwear'=>'👟',
          'outerwear'=>'🧣','jewelry'=>'⌚','vintage'=>'🎩',default=>'📦'
        };
        $isSaved = in_array((int)$item['listingID'], $savedIDs);
    ?>
    <div class="product-card" data-category="<?= htmlspecialchars($item['category']) ?>"
         onclick="location.href='product-detail.php?id=<?= $item['listingID'] ?>'">

      <button class="heart-btn <?= $isSaved ? 'saved' : '' ?>"
              data-id="<?= $item['listingID'] ?>"
              onclick="event.stopPropagation(); handleFav(this, <?= $item['listingID'] ?>)"
              title="<?= $isSaved ? 'Remove from Favourites' : 'Add to Favourites' ?>">
        <?= $isSaved ? '❤️' : '🤍' ?>
      </button>

      <?php if (!empty($item['imagePath'])): ?>
        <img src="../<?= htmlspecialchars($item['imagePath']) ?>" style="width:100%;height:200px;object-fit:cover;" alt="">
      <?php else: ?>
        <div class="product-img-placeholder"><?= $emoji ?></div>
      <?php endif; ?>

      <div class="product-info">
        <div class="product-name"><?= htmlspecialchars($item['title']) ?></div>
        <div class="product-meta"><?= htmlspecialchars($item['category']) ?> • <?= htmlspecialchars($item['condition_']) ?></div>
        <div class="product-price">R <?= number_format($item['price'],2) ?></div>
        <div style="font-size:12px;color:var(--text-muted);margin-top:4px;">@<?= htmlspecialchars($item['username']) ?></div>
      </div>
    </div>
    <?php endwhile; else: ?>
    <div style="grid-column:1/-1;text-align:center;padding:60px 20px;color:var(--text-muted);">
      <div style="font-size:48px;margin-bottom:16px;">🛍</div>
      <div style="font-size:18px;font-weight:600;margin-bottom:8px;">No listings yet</div>
      <div style="font-size:14px;">Be the first to list something amazing.</div>
    </div>
    <?php endif; ?>
  </div>
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

            /*function handleFav(btn, id) {
                if (!isLoggedIn) {
                    if (confirm('You need an account to save favourites. Sign up now?')) {
                        location.href = '../php/AuthSystem/register.php';
                    }
                    return;
                }
                btn.textContent = btn.textContent === '𖹭' ? '♥️' : '𖹭';
            }*/

            function handleFav(btn, id) {
  if (!isLoggedIn) {
    if (confirm('You need an account to save favourites. Sign up now?')) {
      location.href = '../php/AuthSystem/register.php';
    }
    return;
  }


  const wasSaved = btn.classList.contains('saved');
  btn.textContent  = wasSaved ? '𖹭' : '❤️';
  btn.title        = wasSaved ? 'Add to Favourites' : 'Remove from Favourites';
  btn.classList.toggle('saved', !wasSaved);

  // move to DB
  const fd = new FormData();
  fd.append('listingID', id);

  fetch('../php/Favourites/toggleFav.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(data => {
      if (data.error) {
        // Revert on error
        btn.textContent = wasSaved ? '❤️' : '𖹭';
        btn.classList.toggle('saved', wasSaved);
        if (data.error === 'not_logged_in') location.href = '../php/AuthSystem/login.php';
      }
    })
    .catch(() => {
      // catch the  error
      btn.textContent = wasSaved ? '❤️' : '𖹭';
      btn.classList.toggle('saved', wasSaved);
    });
}

        </script>
</body>

</html>