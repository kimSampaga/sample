<?php
/**
 * Logout Handler
 * Destroys user session and clears cookies
 */

require_once dirname(__FILE__) . '/config/session_handler.php';
require_once dirname(__FILE__) . '/config/db_config.php';

// Destroy the session
SessionManager::destroySession();

// Clear remember me cookie
setcookie('remember_username', '', time() - 3600, '/');

// Redirect to login with logout message
header('Location: ' . APP_URL . '/login.php?logout=1');
exit;
?>
