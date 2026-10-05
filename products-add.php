<?php
/**
 * Add New Product Page
 * Form to add new products to inventory
 */

require_once 'config/database.php';
require_once 'config/session.php';
require_once 'config/auth_middleware.php';
require_once 'classes/Product.php';

// Require login
AuthMiddleware::requireLogin();

// Set page variables for layout
$page_title = 'Add Product';
$active_menu = 'products';

// Get user data
$user = SessionManager::getUserData() ?? ['user_id' => 1, 'username' => 'system'];

// Initialize classes
$product = new Product($conn);

// Get unique values for dropdowns
$categories = $product->getUniqueValues('category');
$brands = $product->getUniqueValues('brand');
$suppliers = $product->getUniqueValues('supplier');
$units = $product->getUniqueValues('unit_of_measure');

$message = '';
$message_type = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Set product properties
    $product->product_name = trim($_POST['product_name'] ?? '');
    $product->category = trim($_POST['category'] ?? '');
    $product->brand = trim($_POST['brand'] ?? '');
    $product->supplier = trim($_POST['supplier'] ?? '');
    $product->unit_of_measure = trim($_POST['unit_of_measure'] ?? '');
    $product->cost_price = (float)($_POST['cost_price'] ?? 0);
    $product->selling_price = (float)($_POST['selling_price'] ?? 0);
    $product->current_stock = (int)($_POST['current_stock'] ?? 0);
    $product->reorder_level = (int)($_POST['reorder_level'] ?? 10);
    $product->expiry_date = !empty($_POST['expiry_date']) ? $_POST['expiry_date'] : null;
    $product->description = trim($_POST['description'] ?? '');
    $product->is_active = isset($_POST['is_active']) ? 1 : 0;

    // Create product
    $result = $product->create($user['user_id'] ?? 1);

    if ($result['success']) {
        $message = 'Product added successfully! Product Code: ' . $product->product_code;
        $message_type = 'success';
        // Clear form on success
        $_POST = [];
        // Reset product object
        $product = new Product($conn);
    } else {
        $message = $result['message'];
        $message_type = 'error';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Product - Inventory Master</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f5f5;
        }

        .navbar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
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
            max-width: 1000px;
            margin: 30px auto;
            padding: 0 20px;
        }

        .form-section {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .form-header {
            margin-bottom: 30px;
        }

        .form-header h1 {
            color: #333;
            font-size: 28px;
            margin-bottom: 5px;
        }

        .form-header p {
            color: #666;
            font-size: 14px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group label {
            color: #333;
            font-weight: 600;
            margin-bottom: 8px;
            font-size: 14px;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
            font-family: inherit;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .form-group textarea {
            resize: vertical;
            min-height: 80px;
        }

        .form-section-title {
            color: #333;
            font-size: 16px;
            font-weight: 600;
            margin-top: 30px;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #667eea;
        }

        .form-actions {
            display: flex;
            gap: 15px;
            margin-top: 30px;
        }

        .btn {
            padding: 12px 30px;
            border: none;
            border-radius: 5px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
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

        .alert {
            padding: 12px 20px;
            margin-bottom: 20px;
            border-radius: 5px;
            font-size: 14px;
        }

        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .required {
            color: #ff6b6b;
        }

        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
            }

            .form-actions {
                flex-direction: column;
            }

            .btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>
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
        <div class="form-section">
            <div class="form-header">
                <h1>➕ Add New Product</h1>
                <p>Enter product details to add it to your inventory</p>
            </div>

            <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?>">
                <?php echo htmlspecialchars($message); ?>
                <?php if ($message_type === 'success'): ?>
                <br><a href="products.php" style="color: inherit; text-decoration: underline;">View all products →</a>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <form method="POST" action="">
                <!-- Basic Information -->
                <h3 class="form-section-title">📋 Basic Information</h3>
                <div class="form-grid">
                    <div class="form-group">
                        <label>Product Name <span class="required">*</span></label>
                        <input type="text" name="product_name" placeholder="Enter product name" 
                               value="<?php echo htmlspecialchars($_POST['product_name'] ?? ''); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Category</label>
                        <input type="text" name="category" placeholder="e.g., Electronics, Apparel" 
                               value="<?php echo htmlspecialchars($_POST['category'] ?? ''); ?>" list="categories">
                        <datalist id="categories">
                            <?php foreach ($categories['data'] ?? [] as $cat): ?>
                            <option value="<?php echo htmlspecialchars($cat); ?>">
                            <?php endforeach; ?>
                        </datalist>
                    </div>
                    <div class="form-group">
                        <label>Brand</label>
                        <input type="text" name="brand" placeholder="e.g., Samsung, Nike" 
                               value="<?php echo htmlspecialchars($_POST['brand'] ?? ''); ?>" list="brands">
                        <datalist id="brands">
                            <?php foreach ($brands['data'] ?? [] as $brnd): ?>
                            <option value="<?php echo htmlspecialchars($brnd); ?>">
                            <?php endforeach; ?>
                        </datalist>
                    </div>
                </div>

                <!-- Supplier Information -->
                <h3 class="form-section-title">🏢 Supplier Information</h3>
                <div class="form-grid">
                    <div class="form-group">
                        <label>Supplier</label>
                        <input type="text" name="supplier" placeholder="Supplier name" 
                               value="<?php echo htmlspecialchars($_POST['supplier'] ?? ''); ?>" list="suppliers">
                        <datalist id="suppliers">
                            <?php foreach ($suppliers['data'] ?? [] as $supp): ?>
                            <option value="<?php echo htmlspecialchars($supp); ?>">
                            <?php endforeach; ?>
                        </datalist>
                    </div>
                    <div class="form-group">
                        <label>Unit of Measure</label>
                        <input type="text" name="unit_of_measure" placeholder="e.g., pcs, kg, liters" 
                               value="<?php echo htmlspecialchars($_POST['unit_of_measure'] ?? ''); ?>" list="units">
                        <datalist id="units">
                            <?php foreach ($units['data'] ?? [] as $unit): ?>
                            <option value="<?php echo htmlspecialchars($unit); ?>">
                            <?php endforeach; ?>
                        </datalist>
                    </div>
                </div>

                <!-- Pricing Information -->
                <h3 class="form-section-title">💰 Pricing Information</h3>
                <div class="form-grid">
                    <div class="form-group">
                        <label>Cost Price (₱) <span class="required">*</span></label>
                        <input type="number" name="cost_price" placeholder="0.00" step="0.01" min="0"
                               value="<?php echo htmlspecialchars($_POST['cost_price'] ?? ''); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Selling Price (₱) <span class="required">*</span></label>
                        <input type="number" name="selling_price" placeholder="0.00" step="0.01" min="0"
                               value="<?php echo htmlspecialchars($_POST['selling_price'] ?? ''); ?>" required>
                    </div>
                </div>

                <!-- Stock Information -->
                <h3 class="form-section-title">📦 Stock Information</h3>
                <div class="form-grid">
                    <div class="form-group">
                        <label>Current Stock <span class="required">*</span></label>
                        <input type="number" name="current_stock" placeholder="0" min="0"
                               value="<?php echo htmlspecialchars($_POST['current_stock'] ?? '0'); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Reorder Level</label>
                        <input type="number" name="reorder_level" placeholder="10" min="0"
                               value="<?php echo htmlspecialchars($_POST['reorder_level'] ?? '10'); ?>">
                    </div>
                    <div class="form-group">
                        <label>Expiry Date</label>
                        <input type="date" name="expiry_date"
                               value="<?php echo htmlspecialchars($_POST['expiry_date'] ?? ''); ?>">
                    </div>
                </div>

                <!-- Additional Information -->
                <h3 class="form-section-title">📝 Additional Information</h3>
                <div class="form-grid">
                    <div class="form-group" style="grid-column: 1 / -1;">
                        <label>Description</label>
                        <textarea name="description" placeholder="Enter product description..."><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                    </div>
                    <div class="form-group">
                        <label style="display: flex; align-items: center; margin-bottom: 0; padding-top: 12px;">
                            <input type="checkbox" name="is_active" value="1" 
                                   <?php echo (!isset($_POST['is_active']) || $_POST['is_active']) ? 'checked' : ''; ?>
                                   style="margin-right: 8px;">
                            <span>Active (enabled)</span>
                        </label>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">✓ Add Product</button>
                    <a href="products.php" class="btn btn-secondary">← Back to Products</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
