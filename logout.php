<?php
session_start();

// 1. Clear all session variables
$_SESSION = [];

// 2. Delete the standard session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// ==========================================
// 3. DELETE THE "REMEMBER ME" COOKIE
// ==========================================
setcookie('jilomaac_remember', '', time() - 3600, '/');

// 4. Destroy the server-side session
session_destroy();

// 5. Redirect to Login page
header("Location: login.php");
exit;
?>