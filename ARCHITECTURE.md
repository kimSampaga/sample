# System Architecture & File Structure

## 📁 Complete Directory Structure

```
inventorysystem/
│
├── 📄 LOGIN FILES (Entry point)
│   ├── login.php                      # Beautiful login page (public)
│   ├── process_login.php              # Handles login form submission
│   └── logout.php                     # Logout handler
│
├── 📄 MAIN PAGES (Protected)
│   ├── dashboard.php                  # Main dashboard (requires login)
│   └── protected_page_template.php    # Template for creating secure pages
│
├── 📁 config/ (Configuration & Core)
│   ├── db_config.php                  # Database credentials & constants
│   ├── db_connect.php                 # Database connection class
│   ├── session_handler.php            # Session management (CORE)
│   ├── auth_middleware.php            # Authorization & permissions
│   └── helpers.php                    # Utility functions
│
├── 📁 classes/ (Business Logic)
│   └── LoginHandler.php               # Authentication logic
│
├── 📁 database/
│   └── schema.sql                     # Database schema & demo data
│
├── 📁 logs/ (Auto-created)
│   └── error.log                      # Error logging
│
├── 📄 SETUP & DOCUMENTATION
│   ├── setup.php                      # Database initialization
│   ├── README.md                      # Complete documentation
│   └── QUICK_START.md                 # Quick reference guide
│
└── .htaccess (Optional - for Apache)
```

---

## 🔄 User Authentication Flow

```
┌─────────────┐
│   User      │
│   Browser   │
└──────┬──────┘
       │
       ├─→ GET /login.php
       │   ↓
       ├─→ Shows Login Form (login.php)
       │   • Username input
       │   • Password input
       │   • Remember me checkbox
       │   • CSRF token (hidden)
       │
       ├─→ User fills form and clicks Login
       │   ↓
       ├─→ POST to /process_login.php
       │   │
       │   ├─→ Validate inputs
       │   ├─→ Check CSRF token
       │   ├─→ Call LoginHandler::authenticate()
       │   │   │
       │   │   ├─→ Check account lockout
       │   │   ├─→ Query database
       │   │   ├─→ Verify password hash
       │   │   ├─→ Check account active
       │   │   ├─→ Log login attempt
       │   │   └─→ Return success/error
       │   │
       │   ├─→ IF LOGIN SUCCESSFUL:
       │   │   ├─→ Set user session data
       │   │   ├─→ Regenerate session ID
       │   │   ├─→ Create login log entry
       │   │   ├─→ Set remember-me cookie (optional)
       │   │   └─→ Redirect to dashboard.php
       │   │
       │   └─→ IF LOGIN FAILED:
       │       ├─→ Log failed attempt
       │       ├─→ Check lockout status
       │       └─→ Redirect to login with error message
       │
       ├─→ GET /dashboard.php
       │   ├─→ Include session_handler.php
       │   ├─→ Check SessionManager::isLoggedIn()
       │   │   ├─→ If NOT logged in → Redirect to login.php
       │   │   └─→ If logged in → Show dashboard
       │   │       ├─→ Display user info
       │   │       ├─→ Show stats cards
       │   │       ├─→ Show admin panel (if admin)
       │   │       └─→ Show session info
       │   │
       │   └─→ Auto-logout after 30 minutes
       │       (or custom SESSION_TIMEOUT)
       │
       └─→ User clicks Logout
           │
           ├─→ GET /logout.php
           │   ├─→ Destroy session
           │   ├─→ Clear cookies
           │   └─→ Redirect to login.php with logout message
           │
           └─→ Session ends, user must login again
```

---

## 🔐 Security Layers

### Layer 1: Session Protection
```
Session Handler (session_handler.php)
├─ Secure cookie configuration
│  ├─ HttpOnly: true (prevent XSS)
│  ├─ SameSite: Strict (prevent CSRF)
│  └─ Secure: true (HTTPS only, for production)
├─ Session timeout (30 minutes default)
├─ Session regeneration after login
└─ Automatic logout on timeout
```

### Layer 2: Password Security
```
Password Storage
├─ Bcrypt hashing (algorithm: PASSWORD_BCRYPT)
├─ Automatic salt generation
├─ Cost factor: 10
└─ Resistant to rainbow table attacks
```

### Layer 3: Database Security
```
LoginHandler (LoginHandler.php)
├─ Prepared statements (prevent SQL injection)
├─ Parameter binding (all user input)
├─ Account status checking
├─ Failed login tracking (5 attempts max)
└─ Login logging (IP, user agent, timestamp)
```

### Layer 4: Form Security
```
CSRF Protection
├─ Token generation per session
├─ Token validation on POST
└─ Token stored in session
```

### Layer 5: Access Control
```
AuthMiddleware (auth_middleware.php)
├─ Login requirement enforcement
├─ Role-based access control (Admin/User)
├─ Activity logging
└─ Permission checking
```

---

## 📊 Data Flow Diagram

```
LOGIN PROCESS:
═════════════

User Input (login.php)
    ↓
validate csrf_token
    ↓
Sanitize & Trim Input
    ↓
Check for Account Lockout
    ↓
Query Database (Prepared Statement)
    ↓
User Found?
    ├─ YES: Verify Password Hash
    │   ├─ Correct: Set Session → Log Login → Redirect to Dashboard ✓
    │   └─ Wrong: Increment Failed Attempts → Show Error ✗
    │
    └─ NO: Log Failed Attempt → Show Error ✗


SESSION CHECK ON PAGE LOAD:
═══════════════════════════

Load page.php
    ↓
Include session_handler.php
    ↓
SessionManager::initSession()
    ├─ Session already exists?
    │   └─ YES: Verify session timeout
    │       ├─ NOT expired: Continue
    │       └─ EXPIRED: Destroy session → Redirect to login
    │
    └─ NO: Create new session

Check SessionManager::isLoggedIn()
    ├─ YES: Retrieve user data from $_SESSION
    │   └─ Display protected content
    │
    └─ NO: Redirect to login.php


LOGOUT PROCESS:
═══════════════

Click Logout Button
    ↓
POST to logout.php
    ↓
SessionManager::destroySession()
    ├─ Clear $_SESSION array
    ├─ Delete session cookie
    └─ Destroy session file
    ↓
Delete remember_me cookie
    ↓
Redirect to login.php with message
    ↓
User must login again
```

---

## 🗄️ Database Schema

### Users Table
```
users
├── id (INT, PK)              # User ID
├── username (VARCHAR, UNIQUE) # Login username
├── email (VARCHAR, UNIQUE)   # User email
├── password_hash (VARCHAR)   # Bcrypt hash
├── first_name (VARCHAR)      # First name
├── last_name (VARCHAR)       # Last name
├── role (ENUM)               # 'admin' or 'user'
├── is_active (BOOLEAN)       # Account enabled?
├── created_at (TIMESTAMP)    # Account creation
├── updated_at (TIMESTAMP)    # Last update
└── last_login (TIMESTAMP)    # Last login time
```

### Login Attempts Table
```
login_attempts
├── username (VARCHAR, PK)        # Username attempting login
├── failed_attempts (INT)         # Count of failures
└── last_failed_attempt (TIMESTAMP) # When
```

### Login Logs Table
```
login_logs
├── id (INT, PK)
├── user_id (INT, FK)
├── ip_address (VARCHAR)
├── user_agent (VARCHAR)
├── login_time (TIMESTAMP)
└── logout_time (TIMESTAMP)
```

### Activity Logs Table
```
activity_logs
├── id (INT, PK)
├── user_id (INT, FK)
├── action (VARCHAR)        # What happened
├── description (TEXT)      # Details
├── ip_address (VARCHAR)    # From where
└── created_at (TIMESTAMP)
```

---

## 🎯 Key Classes & Their Responsibilities

### SessionManager (session_handler.php)
```
Purpose: Manage user sessions securely
├─ initSession()          → Start secure session
├─ isLoggedIn()          → Check login status
├─ setUserSession()      → Create session after login
├─ destroySession()      → Logout & cleanup
├─ checkSessionTimeout() → Validate session age
├─ getUserData()         → Get current user info
├─ isAdmin()            → Check if admin
├─ generateCSRFToken()  → Create CSRF token
└─ validateCSRFToken()  → Verify CSRF token
```

### LoginHandler (classes/LoginHandler.php)
```
Purpose: Handle user authentication
├─ authenticate()        → Main login logic
├─ isLockedOut()        → Check account lockout
├─ logFailedAttempt()   → Record failed login
├─ clearFailedAttempts()→ Reset login attempt counter
└─ logLogin()           → Record successful login
```

### AuthMiddleware (config/auth_middleware.php)
```
Purpose: Enforce authentication & authorization
├─ requireLogin()       → Enforce login requirement
├─ requireAdmin()       → Enforce admin role
├─ requireRole()        → Check specific role
├─ validateCSRF()       → Check CSRF token
├─ getCSRFToken()       → Get token for forms
└─ logActivity()        → Log user actions
```

### Database (config/db_connect.php)
```
Purpose: Manage database connection
├─ __construct()        → Connect to database
├─ getConnection()      → Return connection object
├─ escapeString()       → Escape strings safely
└─ __destruct()         → Close connection
```

---

## 🔑 Important Constants

Located in `config/db_config.php`:

```php
// Database
DB_HOST = 'localhost'
DB_USER = 'root'  
DB_PASSWORD = ''
DB_NAME = 'inventory_system'

// Session
SESSION_TIMEOUT = 1800              # 30 minutes
SESSION_SECURE = false              # true for HTTPS
SESSION_HTTPONLY = true             # Always true

// Security
PASSWORD_MIN_LENGTH = 8
MAX_LOGIN_ATTEMPTS = 5
LOGIN_ATTEMPT_TIMEOUT = 900         # 15 minutes

// Roles
ROLE_ADMIN = 'admin'
ROLE_USER = 'user'
```

---

## 🚀 Integration Steps for New Pages

### Step 1: Protect the Page
Add at the very top of any `.php` file:
```php
<?php
require_once dirname(__FILE__) . '/config/auth_middleware.php';
AuthMiddleware::requireLogin();
?>
```

### Step 2: Use Session Data
```php
<?php
$user = SessionManager::getUserData();
echo "Welcome, " . $user['first_name'];
?>
```

### Step 3: Add CSRF Protection to Forms
```html
<form method="POST" action="process.php">
    <?php echo csrf_input(); ?>
    <!-- form fields -->
</form>
```

### Step 4: Admin-Only Pages
```php
<?php
AuthMiddleware::requireAdmin();
// ... admin only code
?>
```

---

## 📝 Testing Checklist

- [ ] Setup.php runs without errors
- [ ] Demo users exist in database
- [ ] Can login with correct credentials
- [ ] Cannot login with wrong password
- [ ] Session created after login
- [ ] Dashboard displays user info
- [ ] Logout works properly
- [ ] Session expires after timeout
- [ ] Account lockout works (5 attempts)
- [ ] CSRF token validates on forms
- [ ] Admin sees admin panel
- [ ] User doesn't see admin panel

---

## 🔧 Deployment Checklist

- [ ] Change demo account passwords
- [ ] Enable HTTPS
- [ ] Set SESSION_SECURE = true
- [ ] Set up database backups
- [ ] Configure error logging
- [ ] Remove setup.php or make it inaccessible
- [ ] Set up monitoring
- [ ] Test all pages thoroughly
- [ ] Review security settings
- [ ] Set up access logs

---

This architecture provides:
✓ **Modular design** - Easy to extend
✓ **Security first** - Multiple protection layers
✓ **Session management** - Automatic timeouts
✓ **Role-based access** - Admin/User separation
✓ **Activity tracking** - Audit trail
✓ **Scalability** - Ready for growth
