<?php
/**
 * Base Layout Template
 * Responsive Bootstrap 5 layout with sidebar navigation
 * Include this at the top of every page after authentication
 */

// Start buffering to include header/footer
$page_title = isset($page_title) ? $page_title : 'Inventory System';
$active_menu = isset($active_menu) ? $active_menu : 'dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?> - Inventory System</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.js"></script>
    
    <!-- Custom Responsive CSS -->
    <style>
        :root {
            --primary-color: #667eea;
            --primary-dark: #764ba2;
            --secondary-color: #f3f4f6;
            --sidebar-width: 260px;
            --sidebar-width-mobile: 0;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f7fa;
            overflow-x: hidden;
        }

        /* Sidebar Navigation */
        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            width: var(--sidebar-width);
            height: 100vh;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
            color: white;
            overflow-y: auto;
            transition: all 0.3s ease;
            z-index: 1000;
            padding: 20px 0;
        }

        .sidebar-header {
            padding: 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            margin-bottom: 20px;
        }

        .sidebar-header h3 {
            font-size: 20px;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .sidebar-menu {
            list-style: none;
        }

        .sidebar-menu li {
            margin: 0;
        }

        .sidebar-menu a {
            display: block;
            padding: 12px 20px;
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            transition: all 0.3s ease;
            border-left: 3px solid transparent;
            font-size: 14px;
        }

        .sidebar-menu a:hover {
            color: white;
            background: rgba(255, 255, 255, 0.1);
            border-left-color: white;
            padding-left: 25px;
        }

        .sidebar-menu a.active {
            color: white;
            background: rgba(255, 255, 255, 0.15);
            border-left-color: #4ade80;
            font-weight: 600;
        }

        .sidebar-menu i {
            width: 20px;
            text-align: center;
            margin-right: 10px;
        }

        /* Top Navigation */
        .navbar-custom {
            position: fixed;
            top: 0;
            left: var(--sidebar-width);
            right: 0;
            background: white;
            border-bottom: 1px solid #e5e7eb;
            z-index: 999;
            transition: all 0.3s ease;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        .navbar-custom .navbar-brand {
            display: none;
        }

        .navbar-custom .nav-item {
            margin: 0 5px;
        }

        .navbar-custom .nav-link {
            color: #374151;
            font-size: 14px;
            transition: color 0.3s;
        }

        .navbar-custom .nav-link:hover {
            color: var(--primary-color);
        }

        .navbar-user {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            cursor: pointer;
        }

        .badge-alert {
            position: relative;
        }

        .badge-alert .badge {
            position: absolute;
            top: -8px;
            right: -8px;
            background: #ef4444;
            color: white;
            font-size: 10px;
            padding: 2px 5px;
            border-radius: 10px;
        }

        /* Main Content Area */
        .main-content {
            margin-top: 70px;
            margin-left: var(--sidebar-width);
            padding: 30px;
            transition: all 0.3s ease;
            min-height: 100vh;
        }

        /* Page Header */
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .page-header h1 {
            font-size: 28px;
            color: #1f2937;
            margin: 0;
        }

        .page-header-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        /* Dashboard Cards */
        .stat-card {
            background: white;
            border-radius: 8px;
            padding: 20px;
            border-left: 4px solid var(--primary-color);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            margin-bottom: 20px;
        }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
        }

        .stat-card.green {
            border-left-color: #10b981;
        }

        .stat-card.blue {
            border-left-color: #3b82f6;
        }

        .stat-card.orange {
            border-left-color: #f59e0b;
        }

        .stat-card.red {
            border-left-color: #ef4444;
        }

        .stat-label {
            font-size: 12px;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
        }

        .stat-value {
            font-size: 28px;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 8px;
        }

        .stat-change {
            font-size: 12px;
            color: #6b7280;
        }

        .stat-change.positive {
            color: #10b981;
        }

        .stat-change.negative {
            color: #ef4444;
        }

        /* Table Styles */
        .table-responsive-wrapper {
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            overflow-x: auto;
        }

        .table {
            margin: 0;
        }

        .table thead th {
            background: #f3f4f6;
            color: #374151;
            font-weight: 600;
            border: none;
            padding: 12px 15px;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .table tbody td {
            padding: 12px 15px;
            border-bottom: 1px solid #e5e7eb;
            color: #374151;
        }

        .table tbody tr:hover {
            background: #f9fafb;
        }

        /* Buttons */
        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            border: none;
            color: white;
            padding: 8px 16px;
            font-size: 14px;
            transition: all 0.3s;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
            color: white;
        }

        .btn-sm {
            padding: 6px 12px;
            font-size: 12px;
        }

        /* Badge Styles */
        .badge {
            padding: 4px 8px;
            font-size: 11px;
            font-weight: 600;
            border-radius: 4px;
        }

        .badge-success {
            background: #d1fae5;
            color: #065f46;
        }

        .badge-danger {
            background: #fee2e2;
            color: #991b1b;
        }

        .badge-warning {
            background: #fef3c7;
            color: #92400e;
        }

        .badge-info {
            background: #dbeafe;
            color: #0c4a6e;
        }

        /* Alert Styles */
        .alert {
            border-radius: 8px;
            border: none;
            margin-bottom: 20px;
        }

        .alert-danger {
            background: #fee2e2;
            color: #991b1b;
        }

        .alert-success {
            background: #d1fae5;
            color: #065f46;
        }

        .alert-warning {
            background: #fef3c7;
            color: #92400e;
        }

        .alert-info {
            background: #dbeafe;
            color: #0c4a6e;
        }

        /* Grid Layout */
        .cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        /* Toggle Sidebar Button (Mobile) */
        .sidebar-toggle {
            display: none;
            background: none;
            border: none;
            color: var(--primary-color);
            font-size: 20px;
            cursor: pointer;
            padding: 10px;
        }

        /* Mobile Responsive */
        @media (max-width: 992px) {
            .sidebar {
                width: 0;
                transform: translateX(-100%);
            }

            .sidebar.active {
                transform: translateX(0);
            }

            .navbar-custom {
                left: 0;
            }

            .main-content {
                margin-left: 0;
            }

            .sidebar-toggle {
                display: block;
            }

            .page-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }

            .page-header-actions {
                width: 100%;
            }

            .cards-grid {
                grid-template-columns: 1fr;
            }

            .navbar-custom .navbar-brand {
                display: block;
                font-size: 18px;
                font-weight: 700;
                color: var(--primary-color);
            }
        }

        @media (max-width: 768px) {
            .main-content {
                padding: 15px;
            }

            .page-header h1 {
                font-size: 22px;
            }

            .stat-card {
                padding: 15px;
            }

            .stat-value {
                font-size: 22px;
            }

            .navbar-custom {
                padding: 10px 15px !important;
            }

            .table {
                font-size: 13px;
            }

            .table thead th,
            .table tbody td {
                padding: 8px 10px;
            }
        }

        /* Footer */
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
            text-align: center;
            color: #6b7280;
            font-size: 12px;
        }

        /* Chart Container */
        .chart-container {
            position: relative;
            height: 300px;
            margin-bottom: 30px;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        }

        /* Loading Spinner */
        .spinner-small {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 2px solid #e5e7eb;
            border-top-color: var(--primary-color);
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Utility Classes */
        .text-muted {
            color: #6b7280;
        }

        .text-success {
            color: #10b981;
        }

        .text-danger {
            color: #ef4444;
        }

        .text-warning {
            color: #f59e0b;
        }

        .gap-10 {
            gap: 10px;
        }

        .gap-20 {
            gap: 20px;
        }
    </style>
</head>
<body>
    <!-- Sidebar Navigation -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <h3>
                <i class="fas fa-box"></i>
                <span>Inventory</span>
            </h3>
        </div>
        <ul class="sidebar-menu">
            <li>
                <a href="dashboard.php" class="<?php echo $active_menu === 'dashboard' ? 'active' : ''; ?>">
                    <i class="fas fa-home"></i> Dashboard
                </a>
            </li>
            <li>
                <a href="products.php" class="<?php echo $active_menu === 'inventory' ? 'active' : ''; ?>">
                    <i class="fas fa-boxes"></i> Inventory Master
                </a>
            </li>
            <li>
                <a href="stock-movement-report.php" class="<?php echo $active_menu === 'stock' ? 'active' : ''; ?>">
                    <i class="fas fa-exchange-alt"></i> Stock Movement
                </a>
            </li>
            <li>
                <a href="purchase-record.php" class="<?php echo $active_menu === 'purchase' ? 'active' : ''; ?>">
                    <i class="fas fa-shopping-cart"></i> Purchases
                </a>
            </li>
            <li>
                <a href="sales-record.php" class="<?php echo $active_menu === 'sales' ? 'active' : ''; ?>">
                    <i class="fas fa-chart-line"></i> Sales
                </a>
            </li>
            <li>
                <a href="analytics-dashboard.php" class="<?php echo $active_menu === 'analytics' ? 'active' : ''; ?>">
                    <i class="fas fa-chart-bar"></i> Analytics
                </a>
            </li>
            <li>
                <a href="alerts-dashboard.php" class="<?php echo $active_menu === 'alerts' ? 'active' : ''; ?>">
                    <i class="fas fa-exclamation-circle"></i> Alerts
                </a>
            </li>
            <li style="margin-top: 20px; border-top: 1px solid rgba(255, 255, 255, 0.1); padding-top: 20px;">
                <a href="logout.php">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </li>
        </ul>
    </aside>

    <!-- Top Navigation -->
    <nav class="navbar navbar-expand-lg navbar-custom">
        <div class="container-fluid">
            <button class="sidebar-toggle" onclick="toggleSidebar()">
                <i class="fas fa-bars"></i>
            </button>
            <a class="navbar-brand" href="dashboard.php">
                <i class="fas fa-box"></i> Inventory System
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <div class="ms-auto">
                    <div class="navbar-user">
                        <div class="badge-alert">
                            <a href="alerts-dashboard.php" class="nav-link" style="color: #ef4444; font-weight: 600;">
                                <i class="fas fa-bell"></i>
                                <span class="badge" id="alert-count" style="display: none;">0</span>
                            </a>
                        </div>
                        <div class="user-avatar" title="<?php echo htmlspecialchars($user['first_name'] ?? 'User'); ?>">
                            <?php echo strtoupper(substr($user['first_name'] ?? 'U', 0, 1) . substr($user['last_name'] ?? '', 0, 1)); ?>
                        </div>
                        <span style="color: #374151; font-size: 14px;">
                            <?php echo htmlspecialchars($user['first_name'] ?? 'User'); ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="main-content">
