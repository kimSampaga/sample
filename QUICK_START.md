# QUICK START GUIDE

## 🚀 Getting Started in 3 Steps

### Step 1: Initialize Database
1. Make sure XAMPP is running (Apache + MySQL)
2. Open your browser and go to:
   ```
   http://localhost/inventorysystem/setup.php
   ```
3. Wait for the setup to complete. You should see green checkmarks.

### Step 2: Login
1. Go to: `http://localhost/inventorysystem/login.php`
2. Use one of the demo credentials:
   - **Admin**: username `admin`, password `admin123`
   - **User**: username `user`, password `user123`

### Step 3: Explore
- After login, you'll see the dashboard with user info and admin panel (if logged in as admin)
- Click "Logout" to end your session

---

## 📁 Important Files (What They Do)

| File | Purpose |
|------|---------|
| `login.php` | Beautiful login page (user goes here) |
| `process_login.php` | Handles login form submission |
| `logout.php` | Logs user out |
| `dashboard.php` | Main page after login (session-protected) |
| `config/session_handler.php` | Manages user sessions & timeouts |
| `config/db_config.php` | Database settings & constants |
| `setup.php` | Creates database tables & demo users |

---

## 🔐 Security Features Built-In

✓ **Password Hashing** - Bcrypt encryption  
✓ **SQL Injection Prevention** - Prepared statements  
✓ **Session Security** - HttpOnly cookies, auto-logout after 30 mins  
✓ **CSRF Protection** - Token validation on forms  
✓ **Failed Login Protection** - Locks account after 5 attempts  
✓ **Activity Logging** - Tracks login history  

---

## 🛠️ How to Protect Your Pages

Add this at the TOP of any PHP page you want to protect:

```php
<?php
require_once dirname(__FILE__) . '/config/auth_middleware.php';
AuthMiddleware::requireLogin();
$user = SessionManager::getUserData();
?>
```

For admin-only pages, also add:
```php
AuthMiddleware::requireAdmin();
```

---

## 🔑 How to Add Users

Use this code in a PHP file (or phpMyAdmin):

```php
<?php
require_once 'config/db_connect.php';

$username = 'newuser';
$email = 'user@example.com';
$password = 'userpassword123';
$first_name = 'John';
$last_name = 'Doe';
$role = 'user'; // or 'admin'

// Hash the password
$password_hash = password_hash($password, PASSWORD_BCRYPT);

// Insert into database
$stmt = $conn->prepare(
    'INSERT INTO users (username, email, password_hash, first_name, last_name, role, is_active) 
     VALUES (?, ?, ?, ?, ?, ?, 1)'
);

$stmt->bind_param('ssssss', $username, $email, $password_hash, $first_name, $last_name, $role);
$stmt->execute();

echo "User created successfully!";
?>
```

---

## 📋 Database Credentials

These are in `config/db_config.php`:
```
Database: inventory_system
Host: localhost
User: root
Password: (empty by default)
```

If your MySQL setup is different, update `db_config.php`.

---

## ⚙️ Configuration Options

Edit `config/db_config.php` to customize:

```php
// How long before session expires (in seconds)
define('SESSION_TIMEOUT', 1800); // 30 minutes

// How many failed login attempts before lockout
define('MAX_LOGIN_ATTEMPTS', 5);

// How long to lock account after too many attempts
define('LOGIN_ATTEMPT_TIMEOUT', 900); // 15 minutes
```

---

## 🐛 Troubleshooting

**"Connection failed" error?**
- Check if MySQL is running in XAMPP
- Verify database name in `db_config.php`

**Can't login with demo account?**
- Run setup.php again
- Check that users table exists in database

**Session expires too fast?**
- Increase `SESSION_TIMEOUT` in `db_config.php`
- For 1 hour: `define('SESSION_TIMEOUT', 3600);`

**Locked out after too many login attempts?**
- Wait 15 minutes for lockout to reset
- Or delete the user from `login_attempts` table

---

## 📚 Using the Provided Template

For creating new protected pages, copy `protected_page_template.php` and customize it.

It includes:
- ✓ Session protection
- ✓ Navigation bar with logout
- ✓ CSRF token for forms
- ✓ Example form
- ✓ User info display

---

## 🔒 Important Security Notes

1. **Change Demo Passwords**: Don't leave admin/user demo accounts in production
2. **Use HTTPS**: In production, set `SESSION_SECURE = true` in `db_config.php`
3. **Environment Variables**: Store passwords in `.env` files, not in code
4. **Regular Backups**: Back up your database regularly
5. **Keep Updated**: Keep PHP and MySQL updated

---

## 💡 Next Features to Add

- Password reset functionality
- User registration page
- Email verification
- Two-factor authentication
- User management admin panel
- Activity logging dashboard
- IP-based access restrictions

---

## 📞 Support

For issues or questions:
1. Check README.md for detailed documentation
2. Review security implementation in the code
3. Check demo pages for implementation examples

---

**You're all set! Start with login.php and explore the system.**
