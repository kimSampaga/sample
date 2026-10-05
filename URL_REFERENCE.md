# 🌐 COMPLETE URL REFERENCE & ACCESS GUIDE

## 📍 Quick URL Reference

### Public Pages (No Login Required)
```
Home/Welcome Page:
  http://localhost/inventorysystem/
  http://localhost/inventorysystem/index.php

Login Page:
  http://localhost/inventorysystem/login.php

Database Setup:
  http://localhost/inventorysystem/setup.php
  ⚠️ Restrict access after setup!
```

### Protected Pages (Login Required)
```
Dashboard:
  http://localhost/inventorysystem/dashboard.php
  ✅ Shows after successful login
  ✅ Requires valid session
  ✅ Shows user information
  ✅ Shows admin panel (if admin)

Protected Page Template:
  http://localhost/inventorysystem/protected_page_template.php
  📋 Use as template for new pages
  📋 Shows best practices
  📋 Copy and customize
```

### Form Submission Endpoints
```
Login Submission:
  POST to: http://localhost/inventorysystem/process_login.php
  From: login.php form
  Required: username, password, csrf_token

Logout Endpoint:
  GET: http://localhost/inventorysystem/logout.php
  Result: Session destroyed, redirect to login
  ✓ Clears all cookies
  ✓ Removes session data
```

---

## 🔐 Login Test URLs

### Using Demo Accounts
```
ADMIN ACCESS:
  URL:      http://localhost/inventorysystem/login.php
  Username: admin
  Password: admin123
  Click:    Login
  Result:   Dashboard with admin panel

REGULAR USER ACCESS:
  URL:      http://localhost/inventorysystem/login.php
  Username: user
  Password: user123
  Click:    Login
  Result:   Dashboard without admin panel

TEST FAILED LOGIN:
  URL:      http://localhost/inventorysystem/login.php
  Username: testadmin
  Password: wrongpassword
  Click:    Login
  Result:   Error message, try again

TEST LOCKED ACCOUNT:
  URL:      http://localhost/inventorysystem/login.php
  Username: admin
  Password: wrongpassword (5x)
  Click:    Login (6th time)
  Result:   "Too many attempts" message
  Wait:     15 minutes for unlock
```

---

## 📚 Documentation URLs

### README Files (Read These!)
```
Welcome/Overview:
  Location: c:/xampp/htdocs/inventorysystem/START_HERE.txt
  Open in:  Text editor or browser
  Time:     5 minutes
  Content:  System overview, demo accounts, next steps

Quick Start:
  File: QUICK_START.md
  Location: c:/xampp/htdocs/inventorysystem/QUICK_START.md
  Time: 10 minutes
  Content: 3-step setup guide, troubleshooting

Complete Guide:
  File: README.md
  Location: c:/xampp/htdocs/inventorysystem/README.md
  Time: 30 minutes
  Content: Everything about the system

System Architecture:
  File: ARCHITECTURE.md
  Location: c:/xampp/htdocs/inventorysystem/ARCHITECTURE.md
  Time: 20 minutes
  Content: System design, diagrams, flows

Implementation:
  File: IMPLEMENTATION_SUMMARY.md
  Location: c:/xampp/htdocs/inventorysystem/IMPLEMENTATION_SUMMARY.md
  Time: 15 minutes
  Content: What was created, features, capabilities

File Reference:
  File: FILES_REFERENCE.md
  Location: c:/xampp/htdocs/inventorysystem/FILES_REFERENCE.md
  Time: 15 minutes
  Content: Detailed file descriptions, dependencies

Checklist:
  File: CHECKLIST.md
  Location: c:/xampp/htdocs/inventorysystem/CHECKLIST.md
  Time: 10 minutes (doing the checks)
  Content: Setup verification, testing procedures
```

---

## 🛠️ Setup & Configuration

### Database Initialization
```
URL: http://localhost/inventorysystem/setup.php
Purpose: Create database and tables
Steps:
  1. Open URL in browser
  2. Wait for completion message
  3. See green checkmarks (✓)
  4. Note the demo credentials

Expected Results:
  ✓ Database 'inventory_system' created
  ✓ 4 tables created (users, login_attempts, login_logs, activity_logs)
  ✓ Demo users with hashed passwords
  ✓ All indexes created
  ✓ Permissions set correctly

Troubleshooting:
  Error: "Connection failed"
    → Check MySQL is running
    → Check database credentials in db_config.php
  
  Error: "Tables exist"
    → This is OK, tables are verified
    → Demo passwords updated
  
  Error: "Access denied"
    → Check MySQL user has CREATE permission
    → Verify username/password in db_config.php
```

### Configuration File Location
```
File: config/db_config.php
Path: c:/xampp/htdocs/inventorysystem/config/db_config.php

Editable Settings:
  - Database host/user/password
  - Database name
  - Session timeout
  - Login attempt limits
  - Security flags
  - User role names
```

---

## 👤 Demo Account Access

### Admin Account
```
LOGIN URL: http://localhost/inventorysystem/login.php

Credentials:
  Username: admin
  Password: admin123

After Login:
  Dashboard: http://localhost/inventorysystem/dashboard.php
  Shows: Admin panel with management links
  Access: Full system access

Can Do:
  ✓ View dashboard
  ✓ See admin panel
  ✓ Click admin features
  ✓ View all pages
  ✓ Manage settings (when added)

Cannot Do (until you add):
  ✗ Create users (need to add function)
  ✗ Delete users (need to add function)
  ✗ Manage products (need to add function)
  ✗ View reports (need to add function)
```

### Regular User Account
```
LOGIN URL: http://localhost/inventorysystem/login.php

Credentials:
  Username: user
  Password: user123

After Login:
  Dashboard: http://localhost/inventorysystem/dashboard.php
  Shows: Dashboard without admin panel
  Access: Limited system access

Can Do:
  ✓ View dashboard
  ✓ View personal info
  ✓ Access basic features (when created)

Cannot Do:
  ✗ Access admin panel
  ✗ See admin-only pages
  ✗ Perform admin functions
  ✗ Access settings (admin only)
```

---

## 🔐 Authentication Flow (URLs)

### Step-by-Step Login Process
```
1. USER VISITS LOGIN PAGE
   → http://localhost/inventorysystem/login.php
   ← Shows: Login form with username/password fields

2. USER FILLS FORM
   → Enters username: admin
   → Enters password: admin123
   → Clicks "Login" button

3. FORM SUBMITS
   → POST to: http://localhost/inventorysystem/process_login.php
   → Sends: username, password, csrf_token

4. PROCESS_LOGIN.PHP
   → Validates CSRF token
   → Calls LoginHandler::authenticate()
   → Hashes password & compares
   → Creates session if valid
   → Logs login attempt

5. SUCCESS - REDIRECT
   → Redirect to: http://localhost/inventorysystem/dashboard.php
   → Session created
   → Shows: Dashboard with user info

6. FAILURE - SHOW ERROR
   → Redirect to: http://localhost/inventorysystem/login.php?error=message
   → Session NOT created
   → Shows: Error message
   → User can try again

7. LOGOUT
   → User clicks "Logout" button
   → → GET: http://localhost/inventorysystem/logout.php
   → Destroys session
   → Clears cookies
   → Redirect to: http://localhost/inventorysystem/login.php?logout=1
   → Shows: Logout confirmation
```

---

## 📋 Protected Pages Access

### Accessing Protected Content
```
Any Protected Page (e.g., dashboard.php):

UNAUTHENTICATED ACCESS:
  User: Not logged in
  URL:  http://localhost/inventorysystem/dashboard.php (or any protected page)
  Load: page checks session
  → Session invalid/not found
  → Redirect to: http://localhost/inventorysystem/login.php
  → Shows: Login form
  
AUTHENTICATED ACCESS:
  User: Logged in (valid session)
  URL:  http://localhost/inventorysystem/dashboard.php
  Load: page checks session
  → Session found and valid
  → Not expired (< 30 min)
  → User data available
  → Shows: Protected content
  
SESSION TIMEOUT:
  User: Logged in but inactive
  Time: 30+ minutes passed
  URL:  http://localhost/inventorysystem/dashboard.php
  Load: page checks session
  → Session expired
  → Redirect to: http://localhost/inventorysystem/login.php?session_expired=1
  → Shows: Re-login form with timeout message

INVALID ROLE ACCESS:
  Admin: Regular user tries to access admin page
  URL:  http://localhost/inventorysystem/admin/settings.php (example)
  Load: page checks role
  → Role is 'user', not 'admin'
  → Redirect to: http://localhost/inventorysystem/dashboard.php
  → Shows: Access denied (implicitly)
```

---

## 🎯 Testing Scenarios & URLs

### Scenario 1: Successful Admin Login
```
Step 1: Visit login page
        http://localhost/inventorysystem/login.php
        
Step 2: Enter credentials
        Username: admin
        Password: admin123
        
Step 3: Click login
        Form submits to process_login.php
        
Step 4: See dashboard
        Redirected to: http://localhost/inventorysystem/dashboard.php
        Shows: Admin panel, user info, session details
```

### Scenario 2: Failed Login - Wrong Password
```
Step 1: Visit login page
        http://localhost/inventorysystem/login.php
        
Step 2: Enter wrong credentials
        Username: admin
        Password: wrongpassword
        
Step 3: Click login
        Form submits to process_login.php
        Validation fails
        
Step 4: See error
        Redirected to: http://localhost/inventorysystem/login.php?error=message
        Shows: "Invalid username or password"
        
Step 5: Try again
        Form clears
        User can retry
```

### Scenario 3: Account Lockout
```
Step 1: Visit login page
        http://localhost/inventorysystem/login.php
        
Step 2: Enter wrong credentials 5 times (click login 5x)
        Username: admin
        Password: wrongpassword
        Repeat 5 times
        
Step 3: 6th attempt shows lockout
        Click login (6th time)
        Redirected to: http://localhost/inventorysystem/login.php?error=message
        Shows: "Too many login attempts. Please try again in 15 minutes."
        
Step 4: Wait 15 minutes
        After 15 minutes, lockout resets
        Can login again
```

### Scenario 4: Session Timeout
```
Step 1: Login successfully
        http://localhost/inventorysystem/login.php
        Enter: admin / admin123
        Arrives at: http://localhost/inventorysystem/dashboard.php
        
Step 2: Wait 30+ minutes without activity
        
Step 3: Click any link or refresh
        http://localhost/inventorysystem/dashboard.php
        
Step 4: Session timeout trigger
        Check session age (30+ minutes)
        Session is expired
        
Step 5: Auto-redirect to login
        Redirected to: http://localhost/inventorysystem/login.php?session_expired=1
        Shows: "Your session has expired. Please log in again."
```

### Scenario 5: Regular User Access
```
Step 1: Login as regular user
        http://localhost/inventorysystem/login.php
        Enter: user / user123
        
Step 2: See dashboard
        Redirected to: http://localhost/inventorysystem/dashboard.php
        
Step 3: Notice: No admin panel
        Shows user dashboard only
        Admin features not visible
        
Step 4: Try to access admin page
        Go to: http://localhost/inventorysystem/admin/page.php
        (when admin pages exist)
        
Step 5: Access denied
        Redirected to: http://localhost/inventorysystem/dashboard.php
        Or shown error message
```

---

## 🔄 File Access Flow

### Access Sequence
```
Browser → index.php
    ↓
  User chooses action
    ↓
  login.php (public)
    ↓
  process_login.php (POST handler)
    ↓
  LoginHandler.php (authentication)
    ↓
  session_handler.php (session creation)
    ↓
  dashboard.php (protected page)
    ↓
  auth_middleware.php (access check)
    ↓
  SessionManager (session validation)
    ↓
  Protected content OR redirect to login
```

---

## 📂 File Access Permissions

### Public Access URLs
```
These are accessible WITHOUT login:

http://localhost/inventorysystem/             ← index.php
http://localhost/inventorysystem/index.php    ← index.php
http://localhost/inventorysystem/login.php    ← login.php
http://localhost/inventorysystem/setup.php    ← setup.php (restrict later!)

Form Submissions (POST):
  process_login.php (from login.php form)
  logout.php (from dashboard)
```

### Protected URLs
```
These require login (redirects to login if not authenticated):

http://localhost/inventorysystem/dashboard.php              ← All users
http://localhost/inventorysystem/protected_page_template.php ← All users

Admin-Only URLs (when created):
http://localhost/inventorysystem/admin/users.php           ← Admins only
http://localhost/inventorysystem/admin/settings.php        ← Admins only
http://localhost/inventorysystem/admin/reports.php         ← Admins only
```

### Non-Accessible URLs
```
These should NOT be accessed directly (they're included):

config/db_config.php              ← Include only
config/db_connect.php             ← Include only
config/session_handler.php        ← Include only
config/auth_middleware.php        ← Include only
config/helpers.php                ← Include only
classes/LoginHandler.php          ← Include only
database/schema.sql               ← Execute via setup.php
```

---

## 🧪 Testing All URLs Checklist

### Public Pages
- [ ] http://localhost/inventorysystem/ loads welcome page
- [ ] http://localhost/inventorysystem/index.php loads welcome page
- [ ] http://localhost/inventorysystem/login.php shows login form
- [ ] http://localhost/inventorysystem/setup.php shows setup script

### Authentication
- [ ] Login with admin/admin123 works
- [ ] Login with user/user123 works
- [ ] Login with wrong password fails
- [ ] 5 wrong attempts lock account
- [ ] Logout clears session

### Protected Pages
- [ ] Dashboard accessible after login
- [ ] Dashboard not accessible without login
- [ ] Dashboard shows user info
- [ ] Dashboard shows admin panel (if admin)
- [ ] Admin panel not visible to users

### Redirects
- [ ] Wrong password redirects with error
- [ ] Session timeout redirects to login
- [ ] Logout redirects to login
- [ ] Direct access to protected page redirects to login
- [ ] Failed CSRF redirects appropriately

---

## 📊 Status Codes Expected

### Successful Requests
```
200 OK
  Pages load successfully
  
302 Found (Redirect)
  Login successful → redirect to dashboard
  Logout → redirect to login
  Session timeout → redirect to login
  Unauthorized → redirect to login
```

### Error Requests
```
403 Forbidden
  Trying to access admin page as user
  Invalid CSRF token
  
404 Not Found
  URL doesn't exist
  File not found
  
500 Internal Server Error
  Database connection failed
  PHP syntax error
  (Check error logs!)
```

---

## 💡 URL Tips & Tricks

### Creating Bookmarks
```
For Testing:
  Bookmark: http://localhost/inventorysystem/login.php
  Bookmark: http://localhost/inventorysystem/setup.php
  
For Development:
  Bookmark: http://localhost/inventorysystem/protected_page_template.php
  Bookmark: http://localhost/inventorysystem/README.md
```

### Testing Different Scenarios
```
After login, test timeouts:
  1. Login to dashboard
  2. Wait 30+ minutes without activity
  3. Click any link
  4. Should redirect to login with timeout message

Testing failed logins:
  1. Go to login page
  2. Try wrong password 5 times
  3. 6th attempt shows lockout
  4. Lockout lasts 15 minutes

Testing roles:
  1. Login as admin - see admin panel
  2. Logout completely
  3. Login as user - no admin panel
  4. Try to access /admin/* pages - redirected
```

---

## 🚀 Development URLs

### During Development
```
File Editor:
  Open: c:/xampp/htdocs/inventorysystem/
  Edit: Any .php file
  Test: Reload browser at corresponding URL

Database Browser:
  phpMyAdmin: http://localhost:8080/phpmyadmin/
  Select: inventory_system
  Browse: users, login_attempts, login_logs

Error Logs:
  PHP Errors: c:/xampp/logs/php_error.log
  MySQL Errors: c:/xampp/mysql/data/
  App Logs: c:/xampp/htdocs/inventorysystem/logs/

Documentation:
  Local: Open .md files in VS Code or browser
  GitHub: (If you push to repository)
```

---

## ✅ Complete URL Reference Summary

| Page | URL | Type | Auth | Purpose |
|------|-----|------|------|---------|
| Welcome | /index.php | GET | No | Homepage |
| Login | /login.php | GET | No | Login form |
| Process Login | /process_login.php | POST | No | Form handler |
| Dashboard | /dashboard.php | GET | Yes | Main page |
| Logout | /logout.php | GET | Yes | Session cleanup |
| Setup | /setup.php | GET | No | DB init |
| Template | /protected_page_template.php | GET | Yes | Example page |

---

**All URLs are now mapped and ready for testing!**

**Start here**: http://localhost/inventorysystem/
