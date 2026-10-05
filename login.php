<?php
/**
 * Login Page
 * User authentication form
 */

session_start();

// Initialize message variables
$alert_message = '';
$alert_type = '';

// Check for session expired message
if (isset($_GET['session_expired'])) {
    $alert_message = 'Your session has expired. Please log in again.';
    $alert_type = 'warning';
}

// Check for logout message
if (isset($_GET['logout'])) {
    $alert_message = 'You have been successfully logged out.';
    $alert_type = 'success';
}

// Check for error message
if (isset($_GET['error'])) {
    $alert_message = htmlspecialchars($_GET['error']);
    $alert_type = 'danger';
}

// Check for success message
if (isset($_GET['success'])) {
    $alert_message = htmlspecialchars($_GET['success']);
    $alert_type = 'success';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Inventory Management System</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .login-container {
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 400px;
        }

        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .login-header h1 {
            color: #333;
            font-size: 28px;
            margin-bottom: 10px;
        }

        .login-header p {
            color: #666;
            font-size: 14px;
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

        .form-group input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
            transition: border-color 0.3s;
        }

        .form-group input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .remember-me {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }

        .remember-me input[type="checkbox"] {
            margin-right: 8px;
            cursor: pointer;
        }

        .remember-me label {
            margin-bottom: 0;
            font-weight: 400;
            cursor: pointer;
        }

        .login-btn {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s;
        }

        .login-btn:hover {
            transform: translateY(-2px);
        }

        .login-btn:active {
            transform: translateY(0);
        }

        .alert {
            padding: 12px;
            margin-bottom: 20px;
            border-radius: 5px;
            font-size: 14px;
        }

        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .alert-warning {
            background-color: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }

        .footer p {
            color: #666;
            font-size: 12px;
        }

        .demo-credentials {
            background-color: #f0f0f0;
            padding: 15px;
            border-radius: 5px;
            margin-top: 20px;
            border-left: 4px solid #667eea;
        }

        .demo-credentials p {
            color: #333;
            font-size: 12px;
            margin-bottom: 5px;
        }

        .demo-credentials strong {
            color: #667eea;
        }

        @media (max-width: 480px) {
            .login-container {
                margin: 20px;
                padding: 30px 20px;
            }

            .login-header h1 {
                font-size: 24px;
            }
        }

        .loading {
            display: none;
            text-align: center;
            color: #667eea;
        }

        .spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #667eea;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            animation: spin 1s linear infinite;
            margin: 0 auto;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1>🔐 Login</h1>
            <p>Inventory Management System</p>
        </div>

        <?php
        // Display alert message if any
        if (!empty($alert_message)) {
            echo '<div class="alert alert-' . $alert_type . '">' . $alert_message . '</div>';
        }
        ?>

        <form method="POST" action="process_login.php" id="loginForm">
            <div class="form-group">
                <label for="username">Username</label>
                <input 
                    type="text" 
                    id="username" 
                    name="username" 
                    placeholder="Enter your username" 
                    required
                    autocomplete="username"
                >
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input 
                    type="password" 
                    id="password" 
                    name="password" 
                    placeholder="Enter your password" 
                    required
                    autocomplete="current-password"
                >
            </div>

            <div class="remember-me">
                <input type="checkbox" id="rememberMe" name="remember_me">
                <label for="rememberMe">Remember me</label>
            </div>

            <button type="submit" class="login-btn">Login</button>

            <div class="loading" id="loading">
                <div class="spinner"></div>
                <p>Logging in...</p>
            </div>
        </form>

        <div class="demo-credentials">
            <p><strong>Demo Credentials:</strong></p>
            <p>Admin - username: <strong>admin</strong> | password: <strong>admin123</strong></p>
            <p>User - username: <strong>user</strong> | password: <strong>user123</strong></p>
        </div>

        <div class="footer">
            <p>&copy; 2026 Inventory Management System. All rights reserved.</p>
        </div>
    </div>

    <script>
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const loading = document.getElementById('loading');
            loading.style.display = 'block';
            
            // Optional: Add a small delay to show loading state
            setTimeout(() => {
                // Form will submit after a brief moment
            }, 300);
        });

        // Auto-focus on username field
        document.getElementById('username').focus();
    </script>
</body>
</html>
