<?php
// Inventory System - Stock Adjustment/Movement Page
session_start();
require_once 'config/database.php';
require_once 'config/auth_middleware.php';
require_once 'classes/Product.php';
require_once 'classes/StockMovement.php';

// Require login
AuthMiddleware::requireLogin();

$user = $_SESSION['user_data'] ?? [];
$message = '';
$type = '';

$product = new Product($db);
$stock_movement = new StockMovement($db);

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
        $product_id = intval($_POST['product_id'] ?? 0);
        $movement_type = trim($_POST['movement_type'] ?? '');
        $quantity = intval($_POST['quantity'] ?? 0);
        $reference = trim($_POST['reference'] ?? '');
        $reason = trim($_POST['reason'] ?? '');
        $notes = trim($_POST['notes'] ?? '');

        // Validate inputs
        $errors = [];
        
        if ($product_id <= 0) {
            $errors[] = 'Please select a product';
        }
        if (empty($movement_type) || !in_array($movement_type, $stock_movement->getMovementTypes())) {
            $errors[] = 'Invalid movement type';
        }
        if ($quantity <= 0) {
            $errors[] = 'Quantity must be greater than 0';
        }
        if (empty($reason)) {
            $errors[] = 'Reason is required';
        }

        if (!empty($errors)) {
            $message = implode('<br>', $errors);
            $type = 'error';
        } else {
            // Log the movement
            $result = $stock_movement->logMovement(
                $product_id,
                $movement_type,
                $quantity,
                $reference,
                $reason,
                $user['user_id'],
                $notes
            );

            if ($result['success']) {
                $message = $result['message'];
                $type = 'success';
                $_POST = []; // Clear form
            } else {
                $message = $result['message'] ?? 'Failed to log stock movement';
                $type = 'error';
            }
        }
    }
}

// Generate CSRF token
$csrf_token = AuthMiddleware::generateCSRFToken();
$movement_types = $stock_movement->getMovementTypes();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stock Adjustment - Inventory System</title>
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
            max-width: 800px;
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

        .movement-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
            margin-left: 10px;
        }

        .badge-in {
            background: #d4edda;
            color: #155724;
        }

        .badge-out {
            background: #f8d7da;
            color: #721c24;
        }

        .badge-adjustment {
            background: #e2e3e5;
            color: #383d41;
        }

        .badge-return {
            background: #cfe2ff;
            color: #084298;
        }

        .badge-damage {
            background: #f8d7da;
            color: #842029;
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

        .hint-text {
            font-size: 12px;
            color: #999;
            margin-top: 5px;
        }

        .info-card {
            background: #f8f9fa;
            border-left: 4px solid #667eea;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 13px;
            color: #666;
        }

        .info-card strong {
            color: #333;
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
            <h1>📦 Stock Adjustment</h1>
            <p>Record stock movements - incoming, outgoing, returns, or adjustments</p>
        </div>

        <div class="info-card">
            <strong>Guide:</strong> Use this form to log all stock movements. Select the movement type that matches your transaction:
            <ul style="margin-left: 20px; margin-top: 8px; line-height: 1.6;">
                <li><strong>IN:</strong> Incoming stock (purchases, receipts)</li>
                <li><strong>OUT:</strong> Outgoing stock (sales, shipments)</li>
                <li><strong>ADJUSTMENT:</strong> Stock corrections or counts</li>
                <li><strong>RETURN:</strong> Returned goods from customers</li>
                <li><strong>DAMAGE:</strong> Damaged or expired stock</li>
            </ul>
        </div>

        <?php if ($message): ?>
            <div class="message <?php echo $type; ?> show">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">

            <div class="form-grid">
                <!-- Product Selection -->
                <div class="section-title">Product Details</div>

                <div class="form-group full">
                    <label for="product_id">Select Product *</label>
                    <select id="product_id" name="product_id" required>
                        <option value="">-- Select a Product --</option>
                        <?php foreach ($products_list as $prod): ?>
                            <option value="<?php echo $prod['id']; ?>" 
                                    data-code="<?php echo htmlspecialchars($prod['product_code']); ?>"
                                    data-stock="<?php echo intval($prod['current_stock']); ?>">
                                <?php echo htmlspecialchars($prod['product_code']); ?> - <?php echo htmlspecialchars($prod['product_name']); ?>
                                (Stock: <?php echo intval($prod['current_stock']); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Movement Details -->
                <div class="section-title">Movement Details</div>

                <div class="form-group">
                    <label for="movement_type">Movement Type *</label>
                    <select id="movement_type" name="movement_type" required onchange="updateMovementBadge()">
                        <option value="">-- Select Type --</option>
                        <?php foreach ($movement_types as $type): ?>
                            <option value="<?php echo htmlspecialchars($type); ?>">
                                <?php echo htmlspecialchars($type); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <span id="badge-display"></span>
                </div>

                <div class="form-group">
                    <label for="quantity">Quantity *</label>
                    <input type="number" id="quantity" name="quantity" required min="1" 
                           placeholder="Enter quantity" value="<?php echo htmlspecialchars($_POST['quantity'] ?? ''); ?>">
                    <p class="hint-text">Must be greater than 0</p>
                </div>

                <!-- Reference Information -->
                <div class="section-title">Reference Information</div>

                <div class="form-group">
                    <label for="reference">Reference (PO/Invoice/etc.)</label>
                    <input type="text" id="reference" name="reference" 
                           placeholder="e.g., PO-2024-001 or INV-123456"
                           value="<?php echo htmlspecialchars($_POST['reference'] ?? ''); ?>">
                    <p class="hint-text">Optional: Document number or reference</p>
                </div>

                <div class="form-group">
                    <label for="reason">Reason *</label>
                    <input type="text" id="reason" name="reason" required
                           placeholder="e.g., Purchase from supplier, Customer order"
                           value="<?php echo htmlspecialchars($_POST['reason'] ?? ''); ?>">
                </div>

                <!-- Additional Notes -->
                <div class="form-group full">
                    <label for="notes">Additional Notes</label>
                    <textarea id="notes" name="notes" 
                              placeholder="Any additional details about this movement..."><?php echo htmlspecialchars($_POST['notes'] ?? ''); ?></textarea>
                </div>
            </div>

            <!-- Buttons -->
            <div class="button-group">
                <button type="submit" class="btn btn-primary">Record Movement</button>
                <a href="stock-movement-report.php" class="btn btn-secondary">View Report</a>
            </div>
        </form>
    </div>

    <script>
        function updateMovementBadge() {
            const type = document.getElementById('movement_type').value;
            const badgeDisplay = document.getElementById('badge-display');
            
            const badgeMap = {
                'IN': { class: 'badge-in', text: '→ Stock In' },
                'OUT': { class: 'badge-out', text: '← Stock Out' },
                'ADJUSTMENT': { class: 'badge-adjustment', text: '⚙ Adjustment' },
                'RETURN': { class: 'badge-return', text: '↩ Return' },
                'DAMAGE': { class: 'badge-damage', text: '✕ Damage' }
            };
            
            if (badgeMap[type]) {
                badgeDisplay.className = 'movement-badge ' + badgeMap[type].class;
                badgeDisplay.textContent = badgeMap[type].text;
            } else {
                badgeDisplay.textContent = '';
            }
        }

        // Update badge on page load if type is selected
        window.addEventListener('load', updateMovementBadge);
    </script>
</body>
</html>
