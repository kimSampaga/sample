<?php
/**
 * Authorization Middleware
 * Protects pages and enforces role-based access control
 */

require_once dirname(__FILE__) . '/session_handler.php';

class AuthMiddleware {
    /**
     * Check if user is authenticated
     * Redirect to login if not
     */
    public static function requireLogin() {
        if (!SessionManager::isLoggedIn()) {
            header('Location: ' . APP_URL . '/login.php');
            exit;
        }
    }

    /**
     * Check if user is admin
     * Redirect to dashboard if not
     */
    public static function requireAdmin() {
        self::requireLogin();
        
        if (!SessionManager::isAdmin()) {
            header('Location: ' . APP_URL . '/dashboard.php');
            exit;
        }
    }

    /**
     * Check if user has specific role
     */
    public static function requireRole($role) {
        self::requireLogin();
        
        if (!SessionManager::hasRole($role)) {
            header('Location: ' . APP_URL . '/dashboard.php');
            exit;
        }
    }

    /**
     * Check CSRF token from POST request
     */
    public static function validateCSRF() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $token = $_POST['csrf_token'] ?? '';
            
            if (!SessionManager::validateCSRFToken($token)) {
                die('CSRF token validation failed. Please try again.');
            }
        }
    }

    /**
     * Get CSRF token for forms
     */
    public static function getCSRFToken() {
        return SessionManager::generateCSRFToken();
    }

    /**
     * Log user activity
     */
    public static function logActivity($action, $description = '', $user_id = null) {
        global $conn;
        
        if (!$user_id && SessionManager::isLoggedIn()) {
            $user = SessionManager::getUserData();
            $user_id = $user['user_id'];
        }
        
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
        
        $stmt = $conn->prepare(
            'INSERT INTO activity_logs (user_id, action, description, ip_address) 
             VALUES (?, ?, ?, ?)'
        );
        
        if ($stmt) {
            $stmt->bind_param('isss', $user_id, $action, $description, $ip_address);
            $stmt->execute();
        }
    }
}
?>
