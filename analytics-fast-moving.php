<?php
session_start();

// Include required files
require_once 'config/database.php';
require_once 'config/session.php';
require_once 'config/auth_middleware.php';
require_once 'classes/Analytics.php';

// Check authentication
AuthMiddleware::requireLogin();

// Create database connection
$db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

// Get analytics instance
$analytics = new Analytics($db);

// Get period parameter (default 30 days)
$period = isset($_GET['period']) ? intval($_GET['period']) : 30;
if ($period <= 0) $period = 30;

// Get fast-moving items
$fast_moving_items = $analytics->getFastMovingItems($period, 50);

// Helper functions
function formatCurrency($value) {
    return number_format($value, 2, '.', ',');
}

function formatPercentage($value) {
    return number_format($value, 2) . '%';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fast-Moving Items - Inventory System</title>
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
            max-width: 1200px;
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

        .filters {
            display: flex;
            gap: 15px;
            align-items: center;
            margin-top: 15px;
        }

        .period-selector {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .period-selector select {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }

        .filter-btn {
            padding: 8px 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 500;
        }

        .filter-btn:hover {
            opacity: 0.9;
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

        .data-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .data-table thead {
            background: #f3f4f6;
            border-bottom: 2px solid #e5e7eb;
        }

        .data-table th {
            padding: 15px;
            text-align: left;
            font-weight: 600;
            color: #374151;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .data-table td {
            padding: 12px 15px;
            border-bottom: 1px solid #e5e7eb;
            font-size: 14px;
        }

        .data-table tbody tr:hover {
            background: #f9fafb;
        }

        .data-table tbody tr:last-child td {
            border-bottom: none;
        }

        .text-right {
            text-align: right;
        }

        .text-green {
            color: #10b981;
            font-weight: 600;
        }

        .text-red {
            color: #ef4444;
            font-weight: 600;
        }

        .text-orange {
            color: #f59e0b;
            font-weight: 600;
        }

        .empty-state {
            background: white;
            padding: 40px;
            border-radius: 8px;
            text-align: center;
            color: #6b7280;
        }

        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .badge-excellent {
            background: #d1fae5;
            color: #065f46;
        }

        .badge-good {
            background: #dbeafe;
            color: #0c4a6e;
        }

        .badge-fair {
            background: #fef3c7;
            color: #92400e;
        }

        .badge-poor {
            background: #fee2e2;
            color: #991b1b;
        }

        .section-title {
            font-size: 20px;
            color: white;
            margin-bottom: 20px;
            font-weight: 600;
        }

        .summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }

        .summary-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        .summary-card .label {
            font-size: 12px;
            color: #6b7280;
            text-transform: uppercase;
            margin-bottom: 8px;
        }

        .summary-card .value {
            font-size: 24px;
            font-weight: bold;
            color: #1f2937;
        }

        .turnaround-bar {
            width: 100%;
            height: 4px;
            background: #e5e7eb;
            border-radius: 2px;
            margin-top: 8px;
            overflow: hidden;
        }

        .turnaround-fill {
            height: 100%;
            background: linear-gradient(90deg, #10b981 0%, #f59e0b 50%, #ef4444 100%);
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="analytics-dashboard.php" class="back-link">← Back to Dashboard</a>

        <div class="header">
            <h1>🚀 Fast-Moving Items Analysis</h1>
            <p>Products with high sales velocity and strong market demand</p>
            
            <div class="filters">
                <form method="GET" class="period-selector" style="display: flex; gap: 10px; align-items: center;">
                    <label for="period">Analysis Period:</label>
                    <select id="period" name="period" onchange="this.form.submit();">
                        <option value="7" <?php echo $period == 7 ? 'selected' : ''; ?>>Last 7 Days</option>
                        <option value="14" <?php echo $period == 14 ? 'selected' : ''; ?>>Last 14 Days</option>
                        <option value="30" <?php echo $period == 30 ? 'selected' : ''; ?>>Last 30 Days</option>
                        <option value="90" <?php echo $period == 90 ? 'selected' : ''; ?>>Last 90 Days</option>
                        <option value="180" <?php echo $period == 180 ? 'selected' : ''; ?>>Last 6 Months</option>
                        <option value="365" <?php echo $period == 365 ? 'selected' : ''; ?>>Last Year</option>
                    </select>
                </form>
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="section-title">Insights</div>
        <div class="summary-grid">
            <div class="summary-card">
                <div class="label">Total Fast-Moving Items</div>
                <div class="value text-green"><?php echo count($fast_moving_items); ?></div>
            </div>
            <div class="summary-card">
                <div class="label">Combined Revenue</div>
                <div class="value text-green">₱<?php echo formatCurrency(array_sum(array_column($fast_moving_items, 'total_sales_amount'))); ?></div>
            </div>
            <div class="summary-card">
                <div class="label">Combined Profit</div>
                <div class="value text-green">₱<?php echo formatCurrency(array_sum(array_column($fast_moving_items, 'total_profit'))); ?></div>
            </div>
            <div class="summary-card">
                <div class="label">Average Margin</div>
                <div class="value text-orange">
                    <?php 
                        $avg_margin = !empty($fast_moving_items) ? array_sum(array_column($fast_moving_items, 'profit_margin')) / count($fast_moving_items) : 0;
                        echo formatPercentage($avg_margin);
                    ?>
                </div>
            </div>
        </div>

        <!-- Detailed Table -->
        <div class="section-title">Detailed Performance Metrics</div>
        <?php if (!empty($fast_moving_items)): ?>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Rank</th>
                    <th>Product Code</th>
                    <th>Product Name</th>
                    <th>Units Sold</th>
                    <th>Sales Amount</th>
                    <th>Total Cost</th>
                    <th>Profit</th>
                    <th>Profit Margin</th>
                    <th>Velocity Ratio</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $rank = 1;
                foreach ($fast_moving_items as $item): 
                    $velocity_ratio = $item['turnover_ratio'];
                    if ($velocity_ratio >= 2) {
                        $status = '<span class="status-badge badge-excellent">⭐ Excellent</span>';
                    } elseif ($velocity_ratio >= 1) {
                        $status = '<span class="status-badge badge-good">✓ Good</span>';
                    } else {
                        $status = '<span class="status-badge badge-fair">~ Fair</span>';
                    }
                ?>
                <tr>
                    <td class="text-green"><strong>#<?php echo $rank; ?></strong></td>
                    <td><strong><?php echo htmlspecialchars($item['product_code']); ?></strong></td>
                    <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                    <td class="text-right text-green"><strong><?php echo intval($item['total_quantity_sold']); ?></strong></td>
                    <td class="text-right">₱<?php echo formatCurrency($item['total_sales_amount']); ?></td>
                    <td class="text-right">₱<?php echo formatCurrency($item['total_cost']); ?></td>
                    <td class="text-right text-green"><strong>₱<?php echo formatCurrency($item['total_profit']); ?></strong></td>
                    <td class="text-right text-green"><?php echo formatPercentage($item['profit_margin']); ?></td>
                    <td class="text-right">
                        <div style="width: 100px; display: inline-block;">
                            <?php echo number_format($velocity_ratio, 2); ?>
                            <div class="turnaround-bar">
                                <div class="turnaround-fill" style="width: <?php echo min($velocity_ratio * 33, 100); ?>%;"></div>
                            </div>
                        </div>
                    </td>
                    <td><?php echo $status; ?></td>
                </tr>
                <?php 
                    $rank++;
                endforeach; 
                ?>
            </tbody>
        </table>
        <?php else: ?>
        <div class="empty-state">
            <p>No fast-moving items found in the selected period.</p>
        </div>
        <?php endif; ?>

    </div>
</body>
</html>
