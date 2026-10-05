<?php
/**
 * Purchase Class
 * Manages CRUD operations for purchase records and supplier monitoring
 */

class Purchase
{
    private $db;
    public $id;
    public $product_id;
    public $supplier_name;
    public $purchase_date;
    public $invoice_number;
    public $quantity;
    public $cost_per_unit;
    public $total_cost;
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
     * Create new purchase record
     * @return array
     */
    public function create($user_id)
    {
        // Validation
        if (!$this->product_id) {
            return array('success' => false, 'message' => 'Product is required');
        }
        if (empty($this->supplier_name)) {
            return array('success' => false, 'message' => 'Supplier name is required');
        }
        if (!$this->purchase_date) {
            return array('success' => false, 'message' => 'Purchase date is required');
        }
        if (empty($this->invoice_number)) {
            return array('success' => false, 'message' => 'Invoice number is required');
        }
        if ($this->quantity <= 0) {
            return array('success' => false, 'message' => 'Quantity must be greater than 0');
        }
        if ($this->cost_per_unit < 0) {
            return array('success' => false, 'message' => 'Cost per unit cannot be negative');
        }

        // Check if invoice already exists
        $checkStmt = $this->db->prepare("SELECT id FROM purchases WHERE invoice_number = ?");
        $checkStmt->bind_param("s", $this->invoice_number);
        $checkStmt->execute();
        if ($checkStmt->get_result()->num_rows > 0) {
            return array('success' => false, 'message' => 'Invoice number already exists');
        }

        // Calculate total cost
        $this->total_cost = $this->quantity * $this->cost_per_unit;

        // Insert purchase record
        $stmt = $this->db->prepare("
            INSERT INTO purchases 
            (product_id, supplier_name, purchase_date, invoice_number, quantity, cost_per_unit, total_cost, notes, user_id)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        if (!$stmt) {
            return array('success' => false, 'message' => 'Database error: ' . $this->db->error);
        }

        $stmt->bind_param(
            "issdiddssi",
            $this->product_id,
            $this->supplier_name,
            $this->purchase_date,
            $this->invoice_number,
            $this->quantity,
            $this->cost_per_unit,
            $this->total_cost,
            $this->notes,
            $user_id
        );

        if ($stmt->execute()) {
            $purchase_id = $stmt->insert_id;
            
            // Log activity
            $this->logActivity($user_id, 'CREATE', 'Added purchase: Invoice ' . $this->invoice_number . ' from ' . $this->supplier_name);

            return array(
                'success' => true,
                'message' => 'Purchase record created successfully',
                'purchase_id' => $purchase_id
            );
        }

        return array('success' => false, 'message' => 'Error creating purchase record');
    }

    /**
     * Get all purchases with optional filtering
     * @param array $filters
     * @return array
     */
    public function getAll($filters = array())
    {
        $query = "
            SELECT 
                pu.id,
                pu.product_id,
                pu.supplier_name,
                pu.purchase_date,
                pu.invoice_number,
                pu.quantity,
                pu.cost_per_unit,
                pu.total_cost,
                pu.notes,
                pu.user_id,
                pu.created_at,
                pu.updated_at,
                p.product_code,
                p.product_name,
                p.unit_of_measure,
                u.username,
                u.first_name,
                u.last_name
            FROM purchases pu
            JOIN products p ON pu.product_id = p.id
            LEFT JOIN users u ON pu.user_id = u.id
            WHERE 1=1
        ";

        $params = array();
        $types = '';

        // Filter by supplier
        if (!empty($filters['supplier_name'])) {
            $query .= " AND pu.supplier_name = ?";
            $params[] = $filters['supplier_name'];
            $types .= "s";
        }

        // Filter by product
        if (!empty($filters['product_id'])) {
            $query .= " AND pu.product_id = ?";
            $params[] = $filters['product_id'];
            $types .= "i";
        }

        // Filter by date range
        if (!empty($filters['date_from'])) {
            $query .= " AND DATE(pu.purchase_date) >= ?";
            $params[] = $filters['date_from'];
            $types .= "s";
        }

        if (!empty($filters['date_to'])) {
            $query .= " AND DATE(pu.purchase_date) <= ?";
            $params[] = $filters['date_to'];
            $types .= "s";
        }

        // Sorting
        $query .= " ORDER BY pu.purchase_date DESC, pu.invoice_number DESC";

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
        $purchases = array();

        while ($row = $result->fetch_assoc()) {
            $purchases[] = $row;
        }

        return $purchases;
    }

    /**
     * Get purchase count for pagination
     * @param array $filters
     * @return int
     */
    public function getCount($filters = array())
    {
        $query = "
            SELECT COUNT(*) as count
            FROM purchases pu
            JOIN products p ON pu.product_id = p.id
            WHERE 1=1
        ";

        $params = array();
        $types = '';

        if (!empty($filters['supplier_name'])) {
            $query .= " AND pu.supplier_name = ?";
            $params[] = $filters['supplier_name'];
            $types .= "s";
        }

        if (!empty($filters['product_id'])) {
            $query .= " AND pu.product_id = ?";
            $params[] = $filters['product_id'];
            $types .= "i";
        }

        if (!empty($filters['date_from'])) {
            $query .= " AND DATE(pu.purchase_date) >= ?";
            $params[] = $filters['date_from'];
            $types .= "s";
        }

        if (!empty($filters['date_to'])) {
            $query .= " AND DATE(pu.purchase_date) <= ?";
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
     * Get purchase by ID
     * @param int $id
     * @return array|null
     */
    public function getById($id)
    {
        $stmt = $this->db->prepare("
            SELECT 
                pu.*,
                p.product_code,
                p.product_name,
                p.unit_of_measure,
                u.username,
                u.first_name,
                u.last_name
            FROM purchases pu
            JOIN products p ON pu.product_id = p.id
            LEFT JOIN users u ON pu.user_id = u.id
            WHERE pu.id = ?
        ");

        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();

        return $result->num_rows > 0 ? $result->fetch_assoc() : null;
    }

    /**
     * Get unique supplier names for dropdown
     * @return array
     */
    public function getSuppliers()
    {
        $result = $this->db->query("
            SELECT DISTINCT supplier_name 
            FROM purchases 
            ORDER BY supplier_name ASC
        ");

        $suppliers = array();
        while ($row = $result->fetch_assoc()) {
            $suppliers[] = $row['supplier_name'];
        }

        return $suppliers;
    }

    /**
     * Get purchase summary by supplier
     * @return array
     */
    public function getSummaryBySupplier()
    {
        $result = $this->db->query("
            SELECT 
                supplier_name,
                COUNT(*) as purchase_count,
                SUM(quantity) as total_quantity,
                SUM(total_cost) as total_spent,
                MAX(purchase_date) as last_purchase_date
            FROM purchases
            GROUP BY supplier_name
            ORDER BY total_spent DESC
        ");

        $summary = array();
        while ($row = $result->fetch_assoc()) {
            $summary[] = $row;
        }

        return $summary;
    }

    /**
     * Get purchase summary statistics
     * @param array $filters
     * @return array
     */
    public function getSummaryStats($filters = array())
    {
        $query = "
            SELECT 
                COUNT(*) as total_purchases,
                SUM(quantity) as total_quantity,
                SUM(total_cost) as total_spent,
                AVG(cost_per_unit) as avg_cost_per_unit,
                COUNT(DISTINCT supplier_name) as total_suppliers,
                COUNT(DISTINCT product_id) as total_products
            FROM purchases
            WHERE 1=1
        ";

        $params = array();
        $types = '';

        if (!empty($filters['supplier_name'])) {
            $query .= " AND supplier_name = ?";
            $params[] = $filters['supplier_name'];
            $types .= "s";
        }

        if (!empty($filters['date_from'])) {
            $query .= " AND DATE(purchase_date) >= ?";
            $params[] = $filters['date_from'];
            $types .= "s";
        }

        if (!empty($filters['date_to'])) {
            $query .= " AND DATE(purchase_date) <= ?";
            $params[] = $filters['date_to'];
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
     * Update purchase record
     * @param int $user_id
     * @return array
     */
    public function update($user_id)
    {
        if (!$this->id) {
            return array('success' => false, 'message' => 'Purchase ID is required');
        }

        // Calculate total cost
        $this->total_cost = $this->quantity * $this->cost_per_unit;

        $stmt = $this->db->prepare("
            UPDATE purchases
            SET product_id = ?, supplier_name = ?, purchase_date = ?, 
                invoice_number = ?, quantity = ?, cost_per_unit = ?, 
                total_cost = ?, notes = ?
            WHERE id = ?
        ");

        if (!$stmt) {
            return array('success' => false, 'message' => 'Database error');
        }

        $stmt->bind_param(
            "issdiddsii",
            $this->product_id,
            $this->supplier_name,
            $this->purchase_date,
            $this->invoice_number,
            $this->quantity,
            $this->cost_per_unit,
            $this->total_cost,
            $this->notes,
            $this->id
        );

        if ($stmt->execute()) {
            $this->logActivity($user_id, 'UPDATE', 'Updated purchase: Invoice ' . $this->invoice_number);
            return array('success' => true, 'message' => 'Purchase updated successfully');
        }

        return array('success' => false, 'message' => 'Error updating purchase');
    }

    /**
     * Delete purchase record
     * @param int $id
     * @param int $user_id
     * @return array
     */
    public function delete($id, $user_id)
    {
        $purchase = $this->getById($id);
        
        if (!$purchase) {
            return array('success' => false, 'message' => 'Purchase not found');
        }

        $stmt = $this->db->prepare("DELETE FROM purchases WHERE id = ?");
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            $this->logActivity($user_id, 'DELETE', 'Deleted purchase: Invoice ' . $purchase['invoice_number']);
            return array('success' => true, 'message' => 'Purchase deleted successfully');
        }

        return array('success' => false, 'message' => 'Error deleting purchase');
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
