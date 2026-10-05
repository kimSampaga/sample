<?php
/**
 * Helper Functions
 * Common utility functions for the application
 */

/**
 * Sanitize user input
 */
function sanitize_input($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

/**
 * Validate email format
 */
function is_valid_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 * Validate password strength
 */
function is_strong_password($password) {
    // At least 8 characters, 1 uppercase, 1 lowercase, 1 number
    return preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[a-zA-Z\d]{8,}$/', $password);
}

/**
 * Redirect with message
 */
function redirect($url, $message = '', $type = 'info') {
    if (!empty($message)) {
        $_SESSION['message'] = $message;
        $_SESSION['message_type'] = $type; // 'success', 'error', 'warning', 'info'
    }
    header('Location: ' . $url);
    exit;
}

/**
 * Display and clear message
 */
function display_message() {
    if (isset($_SESSION['message'])) {
        $message = $_SESSION['message'];
        $type = $_SESSION['message_type'] ?? 'info';
        
        // Map types to CSS classes
        $class_map = [
            'success' => 'alert-success',
            'error' => 'alert-danger',
            'danger' => 'alert-danger',
            'warning' => 'alert-warning',
            'info' => 'alert-info'
        ];
        
        $class = $class_map[$type] ?? 'alert-info';
        
        echo '<div class="alert ' . $class . '">' . htmlspecialchars($message) . '</div>';
        
        // Clear message
        unset($_SESSION['message']);
        unset($_SESSION['message_type']);
    }
}

/**
 * Get user's IP address
 */
function get_client_ip() {
    if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
        // Cloudflare
        $ip = $_SERVER['HTTP_CF_CONNECTING_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        // Behind proxy
        $ip = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
    } else {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
    }
    
    return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : 'Unknown';
}

/**
 * Format date/time for display
 */
function format_datetime($datetime, $format = 'M d, Y H:i:s') {
    if (is_string($datetime)) {
        $datetime = strtotime($datetime);
    }
    return date($format, $datetime);
}

/**
 * Check if value is empty (handles various falsy values)
 */
function is_empty($value) {
    return empty($value) && $value !== '0' && $value !== 0;
}

/**
 * Generate random string
 */
function generate_random_string($length = 32) {
    return bin2hex(random_bytes($length / 2));
}

/**
 * Log error to file
 */
function log_error($message, $context = []) {
    $log_file = dirname(__FILE__) . '/../logs/error.log';
    
    // Create logs directory if it doesn't exist
    $log_dir = dirname($log_file);
    if (!is_dir($log_dir)) {
        mkdir($log_dir, 0755, true);
    }
    
    $timestamp = date('Y-m-d H:i:s');
    $context_str = !empty($context) ? ' | ' . json_encode($context) : '';
    $log_message = "[$timestamp] $message$context_str\n";
    
    file_put_contents($log_file, $log_message, FILE_APPEND);
}

/**
 * Check if user has permission
 */
function has_permission($required_role) {
    if (!SessionManager::isLoggedIn()) {
        return false;
    }
    
    return SessionManager::hasRole($required_role);
}

/**
 * Get role display name
 */
function get_role_display($role) {
    $roles = [
        'admin' => 'Administrator',
        'user' => 'User',
        'manager' => 'Manager',
        'guest' => 'Guest'
    ];
    
    return $roles[$role] ?? ucfirst($role);
}

/**
 * Calculate time ago (e.g., "2 hours ago")
 */
function time_ago($timestamp) {
    if (is_string($timestamp)) {
        $timestamp = strtotime($timestamp);
    }
    
    $time_difference = time() - $timestamp;
    
    if ($time_difference < 60) {
        return 'just now';
    } elseif ($time_difference < 3600) {
        $minutes = floor($time_difference / 60);
        return $minutes . ' minute' . ($minutes > 1 ? 's' : '') . ' ago';
    } elseif ($time_difference < 86400) {
        $hours = floor($time_difference / 3600);
        return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
    } elseif ($time_difference < 604800) {
        $days = floor($time_difference / 86400);
        return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
    } else {
        $weeks = floor($time_difference / 604800);
        return $weeks . ' week' . ($weeks > 1 ? 's' : '') . ' ago';
    }
}

/**
 * Paginate array results
 */
function paginate_array($items, $page = 1, $items_per_page = 10) {
    $total_items = count($items);
    $total_pages = ceil($total_items / $items_per_page);
    
    $page = max(1, min($page, $total_pages));
    $offset = ($page - 1) * $items_per_page;
    
    return [
        'items' => array_slice($items, $offset, $items_per_page),
        'current_page' => $page,
        'total_pages' => $total_pages,
        'total_items' => $total_items,
        'has_next' => $page < $total_pages,
        'has_prev' => $page > 1
    ];
}

/**
 * Validate CSRF token from POST/GET
 */
function validate_csrf() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $_SERVER['REQUEST_METHOD'] !== 'GET') {
        return true; // Not a state-changing request
    }
    
    $token = $_POST['csrf_token'] ?? $_GET['csrf_token'] ?? '';
    return SessionManager::validateCSRFToken($token);
}

/**
 * Generate HTML for CSRF token input
 */
function csrf_input() {
    $token = SessionManager::generateCSRFToken();
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
}
?>
