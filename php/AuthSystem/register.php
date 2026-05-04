
<?php

/**
 * register.php
 * Past Times – New User Self-Registration
 *
 * - Collects: firstName, lastName, username, email, password, confirmPassword
 * - Hashes password with MD5
 * - Saves user with status = 'pending' (admin must verify before login is allowed)
 * - Redirects to login.php with a success notice on completion
 */

session_start();
require_once(__DIR__ . '/../BackendLogic/dbConn.php');

/*if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    var_dump($_POST);
    exit;
}*/

$error   = '';
$success = '';

// Sticky values
$sFirst    = '';
$sLast     = '';
$sUsername = '';
$sEmail    = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sFirst    = htmlspecialchars(trim($_POST['firstName']  ?? ''));
    $sLast     = htmlspecialchars(trim($_POST['lastName']   ?? ''));
    $sUsername = htmlspecialchars(trim($_POST['username']   ?? ''));
    $sEmail    = htmlspecialchars(trim($_POST['email']      ?? ''));
    $password  = trim($_POST['password']  ?? '');

    $confirm   = trim($_POST['confirmPassword']   ?? '');

    // Basic server-side validation
    if ($password !== $confirm) {
        $error = "Passwords do not match.";
    } elseif (strlen($password) < 4) {
        $error = "Password must be at least 4 characters.";
    } elseif (empty($sFirst) || empty($sLast) || empty($sUsername) || empty($sEmail)) {
        $error = "All fields are required.";
    } else {
        // Duplicate check
        $chk = $conn->prepare("SELECT userID FROM tblUser WHERE username = ? OR email = ? LIMIT 1");
        $chk->bind_param("ss", $sUsername, $sEmail);
        $chk->execute();
        $chk->store_result();


        if ($chk->num_rows > 0) {
            $error = "That username or email is already registered.";
        } else {
            $hash  = md5($password);
            $maxID = $conn->query("SELECT MAX(userID) AS m FROM tblUser")->fetch_assoc()['m'] ?? 0;
            $newID = (int)$maxID + 1;

            //this catches the role of the regostering user
            $allowedRoles = ['customer', 'seller'];
            $role = $_POST['role'] ?? 'customer';

            if (!in_array($role, $allowedRoles)) {
                $role = 'customer';
            }

              $ins = $conn->prepare(
                    "INSERT INTO tblUser
                    (username, firstName, lastName, email, passwordHash, role, status, createdAt)
                    VALUES (?, ?, ?, ?, ?, ?, 'pending', CURDATE())"
                );

                $ins->bind_param("ssssss", $sUsername, $sFirst, $sLast, $sEmail, $hash, $role);
                        
            if ($ins->execute()) {
                // ── Redirect to login with a success flag ──────────────────
                $ins->close();
                $chk->close();
                $conn->close();
                header('Location: login.php?registered=1');
                exit;
            } else {
                $error = "Registration failed: " . $conn->error;
            }
            $ins->close();
        }
        $chk->close();
    }
}



$conn->close();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register – Past Times</title>
    <link rel="stylesheet" href="../../css/styles.css">
        <link rel="stylesheet" href="../css/responsive.css">

    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,700&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        body {
            background: var(--cream);
            font-family: var(--font-body);
        }

        .container {
            max-width: 520px;
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

        .links {
            margin-top: 20px;
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

        .pending-notice {
            background: #fff3e0;
            border: 1px solid #ffe0b2;
            border-radius: 8px;
            padding: 12px 16px;
            font-size: 13px;
            color: #7a4f00;
            margin-top: 16px;
        }
    </style>
</head>

<body>

    <div style="text-align:center; padding:24px 0 0;">
        <a href="../../html/home.php" style="font-family:var(--font-display);font-size:22px;font-weight:700;color:var(--primary);text-decoration:none;">Past Times</a>
    </div>

    <div class="container">
        <h1>Create Your Account</h1>
        <div class="subtitle">Join South Africa's most conscious fashion community.</div>

        <!--<php if ($success): ?>
    <div class="alert-success">?= $success ?></div>
    <div class="pending-notice">
      ⏳ An administrator will review your registration. Once verified, you can log in at any time.
    </div>
    <div class="links" style="margin-top:20px;"><a href="../php/AuthSystem/login.php">Back to Login</a></div>
  ?php else: ?>
  -->

        <?php if ($error): ?>
            <div class="alert-error"><?= $error ?></div>
        <?php endif; ?>

        <form method="POST" action="register.php">

            <div class="input-row">
                <div class="form-group">
                    <label class="form-label" for="firstName">First Name</label>
                    <input class="form-input" type="text" id="firstName" name="firstName"
                        value="<?= $sFirst ?>" placeholder="John" required minlength="2">
                </div>
                <div class="form-group">
                    <label class="form-label" for="lastName">Last Name</label>
                    <input class="form-input" type="text" id="lastName" name="lastName"
                        value="<?= $sLast ?>" placeholder="Doe" required minlength="2">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="username">Username</label>
                <input class="form-input" type="text" id="username" name="username"
                    value="<?= $sUsername ?>" placeholder="john_doe" required minlength="3"
                    pattern="[a-zA-Z0-9_]+" title="Letters, numbers, underscores only">
            </div>

            <div class="form-group">
                <label class="form-label" for="email">Email Address</label>
                <input class="form-input" type="email" id="email" name="email"
                    value="<?= $sEmail ?>" placeholder="john.doe@example.com" required>
            </div>

            <div class="form-group">
                <label class="form-label" for="password">Password</label>
                <input class="form-input" type="password" id="password" name="password"
                    placeholder="Choose a password" required minlength="4">
                <div style="font-size:11px;color:var(--text-muted);margin-top:4px;">🔒 Stored as MD5 hash</div>
            </div>

            <div class="form-group">
                <label class="form-label" for="confirmPassword">Confirm Password</label>
                <!-- name="confirmPassword" matches $_POST['confirmPassword'] in PHP -->
                <input class="form-input" type="password" id="confirmPassword" name="confirmPassword"
                    placeholder="Repeat your password" required minlength="4" oninput="checkMatch()">
                <div id="match-msg" style="font-size:12px;margin-top:4px;"></div>
            </div>

            <div class="form-group">
                <label class="form-label">Register As</label>
                    <div style="display:flex; gap:20px; margin-top:8px;">
                <label>
                    <input type="radio" name="role" value="customer" checked>
                    Customer
                </label>
                <label>
                    <input type="radio" name="role" value="seller">
                    Seller
                </label>
            </div>
            </div>

          

            <button class="btn btn-primary btn-lg" type="submit">Register</button>

        </form>


        <div class="links">Already have an account?
            <a href="login.php">Log In</a>
        </div>
    </div>

    <script>
        function checkMatch() {
            const pw = document.getElementById('password').value;
            const c = document.getElementById('confirmPassword').value;
            const msg = document.getElementById('match-msg');
            if (!c.length) {
                msg.textContent = '';
                return;
            }
            if (pw === c) {
                msg.style.color = 'green';
                msg.textContent = '✔ Passwords match';
            } else {
                msg.style.color = 'red';
                msg.textContent = '✘ Passwords do not match';
            }
        }
        
    </script>
</body>

</html>
