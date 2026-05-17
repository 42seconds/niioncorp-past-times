
<?php
session_start();
if (!isset($_SESSION['userID']) || $_SESSION['role'] !== 'seller') {
    header('Location: ../AuthSystem/login.php'); exit;
}
require_once '../BackendLogic/dbConn.php';
$sellerID = (int)$_SESSION['userID'];
$initials = strtoupper(substr($_SESSION['firstName'],0,1).substr($_SESSION['lastName'],0,1));

$filter = $_GET['filter'] ?? 'all';
$valid = ['all','pending','approved','rejected']; if(!in_array($filter,$valid)) $filter='all';
$where = $filter==='all' ? '' : "AND status='$filter'";

$stmt = $conn->prepare("SELECT listingID,title,category,condition_,price,status,imagePath,createdAt FROM tblListings WHERE sellerID=? $where ORDER BY createdAt DESC");
$stmt->bind_param("i",$sellerID); $stmt->execute();
$listings = $stmt->get_result()->fetch_all(MYSQLI_ASSOC); $stmt->close();

$counts = [];
foreach(['all','pending','approved','rejected'] as $s) {
    $w = $s==='all'?'':"AND status='$s'";
    $r = $conn->query("SELECT COUNT(*) c FROM tblListings WHERE sellerID=$sellerID $w");
    $counts[$s] = $r->fetch_assoc()['c'];
}
$conn->close();

$msg = $_GET['msg'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Listings – Past Times</title>
<link rel="stylesheet" href="../../css/styles.css">
<link rel="stylesheet" href="../../css/responsive.css">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
<style>
body{background:var(--cream);font-family:var(--font-body);}
.wrap{max-width:1100px;margin:0 auto;padding:32px 24px;}
h1{font-family:var(--font-display);font-size:28px;font-weight:700;margin:0 0 24px;}
.filter-tabs{display:flex;gap:8px;margin-bottom:20px;flex-wrap:wrap;}
.ftab{padding:7px 18px;border-radius:999px;font-size:13px;font-weight:600;border:1.5px solid var(--border);color:var(--text-muted);text-decoration:none;}
.ftab.active{background:var(--dark);color:white;border-color:var(--dark);}
.badge{padding:3px 10px;border-radius:999px;font-size:11px;font-weight:700;}
.badge-pending{background:#fff3e0;color:#7a4f00;border:1px solid #ffe0b2;}
.badge-approved{background:#e6faf0;color:#1a5c35;border:1px solid #b2dbd7;}
.badge-rejected{background:#fce8e8;color:#8b1a14;border:1px solid #f5b7b7;}
.card{background:white;border-radius:var(--radius-lg);border:1px solid var(--border-light);overflow:hidden;box-shadow:var(--shadow-sm);}
table{width:100%;border-collapse:collapse;}
th{text-align:left;padding:12px 16px;font-size:11px;text-transform:uppercase;letter-spacing:.07em;color:var(--text-muted);border-bottom:2px solid var(--border);}
td{padding:12px 16px;border-bottom:1px solid var(--border-light);font-size:14px;vertical-align:middle;}
tr:last-child td{border-bottom:none;}
tr:hover td{background:#fafafa;}
.thumb{width:44px;height:44px;border-radius:6px;object-fit:cover;}
.thumb-ph{width:44px;height:44px;border-radius:6px;background:var(--bg-warm);display:flex;align-items:center;justify-content:center;font-size:20px;}
.btn-edit{padding:5px 14px;background:var(--primary);color:white;border:none;border-radius:999px;font-size:12px;font-weight:600;cursor:pointer;text-decoration:none;display:inline-block;}
.alert-success{background:#e6faf0;border:1px solid #b2dbd7;color:#1a5c35;border-radius:8px;padding:12px 16px;margin-bottom:16px;font-size:14px;}
</style>
</head>
<body>
<nav class="navbar">
  <div class="navbar-brand" onclick="location.href='sellerDashboard.php'">
    <div class="logo-icon">P</div>
    <span style="font-family:var(--font-display);font-size:16px;font-weight:700;">Past Times</span>
  </div>
  <div class="navbar-nav">
    <a class="nav-link" href="sellerDashboard.php">Dashboard</a>
    <a class="nav-link active" href="myListings.php">My Listings</a>
    <a class="nav-link" href="create-listing.php">+ New</a>
    <a class="nav-link" href="messages.php">Messages</a>
  </div>
  <div class="navbar-actions">
    <div class="avatar-btn" onclick="location.href='../../html/settings.php'"><?= $initials ?></div>
    <a href="../AuthSystem/logout.php" style="font-size:13px;font-weight:600;color:var(--primary);text-decoration:none;margin-left:12px;">Log Out</a>
  </div>
</nav>

<div class="wrap">
  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:24px;">
    <h1>My Listings</h1>
    <a href="create-listing.php" class="btn btn-primary btn-sm">+ New Listing</a>
  </div>

  <?php if ($msg==='deleted'): ?><div class="alert-success">✔ Listing deleted.</div><?php endif; ?>

  <div class="filter-tabs">
    <?php foreach(['all'=>'All','pending'=>'Pending','approved'=>'Approved','rejected'=>'Rejected'] as $k=>$label): ?>
    <a href="?filter=<?= $k ?>" class="ftab <?= $filter===$k?'active':'' ?>"><?= $label ?> <span style="opacity:.7;">(<?= $counts[$k] ?>)</span></a>
    <?php endforeach; ?>
  </div>

  <div class="card">
    <table>
      <thead>
        <tr><th>Photo</th><th>Title</th><th>Category</th><th>Price</th><th>Status</th><th>Listed</th><th>Actions</th></tr>
      </thead>
      <tbody>
        <?php if (!empty($listings)): foreach ($listings as $l): ?>
        <tr>
          <td><?php if(!empty($l['imagePath'])): ?><img src="../../<?= htmlspecialchars($l['imagePath']) ?>" class="thumb" alt=""><?php else: ?><div class="thumb-ph">📦</div><?php endif; ?></td>
          <td style="font-weight:600;"><?= htmlspecialchars($l['title']) ?></td>
          <td style="color:var(--text-muted);"><?= htmlspecialchars($l['category']) ?></td>
          <td style="font-weight:700;">R&nbsp;<?= number_format($l['price'],2) ?></td>
          <td><span class="badge badge-<?= $l['status'] ?>"><?= strtoupper($l['status']) ?></span></td>
          <td style="font-size:12px;color:var(--text-muted);"><?= date('d M Y', strtotime($l['createdAt'])) ?></td>
          <td>
            <div style="display:flex;gap:6px;">
              <a href="editListing.php?id=<?= $l['listingID'] ?>" class="btn-edit">✏ Edit</a>
              <?php if($l['status']==='approved'): ?>
              <a href="../../html/product-detail.php?id=<?= $l['listingID'] ?>" style="padding:5px 14px;background:#e6faf0;color:#1a5c35;border:1px solid #b2dbd7;border-radius:999px;font-size:12px;font-weight:600;text-decoration:none;">View</a>
              <?php endif; ?>
            </div>
          </td>
        </tr>
        <?php endforeach; else: ?>
        <tr><td colspan="7" style="text-align:center;padding:48px;color:var(--text-muted);">No listings found. <a href="create-listing.php" style="color:var(--primary);font-weight:600;">Create one →</a></td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
</body>
</html>