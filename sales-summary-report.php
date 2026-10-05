<?php
// Inventory System - Sales Summary Report
session_start();
require_once 'config/database.php';
require_once 'config/auth_middleware.php';
require_once 'classes/Sales.php';

// Require login
AuthMiddleware::requireLogin();

$user = $_SESSION['user_data'] ?? [];
$sales = new Sales($db);

// Get date filters from GET parameters
$date_from = $_GET['date_from'] ?? date('Y-m-01');
$date_to = $_GET['date_to'] ?? date('Y-m-d');

// Validate dates
if (strtotime($date_from) === false) {
    $date_from = date('Y-m-01');
}
if (strtotime($date_to) === false) {
    $date_to = date('Y-m-d');
}

// Build filters
$filters = array('date_from' => $date_from, 'date_to' => $date_to);

// Get report data
$report_data = $sales->getSalesReport($filters);
$summary = $sales->getSummaryStats($filters);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales Summary Report - Inventory System</title>
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

        input[type="date"] {
            padding: 10px;
            border: 2px solid #e0e0e0;
            border-radius: 6px;
            font-size: 13px;
            font-family: inherit;
        }

        input[type="date"]:focus {
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

        .stat-card.sales {
            border-left-color: #28a745;
        }

        .stat-card.cash {
            border-left-color: #17a2b8;
        }

        .stat-card.credit {
            border-left-color: #ffc107;
        }

        .stat-card.profit {
            border-left-color: #dc3545;
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

        .stat-card.profit .stat-value {
            color: #dc3545;
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

        th.right {
            text-align: right;
        }

        td {
            padding: 14px 12px;
            border-bottom: 1px solid #e9ecef;
            font-size: 13px;
        }

        td.right {
            text-align: right;
        }

        tbody tr:hover {
            background: #f8f9fa;
        }

        .amount {
            color: #28a745;
            font-weight: 600;
        }

        .amount.cash {
            color: #17a2b8;
        }

        .amount.credit {
            color: #ffc107;
        }

        .amount.profit {
            color: #dc3545;
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
            <h1>💰 Sales Summary Report</h1>
            <p>Daily sales overview with profit analysis</p>
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
                        <a href="sales-summary-report.php" class="btn btn-secondary">Reset</a>
                        <a href="sales-record.php" class="btn btn-add">+ Add Sale</a>
                    </div>
                </div>
            </form>
        </div>

        <!-- Summary Statistics -->
        <?php if ($summary): ?>
            <div class="stats-section">
                <div class="stat-card sales">
                    <div class="stat-label">Total Sales</div>
                    <div class="stat-value amount">₱<?php echo number_format($summary['total_sales'] ?? 0, 2); ?></div>
                </div>
                <div class="stat-card cash">
                    <div class="stat-label">Cash Sales</div>
                    <div class="stat-value amount cash">₱<?php echo number_format($summary['cash_sales'] ?? 0, 2); ?></div>
                </div>
                <div class="stat-card credit">
                    <div class="stat-label">Credit Sales</div>
                    <div class="stat-value amount credit">₱<?php echo number_format($summary['credit_sales'] ?? 0, 2); ?></div>
                </div>
                <div class="stat-card profit">
                    <div class="stat-label">Gross Profit</div>
                    <div class="stat-value amount profit">₱<?php echo number_format($summary['gross_profit'] ?? 0, 2); ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Total Cost</div>
                    <div class="stat-value">₱<?php echo number_format($summary['total_cost'] ?? 0, 2); ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Transactions</div>
                    <div class="stat-value"><?php echo intval($summary['total_transactions'] ?? 0); ?></div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Daily Sales Report Table -->
        <div class="table-container">
            <?php if (!empty($report_data)): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th class="right">Transactions</th>
                            <th class="right">Total Sales</th>
                            <th class="right">Cash Sales</th>
                            <th class="right">Credit Sales</th>
                            <th class="right">Total Cost</th>
                            <th class="right">Gross Profit</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($report_data as $row): ?>
                            <tr>
                                <td><?php echo date('M d, Y (D)', strtotime($row['date'])); ?></td>
                                <td class="right"><?php echo intval($row['total_transactions']); ?></td>
                                <td class="right">
                                    <span class="amount">₱<?php echo number_format($row['total_sales'], 2); ?></span>
                                </td>
                                <td class="right">
                                    <span class="amount cash">₱<?php echo number_format($row['cash_sales'] ?? 0, 2); ?></span>
                                </td>
                                <td class="right">
                                    <span class="amount credit">₱<?php echo number_format($row['credit_sales'] ?? 0, 2); ?></span>
                                </td>
                                <td class="right">₱<?php echo number_format($row['total_cost'], 2); ?></td>
                                <td class="right">
                                    <span class="amount profit">₱<?php echo number_format($row['gross_profit'], 2); ?></span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="empty-state">
                    <h3>No Sales Data Found</h3>
                    <p>There are no sales recorded for the selected date range.</p>
                    <p style="margin-top: 10px; font-size: 12px;">
                        <a href="sales-record.php" style="color: #667eea; text-decoration: none; font-weight: 600;">Record a new sale</a>
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
