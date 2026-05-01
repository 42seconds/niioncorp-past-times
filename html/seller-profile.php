
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Seller Profile – Past Times</title>
  <link rel="stylesheet" href="../css/styles.css">
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
</head>
<body>
<nav class="navbar">
  <div class="navbar-brand" onclick="location.href='home.html'"><div class="logo-icon">P</div><span style="font-family:var(--font-display);font-size:16px;font-weight:700;">Past Times</span></div>
  <div class="navbar-nav"><a class="nav-link" href="home.html">Explore</a><a class="nav-link" href="favorites.html">Favorites</a><a class="nav-link" href="create-listing.html">Sell</a></div>
  <div class="navbar-actions"><div class="icon-btn">🛒</div><div class="icon-btn">🔔</div><div class="avatar-btn" onclick="location.href='settings.html'">LM</div></div>
</nav>
<div class="profile-layout">
  <div class="profile-header-card">
    <div class="profile-avatar">VC<div class="verified-badge">✓</div></div>
    <div>
      <h1 class="profile-name">@vintage_curator</h1>
      <div class="profile-rating"><span class="stars">★★★★★</span> 4.9 (124 reviews)</div>
      <div class="profile-location">📍 Cape Town</div>
      <div class="profile-follow-stats"><div class="follow-stat">1.2k <span>Followers</span></div><div class="follow-stat">452 <span>Following</span></div></div>
    </div>
    <div class="profile-actions"><button class="btn btn-primary">Follow</button><button class="btn btn-secondary" onclick="location.href='messages.html'">✉️ Message</button></div>
    <div class="sustainability-card">
      <div class="sus-title">🌿 Sustainability Impact</div>
      <div class="sus-stats"><div><div class="sus-stat-val">25</div><div class="sus-stat-label">ITEMS RE-HOMED</div></div><div><div class="sus-stat-val">112kg</div><div class="sus-stat-label">CO2 SAVED</div></div></div>
      <div class="sus-bio">Curating rare vintage gems and premium pre-loved pieces from the heart of the Mother City.</div>
    </div>
  </div>
  <div class="spotlight-banner">
    <div class="secure-escrow-chip">🛡 Secure Escrow — Protected via Ozow</div>
    <div><div class="spotlight-label">SELLER SPOTLIGHT</div><div class="spotlight-title">The Autumn Edit 2024</div></div>
  </div>
  <div style="margin-bottom:16px;">
    <h2 style="font-family:var(--font-display);font-size:22px;font-weight:700;margin-bottom:2px;">Active Listings</h2>
    <div class="listings-tabs"><div class="listings-tab active">All Items</div><div class="listings-tab" onclick="switchListTab(this)">Clothing</div><div class="listings-tab" onclick="switchListTab(this)">Accessories</div></div>
  </div>
  <div class="fav-grid">
    <div class="product-card" onclick="location.href='product-detail.html'"><button class="heart-btn">🤍</button><div class="product-img-placeholder">🧥</div><div class="product-info"><div class="product-name">Classic Wool Trench Coat</div><div class="product-meta">Outerwear • Size M</div><div class="product-price">R 2,450</div></div></div>
    <div class="product-card" onclick="location.href='product-detail.html'"><button class="heart-btn">🤍</button><div class="product-img-placeholder">👜</div><div class="product-info"><div class="product-name">Minimalist Leather Bag</div><div class="product-meta">Accessories</div><div class="product-price">R 850</div></div></div>
    <div class="eco-card"><div class="eco-icon">🌿</div><div class="eco-title">Better for the Planet</div><div class="eco-desc">By buying from @vintage_curator, you've helped save over 4,000 liters of water.</div><div class="eco-link">LEARN ABOUT OUR IMPACT</div></div>
    <div class="product-card" onclick="location.href='product-detail.html'"><button class="heart-btn">🤍</button><div class="product-img-placeholder">👟</div><div class="product-info"><div class="product-name">Classic White Sneakers</div><div class="product-meta">Footwear • UK 9</div><div class="product-price">R 650</div></div></div>
  </div>
</div>
<script src="../javascript/script.js"></script>
</body>
</html>