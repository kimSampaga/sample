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

// Get slow-moving items
$slow_moving_items = $analytics->getSlowMovingItems($period, 50);

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
    <title>Slow-Moving Items - Inventory System</title>
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

        .badge-dead {
            background: #fee2e2;
            color: #991b1b;
        }

        .badge-overstock {
            background: #fed7aa;
            color: #92400e;
        }

        .badge-monitor {
            background: #fef3c7;
            color: #78350f;
        }

        .badge-normal {
            background: #dbeafe;
            color: #0c4a6e;
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

        .risk-indicator {
            display: inline-block;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            margin-right: 5px;
        }

        .risk-high {
            background: #ef4444;
        }

        .risk-medium {
            background: #f59e0b;
        }

        .risk-low {
            background: #10b981;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="analytics-dashboard.php" class="back-link">← Back to Dashboard</a>

        <div class="header">
            <h1>🐢 Slow-Moving Items Analysis</h1>
            <p>Products with low sales velocity - requires attention for inventory optimization</p>
            
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
                <div class="label">Total Slow-Moving Items</div>
                <div class="value text-red"><?php echo count($slow_moving_items); ?></div>
            </div>
            <div class="summary-card">
                <div class="label">Dead Stock Items</div>
                <div class="value text-red">
                    <?php 
                        $dead_stock_count = 0;
                        foreach ($slow_moving_items as $item) {
                            if ($item['total_quantity_sold'] == 0) {
                                $dead_stock_count++;
                            }
                        }
                        echo $dead_stock_count;
                    ?>
                </div>
            </div>
            <div class="summary-card">
                <div class="label">Locked Inventory Value</div>
                <div class="value text-orange">₱<?php echo formatCurrency(array_sum(array_column($slow_moving_items, 'inventory_value'))); ?></div>
            </div>
            <div class="summary-card">
                <div class="label">Avg Days Without Sale</div>
                <div class="value text-red">
                    <?php 
                        $avg_days = 0;
                        $count = 0;
                        foreach ($slow_moving_items as $item) {
                            if (isset($item['days_since_sale'])) {
                                $avg_days += intval($item['days_since_sale']);
                                $count++;
                            }
                        }
                        echo $count > 0 ? intval($avg_days / $count) : '0';
                    ?> days
                </div>
            </div>
        </div>

        <!-- Detailed Table -->
        <div class="section-title">Detailed Analysis</div>
        <?php if (!empty($slow_moving_items)): ?>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Rank</th>
                    <th>Product Code</th>
                    <th>Product Name</th>
                    <th>Units Sold</th>
                    <th>Days Since Last Sale</th>
                    <th>Current Stock</th>
                    <th>Reorder Level</th>
                    <th>Inventory Value</th>
                    <th>Stock Status</th>
                    <th>Action Required</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $rank = 1;
                foreach ($slow_moving_items as $item): 
                    $days_since = isset($item['days_since_sale']) ? intval($item['days_since_sale']) : 999;
                    
                    // Determine status and recommended action
                    if ($item['total_quantity_sold'] == 0) {
                        $status = '<span class="status-badge badge-dead">☠ Dead Stock</span>';
                        $action = 'Discontinue / Clearance Sale';
                        $risk = 'risk-high';
                    } elseif ($item['current_stock'] > $item['reorder_level'] * 2) {
                        $status = '<span class="status-badge badge-overstock">⚠ Overstock</span>';
                        $action = 'Reduce orders / Promote';
                        $risk = 'risk-high';
                    } elseif ($days_since > 60) {
                        $status = '<span class="status-badge badge-monitor">! Monitor</span>';
                        $action = 'Review demand / Discount';
                        $risk = 'risk-medium';
                    } else {
                        $status = '<span class="status-badge badge-normal">✓ Normal</span>';
                        $action = 'Continue monitoring';
                        $risk = 'risk-low';
                    }
                ?>
                <tr>
                    <td class="text-red"><strong>#<?php echo $rank; ?></strong></td>
                    <td><strong><?php echo htmlspecialchars($item['product_code']); ?></strong></td>
                    <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                    <td class="text-right text-red"><?php echo intval($item['total_quantity_sold']); ?></td>
                    <td class="text-right text-orange">
                        <strong><?php echo $days_since == 999 ? 'N/A' : $days_since . ' days'; ?></strong>
                    </td>
                    <td class="text-right">
                        <?php 
                            $stock_status = $item['current_stock'] > $item['reorder_level'] ? '🔴 High' : '🟢 Low';
                            echo $stock_status . ' (' . intval($item['current_stock']) . ')';
                        ?>
                    </td>
                    <td class="text-right"><?php echo intval($item['reorder_level']); ?></td>
                    <td class="text-right text-orange"><strong>₱<?php echo formatCurrency($item['inventory_value']); ?></strong></td>
                    <td><?php echo $status; ?></td>
                    <td>
                        <div style="display: flex; align-items: center;">
                            <span class="risk-indicator <?php echo $risk; ?>"></span>
                            <span style="font-size: 13px;"><?php echo htmlspecialchars($action); ?></span>
                        </div>
                    </td>
                </tr>
                <?php 
                    $rank++;
                endforeach; 
                ?>
            </tbody>
        </table>
        <?php else: ?>
        <div class="empty-state">
            <p>🎉 Great news! No slow-moving items found in the selected period.</p>
        </div>
        <?php endif; ?>

        <!-- Recommendations -->
        <div style="background: white; padding: 25px; border-radius: 8px; margin-top: 30px; box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);">
            <h3 style="color: #1f2937; margin-bottom: 15px;">📋 Inventory Optimization Recommendations</h3>
            <ul style="color: #6b7280; line-height: 1.8;">
                <li><strong>Dead Stock (0 sales):</strong> These items tie up capital. Consider clearance sales or donation programs.</li>
                <li><strong>Overstocked Items:</strong> Current stock exceeds 2× reorder level. Reduce purchase orders and increase marketing efforts.</li>
                <li><strong>Slow-Moving Items (< 5 sales):</strong> Monitor demand closely. Consider bundling with popular items or running targeted promotions.</li>
                <li><strong>Days Since Last Sale:</strong> The longer without a sale, the higher the risk. Use aggressive discounting strategies for items not sold in 60+ days.</li>
                <li><strong>Inventory Value:</strong> Large inventory values for slow-moving items indicate capital efficiency issues. Prioritize clearing these items.</li>
            </ul>
        </div>

    </div>
</body>
</html>
