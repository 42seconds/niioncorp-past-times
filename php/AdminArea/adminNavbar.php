<?php
// adminNavbar.php — include at top of every admin page after session check

$currentPage = basename($_SERVER['PHP_SELF']);
$initials    = strtoupper(substr($_SESSION['firstName'],0,1).substr($_SESSION['lastName'],0,1));

// Unread message count — use a dedicated connection so we never clash with parent page

$adminID = (int)($_SESSION['adminID'] ?? 0);
$_navConn = new mysqli('localhost','root','','PastTimes',3306);
$unreadMsgs = 0;

if (!$_navConn->connect_error) {
    $_navConn->set_charset('utf8mb4');
    $unreadMsgs = (int)$_navConn->query("SELECT COUNT(*) c FROM tblMessages WHERE receiverID=$adminID AND isRead=0")->fetch_assoc()['c'];
    $_navConn->close();
}

?>
<nav class="navbar">
  <div class="navbar-brand" onclick="location.href='adminDashboard.php'">
    <div class="logo-icon">P</div>
    <span style="font-family:var(--font-display);font-size:16px;font-weight:700;">Past Times Admin</span>
  </div>
  <div class="navbar-nav">
    <a class="nav-link <?= $currentPage==='adminDashboard.php'  ? 'active' : '' ?>" href="adminDashboard.php">Dashboard</a>
    <a class="nav-link <?= $currentPage==='userManagement.php'  ? 'active' : '' ?>" href="userManagement.php">Users</a>
    <a class="nav-link <?= $currentPage==='listingManagement.php'? 'active' : '' ?>" href="listingManagement.php">Listings</a>
    <a class="nav-link <?= $currentPage==='messages.php'        ? 'active' : '' ?>" href="messages.php" style="display:flex;align-items:center;gap:5px;">
      Messages<?php if ($unreadMsgs>0): ?><span style="background:var(--primary);color:white;border-radius:999px;font-size:10px;padding:1px 6px;"><?= $unreadMsgs ?></span><?php endif; ?>
    </a>
  </div>
  <div class="navbar-actions">
    <div class="avatar-btn" onclick="location.href='../../html/settings.php'" title="Settings"><?= $initials ?></div>
    <a href="../AuthSystem/logout.php" style="font-size:13px;font-weight:600;color:var(--primary);text-decoration:none;margin-left:12px;">Log Out</a>
  </div>
</nav>