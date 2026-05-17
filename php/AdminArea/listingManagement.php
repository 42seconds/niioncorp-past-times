<?php
session_start();

if (!isset($_SESSION['adminID']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../admin_login.php');
    exit;
}

require_once('../BackendLogic/dbConn.php');

$initials = strtoupper(substr($_SESSION['firstName'],0,1).substr($_SESSION['lastName'],0,1));

// Filter
$filter  = $_GET['filter'] ?? 'all';
$valid   = ['all','pending','approved','rejected'];
if (!in_array($filter, $valid)) $filter = 'all';

$where   = $filter === 'all' ? '' : "WHERE l.status = '$filter'";
$listings = $conn->query("
    SELECT l.listingID, l.title, l.description, l.category, l.condition_, l.price,
           l.status, l.imagePath, l.delivery, l.createdAt, u.username, u.userID AS sellerID
    FROM tblListings l
    JOIN tblUser u ON l.sellerID = u.userID
    $where
    ORDER BY FIELD(l.status,'pending','approved','rejected'), l.createdAt DESC
");

$counts = [];
foreach (['all','pending','approved','rejected'] as $s) {
    $w = $s === 'all' ? '' : "WHERE status='$s'";
    $counts[$s] = $conn->query("SELECT COUNT(*) c FROM tblListings $w")->fetch_assoc()['c'];
}

$msg = $_GET['msg'] ?? '';
$err = $_GET['err'] ?? '';

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Listing Management – Admin</title>
<link rel="stylesheet" href="../../css/styles.css">
    <link rel="stylesheet" href="../../css/responsive.css">


<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
<style>
body { background: var(--cream); font-family: var(--font-body); }
.admin-wrap { max-width: 1200px; margin: 0 auto; padding: 32px 24px; }
.page-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:24px; }
h1 { font-family: var(--font-display); font-size: 28px; font-weight: 700; margin:0; }

.filter-tabs { display:flex; gap:8px; margin-bottom:20px; flex-wrap:wrap; }
.ftab { padding:7px 18px; border-radius:999px; font-size:13px; font-weight:600; border:1.5px solid var(--border); color:var(--text-muted); text-decoration:none; }
.ftab.active { background:var(--dark); color:white; border-color:var(--dark); }
.ftab-count { background:rgba(255,255,255,.25); border-radius:999px; padding:0 6px; font-size:11px; margin-left:4px; }

.badge { padding:3px 10px; border-radius:999px; font-size:11px; font-weight:700; }
.badge-pending  { background:#fff3e0; color:#7a4f00; border:1px solid #ffe0b2; }
.badge-approved { background:#e6faf0; color:#1a5c35; border:1px solid #b2dbd7; }
.badge-rejected { background:#fce8e8; color:#8b1a14; border:1px solid #f5b7b7; }

.card { background:white; border-radius:var(--radius-lg); border:1px solid var(--border-light); overflow:hidden; box-shadow:var(--shadow-sm); }

table { width:100%; border-collapse:collapse; }
th { text-align:left; padding:12px 16px; font-size:11px; text-transform:uppercase; letter-spacing:.07em; color:var(--text-muted); border-bottom:2px solid var(--border); white-space:nowrap; }
td { padding:12px 16px; border-bottom:1px solid var(--border-light); font-size:14px; vertical-align:middle; }
tr:last-child td { border-bottom:none; }
tr:hover td { background:#fafafa; }

.action-btns { display:flex; gap:6px; align-items:center; flex-wrap:wrap; }
.btn-sm2 { padding:4px 12px; border-radius:999px; font-size:12px; font-weight:600; cursor:pointer; border:none; }
.btn-approve { background:var(--primary); color:white; }
.btn-reject  { background:#fce8e8; color:#8b1a14; border:1px solid #f5b7b7; }
.btn-delete  { background:#2d2d2d; color:white; }
.btn-msg     { background:#e8f0fe; color:#1a3c8b; border:1px solid #c5d5fb; }

.alert-success { background:#e6faf0; border:1px solid #b2dbd7; color:#1a5c35; border-radius:8px; padding:12px 16px; margin-bottom:16px; font-size:14px; }
.alert-error   { background:#fce8e8; border:1px solid #f5b7b7; color:#8b1a14; border-radius:8px; padding:12px 16px; margin-bottom:16px; font-size:14px; }

.empty-state { text-align:center; padding:60px 20px; color:var(--text-muted); }
.thumb { width:44px; height:44px; border-radius:6px; object-fit:cover; }
.thumb-ph { width:44px; height:44px; border-radius:6px; background:var(--bg-warm); display:flex; align-items:center; justify-content:center; font-size:20px; }
</style>
</head>
<body>

<?php include 'adminNavbar.php'; ?>

<div class="admin-wrap">
    <div class="page-header">
        <h1>📋 Listing Management</h1>
          </div>

    <?php if ($msg): ?><div class="alert-success">✔ <?= htmlspecialchars($msg) ?></div><?php endif; ?>
    <?php if ($err): ?><div class="alert-error">⚠ <?= htmlspecialchars($err) ?></div><?php endif; ?>

    <!-- Filter tabs -->
    <div class="filter-tabs">
        <?php foreach (['all'=>'All','pending'=>'Pending','approved'=>'Approved','rejected'=>'Rejected'] as $k=>$label): ?>
        <a href="?filter=<?= $k ?>" class="ftab <?= $filter===$k?'active':'' ?>">
            <?= $label ?><span class="ftab-count"><?= $counts[$k] ?></span>
        </a>
        <?php endforeach; ?>
    </div>

    <div class="card">
        <table>
            <thead>
                <tr>
                    <th>Photo</th>
                    <th>Title / Details</th>
                    <th>Seller</th>
                    <th>Price</th>
                    <th>Status</th>
                    <th>Submitted</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($listings->num_rows > 0): ?>
                    <?php while ($l = $listings->fetch_assoc()): ?>
                    <tr>
                        <td>
                            <?php if (!empty($l['imagePath'])): ?>
                                <img src="../../<?= htmlspecialchars($l['imagePath']) ?>" class="thumb" alt="">
                            <?php else: ?>
                                <div class="thumb-ph">📦</div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div style="font-weight:600;"><?= htmlspecialchars($l['title']) ?></div>
                            <div style="font-size:12px;color:var(--text-muted);"><?= htmlspecialchars($l['category']) ?> · <?= htmlspecialchars($l['condition_']) ?></div>
                            <?php if ($l['description']): ?>
                            <div style="font-size:12px;color:var(--text-muted);margin-top:2px;"><?= htmlspecialchars(substr($l['description'],0,60)) ?>…</div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div style="font-weight:500;">@<?= htmlspecialchars($l['username']) ?></div>
                            <a href="messages.php?to=<?= $l['sellerID'] ?>"
                               class="btn-sm2 btn-msg" style="display:inline-block;margin-top:4px;text-decoration:none;">
                               💬 Message
                            </a>
                        </td>
                        <td style="font-weight:700;font-family:var(--font-display);">R&nbsp;<?= number_format($l['price'],2) ?></td>
                        <td><span class="badge badge-<?= $l['status'] ?>"><?= strtoupper($l['status']) ?></span></td>
                        <td style="font-size:12px;color:var(--text-muted);"><?= date('d M Y', strtotime($l['createdAt'])) ?></td>
                        <td>
                            <div class="action-btns">
                                <?php if ($l['status'] === 'pending'): ?>
                                <form method="POST" action="listingActions.php">
                                    <input type="hidden" name="action" value="approve">
                                    <input type="hidden" name="listingID" value="<?= $l['listingID'] ?>">
                                    <button class="btn-sm2 btn-approve">✔ Approve</button>
                                </form>
                                <form method="POST" action="listingActions.php">
                                    <input type="hidden" name="action" value="reject">
                                    <input type="hidden" name="listingID" value="<?= $l['listingID'] ?>">
                                    <button class="btn-sm2 btn-reject">✕ Reject</button>
                                </form>
                                <?php endif; ?>
                                <form method="POST" action="listingActions.php"
                                      onsubmit="return confirm('Permanently delete this listing?')">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="listingID" value="<?= $l['listingID'] ?>">
                                    <button class="btn-sm2 btn-delete">🗑 Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                <tr>
                    <td colspan="7">
                        <div class="empty-state">
                            <div style="font-size:40px;">📦</div>
                            <div style="font-weight:600;margin-top:10px;">No listings found</div>
                        </div>
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>