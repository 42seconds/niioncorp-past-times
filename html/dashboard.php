<?php
session_start();
if (!isset($_SESSION['userID'])) {
    header('Location: ../php/AuthSystem/login.php');
    exit;
}
require_once '../php/BackendLogic/dbConn.php';

$userID   = (int)$_SESSION['userID'];
$initials = strtoupper(substr($_SESSION['firstName'], 0, 1) . substr($_SESSION['lastName'], 0, 1));

// ── Order stats ───────────────────────────────────────────────────────────────
$orderStats = ['total' => 0, 'pending' => 0, 'paid' => 0, 'shipped' => 0, 'delivered' => 0, 'cancelled' => 0];
$res = $conn->query("SELECT status, COUNT(*) c FROM tblOrders WHERE buyerID=$userID GROUP BY status");
while ($r = $res->fetch_assoc()) {
    $orderStats[$r['status']] = (int)$r['c'];
    $orderStats['total'] += (int)$r['c'];
}

// ── Recent orders (last 5) ────────────────────────────────────────────────────
$stmt = $conn->prepare("
    SELECT o.orderID, o.status, o.totalPrice, o.deliveryMethod, o.createdAt, o.updatedAt,
           l.title, l.imagePath, l.category, l.listingID,
           u.username AS sellerName
    FROM tblOrders o
    JOIN tblListings l ON o.listingID = l.listingID
    JOIN tblUser u     ON o.sellerID  = u.userID
    WHERE o.buyerID = ?
    ORDER BY o.createdAt DESC
    LIMIT 5
");
$stmt->bind_param("i", $userID);
$stmt->execute();
$recentOrders = $stmt->get_result();
$stmt->close();

// ── Favourites count (placeholder until tblFavourites exists) ─────────────────
$favCount = 0;

$conn->close();

$statusColour = [
    'pending'   => ['bg' => '#fff3e0', 'c' => '#7a4f00', 'b' => '#ffe0b2'],
    'paid'      => ['bg' => '#e8f0fe', 'c' => '#1a3c8b', 'b' => '#c5d5fb'],
    'shipped'   => ['bg' => '#e6faf0', 'c' => '#1a5c35', 'b' => '#b2dbd7'],
    'delivered' => ['bg' => '#e6faf0', 'c' => '#1a5c35', 'b' => '#b2dbd7'],
    'cancelled' => ['bg' => '#fce8e8', 'c' => '#8b1a14', 'b' => '#f5b7b7'],
    'refunded'  => ['bg' => '#fce8e8', 'c' => '#8b1a14', 'b' => '#f5b7b7'],
];

// Timeline steps shared
$timelineSteps = [
    ['key' => 'pending',   'label' => 'Order Placed'],
    ['key' => 'paid',      'label' => 'Payment'],
    ['key' => 'shipped',   'label' => 'Shipped'],
    ['key' => 'delivered', 'label' => 'Delivered'],
];
$statusOrder = ['pending', 'paid', 'shipped', 'delivered', 'cancelled', 'refunded'];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Dashboard – Past Times</title>
    <link rel="stylesheet" href="../css/styles.css">
    <link rel="stylesheet" href="../css/dashboard.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        /* ── Status badges ── */
        .sb {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: .04em;
        }

        /* ── Section card ── */
        .sec {
            background: white;
            border-radius: var(--radius-lg);
            border: 1px solid var(--border-light);
            padding: 24px;
            box-shadow: var(--shadow-sm);
            margin-bottom: 20px;
        }

        .sec-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
        }

        .sec-title {
            font-family: var(--font-display);
            font-size: 18px;
            font-weight: 700;
            margin: 0;
        }

        /* ── Order card ── */
        .order-row {
            display: grid;
            grid-template-columns: 52px 1fr auto;
            gap: 14px;
            align-items: center;
            padding: 14px 0;
            border-bottom: 1px solid var(--border-light);
            cursor: pointer;
            transition: .15s;
        }

        .order-row:last-child {
            border-bottom: none;
        }

        .order-row:hover {
            background: #fafafa;
            border-radius: 8px;
            padding-left: 8px;
        }

        .order-thumb {
            width: 52px;
            height: 52px;
            border-radius: 8px;
            background: var(--bg-warm);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            overflow: hidden;
            flex-shrink: 0;
        }

        .order-thumb img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        /* ── Mini timeline inside each order row ── */
        .mini-timeline {
            display: flex;
            align-items: center;
            gap: 0;
            margin-top: 6px;
        }

        .mini-step {
            display: flex;
            align-items: center;
        }

        .mini-dot {
            width: 18px;
            height: 18px;
            border-radius: 50%;
            border: 2px solid var(--border);
            background: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 9px;
            flex-shrink: 0;
        }

        .mini-dot.done {
            background: var(--primary);
            border-color: var(--primary);
            color: white;
        }

        .mini-dot.active {
            background: var(--dark);
            border-color: var(--dark);
            color: white;
        }

        .mini-line {
            width: 24px;
            height: 2px;
            background: var(--border);
        }

        .mini-line.done {
            background: var(--primary);
        }

        .mini-label {
            font-size: 10px;
            color: var(--text-muted);
            margin-top: 3px;
            white-space: nowrap;
        }

        /* ── Empty state ── */
        .empty {
            text-align: center;
            padding: 48px 20px;
            color: var(--text-muted);
        }

        .empty .icon {
            font-size: 44px;
            margin-bottom: 12px;
        }

        /* ── Stat cards ── */
        .stat-card {
            background: white;
            border-radius: var(--radius-lg);
            border: 1px solid var(--border-light);
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: var(--shadow-sm);
        }

        .stat-label {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .07em;
            color: var(--text-muted);
            margin-bottom: 4px;
        }

        .stat-value {
            font-family: var(--font-display);
            font-size: 30px;
            font-weight: 700;
        }

        .stat-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
        }
    </style>
</head>

<body>
    <!-- HAMBURGER -->
    <button class="sidebar-toggle" id="sidebarToggle" onclick="toggleSidebar()" aria-label="Menu">☰</button>
    <div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>
    <div class="dashboard-layout">

        <!-- SIDEBAR -->
        <div class="sidebar">
            <div class="sidebar-header">
                <div style="width:48px;height:48px;border-radius:50%;background:var(--primary);color:white;display:flex;align-items:center;justify-content:center;font-size:18px;font-weight:700;margin-bottom:10px;"><?= $initials ?></div>
                <div class="sidebar-title"><?= htmlspecialchars($_SESSION['firstName'] . ' ' . $_SESSION['lastName']) ?></div>
                <div class="sidebar-subtitle"><?= htmlspecialchars($_SESSION['email'] ?? '') ?></div>
            </div>
            <div class="sidebar-nav">
                <div class="sidebar-link active">🏠︎ My Dashboard</div>
                <div class="sidebar-link" onclick="location.href='../php/Orders&Marketplace/orders.php'"> My Orders
                    <?php if ($orderStats['shipped'] > 0): ?>
                        <span style="margin-left:auto;background:var(--primary);color:white;font-size:10px;padding:1px 7px;border-radius:999px;"><?= $orderStats['shipped'] ?></span>
                    <?php endif; ?>
                </div>
                <div class="sidebar-link" onclick="location.href='messages.php'"> Messages</div>
                <div class="sidebar-link" onclick="location.href='favorites.php'"> Favourites</div>
                <div class="sidebar-link" onclick="location.href='settings.php'"> Settings</div>
            </div>
            <div class="sidebar-footer">
                <a href="home.php">🏠︎ Back to Shop</a>
                <a href="../php/AuthSystem/logout.php">⚠ Log Out</a>
            </div>
        </div>

        <!-- MAIN -->
        <div class="dashboard-main">

            <div style="margin-bottom:24px;">
                <h1 style="font-family:var(--font-display);font-size:26px;font-weight:700;margin:0 0 4px;">Welcome back, <?= htmlspecialchars($_SESSION['firstName']) ?> 👋</h1>
                <div style="font-size:14px;color:var(--text-muted);">Here's a summary of your shopping activity.</div>
            </div>

            <!-- STAT CARDS -->
            <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:24px;">
                <div class="stat-card">
                    <div>
                        <div class="stat-label">Total Orders</div>
                        <div class="stat-value"><?= $orderStats['total'] ?></div>
                    </div>

                </div>
                <div class="stat-card">
                    <div>
                        <div class="stat-label">In Transit</div>
                        <div class="stat-value"><?= $orderStats['shipped'] ?></div>
                    </div>

                </div>
                <div class="stat-card">
                    <div>
                        <div class="stat-label">Delivered</div>
                        <div class="stat-value"><?= $orderStats['delivered'] ?></div>
                    </div>

                </div>
                <div class="stat-card">
                    <div>
                        <div class="stat-label">Pending</div>
                        <div class="stat-value"><?= $orderStats['pending'] ?></div>
                    </div>

                </div>
            </div>

            <!-- QUICK ACTIONS -->
            <div style="display:flex;gap:10px;margin-bottom:24px;flex-wrap:wrap;">
                <a href="home.php" class="btn btn-primary">Shop Now</a>
                <a href="../php/Orders&Marketplace/orders.php" class="btn btn-secondary"> All Orders</a>

                <a href="favorites.php" class="btn btn-secondary"> Favourites</a>
            </div>

            <!-- ACTIVE ORDERS -->
            <div class="sec">
                <div class="sec-header">
                    <div class="sec-title">My Recent Orders</div>
                    <a href="../php/Orders&Marketplace/orders.php" style="font-size:13px;color:var(--primary);font-weight:600;text-decoration:none;">View All →</a>
                </div>

                <?php if ($recentOrders->num_rows > 0):
                    while ($o = $recentOrders->fetch_assoc()):
                        $sc = $statusColour[$o['status']] ?? $statusColour['pending'];
                        $currentIdx = array_search($o['status'], $statusOrder);
                        $isCancelled = in_array($o['status'], ['cancelled', 'refunded']);
                ?>
                        <div class="order-row" onclick="location.href='../php/Orders&Marketplace/order-details.php?id=<?= $o['orderID'] ?>'">

                            <div class="order-thumb">
                                <?php if (!empty($o['imagePath'])): ?>
                                    <img src="../<?= htmlspecialchars($o['imagePath']) ?>" alt="">
                                    <?php else: ?>📦<?php endif; ?>
                            </div>

                            <div>
                                <div style="font-weight:600;font-size:14px;margin-bottom:2px;"><?= htmlspecialchars($o['title']) ?></div>
                                <div style="font-size:12px;color:var(--text-muted);margin-bottom:6px;">
                                    Order #<?= $o['orderID'] ?> &nbsp;·&nbsp;
                                    @<?= htmlspecialchars($o['sellerName']) ?> &nbsp;·&nbsp;
                                    <?= date('d M Y', strtotime($o['createdAt'])) ?>
                                </div>

                                <?php if (!$isCancelled): ?>
                                    <!-- TIMELINE -->
                                    <div class="mini-timeline">
                                        <?php foreach ($timelineSteps as $i => $step):
                                            $stepIdx = array_search($step['key'], $statusOrder);
                                            $done    = $stepIdx < $currentIdx;
                                            $active  = $stepIdx === $currentIdx;
                                        ?>
                                            <?php if ($i > 0): ?>
                                                <div class="mini-line <?= $done ? 'done' : '' ?>"></div>
                                            <?php endif; ?>
                                            <div style="display:flex;flex-direction:column;align-items:center;">
                                                <div class="mini-dot <?= $done ? 'done' : ($active ? 'active' : '') ?>">
                                                    <?= $done ? '✓' : ($active ? '●' : '') ?>
                                                </div>
                                                <div class="mini-label" style="<?= ($done || $active) ? 'color:var(--dark);font-weight:600;' : '' ?>"><?= $step['label'] ?></div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <span class="sb" style="background:<?= $sc['bg'] ?>;color:<?= $sc['c'] ?>;border:1px solid <?= $sc['b'] ?>;"><?= strtoupper($o['status']) ?></span>
                                <?php endif; ?>
                            </div>

                            <div style="text-align:right;flex-shrink:0;">
                                <div style="font-family:var(--font-display);font-size:16px;font-weight:700;color:var(--primary);">R <?= number_format($o['totalPrice'], 2) ?></div>
                                <div style="margin-top:4px;">
                                    <span class="sb" style="background:<?= $sc['bg'] ?>;color:<?= $sc['c'] ?>;border:1px solid <?= $sc['b'] ?>;">
                                        <?= strtoupper($o['status']) ?>
                                    </span>
                                </div>
                                <?php if ($o['status'] === 'shipped'): ?>
                                    <div style="font-size:11px;color:var(--primary);font-weight:600;margin-top:4px;">⚠ Confirm receipt?</div>
                                <?php endif; ?>
                            </div>

                        </div>
                    <?php endwhile;
                else: ?>
                    <div class="empty">
                        <div class="icon">🛍</div>
                        <div style="font-weight:600;font-size:16px;margin-bottom:8px;">No orders yet</div>
                        <div style="font-size:13px;margin-bottom:16px;">Find something you love in the marketplace.</div>
                        <a href="home.php" class="btn btn-primary">Browse Listings</a>
                    </div>
                <?php endif; ?>
            </div>

            <!-- ESCROW  -->
            <div style="background:linear-gradient(135deg,var(--dark) 0%,#2d2d2d 100%);border-radius:var(--radius-lg);padding:24px;color:white;display:flex;justify-content:space-between;align-items:center;gap:20px;">
                <div>
                    <div style="font-family:var(--font-display);font-size:18px;font-weight:700;margin-bottom:6px;">🛡 Your money is always protected</div>
                    <div style="font-size:13px;opacity:.8;line-height:1.6;">Every purchase on Past Times is held in secure Ozow Escrow. Funds are only released to the seller once you confirm you've received your item in good condition.</div>
                </div>
                <a href="../php/Orders&Marketplace/orders.php" class="btn" style="background:white;color:var(--dark);font-weight:700;white-space:nowrap;flex-shrink:0;">View All Orders</a>
            </div>

        </div>
    </div>
    <script src="../javascript/script.js"></script>
    <script>
        function toggleSidebar() {
            document.querySelector(".sidebar").classList.toggle("open");
            document.getElementById("sidebarOverlay").classList.toggle("active");
        }

        function closeSidebar() {
            document.querySelector(".sidebar").classList.remove("open");
            document.getElementById("sidebarOverlay").classList.remove("active");
        }
        document.querySelectorAll(".sidebar-link").forEach(function(link) {
            link.addEventListener("click", closeSidebar);
        });
    </script>
</body>

</html>