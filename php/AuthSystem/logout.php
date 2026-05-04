<?php
session_start();

// Unset all session variables
$_SESSION = [];
session_unset();

// Destroy the session
session_destroy();

// Optional: destroy session cookie (more complete logout)
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Redirect to login page
header("Location: login.php"); // login.php is in the same AuthSystem/ folder
exit;