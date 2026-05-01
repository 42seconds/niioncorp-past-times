
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Settings – Past Times</title>
  <link rel="stylesheet" href="../css/styles.css">
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
</head>
<body>
<nav class="navbar">
  <div class="navbar-brand" onclick="location.href='home.php'"><div class="logo-icon">P</div><span style="font-family:var(--font-display);font-size:16px;font-weight:700;">Past Times</span></div>
  <div class="navbar-nav"><a class="nav-link" href="home.php">Explore</a><a class="nav-link" href="favorites.html">Favorites</a><a class="nav-link" href="create-listing.html">Sell</a></div>
  <div class="navbar-actions"><div class="icon-btn">🛒</div><div class="icon-btn">🔔</div><div class="avatar-btn">LM</div></div>
</nav>
<div style="display:grid;grid-template-columns:240px 1fr;min-height:calc(100vh - 64px);">
  <div style="background:white;border-right:1px solid var(--border);padding:32px 0;">
    <div style="padding:0 24px 24px;border-bottom:1px solid var(--border);margin-bottom:16px;"><div style="font-family:var(--font-display);font-size:22px;font-weight:700;">Settings</div></div>
    <div class="settings-nav-link active" onclick="switchSettingsSection(this,'s-profile')">👤 Profile Info</div>
    <div class="settings-nav-link" onclick="switchSettingsSection(this,'s-delivery')">📦 Delivery Addresses</div>
    <div class="settings-nav-link" onclick="switchSettingsSection(this,'s-payment')">💳 Payment &amp; Payouts</div>
    <div class="settings-nav-link" onclick="switchSettingsSection(this,'s-notifications')">🔔 Notifications</div>
    <div style="margin-top:24px;padding:16px 24px;border-top:1px solid var(--border);">
      <div style="display:flex;align-items:center;gap:8px;color:var(--primary);font-size:14px;font-weight:600;cursor:pointer;" onclick="location.href='../php/AuthSystem/logout.php'">🚪 Sign Out</div>
    </div>
</div>
  <div style="padding:32px 40px;background:var(--cream);overflow-y:auto;">
    <div id="s-profile">
      <div class="settings-section">
        <div class="settings-section-title">Profile Info</div>
        <div class="settings-section-sub">Update your public presence on the marketplace.</div>
        <div class="input-row">
          <div class="form-group"><label class="form-label">FULL NAME</label><input class="form-input" value="Lindiwe Mazibuko" title="full name"></div>
          <div class="form-group"><label class="form-label">USERNAME</label><input class="form-input" value="lindim_thrift" title="username"></div>
        </div>
        <div class="form-group"><label class="form-label">BIO</label><textarea class="form-input" rows="4" title="bio">Curating pre-loved vintage from Cape Town. Sustainability is the new luxury. ✨</textarea></div>
      </div>
    </div>
    <div id="s-delivery" style="display:none;">
      <div class="settings-section">
        <div class="settings-section-title">Delivery Addresses</div>
        <div class="settings-section-sub">Manage where your curated finds are shipped.</div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px;">
          <div style="border:2px solid var(--primary);border-radius:var(--radius);padding:18px;background:#fff8f7;">
            <div style="font-size:20px;margin-bottom:8px;">📦</div>
            <div style="font-weight:700;font-size:15px;margin-bottom:4px;">PUDO Locker</div>
            <div style="font-size:13px;color:var(--text-muted);margin-bottom:4px;">The Paddocks Shopping Centre</div>
            <div style="font-size:13px;color:var(--text-muted);margin-bottom:12px;">Milnerton, Cape Town, 7441</div>
            <button class="btn btn-primary btn-sm">Change Locker</button>
          </div>
          <div style="border:1.5px solid var(--border);border-radius:var(--radius);padding:18px;">
            <div style="font-size:20px;margin-bottom:8px;">🏠</div>
            <div style="font-weight:700;font-size:15px;margin-bottom:4px;">Home Office</div>
            <div style="font-size:13px;color:var(--text-muted);margin-bottom:4px;">12 Loop Street, Unit 402</div>
            <div style="font-size:13px;color:var(--text-muted);margin-bottom:12px;">Cape Town CBD, 8001</div>
            <div style="display:flex;gap:12px;"><span style="font-size:13px;color:var(--primary);font-weight:600;cursor:pointer;">Edit</span><span style="font-size:13px;color:var(--text-muted);cursor:pointer;">Remove</span></div>
          </div>
        </div>
        <button class="btn btn-secondary btn-sm">+ Add New Address</button>
      </div>
    </div>
    <div id="s-payment" style="display:none;">
      <div class="settings-section">
        <div class="settings-section-title">Payment &amp; Payouts</div>
        <div class="settings-section-sub">Secure transactions and marketplace earnings.</div>
        <div style="background:linear-gradient(135deg,var(--primary) 0%,var(--primary-dark) 100%);border-radius:var(--radius);padding:24px;margin-bottom:20px;">
          <div style="font-size:11px;letter-spacing:0.1em;text-transform:uppercase;color:rgba(255,255,255,0.7);margin-bottom:8px;">WALLET BALANCE</div>
          <div style="font-family:var(--font-display);font-size:36px;font-weight:700;color:white;margin-bottom:16px;">R 1,450.00</div>
          <div style="display:flex;gap:10px;"><button class="wallet-btn wallet-btn-primary">Withdraw Funds</button><button class="wallet-btn wallet-btn-secondary">Transaction History</button></div>
        </div>
      </div>
    </div>
    <div id="s-notifications" style="display:none;">
      <div class="settings-section">
        <div class="settings-section-title">Notifications</div>
        <div class="settings-section-sub">Choose how you want to be updated.</div>
        <div style="border-bottom:1px solid var(--border-light);padding:18px 0;display:flex;align-items:flex-start;justify-content:space-between;">
          <div><div style="font-weight:600;font-size:14px;margin-bottom:3px;">Sales &amp; Offers</div><div style="font-size:13px;color:var(--text-muted);">Get notified when someone buys your item.</div></div>
          <div class="toggle-switch on" onclick="toggleSwitch(this)"><div class="toggle-knob"></div></div>
        </div>
        <div style="padding:18px 0;display:flex;align-items:flex-start;justify-content:space-between;">
          <div><div style="font-weight:600;font-size:14px;margin-bottom:3px;">New Messages</div><div style="font-size:13px;color:var(--text-muted);">Alerts for when buyers or sellers message you.</div></div>
          <div class="toggle-switch on" onclick="toggleSwitch(this)"><div class="toggle-knob"></div></div>
        </div>
      </div>
    </div>
    <div style="display:flex;justify-content:flex-end;gap:12px;padding-top:8px;">
      <button class="btn btn-secondary">Discard Changes</button>
      <button class="btn btn-primary">Save All Settings</button>
    </div>
  </div>
</div>
<script src="../javascript/script.js"></script>
</body>
</html>