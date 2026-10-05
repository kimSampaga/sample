<?php
/**
 * Session Configuration Wrapper
 * Initializes session management
 */

require_once dirname(__FILE__) . '/session_handler.php';

// Initialize session
SessionManager::initSession();

// Check session timeout on every page load
if (!SessionManager::checkSessionTimeout()) {
    header('Location: ' . APP_URL . '/login.php?session_expired=1');
    exit;
}
?>
