<?php
/**
 * Dashboard - Main Landing Page
 * Shows key metrics and quick stats
 */

require_once 'config/database.php';
require_once 'config/session.php';
require_once 'classes/Product.php';
require_once 'classes/Analytics.php';
require_once 'classes/Alerts.php';

// Set page variables for layout
$page_title = 'Dashboard';
$active_menu = 'dashboard';

// Get user data for layout
$user = SessionManager::getUserData() ?? ['user_id' => 1, 'username' => 'system', 'first_name' => 'User', 'last_name' => ''];

// Initialize classes
$product = new Product($conn, $user['user_id'] ?? 1);
$analytics = new Analytics($conn);
$alerts = new Alerts($conn);

// Get dashboard data
try {
    // Get inventory stats
    $inventoryStats = $product->getInventoryStats();
    
    // Get analytics metrics
    $totalValue = $analytics->getTotalInventoryValue();
    $fastMoving = $analytics->getFastMovingItems(5);
    $slowMoving = $analytics->getSlowMovingItems(5);
    $grossMargin = $analytics->getGrossProfitMargin();
    $stockTurnover = $analytics->getStockTurnoverRate();
    
    // Get alert information
    $alertCounts = $alerts->getAlertCounts();
    $criticalAlerts = $alerts->getCriticalAlerts();
    
    // Get inventory composition
    $composition = $analytics->getInventoryComposition();
    
    // Get expiring soon items (4 months)
    $expiryAlerts = $alerts->getExpiryAlerts();
    $expiryCount = count($expiryAlerts ?? []);
    
    // Get today's sales
    $todayDate = date('Y-m-d');
    $todayQuery = $conn->prepare(
        "SELECT COUNT(*) as count, SUM(quantity_sold) as qty, SUM(quantity_sold * unit_price) as total 
         FROM sales 
         WHERE DATE(created_at) = ? "
    );
    $todayQuery->bind_param('s', $todayDate);
    $todayQuery->execute();
    $todayResult = $todayQuery->get_result()->fetch_assoc();
    $todaySales = $todayResult['total'] ?? 0;
    $todayTransactions = $todayResult['count'] ?? 0;
    
    // Get daily stock movements (today)
    $movementQuery = $conn->prepare(
        "SELECT COUNT(*) as count, SUM(quantity) as total 
         FROM stock_movements 
         WHERE DATE(created_at) = ? "
    );
    $movementQuery->bind_param('s', $todayDate);
    $movementQuery->execute();
    $movementResult = $movementQuery->get_result()->fetch_assoc();
    
    // Get sales trend data (last 7 days)
    $salesTrendQuery = $conn->prepare(
        "SELECT DATE(created_at) as date, SUM(quantity_sold * unit_price) as sales, COUNT(*) as transactions 
         FROM sales 
         WHERE created_at >= DATE_SUB(?, INTERVAL 7 DAY) 
         GROUP BY DATE(created_at) 
         ORDER BY date ASC"
    );
    $salesTrendQuery->bind_param('s', $todayDate);
    $salesTrendQuery->execute();
    $salesTrendData = $salesTrendQuery->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // Get stock movement trend (last 7 days)
    $movementTrendQuery = $conn->prepare(
        "SELECT DATE(created_at) as date, movement_type, SUM(quantity) as total 
         FROM stock_movements 
         WHERE created_at >= DATE_SUB(?, INTERVAL 7 DAY) 
         GROUP BY DATE(created_at), movement_type 
         ORDER BY date ASC"
    );
    $movementTrendQuery->bind_param('s', $todayDate);
    $movementTrendQuery->execute();
    $movementTrendData = $movementTrendQuery->get_result()->fetch_all(MYSQLI_ASSOC);
    
} catch (Exception $e) {
    $error_message = $e->getMessage();
}

include 'includes/layout-header.php';
?>

<!-- Page Header -->
<div class="page-header">
    <h1>Dashboard</h1>
    <div class="page-header-actions">
        <a href="products.php" class="btn btn-primary btn-sm">
            <i class="fas fa-plus"></i> Add Product
        </a>
        <a href="stock-adjustment.php" class="btn btn-primary btn-sm">
            <i class="fas fa-exchange-alt"></i> Stock Movement
        </a>
    </div>
</div>

<?php if (isset($error_message)): ?>
    <div class="alert alert-danger">
        <i class="fas fa-exclamation-circle"></i> 
        <?php echo htmlspecialchars($error_message); ?>
    </div>
<?php endif; ?>

<!-- Quick Stats Cards (6 Metrics) -->
<div class="cards-grid">
    <!-- Total Products -->
    <div class="stat-card green">
        <div class="stat-label">Total Products</div>
        <div class="stat-value">
            <?php echo isset($inventoryStats['total_products']) ? $inventoryStats['total_products'] : 0; ?>
        </div>
        <div class="stat-change">
            Active products in stock
        </div>
    </div>

    <!-- Low Stock Items -->
    <div class="stat-card orange">
        <div class="stat-label">Low Stock Items</div>
        <div class="stat-value">
            <?php echo isset($inventoryStats['low_stock_count']) ? $inventoryStats['low_stock_count'] : 0; ?>
        </div>
        <div class="stat-change text-warning">
            <i class="fas fa-exclamation-triangle"></i> Need reordering
        </div>
    </div>

    <!-- Expiring Soon Items -->
    <div class="stat-card red">
        <div class="stat-label">Expiring Soon</div>
        <div class="stat-value">
            <?php echo $expiryCount; ?>
        </div>
        <div class="stat-change text-danger">
            <i class="fas fa-calendar-times"></i> Within 4 months
        </div>
    </div>

    <!-- Total Sales Today -->
    <div class="stat-card blue">
        <div class="stat-label">Sales Today</div>
        <div class="stat-value">
            ₱<?php echo number_format($todaySales, 2); ?>
        </div>
        <div class="stat-change">
            <i class="fas fa-shopping-cart"></i> 
            <?php echo $todayTransactions; ?> transaction<?php echo $todayTransactions !== 1 ? 's' : ''; ?>
        </div>
    </div>

    <!-- Total Inventory Value -->
    <div class="stat-card green">
        <div class="stat-label">Inventory Value</div>
        <div class="stat-value">
            ₱<?php echo number_format($totalValue ?? 0, 2); ?>
        </div>
        <div class="stat-change">
            <i class="fas fa-arrow-up text-success"></i> 
            <?php echo isset($inventoryStats['total_items']) ? $inventoryStats['total_items'] . ' items' : '0 items'; ?>
        </div>
    </div>

    <!-- Active Alerts -->
    <div class="stat-card red">
        <div class="stat-label">Active Alerts</div>
        <div class="stat-value">
            <?php echo $alertCounts['active'] ?? 0; ?>
        </div>
        <div class="stat-change" style="<?php echo ($alertCounts['critical'] ?? 0) > 0 ? 'color: #ef4444;' : ''; ?>">
            <i class="fas fa-bell"></i> 
            <?php echo ($alertCounts['critical'] ?? 0) . ' critical'; ?>
        </div>
    </div>
</div>

<!-- Critical Alerts Alert Box -->
<?php if (!empty($criticalAlerts)): ?>
    <div class="alert alert-danger">
        <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px;">
            <i class="fas fa-exclamation-circle" style="font-size: 20px;"></i>
            <strong>Critical Alerts:</strong>
        </div>
        <ul style="margin: 10px 0 0 30px;">
            <?php foreach (array_slice($criticalAlerts, 0, 3) as $alert): ?>
                <li>
                    <?php echo htmlspecialchars($alert['product_name']); ?> 
                    (<?php echo htmlspecialchars(ucfirst(strtolower($alert['alert_type']))); ?>)
                </li>
            <?php endforeach; ?>
        </ul>
        <a href="alerts-dashboard.php" class="btn btn-sm btn-outline-danger mt-2">
            View All Alerts
        </a>
    </div>
<?php endif; ?>

<!-- Main Content Row -->
<div class="row">
    <!-- Left Column (2/3) -->
    <div class="col-lg-8">
        <!-- Fast Moving Items -->
        <div class="table-responsive-wrapper">
            <h5 style="margin-bottom: 20px; color: #1f2937;">
                <i class="fas fa-fire" style="color: #f59e0b;"></i> Top Fast Moving Items
            </h5>
            <?php if (!empty($fastMoving)): ?>
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Current Stock</th>
                            <th>Movements (30d)</th>
                            <th>Turnover Rate</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (array_slice($fastMoving, 0, 5) as $item): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($item['product_name']); ?></strong>
                                    <br>
                                    <small class="text-muted"><?php echo htmlspecialchars($item['product_code']); ?></small>
                                </td>
                                <td><?php echo number_format($item['current_stock']); ?> units</td>
                                <td>
                                    <span class="badge badge-success">
                                        <?php echo isset($item['movement_count']) ? $item['movement_count'] : 0; ?>
                                    </span>
                                </td>
                                <td><?php echo number_format($item['turnover_rate'] ?? 0, 2); ?>x</td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="text-muted">No fast-moving items data available yet.</p>
            <?php endif; ?>
        </div>

        <!-- Inventory Composition Chart -->
        <div class="chart-container">
            <h5 style="margin-bottom: 20px; color: #1f2937;">
                <i class="fas fa-chart-pie"></i> Inventory by Category
            </h5>
            <canvas id="inventoryChart"></canvas>
        </div>
    </div>

    <!-- Right Column (1/3) -->
    <div class="col-lg-4">
        <!-- Key Metrics -->
        <div class="table-responsive-wrapper">
            <h5 style="margin-bottom: 20px; color: #1f2937;">
                <i class="fas fa-chart-line"></i> Key Metrics
            </h5>
            <table class="table table-sm" style="font-size: 14px;">
                <tr>
                    <td><strong>Gross Profit Margin</strong></td>
                    <td class="text-end">
                        <span class="badge badge-success">
                            <?php echo number_format(($grossMargin ?? 0) * 100, 2); ?>%
                        </span>
                    </td>
                </tr>
                <tr>
                    <td><strong>Stock Turnover Rate</strong></td>
                    <td class="text-end">
                        <span class="badge badge-info">
                            <?php echo number_format($stockTurnover ?? 0, 2); ?>x
                        </span>
                    </td>
                </tr>
                <tr>
                    <td><strong>Slow Moving Items</strong></td>
                    <td class="text-end">
                        <span class="badge badge-warning">
                            <?php echo count($slowMoving ?? []); ?>
                        </span>
                    </td>
                </tr>
                <tr>
                    <td><strong>Expired Products</strong></td>
                    <td class="text-end">
                        <span class="badge badge-danger">
                            <?php echo isset($alertCounts['expiry']) ? $alertCounts['expiry'] : 0; ?>
                        </span>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Recent Activities -->
        <div class="table-responsive-wrapper" style="margin-top: 20px;">
            <h5 style="margin-bottom: 20px; color: #1f2937;">
                <i class="fas fa-history"></i> Quick Actions
            </h5>
            <div style="display: flex; flex-direction: column; gap: 10px;">
                <a href="products.php" class="btn btn-outline-primary btn-sm">
                    <i class="fas fa-boxes"></i> View All Products
                </a>
                <a href="stock-movement-report.php" class="btn btn-outline-primary btn-sm">
                    <i class="fas fa-exchange-alt"></i> Stock Report
                </a>
                <a href="sales-record.php" class="btn btn-outline-primary btn-sm">
                    <i class="fas fa-plus"></i> Record Sale
                </a>
                <a href="purchase-record.php" class="btn btn-outline-primary btn-sm">
                    <i class="fas fa-shopping-cart"></i> New Purchase
                </a>
                <a href="alerts-dashboard.php" class="btn btn-outline-danger btn-sm">
                    <i class="fas fa-bell"></i> View Alerts (<?php echo $alertCounts['active'] ?? 0; ?>)
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Charts Row (Sales & Stock Movement) -->
<div class="row" style="margin-top: 30px;">
    <!-- Sales Trend Chart (7 days) -->
    <div class="col-lg-6">
        <div class="chart-container">
            <h5 style="margin-bottom: 20px; color: #1f2937;">
                <i class="fas fa-chart-line"></i> Sales Trend (Last 7 Days)
            </h5>
            <canvas id="salesTrendChart"></canvas>
        </div>
    </div>

    <!-- Stock Movement Chart (7 days) -->
    <div class="col-lg-6">
        <div class="chart-container">
            <h5 style="margin-bottom: 20px; color: #1f2937;">
                <i class="fas fa-exchange-alt"></i> Stock Movement (Last 7 Days)
            </h5>
            <canvas id="movementChart"></canvas>
        </div>
    </div>
</div>

<!-- Footer -->
<div class="row" style="margin-top: 40px;">
    <div class="col-12">
        <div style="background: #f3f4f6; padding: 20px; border-radius: 8px; text-align: center; color: #6b7280;">
            <p style="margin: 0; font-size: 12px;">
                Welcome back, <strong><?php echo htmlspecialchars($user['first_name'] ?? 'User'); ?></strong>! 
                Your inventory system is running smoothly.
            </p>
        </div>
    </div>
</div>

<script>
    // Inventory Composition Chart
    const compositionCtx = document.getElementById('inventoryChart');
    if (compositionCtx) {
        const compositionData = <?php echo json_encode($composition ?? []); ?>;
        
        if (compositionData && compositionData.length > 0) {
            const labels = compositionData.map(item => item.category || 'Uncategorized');
            const values = compositionData.map(item => item.count);
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

    // Sales Trend Chart (Last 7 days)
    const salesCtx = document.getElementById('salesTrendChart');
    if (salesCtx) {
        const salesData = <?php echo json_encode($salesTrendData ?? []); ?>;
        
        if (salesData && salesData.length > 0) {
            const dates = salesData.map(item => item.date);
            const sales = salesData.map(item => parseFloat(item.sales) || 0);
            
            new Chart(salesCtx, {
                type: 'line',
                data: {
                    labels: dates,
                    datasets: [{
                        label: 'Daily Sales',
                        data: sales,
                        borderColor: '#10b981',
                        backgroundColor: 'rgba(16, 185, 129, 0.1)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.4,
                        pointRadius: 5,
                        pointBackgroundColor: '#10b981',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                        pointHoverRadius: 7
                    }]
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

    // Stock Movement Chart (Last 7 days)
    const movementCtx = document.getElementById('movementChart');
    if (movementCtx) {
        const movementData = <?php echo json_encode($movementTrendData ?? []); ?>;
        
        // Reorganize data by date
        const movementByDate = {};
        const types = { 'IN': 0, 'OUT': 0, 'ADJUSTMENT': 0, 'RETURN': 0, 'DAMAGE': 0 };
        
        if (movementData && movementData.length > 0) {
            movementData.forEach(item => {
                if (!movementByDate[item.date]) {
                    movementByDate[item.date] = {...types};
                }
                const type = item.movement_type.toUpperCase();
                movementByDate[item.date][type] = parseFloat(item.total) || 0;
            });
            
            const dates = Object.keys(movementByDate).sort();
            const inData = dates.map(d => movementByDate[d]['IN']);
            const outData = dates.map(d => movementByDate[d]['OUT']);
            const adjustData = dates.map(d => movementByDate[d]['ADJUSTMENT']);
            
            new Chart(movementCtx, {
                type: 'bar',
                data: {
                    labels: dates,
                    datasets: [
                        {
                            label: 'Stock IN',
                            data: inData,
                            backgroundColor: '#10b981',
                            borderRadius: 4
                        },
                        {
                            label: 'Stock OUT',
                            data: outData,
                            backgroundColor: '#ef4444',
                            borderRadius: 4
                        },
                        {
                            label: 'Adjustments',
                            data: adjustData,
                            backgroundColor: '#f59e0b',
                            borderRadius: 4
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
</script>

<?php include 'includes/layout-footer.php'; ?>

