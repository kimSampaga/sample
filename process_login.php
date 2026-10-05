<?php
/**
 * Process Login Form
 * Handles user authentication and session creation
 */

require_once dirname(__FILE__) . '/config/db_connect.php';
require_once dirname(__FILE__) . '/config/session_handler.php';
require_once dirname(__FILE__) . '/config/db_config.php';
require_once dirname(__FILE__) . '/classes/LoginHandler.php';

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.php');
    exit;
}

// Get form data
$username = isset($_POST['username']) ? trim($_POST['username']) : '';
$password = isset($_POST['password']) ? $_POST['password'] : '';

// Initialize login handler
$loginHandler = new LoginHandler($conn);

// Attempt to authenticate
$result = $loginHandler->authenticate($username, $password);

if ($result['success']) {
    // Login successful
    if (isset($_POST['remember_me'])) {
        // Set remember me cookie (14 days)
        setcookie('remember_username', $username, time() + (14 * 24 * 60 * 60), '/');
    } else {
        // Clear remember me cookie
        setcookie('remember_username', '', time() - 3600, '/');
    }

    // Redirect to dashboard
    header('Location: dashboard.php');
    exit;
} else {
    // Login failed - redirect with error message
    $error = urlencode($result['message']);
    header('Location: login.php?error=' . $error);
    exit;
}
?>
