<?php
/**
 * Product Class
 * Manages CRUD operations for products/inventory items
 */

class Product {
    private $conn;
    private $table = 'products';

    // Product properties
    public $id;
    public $product_code;
    public $product_name;
    public $category;
    public $brand;
    public $supplier;
    public $unit_of_measure;
    public $cost_price;
    public $selling_price;
    public $current_stock;
    public $reorder_level;
    public $expiry_date;
    public $description;
    public $is_active;
    public $created_by;
    public $created_at;
    public $updated_by;
    public $updated_at;

    public function __construct($database_conn) {
        $this->conn = $database_conn;
    }

    /**
     * Get all products with optional filtering
     */
    public function getAll($filter = [], $sort = 'product_name', $order = 'ASC', $limit = null, $offset = 0) {
        $query = "SELECT p.*, 
                         CONCAT(u1.first_name, ' ', u1.last_name) as created_by_name,
                         CONCAT(u2.first_name, ' ', u2.last_name) as updated_by_name,
                         (p.cost_price * p.current_stock) as inventory_value
                  FROM " . $this->table . " p
                  LEFT JOIN users u1 ON p.created_by = u1.id
                  LEFT JOIN users u2 ON p.updated_by = u2.id
                  WHERE 1=1";

        // Apply filters
        if (!empty($filter['category'])) {
            $query .= " AND p.category = '" . $this->conn->real_escape_string($filter['category']) . "'";
        }
        if (!empty($filter['brand'])) {
            $query .= " AND p.brand = '" . $this->conn->real_escape_string($filter['brand']) . "'";
        }
        if (!empty($filter['supplier'])) {
            $query .= " AND p.supplier = '" . $this->conn->real_escape_string($filter['supplier']) . "'";
        }
        if (!empty($filter['search'])) {
            $search = $this->conn->real_escape_string($filter['search']);
            $query .= " AND (p.product_code LIKE '%$search%' OR p.product_name LIKE '%$search%')";
        }
        if (isset($filter['is_active'])) {
            $query .= " AND p.is_active = " . (int)$filter['is_active'];
        }
        if (isset($filter['low_stock']) && $filter['low_stock']) {
            $query .= " AND p.current_stock <= p.reorder_level";
        }

        // Sort
        $allowed_sorts = ['product_code', 'product_name', 'category', 'brand', 'current_stock', 'cost_price', 'selling_price', 'created_at'];
        if (in_array($sort, $allowed_sorts)) {
            $query .= " ORDER BY p.{$sort} {$order}";
        } else {
            $query .= " ORDER BY p.product_name ASC";
        }

        // Limit
        if ($limit !== null) {
            $query .= " LIMIT {$offset}, {$limit}";
        }

        $result = $this->conn->query($query);
        
        if (!$result) {
            return [
                'success' => false,
                'message' => 'Database error: ' . $this->conn->error
            ];
        }

        $products = [];
        while ($row = $result->fetch_assoc()) {
            $products[] = $row;
        }

        return [
            'success' => true,
            'data' => $products,
            'count' => count($products)
        ];
    }

    /**
     * Get single product by ID
     */
    public function getById($id) {
        $stmt = $this->conn->prepare(
            "SELECT p.*, 
                    CONCAT(u1.first_name, ' ', u1.last_name) as created_by_name,
                    CONCAT(u2.first_name, ' ', u2.last_name) as updated_by_name,
                    (p.cost_price * p.current_stock) as inventory_value
             FROM " . $this->table . " p
             LEFT JOIN users u1 ON p.created_by = u1.id
             LEFT JOIN users u2 ON p.updated_by = u2.id
             WHERE p.id = ?"
        );

        if (!$stmt) {
            return ['success' => false, 'message' => 'Database error'];
        }

        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            return [
                'success' => true,
                'data' => $result->fetch_assoc()
            ];
        }

        return ['success' => false, 'message' => 'Product not found'];
    }

    /**
     * Get product by code
     */
    public function getByCode($product_code) {
        $stmt = $this->conn->prepare(
            "SELECT * FROM " . $this->table . " WHERE product_code = ?"
        );

        $stmt->bind_param('s', $product_code);
        $stmt->execute();
        $result = $stmt->get_result();

        return $result->num_rows > 0 ? $result->fetch_assoc() : null;
    }

    /**
     * Generate unique product code
     */
    public function generateProductCode($category = '') {
        // Get next sequence number
        $result = $this->conn->query(
            "SELECT COUNT(*) as total FROM " . $this->table
        );
        $row = $result->fetch_assoc();
        $sequence = $row['total'] + 1;

        // Create code: CATXXX-00001 format (if category provided)
        if (!empty($category)) {
            $cat_prefix = strtoupper(substr($category, 0, 3));
        } else {
            $cat_prefix = 'PDD'; // Default if no category
        }

        $code = $cat_prefix . str_pad($sequence, 5, '0', STR_PAD_LEFT);

        // Make sure it's unique
        $check = $this->getByCode($code);
        if ($check) {
            return $this->generateProductCode($category);
        }

        return $code;
    }

    /**
     * Create new product
     */
    public function create($user_id) {
        // Validation
        if (empty($this->product_name)) {
            return ['success' => false, 'message' => 'Product name is required'];
        }
        if (empty($this->product_code)) {
            $this->product_code = $this->generateProductCode($this->category);
        }
        if ($this->cost_price === null || $this->cost_price < 0) {
            return ['success' => false, 'message' => 'Cost price must be a positive number'];
        }
        if ($this->selling_price === null || $this->selling_price < 0) {
            return ['success' => false, 'message' => 'Selling price must be a positive number'];
        }
        if ($this->current_stock === null || $this->current_stock < 0) {
            return ['success' => false, 'message' => 'Stock must be a positive number'];
        }

        // Check if product code already exists
        if ($this->getByCode($this->product_code)) {
            return ['success' => false, 'message' => 'Product code already exists'];
        }

        // Prepare statement
        $stmt = $this->conn->prepare(
            "INSERT INTO " . $this->table . "
             (product_code, product_name, category, brand, supplier, unit_of_measure, 
              cost_price, selling_price, current_stock, reorder_level, expiry_date, 
              description, is_active, created_by)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );

        if (!$stmt) {
            return ['success' => false, 'message' => 'Database error: ' . $this->conn->error];
        }

        // Bind parameters
        $stmt->bind_param(
            'ssssssddiiisii',
            $this->product_code,
            $this->product_name,
            $this->category,
            $this->brand,
            $this->supplier,
            $this->unit_of_measure,
            $this->cost_price,
            $this->selling_price,
            $this->current_stock,
            $this->reorder_level,
            $this->expiry_date,
            $this->description,
            $this->is_active,
            $user_id
        );

        if ($stmt->execute()) {
            $product_id = $stmt->insert_id;

            // Log activity
            $this->logActivity($user_id, 'CREATE', 'Added product: ' . $this->product_name . ' (Code: ' . $this->product_code . ')');

            return [
                'success' => true,
                'message' => 'Product created successfully',
                'product_id' => $product_id
            ];
        }

        return ['success' => false, 'message' => 'Error creating product: ' . $stmt->error];
    }

    /**
     * Update existing product
     */
    public function update($user_id) {
        if (!$this->id) {
            return ['success' => false, 'message' => 'Product ID is required'];
        }

        // Validation
        if (empty($this->product_name)) {
            return ['success' => false, 'message' => 'Product name is required'];
        }

        // Prepare statement
        $stmt = $this->conn->prepare(
            "UPDATE " . $this->table . "
             SET product_name = ?, category = ?, brand = ?, supplier = ?, 
                 unit_of_measure = ?, cost_price = ?, selling_price = ?, 
                 current_stock = ?, reorder_level = ?, expiry_date = ?, 
                 description = ?, is_active = ?, updated_by = ?
             WHERE id = ?"
        );

        if (!$stmt) {
            return ['success' => false, 'message' => 'Database error'];
        }

        $stmt->bind_param(
            'sssssddiiiisii',
            $this->product_name,
            $this->category,
            $this->brand,
            $this->supplier,
            $this->unit_of_measure,
            $this->cost_price,
            $this->selling_price,
            $this->current_stock,
            $this->reorder_level,
            $this->expiry_date,
            $this->description,
            $this->is_active,
            $user_id,
            $this->id
        );

        if ($stmt->execute()) {
            // Log activity
            $this->logActivity($user_id, 'UPDATE', 'Updated product: ' . $this->product_name . ' (ID: ' . $this->id . ')');

            return [
                'success' => true,
                'message' => 'Product updated successfully'
            ];
        }

        return ['success' => false, 'message' => 'Error updating product'];
    }

    /**
     * Delete product
     */
    public function delete($id, $user_id) {
        // Get product info before deletion for logging
        $product = $this->getById($id);
        
        if (!$product['success']) {
            return ['success' => false, 'message' => 'Product not found'];
        }

        $stmt = $this->conn->prepare(
            "DELETE FROM " . $this->table . " WHERE id = ?"
        );

        if (!$stmt) {
            return ['success' => false, 'message' => 'Database error'];
        }

        $stmt->bind_param('i', $id);

        if ($stmt->execute()) {
            // Log activity
            $this->logActivity($user_id, 'DELETE', 'Deleted product: ' . $product['data']['product_name']);

            return [
                'success' => true,
                'message' => 'Product deleted successfully'
            ];
        }

        return ['success' => false, 'message' => 'Error deleting product'];
    }

    /**
     * Get inventory statistics
     */
    public function getInventoryStats() {
        $query = "SELECT 
                    COUNT(*) as total_products,
                    SUM(current_stock) as total_stock,
                    SUM(cost_price * current_stock) as total_cost_value,
                    SUM(selling_price * current_stock) as total_selling_value,
                    COUNT(CASE WHEN current_stock <= reorder_level THEN 1 END) as low_stock_count,
                    COUNT(CASE WHEN expiry_date < CURDATE() AND expiry_date IS NOT NULL THEN 1 END) as expired_count,
                    COUNT(CASE WHEN expiry_date < DATE_ADD(CURDATE(), INTERVAL 7 DAY) AND expiry_date IS NOT NULL THEN 1 END) as expiring_soon_count
                  FROM " . $this->table . " WHERE is_active = 1";

        $result = $this->conn->query($query);

        if ($result) {
            return [
                'success' => true,
                'data' => $result->fetch_assoc()
            ];
        }

        return ['success' => false, 'message' => 'Error fetching statistics'];
    }

    /**
     * Get unique values for dropdowns
     */
    public function getUniqueValues($field) {
        $allowed_fields = ['category', 'brand', 'supplier', 'unit_of_measure'];
        
        if (!in_array($field, $allowed_fields)) {
            return ['success' => false, 'message' => 'Invalid field'];
        }

        $query = "SELECT DISTINCT {$field} FROM " . $this->table . " 
                  WHERE {$field} IS NOT NULL AND {$field} != '' 
                  ORDER BY {$field}";
        
        $result = $this->conn->query($query);

        if ($result) {
            $values = [];
            while ($row = $result->fetch_assoc()) {
                $values[] = $row[$field];
            }
            return [
                'success' => true,
                'data' => $values
            ];
        }

        return ['success' => false, 'data' => []];
    }

    /**
     * Log activity
     */
    private function logActivity($user_id, $action, $description) {
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
        
        $stmt = $this->conn->prepare(
            "INSERT INTO activity_logs (user_id, action, description, ip_address) 
             VALUES (?, ?, ?, ?)"
        );

        if ($stmt) {
            $stmt->bind_param('isss', $user_id, $action, $description, $ip_address);
            $stmt->execute();
        }
    }

    /**
     * Check if product exists by code
     */
    public function exists($product_code, $exclude_id = null) {
        $stmt = $this->conn->prepare(
            "SELECT id FROM " . $this->table . " WHERE product_code = ?"
        );

        $stmt->bind_param('s', $product_code);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            
            // If exclude_id provided, check if it's a different product
            if ($exclude_id && $row['id'] == $exclude_id) {
                return false;
            }
            
            return true;
        }

        return false;
    }
}
?>
