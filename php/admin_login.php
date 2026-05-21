<?php
session_start();

   if (isset($_GET['logout'])) {
        session_unset();
        session_destroy();
        header('Location: admin_login.php');
        exit;
    }
    
include('BackendLogic/dbConn.php');

 
 

$error    = '';
$userData = null;

// Sticky values
$sUsername = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sUsername = htmlspecialchars(trim($_POST['username'] ?? ''));
    $password  = trim($_POST['password'] ?? '');

    $hashedPassword = md5($password);

    $stmt = $conn->prepare(
        "SELECT * FROM tblUser
         WHERE username = ? AND role = 'admin'
         LIMIT 1"
    );

    $stmt->bind_param("s", $sUsername);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $row = $result->fetch_assoc();

        if ($row['passwordHash'] !== $hashedPassword) {
            $error = "Incorrect password.";
        } else {
            // SUCCESS
            $_SESSION['adminID']   = $row['userID'];
            $_SESSION['adminName'] = $row['username'];
            $_SESSION['firstName'] = $row['firstName'];
            $_SESSION['lastName']  = $row['lastName'];
            $_SESSION['role']      = 'admin';
            $stmt->close();
            $conn->close();
            header('Location: AdminArea/adminDashboard.php');
            exit;
        }
    } else {
        $error = "Admin account not found.";
    }

    $stmt->close();
}

$conn->close();

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Admin Login – Past Times</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,600;0,700;1,400&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/styles.css">
        <link rel="stylesheet" href="../css/responsive.css">


    <style>
        body {
            background: var(--cream);
            font-family: var(--font-body);
        }

        .container {
            max-width: 480px;
            margin: 60px auto;
            background: #fff;
            border-radius: 16px;
            padding: 40px;
            box-shadow: 0 4px 24px rgba(0, 0, 0, 0.10);
            border: 1px solid var(--border);
        }

        h1 {
            font-family: var(--font-display);
            font-size: 28px;
            margin-bottom: 6px;
        }

        .subtitle {
            color: var(--text-muted);
            font-size: 14px;
            margin-bottom: 28px;
        }

        .alert-success {
            background: #e6faf0;
            border: 1px solid #b2dbd7;
            color: #1a5c35;
            border-radius: 8px;
            padding: 14px 18px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .alert-error {
            background: #fce8e8;
            border: 1px solid #f5b7b7;
            color: #8b1a14;
            border-radius: 8px;
            padding: 14px 18px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .user-banner {
            background: var(--dark);
            color: white;
            border-radius: 8px;
            padding: 14px 18px;
            margin-bottom: 24px;
            font-size: 15px;
            font-weight: 600;
            letter-spacing: 0.02em;
        }

        .user-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }

        .user-table th {
            text-align: left;
            padding: 10px 14px;
            background: var(--bg-warm);
            border-bottom: 2px solid var(--border);
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: var(--text-muted);
        }

        .user-table td {
            padding: 10px 14px;
            border-bottom: 1px solid var(--border-light);
        }

        .user-table tr:last-child td {
            border-bottom: none;
        }

        .links {
            margin-top: 24px;
            font-size: 14px;
            text-align: center;
        }

        .links a {
            color: var(--primary);
            font-weight: 600;
            text-decoration: none;
        }

        .links a:hover {
            text-decoration: underline;
        }

        .admin-link {
            display: block;
            margin-top: 12px;
            text-align: center;
            font-size: 13px;
        }

        .btn-admin {
            display: inline-block;
            padding: 9px 22px;
            background: var(--dark);
            color: #fff;
            border-radius: 999px;
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
            margin-top: 6px;
        }

        .btn-admin:hover {
            background: #333;
        }
    </style>
</head>

<body>

    <div style="text-align:center; padding:24px 0;">
        <a href="../html/home.php" style="font-size:22px;font-weight:700;color:var(--primary);text-decoration:none;">
            Past Times
        </a>
    </div>

    <div class="container">

        <?php if ($userData): ?>

            <!-- SUCCESS -->
            <div class="user-banner">
                Admin <?= htmlspecialchars($userData['firstName'] . ' ' . $userData['lastName']) ?> is logged in
            </div>

            <h2 style="font-family:var(--font-display);font-size:20px;margin-bottom:16px;">
                Admin Details
            </h2>

            <table class="user-table">
                <?php foreach ($userData as $column => $value): ?>
                    <?php if ($column === 'passwordHash') continue; ?>
                    <tr>
                        <th><?= htmlspecialchars($column) ?></th>
                        <td><?= htmlspecialchars((string)$value) ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>

            <div class="links" style="margin-top:20px;">
                <a href="AdminArea/adminDashboard.php">Go to Dashboard</a> |
                <a href="../php/AdminSystem/admin_login.php?logout=1">Log Out</a>
            </div>

        <?php else: ?>

            <!-- FORM -->
            <h1>Welcome Back, Admin</h1>
            <div class="subtitle">Sign in to manage the system.</div>

            <?php if ($error): ?>
                <div class="alert-error"><?= $error ?></div>
            <?php endif; ?>

            <form method="POST" action="admin_login.php">

                <div class="form-group">
                    <label class="form-label" for="username">Username</label>
                    <input
                        class="form-input"
                        type="text"
                        id="username"
                        name="username"
                        value="<?= $sUsername ?>"
                        placeholder="Enter admin username"
                        required
                        minlength="3">
                </div>

                <div class="form-group">
                    <label class="form-label" for="password">Password</label>
                    <input
                        class="form-input"
                        type="password"
                        id="password"
                        name="password"
                        placeholder="Enter password"
                        required
                        minlength="4">
                </div>

                <button class="btn btn-primary btn-lg" type="submit">Log In</button>
            </form>

            <div class="links" style="margin-top:20px;">
                <a href="../php/AuthSystem/login.php">Back to Customer Login</a>
            </div>

        <?php endif; ?>

    </div>

   

</body>

</html>