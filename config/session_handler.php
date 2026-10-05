<?php
/**
 * Session Handler
 * Manages user sessions with timeout and security checks
 */

require_once dirname(__FILE__) . '/db_config.php';

class SessionManager {
    /**
     * Initialize secure session
     */
    public static function initSession() {
        // Only set session cookie params if session is not already active
        if (session_status() === PHP_SESSION_NONE) {
            // Session security settings
            session_set_cookie_params([
                'lifetime' => SESSION_TIMEOUT,
                'path' => '/',
                'domain' => $_SERVER['HTTP_HOST'] ?? 'localhost',
                'secure' => SESSION_SECURE,
                'httponly' => SESSION_HTTPONLY,
                'samesite' => 'Strict'
            ]);

            session_start();
        }
        
        // Regenerate session ID for security (only if session is active)
        if (session_status() === PHP_SESSION_ACTIVE && !isset($_SESSION['CREATED'])) {
            $_SESSION['CREATED'] = time();
            session_regenerate_id(true);
        }
    }

    /**
     * Check if user is logged in
     */
    public static function isLoggedIn() {
        return isset($_SESSION['user_id']) && isset($_SESSION['username']);
    }

    /**
     * Check session timeout
     */
    public static function checkSessionTimeout() {
        if (isset($_SESSION['CREATED'])) {
            if (time() - $_SESSION['CREATED'] > SESSION_TIMEOUT) {
                self::destroySession();
                return false;
            }
        }
        return true;
    }

    /**
     * Get current user data
     */
    public static function getUserData() {
        if (self::isLoggedIn()) {
            return [
                'user_id' => $_SESSION['user_id'],
                'username' => $_SESSION['username'],
                'email' => $_SESSION['email'] ?? null,
                'role' => $_SESSION['role'] ?? ROLE_USER,
                'first_name' => $_SESSION['first_name'] ?? null,
                'last_name' => $_SESSION['last_name'] ?? null
            ];
        }
        return null;
    }

    /**
     * Set user session
     */
    public static function setUserSession($user) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['first_name'] = $user['first_name'] ?? '';
        $_SESSION['last_name'] = $user['last_name'] ?? '';
        $_SESSION['login_time'] = time();
        
        // Regenerate session ID after login for security
        session_regenerate_id(true);
    }

    /**
     * Destroy session
     */
    public static function destroySession() {
        $_SESSION = array();
        
        if (ini_get('session.use_cookies') === '1') {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }
        
        session_destroy();
    }

    /**
     * Regenerate session ID (for CSRF protection)
     */
    public static function regenerateSessionId() {
        session_regenerate_id(true);
    }

    /**
     * Generate CSRF token
     */
    public static function generateCSRFToken() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    /**
     * Validate CSRF token
     */
    public static function validateCSRFToken($token) {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }

    /**
     * Check user role
     */
    public static function hasRole($role) {
        return isset($_SESSION['role']) && $_SESSION['role'] === $role;
    }

    /**
     * Check if user is admin
     */
    public static function isAdmin() {
        return self::hasRole(ROLE_ADMIN);
    }
}
?>
