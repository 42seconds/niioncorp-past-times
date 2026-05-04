<?php
session_start();
if (!isset($_SESSION['adminID']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../admin_login.php');
    exit;
}
require_once('../BackendLogic/dbConn.php');
$users = $conn->query("SELECT userID, username, firstName, lastName, email, role, status, createdAt FROM tblUser ORDER BY status ASC, createdAt DESC");
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management – Past Times Admin</title>
    <link rel="stylesheet" href="../../css/styles.css">
        <link rel="stylesheet" href="../../css/responsive.css">

    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
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

        .badge-pending {
            background: #fff3e0;
            color: #7a4f00;
            border: 1px solid #ffe0b2;
            padding: 3px 10px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 600;
        }

        .badge-verified {
            background: #e6faf0;
            color: #1a5c35;
            border: 1px solid #b2dbd7;
            padding: 3px 10px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 600;
        }

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

        .action-btns {
            display: flex;
            gap: 8px;
        }

        .btn-verify {
            padding: 5px 14px;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
        }

        .btn-delete {
            padding: 5px 14px;
            background: #fce8e8;
            color: #8b1a14;
            border: 1px solid #f5b7b7;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
        }

        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .alert-success {
            background: #e6faf0;
            border: 1px solid #b2dbd7;
            color: #1a5c35;
        }

        .alert-error {
            background: #fce8e8;
            border: 1px solid #f5b7b7;
            color: #8b1a14;
        }
    </style>
</head>

<body>

<!-- the nav bar items such as this need to be in the settings tof the admin acc hence all users have different settings.-->
<?php include 'adminNavbar.php'; ?>


    <div class="admin-wrap">
        <div class="admin-header">
            <h1>User Management</h1>
            <a href="../../html/home.php" style="font-size:13px;color:var(--text-muted);text-decoration:none;">← Back to Site</a>
        </div>
        <?php if (isset($_GET['msg'])): ?><div class="alert alert-success"><?= htmlspecialchars($_GET['msg']) ?></div><?php endif; ?>
        <?php if (isset($_GET['err'])): ?><div class="alert alert-error"><?= htmlspecialchars($_GET['err']) ?></div><?php endif; ?>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($u = $users->fetch_assoc()): ?>
                    <tr>
                        <td><?= $u['userID'] ?></td>
                        <td><?= htmlspecialchars($u['firstName'] . ' ' . $u['lastName']) ?></td>
                        <td><?= htmlspecialchars($u['username']) ?></td>
                        <td><?= htmlspecialchars($u['email']) ?></td>
                        <td><?= $u['role'] ?></td>
                        <td><span class="badge-<?= $u['status'] ?>"><?= strtoupper($u['status']) ?></span></td>
                        <td>
                            <div class="action-btns">
                                <?php if ($u['status'] === 'pending'): ?>
                                    <form method="POST" action="userActions.php" style="display:inline;">
                                        <input type="hidden" name="action" value="verify">
                                        <input type="hidden" name="userID" value="<?= $u['userID'] ?>">
                                        <button class="btn-verify">✔ Verify</button>
                                    </form>
                                <?php endif; ?>
                                <form method="POST" action="userActions.php" style="display:inline;" onsubmit="return confirm('Delete this user?')">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="userID" value="<?= $u['userID'] ?>">
                                    <button class="btn-delete">✕ Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</body>

</html>