<?php
// Inventory System - Stock Movement Report
session_start();
require_once 'config/database.php';
require_once 'config/auth_middleware.php';
require_once 'classes/StockMovement.php';

// Require login
AuthMiddleware::requireLogin();

$user = $_SESSION['user_data'] ?? [];
$stock_movement = new StockMovement($db);

// Get date filters from GET parameters
$date_from = $_GET['date_from'] ?? date('Y-m-01');
$date_to = $_GET['date_to'] ?? date('Y-m-d');
$product_id = $_GET['product_id'] ?? '';

// Validate dates
if (strtotime($date_from) === false) {
    $date_from = date('Y-m-01');
}
if (strtotime($date_to) === false) {
    $date_to = date('Y-m-d');
}

// Get report data
$report_data = $stock_movement->getStockReport($date_from, $date_to, $product_id ?: null);
$summary = $stock_movement->getSummaryStats($date_from, $date_to);

// Handle PDF export
if (isset($_GET['export']) && $_GET['export'] === 'pdf') {
    generatePDF($report_data, $summary, $date_from, $date_to);
    exit;
}

/**
 * Generate PDF report
 */
function generatePDF($report_data, $summary, $date_from, $date_to)
{
    // Check if TCPDF is available
    if (!file_exists('vendor/autoload.php') && !class_exists('TCPDF')) {
        // Fallback to basic HTML to PDF conversion using browser
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="stock-movement-report-' . date('Y-m-d') . '.pdf"');
        
        // Use browser's built-in PDF print functionality by setting content type
        echo '<html><head><title>Stock Movement Report</title>';
        echo '<style>
            body { font-family: Arial, sans-serif; }
            h1 { text-align: center; color: #333; }
            .header { margin-bottom: 20px; font-size: 12px; }
            table { width: 100%; border-collapse: collapse; margin-top: 20px; }
            th { background-color: #667eea; color: white; padding: 10px; text-align: left; border: 1px solid #ddd; }
            td { padding: 8px; border: 1px solid #ddd; }
            tr:nth-child(even) { background-color: #f9f9f9; }
            .summary { margin-top: 30px; padding: 15px; background-color: #f0f0f0; border-radius: 5px; }
        </style></head><body>';
        
        echo '<h1>Stock Movement Report</h1>';
        echo '<div class="header">';
        echo '<p><strong>Report Period:</strong> ' . date('M d, Y', strtotime($date_from)) . ' to ' . date('M d, Y', strtotime($date_to)) . '</p>';
        echo '<p><strong>Generated:</strong> ' . date('M d, Y H:i') . '</p>';
        echo '</div>';
        
        echo '<table>';
        echo '<thead><tr>';
        echo '<th>Date</th>';
        echo '<th>Item Code</th>';
        echo '<th>Product Name</th>';
        echo '<th>Beginning Stock</th>';
        echo '<th>Stock In</th>';
        echo '<th>Stock Out</th>';
        echo '<th>Ending Stock</th>';
        echo '</tr></thead><tbody>';
        
        foreach ($report_data as $row) {
            echo '<tr>';
            echo '<td>' . htmlspecialchars($date_from) . '</td>';
            echo '<td><strong>' . htmlspecialchars($row['product_code']) . '</strong></td>';
            echo '<td>' . htmlspecialchars($row['product_name']) . '</td>';
            echo '<td>' . intval($row['beginning_stock']) . '</td>';
            echo '<td style="color: green;">' . intval($row['stock_in']) . '</td>';
            echo '<td style="color: red;">' . intval($row['stock_out']) . '</td>';
            echo '<td><strong>' . intval($row['ending_stock']) . '</strong></td>';
            echo '</tr>';
        }
        
        echo '</tbody></table>';
        
        if ($summary) {
            echo '<div class="summary">';
            echo '<h3>Summary Statistics</h3>';
            echo '<p>Total Products: ' . intval($summary['total_products']) . '</p>';
            echo '<p>Total Movements: ' . intval($summary['total_movements']) . '</p>';
            echo '<p>Total Stock In: <span style="color: green;">' . intval($summary['total_stock_in']) . '</span></p>';
            echo '<p>Total Stock Out: <span style="color: red;">' . intval($summary['total_stock_out']) . '</span></p>';
            echo '<p>Net Change: <strong>' . intval($summary['total_stock_in'] - $summary['total_stock_out']) . '</strong></p>';
            echo '</div>';
        }
        
        echo '</body></html>';
        return;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stock Movement Report - Inventory System</title>
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

        .btn-export {
            background: #28a745;
            color: white;
        }

        .btn-export:hover {
            background: #218838;
            box-shadow: 0 5px 15px rgba(40, 167, 69, 0.3);
        }

        .btn-reset {
            background: #f0f0f0;
            color: #333;
        }

        .btn-reset:hover {
            background: #e0e0e0;
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

        .code-badge {
            background: #e8eaf6;
            color: #3f51b5;
            padding: 4px 8px;
            border-radius: 4px;
            font-weight: 600;
            font-family: monospace;
            font-size: 12px;
        }

        .stock-in {
            color: #28a745;
            font-weight: 600;
        }

        .stock-out {
            color: #dc3545;
            font-weight: 600;
        }

        .ending-stock {
            font-weight: 700;
            color: #667eea;
        }

        .empty-state {
            text-align: center;
            padding: 60px 30px;
            color: #999;
        }

        .empty-state svg {
            width: 80px;
            height: 80px;
            margin-bottom: 20px;
            opacity: 0.5;
        }

        .empty-state h3 {
            margin-bottom: 10px;
            color: #333;
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
            <h1>📊 Stock Movement Report</h1>
            <p>Track and analyze inventory movements over time</p>
        </div>

        <!-- Filter Section -->
        <div class="filter-section">
            <form method="GET" action="">
                <div class="filter-grid">
                    <div class="form-group">
                        <label for="date_from">From Date</label>
                        <input type="date" id="date_from" name="date_from" 
                               value="<?php echo htmlspecialchars($date_from); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="date_to">To Date</label>
                        <input type="date" id="date_to" name="date_to" 
                               value="<?php echo htmlspecialchars($date_to); ?>" required>
                    </div>

                    <div class="button-group">
                        <button type="submit" class="btn btn-primary">Filter Report</button>
                        <a href="stock-movement-report.php" class="btn btn-reset">Reset</a>
                        <a href="?date_from=<?php echo htmlspecialchars($date_from); ?>&date_to=<?php echo htmlspecialchars($date_to); ?>&export=pdf" 
                           class="btn btn-export" title="Export to PDF">
                            📥 Export PDF
                        </a>
                    </div>
                </div>
            </form>
        </div>

        <!-- Summary Statistics -->
        <?php if ($summary): ?>
            <div class="stats-section">
                <div class="stat-card">
                    <div class="stat-label">Total Products</div>
                    <div class="stat-value"><?php echo intval($summary['total_products']); ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Total Movements</div>
                    <div class="stat-value"><?php echo intval($summary['total_movements']); ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Stock In</div>
                    <div class="stat-value stock-in">+<?php echo intval($summary['total_stock_in']); ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Stock Out</div>
                    <div class="stat-value stock-out">-<?php echo intval($summary['total_stock_out']); ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Net Change</div>
                    <div class="stat-value ending-stock">
                        <?php 
                        $net = intval($summary['total_stock_in']) - intval($summary['total_stock_out']);
                        echo ($net >= 0 ? '+' : '') . $net;
                        ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Report Table -->
        <div class="table-container">
            <?php if (!empty($report_data)): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Item Code</th>
                            <th>Product Name</th>
                            <th style="text-align: right;">Beginning Stock</th>
                            <th style="text-align: right;">Stock In</th>
                            <th style="text-align: right;">Stock Out</th>
                            <th style="text-align: right;">Ending Stock</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($report_data as $row): ?>
                            <tr>
                                <td><?php echo date('M d, Y', strtotime($date_from)); ?></td>
                                <td><span class="code-badge"><?php echo htmlspecialchars($row['product_code']); ?></span></td>
                                <td><?php echo htmlspecialchars($row['product_name']); ?></td>
                                <td style="text-align: right;"><?php echo intval($row['beginning_stock']); ?></td>
                                <td style="text-align: right;">
                                    <span class="stock-in">+<?php echo intval($row['stock_in']); ?></span>
                                </td>
                                <td style="text-align: right;">
                                    <span class="stock-out">-<?php echo intval($row['stock_out']); ?></span>
                                </td>
                                <td style="text-align: right;">
                                    <span class="ending-stock"><?php echo intval($row['ending_stock']); ?></span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="empty-state">
                    <h3>No Stock Movements Found</h3>
                    <p>There are no stock movements recorded for the selected date range.</p>
                    <p style="margin-top: 10px; font-size: 12px;">Try adjusting your filters or start recording stock movements in your inventory.</p>
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
