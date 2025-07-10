<?php
require_once 'core/initialize.php';

// Log the logout action before destroying the session
if (is_logged_in()) {
    create_log_entry($_SESSION['user_id'], 'LOGOUT', 'User logged out.');
}

// 1. Unset all of the session variables.
$_SESSION = array();

// 2. Destroy the session cookie.
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 3. Finally, destroy the session data on the server.
session_destroy();

// 4. Redirect to the homepage.
redirect('index.php');
?>