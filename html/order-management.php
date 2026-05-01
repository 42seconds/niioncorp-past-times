
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Order Management – Past Times</title>
  <link rel="stylesheet" href="../css/styles.css">
  <link rel="stylesheet" href="../css/dashboard.css">
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
</head>
<body>
<div class="dashboard-layout">
  <div class="sidebar">
    <div class="sidebar-header"><div class="sidebar-title">Seller Studio</div><div class="sidebar-subtitle">Verified Curator</div></div>
    <div class="sidebar-nav">
      <div class="sidebar-link" onclick="location.href='dashboard.html'">🏪 My Shop</div>
      <div class="sidebar-link active">📦 Orders</div>
      <div class="sidebar-link">💰 Earnings</div>
      <div class="sidebar-link" onclick="location.href='messages.html'">💬 Messages</div>
      <div class="sidebar-link" onclick="location.href='settings.html'">⚙️ Settings</div>
    </div>
    <div style="padding:0 20px;margin-top:auto;"><button class="btn btn-primary" style="width:100%;margin-bottom:16px;" onclick="location.href='create-listing.html'">+ Add Item</button></div>
    <div class="sidebar-footer"><a>❓ HELP CENTER</a><a onclick="location.href='../php/AuthSystem/logout.php'">🚪 LOG OUT</a></div>
  </div>
  <div class="dashboard-main">
    <div style="display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:28px;">
      <div><h1 style="font-family:var(--font-display);font-size:30px;font-weight:700;margin-bottom:6px;">Order Management</h1><p style="font-size:14px;color:var(--text-muted);max-width:540px;line-height:1.6;">Monitor your sales and manage shipping via South African escrow.</p></div>
      <div style="display:flex;align-items:center;gap:6px;background:#e0f5f3;border:1px solid #b2dbd7;border-radius:var(--radius-pill);padding:8px 16px;font-size:13px;font-weight:600;color:var(--teal);white-space:nowrap;">🛡 South African Escrow Protected</div>
    </div>
    <div style="background:white;border-radius:var(--radius-lg);border:1px solid var(--border-light);overflow:hidden;margin-bottom:28px;box-shadow:var(--shadow-sm);">
      <div style="padding:24px 28px;border-bottom:1px solid var(--border-light);">
        <div style="display:flex;align-items:center;position:relative;">
          <div style="position:absolute;left:0;right:0;top:20px;height:3px;background:var(--border);z-index:0;"></div>
          <div style="position:absolute;left:0;width:30%;top:20px;height:3px;background:var(--primary);z-index:1;"></div>
          <div style="flex:1;display:flex;flex-direction:column;align-items:center;z-index:2;"><div style="width:40px;height:40px;border-radius:50%;background:var(--primary);color:white;display:flex;align-items:center;justify-content:center;font-size:16px;margin-bottom:8px;border:3px solid white;box-shadow:0 0 0 2px var(--primary);">✓</div><div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.07em;color:var(--primary);">PAID</div></div>
          <div style="flex:1;display:flex;flex-direction:column;align-items:center;z-index:2;"><div style="width:40px;height:40px;border-radius:50%;background:var(--primary);color:white;display:flex;align-items:center;justify-content:center;font-size:16px;margin-bottom:8px;border:3px solid white;box-shadow:0 0 0 2px var(--primary);">🚐</div><div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.07em;color:var(--primary);">SHIPPED</div></div>
          <div style="flex:1;display:flex;flex-direction:column;align-items:center;z-index:2;"><div style="width:40px;height:40px;border-radius:50%;background:var(--border);color:var(--text-muted);display:flex;align-items:center;justify-content:center;font-size:16px;margin-bottom:8px;border:3px solid white;">📬</div><div style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:0.07em;color:var(--text-muted);">DELIVERED</div></div>
          <div style="flex:1;display:flex;flex-direction:column;align-items:center;z-index:2;"><div style="width:40px;height:40px;border-radius:50%;background:var(--border);color:var(--text-muted);display:flex;align-items:center;justify-content:center;font-size:16px;margin-bottom:8px;border:3px solid white;">✅</div><div style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:0.07em;color:var(--text-muted);">COMPLETED</div></div>
        </div>
      </div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:0;">
        <div style="padding:24px 28px;border-right:1px solid var(--border-light);">
          <div style="display:flex;gap:16px;align-items:flex-start;margin-bottom:20px;">
            <div style="width:90px;height:110px;border-radius:var(--radius-sm);background:linear-gradient(135deg,#1a1a1a,#2d2d2d);display:flex;align-items:center;justify-content:center;font-size:48px;flex-shrink:0;">🧥</div>
            <div><div style="font-size:12px;color:var(--text-muted);font-weight:500;margin-bottom:4px;">ORDER #8832-ZA</div><div style="font-family:var(--font-display);font-size:20px;font-weight:700;margin-bottom:6px;line-height:1.2;">Vintage 1990s Distressed Leather Jacket</div><div style="font-size:13px;color:var(--text-muted);margin-bottom:8px;">Size: Medium • Condition: Excellent</div><div style="font-size:20px;font-weight:700;color:var(--primary);">R 2,450.00</div></div>
          </div>
        </div>
        <div style="padding:24px 28px;">
          <div style="font-weight:700;font-size:17px;margin-bottom:10px;">Fulfillment Action</div>
          <div style="font-size:13px;color:var(--text-muted);line-height:1.6;margin-bottom:20px;">Please drop off the item at your nearest PUDO locker within 48 hours.</div>
          <div style="margin-bottom:16px;">
            <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.07em;color:var(--text-muted);margin-bottom:8px;">TRACKING NUMBER</div>
            <input class="form-input tracking-input" placeholder="Enter PUDO or Aramex Waybill" style="margin-bottom:12px;">
            <button class="btn btn-primary btn-lg" style="width:100%;border-radius:var(--radius-sm);" onclick="confirmShipped('8832-ZA')">🚐 Confirm Item Shipped</button>
          </div>
          <div style="text-align:center;font-size:12px;color:var(--teal);">🛡 Funds held securely in escrow</div>
        </div>
      </div>
    </div>
  </div>
</div>
<script src="../javascript/script.js"></script>
<script src="../javascript/orders.js"></script>
</body>
</html>