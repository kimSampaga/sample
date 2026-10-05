<?php
/**
 * Protected Page Template
 * Use this as a template for creating protected pages
 * Copy and modify as needed
 */

require_once dirname(__FILE__) . '/config/auth_middleware.php';

// Require user to be logged in
AuthMiddleware::requireLogin();

// Get current user
$user = SessionManager::getUserData();

// For this example page, require admin
// Uncomment the line below to restrict to admin only:
// AuthMiddleware::requireAdmin();

// Get CSRF token for forms
$csrf_token = AuthMiddleware::getCSRFToken();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Protected Page - Inventory Management System</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f5f5;
        }

        .navbar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .navbar-brand {
            font-size: 24px;
            font-weight: bold;
        }

        .user-menu {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .logout-btn {
            background: #ff6b6b;
            padding: 8px 15px;
            border-radius: 5px;
            border: none;
            color: white;
            cursor: pointer;
            text-decoration: none;
            font-size: 14px;
            transition: background 0.3s;
        }

        .logout-btn:hover {
            background: #ff5252;
        }

        .container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .page-header {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }

        .page-header h1 {
            color: #333;
            margin-bottom: 10px;
        }

        .page-header p {
            color: #666;
            font-size: 14px;
        }

        .content-section {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 600;
            font-size: 14px;
        }

        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }

        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            font-size: 14px;
            cursor: pointer;
            transition: background 0.3s;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-primary:hover {
            opacity: 0.9;
        }

        .btn-secondary {
            background: #ddd;
            color: #333;
        }

        .btn-secondary:hover {
            background: #ccc;
        }

        .alert {
            padding: 12px;
            margin-bottom: 20px;
            border-radius: 5px;
            font-size: 14px;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        @media (max-width: 768px) {
            .navbar {
                flex-direction: column;
                gap: 15px;
            }

            .container {
                padding: 0 15px;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar">
        <div class="navbar-brand">📄 Protected Page</div>
        <div class="user-menu">
            <span>👤 <?php echo htmlspecialchars($user['first_name'] ?? $user['username']); ?></span>
            <a href="logout.php" class="logout-btn">🚪 Logout</a>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container">
        <!-- Page Header -->
        <div class="page-header">
            <h1>Protected Page Example</h1>
            <p>This page is only accessible to logged-in users. You are currently logged in as <strong><?php echo htmlspecialchars($user['username']); ?></strong>.</p>
        </div>

        <!-- Content Section -->
        <div class="content-section">
            <h2>Welcome!</h2>
            <p>This is a template for a protected page. Modify this as needed for your application.</p>

            <!-- Example Form -->
            <form method="POST" action="process_example.php" style="margin-top: 30px;">
                <div class="form-group">
                    <label for="name">Name</label>
                    <input type="text" id="name" name="name" placeholder="Enter your name" required>
                </div>

                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" placeholder="Enter your email" required>
                </div>

                <div class="form-group">
                    <label for="message">Message</label>
                    <textarea id="message" name="message" placeholder="Enter your message"></textarea>
                </div>

                <!-- CSRF Token (Required for form security) -->
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">

                <div>
                    <button type="submit" class="btn btn-primary">Submit</button>
                    <button type="reset" class="btn btn-secondary">Clear</button>
                </div>
            </form>

            <!-- User Information -->
            <div style="margin-top: 40px; padding: 20px; background: #f9f9f9; border-radius: 5px;">
                <h3>Your Information</h3>
                <p><strong>Username:</strong> <?php echo htmlspecialchars($user['username']); ?></p>
                <p><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
                <p><strong>Role:</strong> <?php echo strtoupper($user['role']); ?></p>
                <p><strong>Full Name:</strong> <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></p>
            </div>
        </div>
    </div>
</body>
</html>
