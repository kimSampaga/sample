<?php
/**
 * Login Process Handler
 * Handles user authentication with security measures
 */

require_once dirname(__FILE__) . '/../config/db_connect.php';
require_once dirname(__FILE__) . '/../config/session_handler.php';

class LoginHandler {
    private $conn;

    public function __construct($database_conn) {
        $this->conn = $database_conn;
    }

    /**
     * Authenticate user
     */
    public function authenticate($username, $password) {
        // Validate input
        if (empty($username) || empty($password)) {
            return [
                'success' => false,
                'message' => 'Username and password are required.'
            ];
        }

        // Check login attempts
        if ($this->isLockedOut($username)) {
            return [
                'success' => false,
                'message' => 'Too many login attempts. Please try again in 15 minutes.'
            ];
        }

        // Prepare statement to prevent SQL injection
        $stmt = $this->conn->prepare('SELECT id, username, email, password_hash, role, first_name, last_name, is_active FROM users WHERE username = ?');
        
        if (!$stmt) {
            return [
                'success' => false,
                'message' => 'Database error occurred.'
            ];
        }

        $stmt->bind_param('s', $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();

            // Check if account is active
            if (!$user['is_active']) {
                $this->logFailedAttempt($username);
                return [
                    'success' => false,
                    'message' => 'Your account is inactive. Please contact an administrator.'
                ];
            }

            // Verify password
            if (password_verify($password, $user['password_hash'])) {
                // Clear failed login attempts
                $this->clearFailedAttempts($username);

                // Set user session
                SessionManager::setUserSession($user);

                // Log login activity
                $this->logLogin($user['id']);

                return [
                    'success' => true,
                    'message' => 'Login successful.',
                    'user' => $user
                ];
            } else {
                $this->logFailedAttempt($username);
                return [
                    'success' => false,
                    'message' => 'Invalid username or password.'
                ];
            }
        } else {
            // Log failed attempt even if user doesn't exist (for security)
            $this->logFailedAttempt($username);
            return [
                'success' => false,
                'message' => 'Invalid username or password.'
            ];
        }
    }

    /**
     * Check if user account is locked out
     */
    private function isLockedOut($username) {
        $stmt = $this->conn->prepare('SELECT failed_attempts, last_failed_attempt FROM login_attempts WHERE username = ?');
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $attempt = $result->fetch_assoc();
            
            // Check if lockout time has passed
            if ($attempt['failed_attempts'] >= MAX_LOGIN_ATTEMPTS) {
                $time_passed = time() - strtotime($attempt['last_failed_attempt']);
                if ($time_passed < LOGIN_ATTEMPT_TIMEOUT) {
                    return true;
                } else {
                    // Lockout time has passed, reset attempts
                    $this->clearFailedAttempts($username);
                    return false;
                }
            }
        }
        return false;
    }

    /**
     * Log failed login attempt
     */
    private function logFailedAttempt($username) {
        $stmt = $this->conn->prepare(
            'INSERT INTO login_attempts (username, failed_attempts, last_failed_attempt) 
             VALUES (?, 1, NOW())
             ON DUPLICATE KEY UPDATE 
             failed_attempts = failed_attempts + 1, 
             last_failed_attempt = NOW()'
        );
        $stmt->bind_param('s', $username);
        $stmt->execute();
    }

    /**
     * Clear failed login attempts
     */
    private function clearFailedAttempts($username) {
        $stmt = $this->conn->prepare('DELETE FROM login_attempts WHERE username = ?');
        $stmt->bind_param('s', $username);
        $stmt->execute();
    }

    /**
     * Log successful login
     */
    private function logLogin($user_id) {
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
        
        $stmt = $this->conn->prepare(
            'INSERT INTO login_logs (user_id, ip_address, user_agent, login_time) 
             VALUES (?, ?, ?, NOW())'
        );
        $stmt->bind_param('iss', $user_id, $ip_address, $user_agent);
        $stmt->execute();
    }
}
?>
