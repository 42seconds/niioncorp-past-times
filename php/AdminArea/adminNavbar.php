<?php
// adminNavbar.php — include at top of every admin page after session check
$currentPage = basename($_SERVER['PHP_SELF']);
$initials    = strtoupper(substr($_SESSION['firstName'],0,1).substr($_SESSION['lastName'],0,1));
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
  </div>
  <div class="navbar-actions">
    <div class="avatar-btn" onclick="location.href='../../html/settings.php'" title="Settings"><?= $initials ?></div>
    <a href="../AuthSystem/logout.php" style="font-size:13px;font-weight:600;color:var(--primary);text-decoration:none;margin-left:12px;">Log Out</a>
  </div>
</nav>