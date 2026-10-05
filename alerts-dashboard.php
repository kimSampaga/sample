<?php
session_start();

// Include required files
require_once 'config/database.php';
require_once 'config/session.php';
require_once 'config/auth_middleware.php';
require_once 'classes/Alerts.php';

// Check authentication
AuthMiddleware::requireLogin();

// Create database connection
$db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

// Get alerts instance
$alerts = new Alerts($db);

// Get action parameter
$action = isset($_GET['action']) ? $_GET['action'] : '';
$alert_id = isset($_GET['alert_id']) ? intval($_GET['alert_id']) : 0;

// Handle alert actions
$message = '';
if (!empty($action)) {
    if ($action == 'acknowledge' && $alert_id > 0) {
        if ($alerts->acknowledgeAlert($alert_id, $_SESSION['user_id'])) {
            $message = '<div style="background: #d1fae5; color: #065f46; padding: 15px; border-radius: 4px; margin-bottom: 20px;">✓ Alert acknowledged successfully.</div>';
        }
    } elseif ($action == 'resolve' && $alert_id > 0) {
        if ($alerts->resolveAlert($alert_id)) {
            $message = '<div style="background: #d1fae5; color: #065f46; padding: 15px; border-radius: 4px; margin-bottom: 20px;">✓ Alert resolved successfully.</div>';
        }
    } elseif ($action == 'delete' && $alert_id > 0) {
        if ($alerts->deleteAlert($alert_id)) {
            $message = '<div style="background: #d1fae5; color: #065f46; padding: 15px; border-radius: 4px; margin-bottom: 20px;">✓ Alert deleted successfully.</div>';
        }
    }
}

// Get all active alerts
$active_alerts = $alerts->getActiveAlerts();
$critical_alerts = $alerts->getCriticalAlerts();
$alert_counts = $alerts->getAlertCounts();

// Helper function for severity badge
function getSeverityBadge($severity) {
    switch ($severity) {
        case 'EXPIRED':
            return '<span style="background: #fee2e2; color: #991b1b; padding: 4px 8px; border-radius: 4px; font-weight: 600; font-size: 12px;">🔴 EXPIRED</span>';
        case 'OUT_OF_STOCK':
            return '<span style="background: #fee2e2; color: #991b1b; padding: 4px 8px; border-radius: 4px; font-weight: 600; font-size: 12px;">🔴 OUT OF STOCK</span>';
        case 'CRITICAL':
            return '<span style="background: #fed7aa; color: #92400e; padding: 4px 8px; border-radius: 4px; font-weight: 600; font-size: 12px;">🟠 CRITICAL</span>';
        case 'WARNING':
            return '<span style="background: #fef3c7; color: #78350f; padding: 4px 8px; border-radius: 4px; font-weight: 600; font-size: 12px;">🟡 WARNING</span>';
        case 'NOTICE':
            return '<span style="background: #dbeafe; color: #0c4a6e; padding: 4px 8px; border-radius: 4px; font-weight: 600; font-size: 12px;">ℹ️ NOTICE</span>';
        default:
            return '<span style="background: #e5e7eb; color: #374151; padding: 4px 8px; border-radius: 4px; font-weight: 600; font-size: 12px;">INFO</span>';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alerts Dashboard - Inventory System</title>
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
            padding: 20px;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
        }

        .header {
            background: white;
            padding: 25px;
            border-radius: 8px;
            margin-bottom: 25px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .header h1 {
            font-size: 28px;
            color: #1f2937;
            margin-bottom: 15px;
        }

        .header p {
            color: #6b7280;
            font-size: 14px;
        }

        .back-link {
            display: inline-block;
            color: white;
            text-decoration: none;
            margin-bottom: 20px;
            padding: 8px 16px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 4px;
            transition: background 0.3s;
        }

        .back-link:hover {
            background: rgba(255, 255, 255, 0.3);
        }

        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }

        .metric-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
        }

        .metric-label {
            font-size: 12px;
            opacity: 0.9;
            text-transform: uppercase;
            margin-bottom: 8px;
        }

        .metric-value {
            font-size: 32px;
            font-weight: bold;
        }

        .section-title {
            font-size: 20px;
            color: white;
            margin-top: 30px;
            margin-bottom: 20px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .alert-table thead {
            background: #f3f4f6;
            border-bottom: 2px solid #e5e7eb;
        }

        .alert-table th {
            padding: 15px;
            text-align: left;
            font-weight: 600;
            color: #374151;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .alert-table td {
            padding: 12px 15px;
            border-bottom: 1px solid #e5e7eb;
            font-size: 14px;
        }

        .alert-table tbody tr:hover {
            background: #f9fafb;
        }

        .alert-table tbody tr:last-child td {
            border-bottom: none;
        }

        .alert-type {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-weight: 600;
            font-size: 12px;
        }

        .alert-type.expiry {
            background: #fce7f3;
            color: #831843;
        }

        .alert-type.reorder {
            background: #cffafe;
            color: #0e4a6e;
        }

        .action-buttons {
            display: flex;
            gap: 8px;
        }

        .btn-small {
            padding: 6px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
        }

        .btn-acknowledge {
            background: #3b82f6;
            color: white;
        }

        .btn-acknowledge:hover {
            background: #2563eb;
        }

        .btn-resolve {
            background: #10b981;
            color: white;
        }

        .btn-resolve:hover {
            background: #059669;
        }

        .btn-delete {
            background: #ef4444;
            color: white;
        }

        .btn-delete:hover {
            background: #dc2626;
        }

        .empty-state {
            background: white;
            padding: 40px;
            border-radius: 8px;
            text-align: center;
            color: #6b7280;
        }

        .empty-state-icon {
            font-size: 48px;
            margin-bottom: 15px;
        }

        .status-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
        }

        .status-active {
            background: #dbeafe;
            color: #0c4a6e;
        }

        .status-acknowledged {
            background: #f3e8ff;
            color: #6b21a8;
        }

        .status-resolved {
            background: #d1fae5;
            color: #065f46;
        }

        .text-right {
            text-align: right;
        }

        .critical-banner {
            background: #fee2e2;
            border-left: 5px solid #dc2626;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }

        .critical-banner h3 {
            color: #991b1b;
            margin-bottom: 10px;
        }

        .critical-banner p {
            color: #7f1d1d;
            font-size: 14px;
        }

        .filter-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }

        .filter-tab {
            padding: 10px 20px;
            border: none;
            background: rgba(255, 255, 255, 0.2);
            color: white;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .filter-tab.active {
            background: white;
            color: #667eea;
        }

        .filter-tab:hover {
            background: rgba(255, 255, 255, 0.3);
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="products.php" class="back-link">← Back to Inventory</a>

        <div class="header">
            <h1>🔔 Alerts Dashboard</h1>
            <p>Monitor expiry alerts and reorder notifications</p>

            <div class="metrics-grid">
                <div class="metric-card">
                    <div class="metric-label">Total Active Alerts</div>
                    <div class="metric-value"><?php echo $alert_counts['TOTAL']; ?></div>
                </div>
                <div class="metric-card">
                    <div class="metric-label">Expiry Alerts</div>
                    <div class="metric-value"><?php echo $alert_counts['EXPIRY']; ?></div>
                </div>
                <div class="metric-card">
                    <div class="metric-label">Reorder Alerts</div>
                    <div class="metric-value"><?php echo $alert_counts['REORDER']; ?></div>
                </div>
                <div class="metric-card">
                    <div class="metric-label">Critical Alerts</div>
                    <div class="metric-value"><?php echo count($critical_alerts); ?></div>
                </div>
            </div>
        </div>

        <?php echo $message; ?>

        <!-- Critical Alerts Section -->
        <?php if (!empty($critical_alerts)): ?>
        <div class="critical-banner">
            <h3>⚠️ Critical Alert! Immediate Action Required</h3>
            <p><?php echo count($critical_alerts); ?> alert(s) require immediate attention. Please review below and take action.</p>
        </div>
        <?php endif; ?>

        <!-- All Active Alerts Section -->
        <div class="section-title">
            🔴 All Active Alerts (<?php echo count($active_alerts); ?>)
        </div>

        <?php if (!empty($active_alerts)): ?>
        <table class="alert-table">
            <thead>
                <tr>
                    <th>Type</th>
                    <th>Product Code</th>
                    <th>Product Name</th>
                    <th>Current Stock / Expiry</th>
                    <th>Reorder Level / Days</th>
                    <th>Severity</th>
                    <th>Status</th>
                    <th>Notifications</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($active_alerts as $alert): ?>
                <tr>
                    <td>
                        <span class="alert-type <?php echo strtolower($alert['alert_type']); ?>">
                            <?php 
                                echo $alert['alert_type'] == 'EXPIRY' ? '📅 EXPIRY' : '📦 REORDER';
                            ?>
                        </span>
                    </td>
                    <td><strong><?php echo htmlspecialchars($alert['product_code']); ?></strong></td>
                    <td><?php echo htmlspecialchars($alert['product_name']); ?></td>
                    <td>
                        <?php 
                            if ($alert['alert_type'] == 'EXPIRY') {
                                echo htmlspecialchars($alert['expiry_date']);
                            } else {
                                echo $alert['current_stock'] . ' units';
                            }
                        ?>
                    </td>
                    <td>
                        <?php 
                            if ($alert['alert_type'] == 'EXPIRY') {
                                echo $alert['days_until_expiry'] . ' days';
                            } else {
                                echo 'Reorder: ' . $alert['reorder_level'] . ' units';
                            }
                        ?>
                    </td>
                    <td><?php echo getSeverityBadge($alert['severity']); ?></td>
                    <td>
                        <span class="status-badge status-<?php echo strtolower($alert['alert_status']); ?>">
                            <?php echo htmlspecialchars($alert['alert_status']); ?>
                        </span>
                    </td>
                    <td>
                        <?php 
                            $notifications = array();
                            if ($alert['email_sent']) $notifications[] = '📧 Email';
                            if ($alert['sms_sent']) $notifications[] = '📱 SMS';
                            if (empty($notifications)) {
                                echo '<span style="color: #6b7280;">Not sent</span>';
                            } else {
                                echo implode(', ', $notifications);
                            }
                        ?>
                    </td>
                    <td>
                        <div class="action-buttons">
                            <?php if ($alert['alert_status'] == 'ACTIVE'): ?>
                            <a href="?action=acknowledge&alert_id=<?php echo $alert['id']; ?>" class="btn-small btn-acknowledge">✓ Acknowledge</a>
                            <?php else: ?>
                            <a href="?action=resolve&alert_id=<?php echo $alert['id']; ?>" class="btn-small btn-resolve">✓ Resolve</a>
                            <?php endif; ?>
                            <a href="?action=delete&alert_id=<?php echo $alert['id']; ?>" class="btn-small btn-delete" onclick="return confirm('Delete this alert?');">✕ Delete</a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <div class="empty-state">
            <div class="empty-state-icon">✓</div>
            <h3 style="color: #1f2937; margin-bottom: 10px;">No Active Alerts</h3>
            <p>Great! All products are within normal parameters. No action needed at this time.</p>
        </div>
        <?php endif; ?>

    </div>
</body>
</html>
