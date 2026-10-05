<?php
/**
 * StockMovement Class
 * Handles stock movement tracking, reporting, and analysis
 */

class StockMovement
{
    private $db;
    public $id;
    public $product_id;
    public $movement_type;
    public $quantity;
    public $beginning_stock;
    public $ending_stock;
    public $reference;
    public $reason;
    public $notes;
    public $user_id;
    public $created_at;

    /**
     * Constructor
     */
    public function __construct($database)
    {
        $this->db = $database;
    }

    /**
     * Log a stock movement
     * @param int $product_id
     * @param string $movement_type (IN, OUT, ADJUSTMENT, RETURN, DAMAGE)
     * @param int $quantity
     * @param string $reference (PO, Invoice, etc.)
     * @param string $reason
     * @param int $user_id
     * @return array
     */
    public function logMovement($product_id, $movement_type, $quantity, $reference, $reason, $user_id, $notes = '')
    {
        try {
            // Get current stock from products table
            $currentStockStmt = $this->db->prepare("
                SELECT current_stock FROM products WHERE id = ?
            ");
            $currentStockStmt->bind_param("i", $product_id);
            $currentStockStmt->execute();
            $result = $currentStockStmt->get_result();
            $product = $result->fetch_assoc();

            if (!$product) {
                return array('success' => false, 'message' => 'Product not found');
            }

            $beginning_stock = $product['current_stock'];
            
            // Calculate ending stock based on movement type
            $ending_stock = $beginning_stock;
            if ($movement_type === 'IN' || $movement_type === 'RETURN') {
                $ending_stock = $beginning_stock + $quantity;
            } elseif ($movement_type === 'OUT' || $movement_type === 'DAMAGE') {
                $ending_stock = $beginning_stock - $quantity;
            } elseif ($movement_type === 'ADJUSTMENT') {
                $ending_stock = $beginning_stock + $quantity; // quantity can be negative
            }

            // Prevent negative stock
            if ($ending_stock < 0) {
                return array('success' => false, 'message' => 'Insufficient stock for this transaction');
            }

            // Start transaction
            $this->db->begin_transaction();

            try {
                // Insert stock movement record
                $insertStmt = $this->db->prepare("
                    INSERT INTO stock_movements 
                    (product_id, movement_type, quantity, beginning_stock, ending_stock, reference, reason, notes, user_id)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $insertStmt->bind_param(
                    "isiiisssi",
                    $product_id,
                    $movement_type,
                    $quantity,
                    $beginning_stock,
                    $ending_stock,
                    $reference,
                    $reason,
                    $notes,
                    $user_id
                );

                if (!$insertStmt->execute()) {
                    throw new Exception('Failed to insert stock movement');
                }

                // Update product current_stock
                $updateStmt = $this->db->prepare("
                    UPDATE products SET current_stock = ? WHERE id = ?
                ");
                $updateStmt->bind_param("ii", $ending_stock, $product_id);

                if (!$updateStmt->execute()) {
                    throw new Exception('Failed to update product stock');
                }

                // Commit transaction
                $this->db->commit();

                return array(
                    'success' => true,
                    'message' => 'Stock movement logged successfully',
                    'movement_id' => $this->db->insert_id
                );

            } catch (Exception $e) {
                $this->db->rollback();
                return array('success' => false, 'message' => $e->getMessage());
            }

        } catch (Exception $e) {
            return array('success' => false, 'message' => $e->getMessage());
        }
    }

    /**
     * Get stock movements with filtering
     * @param array $filters (product_id, movement_type, date_from, date_to, limit, offset)
     * @return array
     */
    public function getMovements($filters = array())
    {
        $query = "
            SELECT 
                sm.id,
                sm.product_id,
                sm.movement_type,
                sm.quantity,
                sm.beginning_stock,
                sm.ending_stock,
                sm.reference,
                sm.reason,
                sm.notes,
                sm.user_id,
                sm.created_at,
                p.product_code,
                p.product_name,
                p.unit_of_measure,
                u.username
            FROM stock_movements sm
            JOIN products p ON sm.product_id = p.id
            LEFT JOIN users u ON sm.user_id = u.id
            WHERE 1=1
        ";

        $params = array();
        $types = '';

        // Apply filters
        if (!empty($filters['product_id'])) {
            $query .= " AND sm.product_id = ?";
            $params[] = $filters['product_id'];
            $types .= "i";
        }

        if (!empty($filters['movement_type'])) {
            $query .= " AND sm.movement_type = ?";
            $params[] = $filters['movement_type'];
            $types .= "s";
        }

        if (!empty($filters['date_from'])) {
            $query .= " AND DATE(sm.created_at) >= ?";
            $params[] = $filters['date_from'];
            $types .= "s";
        }

        if (!empty($filters['date_to'])) {
            $query .= " AND DATE(sm.created_at) <= ?";
            $params[] = $filters['date_to'];
            $types .= "s";
        }

        // Add sorting and pagination
        $query .= " ORDER BY sm.created_at DESC";

        if (!empty($filters['limit'])) {
            $limit = intval($filters['limit']);
            $offset = intval($filters['offset'] ?? 0);
            $query .= " LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;
            $types .= "ii";
        }

        $stmt = $this->db->prepare($query);

        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }

        $stmt->execute();
        $result = $stmt->get_result();
        $movements = array();

        while ($row = $result->fetch_assoc()) {
            $movements[] = $row;
        }

        return $movements;
    }

    /**
     * Get stock movement count for pagination
     * @param array $filters
     * @return int
     */
    public function getMovementCount($filters = array())
    {
        $query = "
            SELECT COUNT(*) as count
            FROM stock_movements sm
            JOIN products p ON sm.product_id = p.id
            WHERE 1=1
        ";

        $params = array();
        $types = '';

        if (!empty($filters['product_id'])) {
            $query .= " AND sm.product_id = ?";
            $params[] = $filters['product_id'];
            $types .= "i";
        }

        if (!empty($filters['movement_type'])) {
            $query .= " AND sm.movement_type = ?";
            $params[] = $filters['movement_type'];
            $types .= "s";
        }

        if (!empty($filters['date_from'])) {
            $query .= " AND DATE(sm.created_at) >= ?";
            $params[] = $filters['date_from'];
            $types .= "s";
        }

        if (!empty($filters['date_to'])) {
            $query .= " AND DATE(sm.created_at) <= ?";
            $params[] = $filters['date_to'];
            $types .= "s";
        }

        $stmt = $this->db->prepare($query);

        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }

        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();

        return intval($row['count']);
    }

    /**
     * Get stock report for a specific date range
     * @param string $date_from (YYYY-MM-DD)
     * @param string $date_to (YYYY-MM-DD)
     * @param int $product_id (optional)
     * @return array
     */
    public function getStockReport($date_from, $date_to, $product_id = null)
    {
        // Get beginning stock for date_from
        $query = "
            SELECT 
                p.id,
                p.product_code,
                p.product_name,
                p.unit_of_measure,
                p.category,
                p.brand,
                IFNULL(MAX(CASE WHEN DATE(sm.created_at) < ? THEN sm.ending_stock END), 0) as beginning_stock,
                IFNULL(SUM(CASE WHEN movement_type IN ('IN', 'RETURN') AND DATE(sm.created_at) BETWEEN ? AND ? THEN quantity ELSE 0 END), 0) as stock_in,
                IFNULL(SUM(CASE WHEN movement_type IN ('OUT', 'DAMAGE') AND DATE(sm.created_at) BETWEEN ? AND ? THEN quantity ELSE 0 END), 0) as stock_out,
                IFNULL(MAX(CASE WHEN DATE(sm.created_at) <= ? THEN sm.ending_stock END), 0) as ending_stock
            FROM products p
            LEFT JOIN stock_movements sm ON p.id = sm.product_id
        ";

        $params = array($date_from, $date_from, $date_to, $date_from, $date_to, $date_to);
        $types = "ssssss";

        if ($product_id) {
            $query .= " WHERE p.id = ?";
            $params[] = $product_id;
            $types .= "i";
        } else {
            $query .= " WHERE p.is_active = 1";
        }

        $query .= " GROUP BY p.id ORDER BY p.product_code";

        $stmt = $this->db->prepare($query);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        $report = array();

        while ($row = $result->fetch_assoc()) {
            // Only include products with movements in the period
            if ($row['stock_in'] > 0 || $row['stock_out'] > 0 || $row['beginning_stock'] > 0) {
                $report[] = $row;
            }
        }

        return $report;
    }

    /**
     * Get summary statistics for a date range
     * @param string $date_from
     * @param string $date_to
     * @return array
     */
    public function getSummaryStats($date_from, $date_to)
    {
        $stmt = $this->db->prepare("
            SELECT 
                COUNT(DISTINCT product_id) as total_products,
                COUNT(*) as total_movements,
                SUM(CASE WHEN movement_type IN ('IN', 'RETURN') THEN quantity ELSE 0 END) as total_stock_in,
                SUM(CASE WHEN movement_type IN ('OUT', 'DAMAGE') THEN quantity ELSE 0 END) as total_stock_out,
                SUM(CASE WHEN movement_type = 'ADJUSTMENT' THEN quantity ELSE 0 END) as total_adjustments
            FROM stock_movements
            WHERE DATE(created_at) BETWEEN ? AND ?
        ");
        $stmt->bind_param("ss", $date_from, $date_to);
        $stmt->execute();
        $result = $stmt->get_result();

        return $result->fetch_assoc();
    }

    /**
     * Get movement types for dropdown
     * @return array
     */
    public function getMovementTypes()
    {
        return array('IN', 'OUT', 'ADJUSTMENT', 'RETURN', 'DAMAGE');
    }
}
?>
