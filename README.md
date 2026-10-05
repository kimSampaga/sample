# Inventory Management System - Secure Login & Session Management

## 🔐 System Overview

This is a complete, production-ready login system with secure session-based authentication for the Inventory Management System. It includes admin and user roles, session timeouts, failed login tracking, and comprehensive security measures.

## 📁 Project Structure

```
inventorysystem/
├── config/
│   ├── db_config.php          # Database configuration and constants
│   ├── db_connect.php         # Database connection class
│   └── session_handler.php    # Session management and security
├── classes/
│   └── LoginHandler.php       # Authentication logic
├── database/
│   └── schema.sql             # Database schema with tables
├── login.php                  # Login page (HTML/CSS)
├── process_login.php          # Login form processor
├── logout.php                 # Logout handler
├── dashboard.php              # Protected dashboard page
├── setup.php                  # Database initialization script
└── README.md                  # This file
```

## ✨ Features

### Security Features
- **Password Hashing**: Uses bcrypt for secure password storage
- **SQL Injection Prevention**: Prepared statements with parameterized queries
- **Session Security**: HTTPS-ready with HttpOnly and SameSite cookies
- **CSRF Protection**: Token generation and validation
- **Session Timeout**: Auto-logout after 30 minutes (configurable)
- **Failed Login Tracking**: Account lockout after 5 failed attempts
- **Session Regeneration**: New ID generated after login
- **Input Validation**: All user inputs validated and sanitized

### Authentication Features
- **Secure Login**: Username/password authentication
- **Remember Me**: Optional persistent login (14 days)
- **Role-Based Access**: Admin and User roles
- **Account Status**: Active/Inactive user accounts
- **Login Logging**: Track login attempts with IP and user agent
- **Session Management**: Track login time and expiration

## 🚀 Quick Start

### 1. Initial Setup
Access the setup page to create tables and demo users:
```
http://localhost/inventorysystem/setup.php
```

### 2. Database Configuration
Edit `config/db_config.php` to match your MySQL setup:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'inventory_system');
```

### 3. Demo Login Credentials
After setup, you can login with:
- **Admin User**: `admin` / `admin123`
- **Regular User**: `user` / `user123`

## 📄 File Descriptions

### Configuration Files

#### `config/db_config.php`
Central configuration file with all constants:
- Database connection details
- Session timeout (30 minutes)
- Password requirements
- Login attempt limits (5 attempts, 15-minute lockout)
- User roles

#### `config/db_connect.php`
Database connection class using MySQLi:
- Connection management
- String escaping for security
- UTF-8 charset support

#### `config/session_handler.php`
Session management class with features:
- Secure session initialization
- Session timeout checking
- CSRF token generation/validation
- User data retrieval
- Role-based access checks

### Authentication Files

#### `classes/LoginHandler.php`
Handles user authentication:
- Password verification using bcrypt
- Failed login tracking
- Account lockout logic
- Login logging
- Account status validation

#### `login.php`
Beautiful, responsive login page:
- Modern gradient design
- Form validation
- Error/success messages
- Demo credentials display
- Mobile responsive

#### `process_login.php`
Form processor that:
- Validates input
- Calls authentication
- Creates session
- Handles "Remember Me"
- Redirects to dashboard

#### `logout.php`
Logout handler that:
- Destroys session completely
- Clears cookies
- Removes login attempt records
- Redirects to login page

#### `dashboard.php`
Protected dashboard page:
- Session validation
- User welcome message
- Role-based content
- Admin panel (admin only)
- Session information display
- Stats cards

### Database

#### `database/schema.sql`
SQL schema creating:
- `users`: User accounts and profiles
- `login_attempts`: Failed login tracking
- `login_logs`: Login history
- `products`: Inventory products
- `activity_logs`: System activity tracking

#### `setup.php`
Database initialization script:
- Creates database if needed
- Executes schema
- Creates demo users
- Hashes demo passwords
- Provides status feedback

## 🔒 Security Implementation Details

### Password Security
```php
// Passwords are hashed using bcrypt (PHP 8.1+ standard)
$hashed = password_hash($password, PASSWORD_BCRYPT);

// Verification
$verified = password_verify($input_password, $hashed);
```

### SQL Injection Prevention
```php
// Using prepared statements
$stmt = $conn->prepare('SELECT * FROM users WHERE username = ?');
$stmt->bind_param('s', $username);
$stmt->execute();
```

### Session Security
```php
// HttpOnly cookies prevent JavaScript access
// SameSite=Strict prevents CSRF attacks
// Session regeneration after login
session_regenerate_id(true);
```

### Failed Login Protection
```php
// Tracks failed attempts per username
// Locks account after 5 failed attempts
// Auto-resets after 15 minutes
```

## 🛠️ Configuration Options

Edit `config/db_config.php` to customize:

```php
// Session timeout (in seconds)
define('SESSION_TIMEOUT', 1800); // 30 minutes

// Login attempt limits
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_ATTEMPT_TIMEOUT', 900); // 15 minutes

// HTTPS support (when deploying)
define('SESSION_SECURE', true); // Only send cookies over HTTPS
```

## 📊 Database Schema

### Users Table
```
id (INT) - Primary key
username (VARCHAR) - Unique username
email (VARCHAR) - User email
password_hash (VARCHAR) - Bcrypt hashed password
first_name (VARCHAR) - User first name
last_name (VARCHAR) - User last name
role (ENUM) - 'admin' or 'user'
is_active (BOOLEAN) - Account status
created_at (TIMESTAMP)
updated_at (TIMESTAMP)
last_login (TIMESTAMP)
```

### Login Attempts Table
```
username (VARCHAR) - User attempting login
failed_attempts (INT) - Count of failures
last_failed_attempt (TIMESTAMP)
```

### Login Logs Table
```
id (INT) - Primary key
user_id (INT) - User who logged in
ip_address (VARCHAR) - IP address
user_agent (VARCHAR) - Browser info
login_time (TIMESTAMP)
logout_time (TIMESTAMP)
```

## 🔐 Usage Examples

### Check if User is Logged In
```php
require_once 'config/session_handler.php';

if (SessionManager::isLoggedIn()) {
    $user = SessionManager::getUserData();
    echo "Welcome, " . $user['first_name'];
}
```

### Protect a Page
```php
require_once 'config/session_handler.php';

if (!SessionManager::isLoggedIn()) {
    header('Location: login.php');
    exit;
}
```

### Check Admin Access
```php
if (!SessionManager::isAdmin()) {
    header('Location: dashboard.php');
    exit;
}
```

### Generate CSRF Token
```php
$token = SessionManager::generateCSRFToken();
echo '<input type="hidden" name="csrf_token" value="' . $token . '">';
```

### Validate CSRF Token
```php
if (!SessionManager::validateCSRFToken($_POST['csrf_token'])) {
    die('CSRF token validation failed');
}
```

## 🚨 Common Issues & Solutions

### "Connection failed" Error
- Check MySQL is running
- Verify credentials in `db_config.php`
- Ensure database exists

### "Access Denied" on Login
- check password hash was properly updated in setup
- Verify user exists in database

### Session Expires Too Quickly
- Increase `SESSION_TIMEOUT` in `db_config.php`
- Check server's session.gc_maxlifetime setting

### "Too many login attempts" Error
- Wait 15 minutes for lockout to reset
- Or check `login_attempts` table in database

## 📝 Creating New Users

Insert into database:
```php
$password_hash = password_hash('newpassword', PASSWORD_BCRYPT);

$sql = "INSERT INTO users (username, email, password_hash, first_name, last_name, role, is_active) 
        VALUES ('newuser', 'email@example.com', '$password_hash', 'John', 'Doe', 'user', 1)";
$conn->query($sql);
```

## 🔄 Adding to Existing Pages

To protect any page, add at the top:
```php
<?php
require_once dirname(__FILE__) . '/config/session_handler.php';

if (!SessionManager::isLoggedIn()) {
    header('Location: login.php');
    exit;
}
?>
```

## 📋 Testing Checklist

- [ ] Setup script runs successfully
- [ ] Admin can login with correct password
- [ ] Regular user can login with correct password
- [ ] Invalid password shows error
- [ ] Wrong username shows error
- [ ] Session expires after 30 minutes
- [ ] Logout destroys session
- [ ] Admin sees admin panel on dashboard
- [ ] Regular user doesn't see admin panel
- [ ] Failed logins lock account after 5 attempts
- [ ] Account unlocks after 15 minutes

## 🔧 Next Steps

1. **Customize Demo Users**: Change demo passwords or remove them
2. **Add Encryption**: Enable HTTPS and set SESSION_SECURE = true
3. **Create Admin Panel**: Add user management features
4. **Add Password Reset**: Implement forgot password functionality
5. **Email Verification**: Send verification emails on registration
6. **Two-Factor Authentication**: Add 2FA for admin accounts
7. **Activity Logging**: Log all user actions
8. **IP Whitelisting**: Restrict access by IP address

## 📚 Additional Resources

- PHP MySQLi Documentation: https://www.php.net/manual/en/book.mysqli.php
- Password Hashing: https://www.php.net/manual/en/function.password-hash.php
- Session Security: https://www.php.net/manual/en/session.security.php
- OWASP Authentication: https://owasp.org/www-project-authentication-cheat-sheet/

## ⚠️ Production Deployment Checklist

- [ ] Use HTTPS for all pages
- [ ] Set SESSION_SECURE = true
- [ ] Change demo credentials
- [ ] Set up proper error logging
- [ ] Configure database backups
- [ ] Set up rate limiting
- [ ] Enable CSRF protection on all forms
- [ ] Use environment variables for credentials
- [ ] Set up monitoring and alerts
- [ ] Regular security audits

---

**Version**: 1.0  
**Last Updated**: March 4, 2026  
**License**: MIT
