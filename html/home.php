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
<nav class="navbar">
  <div class="navbar-brand" onclick="location.href='home.php'">
    <div class="logo-icon">P</div>
    <span style="font-family:var(--font-display);font-size:16px;font-weight:700;">Past Times</span>
  </div>
  <div class="navbar-nav">
    <a class="nav-link active" href="home.php">Explore</a>
    <a class="nav-link" href="favorites.html">Favorites</a>
    <a class="nav-link" href="create-listing.html">Sell</a>
    <a class="nav-link" href="about.html">About</a>
    <a class="nav-link" href="contactUs.html">Contact</a>
  </div>
  <div class="navbar-actions">
    <div class="icon-btn" onclick="location.href='favorites.html'">🛒</div>
    <div class="icon-btn">🔔</div>
    <div class="avatar-btn" onclick="location.href='settings.php'">LM</div>
  </div>
</nav>

<div class="hero">
  <div class="hero-inner">
    <div class="hero-overline">South Africa's Premium Pre-loved Boutique</div>
    <h1 class="hero-title">Fashion that tells a <em>story</em></h1>
    <p class="hero-subtitle">Discover curated pre-loved pieces from South Africa's most conscious fashion community. Every purchase is escrow protected.</p>
    <div class="hero-btns">
      <button class="btn btn-primary btn-lg" style="width:auto;">Shop Now</button>
      <button class="btn btn-secondary btn-lg" style="width:auto;border-color:rgba(255,255,255,0.3);color:white;" onclick="location.href='create-listing.html'">Start Selling</button>
    </div>
  </div>
</div>

<div class="trust-strip">
  <div class="trust-item-strip"><span class="trust-icon">🔒</span> Secure Escrow via Ozow</div>
  <div class="trust-item-strip"><span class="trust-icon">📦</span> PUDO Locker Delivery</div>
  <div class="trust-item-strip"><span class="trust-icon">⚡</span> Aramex Express Available</div>
  <div class="trust-item-strip"><span class="trust-icon">🌿</span> Proudly South African</div>
</div>

<div class="explore-section">
  <div class="explore-section-title">Explore Curated Finds</div>
  <div class="explore-section-sub">Handpicked pre-loved treasures from verified sellers across South Africa</div>
  <div class="category-chips">
    <div class="category-chip active" onclick="filterCategory(this)">All Items</div>
    <div class="category-chip" onclick="filterCategory(this)">Clothing</div>
    <div class="category-chip" onclick="filterCategory(this)">Accessories</div>
    <div class="category-chip" onclick="filterCategory(this)">Footwear</div>
    <div class="category-chip" onclick="filterCategory(this)">Outerwear</div>
    <div class="category-chip" onclick="filterCategory(this)">Jewelry</div>
    <div class="category-chip" onclick="filterCategory(this)">Vintage</div>
  </div>
  <div class="product-grid">
    <div class="product-card" onclick="location.href='product-detail.php'">
      <div class="badge-overlay"><span class="badge badge-new">New Arrival</span></div>
      <button class="heart-btn">🤍</button>
      <div class="product-img-placeholder">🧥</div>
      <div class="product-info"><div class="product-name">Classic Wool Trench Coat</div><div class="product-meta">Outerwear • Size M</div><div class="product-price">R 2,450</div></div>
    </div>
    <div class="product-card" onclick="location.href='product-detail.php'">
      <button class="heart-btn">🤍</button>
      <div class="product-img-placeholder">👜</div>
      <div class="product-info"><div class="product-name">Minimalist Leather Bag</div><div class="product-meta">Accessories • Authentic</div><div class="product-price">R 850</div></div>
    </div>
    <div class="product-card" onclick="location.href='product-detail.php'">
      <div class="badge-overlay"><span class="badge badge-price-drop">↓ Price Drop</span></div>
      <button class="heart-btn">❤️</button>
      <div class="product-img-placeholder">⌚</div>
      <div class="product-info"><div class="product-name">Vintage Gold Timepiece</div><div class="product-meta">Jewelry • Original Box</div><div><span class="product-price">R 1,200</span><span class="product-price-orig">R 1,600</span></div></div>
    </div>
    <div class="product-card" onclick="location.href='product-detail.php'">
      <button class="heart-btn">🤍</button>
      <div class="product-img-placeholder">👟</div>
      <div class="product-info"><div class="product-name">Classic White Sneakers</div><div class="product-meta">Footwear • UK 9</div><div class="product-price">R 650</div></div>
    </div>
    <div class="product-card" onclick="location.href='product-detail.php'">
      <button class="heart-btn">🤍</button>
      <div class="product-img-placeholder">👖</div>
      <div class="product-info"><div class="product-name">Indigo Dyed Trousers</div><div class="product-meta">Pants • Size 32</div><div class="product-price">R 450</div></div>
    </div>
    <div class="product-card" onclick="location.href='product-detail.php'">
      <div class="badge-overlay"><span class="badge badge-price-drop">↓ Price Drop</span></div>
      <button class="heart-btn">❤️</button>
      <div class="product-img-placeholder">🧣</div>
      <div class="product-info"><div class="product-name">Vintage Wool Trench</div><div class="product-meta">Size M • Cape Town</div><div><span class="product-price">R 1,450</span><span class="product-price-orig">R 1,800</span></div></div>
    </div>
    <div class="product-card" onclick="location.href='product-detail.php'">
      <button class="heart-btn">🤍</button>
      <div class="product-img-placeholder">👜</div>
      <div class="product-info"><div class="product-name">Artisan Leather Tote</div><div class="product-meta">Authentic • Johannesburg</div><div class="product-price">R 2,200</div></div>
    </div>
    <div class="product-card" onclick="location.href='product-detail.php'">
      <div class="badge-overlay"><span class="badge badge-price-drop">↓ Price Drop</span></div>
      <button class="heart-btn">🤍</button>
      <div class="product-img-placeholder">👟</div>
      <div class="product-info"><div class="product-name">Retro Sneakers</div><div class="product-meta">UK 8 • Durban</div><div><span class="product-price">R 950</span><span class="product-price-orig">R 1,100</span></div></div>
    </div>
  </div>
</div>

<footer class="footer">
  <div class="footer-grid">
    <div><div class="footer-brand-name">Past Times</div><div class="footer-tagline">© 2026 Past Times. South Africa's Premium Pre-loved Boutique.</div></div>
    <div class="footer-col"><h4>Marketplace</h4><a>Secure Escrow via Ozow</a><a>Paxi Points</a><a>PUDO Lockers</a></div>
    <div class="footer-col"><h4>Company</h4><a href="about.html">About Us</a><a href="contactUs.html">Contact</a><a>Terms of Service</a></div>
  </div>
  <div class="footer-bottom"><span>© 2026 Past Times Marketplace</span><div class="footer-badges"><span>🔒 SECURE</span><span>🚐 PUDO</span><span>🌿 PROUDLY SA</span></div></div>
</footer>

<script src="../javascript/script.js"></script>
<script src="../javascript/ui.js"></script>
</body>
</html>