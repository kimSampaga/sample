<?php
// Inventory System - Record Sale Page
session_start();
require_once 'config/database.php';
require_once 'config/auth_middleware.php';
require_once 'classes/Product.php';
require_once 'classes/Sales.php';

// Require login
AuthMiddleware::requireLogin();

$user = $_SESSION['user_data'] ?? [];
$message = '';
$type = '';

$product = new Product($db);
$sales = new Sales($db);

// Get all products for dropdown
$all_products = $product->getAll(['is_active' => 1]);
$products_list = $all_products['data'] ?? [];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !AuthMiddleware::validateCSRF($_POST['csrf_token'])) {
        $message = 'Security validation failed. Please try again.';
        $type = 'error';
    } else {
        $sales->product_id = intval($_POST['product_id'] ?? 0);
        $sales->sale_date = trim($_POST['sale_date'] ?? '');
        $sales->quantity_sold = intval($_POST['quantity_sold'] ?? 0);
        $sales->unit_price = floatval($_POST['unit_price'] ?? 0);
        $sales->cost_price = floatval($_POST['cost_price'] ?? 0);
        $sales->payment_type = trim($_POST['payment_type'] ?? 'CASH');
        $sales->customer_name = trim($_POST['customer_name'] ?? '');
        $sales->reference = trim($_POST['reference'] ?? '');
        $sales->notes = trim($_POST['notes'] ?? '');

        // Validate inputs
        $errors = [];
        
        if ($sales->product_id <= 0) {
            $errors[] = 'Please select a product';
        }
        if (empty($sales->sale_date)) {
            $errors[] = 'Sale date is required';
        } else if (strtotime($sales->sale_date) === false) {
            $errors[] = 'Invalid sale date';
        }
        if ($sales->quantity_sold <= 0) {
            $errors[] = 'Quantity must be greater than 0';
        }
        if ($sales->unit_price < 0) {
            $errors[] = 'Unit price cannot be negative';
        }
        if ($sales->cost_price < 0) {
            $errors[] = 'Cost price cannot be negative';
        }
        if (!in_array($sales->payment_type, array('CASH', 'CREDIT'))) {
            $errors[] = 'Invalid payment type';
        }

        if (!empty($errors)) {
            $message = implode('<br>', $errors);
            $type = 'error';
        } else {
            // Create sales record
            $result = $sales->create($user['user_id']);

            if ($result['success']) {
                $total = $sales->quantity_sold * $sales->unit_price;
                $profit = $total - ($sales->cost_price * $sales->quantity_sold);
                $message = $result['message'] . ' | Amount: ₱' . number_format($total, 2) . ' | Profit: ₱' . number_format($profit, 2);
                $type = 'success';
                $_POST = []; // Clear form
            } else {
                $message = $result['message'] ?? 'Failed to record sale';
                $type = 'error';
            }
        }
    }
}

// Generate CSRF token
$csrf_token = AuthMiddleware::generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Record Sale - Inventory System</title>
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
            padding: 40px 20px;
        }

        .container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            padding: 40px;
        }

        .page-header {
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #f0f0f0;
        }

        .page-header h1 {
            font-size: 28px;
            color: #333;
            margin-bottom: 5px;
        }

        .page-header p {
            color: #999;
            font-size: 14px;
        }

        .message {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: none;
        }

        .message.show {
            display: block;
        }

        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group.full {
            grid-column: 1 / -1;
        }

        label {
            font-weight: 600;
            margin-bottom: 8px;
            color: #333;
            font-size: 14px;
        }

        input[type="text"],
        input[type="number"],
        input[type="date"],
        select,
        textarea {
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 6px;
            font-size: 14px;
            font-family: inherit;
            transition: border-color 0.3s;
        }

        input[type="text"]:focus,
        input[type="number"]:focus,
        input[type="date"]:focus,
        select:focus,
        textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        textarea {
            resize: vertical;
            min-height: 80px;
        }

        .section-title {
            grid-column: 1 / -1;
            font-size: 16px;
            font-weight: 700;
            color: #667eea;
            margin-top: 20px;
            margin-bottom: 15px;
            padding-top: 15px;
            border-top: 2px solid #f0f0f0;
        }

        .calculation-box {
            background: #f8f9fa;
            border-left: 4px solid #28a745;
            padding: 15px;
            border-radius: 6px;
            margin: 15px 0;
            display: none;
        }

        .calculation-box.show {
            display: block;
        }

        .calc-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            font-size: 14px;
        }

        .calc-row:nth-child(3),
        .calc-row:nth-child(4) {
            border-top: 2px solid #e0e0e0;
            padding-top: 10px;
            margin-top: 10px;
        }

        .calc-row.profit {
            font-weight: 700;
            font-size: 16px;
            color: #28a745;
        }

        .hint-text {
            font-size: 12px;
            color: #999;
            margin-top: 5px;
        }

        .button-group {
            display: flex;
            gap: 15px;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 2px solid #f0f0f0;
        }

        button, .btn {
            padding: 12px 30px;
            border: none;
            border-radius: 6px;
            font-size: 14px;
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
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3);
            transform: translateY(-2px);
        }

        .btn-secondary {
            background: #f0f0f0;
            color: #333;
        }

        .btn-secondary:hover {
            background: #e0e0e0;
        }

        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
                gap: 15px;
            }

            .container {
                padding: 25px;
            }

            .button-group {
                flex-direction: column;
            }

            button, .btn {
                width: 100%;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="page-header">
            <h1>💰 Record Sale</h1>
            <p>Add a new sales transaction</p>
        </div>

        <?php if ($message): ?>
            <div class="message <?php echo $type; ?> show">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">

            <div class="form-grid">
                <!-- Product Information -->
                <div class="section-title">Product Information</div>

                <div class="form-group full">
                    <label for="product_id">Product *</label>
                    <select id="product_id" name="product_id" required onchange="updateProductDetails()">
                        <option value="">-- Select a Product --</option>
                        <?php foreach ($products_list as $prod): ?>
                            <option value="<?php echo $prod['id']; ?>" 
                                    data-code="<?php echo htmlspecialchars($prod['product_code']); ?>"
                                    data-name="<?php echo htmlspecialchars($prod['product_name']); ?>"
                                    data-cost="<?php echo floatval($prod['cost_price']); ?>"
                                    data-unit="<?php echo htmlspecialchars($prod['unit_of_measure'] ?? ''); ?>">
                                <?php echo htmlspecialchars($prod['product_code']); ?> - <?php echo htmlspecialchars($prod['product_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Sale Details -->
                <div class="section-title">Sale Details</div>

                <div class="form-group">
                    <label for="sale_date">Sale Date *</label>
                    <input type="date" id="sale_date" name="sale_date" required
                           value="<?php echo htmlspecialchars($_POST['sale_date'] ?? date('Y-m-d')); ?>">
                </div>

                <div class="form-group">
                    <label for="payment_type">Payment Type *</label>
                    <select id="payment_type" name="payment_type" required>
                        <option value="CASH" <?php echo (($_POST['payment_type'] ?? 'CASH') === 'CASH') ? 'selected' : ''; ?>>CASH</option>
                        <option value="CREDIT" <?php echo (($_POST['payment_type'] ?? '') === 'CREDIT') ? 'selected' : ''; ?>>CREDIT</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="quantity_sold">Quantity Sold *</label>
                    <input type="number" id="quantity_sold" name="quantity_sold" required min="1" step="1"
                           placeholder="0"
                           value="<?php echo htmlspecialchars($_POST['quantity_sold'] ?? ''); ?>"
                           onchange="calculateProfit()" 
                           oninput="calculateProfit()">
                </div>

                <div class="form-group">
                    <label for="cost_price">Cost Price per Unit (₱) *</label>
                    <input type="number" id="cost_price" name="cost_price" required min="0" step="0.01"
                           placeholder="0.00"
                           value="<?php echo htmlspecialchars($_POST['cost_price'] ?? ''); ?>"
                           onchange="calculateProfit()" 
                           oninput="calculateProfit()">
                    <p class="hint-text">Auto-filled from product cost</p>
                </div>

                <div class="form-group">
                    <label for="unit_price">Selling Price per Unit (₱) *</label>
                    <input type="number" id="unit_price" name="unit_price" required min="0" step="0.01"
                           placeholder="0.00"
                           value="<?php echo htmlspecialchars($_POST['unit_price'] ?? ''); ?>"
                           onchange="calculateProfit()" 
                           oninput="calculateProfit()">
                </div>

                <!-- Profit Calculation Display -->
                <div class="calculation-box full" id="profitBox">
                    <div class="calc-row">
                        <span>Quantity:</span>
                        <span id="qtyDisplay">0</span>
                    </div>
                    <div class="calc-row">
                        <span>Selling Price:</span>
                        <span id="sellingDisplay">₱0.00</span>
                    </div>
                    <div class="calc-row">
                        <span>Total Sales (Qty × Selling Price):</span>
                        <span id="totalSalesDisplay" style="color: #28a745; font-weight: 600;">₱0.00</span>
                    </div>
                    <div class="calc-row">
                        <span>Cost Price:</span>
                        <span id="costDisplay">₱0.00</span>
                    </div>
                    <div class="calc-row">
                        <span>Total Cost (Qty × Cost Price):</span>
                        <span id="totalCostDisplay">₱0.00</span>
                    </div>
                    <div class="calc-row profit">
                        <span>Gross Profit (Sales - Cost):</span>
                        <span id="profitDisplay">₱0.00</span>
                    </div>
                </div>

                <!-- Customer Information -->
                <div class="section-title">Customer Information</div>

                <div class="form-group">
                    <label for="customer_name">Customer Name</label>
                    <input type="text" id="customer_name" name="customer_name"
                           placeholder="e.g., John Doe"
                           value="<?php echo htmlspecialchars($_POST['customer_name'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label for="reference">Reference (Invoice/Order)</label>
                    <input type="text" id="reference" name="reference"
                           placeholder="e.g., INV-2024-001"
                           value="<?php echo htmlspecialchars($_POST['reference'] ?? ''); ?>">
                </div>

                <!-- Additional Information -->
                <div class="section-title">Additional Information</div>

                <div class="form-group full">
                    <label for="notes">Notes</label>
                    <textarea id="notes" name="notes" 
                              placeholder="Any additional details about this sale..."><?php echo htmlspecialchars($_POST['notes'] ?? ''); ?></textarea>
                </div>
            </div>

            <!-- Buttons -->
            <div class="button-group">
                <button type="submit" class="btn btn-primary">Record Sale</button>
                <a href="sales-summary-report.php" class="btn btn-secondary">View Sales Summary</a>
            </div>
        </form>
    </div>

    <script>
        function updateProductDetails() {
            const select = document.getElementById('product_id');
            const option = select.options[select.selectedIndex];
            
            if (option.value) {
                const cost = parseFloat(option.dataset.cost) || 0;
                document.getElementById('cost_price').value = cost.toFixed(2);
                calculateProfit();
            }
        }

        function calculateProfit() {
            const quantity = parseFloat(document.getElementById('quantity_sold').value) || 0;
            const sellingPrice = parseFloat(document.getElementById('unit_price').value) || 0;
            const costPrice = parseFloat(document.getElementById('cost_price').value) || 0;

            const totalSales = quantity * sellingPrice;
            const totalCost = quantity * costPrice;
            const profit = totalSales - totalCost;

            // Update display
            document.getElementById('qtyDisplay').textContent = quantity;
            document.getElementById('sellingDisplay').textContent = '₱' + sellingPrice.toFixed(2);
            document.getElementById('totalSalesDisplay').textContent = '₱' + totalSales.toFixed(2);
            document.getElementById('costDisplay').textContent = '₱' + costPrice.toFixed(2);
            document.getElementById('totalCostDisplay').textContent = '₱' + totalCost.toFixed(2);
            document.getElementById('profitDisplay').textContent = '₱' + profit.toFixed(2);

            // Show calculation box if inputs are valid
            if (quantity > 0 && sellingPrice >= 0 && costPrice >= 0) {
                document.getElementById('profitBox').classList.add('show');
                // Change profit color based on profitability
                if (profit < 0) {
                    document.querySelector('.calc-row.profit').style.color = '#dc3545';
                } else {
                    document.querySelector('.calc-row.profit').style.color = '#28a745';
                }
            } else {
                document.getElementById('profitBox').classList.remove('show');
            }
        }

        // Calculate on page load if form was submitted with errors
        window.addEventListener('load', () => {
            updateProductDetails();
            calculateProfit();
        });

        // Calculate on input
        document.getElementById('quantity_sold').addEventListener('input', calculateProfit);
        document.getElementById('unit_price').addEventListener('input', calculateProfit);
        document.getElementById('cost_price').addEventListener('input', calculateProfit);
    </script>
</body>
</html>
