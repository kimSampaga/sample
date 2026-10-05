<?php
/**
 * Sales Class
 * Manages CRUD operations for sales transactions and reporting
 */

class Sales
{
    private $db;
    public $id;
    public $product_id;
    public $sale_date;
    public $quantity_sold;
    public $unit_price;
    public $cost_price;
    public $total_amount;
    public $payment_type;
    public $customer_name;
    public $reference;
    public $notes;
    public $user_id;
    public $created_at;
    public $updated_at;

    /**
     * Constructor
     */
    public function __construct($database)
    {
        $this->db = $database;
    }

    /**
     * Create new sales record
     * @return array
     */
    public function create($user_id)
    {
        // Validation
        if (!$this->product_id) {
            return array('success' => false, 'message' => 'Product is required');
        }
        if (!$this->sale_date) {
            return array('success' => false, 'message' => 'Sale date is required');
        }
        if ($this->quantity_sold <= 0) {
            return array('success' => false, 'message' => 'Quantity must be greater than 0');
        }
        if ($this->unit_price < 0) {
            return array('success' => false, 'message' => 'Unit price cannot be negative');
        }
        if ($this->cost_price < 0) {
            return array('success' => false, 'message' => 'Cost price cannot be negative');
        }
        if (empty($this->payment_type) || !in_array($this->payment_type, array('CASH', 'CREDIT'))) {
            return array('success' => false, 'message' => 'Invalid payment type');
        }

        // Calculate total amount
        $this->total_amount = $this->quantity_sold * $this->unit_price;

        // Insert sales record
        $stmt = $this->db->prepare("
            INSERT INTO sales 
            (product_id, sale_date, quantity_sold, unit_price, cost_price, total_amount, payment_type, customer_name, reference, notes, user_id)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        if (!$stmt) {
            return array('success' => false, 'message' => 'Database error: ' . $this->db->error);
        }

        $stmt->bind_param(
            "isidddssssii",
            $this->product_id,
            $this->sale_date,
            $this->quantity_sold,
            $this->unit_price,
            $this->cost_price,
            $this->total_amount,
            $this->payment_type,
            $this->customer_name,
            $this->reference,
            $this->notes,
            $user_id
        );

        if ($stmt->execute()) {
            $sale_id = $stmt->insert_id;
            
            // Log activity
            $this->logActivity($user_id, 'CREATE', 'Recorded sale: ' . $this->quantity_sold . ' units on ' . $this->sale_date);

            return array(
                'success' => true,
                'message' => 'Sale recorded successfully',
                'sale_id' => $sale_id
            );
        }

        return array('success' => false, 'message' => 'Error recording sale');
    }

    /**
     * Get all sales with optional filtering
     * @param array $filters
     * @return array
     */
    public function getAll($filters = array())
    {
        $query = "
            SELECT 
                s.id,
                s.product_id,
                s.sale_date,
                s.quantity_sold,
                s.unit_price,
                s.cost_price,
                s.total_amount,
                s.payment_type,
                s.customer_name,
                s.reference,
                s.notes,
                s.user_id,
                s.created_at,
                s.updated_at,
                p.product_code,
                p.product_name,
                p.unit_of_measure,
                (s.total_amount - (s.cost_price * s.quantity_sold)) as profit,
                u.username,
                u.first_name,
                u.last_name
            FROM sales s
            JOIN products p ON s.product_id = p.id
            LEFT JOIN users u ON s.user_id = u.id
            WHERE 1=1
        ";

        $params = array();
        $types = '';

        // Filter by product
        if (!empty($filters['product_id'])) {
            $query .= " AND s.product_id = ?";
            $params[] = $filters['product_id'];
            $types .= "i";
        }

        // Filter by payment type
        if (!empty($filters['payment_type'])) {
            $query .= " AND s.payment_type = ?";
            $params[] = $filters['payment_type'];
            $types .= "s";
        }

        // Filter by date range
        if (!empty($filters['date_from'])) {
            $query .= " AND DATE(s.sale_date) >= ?";
            $params[] = $filters['date_from'];
            $types .= "s";
        }

        if (!empty($filters['date_to'])) {
            $query .= " AND DATE(s.sale_date) <= ?";
            $params[] = $filters['date_to'];
            $types .= "s";
        }

        // Sorting
        $query .= " ORDER BY s.sale_date DESC, s.id DESC";

        // Pagination
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
        $sales = array();

        while ($row = $result->fetch_assoc()) {
            $sales[] = $row;
        }

        return $sales;
    }

    /**
     * Get sales count for pagination
     * @param array $filters
     * @return int
     */
    public function getCount($filters = array())
    {
        $query = "
            SELECT COUNT(*) as count
            FROM sales s
            JOIN products p ON s.product_id = p.id
            WHERE 1=1
        ";

        $params = array();
        $types = '';

        if (!empty($filters['product_id'])) {
            $query .= " AND s.product_id = ?";
            $params[] = $filters['product_id'];
            $types .= "i";
        }

        if (!empty($filters['payment_type'])) {
            $query .= " AND s.payment_type = ?";
            $params[] = $filters['payment_type'];
            $types .= "s";
        }

        if (!empty($filters['date_from'])) {
            $query .= " AND DATE(s.sale_date) >= ?";
            $params[] = $filters['date_from'];
            $types .= "s";
        }

        if (!empty($filters['date_to'])) {
            $query .= " AND DATE(s.sale_date) <= ?";
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
     * Get sale by ID
     * @param int $id
     * @return array|null
     */
    public function getById($id)
    {
        $stmt = $this->db->prepare("
            SELECT 
                s.*,
                p.product_code,
                p.product_name,
                p.unit_of_measure,
                (s.total_amount - (s.cost_price * s.quantity_sold)) as profit,
                u.username,
                u.first_name,
                u.last_name
            FROM sales s
            JOIN products p ON s.product_id = p.id
            LEFT JOIN users u ON s.user_id = u.id
            WHERE s.id = ?
        ");

        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();

        return $result->num_rows > 0 ? $result->fetch_assoc() : null;
    }

    /**
     * Get sales summary grouped by date
     * @param array $filters
     * @return array
     */
    public function getSalesReport($filters = array())
    {
        $query = "
            SELECT 
                s.sale_date as date,
                COUNT(*) as total_transactions,
                SUM(CASE WHEN s.payment_type = 'CASH' THEN s.total_amount ELSE 0 END) as cash_sales,
                SUM(CASE WHEN s.payment_type = 'CREDIT' THEN s.total_amount ELSE 0 END) as credit_sales,
                SUM(s.total_amount) as total_sales,
                SUM(s.cost_price * s.quantity_sold) as total_cost,
                (SUM(s.total_amount) - SUM(s.cost_price * s.quantity_sold)) as gross_profit
            FROM sales s
            WHERE 1=1
        ";

        $params = array();
        $types = '';

        if (!empty($filters['date_from'])) {
            $query .= " AND DATE(s.sale_date) >= ?";
            $params[] = $filters['date_from'];
            $types .= "s";
        }

        if (!empty($filters['date_to'])) {
            $query .= " AND DATE(s.sale_date) <= ?";
            $params[] = $filters['date_to'];
            $types .= "s";
        }

        $query .= " GROUP BY s.sale_date ORDER BY s.sale_date DESC";

        $stmt = $this->db->prepare($query);

        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }

        $stmt->execute();
        $result = $stmt->get_result();
        $report = array();

        while ($row = $result->fetch_assoc()) {
            $report[] = $row;
        }

        return $report;
    }

    /**
     * Get summary statistics for sales
     * @param array $filters
     * @return array
     */
    public function getSummaryStats($filters = array())
    {
        $query = "
            SELECT 
                COUNT(*) as total_transactions,
                COUNT(DISTINCT DATE(sale_date)) as total_days,
                SUM(quantity_sold) as total_quantity,
                SUM(CASE WHEN payment_type = 'CASH' THEN total_amount ELSE 0 END) as cash_sales,
                SUM(CASE WHEN payment_type = 'CREDIT' THEN total_amount ELSE 0 END) as credit_sales,
                SUM(total_amount) as total_sales,
                SUM(cost_price * quantity_sold) as total_cost,
                (SUM(total_amount) - SUM(cost_price * quantity_sold)) as gross_profit,
                AVG(total_amount) as avg_sale_value,
                MAX(total_amount) as max_sale,
                MIN(total_amount) as min_sale
            FROM sales
            WHERE 1=1
        ";

        $params = array();
        $types = '';

        if (!empty($filters['date_from'])) {
            $query .= " AND DATE(sale_date) >= ?";
            $params[] = $filters['date_from'];
            $types .= "s";
        }

        if (!empty($filters['date_to'])) {
            $query .= " AND DATE(sale_date) <= ?";
            $params[] = $filters['date_to'];
            $types .= "s";
        }

        if (!empty($filters['payment_type'])) {
            $query .= " AND payment_type = ?";
            $params[] = $filters['payment_type'];
            $types .= "s";
        }

        $stmt = $this->db->prepare($query);

        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }

        $stmt->execute();
        $result = $stmt->get_result();

        return $result->fetch_assoc();
    }

    /**
     * Update sales record
     * @param int $user_id
     * @return array
     */
    public function update($user_id)
    {
        if (!$this->id) {
            return array('success' => false, 'message' => 'Sale ID is required');
        }

        // Calculate total amount
        $this->total_amount = $this->quantity_sold * $this->unit_price;

        $stmt = $this->db->prepare("
            UPDATE sales
            SET product_id = ?, sale_date = ?, quantity_sold = ?, 
                unit_price = ?, cost_price = ?, total_amount = ?, 
                payment_type = ?, customer_name = ?, reference = ?, notes = ?
            WHERE id = ?
        ");

        if (!$stmt) {
            return array('success' => false, 'message' => 'Database error');
        }

        $stmt->bind_param(
            "isidddssssii",
            $this->product_id,
            $this->sale_date,
            $this->quantity_sold,
            $this->unit_price,
            $this->cost_price,
            $this->total_amount,
            $this->payment_type,
            $this->customer_name,
            $this->reference,
            $this->notes,
            $this->id
        );

        if ($stmt->execute()) {
            $this->logActivity($user_id, 'UPDATE', 'Updated sale: ID ' . $this->id);
            return array('success' => true, 'message' => 'Sale updated successfully');
        }

        return array('success' => false, 'message' => 'Error updating sale');
    }

    /**
     * Delete sales record
     * @param int $id
     * @param int $user_id
     * @return array
     */
    public function delete($id, $user_id)
    {
        $sale = $this->getById($id);
        
        if (!$sale) {
            return array('success' => false, 'message' => 'Sale not found');
        }

        $stmt = $this->db->prepare("DELETE FROM sales WHERE id = ?");
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            $this->logActivity($user_id, 'DELETE', 'Deleted sale: ID ' . $id);
            return array('success' => true, 'message' => 'Sale deleted successfully');
        }

        return array('success' => false, 'message' => 'Error deleting sale');
    }

    /**
     * Log activity
     * @param int $user_id
     * @param string $action
     * @param string $description
     */
    private function logActivity($user_id, $action, $description)
    {
        $stmt = $this->db->prepare("
            INSERT INTO activity_logs (user_id, action, description)
            VALUES (?, ?, ?)
        ");
        $stmt->bind_param("iss", $user_id, $action, $description);
        $stmt->execute();
    }
}
?>
