<?php
session_start();
if (!isset($_SESSION['userID']) && !isset($_SESSION['adminID'])) {
    header('Location: ../php/AuthSystem/login.php');
    exit;
}
require_once '../php/BackendLogic/dbConn.php';

// Determine who is logged in
$isAdmin    = isset($_SESSION['adminID']) && $_SESSION['role'] === 'admin';
$isSeller   = !$isAdmin && isset($_SESSION['role']) && $_SESSION['role'] === 'seller';
$isCustomer = isset($_SESSION['role']) && $_SESSION['role'] === 'customer';

$userID   = $isAdmin ? $_SESSION['adminID'] : $_SESSION['userID'];
$initials = strtoupper(substr($_SESSION['firstName'], 0, 1) . substr($_SESSION['lastName'], 0, 1));

// Fetch current user data from DB
$stmt = $conn->prepare("SELECT * FROM tblUser WHERE userID = ? LIMIT 1");
$stmt->bind_param("i", $userID);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();


// Handle save
$saved = false;
$saveError = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['saveProfile'])) {
    $newFirst = htmlspecialchars(trim($_POST['firstName'] ?? ''));
    $newLast  = htmlspecialchars(trim($_POST['lastName']  ?? ''));
    $newEmail = htmlspecialchars(trim($_POST['email']     ?? ''));

    $upd = $conn->prepare("UPDATE tblUser SET firstName=?, lastName=?, email=? WHERE userID=?");
    $upd->bind_param("sssi", $newFirst, $newLast, $newEmail, $userID);
    if ($upd->execute()) {
        $_SESSION['firstName'] = $newFirst;
        $_SESSION['lastName']  = $newLast;
        $_SESSION['email']     = $newEmail;
        $user['firstName']     = $newFirst;
        $user['lastName']      = $newLast;
        $user['email']         = $newEmail;
        $initials = strtoupper(substr($newFirst, 0, 1) . substr($newLast, 0, 1));
        $saved = true;
    } else {
        $saveError = $conn->error;
    }
    $upd->close();
}
$conn->close();

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings – Past Times</title>
    <link rel="stylesheet" href="../css/styles.css">
        <link rel="stylesheet" href="../css/responsive.css">

    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        .settings-shell {
            display: grid;
            grid-template-columns: 260px 1fr;
            min-height: calc(100vh - 64px);
        }

        .settings-side {
            background: white;
            border-right: 1px solid var(--border);
            padding: 0;
        }

        .settings-side-header {
            padding: 28px 24px 20px;
            border-bottom: 1px solid var(--border);
        }

        .side-avatar {
            width: 52px;
            height: 52px;
            border-radius: 50%;
            background: var(--primary);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 12px;
        }

        .side-name {
            font-family: var(--font-display);
            font-size: 17px;
            font-weight: 700;
        }

        .side-role {
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .07em;
            margin-top: 3px;
            padding: 2px 8px;
            border-radius: 999px;
            display: inline-block;
        }

        .role-admin {
            background: #fce8e8;
            color: #8b1a14;
        }

        .role-seller {
            background: #e6faf0;
            color: #1a5c35;
        }

        .role-customer {
            background: #e8f0fe;
            color: #1a3c8b;
        }

        .settings-nav-link {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 13px 24px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            color: var(--text-muted);
            border-left: 3px solid transparent;
            transition: .15s;
        }

        .settings-nav-link:hover {
            background: var(--bg-warm);
            color: var(--dark);
        }

        .settings-nav-link.active {
            background: var(--bg-warm);
            color: var(--primary);
            font-weight: 600;
            border-left-color: var(--primary);
        }

        .settings-nav-section {
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .1em;
            color: var(--text-muted);
            padding: 16px 24px 6px;
        }

        .settings-main {
            padding: 36px 48px;
            background: var(--cream);
            overflow-y: auto;
        }

        .settings-section {
            background: white;
            border-radius: var(--radius-lg);
            border: 1px solid var(--border-light);
            padding: 28px;
            margin-bottom: 20px;
            box-shadow: var(--shadow-sm);
        }

        .settings-section-title {
            font-family: var(--font-display);
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 4px;
        }

        .settings-section-sub {
            font-size: 13px;
            color: var(--text-muted);
            margin-bottom: 20px;
        }

        .section-panel {
            display: none;
        }

        .section-panel.active {
            display: block;
        }

        .alert-success {
            background: #e6faf0;
            border: 1px solid #b2dbd7;
            color: #1a5c35;
            border-radius: 8px;
            padding: 12px 16px;
            margin-bottom: 16px;
            font-size: 14px;
        }

        .alert-error {
            background: #fce8e8;
            border: 1px solid #f5b7b7;
            color: #8b1a14;
            border-radius: 8px;
            padding: 12px 16px;
            margin-bottom: 16px;
            font-size: 14px;
        }

        .danger-zone {
            border: 1.5px solid #f5b7b7;
            border-radius: var(--radius);
            padding: 20px;
            margin-top: 12px;
        }

        .signout-link {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 14px 24px;
            color: #8b1a14;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            border-top: 1px solid var(--border);
            margin-top: auto;
            text-decoration: none;
        }

        .signout-link:hover {
            background: #fce8e8;
        }
    </style>
</head>

<body>

    <!-- NAVBAR — role-aware -->
    <nav class="navbar">

        <div class="navbar-brand" onclick="location.href='<?= $isAdmin ? '../php/AdminArea/adminDashboard.php' : ($isSeller ? '../php/SellerArea/sellerDashboard.php' : 'home.php') ?>'">
            <div class="logo-icon">P</div>
            <span style="font-family:var(--font-display);font-size:16px;font-weight:700;">Past Times
                <?= $isAdmin ? ' Admin' : '' ?>
            </span>
        </div>

                <div class="navbar-nav">
                <?php if ($isAdmin): ?>
                    <a class="nav-link" href="../php/AdminArea/adminDashboard.php">Dashboard</a>
                    <a class="nav-link" href="../php/AdminArea/userManagement.php">Users</a>
                    <a class="nav-link" href="../php/AdminArea/listingManagement.php">Listings</a>

                <?php elseif ($isSeller): ?>
                    <a class="nav-link" href="../php/SellerArea/sellerDashboard.php">My Dashboard</a>
                    <a class="nav-link" href="../php/SellerArea/create-listing.php">Sell</a>

                <?php else: ?>
                    
                    <a class="nav-link" href="../html/dashboard.php">My Dashboard</a>
                    <a class="nav-link" href="home.php">Explore</a>
                <?php endif; ?>
            </div>

            <div class="navbar-actions">
                <div class="icon-btn avatar-btn" style="cursor:default;">
                    <?= $initials ?>
                </div>

                <a href="../php/AuthSystem/logout.php"
                style="font-size:13px;font-weight:600;color:var(--primary);text-decoration:none;margin-left:12px;">
                Log Out
                </a>
            </div>
    </nav>

    <div class="settings-shell">

        <!-- SIDEBAR -->
        <div class="settings-side">
            <div class="settings-side-header">
                <div class="side-avatar"><?= $initials ?></div>
                <div class="side-name"><?= htmlspecialchars($user['firstName'] . ' ' . $user['lastName']) ?></div>
                <div class="side-role role-<?= $user['role'] ?>"><?= ucfirst($user['role']) ?></div>
                <div style="font-size:12px;color:var(--text-muted);margin-top:6px;"><?= htmlspecialchars($user['email']) ?></div>
            </div>

            <div style="display:flex;flex-direction:column;height:calc(100% - 140px);">
                <!-- SHARED: all roles -->
                <div class="settings-nav-section">Account</div>
                <div class="settings-nav-link active" onclick="showSection(this,'s-profile')"> Profile Info</div>
                <div class="settings-nav-link" onclick="showSection(this,'s-password')">Change Password</div>
                <div class="settings-nav-link" onclick="showSection(this,'s-notifications')"> Notifications</div>

                <?php if ($isCustomer || $isSeller): ?>
                    <!-- CUSTOMER + SELLER -->
                    <div class="settings-nav-section">Shopping</div>
                    <div class="settings-nav-link" onclick="showSection(this,'s-delivery')"> Delivery Addresses</div>
                    <div class="settings-nav-link" onclick="showSection(this,'s-payment')"> Payment & Wallet</div>
                <?php endif; ?>

                <?php if ($isSeller): ?>
                    <!-- SELLER ONLY -->
                    <div class="settings-nav-section">Seller</div>
                    <div class="settings-nav-link" onclick="showSection(this,'s-shop')">Shop Settings</div>
                    <div class="settings-nav-link" onclick="showSection(this,'s-payout')"> Payout Details</div>
                <?php endif; ?>

                <?php if ($isAdmin): ?>
                    <!-- ADMIN ONLY -->
                    <div class="settings-nav-section">Administration</div>
                    <div class="settings-nav-link" onclick="showSection(this,'s-admin')"> Admin Preferences</div>
                <?php endif; ?>

                <div class="settings-nav-section">Account Actions</div>
                <div class="settings-nav-link" onclick="showSection(this,'s-danger')" style="color:#8b1a14;">⚠  Danger Zone</div>

                <a class="signout-link" href="<?= $isAdmin ? '../php/AuthSystem/logout.php' : '../php/AuthSystem/logout.php' ?>">⚠  Sign Out</a>
            </div>
        </div>

        <!-- MAIN CONTENT -->
        <div class="settings-main">

            <?php if ($saved): ?>
                <div class="alert-success">Profile updated successfully.</div>
            <?php endif; ?>
            
            <?php if ($saveError): ?>
                <div class="alert-error">Error: 
                    <?= htmlspecialchars($saveError) ?>
                </div>
            <?php endif; ?>

            <!-- PROFILE (all roles) -->
            <div id="s-profile" class="section-panel active">
                <div class="settings-section">
                    <div class="settings-section-title">Profile Information</div>
                    <div class="settings-section-sub">This is your public identity on Past Times.</div>
                    <form method="POST">
                        <div class="input-row">
                            <div class="form-group">
                                <label class="form-label">FIRST NAME</label>
                                <input class="form-input" type="text" name="firstName" value="<?= htmlspecialchars($user['firstName']) ?>" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">LAST NAME</label>
                                <input class="form-input" type="text" name="lastName" value="<?= htmlspecialchars($user['lastName']) ?>" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">USERNAME</label>
                            <input class="form-input" type="text" value="<?= htmlspecialchars($user['username']) ?>" disabled style="opacity:.6;">
                            <div style="font-size:12px;color:var(--text-muted);margin-top:4px;">Username cannot be changed.</div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">EMAIL ADDRESS</label>
                            <input class="form-input" type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">ACCOUNT ROLE</label>
                            <input class="form-input" value="<?= ucfirst($user['role']) ?>" disabled style="opacity:.6;">
                        </div>
                        <div class="form-group">
                            <label class="form-label">ACCOUNT STATUS</label>
                            <input class="form-input" value="<?= ucfirst($user['status']) ?>" disabled style="opacity:.6;">
                        </div>
                        <div style="display:flex;gap:12px;margin-top:8px;">
                            <button class="btn btn-primary" type="submit" name="saveProfile">Save Changes</button>
                            <button class="btn btn-secondary" type="reset">Discard</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- CHANGE PASSWORD (all roles) -->
            <div id="s-password" class="section-panel">
                <div class="settings-section">
                    <div class="settings-section-title">Change Password</div>
                    <div class="settings-section-sub">Your password is stored as an MD5 hash.</div>
                    <div class="form-group">
                        <label class="form-label">CURRENT PASSWORD</label>
                        <input class="form-input" type="password" placeholder="Enter current password">
                    </div>
                    <div class="form-group">
                        <label class="form-label">NEW PASSWORD</label>
                        <input class="form-input" type="password" placeholder="New password" minlength="4">
                    </div>
                    <div class="form-group">
                        <label class="form-label">CONFIRM NEW PASSWORD</label>
                        <input class="form-input" type="password" placeholder="Repeat new password">
                    </div>
                    <button class="btn btn-primary">Update Password</button>
                </div>
            </div>

            <!-- NOTIFICATIONS (all roles) -->
            <div id="s-notifications" class="section-panel">
                <div class="settings-section">
                    <div class="settings-section-title">Notifications</div>
                    <div class="settings-section-sub">Choose how you want to be notified.</div>
                    <div style="border-bottom:1px solid var(--border-light);padding:16px 0;display:flex;justify-content:space-between;align-items:center;">
                        <div>
                            <div style="font-weight:600;font-size:14px;">New Messages</div>
                            <div style="font-size:13px;color:var(--text-muted);">Alerts when you receive a message.</div>
                        </div>
                        <div class="toggle-switch on" onclick="toggleSwitch(this)">
                            <div class="toggle-knob"></div>
                        </div>
                    </div>
                    <?php if (!$isAdmin): ?>
                        <div style="padding:16px 0;display:flex;justify-content:space-between;align-items:center;">
                            <div>
                                <div style="font-weight:600;font-size:14px;">Order Updates</div>
                                <div style="font-size:13px;color:var(--text-muted);">Shipping and delivery notifications.</div>
                            </div>
                            <div class="toggle-switch on" onclick="toggleSwitch(this)">
                                <div class="toggle-knob"></div>
                            </div>
                        </div>
                    <?php endif; ?>
                    <?php if ($isSeller): ?>
                        <div style="padding:16px 0;display:flex;justify-content:space-between;align-items:center;">
                            <div>
                                <div style="font-weight:600;font-size:14px;">New Sales</div>
                                <div style="font-size:13px;color:var(--text-muted);">Get notified when someone buys your item.</div>
                            </div>
                            <div class="toggle-switch on" onclick="toggleSwitch(this)">
                                <div class="toggle-knob"></div>
                            </div>
                        </div>
                    <?php endif; ?>
                    <?php if ($isAdmin): ?>
                        <div style="padding:16px 0;display:flex;justify-content:space-between;align-items:center;">
                            <div>
                                <div style="font-weight:600;font-size:14px;">New Registrations</div>
                                <div style="font-size:13px;color:var(--text-muted);">Alert when a new user registers.</div>
                            </div>
                            <div class="toggle-switch on" onclick="toggleSwitch(this)">
                                <div class="toggle-knob"></div>
                            </div>
                        </div>
                        <div style="padding:16px 0;display:flex;justify-content:space-between;align-items:center;">
                            <div>
                                <div style="font-weight:600;font-size:14px;">New Listing Submissions</div>
                                <div style="font-size:13px;color:var(--text-muted);">Alert when a listing needs approval.</div>
                            </div>
                            <div class="toggle-switch on" onclick="toggleSwitch(this)">
                                <div class="toggle-knob"></div>
                            </div>
                        </div>
                    <?php endif; ?>
                    <div style="margin-top:12px;"><button class="btn btn-primary">Save Preferences</button></div>
                </div>
            </div>

            <!-- DELIVERY (customer + seller) -->
            <?php if ($isCustomer || $isSeller): ?>
                <div id="s-delivery" class="section-panel">
                    <div class="settings-section">
                        <div class="settings-section-title">Delivery Addresses</div>
                        <div class="settings-section-sub">Manage where your orders are shipped.</div>
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px;">
                            <div style="border:2px solid var(--primary);border-radius:var(--radius);padding:18px;background:#fff8f7;">
                                <div style="font-size:20px;margin-bottom:8px;">📦</div>
                                <div style="font-weight:700;font-size:15px;margin-bottom:4px;">PUDO Locker</div>
                                <div style="font-size:13px;color:var(--text-muted);margin-bottom:12px;">No locker set yet.</div>
                                <button class="btn btn-primary btn-sm">Set Locker</button>
                            </div>
                            <div style="border:1.5px dashed var(--border);border-radius:var(--radius);padding:18px;display:flex;align-items:center;justify-content:center;flex-direction:column;gap:8px;cursor:pointer;" onclick="">
                                <div style="font-size:24px;">➕</div>
                                <div style="font-size:13px;font-weight:600;color:var(--text-muted);">Add Address</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- PAYMENT (customer + seller) -->
                <div id="s-payment" class="section-panel">
                    <div class="settings-section">
                        <div class="settings-section-title">Payment & Wallet</div>
                        <div class="settings-section-sub">Manage your payment methods and escrow wallet.</div>
                        <div style="background:linear-gradient(135deg,var(--primary),var(--primary-dark));border-radius:var(--radius);padding:24px;margin-bottom:20px;">
                            <div style="font-size:11px;letter-spacing:.1em;text-transform:uppercase;color:rgba(255,255,255,.7);margin-bottom:8px;">WALLET BALANCE</div>
                            <div style="font-family:var(--font-display);font-size:36px;font-weight:700;color:white;margin-bottom:16px;">R 0.00</div>
                            <div style="display:flex;gap:10px;">
                                <button class="wallet-btn wallet-btn-primary">Add Funds</button>
                                <button class="wallet-btn wallet-btn-secondary">Transaction History</button>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">LINKED CARD / EFT</label>
                            <input class="form-input" placeholder="No payment method linked yet">
                        </div>
                        <button class="btn btn-secondary btn-sm">+ Link Payment Method</button>
                    </div>
                </div>
            <?php endif; ?>

            <!-- SHOP SETTINGS (seller only) -->
            <?php if ($isSeller): ?>
                <div id="s-shop" class="section-panel">
                    <div class="settings-section">
                        <div class="settings-section-title">Shop Settings</div>
                        <div class="settings-section-sub">Customise your seller storefront.</div>
                        <div class="form-group">
                            <label class="form-label">SHOP NAME</label>
                            <input class="form-input" placeholder="e.g. Lindi's Vintage" value="<?= htmlspecialchars($user['username']) ?>'s Shop">
                        </div>
                        <div class="form-group">
                            <label class="form-label">SHOP BIO</label>
                            <textarea class="form-input" rows="3" placeholder="Tell buyers about your style..."></textarea>
                        </div>
                        <button class="btn btn-primary">Save Shop Details</button>
                    </div>
                </div>

                <!-- PAYOUT (seller only) -->
                <div id="s-payout" class="section-panel">
                    <div class="settings-section">
                        <div class="settings-section-title">Payout Details</div>
                        <div class="settings-section-sub">Where should we send your earnings?</div>
                        <div class="form-group">
                            <label class="form-label">BANK NAME</label>
                            <input class="form-input" placeholder="e.g. FNB, Capitec, Nedbank">
                        </div>
                        <div class="form-group">
                            <label class="form-label">ACCOUNT NUMBER</label>
                            <input class="form-input" placeholder="Your bank account number">
                        </div>
                        <div class="form-group">
                            <label class="form-label">ACCOUNT HOLDER NAME</label>
                            <input class="form-input" value="<?= htmlspecialchars($user['firstName'] . ' ' . $user['lastName']) ?>">
                        </div>
                        <button class="btn btn-primary">Save Payout Info</button>
                    </div>
                </div>
            <?php endif; ?>

            <!-- ADMIN PREFERENCES (admin only) -->
            <?php if ($isAdmin): ?>
                <div id="s-admin" class="section-panel">
                    <div class="settings-section">
                        <div class="settings-section-title">Admin Preferences</div>
                        <div class="settings-section-sub">Control how the admin panel behaves.</div>
                        <div style="padding:16px 0;display:flex;justify-content:space-between;align-items:center;border-bottom:1px solid var(--border-light);">
                            <div>
                                <div style="font-weight:600;font-size:14px;">Auto-approve Verified Sellers</div>
                                <div style="font-size:13px;color:var(--text-muted);">Listings from verified sellers skip manual review.</div>
                            </div>
                            <div class="toggle-switch" onclick="toggleSwitch(this)">
                                <div class="toggle-knob"></div>
                            </div>
                        </div>
                        <div style="padding:16px 0;display:flex;justify-content:space-between;align-items:center;">
                            <div>
                                <div style="font-weight:600;font-size:14px;">Show All Users Including Admins</div>
                                <div style="font-size:13px;color:var(--text-muted);">Admin accounts shown in user management table.</div>
                            </div>
                            <div class="toggle-switch" onclick="toggleSwitch(this)">
                                <div class="toggle-knob"></div>
                            </div>
                        </div>
                        <button class="btn btn-primary" style="margin-top:12px;">Save Preferences</button>
                    </div>
                </div>
            <?php endif; ?>

            <!-- DANGER ZONE (all roles) -->
            <div id="s-danger" class="section-panel">
                <div class="settings-section">
                    <div class="settings-section-title" style="color:#8b1a14;">Danger Zone</div>
                    <div class="settings-section-sub">These actions are permanent and cannot be undone.</div>
                    <div class="danger-zone">
                        <div style="font-weight:700;font-size:15px;margin-bottom:6px;">Delete My Account</div>
                        <div style="font-size:13px;color:var(--text-muted);margin-bottom:16px;">
                            <?php if ($isAdmin): ?>
                                Admin accounts cannot be self-deleted. Contact the system owner.
                            <?php else: ?>
                                All your data<?= $isSeller ? ', listings,' : '' ?> and order history will be permanently removed.
                            <?php endif; ?>
                        </div>
                        <?php if (!$isAdmin): ?>
                            <button class="btn" style="background:#fce8e8;color:#8b1a14;border:1px solid #f5b7b7;" onclick="return confirm('Are you sure? This cannot be undone.')">Delete My Account</button>
                        <?php else: ?>
                            <button class="btn" style="background:var(--border);color:var(--text-muted);cursor:not-allowed;" disabled>Not Available for Admins</button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

        </div><!-- end settings-main -->
    </div><!-- end settings-shell -->

    <script src="../javascript/script.js"></script>
    <script>
        function showSection(el, id) {
            document.querySelectorAll('.settings-nav-link').forEach(l => l.classList.remove('active'));
            document.querySelectorAll('.section-panel').forEach(p => p.classList.remove('active'));
            el.classList.add('active');
            document.getElementById(id).classList.add('active');
        }
    </script>
</body>

</html>