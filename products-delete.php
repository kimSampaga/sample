<?php
// Inventory System - Delete Product Handler
session_start();
require_once 'config/database.php';
require_once 'config/auth_middleware.php';
require_once 'classes/Product.php';

// Require login
AuthMiddleware::requireLogin();

$user = $_SESSION['user_data'] ?? [];
$message = '';
$product_data = null;

// Check if product ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error'] = 'Product ID not provided';
    header('Location: products.php');
    exit;
}

$product_id = intval($_GET['id']);
$product = new Product($db);

// Get product data for display
$product_data = $product->getById($product_id);
if (!$product_data) {
    $_SESSION['error'] = 'Product not found';
    header('Location: products.php');
    exit;
}

// Handle deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !AuthMiddleware::validateCSRF($_POST['csrf_token'])) {
        $message = 'Security validation failed. Please try again.';
    } else if (isset($_POST['confirm_delete'])) {
        // Delete product
        $result = $product->delete($product_id, $user['user_id']);

        if ($result['success']) {
            $_SESSION['success'] = 'Product deleted successfully!';
            header('Location: products.php');
            exit;
        } else {
            $message = $result['message'] ?? 'Failed to delete product';
        }
    } else {
        // Deletion cancelled
        header('Location: products.php');
        exit;
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
    <title>Delete Product - Inventory System</title>
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
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            padding: 40px;
            max-width: 500px;
            width: 100%;
        }

        .confirmation-icon {
            text-align: center;
            margin-bottom: 30px;
        }

        .icon {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: #fff3cd;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 40px;
        }

        h1 {
            text-align: center;
            color: #333;
            margin-bottom: 10px;
            font-size: 24px;
        }

        .confirmation-text {
            text-align: center;
            color: #666;
            margin-bottom: 25px;
            line-height: 1.6;
        }

        .product-info {
            background: #f8f9fa;
            border-left: 4px solid #667eea;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 25px;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            font-size: 14px;
        }

        .info-row:last-child {
            margin-bottom: 0;
        }

        .info-label {
            font-weight: 600;
            color: #333;
        }

        .info-value {
            color: #666;
            text-align: right;
            word-break: break-word;
        }

        .message {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .button-group {
            display: flex;
            gap: 12px;
        }

        form {
            margin: 0;
        }

        button, a.btn {
            flex: 1;
            padding: 12px 20px;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s;
        }

        .btn-delete {
            background: #dc3545;
            color: white;
        }

        .btn-delete:hover {
            background: #c82333;
            box-shadow: 0 5px 15px rgba(220, 53, 69, 0.3);
        }

        .btn-cancel {
            background: #f0f0f0;
            color: #333;
        }

        .btn-cancel:hover {
            background: #e0e0e0;
        }

        .warning-text {
            text-align: center;
            font-size: 12px;
            color: #999;
            margin-top: 20px;
        }

        @media (max-width: 480px) {
            .container {
                padding: 25px;
            }

            .button-group {
                flex-direction: column;
                gap: 10px;
            }

            h1 {
                font-size: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="confirmation-icon">
            <div class="icon">⚠️</div>
            <h1>Delete Product?</h1>
            <p class="confirmation-text">This action cannot be undone. Are you sure you want to delete this product?</p>
        </div>

        <?php if ($message): ?>
            <div class="message">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <?php if ($product_data): ?>
            <div class="product-info">
                <div class="info-row">
                    <span class="info-label">Code:</span>
                    <span class="info-value" style="font-family: monospace;">
                        <?php echo htmlspecialchars($product_data['product_code']); ?>
                    </span>
                </div>
                <div class="info-row">
                    <span class="info-label">Product:</span>
                    <span class="info-value">
                        <?php echo htmlspecialchars($product_data['product_name']); ?>
                    </span>
                </div>
                <div class="info-row">
                    <span class="info-label">Category:</span>
                    <span class="info-value">
                        <?php echo htmlspecialchars($product_data['category']); ?>
                    </span>
                </div>
                <div class="info-row">
                    <span class="info-label">Current Stock:</span>
                    <span class="info-value">
                        <?php echo intval($product_data['current_stock']); ?> 
                        <?php echo htmlspecialchars($product_data['unit_of_measure'] ?? 'units'); ?>
                    </span>
                </div>
            </div>

            <div class="button-group">
                <form method="POST" style="flex: 1;">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                    <input type="hidden" name="confirm_delete" value="1">
                    <button type="submit" class="btn-delete">Delete Product</button>
                </form>
                <a href="products.php" class="btn btn-cancel">Cancel</a>
            </div>

            <p class="warning-text">
                ⚠️ This will permanently remove the product and all associated data from the inventory.
            </p>
        <?php endif; ?>
    </div>
</body>
</html>
