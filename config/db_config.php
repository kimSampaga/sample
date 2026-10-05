<?php
/**
 * Database Configuration File
 * Stores all database connection parameters
 */

// Database credentials
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'inventory_system');

// App settings
define('APP_NAME', 'Inventory Management System');
define('APP_URL', 'http://localhost/inventorysystem');

// Session settings
define('SESSION_TIMEOUT', 1800); // 30 minutes in seconds
define('SESSION_SECURE', false); // Set to true if using HTTPS
define('SESSION_HTTPONLY', true);

// Security settings
define('PASSWORD_MIN_LENGTH', 8);
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_ATTEMPT_TIMEOUT', 900); // 15 minutes in seconds

// User roles
define('ROLE_ADMIN', 'admin');
define('ROLE_USER', 'user');
?>
