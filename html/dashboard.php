<?php
session_start();

if (!isset($_SESSION['userID'])) {
    header('Location: ../php/AuthSystem/login.php');
    exit;
}


require_once('../php/BackendLogic/dbConn.php');

$userID = $_SESSION['userID'];


$balance = 0.00;

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seller Dashboard – Past Times</title>
    <link rel="stylesheet" href="../css/styles.css">
    <link rel="stylesheet" href="../css/dashboard.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
</head>

<body>
    <div class="dashboard-layout">
        <div class="sidebar">
            <h2>Welcome back, <?= $_SESSION['firstName'] ?></h2>

            <div class="sidebar-header">
                <div class="sidebar-title">Verified Seller</div>
                <div class="sidebar-subtitle">Verified </div>
            </div>

        <div class="sidebar-nav">
            <div class="sidebar-link active">🏪 My Shop</div>
            <div class="sidebar-link" onclick="location.href='order-management.php'">📦 Orders</div>
            <div class="sidebar-link">💰 Earnings</div>
            <div class="sidebar-link" onclick="location.href='messages.php'">💬 Messages</div>
            <div class="sidebar-link" onclick="location.href='settings.php'">⚙️ Settings</div>
        </div>
        <div class="sidebar-footer">
            <a>❓ HELP CENTER</a>
            <a href='../php/AuthSystem/logout.php'>🚪 LOG OUT</a>
        </div>
    </div>
    <div class="dashboard-main">
        <div class="dashboard-top">
            <div>
                <div class="wallet-card">
                    <div class="wallet-label">WALLET BALANCE</div>

                    <!--- <div class="wallet-amount">R 4,850.00</div> -->
                    <div class="wallet-amount">R <?= number_format($balance, 2) ?></div>

                    <div class="escrow-badge">🛡 Escrow Protected</div>
                    <div class="wallet-btns">
                        <button class="wallet-btn wallet-btn-primary">Payout</button>
                        <button class="wallet-btn wallet-btn-secondary">History</button>
                    </div>
                </div>
            </div>
            <div class="stat-cards">
                <div class="stat-card">
                    <div>
                        <div class="stat-label">Followers</div>
                        <div class="stat-value">1,204</div>
                    </div>
                    <div class="stat-icon stat-icon-teal">👥</div>
                </div>
                <div class="stat-card">
                    <div>
                        <div class="stat-label">Active Listings</div>
                        <div class="stat-value">42</div>
                    </div>
                    <div class="stat-icon stat-icon-orange">📋</div>
                </div>
            </div>
        </div>
        <div>
            <div class="section-header">
                <h2 class="section-title" style="margin:0;">Orders Management</h2><a class="view-all" onclick="location.href='order-management.php'">View all →</a>
            </div>
            <div class="order-tabs">
                <button class="order-tab active">New (3)</button>
                <button class="order-tab" onclick="switchTab(this)">To Ship (1)</button>
                <button class="order-tab" onclick="switchTab(this)">Shipped (8)</button>
                <button class="order-tab" onclick="switchTab(this)">Completed (124)</button>
            </div>
            <div class="order-item">
                <span class="badge badge-new">NEW</span>
                <div class="order-thumb">👜</div>
                <div class="order-info">
                    <div class="order-name">Vintage Leather Satchel</div>
                    <div class="order-meta">Order #YG-99201 • Today, 14:20</div>
                    <div class="order-ship">📦 Paxi Point-to-Point &nbsp; 👤 Lerato M.</div>
                </div>
                <div class="order-price">R 1,250</div>
                <div class="order-actions"><button class="btn btn-primary btn-sm">Accept Order</button><button class="btn btn-secondary btn-sm">Message</button></div>
            </div>
            <div class="order-item">
                <span class="badge badge-new">NEW</span>
                <div class="order-thumb">⌚</div>
                <div class="order-info">
                    <div class="order-name">Classic Minimalist Watch</div>
                    <div class="order-meta">Order #YG-99188 • Yesterday, 18:05</div>
                    <div class="order-ship">🔒 PUDO Locker &nbsp; 👤 Johan S.</div>
                </div>
                <div class="order-price">R 850</div>
                <div class="order-actions"><button class="btn btn-primary btn-sm">Accept Order</button><button class="btn btn-secondary btn-sm">Message</button></div>
            </div>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-top:24px;">
            <div class="chart-card">
                <div class="chart-header">
                    <div style="font-weight:700;font-size:15px;">Weekly Performance</div>
                    <div class="chart-label">+12.5% vs last week</div>
                </div>
                <div class="chart-bars">
                    <div class="chart-bar" style="height:40%;background:var(--border);"></div>
                    <div class="chart-bar" style="height:55%;background:var(--border);"></div>
                    <div class="chart-bar" style="height:70%;background:var(--border);"></div>
                    <div class="chart-bar" style="height:95%;background:var(--primary);"></div>
                    <div class="chart-bar" style="height:45%;background:var(--border);"></div>
                    <div class="chart-bar" style="height:65%;background:var(--border);"></div>
                    <div class="chart-bar" style="height:85%;background:var(--primary-light);"></div>
                </div>
                <div class="chart-days">
                    <div class="chart-day">MON</div>
                    <div class="chart-day">TUE</div>
                    <div class="chart-day">WED</div>
                    <div class="chart-day">THU</div>
                    <div class="chart-day">FRI</div>
                    <div class="chart-day">SAT</div>
                    <div class="chart-day">SUN</div>
                </div>
            </div>
            <div class="shop-health">
                <div style="font-weight:700;font-size:15px;margin-bottom:8px;">Shop Health</div>
                <div class="health-bar-wrap">
                    <div class="health-bar" style="width:94%;"></div>
                </div>
                <div class="health-score">94% Excellent</div>
                <div class="health-tip">"Your response time is faster than 80% of sellers this week!"</div>
                <div class="top-seller-banner">
                    <div>
                        <div class="ts-title">⭐ Top Rated Seller</div>
                        <div class="ts-sub">Maintain this status for 5% lower fees.</div>
                    </div>
                    <div style="font-size:28px;">🏆</div>
                </div>
            </div>
        </div>
    </div>
    </div>
    <div class="add-item-fab"><button class="btn btn-primary" onclick="location.href='create-listing.html'">+ Add Item</button></div>
    <script src="../javascript/script.js"></script>
    <script src="../javascript/dashboard.js"></script>
</body>

</html>