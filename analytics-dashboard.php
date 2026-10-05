<?php
/**
 * Analytics Dashboard - Key Metrics & Visualizations
 * Shows inventory analysis with Chart.js visualizations
 */

require_once 'config/database.php';
require_once 'config/session.php';
require_once 'classes/Analytics.php';
require_once 'classes/ChartUtils.php';

// Set page variables for layout
$page_title = 'Analytics Dashboard';
$active_menu = 'analytics';

// Initialize classes
$analytics = new Analytics($conn);

// Get date parameters
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : date('Y-m-01', strtotime('-3 months'));
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : date('Y-m-d');

// Validate dates
if (!DateTime::createFromFormat('Y-m-d', $date_from) || !DateTime::createFromFormat('Y-m-d', $date_to)) {
    $date_from = date('Y-m-01', strtotime('-3 months'));
    $date_to = date('Y-m-d');
}

// Get all analytics metrics
try {
    $total_inventory_value = $analytics->getTotalInventoryValue();
    $gross_profit_margin = $analytics->getGrossProfitMargin();
    $stock_turnover_rate = $analytics->getStockTurnoverRate();
    $fast_moving_items = $analytics->getFastMovingItems(10);
    $slow_moving_items = $analytics->getSlowMovingItems(10);
    $inventory_composition = $analytics->getInventoryComposition();
    $product_performance = $analytics->getProductPerformance(10);
    $monthly_trend = $analytics->getMonthlySalesTrend(6);
} catch (Exception $e) {
    $error_message = $e->getMessage();
}

include 'includes/layout-header.php';
?>

<!-- Page Header -->
<div class="page-header">
    <h1>Analytics Dashboard</h1>
    <div class="page-header-actions">
        <form method="GET" style="display: flex; gap: 10px; align-items: center;">
            <input type="date" name="date_from" value="<?php echo htmlspecialchars($date_from); ?>" class="form-control form-control-sm" style="width: 150px;">
            <input type="date" name="date_to" value="<?php echo htmlspecialchars($date_to); ?>" class="form-control form-control-sm" style="width: 150px;">
            <button type="submit" class="btn btn-primary btn-sm">Filter</button>
            <a href="analytics-dashboard.php" class="btn btn-secondary btn-sm">Reset</a>
        </form>
    </div>
</div>

<?php if (isset($error_message)): ?>
    <div class="alert alert-danger">
        <i class="fas fa-exclamation-circle"></i> 
        <?php echo htmlspecialchars($error_message); ?>
    </div>
<?php endif; ?>

<!-- Key Metrics Cards -->
<div class="cards-grid">
    <!-- Total Inventory Value -->
    <div class="stat-card green">
        <div class="stat-label">Total Inventory Value</div>
        <div class="stat-value">
            $<?php echo number_format($total_inventory_value ?? 0, 2); ?>
        </div>
        <div class="stat-change">Sum of all product costs</div>
    </div>

    <!-- Gross Profit Margin -->
    <div class="stat-card blue">
        <div class="stat-label">Gross Profit Margin</div>
        <div class="stat-value">
            <?php echo number_format((isset($gross_profit_margin) ? $gross_profit_margin * 100 : 0), 2); ?>%
        </div>
        <div class="stat-change">
            <i class="fas fa-arrow-up text-success"></i> Profitability
        </div>
    </div>

    <!-- Stock Turnover Rate -->
    <div class="stat-card orange">
        <div class="stat-label">Stock Turnover Rate</div>
        <div class="stat-value">
            <?php echo number_format($stock_turnover_rate ?? 0, 2); ?>x
        </div>
        <div class="stat-change">Inventory velocity</div>
    </div>

    <!-- Fast Moving Count -->
    <div class="stat-card red">
        <div class="stat-label">Fast Moving Items</div>
        <div class="stat-value">
            <?php echo count($fast_moving_items ?? []); ?>
        </div>
        <div class="stat-change">High-demand products</div>
    </div>
</div>

<!-- Charts Row -->
<div class="row">
    <!-- Monthly Sales Trend Chart -->
    <div class="col-lg-8">
        <div class="chart-container">
            <h5 style="margin-bottom: 20px; color: #1f2937;">
                <i class="fas fa-chart-line"></i> Monthly Sales Trend (Last 6 Months)
            </h5>
            <canvas id="monthlySalesChart"></canvas>
        </div>
    </div>

    <!-- Inventory Composition Chart -->
    <div class="col-lg-4">
        <div class="chart-container">
            <h5 style="margin-bottom: 20px; color: #1f2937;">
                <i class="fas fa-chart-pie"></i> Inventory by Category
            </h5>
            <canvas id="compositionChart"></canvas>
        </div>
    </div>
</div>

<!-- Fast Moving Items Table -->
<div class="row" style="margin-top: 30px;">
    <div class="col-12">
        <div class="table-responsive-wrapper">
            <h5 style="margin-bottom: 20px; color: #1f2937;">
                <i class="fas fa-fire" style="color: #f59e0b;"></i> Top Fast-Moving Items
            </h5>
            <?php if (!empty($fast_moving_items)): ?>
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Units Sold</th>
                            <th>Revenue</th>
                            <th>Profit</th>
                            <th>Margin %</th>
                            <th>Current Stock</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (array_slice($fast_moving_items, 0, 5) as $item): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($item['product_name']); ?></strong>
                                    <br>
                                    <small class="text-muted"><?php echo htmlspecialchars($item['product_code']); ?></small>
                                </td>
                                <td><?php echo number_format($item['total_quantity_sold'] ?? 0); ?></td>
                                <td>$<?php echo number_format($item['total_sales_amount'] ?? 0, 2); ?></td>
                                <td>$<?php echo number_format($item['total_profit'] ?? 0, 2); ?></td>
                                <td>
                                    <span class="badge badge-success">
                                        <?php echo number_format(($item['profit_margin'] ?? 0) * 100, 2); ?>%
                                    </span>
                                </td>
                                <td><?php echo number_format($item['current_stock'] ?? 0); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="text-muted">No fast-moving items found in this period.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Slow Moving Items Table -->
<div class="row" style="margin-top: 30px;">
    <div class="col-12">
        <div class="table-responsive-wrapper">
            <h5 style="margin-bottom: 20px; color: #1f2937;">
                <i class="fas fa-snail" style="color: #6b7280;"></i> Slow-Moving Items
                <a href="analytics-slow-moving.php" class="btn btn-sm btn-outline-primary" style="float: right;">View Full Report</a>
            </h5>
            <?php if (!empty($slow_moving_items)): ?>
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Units Sold</th>
                            <th>Days Since Sale</th>
                            <th>Stock Level</th>
                            <th>Inventory Value</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (array_slice($slow_moving_items, 0, 5) as $item): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($item['product_name']); ?></strong>
                                    <br>
                                    <small class="text-muted"><?php echo htmlspecialchars($item['product_code']); ?></small>
                                </td>
                                <td><?php echo number_format($item['total_quantity_sold'] ?? 0); ?></td>
                                <td><?php echo isset($item['days_since_sale']) ? $item['days_since_sale'] . ' days' : 'N/A'; ?></td>
                                <td><?php echo number_format($item['current_stock'] ?? 0); ?></td>
                                <td>$<?php echo number_format($item['inventory_value'] ?? 0, 2); ?></td>
                                <td>
                                    <?php 
                                    if (($item['current_stock'] ?? 0) > (($item['reorder_level'] ?? 0) * 2)):
                                        echo '<span class="badge badge-warning">Overstock</span>';
                                    elseif (($item['total_quantity_sold'] ?? 0) == 0):
                                        echo '<span class="badge badge-danger">Dead Stock</span>';
                                    else:
                                        echo '<span class="badge badge-info">Monitor</span>';
                                    endif;
                                    ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="text-muted">No slow-moving items found in this period.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Product Performance -->
<div class="row" style="margin-top: 30px;">
    <div class="col-12">
        <div class="table-responsive-wrapper">
            <h5 style="margin-bottom: 20px; color: #1f2937;">
                <i class="fas fa-star" style="color: #f59e0b;"></i> Top 10 Products by Profit
            </h5>
            <?php if (!empty($product_performance)): ?>
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Lifetime Sales</th>
                            <th>Revenue</th>
                            <th>Profit</th>
                            <th>Margin %</th>
                            <th>Current Stock</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (array_slice($product_performance, 0, 10) as $perf): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($perf['product_name']); ?></strong>
                                    <br>
                                    <small class="text-muted"><?php echo htmlspecialchars($perf['category']); ?></small>
                                </td>
                                <td><?php echo number_format($perf['lifetime_sales'] ?? 0); ?></td>
                                <td>$<?php echo number_format($perf['lifetime_revenue'] ?? 0, 2); ?></td>
                                <td class="text-success">$<?php echo number_format($perf['lifetime_profit'] ?? 0, 2); ?></td>
                                <td>
                                    <span class="badge badge-success">
                                        <?php echo number_format(($perf['markup_percentage'] ?? 0) * 100, 2); ?>%
                                    </span>
                                </td>
                                <td><?php echo number_format($perf['current_stock'] ?? 0); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="text-muted">No product performance data available.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Charts Script -->
<script>
    // Monthly Sales Trend Chart
    const monthlyCtx = document.getElementById('monthlySalesChart');
    if (monthlyCtx && <?php echo json_encode(!empty($monthly_trend)); ?>) {
        const monthlyData = <?php echo json_encode($monthly_trend ?? []); ?>;
        
        if (monthlyData && monthlyData.length > 0) {
            const labels = monthlyData.map(item => item.month);
            const salesData = monthlyData.map(item => parseFloat(item.total_sales) || 0);
            const profitData = monthlyData.map(item => parseFloat(item.gross_profit) || 0);
            
            new Chart(monthlyCtx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: 'Sales',
                            data: salesData,
                            borderColor: '#667eea',
                            backgroundColor: 'transparent',
                            borderWidth: 3,
                            tension: 0.4,
                            pointRadius: 5,
                            pointBackgroundColor: '#667eea',
                            pointBorderColor: '#fff',
                            pointBorderWidth: 2
                        },
                        {
                            label: 'Profit',
                            data: profitData,
                            borderColor: '#10b981',
                            backgroundColor: 'transparent',
                            borderWidth: 3,
                            tension: 0.4,
                            pointRadius: 5,
                            pointBackgroundColor: '#10b981',
                            pointBorderColor: '#fff',
                            pointBorderWidth: 2
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(0, 0, 0, 0.05)'
                            }
                        }
                    }
                }
            });
        }
    }

    // Inventory Composition Chart
    const compositionCtx = document.getElementById('compositionChart');
    if (compositionCtx && <?php echo json_encode(!empty($inventory_composition)); ?>) {
        const compositionData = <?php echo json_encode($inventory_composition ?? []); ?>;
        
        if (compositionData && compositionData.length > 0) {
            const labels = compositionData.map(item => item.category || 'Uncategorized');
            const values = compositionData.map(item => item.product_count || 0);
            const colors = ['#667eea', '#764ba2', '#f093fb', '#4facfe', '#00f2fe', '#43e97b', '#fa709a', '#fee140'];
            
            new Chart(compositionCtx, {
                type: 'doughnut',
                data: {
                    labels: labels,
                    datasets: [{
                        data: values,
                        backgroundColor: colors.slice(0, labels.length),
                        borderColor: '#fff',
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });
        }
    }
</script>

<?php include 'includes/layout-footer.php'; ?>
