<?php
session_start();
if (!isset($_SESSION['adminID']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../admin_login.php');
    exit;
}
require_once '../BackendLogic/dbConn.php';

// Admin's own info
$adminID  = (int)$_SESSION['adminID'];
$initials = strtoupper(substr($_SESSION['firstName'], 0, 1) . substr($_SESSION['lastName'], 0, 1));

// Stats
$totalUsers    = $conn->query("SELECT COUNT(*) c FROM tblUser WHERE role != 'admin'")->fetch_assoc()['c'];
$pendingUsers  = $conn->query("SELECT COUNT(*) c FROM tblUser WHERE status='pending' AND role != 'admin'")->fetch_assoc()['c'];
$totalListings = $conn->query("SELECT COUNT(*) c FROM tblListings")->fetch_assoc()['c'];
$pendingList   = $conn->query("SELECT COUNT(*) c FROM tblListings WHERE status='pending'")->fetch_assoc()['c'];
$approvedList  = $conn->query("SELECT COUNT(*) c FROM tblListings WHERE status='approved'")->fetch_assoc()['c'];
$rejectedList  = $conn->query("SELECT COUNT(*) c FROM tblListings WHERE status='rejected'")->fetch_assoc()['c'];

// Recent activity: last 5 pending listings
$recentListings = $conn->query("
    SELECT l.listingID, l.title, l.category, l.price, l.status, l.createdAt, u.username
    FROM tblListings l JOIN tblUser u ON l.sellerID = u.userID
    WHERE l.status = 'pending'
    ORDER BY l.createdAt DESC LIMIT 5
");

// Recent activity: last 5 pending users
$recentUsers = $conn->query("
    SELECT userID, username, firstName, lastName, email, createdAt
    FROM tblUser WHERE status='pending' AND role != 'admin'
    ORDER BY createdAt DESC LIMIT 5
");

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard – Past Times</title>
    <link rel="stylesheet" href="../../css/styles.css">
     <link rel="stylesheet" href="../../css/dashboard.css">

    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        body {
            background: var(--cream);
            font-family: var(--font-body);
        }

        .admin-wrap {
            max-width: 1200px;
            margin: 0 auto;
            padding: 36px 28px;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 32px;
        }

        .page-header h1 {
            font-family: var(--font-display);
            font-size: 30px;
            font-weight: 700;
            margin: 0 0 4px;
        }

        .page-header p {
            font-size: 14px;
            color: var(--text-muted);
            margin: 0;
        }

        /* Stat cards */
        .stat-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
            margin-bottom: 28px;
        }

        .stat-card {
            background: white;
            border-radius: var(--radius-lg);
            border: 1px solid var(--border-light);
            padding: 22px 24px;
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
            margin-bottom: 6px;
        }

        .stat-value {
            font-family: var(--font-display);
            font-size: 36px;
            font-weight: 700;
        }

        .stat-icon {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }

        /* Nav cards */
        .nav-cards {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 28px;
        }

        .nav-card {
            background: white;
            border-radius: var(--radius-lg);
            border: 1.5px solid var(--border-light);
            padding: 28px;
            cursor: pointer;
            transition: .2s;
            box-shadow: var(--shadow-sm);
            text-decoration: none;
            color: inherit;
            display: block;
        }

        .nav-card:hover {
            border-color: var(--primary);
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(0, 0, 0, .1);
        }

        .nav-card-icon {
            font-size: 36px;
            margin-bottom: 14px;
        }

        .nav-card-title {
            font-family: var(--font-display);
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 6px;
        }

        .nav-card-desc {
            font-size: 13px;
            color: var(--text-muted);
            line-height: 1.6;
            margin-bottom: 16px;
        }

        .nav-card-meta {
            font-size: 12px;
            font-weight: 600;
            color: var(--primary);
        }

        /* Admin profile card */
        .admin-card {
            background: white;
            border-radius: var(--radius-lg);
            border: 1px solid var(--border-light);
            padding: 24px;
            margin-bottom: 28px;
            display: flex;
            align-items: center;
            gap: 20px;
            box-shadow: var(--shadow-sm);
        }

        .admin-avatar-lg {
            width: 64px;
            height: 64px;
            border-radius: 50%;
            background: var(--dark);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            font-weight: 700;
            flex-shrink: 0;
        }

        /* Tables */
        .section-card {
            background: white;
            border-radius: var(--radius-lg);
            border: 1px solid var(--border-light);
            padding: 24px;
            margin-bottom: 20px;
            box-shadow: var(--shadow-sm);
        }

        .section-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
        }

        .section-card-title {
            font-family: var(--font-display);
            font-size: 18px;
            font-weight: 700;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            text-align: left;
            padding: 10px 14px;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: .07em;
            color: var(--text-muted);
            border-bottom: 2px solid var(--border);
        }

        td {
            padding: 11px 14px;
            border-bottom: 1px solid var(--border-light);
            font-size: 14px;
            vertical-align: middle;
        }

        tr:last-child td {
            border-bottom: none;
        }

        tr:hover td {
            background: #fafafa;
        }

        .badge-pending {
            background: #fff3e0;
            color: #7a4f00;
            border: 1px solid #ffe0b2;
            padding: 3px 10px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 700;
        }

        .badge-approved {
            background: #e6faf0;
            color: #1a5c35;
            border: 1px solid #b2dbd7;
            padding: 3px 10px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 700;
        }

        .badge-rejected {
            background: #fce8e8;
            color: #8b1a14;
            border: 1px solid #f5b7b7;
            padding: 3px 10px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 700;
        }

        .empty-row {
            text-align: center;
            padding: 32px;
            color: var(--text-muted);
            font-size: 14px;
        }

        @media(max-width:768px) {
            .navbar-nav {
                display: none !important;
            }

            .admin-mobile-menu {
                display: flex !important;
            }
        }

        .admin-mobile-menu {
            display: none;
            align-items: center;
            justify-content: center;
            width: 38px;
            height: 38px;
            border-radius: 8px;
            background: var(--primary);
            color: white;
            font-size: 18px;
            cursor: pointer;
            border: none;
            margin-right: 4px;
        }
    </style>
</head>

<body>

    <!-- NAVBAR -->
    <nav class="navbar">
        <div class="navbar-brand" onclick="location.href='adminDashboard.php'">
            <div class="logo-icon">P</div>
            <span style="font-family:var(--font-display);font-size:16px;font-weight:700;">Past Times Admin</span>
        </div>
        <div class="navbar-nav">
            <a class="nav-link active" href="adminDashboard.php">Dashboard</a>
            <a class="nav-link" href="userManagement.php">Users</a>
            <a class="nav-link" href="listingManagement.php">Listings</a>
            <a class="nav-link" href="messages.php">Messages</a>
        </div>
        <button class="admin-mobile-menu" onclick="document.querySelector('.navbar-nav').style.cssText=document.querySelector('.navbar-nav').style.display==='flex'?'display:none':'display:flex;position:absolute;top:64px;left:0;right:0;background:white;padding:16px;flex-direction:column;gap:8px;border-bottom:1px solid var(--border);z-index:99'">☰</button>
        <div class="navbar-actions">
            <div class="avatar-btn" onclick="location.href='../../html/settings.php'" title="Settings"><?= $initials ?></div>
            <a href="../AuthSystem/logout.php" style="font-size:13px;font-weight:600;color:var(--primary);text-decoration:none;margin-left:12px;">Log Out</a>
        </div>
    </nav>

    <div class="admin-wrap">

        <!-- PAGE HEADER -->
        <div class="page-header">
            <div>
                <h1>Admin Dashboard</h1>
                <p>Welcome back, <?= htmlspecialchars($_SESSION['firstName']) ?>. Here's what needs your attention.</p>
            </div>
            <a href="../../html/home.php"
               style="display:inline-flex;align-items:center;gap:6px;font-size:13px;font-weight:600;
                      color:var(--text-muted);text-decoration:none;padding:8px 18px;
                      border:1.5px solid var(--border);border-radius:999px;background:white;
                      box-shadow:var(--shadow-sm);transition:.15s;"
               onmouseover="this.style.borderColor='var(--primary)';this.style.color='var(--primary)'"
               onmouseout="this.style.borderColor='var(--border)';this.style.color='var(--text-muted)'">
               👁 View Site
            </a>
        </div>

        <!-- ADMIN PROFILE CARD -->
        <div class="admin-card">
            <div class="admin-avatar-lg"><?= $initials ?></div>
            <div style="flex:1;">
                <div style="font-family:var(--font-display);font-size:20px;font-weight:700;"><?= htmlspecialchars($_SESSION['firstName'] . ' ' . $_SESSION['lastName']) ?></div>
                <div style="font-size:13px;color:var(--text-muted);margin-top:2px;"><?= htmlspecialchars($_SESSION['email'] ?? 'admin@pasttimes.co.za') ?></div>
                <div style="margin-top:6px;"><span style="background:var(--dark);color:white;font-size:11px;font-weight:700;padding:3px 10px;border-radius:999px;letter-spacing:.05em;">ADMINISTRATOR</span></div>
            </div>
            <a href="../../html/settings.php" class="btn btn-secondary btn-sm">⚙️ My Settings</a>
        </div>

        <!-- STAT CARDS -->
        <div class="stat-grid">
            <div class="stat-card">
                <div>
                    <div class="stat-label">Total Users</div>
                    <div class="stat-value"><?= $totalUsers ?></div>
                    <div style="font-size:12px;color:#e67e22;margin-top:4px;">⏳ <?= $pendingUsers ?> pending verification</div>
                </div>
                <div class="stat-icon" style="background:#fff3e0;color:#7a4f00;">👥</div>
            </div>
            <div class="stat-card">
                <div>
                    <div class="stat-label">Total Listings</div>
                    <div class="stat-value"><?= $totalListings ?></div>
                    <div style="font-size:12px;color:#e67e22;margin-top:4px;">⏳ <?= $pendingList ?> awaiting approval</div>
                </div>
                <div class="stat-icon" style="background:#e8f0fe;color:#1a3c8b;">📋</div>
            </div>
            <div class="stat-card">
                <div>
                    <div class="stat-label">Listing Decisions</div>
                    <div class="stat-value"><?= $approvedList + $rejectedList ?></div>
                    <div style="font-size:12px;color:var(--text-muted);margin-top:4px;">✔ <?= $approvedList ?> approved &nbsp; ✕ <?= $rejectedList ?> rejected</div>
                </div>
                <div class="stat-icon" style="background:#e6faf0;color:#1a5c35;">✔</div>
            </div>
        </div>

        <!-- NAVIGATION CARDS -->
        <div class="nav-cards">
            <a class="nav-card" href="userManagement.php">
                <div class="nav-card-icon">👥</div>
                <div class="nav-card-title">Manage Users</div>
                <div class="nav-card-desc">Verify new customer registrations, view all accounts, update roles, and remove users from the platform.</div>
                <div class="nav-card-meta">
                    <?= $pendingUsers ?> pending verification →
                </div>
            </a>
            <a class="nav-card" href="listingManagement.php">
                <div class="nav-card-icon">📦</div>
                <div class="nav-card-title">Manage Listings</div>
                <div class="nav-card-desc">Review submitted listings, approve items to go live on the marketplace, or reject listings that don't meet guidelines.</div>
                <div class="nav-card-meta">
                    <?= $pendingList ?> listings awaiting approval →
                </div>
            </a>
        </div>

        <!-- PENDING LISTINGS TABLE -->
        <div class="section-card">
            <div class="section-card-header">
                <div class="section-card-title">⏳ Listings Awaiting Approval</div>
                <a href="listingManagement.php" style="font-size:13px;color:var(--primary);font-weight:600;text-decoration:none;">View All →</a>
            </div>
            <?php if ($recentListings->num_rows > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Seller</th>
                            <th>Category</th>
                            <th>Price</th>
                            <th>Submitted</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($l = $recentListings->fetch_assoc()): ?>
                            <tr>
                                <td style="font-weight:500;"><?= htmlspecialchars($l['title']) ?></td>
                                <td style="color:var(--text-muted);">@<?= htmlspecialchars($l['username']) ?></td>
                                <td><?= htmlspecialchars($l['category']) ?></td>
                                <td style="font-weight:600;">R <?= number_format($l['price'], 2) ?></td>
                                <td style="font-size:13px;color:var(--text-muted);"><?= date('d M Y', strtotime($l['createdAt'])) ?></td>
                                <td>
                                    <div style="display:flex;gap:6px;">
                                        <form method="POST" action="listingActions.php" style="display:inline;">
                                            <input type="hidden" name="action" value="approve">
                                            <input type="hidden" name="listingID" value="<?= $l['listingID'] ?>">
                                            <button style="padding:4px 12px;background:var(--primary);color:white;border:none;border-radius:999px;font-size:12px;font-weight:600;cursor:pointer;">✔ Approve</button>
                                        </form>
                                        <form method="POST" action="listingActions.php" style="display:inline;">
                                            <input type="hidden" name="action" value="reject">
                                            <input type="hidden" name="listingID" value="<?= $l['listingID'] ?>">
                                            <button style="padding:4px 12px;background:#fce8e8;color:#8b1a14;border:1px solid #f5b7b7;border-radius:999px;font-size:12px;font-weight:600;cursor:pointer;">✕ Reject</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="empty-row">✅ No listings pending approval.</div>
            <?php endif; ?>
        </div>

        <!-- PENDING USERS TABLE -->
        <div class="section-card">
            <div class="section-card-header">
                <div class="section-card-title">⏳ Users Pending Verification</div>
                <a href="userManagement.php" style="font-size:13px;color:var(--primary);font-weight:600;text-decoration:none;">View All →</a>
            </div>
            <?php if ($recentUsers->num_rows > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Registered</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($u = $recentUsers->fetch_assoc()): ?>
                            <tr>
                                <td style="font-weight:500;"><?= htmlspecialchars($u['firstName'] . ' ' . $u['lastName']) ?></td>
                                <td style="color:var(--text-muted);">@<?= htmlspecialchars($u['username']) ?></td>
                                <td><?= htmlspecialchars($u['email']) ?></td>
                                <td style="font-size:13px;color:var(--text-muted);"><?= date('d M Y', strtotime($u['createdAt'])) ?></td>
                                <td>
                                    <form method="POST" action="userActions.php" style="display:inline;">
                                        <input type="hidden" name="action" value="verify">
                                        <input type="hidden" name="userID" value="<?= $u['userID'] ?>">
                                        <button style="padding:4px 12px;background:var(--primary);color:white;border:none;border-radius:999px;font-size:12px;font-weight:600;cursor:pointer;">✔ Verify</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="empty-row">✅ No users pending verification.</div>
            <?php endif; ?>
        </div>

    </div><!-- end admin-wrap -->
</body>

</html>