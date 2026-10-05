<?php
// Inventory System - Edit Product Page
session_start();
require_once 'config/database.php';
require_once 'config/auth_middleware.php';
require_once 'classes/Product.php';

// Require login
AuthMiddleware::requireLogin();

$user = $_SESSION['user_data'] ?? [];
$message = '';
$type = '';
$product_data = null;

// Check if product ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error'] = 'Product ID not provided';
    header('Location: products.php');
    exit;
}

$product_id = intval($_GET['id']);
$product = new Product($db);

// Get existing product data
$product_data = $product->getById($product_id);
if (!$product_data) {
    $_SESSION['error'] = 'Product not found';
    header('Location: products.php');
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !AuthMiddleware::validateCSRF($_POST['csrf_token'])) {
        $message = 'Security validation failed. Please try again.';
        $type = 'error';
    } else {
        // Create new product instance and assign values
        $product->id = $product_id;
        $product->product_name = trim($_POST['product_name'] ?? '');
        $product->category = trim($_POST['category'] ?? '');
        $product->brand = trim($_POST['brand'] ?? '');
        $product->supplier = trim($_POST['supplier'] ?? '');
        $product->unit_of_measure = trim($_POST['unit_of_measure'] ?? '');
        $product->cost_price = floatval($_POST['cost_price'] ?? 0);
        $product->selling_price = floatval($_POST['selling_price'] ?? 0);
        $product->current_stock = intval($_POST['current_stock'] ?? 0);
        $product->reorder_level = intval($_POST['reorder_level'] ?? 0);
        $product->expiry_date = $_POST['expiry_date'] ?? null;
        $product->description = trim($_POST['description'] ?? '');
        $product->is_active = isset($_POST['is_active']) ? 1 : 0;

        // Validate inputs
        $errors = [];
        
        if (empty($product->product_name)) {
            $errors[] = 'Product name is required';
        }
        if (empty($product->category)) {
            $errors[] = 'Category is required';
        }
        if ($product->cost_price < 0) {
            $errors[] = 'Cost price cannot be negative';
        }
        if ($product->selling_price < 0) {
            $errors[] = 'Selling price cannot be negative';
        }
        if ($product->current_stock < 0) {
            $errors[] = 'Current stock cannot be negative';
        }
        if ($product->reorder_level < 0) {
            $errors[] = 'Reorder level cannot be negative';
        }

        if (!empty($errors)) {
            $message = implode('<br>', $errors);
            $type = 'error';
        } else {
            // Update product
            $result = $product->update($user['user_id']);

            if ($result['success']) {
                $_SESSION['success'] = 'Product updated successfully!';
                header('Location: products.php');
                exit;
            } else {
                $message = $result['message'] ?? 'Failed to update product';
                $type = 'error';
            }
        }
    }
}

// Get unique values for dropdowns
$categories = $product->getUniqueValues('category');
$brands = $product->getUniqueValues('brand');
$suppliers = $product->getUniqueValues('supplier');
$units = $product->getUniqueValues('unit_of_measure') ?? ['pcs', 'kg', 'ltr', 'box', 'pack'];

// Generate CSRF token
$csrf_token = AuthMiddleware::generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Product - Inventory System</title>
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
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #f0f0f0;
        }

        .page-header h1 {
            font-size: 28px;
            color: #333;
        }

        .product-code {
            background: #f0f0f0;
            padding: 8px 16px;
            border-radius: 6px;
            font-size: 14px;
            color: #666;
            font-family: monospace;
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
            min-height: 100px;
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

        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        input[type="checkbox"] {
            width: 18px;
            height: 18px;
            cursor: pointer;
        }

        .checkbox-label {
            margin: 0;
            font-weight: 500;
            color: #333;
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

        datalist {
            display: none;
        }

        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
                gap: 15px;
            }

            .container {
                padding: 25px;
            }

            .page-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
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
            <div>
                <h1>Edit Product</h1>
                <p style="color: #999; margin-top: 5px; font-size: 14px;">Update product information</p>
            </div>
            <?php if ($product_data): ?>
                <div class="product-code">
                    Code: <?php echo htmlspecialchars($product_data['product_code']); ?>
                </div>
            <?php endif; ?>
        </div>

        <?php if ($message): ?>
            <div class="message <?php echo $type; ?> show">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <?php if ($product_data): ?>
            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">

                <!-- Basic Information -->
                <div class="form-grid">
                    <div class="section-title">Basic Information</div>

                    <div class="form-group">
                        <label for="product_name">Product Name *</label>
                        <input type="text" id="product_name" name="product_name" required 
                               value="<?php echo htmlspecialchars($product_data['product_name']); ?>"
                               placeholder="Enter product name">
                    </div>

                    <div class="form-group">
                        <label for="category">Category *</label>
                        <input type="text" id="category" name="category" required list="categories"
                               value="<?php echo htmlspecialchars($product_data['category']); ?>"
                               placeholder="Select or enter category">
                        <datalist id="categories">
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo htmlspecialchars($cat); ?>">
                            <?php endforeach; ?>
                        </datalist>
                    </div>

                    <div class="form-group">
                        <label for="brand">Brand</label>
                        <input type="text" id="brand" name="brand" list="brands"
                               value="<?php echo htmlspecialchars($product_data['brand'] ?? ''); ?>"
                               placeholder="Select or enter brand">
                        <datalist id="brands">
                            <?php foreach ($brands as $brand): ?>
                                <option value="<?php echo htmlspecialchars($brand); ?>">
                            <?php endforeach; ?>
                        </datalist>
                    </div>

                    <!-- Supplier Information -->
                    <div class="section-title">Supplier Information</div>

                    <div class="form-group">
                        <label for="supplier">Supplier</label>
                        <input type="text" id="supplier" name="supplier" list="suppliers"
                               value="<?php echo htmlspecialchars($product_data['supplier'] ?? ''); ?>"
                               placeholder="Select or enter supplier">
                        <datalist id="suppliers">
                            <?php foreach ($suppliers as $sup): ?>
                                <option value="<?php echo htmlspecialchars($sup); ?>">
                            <?php endforeach; ?>
                        </datalist>
                    </div>

                    <div class="form-group">
                        <label for="unit_of_measure">Unit of Measure</label>
                        <input type="text" id="unit_of_measure" name="unit_of_measure" list="units"
                               value="<?php echo htmlspecialchars($product_data['unit_of_measure'] ?? ''); ?>"
                               placeholder="e.g., pcs, kg, ltr">
                        <datalist id="units">
                            <?php foreach ($units as $unit): ?>
                                <option value="<?php echo htmlspecialchars($unit); ?>">
                            <?php endforeach; ?>
                        </datalist>
                    </div>

                    <!-- Pricing Information -->
                    <div class="section-title">Pricing Information</div>

                    <div class="form-group">
                        <label for="cost_price">Cost Price (<?php echo htmlspecialchars($_SESSION['currency'] ?? 'USD'); ?>) *</label>
                        <input type="number" id="cost_price" name="cost_price" required step="0.01" min="0"
                               value="<?php echo htmlspecialchars($product_data['cost_price']); ?>"
                               placeholder="0.00">
                    </div>

                    <div class="form-group">
                        <label for="selling_price">Selling Price (<?php echo htmlspecialchars($_SESSION['currency'] ?? 'USD'); ?>) *</label>
                        <input type="number" id="selling_price" name="selling_price" required step="0.01" min="0"
                               value="<?php echo htmlspecialchars($product_data['selling_price']); ?>"
                               placeholder="0.00">
                    </div>

                    <!-- Stock Information -->
                    <div class="section-title">Stock Information</div>

                    <div class="form-group">
                        <label for="current_stock">Current Stock *</label>
                        <input type="number" id="current_stock" name="current_stock" required min="0"
                               value="<?php echo htmlspecialchars($product_data['current_stock']); ?>"
                               placeholder="0">
                    </div>

                    <div class="form-group">
                        <label for="reorder_level">Reorder Level *</label>
                        <input type="number" id="reorder_level" name="reorder_level" required min="0"
                               value="<?php echo htmlspecialchars($product_data['reorder_level']); ?>"
                               placeholder="0">
                        <p class="hint-text">Alert when stock falls below this level</p>
                    </div>

                    <div class="form-group">
                        <label for="expiry_date">Expiry Date</label>
                        <input type="date" id="expiry_date" name="expiry_date"
                               value="<?php echo $product_data['expiry_date'] ? htmlspecialchars($product_data['expiry_date']) : ''; ?>">
                    </div>

                    <!-- Additional Information -->
                    <div class="section-title">Additional Information</div>

                    <div class="form-group full">
                        <label for="description">Description</label>
                        <textarea id="description" name="description" 
                                  placeholder="Enter product description"><?php echo htmlspecialchars($product_data['description'] ?? ''); ?></textarea>
                    </div>

                    <div class="form-group">
                        <div class="checkbox-group">
                            <input type="checkbox" id="is_active" name="is_active" value="1"
                                   <?php echo $product_data['is_active'] ? 'checked' : ''; ?>>
                            <label for="is_active" class="checkbox-label">Active Product</label>
                        </div>
                    </div>
                </div>

                <!-- Buttons -->
                <div class="button-group">
                    <button type="submit" class="btn btn-primary">Update Product</button>
                    <a href="products.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>
