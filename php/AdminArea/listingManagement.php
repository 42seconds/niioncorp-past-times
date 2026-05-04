<?php
session_start();

if (!isset($_SESSION['adminID']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../admin_login.php');
    exit;
}

require_once('../BackendLogic/dbConn.php');

// Fetch all listings
$listings = $conn->query("
    SELECT l.listingID, l.title, l.category, l.price, l.status, l.imagePath, l.createdAt,
           u.username
    FROM tblListings l
    JOIN tblUser u ON l.sellerID = u.userID
    ORDER BY l.status ASC, l.createdAt DESC
");

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Listing Management – Admin</title>

<link rel="stylesheet" href="../../css/styles.css">
    <link rel="stylesheet" href="../css/responsive.css">


<style>
body {
    background: var(--cream);
    font-family: var(--font-body);
}

.admin-wrap {
    max-width: 1100px;
    margin: 0 auto;
    padding: 32px 24px;
}

.admin-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 28px;
}

h1 {
    font-family: var(--font-display);
    font-size: 28px;
    font-weight: 700;
}

/* ✅ SAME BADGE STYLE AS ADMIN */
.badge-pending {
    background: #fff3e0;
    color: #7a4f00;
    border: 1px solid #ffe0b2;
    padding: 3px 10px;
    border-radius: 999px;
    font-size: 12px;
    font-weight: 600;
}

.badge-approved {
    background: #e6faf0;
    color: #1a5c35;
    border: 1px solid #b2dbd7;
    padding: 3px 10px;
    border-radius: 999px;
    font-size: 12px;
    font-weight: 600;
}

.badge-rejected {
    background: #fce8e8;
    color: #8b1a14;
    border: 1px solid #f5b7b7;
    padding: 3px 10px;
    border-radius: 999px;
    font-size: 12px;
    font-weight: 600;
}

/* ✅ SAME TABLE STYLE */
table {
    width: 100%;
    border-collapse: collapse;
    background: white;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 2px 12px rgba(0, 0, 0, .07);
}

th {
    background: var(--dark);
    color: white;
    text-align: left;
    padding: 12px 16px;
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: .06em;
}

td {
    padding: 12px 16px;
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

/* ✅ MATCH BUTTON STYLE */
.action-btns {
    display: flex;
    gap: 8px;
}

.btn-approve {
    padding: 5px 14px;
    background: var(--primary);
    color: white;
    border: none;
    border-radius: 999px;
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
}

.btn-reject {
    padding: 5px 14px;
    background: #fce8e8;
    color: #8b1a14;
    border: 1px solid #f5b7b7;
    border-radius: 999px;
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
}

.empty-state {
    text-align: center;
    padding: 60px 20px;
    color: var(--text-muted);
}
</style>
</head>

<body>

<!-- ✅ SAME NAVBAR -->
<?php include 'adminNavbar.php'; ?>

<div class="admin-wrap">
   <div class="admin-header">
    <h1>Listing Management</h1>
    <a href="../../html/home.php" 
       style="font-size:13px;color:var(--text-muted);text-decoration:none;">
       ← Back to Site
    </a>
</div>

    <table>
        <thead>
            <tr>
                <th>Photo</th>
                <th>Title</th>
                <th>Seller</th>
                <th>Category</th>
                <th>Price</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>

        <tbody>
            <?php if ($listings->num_rows > 0): ?>
                <?php while ($l = $listings->fetch_assoc()): ?>
                    <tr>
                        <td>
                            <!-- ✅ IMAGE THUMBNAIL: imagePath stored as "php/uploads/listings/file" -->
                            <!-- from php/AdminArea/, path to root is ../../ so src=../../php/uploads/... -->
                            <?php if (!empty($l['imagePath'])): ?>
                                <img src="../../<?= htmlspecialchars($l['imagePath']) ?>"
                                     style="width:48px;height:48px;border-radius:6px;object-fit:cover;">
                            <?php else: ?>
                                <div style="width:48px;height:48px;border-radius:6px;background:var(--bg-warm);display:flex;align-items:center;justify-content:center;font-size:20px;">📦</div>
                            <?php endif; ?>
                        </td>
                        <td style="font-weight:500"><?= htmlspecialchars($l['title']) ?></td>
                        <td>@<?= htmlspecialchars($l['username']) ?></td>
                        <td><?= $l['category'] ?></td>
                        <td>R <?= number_format($l['price'],2) ?></td>

                        <td>
                            <span class="badge-<?= $l['status'] ?>">
                                <?= strtoupper($l['status']) ?>
                            </span>
                        </td>

                        <td>
                            <div class="action-btns">
                            <?php if ($l['status'] === 'pending'): ?>
                                <form method="POST" action="listingActions.php">
                                    <input type="hidden" name="action" value="approve">
                                    <input type="hidden" name="listingID" value="<?= $l['listingID'] ?>">
                                    <button class="btn-approve">✔ Approve</button>
                                </form>

                                <form method="POST" action="listingActions.php">
                                    <input type="hidden" name="action" value="reject">
                                    <input type="hidden" name="listingID" value="<?= $l['listingID'] ?>">
                                    <button class="btn-reject">✕ Reject</button>
                                </form>
                            <?php else: ?>
                                —
                            <?php endif; ?>
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
                            <div style="font-size:13px;">New listings will appear here for approval</div>
                        </div>
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
        
    </table>
</div>

</body>
</html>