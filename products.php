<?php
/**
 * Products List Page
 * Displays all products with search, filter, and management options
 */

require_once 'config/database.php';
require_once 'config/session.php';
require_once 'classes/Product.php';
require_once 'classes/Alerts.php';

// Set page variables for layout
$page_title = 'Products';
$active_menu = 'products';

// Initialize classes
$product = new Product($conn);
$alerts = new Alerts($conn);

// Get statistics
$stats = $product->getInventoryStats();
$stats_data = $stats['success'] ? $stats['data'] : null;

// Get alert counts
$alert_counts = $alerts->getAlertCounts();
$critical_alerts = $alerts->getCriticalAlerts();

// Auto-trigger alert creation
$alerts->createExpiryAlerts();
$alerts->createReorderAlerts();

// Handle search and filters
$search = $_GET['search'] ?? '';
$category = $_GET['category'] ?? '';
$brand = $_GET['brand'] ?? '';
$supplier = $_GET['supplier'] ?? '';
$low_stock = isset($_GET['low_stock']);
$sort = $_GET['sort'] ?? 'product_name';
$order = (isset($_GET['order']) && $_GET['order'] === 'DESC') ? 'DESC' : 'ASC';

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$items_per_page = 20;
$offset = ($page - 1) * $items_per_page;

// Get filter options
$categories = $product->getUniqueValues('category');
$brands = $product->getUniqueValues('brand');
$suppliers = $product->getUniqueValues('supplier');

// Build filter array
$filter = [];
if (!empty($search)) {
    $filter['search'] = $search;
}
if (!empty($category)) {
    $filter['category'] = $category;
}
if (!empty($brand)) {
    $filter['brand'] = $brand;
}
if (!empty($supplier)) {
    $filter['supplier'] = $supplier;
}
if ($low_stock) {
    $filter['low_stock'] = true;
}

// Get products
$result = $product->getAll($filter, $sort, $order, $items_per_page, $offset);
$products_list = $result['success'] ? $result['data'] : [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Master - Products List</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f5f5;
            padding-bottom: 40px;
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

        .navbar-menu {
            display: flex;
            gap: 20px;
            align-items: center;
        }

        .navbar-menu a {
            color: white;
            text-decoration: none;
            font-size: 14px;
            transition: opacity 0.3s;
        }

        .navbar-menu a:hover {
            opacity: 0.8;
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
        }

        .container {
            max-width: 1400px;
            margin: 30px auto;
            padding: 0 20px;
        }

        .page-header {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .page-header h1 {
            color: #333;
            font-size: 32px;
            margin-bottom: 5px;
        }

        .page-header p {
            color: #666;
            font-size: 14px;
        }

        .header-actions {
            display: flex;
            gap: 15px;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
        }

        .btn-secondary {
            background: #ddd;
            color: #333;
        }

        .btn-secondary:hover {
            background: #ccc;
        }

        .btn-sm {
            padding: 6px 12px;
            font-size: 12px;
        }

        .btn-danger {
            background: #ff6b6b;
            color: white;
        }

        .btn-danger:hover {
            background: #ff5252;
        }

        .btn-warning {
            background: #ffd43b;
            color: #333;
        }

        .btn-warning:hover {
            background: #ffca3a;
        }

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            border-left: 4px solid #667eea;
        }

        .stat-card p {
            color: #666;
            font-size: 12px;
            margin-bottom: 8px;
        }

        .stat-card h3 {
            color: #667eea;
            font-size: 28px;
        }

        .stat-card.warning {
            border-left-color: #ffd43b;
        }

        .stat-card.warning h3 {
            color: #ffd43b;
        }

        .stat-card.danger {
            border-left-color: #ff6b6b;
        }

        .stat-card.danger h3 {
            color: #ff6b6b;
        }

        /* Filter Section */
        .filters-section {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }

        .filters-section h3 {
            margin-bottom: 15px;
            color: #333;
            font-size: 16px;
        }

        .filters-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-bottom: 15px;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
        }

        .filter-group label {
            color: #666;
            font-size: 12px;
            font-weight: 600;
            margin-bottom: 5px;
        }

        .filter-group input,
        .filter-group select {
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }

        .filter-group input:focus,
        .filter-group select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .filter-actions {
            display: flex;
            gap: 10px;
        }

        /* Products Table */
        .products-section {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .products-header {
            background: #f9f9f9;
            padding: 15px 20px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .products-header h3 {
            color: #333;
            font-size: 16px;
            margin: 0;
        }

        .table-responsive {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead {
            background: #f9f9f9;
        }

        th {
            padding: 12px 20px;
            text-align: left;
            color: #666;
            font-size: 12px;
            font-weight: 600;
            border-bottom: 1px solid #eee;
            white-space: nowrap;
        }

        td {
            padding: 12px 20px;
            color: #333;
            font-size: 13px;
            border-bottom: 1px solid #eee;
        }

        tbody tr:hover {
            background: #f9f9f9;
        }

        .code {
            font-family: monospace;
            background: #f0f0f0;
            padding: 2px 6px;
            border-radius: 3px;
            font-weight: 600;
            color: #667eea;
        }

        .stock-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
        }

        .stock-ok {
            background: #d4edda;
            color: #155724;
        }

        .stock-low {
            background: #fff3cd;
            color: #856404;
        }

        .stock-critical {
            background: #f8d7da;
            color: #721c24;
        }

        .value-cell {
            font-weight: 600;
            color: #667eea;
        }

        .actions {
            display: flex;
            gap: 5px;
        }

        .actions a {
            padding: 5px 10px;
            border-radius: 3px;
            text-decoration: none;
            font-size: 12px;
            transition: all 0.3s;
        }

        .action-edit {
            background: #667eea;
            color: white;
        }

        .action-edit:hover {
            background: #5568d3;
        }

        .action-delete {
            background: #ff6b6b;
            color: white;
            cursor: pointer;
        }

        .action-delete:hover {
            background: #ff5252;
        }

        .empty-state {
            padding: 40px;
            text-align: center;
            color: #999;
        }

        .empty-state-icon {
            font-size: 48px;
            margin-bottom: 15px;
        }

        .pagination {
            padding: 20px;
            text-align: center;
            border-top: 1px solid #eee;
        }

        .pagination a,
        .pagination span {
            display: inline-block;
            padding: 8px 12px;
            margin: 0 3px;
            border: 1px solid #ddd;
            border-radius: 3px;
            color: #667eea;
            text-decoration: none;
            transition: all 0.3s;
        }

        .pagination a:hover {
            background: #667eea;
            color: white;
        }

        .pagination .active {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }

        .alert {
            padding: 12px 20px;
            margin-bottom: 20px;
            border-radius: 5px;
            font-size: 14px;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        @media (max-width: 768px) {
            .page-header {
                flex-direction: column;
                gap: 15px;
                align-items: flex-start;
            }

            .filters-grid {
                grid-template-columns: 1fr;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            table {
                font-size: 12px;
            }

            th, td {
                padding: 8px 10px;
            }

            .actions {
                flex-direction: column;
            }
        }
    </style>
</head>
    <!-- Navbar -->
    <nav class="navbar">
        <div class="navbar-brand">📊 Inventory Master</div>
        <div class="navbar-menu">
            <a href="dashboard.php">Dashboard</a>
            <a href="products.php">Products</a>
            <?php $userData = SessionManager::getUserData(); ?>
            <span>👤 <?php echo htmlspecialchars($userData ? ($userData['first_name'] ?? $userData['username']) : 'User'); ?></span>
            <a href="logout.php" class="logout-btn">🚪 Logout</a>
        </div>
    </nav>

    <div class="container">
        <!-- Alert Popup Component -->
        <?php include 'includes/alert-popup.php'; ?>

        <!-- Page Header -->
        <div class="page-header">
            <div>
                <h1>📦 Inventory Master List</h1>
                <p>Manage your product database and inventory levels</p>
            </div>
            <div class="header-actions">
                <a href="products-add.php" class="btn btn-primary">+ Add New Product</a>
                <a href="stock-adjustment.php" class="btn btn-secondary">📊 Stock Adjustment</a>
                <a href="stock-movement-report.php" class="btn btn-secondary">📈 Movement Report</a>
                <a href="purchase-record.php" class="btn btn-secondary">🛍️ Record Purchase</a>
                <a href="purchase-history.php" class="btn btn-secondary">📋 Purchase History</a>
                <a href="sales-record.php" class="btn btn-secondary">💰 Record Sale</a>
                <a href="sales-summary-report.php" class="btn btn-secondary">📊 Sales Summary</a>
                <a href="analytics-dashboard.php" class="btn btn-secondary">📈 Analytics</a>
                <a href="alerts-dashboard.php" class="btn btn-secondary">🔔 Alerts<?php if ($alert_counts['TOTAL'] > 0): ?><span style="display: inline-block; background: #dc2626; color: white; padding: 2px 6px; border-radius: 12px; font-size: 12px; font-weight: 700; margin-left: 6px;"><?php echo $alert_counts['TOTAL']; ?></span><?php endif; ?></a>
            </div>
        </div>

        <!-- Stats Cards -->
        <?php if ($stats_data): ?>
        <div class="stats-grid">
            <div class="stat-card">
                <p>Total Products</p>
                <h3><?php echo number_format($stats_data['total_products']); ?></h3>
            </div>
            <div class="stat-card">
                <p>Total Stock Units</p>
                <h3><?php echo number_format($stats_data['total_stock']); ?></h3>
            </div>
            <div class="stat-card">
                <p>Inventory Value (Cost)</p>
                <h3>₱<?php echo number_format($stats_data['total_cost_value'], 2); ?></h3>
            </div>
            <div class="stat-card">
                <p>Potential Revenue</p>
                <h3>₱<?php echo number_format($stats_data['total_selling_value'], 2); ?></h3>
            </div>
            <div class="stat-card warning">
                <p>Low Stock Items</p>
                <h3><?php echo number_format($stats_data['low_stock_count']); ?></h3>
            </div>
            <div class="stat-card danger">
                <p>Expired Products</p>
                <h3><?php echo number_format($stats_data['expired_count']); ?></h3>
            </div>
        </div>
        <?php endif; ?>

        <!-- Filters Section -->
        <div class="filters-section">
            <h3>🔍 Search & Filter</h3>
            <form method="GET" action="">
                <div class="filters-grid">
                    <div class="filter-group">
                        <label>Search</label>
                        <input type="text" name="search" placeholder="Code or Name..." 
                               value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
                    </div>
                    <div class="filter-group">
                        <label>Category</label>
                        <select name="category">
                            <option value="">All Categories</option>
                            <?php foreach ($categories['data'] ?? [] as $cat): ?>
                                <option value="<?php echo htmlspecialchars($cat); ?>" 
                                    <?php echo ($_GET['category'] ?? '') === $cat ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label>Brand</label>
                        <select name="brand">
                            <option value="">All Brands</option>
                            <?php foreach ($brands['data'] ?? [] as $brnd): ?>
                                <option value="<?php echo htmlspecialchars($brnd); ?>" 
                                    <?php echo ($_GET['brand'] ?? '') === $brnd ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($brnd); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label>Supplier</label>
                        <select name="supplier">
                            <option value="">All Suppliers</option>
                            <?php foreach ($suppliers['data'] ?? [] as $supp): ?>
                                <option value="<?php echo htmlspecialchars($supp); ?>" 
                                    <?php echo ($_GET['supplier'] ?? '') === $supp ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($supp); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label>&nbsp;</label>
                        <label style="display: flex; align-items: center; margin: 0;">
                            <input type="checkbox" name="low_stock" style="margin-right: 5px;" 
                                   <?php echo !empty($_GET['low_stock']) ? 'checked' : ''; ?>>
                            Low Stock Only
                        </label>
                    </div>
                </div>
                <div class="filter-actions">
                    <button type="submit" class="btn btn-primary btn-sm">Apply Filters</button>
                    <a href="products.php" class="btn btn-secondary btn-sm">Clear Filters</a>
                </div>
            </form>
        </div>

        <!-- Products Table -->
        <div class="products-section">
            <div class="products-header">
                <h3>📋 Products (<?php echo count($products_list); ?>)</h3>
            </div>

            <?php if (!empty($products_list)): ?>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Product Name</th>
                            <th>Category</th>
                            <th>Brand</th>
                            <th>Unit</th>
                            <th>Stock</th>
                            <th>Cost Price</th>
                            <th>Selling Price</th>
                            <th>Inventory Value</th>
                            <th>Expiry</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products_list as $prod): ?>
                        <tr>
                            <td><span class="code"><?php echo htmlspecialchars($prod['product_code']); ?></span></td>
                            <td><strong><?php echo htmlspecialchars($prod['product_name']); ?></strong></td>
                            <td><?php echo htmlspecialchars($prod['category'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($prod['brand'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($prod['unit_of_measure'] ?? '-'); ?></td>
                            <td>
                                <?php 
                                $stock = $prod['current_stock'];
                                $reorder = $prod['reorder_level'];
                                if ($stock == 0) {
                                    $class = 'stock-critical';
                                } elseif ($stock <= $reorder) {
                                    $class = 'stock-low';
                                } else {
                                    $class = 'stock-ok';
                                }
                                ?>
                                <span class="stock-badge <?php echo $class; ?>">
                                    <?php echo number_format($stock); ?>
                                </span>
                            </td>
                            <td>₱<?php echo number_format($prod['cost_price'], 2); ?></td>
                            <td>₱<?php echo number_format($prod['selling_price'], 2); ?></td>
                            <td class="value-cell">₱<?php echo number_format($prod['inventory_value'], 2); ?></td>
                            <td>
                                <?php 
                                if ($prod['expiry_date']) {
                                    $expiry = new DateTime($prod['expiry_date']);
                                    $today = new DateTime();
                                    $diff = $today->diff($expiry);
                                    
                                    if ($expiry < $today) {
                                        echo '<span class="stock-badge stock-critical">Expired</span>';
                                    } elseif ($diff->days <= 7) {
                                        echo '<span class="stock-badge stock-low">expires in ' . $diff->days . ' days</span>';
                                    } else {
                                        echo date('M d, Y', strtotime($prod['expiry_date']));
                                    }
                                } else {
                                    echo '-';
                                }
                                ?>
                            </td>
                            <td>
                                <div class="actions">
                                    <a href="products-edit.php?id=<?php echo $prod['id']; ?>" class="action-edit btn-sm">Edit</a>
                                    <a href="products-delete.php?id=<?php echo $prod['id']; ?>" class="action-delete btn-sm" 
                                       onclick="return confirm('Are you sure?');">Delete</a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="empty-state">
                <div class="empty-state-icon">📭</div>
                <p>No products found. <a href="products-add.php" style="color: #667eea;">Add your first product</a></p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
