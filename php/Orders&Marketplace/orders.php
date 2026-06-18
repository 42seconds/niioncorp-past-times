<?php

session_start();
if (!isset($_SESSION['userID'])) {
    header('Location: ../../html/home.php');
    exit;
}
require_once '../BackendLogic/dbConn.php';

$buyerID  = (int)$_SESSION['userID'];
$initials = strtoupper(substr($_SESSION['firstName'], 0, 1) . substr($_SESSION['lastName'], 0, 1));

// Active filter
$filter = $_GET['status'] ?? 'all';
$allowed = ['all', 'pending', 'paid', 'shipped', 'delivered', 'cancelled'];
if (!in_array($filter, $allowed)) $filter = 'all';

$where = $filter !== 'all' ? "AND o.status = '$filter'" : '';

// Get orders
$orders = $conn->query("
    SELECT o.orderID, o.status, o.totalPrice, o.deliveryMethod, o.createdAt, o.updatedAt,
           l.title, l.imagePath, l.category, l.listingID,
           u.username AS sellerName
    FROM tblOrders o
    JOIN tblListings l ON o.listingID = l.listingID
    JOIN tblUser u     ON o.sellerID  = u.userID
    WHERE o.buyerID = $buyerID $where
    ORDER BY o.createdAt DESC
");

// Get order statistics for dashboard
$statsQuery = $conn->query("
    SELECT 
        COUNT(*) as total_orders,
        SUM(totalPrice) as total_spent,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_count,
        SUM(CASE WHEN status = 'pending' THEN totalPrice ELSE 0 END) as pending_total,
        SUM(CASE WHEN status = 'paid' THEN 1 ELSE 0 END) as paid_count,
        SUM(CASE WHEN status = 'paid' THEN totalPrice ELSE 0 END) as paid_total,
        SUM(CASE WHEN status = 'shipped' THEN 1 ELSE 0 END) as shipped_count,
        SUM(CASE WHEN status = 'shipped' THEN totalPrice ELSE 0 END) as shipped_total,
        SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END) as delivered_count,
        SUM(CASE WHEN status = 'delivered' THEN totalPrice ELSE 0 END) as delivered_total,
        SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_count,
        SUM(CASE WHEN status = 'cancelled' THEN totalPrice ELSE 0 END) as cancelled_total
    FROM tblOrders
    WHERE buyerID = $buyerID
");

$stats = $statsQuery->fetch_assoc();

$conn->close();

$statusColour = [
    'pending'   => ['bg' => '#fff3e0', 'c' => '#7a4f00', 'b' => '#ffe0b2'],
    'paid'      => ['bg' => '#e8f0fe', 'c' => '#1a3c8b', 'b' => '#c5d5fb'],
    'shipped'   => ['bg' => '#e6faf0', 'c' => '#1a5c35', 'b' => '#b2dbd7'],
    'delivered' => ['bg' => '#e6faf0', 'c' => '#1a5c35', 'b' => '#b2dbd7'],
    'cancelled' => ['bg' => '#fce8e8', 'c' => '#8b1a14', 'b' => '#f5b7b7'],
    'refunded'  => ['bg' => '#fce8e8', 'c' => '#8b1a14', 'b' => '#f5b7b7'],
];

// Format currency helper
function formatCurrency($amount)
{
    return 'R ' . number_format($amount, 2);
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders – Past Times</title>
    <link rel="stylesheet" href="../../css/styles.css">
    <link rel="stylesheet" href="../css/responsive.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        body {
            background: var(--cream);
            font-family: var(--font-body);
        }

        .orders-wrap {
            max-width: 1000px;
            margin: 36px auto;
            padding: 0 24px;
        }

        h1 {
            font-family: var(--font-display);
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 4px;
        }

        .filter-tabs {
            display: flex;
            gap: 8px;
            margin-bottom: 24px;
            flex-wrap: wrap;
        }

        .filter-tab {
            padding: 7px 16px;
            border-radius: 999px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            border: 1.5px solid var(--border);
            color: var(--text-muted);
            text-decoration: none;
            transition: all 0.2s;
        }

        .filter-tab.active,
        .filter-tab:hover {
            background: var(--dark);
            color: white;
            border-color: var(--dark);
        }

        .order-card {
            background: white;
            border-radius: var(--radius-lg);
            border: 1px solid var(--border-light);
            padding: 20px 24px;
            margin-bottom: 14px;
            box-shadow: var(--shadow-sm);
            display: grid;
            grid-template-columns: 56px 1fr auto;
            gap: 16px;
            align-items: center;
            cursor: pointer;
            transition: .15s;
        }

        .order-card:hover {
            border-color: var(--primary);
            transform: translateY(-1px);
        }

        .order-thumb {
            width: 56px;
            height: 56px;
            border-radius: 8px;
            background: var(--bg-warm);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            overflow: hidden;
            flex-shrink: 0;
        }

        .order-thumb img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .order-id {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .06em;
            color: var(--text-muted);
            margin-bottom: 3px;
        }

        .order-title {
            font-weight: 600;
            font-size: 15px;
            margin-bottom: 3px;
        }

        .order-meta {
            font-size: 13px;
            color: var(--text-muted);
        }

        .status-pill {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .05em;
            white-space: nowrap;
        }

        .order-price {
            font-family: var(--font-display);
            font-size: 18px;
            font-weight: 700;
            color: var(--primary);
            text-align: right;
        }

        .empty-state {
            text-align: center;
            padding: 64px 20px;
            color: var(--text-muted);
        }

        .empty-state .icon {
            font-size: 52px;
            margin-bottom: 16px;
        }

        /* ── Stats Grid ── */
        .stats-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin: 24px 0 32px;
            max-width: 600px;
        }

        @media(max-width:600px) {
            .stats-grid {
                grid-template-columns: 1fr;
                max-width: 100%;
            }
        }

        .stat-card {
            background: white;
            border-radius: var(--radius-lg);
            border: 1px solid var(--border-light);
            padding: 24px 28px;
            box-shadow: var(--shadow-sm);
            text-align: center;
            transition: all 0.2s;
        }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .stat-card .stat-number {
            font-family: var(--font-display);
            font-size: 32px;
            font-weight: 700;
            line-height: 1.2;
        }

        .stat-card .stat-number.primary {
            color: var(--primary);
        }

        .stat-card .stat-number.green {
            color: #1a5c35;
        }

        .stat-card .stat-label {
            font-size: 13px;
            font-weight: 600;
            color: var(--text-muted);
            margin-top: 6px;
            text-transform: uppercase;
            letter-spacing: .06em;
        }

        .stat-card .stat-sub {
            font-size: 12px;
            color: var(--text-muted);
            margin-top: 2px;
            font-weight: 400;
        }

        .welcome-message {
            font-family: var(--font-display);
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 4px;
        }

        .welcome-sub {
            font-size: 14px;
            color: var(--text-muted);
        }
    </style>
</head>

<body>
    <nav class="navbar">
        <div class="navbar-brand" onclick="location.href='../../html/home.php'">
            <div class="logo-icon">P</div>
            <span style="font-family:var(--font-display);font-size:16px;font-weight:700;">Past Times</span>
        </div>

        <div class="navbar-nav">
            <a class="nav-link" href="../../html/home.php">Explore</a>
            <a class="nav-link" href="../../html/favorites.php">Favourites</a>
            <a class="nav-link active">My Orders</a>
        </div>
        <div class="navbar-actions">
            <div class="avatar-btn" onclick="location.href='../../html/dashboard.php'"><?= $initials ?></div>
        </div>
    </nav>

    <div class="orders-wrap">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:20px;flex-wrap:wrap;gap:12px;">
            <div>
                <div class="welcome-message">📦 My Orders</div>
                <div class="welcome-sub">Track and manage your purchases.</div>
            </div>
            <a href="../../html/home.php" class="btn btn-secondary btn-sm">+ Shop More</a>
        </div>

        <!-- 📊 STATISTICS DASHBOARD - Only Total Orders & Total Spent -->
        <div class="stats-grid">
            <div class="stat-card">

                <div class="stat-label">Total Orders</div>
                <div class="stat-number primary"><?= $stats['total_orders'] ?? 0 ?></div>

            </div>
            <div class="stat-card">
                <div class="stat-label">Total Spent</div>
                <br>
                <div class="stat-number green"><?= formatCurrency($stats['total_spent'] ?? 0) ?></div>

            </div>
        </div>

        <!-- Filter Tabs -->
        <div class="filter-tabs">
            <?php foreach ($allowed as $s): ?>
                <a href="orders.php?status=<?= $s ?>"
                    class="filter-tab <?= $filter === $s ? 'active' : '' ?>"><?= ucfirst($s) ?></a>
            <?php endforeach; ?>
        </div>

        <!-- Order List -->
        <?php if ($orders->num_rows > 0):
            while ($o = $orders->fetch_assoc()):
                $sc = $statusColour[$o['status']] ?? $statusColour['pending'];
        ?>
                <div class="order-card" onclick="location.href='order-details.php?id=<?= $o['orderID'] ?>'">
                    <div class="order-thumb">
                        <?php if (!empty($o['imagePath'])): ?>
                            <img src="../../<?= htmlspecialchars($o['imagePath']) ?>" alt="">
                            <?php else: ?>📦<?php endif; ?>
                    </div>
                    <div>
                        <div class="order-id">Order #<?= $o['orderID'] ?></div>
                        <div class="order-title"><?= htmlspecialchars($o['title']) ?></div>
                        <div class="order-meta">
                            Seller: @<?= htmlspecialchars($o['sellerName']) ?> &nbsp;·&nbsp;
                            <?= htmlspecialchars($o['deliveryMethod']) ?> &nbsp;·&nbsp;
                            <?= date('d M Y', strtotime($o['createdAt'])) ?>
                        </div>
                    </div>
                    <div style="text-align:right;">
                        <div class="order-price">R <?= number_format($o['totalPrice'], 2) ?></div>
                        <div style="margin-top:6px;">
                            <span class="status-pill" style="background:<?= $sc['bg'] ?>;color:<?= $sc['c'] ?>;border:1px solid <?= $sc['b'] ?>;">
                                <?= strtoupper($o['status']) ?>
                            </span>
                        </div>
                    </div>
                </div>
            <?php endwhile;
        else: ?>
            <div class="empty-state">
                <div class="icon">🛍</div>
                <div style="font-weight:600;font-size:18px;margin-bottom:8px;">No orders yet</div>
                <div style="font-size:14px;margin-bottom:20px;">Browse the marketplace and find something you love.</div>
                <a href="../../html/home.php" class="btn btn-primary">Start Shopping</a>
            </div>
        <?php endif; ?>
    </div>
    <script src="../../javascript/script.js"></script>
</body>

</html>