
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Product Detail – Past Times</title>
  <link rel="stylesheet" href="../css/styles.css">
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
</head>
<body>
<nav class="navbar">
  <div class="navbar-brand" onclick="location.href='home.php'"><div class="logo-icon">P</div><span style="font-family:var(--font-display);font-size:16px;font-weight:700;">Past Times</span></div>
  <div class="navbar-nav">
    <a class="nav-link" href="home.php">Explore</a>
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
<div class="product-detail-layout">
  <div>
    <div class="product-gallery-main">🧥</div>
    <div class="gallery-thumbs">
      <div class="gallery-thumb active">🧥</div>
      <div class="gallery-thumb">2</div>
      <div class="gallery-thumb">3</div>
      <div class="gallery-thumb">4</div>
    </div>
  </div>
  <div style="position:relative;">
    <div class="escrow-note">🛡 ESCROW PROTECTED</div>
    <div class="product-detail-brand">ZARA PREMIUM</div>
    <h1 class="product-detail-title">Organic Silk Blend Midi Dress</h1>
    <div class="product-detail-price">R 1,450.00</div>
    <div class="condition-box">
      <div class="condition-row"><span style="font-size:13px;font-weight:600;">Condition</span><span class="badge badge-teal">NEW WITH TAGS</span></div>
      <div class="condition-desc">Purchased for an event and never worn. Still in original packaging with all tags attached.</div>
    </div>
    <div class="seller-box" onclick="location.href='seller-profile.php'">
      <div class="seller-avatar">LC</div>
      <div style="flex:1;"><div class="seller-name">@Lindi_Curates</div><div class="seller-stats">⭐⭐⭐⭐⭐ (128 reviews)</div><div class="seller-response">98% RESPONSE RATE • ACTIVE TODAY</div></div>
      <div>💬</div>
    </div>
    <div class="shipping-section">
      <h4>Shipping Options</h4>
      <div class="shipping-grid">
        <div class="shipping-option active"><div>📦</div><div class="shipping-name">Aramex Express</div><div class="shipping-days">2-3 Business Days</div></div>
        <div class="shipping-option"><div>🚐</div><div class="shipping-name">Paxi Point</div><div class="shipping-days">5-7 Business Days</div></div>
      </div>
    </div>
    <div class="action-btns">
      <button class="btn btn-primary btn-lg">Buy Now</button>
      <div class="action-row">
        <button class="btn btn-secondary">📦 Add to Bundle</button>
        <button class="btn btn-teal" onclick="location.href='messages.php'">💬 Message</button>
      </div>
    </div>
  </div>
</div>
<footer class="footer" style="margin-top:40px;">
  <div class="footer-bottom">
    <span class="footer-brand-name">Past Times</span>
    <div style="display:flex;gap:24px;font-size:13px;color:var(--text-muted);"><span>Secure Escrow via Ozow</span><span>Paxi Points</span><span>PUDO Lockers</span><span>Terms of Service</span></div>
    <div class="footer-badges">🔒 🚐 🌿 PROUDLY SOUTH AFRICAN</div>
  </div>
</footer>
<script src="../javascript/script.js"></script>
</body>
</html>