<?php
// Inventory System - Purchase History Page
session_start();
require_once 'config/database.php';
require_once 'config/auth_middleware.php';
require_once 'classes/Purchase.php';

// Require login
AuthMiddleware::requireLogin();

$user = $_SESSION['user_data'] ?? [];
$purchase = new Purchase($db);

// Get filters from GET parameters
$supplier_name = $_GET['supplier_name'] ?? '';
$date_from = $_GET['date_from'] ?? date('Y-m-01');
$date_to = $_GET['date_to'] ?? date('Y-m-d');

// Pagination
$limit = 50;
$page = intval($_GET['page'] ?? 1);
$offset = ($page - 1) * $limit;

// Build filters
$filters = array('limit' => $limit, 'offset' => $offset);
if (!empty($supplier_name)) {
    $filters['supplier_name'] = $supplier_name;
}
if (!empty($date_from) && strtotime($date_from) !== false) {
    $filters['date_from'] = $date_from;
}
if (!empty($date_to) && strtotime($date_to) !== false) {
    $filters['date_to'] = $date_to;
}

// Get purchases
$purchases_list = $purchase->getAll($filters);
$total_count = $purchase->getCount($filters);
$total_pages = ceil($total_count / $limit);

// Get summary
$summary = $purchase->getSummaryStats($filters);

// Get unique suppliers
$suppliers = $purchase->getSuppliers();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Purchase History - Inventory System</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .header {
            padding: 30px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .header h1 {
            font-size: 28px;
            margin-bottom: 5px;
        }

        .header p {
            font-size: 14px;
            opacity: 0.9;
        }

        .filter-section {
            padding: 25px 30px;
            background: #f8f9fa;
            border-bottom: 2px solid #e9ecef;
        }

        .filter-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            align-items: flex-end;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        label {
            font-weight: 600;
            margin-bottom: 6px;
            color: #333;
            font-size: 14px;
        }

        input[type="date"],
        select {
            padding: 10px;
            border: 2px solid #e0e0e0;
            border-radius: 6px;
            font-size: 13px;
            font-family: inherit;
        }

        input[type="date"]:focus,
        select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .button-group {
            display: flex;
            gap: 10px;
        }

        button, a.btn {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            font-size: 13px;
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
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
        }

        .btn-secondary {
            background: #f0f0f0;
            color: #333;
        }

        .btn-secondary:hover {
            background: #e0e0e0;
        }

        .btn-add {
            background: #28a745;
            color: white;
        }

        .btn-add:hover {
            background: #218838;
        }

        .stats-section {
            padding: 20px 30px;
            background: #f8f9fa;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
        }

        .stat-card {
            background: white;
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid #667eea;
        }

        .stat-label {
            font-size: 12px;
            color: #999;
            text-transform: uppercase;
            margin-bottom: 5px;
        }

        .stat-value {
            font-size: 20px;
            font-weight: 700;
            color: #333;
        }

        .table-container {
            padding: 30px;
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead {
            background: #f8f9fa;
            border-bottom: 2px solid #e9ecef;
        }

        th {
            padding: 12px;
            text-align: left;
            font-weight: 600;
            color: #333;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        td {
            padding: 14px 12px;
            border-bottom: 1px solid #e9ecef;
            font-size: 13px;
        }

        tbody tr:hover {
            background: #f8f9fa;
        }

        .badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
            background: #e8eaf6;
            color: #3f51b5;
        }

        .amount {
            color: #28a745;
            font-weight: 600;
        }

        .empty-state {
            text-align: center;
            padding: 60px 30px;
            color: #999;
        }

        .empty-state h3 {
            margin-bottom: 10px;
            color: #333;
        }

        /* Pagination */
        .pagination {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #e9ecef;
        }

        .pagination a, .pagination span {
            padding: 8px 12px;
            border-radius: 4px;
            border: 1px solid #e9ecef;
            text-decoration: none;
            color: #667eea;
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

        @media (max-width: 768px) {
            .filter-grid {
                grid-template-columns: 1fr;
            }

            .button-group {
                flex-direction: column;
            }

            button, a.btn {
                width: 100%;
                text-align: center;
            }

            .stats-section {
                grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            }

            table {
                font-size: 12px;
            }

            th, td {
                padding: 8px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>🛍️ Purchase History</h1>
            <p>Track and manage supplier purchases</p>
        </div>

        <!-- Filter Section -->
        <div class="filter-section">
            <form method="GET" action="">
                <div class="filter-grid">
                    <div class="form-group">
                        <label for="supplier_name">Supplier</label>
                        <select id="supplier_name" name="supplier_name">
                            <option value="">-- All Suppliers --</option>
                            <?php foreach ($suppliers as $supplier): ?>
                                <option value="<?php echo htmlspecialchars($supplier); ?>"
                                        <?php echo ($supplier_name === $supplier) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($supplier); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="date_from">From Date</label>
                        <input type="date" id="date_from" name="date_from" 
                               value="<?php echo htmlspecialchars($date_from); ?>">
                    </div>

                    <div class="form-group">
                        <label for="date_to">To Date</label>
                        <input type="date" id="date_to" name="date_to" 
                               value="<?php echo htmlspecialchars($date_to); ?>">
                    </div>

                    <div class="button-group">
                        <button type="submit" class="btn btn-primary">Filter</button>
                        <a href="purchase-history.php" class="btn btn-secondary">Reset</a>
                        <a href="purchase-record.php" class="btn btn-add">+ Add Purchase</a>
                    </div>
                </div>
            </form>
        </div>

        <!-- Summary Statistics -->
        <?php if ($summary): ?>
            <div class="stats-section">
                <div class="stat-card">
                    <div class="stat-label">Total Purchases</div>
                    <div class="stat-value"><?php echo intval($summary['total_purchases']); ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Total Quantity</div>
                    <div class="stat-value"><?php echo intval($summary['total_quantity']); ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Total Spent</div>
                    <div class="stat-value amount">₱<?php echo number_format($summary['total_spent'], 2); ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Avg Cost/Unit</div>
                    <div class="stat-value">₱<?php echo number_format($summary['avg_cost_per_unit'], 2); ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Total Suppliers</div>
                    <div class="stat-value"><?php echo intval($summary['total_suppliers']); ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Total Products</div>
                    <div class="stat-value"><?php echo intval($summary['total_products']); ?></div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Purchase History Table -->
        <div class="table-container">
            <?php if (!empty($purchases_list)): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Invoice Number</th>
                            <th>Product</th>
                            <th>Supplier</th>
                            <th style="text-align: right;">Quantity</th>
                            <th style="text-align: right;">Cost/Unit</th>
                            <th style="text-align: right;">Total Cost</th>
                            <th>Recorded By</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($purchases_list as $pur): ?>
                            <tr>
                                <td><?php echo date('M d, Y', strtotime($pur['purchase_date'])); ?></td>
                                <td><span class="badge"><?php echo htmlspecialchars($pur['invoice_number']); ?></span></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($pur['product_code']); ?></strong><br>
                                    <span style="font-size: 12px; color: #999;">
                                        <?php echo htmlspecialchars($pur['product_name']); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($pur['supplier_name']); ?></td>
                                <td style="text-align: right;">
                                    <?php echo intval($pur['quantity']); ?> 
                                    <?php echo htmlspecialchars($pur['unit_of_measure'] ?? ''); ?>
                                </td>
                                <td style="text-align: right;">₱<?php echo number_format($pur['cost_per_unit'], 2); ?></td>
                                <td style="text-align: right;">
                                    <span class="amount">₱<?php echo number_format($pur['total_cost'], 2); ?></span>
                                </td>
                                <td><?php echo htmlspecialchars($pur['first_name'] ?? $pur['username']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                            <a href="?supplier_name=<?php echo urlencode($supplier_name); ?>&date_from=<?php echo urlencode($date_from); ?>&date_to=<?php echo urlencode($date_to); ?>&page=1">
                                &laquo; First
                            </a>
                            <a href="?supplier_name=<?php echo urlencode($supplier_name); ?>&date_from=<?php echo urlencode($date_from); ?>&date_to=<?php echo urlencode($date_to); ?>&page=<?php echo $page - 1; ?>">
                                &lt; Previous
                            </a>
                        <?php endif; ?>

                        <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                            <?php if ($i === $page): ?>
                                <span class="active"><?php echo $i; ?></span>
                            <?php else: ?>
                                <a href="?supplier_name=<?php echo urlencode($supplier_name); ?>&date_from=<?php echo urlencode($date_from); ?>&date_to=<?php echo urlencode($date_to); ?>&page=<?php echo $i; ?>">
                                    <?php echo $i; ?>
                                </a>
                            <?php endif; ?>
                        <?php endfor; ?>

                        <?php if ($page < $total_pages): ?>
                            <a href="?supplier_name=<?php echo urlencode($supplier_name); ?>&date_from=<?php echo urlencode($date_from); ?>&date_to=<?php echo urlencode($date_to); ?>&page=<?php echo $page + 1; ?>">
                                Next &gt;
                            </a>
                            <a href="?supplier_name=<?php echo urlencode($supplier_name); ?>&date_from=<?php echo urlencode($date_from); ?>&date_to=<?php echo urlencode($date_to); ?>&page=<?php echo $total_pages; ?>">
                                Last &raquo;
                            </a>
                        <?php endif; ?>
                    </div>
                    <div style="text-align: center; font-size: 12px; color: #999; margin-top: 15px;">
                        Showing page <?php echo $page; ?> of <?php echo $total_pages; ?> 
                        (<?php echo $total_count; ?> total <?php echo $total_count === 1 ? 'purchase' : 'purchases'; ?>)
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="empty-state">
                    <h3>No Purchases Found</h3>
                    <p>No purchase records match your filter criteria.</p>
                    <p style="margin-top: 10px; font-size: 12px;">
                        <a href="purchase-record.php" style="color: #667eea; text-decoration: none; font-weight: 600;">Record a new purchase</a>
                    </p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Restrict "To Date" to not be before "From Date"
        document.getElementById('date_from').addEventListener('change', function() {
            const fromDate = this.value;
            const toDateInput = document.getElementById('date_to');
            toDateInput.setAttribute('min', fromDate);
            
            if (toDateInput.value < fromDate) {
                toDateInput.value = fromDate;
            }
        });

        // Auto-update min on page load
        window.addEventListener('load', function() {
            const fromDate = document.getElementById('date_from').value;
            document.getElementById('date_to').setAttribute('min', fromDate);
        });
    </script>
</body>
</html>
