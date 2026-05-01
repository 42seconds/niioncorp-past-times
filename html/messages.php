
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Messages – Past Times</title>
  <link rel="stylesheet" href="../css/styles.css">
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
</head>
<body>
<nav class="navbar">
  <div class="navbar-brand" onclick="location.href='home.html'"><div class="logo-icon">P</div><span style="font-family:var(--font-display);font-size:16px;font-weight:700;">Past Times</span></div>
  <div class="navbar-nav">
    <a class="nav-link" href="home.html">Shop</a>
    <a class="nav-link" href="create-listing.html">Sell</a>
    <a class="nav-link active">Messages</a>
  </div>
  <div class="navbar-actions">
    <div class="icon-btn" onclick="location.href='favorites.html'">🛒</div>
    <div class="icon-btn">🔔</div>
    <div class="avatar-btn" onclick="location.href='settings.html'">LM</div>
  </div>
</nav>
<div class="messages-layout">
  <div class="inbox-panel">
    <div class="inbox-header">
      <div class="inbox-title">Inbox</div>
      <div class="inbox-search"><span>🔍</span><input placeholder="Search buyers &amp; sellers"></div>
    </div>
    <div class="convo-list">
      <div class="convo-item active">
        <div class="convo-avatar">SM</div>
        <div class="convo-info">
          <div style="display:flex;justify-content:space-between;"><div class="convo-name">Sarah M.</div><div class="convo-time">14:02</div></div>
          <div class="convo-preview">Is the vintage blazer still available?</div>
          <div class="convo-thumb-ref">🧥 Vintage Wool Blazer...</div>
        </div>
        <div class="unread-dot"></div>
      </div>
      <div class="convo-item">
        <div class="convo-avatar">DK</div>
        <div class="convo-info">
          <div style="display:flex;justify-content:space-between;"><div class="convo-name">David K.</div><div class="convo-time">Yesterday</div></div>
          <div class="convo-preview">Perfect, I'll track the PUDO locker.</div>
        </div>
      </div>
    </div>
  </div>
  <div class="chat-panel">
    <div class="chat-header">
      <div class="convo-avatar" style="width:38px;height:38px;font-size:13px;">SM</div>
      <div><div class="chat-contact-name">Sarah M.</div><div class="chat-online">ONLINE NOW</div></div>
      <div class="item-preview-chip">
        <div class="item-chip-img">🧥</div>
        <div><div class="item-chip-name">Vintage Wool Blazer</div><div class="item-chip-price">R550.00</div></div>
        <button class="btn btn-primary btn-sm">BUY NOW</button>
      </div>
    </div>
    <div class="escrow-banner">
      <div class="escrow-banner-title">🛡 Stay Protected with Escrow</div>
      <div class="escrow-banner-text">Always pay through the app. Our Ozow escrow system keeps your money safe until you receive your item.</div>
    </div>
    <div class="chat-messages">
      <div class="chat-date">TODAY</div>
      <div class="msg-row"><div class="msg-avatar">SM</div><div><div class="msg-bubble received">Hi there! I saw you liked the blazer. Are you still interested?</div><div class="msg-time">13:45</div></div></div>
      <div class="msg-row sent"><div class="msg-avatar">Me</div><div><div class="msg-bubble sent">Hey! Yes, I love it. Does it have any wear on the elbows?</div><div class="msg-time" style="text-align:left;">13:58</div></div></div>
      <div class="msg-row"><div class="msg-avatar">SM</div><div><div class="msg-bubble received">It's in great condition! Here's a close up of the fabric.</div><div class="msg-img">🧥</div><div class="msg-time">14:01</div></div></div>
    </div>
    <div>
      <div class="chat-input-area">
        <span class="attach-btn">➕</span>
        <span class="attach-btn">🖼</span>
        <input class="chat-input" placeholder="Type a message..." id="chat-input-field">
        <button class="send-btn" onclick="sendMsg()">➤</button>
      </div>
      <div class="chat-safety-note">Never share your phone number or email. Keep conversations here.</div>
    </div>
  </div>
</div>
<script src="../javascript/script.js"></script>
<script src="../javascript/ui.js"></script>
</body>
</html>