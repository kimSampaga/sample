<?php
/**
 * Database Setup Script
 * Run this once to initialize the database with tables and default users
 * 
 * Usage: Access http://localhost/inventorysystem/setup.php in your browser
 */

require_once dirname(__FILE__) . '/config/db_config.php';

// Simple database setup
$servername = DB_HOST;
$username = DB_USER;
$password = DB_PASSWORD;

// Create connection to MySQL server (without database selected)
$conn = new mysqli($servername, $username, $password);

if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}

echo '<h2>Inventory Management System - Database Setup</h2>';

// Step 1: Create database
$create_db = 'CREATE DATABASE IF NOT EXISTS ' . DB_NAME;
if ($conn->query($create_db) === TRUE) {
    echo '<p style="color: green;">✓ Database created/verified successfully.</p>';
} else {
    echo '<p style="color: red;">✗ Error creating database: ' . $conn->error . '</p>';
}

// Select database
$conn->select_db(DB_NAME);

// Read and execute schema file
$schema_file = dirname(__FILE__) . '/database/schema.sql';
if (file_exists($schema_file)) {
    $sql = file_get_contents($schema_file);
    
    if ($conn->multi_query($sql) === TRUE) {
        echo '<p style="color: green;">✓ Tables created/verified successfully.</p>';
    } else {
        echo '<p style="color: red;">✗ Error creating tables: ' . $conn->error . '</p>';
    }
    
    // Clear results
    while ($conn->more_results() && $conn->next_result()) {}
} else {
    echo '<p style="color: red;">✗ Schema file not found.</p>';
}

// Step 3: Verify tables exist
$tables = [];
$result = $conn->query("SHOW TABLES");
if ($result) {
    while ($row = $result->fetch_row()) {
        $tables[] = $row[0];
    }
    echo '<p style="color: green;">✓ Tables in database: ' . implode(', ', $tables) . '</p>';
}

// Step 4: Verify admin user exists
$admin_check = $conn->query("SELECT COUNT(*) as count FROM users WHERE username = 'admin'");
$admin_result = $admin_check->fetch_assoc();

if ($admin_result['count'] > 0) {
    echo '<p style="color: green;">✓ Admin user exists.</p>';
} else {
    echo '<p style="color: orange;">⚠ Admin user not found. You need to create users manually.</p>';
}

// Step 5: Update demo user passwords with proper hashes
$admin_password = password_hash('admin123', PASSWORD_BCRYPT);
$user_password = password_hash('user123', PASSWORD_BCRYPT);

$conn->query("UPDATE users SET password_hash = '$admin_password' WHERE username = 'admin'");
$conn->query("UPDATE users SET password_hash = '$user_password' WHERE username = 'user'");

echo '<p style="color: green;">✓ Demo user passwords updated.</p>';

echo '<hr>';
echo '<h3>Setup Complete!</h3>';
echo '<p><strong>Demo Login Credentials:</strong></p>';
echo '<ul>';
echo '<li><strong>Admin:</strong> Username: <code>admin</code>, Password: <code>admin123</code></li>';
echo '<li><strong>User:</strong> Username: <code>user</code>, Password: <code>user123</code></li>';
echo '</ul>';
echo '<p><a href="login.php">Click here to proceed to login page</a></p>';

$conn->close();
?>

<style>
body {
    font-family: Arial, sans-serif;
    max-width: 800px;
    margin: 50px auto;
    padding: 20px;
    background: #f5f5f5;
}

h2, h3 {
    color: #333;
}

p {
    font-size: 14px;
    line-height: 1.6;
}

code {
    background: #f0f0f0;
    padding: 2px 5px;
    border-radius: 3px;
}

hr {
    margin: 30px 0;
    border: none;
    border-top: 1px solid #ddd;
}

a {
    color: #667eea;
    text-decoration: none;
}

a:hover {
    text-decoration: underline;
}

ul {
    margin: 10px 0;
    padding-left: 20px;
}

li {
    margin: 10px 0;
}
</style>
