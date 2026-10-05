<?php
// Inventory System - Record Purchase Page
session_start();
require_once 'config/database.php';
require_once 'config/auth_middleware.php';
require_once 'classes/Product.php';
require_once 'classes/Purchase.php';

// Require login
AuthMiddleware::requireLogin();

$user = $_SESSION['user_data'] ?? [];
$message = '';
$type = '';

$product = new Product($db);
$purchase = new Purchase($db);

// Get all products for dropdown
$all_products = $product->getAll(['is_active' => 1]);
$products_list = $all_products['data'] ?? [];

// Get suppliers from previous purchases
$suppliers = $purchase->getSuppliers();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !AuthMiddleware::validateCSRF($_POST['csrf_token'])) {
        $message = 'Security validation failed. Please try again.';
        $type = 'error';
    } else {
        $purchase->product_id = intval($_POST['product_id'] ?? 0);
        $purchase->supplier_name = trim($_POST['supplier_name'] ?? '');
        $purchase->purchase_date = trim($_POST['purchase_date'] ?? '');
        $purchase->invoice_number = trim($_POST['invoice_number'] ?? '');
        $purchase->quantity = intval($_POST['quantity'] ?? 0);
        $purchase->cost_per_unit = floatval($_POST['cost_per_unit'] ?? 0);
        $purchase->notes = trim($_POST['notes'] ?? '');

        // Validate inputs
        $errors = [];
        
        if ($purchase->product_id <= 0) {
            $errors[] = 'Please select a product';
        }
        if (empty($purchase->supplier_name)) {
            $errors[] = 'Supplier name is required';
        }
        if (empty($purchase->purchase_date)) {
            $errors[] = 'Purchase date is required';
        } else if (strtotime($purchase->purchase_date) === false) {
            $errors[] = 'Invalid purchase date';
        }
        if (empty($purchase->invoice_number)) {
            $errors[] = 'Invoice number is required';
        }
        if ($purchase->quantity <= 0) {
            $errors[] = 'Quantity must be greater than 0';
        }
        if ($purchase->cost_per_unit < 0) {
            $errors[] = 'Cost per unit cannot be negative';
        }

        if (!empty($errors)) {
            $message = implode('<br>', $errors);
            $type = 'error';
        } else {
            // Create purchase record
            $result = $purchase->create($user['user_id']);

            if ($result['success']) {
                $message = $result['message'] . ' (Invoice: ' . htmlspecialchars($purchase->invoice_number) . ')';
                $type = 'success';
                $_POST = []; // Clear form
                
                // Recalculate total for display
                $total = $purchase->quantity * $purchase->cost_per_unit;
                $message .= ' | Total Cost: ₱' . number_format($total, 2);
            } else {
                $message = $result['message'] ?? 'Failed to create purchase record';
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
    <title>Record Purchase - Inventory System</title>
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

        .calc-row:last-child {
            margin-bottom: 0;
            border-top: 2px solid #e0e0e0;
            padding-top: 10px;
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
            <h1>🛍️ Record Purchase</h1>
            <p>Add a new purchase order from a supplier</p>
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
                    <select id="product_id" name="product_id" required>
                        <option value="">-- Select a Product --</option>
                        <?php foreach ($products_list as $prod): ?>
                            <option value="<?php echo $prod['id']; ?>" 
                                    data-code="<?php echo htmlspecialchars($prod['product_code']); ?>"
                                    data-name="<?php echo htmlspecialchars($prod['product_name']); ?>"
                                    data-unit="<?php echo htmlspecialchars($prod['unit_of_measure'] ?? ''); ?>">
                                <?php echo htmlspecialchars($prod['product_code']); ?> - <?php echo htmlspecialchars($prod['product_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Supplier Information -->
                <div class="section-title">Supplier Information</div>

                <div class="form-group">
                    <label for="supplier_name">Supplier Name *</label>
                    <input type="text" id="supplier_name" name="supplier_name" required list="suppliers_list"
                           placeholder="Enter or select supplier name"
                           value="<?php echo htmlspecialchars($_POST['supplier_name'] ?? ''); ?>">
                    <datalist id="suppliers_list">
                        <?php foreach ($suppliers as $supplier): ?>
                            <option value="<?php echo htmlspecialchars($supplier); ?>">
                        <?php endforeach; ?>
                    </datalist>
                </div>

                <div class="form-group">
                    <label for="invoice_number">Invoice Number *</label>
                    <input type="text" id="invoice_number" name="invoice_number" required
                           placeholder="e.g., INV-2024-001"
                           value="<?php echo htmlspecialchars($_POST['invoice_number'] ?? ''); ?>">
                </div>

                <!-- Purchase Details -->
                <div class="section-title">Purchase Details</div>

                <div class="form-group">
                    <label for="purchase_date">Purchase Date *</label>
                    <input type="date" id="purchase_date" name="purchase_date" required
                           value="<?php echo htmlspecialchars($_POST['purchase_date'] ?? date('Y-m-d')); ?>">
                </div>

                <div class="form-group">
                    <label for="quantity">Quantity *</label>
                    <input type="number" id="quantity" name="quantity" required min="1" step="1"
                           placeholder="0"
                           value="<?php echo htmlspecialchars($_POST['quantity'] ?? ''); ?>"
                           onchange="calculateTotal()">
                </div>

                <div class="form-group">
                    <label for="cost_per_unit">Cost per Unit (₱) *</label>
                    <input type="number" id="cost_per_unit" name="cost_per_unit" required min="0" step="0.01"
                           placeholder="0.00"
                           value="<?php echo htmlspecialchars($_POST['cost_per_unit'] ?? ''); ?>"
                           onchange="calculateTotal()" 
                           oninput="calculateTotal()">
                </div>

                <!-- Total Cost Display -->
                <div class="calculation-box" id="totalBox">
                    <div class="calc-row">
                        <span>Qty:</span>
                        <span id="qtyDisplay">0</span>
                    </div>
                    <div class="calc-row">
                        <span>Cost per Unit:</span>
                        <span id="costDisplay">₱0.00</span>
                    </div>
                    <div class="calc-row">
                        <span>Total Cost:</span>
                        <span id="totalDisplay">₱0.00</span>
                    </div>
                </div>

                <!-- Additional Information -->
                <div class="section-title">Additional Information</div>

                <div class="form-group full">
                    <label for="notes">Notes</label>
                    <textarea id="notes" name="notes" 
                              placeholder="e.g., Payment terms, delivery details, quality notes..."><?php echo htmlspecialchars($_POST['notes'] ?? ''); ?></textarea>
                    <p class="hint-text">Optional: Add any additional details about this purchase</p>
                </div>
            </div>

            <!-- Buttons -->
            <div class="button-group">
                <button type="submit" class="btn btn-primary">Record Purchase</button>
                <a href="purchase-history.php" class="btn btn-secondary">View Purchase History</a>
            </div>
        </form>
    </div>

    <script>
        function calculateTotal() {
            const quantity = parseFloat(document.getElementById('quantity').value) || 0;
            const costPerUnit = parseFloat(document.getElementById('cost_per_unit').value) || 0;
            const total = quantity * costPerUnit;

            // Update display
            document.getElementById('qtyDisplay').textContent = quantity;
            document.getElementById('costDisplay').textContent = '₱' + costPerUnit.toFixed(2);
            document.getElementById('totalDisplay').textContent = '₱' + total.toFixed(2);

            // Show calculation box if inputs are valid
            if (quantity > 0 && costPerUnit >= 0) {
                document.getElementById('totalBox').classList.add('show');
            } else {
                document.getElementById('totalBox').classList.remove('show');
            }
        }

        // Calculate on page load if form was submitted with errors
        window.addEventListener('load', calculateTotal);

        // Calculate on input
        document.getElementById('quantity').addEventListener('input', calculateTotal);
        document.getElementById('cost_per_unit').addEventListener('input', calculateTotal);
    </script>
</body>
</html>
