<?php
/**
 * Analytics Class
 * Computes business intelligence metrics and analytical reports
 */

class Analytics
{
    private $db;

    /**
     * Constructor
     */
    public function __construct($database)
    {
        $this->db = $database;
    }

    /**
     * Calculate total inventory value
     * Total Inventory Value = Sum of (Cost Price × Current Stock) for all active products
     * @return float
     */
    public function getTotalInventoryValue()
    {
        $result = $this->db->query("
            SELECT SUM(cost_price * current_stock) as total_value
            FROM products
            WHERE is_active = 1
        ");

        $row = $result->fetch_assoc();
        return floatval($row['total_value'] ?? 0);
    }

    /**
     * Get fast-moving items (high sales velocity)
     * Based on items sold in the last 30 days
     * @param int $days
     * @param int $limit
     * @return array
     */
    public function getFastMovingItems($days = 30, $limit = 10)
    {
        $date_from = date('Y-m-d', strtotime("-$days days"));
        
        $result = $this->db->prepare("
            SELECT 
                p.id,
                p.product_code,
                p.product_name,
                p.current_stock,
                p.cost_price,
                p.selling_price,
                p.unit_of_measure,
                COUNT(s.id) as sales_count,
                SUM(s.quantity_sold) as total_quantity_sold,
                SUM(s.total_amount) as total_sales_amount,
                SUM(s.quantity_sold * s.cost_price) as total_cost,
                (SUM(s.total_amount) - SUM(s.quantity_sold * s.cost_price)) as total_profit,
                ROUND(((SUM(s.total_amount) - SUM(s.quantity_sold * s.cost_price)) / SUM(s.total_amount) * 100), 2) as profit_margin,
                AVG(s.quantity_sold) as avg_quantity_per_sale,
                (SUM(s.quantity_sold) / (p.current_stock + 0.1)) as turnover_ratio,
                MAX(s.sale_date) as last_sale_date
            FROM products p
            LEFT JOIN sales s ON p.id = s.product_id AND DATE(s.sale_date) >= ?
            WHERE p.is_active = 1
            GROUP BY p.id
            HAVING SUM(s.quantity_sold) > 0
            ORDER BY SUM(s.quantity_sold) DESC
            LIMIT ?
        ");

        $result->bind_param("si", $date_from, $limit);
        $result->execute();
        $result = $result->get_result();

        $items = array();
        while ($row = $result->fetch_assoc()) {
            $items[] = $row;
        }

        return $items;
    }

    /**
     * Get slow-moving items (low sales velocity)
     * Products with minimal sales or not sold recently
     * @param int $days
     * @param int $limit
     * @return array
     */
    public function getSlowMovingItems($days = 30, $limit = 10)
    {
        $date_from = date('Y-m-d', strtotime("-$days days"));
        
        $result = $this->db->prepare("
            SELECT 
                p.id,
                p.product_code,
                p.product_name,
                p.current_stock,
                p.cost_price,
                p.selling_price,
                p.unit_of_measure,
                p.reorder_level,
                COUNT(s.id) as sales_count,
                COALESCE(SUM(s.quantity_sold), 0) as total_quantity_sold,
                COALESCE(SUM(s.total_amount), 0) as total_sales_amount,
                COALESCE(SUM(s.quantity_sold * s.cost_price), 0) as total_cost,
                (p.cost_price * p.current_stock) as inventory_value,
                MAX(s.sale_date) as last_sale_date,
                DATEDIFF(CURDATE(), MAX(s.sale_date)) as days_since_sale
            FROM products p
            LEFT JOIN sales s ON p.id = s.product_id AND DATE(s.sale_date) >= ?
            WHERE p.is_active = 1
            GROUP BY p.id
            HAVING (SUM(s.quantity_sold) IS NULL OR SUM(s.quantity_sold) < 5)
            ORDER BY SUM(s.quantity_sold) ASC, p.product_name ASC
            LIMIT ?
        ");

        $result->bind_param("si", $date_from, $limit);
        $result->execute();
        $result = $result->get_result();

        $items = array();
        while ($row = $result->fetch_assoc()) {
            $items[] = $row;
        }

        return $items;
    }

    /**
     * Calculate gross profit margin percentage
     * Gross Profit Margin = (Gross Profit / Total Sales) × 100
     * @param string $date_from
     * @param string $date_to
     * @return float
     */
    public function getGrossProfitMargin($date_from = null, $date_to = null)
    {
        $query = "
            SELECT 
                SUM(total_amount) as total_sales,
                SUM(quantity_sold * cost_price) as total_cost
            FROM sales
            WHERE 1=1
        ";

        $params = array();
        $types = '';

        if (!empty($date_from)) {
            $query .= " AND DATE(sale_date) >= ?";
            $params[] = $date_from;
            $types .= "s";
        }

        if (!empty($date_to)) {
            $query .= " AND DATE(sale_date) <= ?";
            $params[] = $date_to;
            $types .= "s";
        }

        $stmt = $this->db->prepare($query);

        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }

        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();

        $total_sales = floatval($row['total_sales'] ?? 0);
        $total_cost = floatval($row['total_cost'] ?? 0);

        if ($total_sales > 0) {
            $gross_profit = $total_sales - $total_cost;
            return round(($gross_profit / $total_sales) * 100, 2);
        }

        return 0;
    }

    /**
     * Calculate stock turnover rate
     * Stock Turnover Rate = Total Sales Amount / Average Inventory Value
     * OR = Total Quantity Sold / Average Stock Level
     * @param string $date_from
     * @param string $date_to
     * @return float
     */
    public function getStockTurnoverRate($date_from = null, $date_to = null)
    {
        // Get total sales amount in period
        $sales_query = "
            SELECT SUM(total_amount) as total_sales_amount
            FROM sales
            WHERE 1=1
        ";

        $params = array();
        $types = '';

        if (!empty($date_from)) {
            $sales_query .= " AND DATE(sale_date) >= ?";
            $params[] = $date_from;
            $types .= "s";
        }

        if (!empty($date_to)) {
            $sales_query .= " AND DATE(sale_date) <= ?";
            $params[] = $date_to;
            $types .= "s";
        }

        $stmt = $this->db->prepare($sales_query);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $sales_result = $stmt->get_result();
        $sales_row = $sales_result->fetch_assoc();
        $total_sales = floatval($sales_row['total_sales_amount'] ?? 0);

        // Get average inventory value
        $inventory_result = $this->db->query("
            SELECT AVG(cost_price * current_stock) as avg_inventory_value
            FROM products
            WHERE is_active = 1
        ");

        $inventory_row = $inventory_result->fetch_assoc();
        $avg_inventory = floatval($inventory_row['avg_inventory_value'] ?? 0);

        if ($avg_inventory > 0) {
            return round($total_sales / $avg_inventory, 2);
        }

        return 0;
    }

    /**
     * Get overall analytics summary
     * @param string $date_from
     * @param string $date_to
     * @return array
     */
    public function getAnalyticsSummary($date_from = null, $date_to = null)
    {
        return array(
            'total_inventory_value' => $this->getTotalInventoryValue(),
            'gross_profit_margin' => $this->getGrossProfitMargin($date_from, $date_to),
            'stock_turnover_rate' => $this->getStockTurnoverRate($date_from, $date_to),
            'fast_moving_count' => count($this->getFastMovingItems(30, 100)),
            'slow_moving_count' => count($this->getSlowMovingItems(30, 100))
        );
    }

    /**
     * Get inventory composition report
     * Shows value distribution across categories
     * @return array
     */
    public function getInventoryComposition()
    {
        $result = $this->db->query("
            SELECT 
                category,
                COUNT(*) as product_count,
                SUM(current_stock) as total_stock,
                SUM(cost_price * current_stock) as category_value,
                ROUND((SUM(cost_price * current_stock) / (SELECT SUM(cost_price * current_stock) FROM products WHERE is_active = 1) * 100), 2) as percentage
            FROM products
            WHERE is_active = 1
            GROUP BY category
            ORDER BY category_value DESC
        ");

        $composition = array();
        while ($row = $result->fetch_assoc()) {
            $composition[] = $row;
        }

        return $composition;
    }

    /**
     * Get product performance metrics
     * @param int $limit
     * @return array
     */
    public function getProductPerformance($limit = 20)
    {
        $stmt = $this->db->prepare("
            SELECT 
                p.id,
                p.product_code,
                p.product_name,
                p.category,
                p.current_stock,
                p.reorder_level,
                p.cost_price,
                p.selling_price,
                (p.cost_price * p.current_stock) as inventory_value,
                COALESCE(SUM(s.quantity_sold), 0) as lifetime_sales,
                COALESCE(SUM(s.total_amount), 0) as lifetime_revenue,
                COALESCE(SUM(s.quantity_sold * s.cost_price), 0) as lifetime_cost,
                COALESCE(SUM(s.total_amount) - SUM(s.quantity_sold * s.cost_price), 0) as lifetime_profit,
                ((p.selling_price - p.cost_price) / p.cost_price * 100) as markup_percentage,
                MAX(s.sale_date) as last_sale_date
            FROM products p
            LEFT JOIN sales s ON p.id = s.product_id
            WHERE p.is_active = 1
            GROUP BY p.id
            ORDER BY lifetime_profit DESC
            LIMIT ?
        ");

        $stmt->bind_param("i", $limit);
        $stmt->execute();
        $result = $stmt->get_result();

        $performance = array();
        while ($row = $result->fetch_assoc()) {
            $performance[] = $row;
        }

        return $performance;
    }

    /**
     * Get monthly sales trend
     * @param int $months
     * @return array
     */
    public function getMonthlySalesTrend($months = 6)
    {
        $stmt = $this->db->prepare("
            SELECT 
                DATE_FORMAT(sale_date, '%Y-%m') as month,
                COUNT(*) as transaction_count,
                SUM(quantity_sold) as total_quantity,
                SUM(total_amount) as total_sales,
                SUM(quantity_sold * cost_price) as total_cost,
                (SUM(total_amount) - SUM(quantity_sold * cost_price)) as gross_profit
            FROM sales
            WHERE sale_date >= DATE_SUB(CURDATE(), INTERVAL ? MONTH)
            GROUP BY DATE_FORMAT(sale_date, '%Y-%m')
            ORDER BY month DESC
        ");

        $stmt->bind_param("i", $months);
        $stmt->execute();
        $result = $stmt->get_result();

        $trend = array();
        while ($row = $result->fetch_assoc()) {
            $trend[] = $row;
        }

        return $trend;
    }
}
?>
