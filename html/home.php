<?php
session_start();
// Detect any logged-in role (admin uses adminID, others use userID)
$isAdmin   = isset($_SESSION['adminID']) && ($_SESSION['role'] ?? '') === 'admin';
$loggedIn  = isset($_SESSION['userID']) || $isAdmin;
$userID    = $isAdmin ? (int)$_SESSION['adminID'] : ((int)($_SESSION['userID'] ?? 0));
$initials  = $loggedIn ? strtoupper(substr($_SESSION['firstName'],0,1).substr($_SESSION['lastName'],0,1)) : '';
$isSeller  = $loggedIn && ($_SESSION['role'] ?? '') === 'seller';

require_once '../php/BackendLogic/dbConn.php';

// Approved listings
$listings = $conn->query("
    SELECT l.listingID, l.title, l.category, l.price, l.imagePath, l.condition_,
           l.sellerID, u.username
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

<?php if ($isAdmin): ?>
<div style="background:var(--dark);color:white;font-size:13px;padding:8px 24px;display:flex;align-items:center;justify-content:space-between;">
  <span>👁 Viewing as Admin — Browse Mode</span>
  <a href="../php/AdminArea/adminDashboard.php" style="color:white;font-weight:700;text-decoration:none;padding:4px 14px;border:1.5px solid rgba(255,255,255,.5);border-radius:999px;font-size:12px;">← Back to Admin Dashboard</a>
</div>
<?php endif; ?>

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
    <?php if ($loggedIn && !$isSeller && !$isAdmin): ?>
      <a class="nav-link" href="favorites.php">Favourites</a>
    <?php endif; ?>
    <a class="nav-link" href="contactUs.php">Contact</a>
  </div>
  <div class="navbar-actions">
    <?php if ($loggedIn): ?>
      <?php if (!$isSeller && !$isAdmin): ?>
      <div class="icon-btn" onclick="location.href='cart.php'" title="Cart">🛒</div>
      <div class="icon-btn">🔔</div>
      <?php endif; ?>
      <?php
        $dashUrl = $isAdmin ? '../php/AdminArea/adminDashboard.php' : ($isSeller ? '../php/SellerArea/sellerDashboard.php' : 'dashboard.php');
        $dashTitle = $isAdmin ? 'Admin Dashboard' : ($isSeller ? 'Seller Dashboard' : 'My Dashboard');
      ?>
      <div class="avatar-btn" onclick="location.href='<?= $dashUrl ?>'" title="<?= $dashTitle ?>"><?= $initials ?></div>
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
        $isSaved  = in_array((int)$item['listingID'], $savedIDs);
        $isOwner  = !$isAdmin && $loggedIn && (int)($_SESSION['userID'] ?? 0) === (int)$item['sellerID'];
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
        <?php if ($loggedIn && !$isSeller && !$isAdmin && !$isOwner): ?>
        <div style="margin-top:10px;display:flex;gap:6px;" onclick="event.stopPropagation()">
            <button class="btn btn-primary btn-sm cart-btn" data-id="<?= $item['listingID'] ?>"
                    style="font-size:12px;padding:6px 14px;width:100%;"
                    onclick="addToCart(this, <?= $item['listingID'] ?>)">
                🛒 Add to Cart
            </button>
        </div>
        <?php elseif ($isOwner): ?>
        <div style="margin-top:10px;" onclick="event.stopPropagation()">
            <a href="../php/SellerArea/editListing.php?id=<?= $item['listingID'] ?>"
               class="btn btn-secondary btn-sm" style="font-size:12px;padding:6px 14px;width:100%;display:block;text-align:center;">
                ✏ Edit Listing
            </a>
        </div>
        <?php endif; ?>
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
                    <h4>Company</h4>
                    <a href="about.php">About Us</a>
                    <a href="contactUs.php">Contact</a>
                </div>
            </div>
            <div class="footer-bottom"><span>© 2026 Past Times Marketplace</span>
                <div class="footer-badges"><span>SECURE</span><span>PUDO ASSURED</span><span>& PROUDLY SA</span></div>
            </div>
        </footer>

        <script src="../javascript/script.js"></script>
        <script>
            const isLoggedIn = <?= $loggedIn ? 'true' : 'false' ?>;
            const isSeller   = <?= $isSeller ? 'true' : 'false' ?>;


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
    ptShowModal('🤍', 'Save your favourites', 'Create a free account to save items you love and come back to them anytime.');
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

        
function addToCart(btn, listingID) {
    if (!isLoggedIn) { ptShowModal('🛒', 'Sign in to shop', 'Log in or create a free account to add items to your cart and place orders.'); return; }
    if (isSeller) { ptShowModal('🏪', 'Sellers cannot purchase', 'Your account is registered as a seller. Purchasing is reserved for customer accounts.'); return; }
    btn.disabled = true;
    btn.textContent = '...';
    const fd = new FormData();
    fd.append('action','add'); fd.append('listingID', listingID);
    fetch('../php/Cart/cartAction.php', {method:'POST',body:fd})
        .then(r=>r.json())
        .then(data=>{
            if(data.error==='not_logged_in'){location.href='../php/AuthSystem/login.php';return;}
            if(data.action==='added'){
                btn.textContent='✔ In Cart';
                btn.style.cssText='font-size:12px;padding:6px 14px;width:100%;background:#e6faf0;color:#1a5c35;border:1px solid #b2dbd7;border-radius:999px;';
            } else {
                btn.textContent='🛒 Add to Cart'; btn.disabled=false;
            }
        })
        .catch(()=>{btn.textContent='🛒 Add to Cart';btn.disabled=false;});
}
        </script>

<!-- ── PAST TIMES AUTH MODAL ─────────────────────────────────────────────── -->
<div id="ptAuthModal" style="
    display:none;position:fixed;inset:0;z-index:9999;
    background:rgba(0,0,0,.45);backdrop-filter:blur(4px);
    align-items:center;justify-content:center;">
  <div style="
      background:white;border-radius:20px;padding:36px 32px;
      width:360px;max-width:90vw;box-shadow:0 24px 64px rgba(0,0,0,.18);
      text-align:center;position:relative;animation:ptSlideUp .25s ease;">
    <button onclick="ptCloseModal()" style="
        position:absolute;top:14px;right:16px;background:none;border:none;
        font-size:20px;color:var(--text-muted);cursor:pointer;line-height:1;">✕</button>
    <div id="ptModalIcon" style="font-size:44px;margin-bottom:12px;"></div>
    <div id="ptModalTitle" style="font-family:var(--font-display);font-size:22px;font-weight:700;margin-bottom:8px;"></div>
    <div id="ptModalBody" style="font-size:14px;color:var(--text-muted);margin-bottom:24px;line-height:1.6;"></div>
    <div style="display:flex;flex-direction:column;gap:10px;">
      <a id="ptModalLoginBtn" href="../php/AuthSystem/login.php"
         style="display:block;background:var(--primary);color:white;border-radius:999px;
                padding:12px;font-weight:700;font-size:15px;text-decoration:none;">Log In</a>
      <a id="ptModalRegBtn" href="../php/AuthSystem/register.php"
         style="display:block;background:white;color:var(--dark);border:1.5px solid var(--border);
                border-radius:999px;padding:12px;font-weight:600;font-size:14px;text-decoration:none;">Create Free Account</a>
      <button onclick="ptCloseModal()" style="
          background:none;border:none;color:var(--text-muted);font-size:13px;
          cursor:pointer;margin-top:4px;">Maybe later</button>
    </div>
  </div>
</div>

<style>
@keyframes ptSlideUp {
  from { opacity:0; transform:translateY(20px); }
  to   { opacity:1; transform:translateY(0); }
}
</style>
<script>
function ptShowModal(icon, title, body) {
  document.getElementById('ptModalIcon').textContent  = icon;
  document.getElementById('ptModalTitle').textContent = title;
  document.getElementById('ptModalBody').textContent  = body;
  const m = document.getElementById('ptAuthModal');
  m.style.display = 'flex';
  m.addEventListener('click', function handler(e) {
    if (e.target === m) { ptCloseModal(); m.removeEventListener('click', handler); }
  });
}
function ptCloseModal() {
  document.getElementById('ptAuthModal').style.display = 'none';
}
</script>

</body>

</html>