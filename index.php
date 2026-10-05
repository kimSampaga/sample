<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Management System</title>
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

        .container {
            background: white;
            padding: 50px;
            border-radius: 15px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            max-width: 800px;
            text-align: center;
        }

        h1 {
            color: #333;
            font-size: 42px;
            margin-bottom: 20px;
        }

        .subtitle {
            color: #666;
            font-size: 18px;
            margin-bottom: 40px;
        }

        .features {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin: 40px 0;
            text-align: left;
        }

        .feature {
            background: #f9f9f9;
            padding: 20px;
            border-radius: 10px;
            border-left: 4px solid #667eea;
        }

        .feature h3 {
            color: #667eea;
            margin-bottom: 10px;
        }

        .feature p {
            color: #666;
            font-size: 14px;
        }

        .buttons {
            margin-top: 40px;
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn {
            padding: 15px 40px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }

        .btn-secondary {
            background: white;
            color: #667eea;
            border: 2px solid #667eea;
        }

        .btn-secondary:hover {
            background: #f9f9f9;
        }

        .status {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 30px;
            border: 1px solid #c3e6cb;
        }

        .docs {
            background: #f0f0f0;
            padding: 20px;
            border-radius: 10px;
            margin-top: 30px;
            text-align: left;
        }

        .docs h3 {
            color: #333;
            margin-bottom: 15px;
        }

        .docs ul {
            list-style: none;
        }

        .docs li {
            padding: 8px 0;
            color: #666;
        }

        .docs a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
        }

        .docs a:hover {
            text-decoration: underline;
        }

        @media (max-width: 480px) {
            .container {
                margin: 20px;
                padding: 30px 20px;
            }

            h1 {
                font-size: 32px;
            }

            .features {
                grid-template-columns: 1fr;
            }

            .buttons {
                flex-direction: column;
            }

            .btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🏪 Inventory Management System</h1>
        <p class="subtitle">Secure. Scalable. Simple.</p>

        <div class="status">
            ✅ <strong>System Ready!</strong> Admin login is configured and ready to use.
        </div>

        <div class="features">
            <div class="feature">
                <h3>🔐 Secure Login</h3>
                <p>Bcrypt password hashing and session-based authentication</p>
            </div>
            <div class="feature">
                <h3>👥 Role Management</h3>
                <p>Admin and User roles with different access levels</p>
            </div>
            <div class="feature">
                <h3>⏱️ Session Control</h3>
                <p>Auto-logout after 30 minutes, failed login tracking</p>
            </div>
            <div class="feature">
                <h3>🛡️ CSRF Protection</h3>
                <p>Token validation on all forms for security</p>
            </div>
            <div class="feature">
                <h3>📋 Activity Logging</h3>
                <p>Track logins and user activities</p>
            </div>
            <div class="feature">
                <h3>📱 Responsive Design</h3>
                <p>Works on desktop, tablet, and mobile devices</p>
            </div>
        </div>

        <div class="buttons">
            <a href="login.php" class="btn btn-primary">🚪 Go to Login</a>
            <a href="setup.php" class="btn btn-secondary">⚙️ Database Setup</a>
        </div>

        <div class="docs">
            <h3>📚 Documentation</h3>
            <ul>
                <li>📖 <a href="README.md" target="_blank">Detailed Documentation</a> - Full system guide</li>
                <li>🚀 <a href="QUICK_START.md" target="_blank">Quick Start Guide</a> - Get started in 3 steps</li>
                <li>🏗️ <a href="ARCHITECTURE.md" target="_blank">Architecture Guide</a> - System design & structure</li>
                <li>💾 <a href="database/schema.sql" target="_blank">Database Schema</a> - SQL tables and relationships</li>
            </ul>
        </div>

        <div class="docs" style="margin-top: 20px; background: #fff3cd;">
            <h3>👤 Demo Credentials</h3>
            <ul>
                <li><strong>Admin:</strong> username: <code style="background: #f0f0f0; padding: 2px 5px;">admin</code>, password: <code style="background: #f0f0f0; padding: 2px 5px;">admin123</code></li>
                <li><strong>User:</strong> username: <code style="background: #f0f0f0; padding: 2px 5px;">user</code>, password: <code style="background: #f0f0f0; padding: 2px 5px;">user123</code></li>
            </ul>
        </div>
    </div>
</body>
</html>
