<?php
/**
 * Alerts Class
 * Handles expiry alerts and reorder alerts
 * Manages email, SMS, and popup notifications
 */

class Alerts
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
     * Get products expiring within 4 months
     * @return array
     */
    public function getExpiryAlerts()
    {
        $expiry_threshold = date('Y-m-d', strtotime('+4 months'));
        
        $stmt = $this->db->prepare("
            SELECT 
                p.id,
                p.product_code,
                p.product_name,
                p.current_stock,
                p.expiry_date,
                DATEDIFF(p.expiry_date, CURDATE()) as days_until_expiry,
                CASE 
                    WHEN DATEDIFF(p.expiry_date, CURDATE()) <= 0 THEN 'EXPIRED'
                    WHEN DATEDIFF(p.expiry_date, CURDATE()) <= 30 THEN 'CRITICAL'
                    WHEN DATEDIFF(p.expiry_date, CURDATE()) <= 60 THEN 'WARNING'
                    ELSE 'NOTICE'
                END as severity
            FROM products p
            WHERE p.is_active = 1 
                AND p.expiry_date IS NOT NULL
                AND p.expiry_date <= ?
                AND NOT EXISTS (
                    SELECT 1 FROM alerts a 
                    WHERE a.product_id = p.id 
                    AND a.alert_type = 'EXPIRY' 
                    AND a.alert_status IN ('ACTIVE', 'ACKNOWLEDGED')
                )
            ORDER BY p.expiry_date ASC
        ");

        $stmt->bind_param("s", $expiry_threshold);
        $stmt->execute();
        $result = $stmt->get_result();

        $alerts = array();
        while ($row = $result->fetch_assoc()) {
            $alerts[] = $row;
        }

        $stmt->close();
        return $alerts;
    }

    /**
     * Get products needing reorder (stock <= reorder level)
     * @return array
     */
    public function getReorderAlerts()
    {
        $result = $this->db->query("
            SELECT 
                p.id,
                p.product_code,
                p.product_name,
                p.current_stock,
                p.reorder_level,
                (p.reorder_level - p.current_stock) as units_short,
                CASE 
                    WHEN p.current_stock = 0 THEN 'OUT_OF_STOCK'
                    WHEN p.current_stock <= (p.reorder_level * 0.5) THEN 'CRITICAL'
                    ELSE 'WARNING'
                END as severity
            FROM products p
            WHERE p.is_active = 1 
                AND p.current_stock <= p.reorder_level
                AND NOT EXISTS (
                    SELECT 1 FROM alerts a 
                    WHERE a.product_id = p.id 
                    AND a.alert_type = 'REORDER' 
                    AND a.alert_status IN ('ACTIVE', 'ACKNOWLEDGED')
                )
            ORDER BY (p.reorder_level - p.current_stock) DESC
        ");

        $alerts = array();
        while ($row = $result->fetch_assoc()) {
            $alerts[] = $row;
        }

        return $alerts;
    }

    /**
     * Create expiry alerts for products
     * @return int Count of alerts created
     */
    public function createExpiryAlerts()
    {
        $expiry_alerts = $this->getExpiryAlerts();
        $count = 0;

        foreach ($expiry_alerts as $product) {
            $stmt = $this->db->prepare("
                INSERT INTO alerts (product_id, alert_type, alert_status)
                VALUES (?, 'EXPIRY', 'ACTIVE')
                ON DUPLICATE KEY UPDATE alert_status = 'ACTIVE'
            ");

            $stmt->bind_param("i", $product['id']);
            if ($stmt->execute()) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Create reorder alerts for products
     * @return int Count of alerts created
     */
    public function createReorderAlerts()
    {
        $reorder_alerts = $this->getReorderAlerts();
        $count = 0;

        foreach ($reorder_alerts as $product) {
            $stmt = $this->db->prepare("
                INSERT INTO alerts (product_id, alert_type, alert_status)
                VALUES (?, 'REORDER', 'ACTIVE')
                ON DUPLICATE KEY UPDATE alert_status = 'ACTIVE'
            ");

            $stmt->bind_param("i", $product['id']);
            if ($stmt->execute()) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Get all active alerts
     * @return array
     */
    public function getActiveAlerts()
    {
        $result = $this->db->query("
            SELECT 
                a.id,
                a.product_id,
                a.alert_type,
                a.alert_status,
                a.is_notified,
                a.email_sent,
                a.sms_sent,
                p.product_code,
                p.product_name,
                p.current_stock,
                p.reorder_level,
                p.expiry_date,
                DATEDIFF(p.expiry_date, CURDATE()) as days_until_expiry,
                CASE 
                    WHEN a.alert_type = 'EXPIRY' AND DATEDIFF(p.expiry_date, CURDATE()) <= 0 THEN 'EXPIRED'
                    WHEN a.alert_type = 'EXPIRY' AND DATEDIFF(p.expiry_date, CURDATE()) <= 30 THEN 'CRITICAL'
                    WHEN a.alert_type = 'EXPIRY' AND DATEDIFF(p.expiry_date, CURDATE()) <= 60 THEN 'WARNING'
                    WHEN a.alert_type = 'REORDER' AND p.current_stock = 0 THEN 'OUT_OF_STOCK'
                    WHEN a.alert_type = 'REORDER' AND p.current_stock <= (p.reorder_level * 0.5) THEN 'CRITICAL'
                    ELSE 'WARNING'
                END as severity,
                a.created_at
            FROM alerts a
            JOIN products p ON a.product_id = p.id
            WHERE a.alert_status IN ('ACTIVE', 'ACKNOWLEDGED')
            ORDER BY 
                CASE 
                    WHEN a.alert_type = 'REORDER' AND p.current_stock = 0 THEN 0
                    WHEN a.alert_type = 'EXPIRY' AND DATEDIFF(p.expiry_date, CURDATE()) <= 0 THEN 1
                    WHEN CASE WHEN a.alert_type = 'EXPIRY' THEN DATEDIFF(p.expiry_date, CURDATE()) ELSE (p.reorder_level - p.current_stock) END <= 30 THEN 2
                    ELSE 3
                END,
                a.created_at DESC
        ");

        $alerts = array();
        while ($row = $result->fetch_assoc()) {
            $alerts[] = $row;
        }

        return $alerts;
    }

    /**
     * Send email notification for alert
     * @param int $alert_id
     * @param string $email
     * @return bool
     */
    public function sendEmailAlert($alert_id, $email)
    {
        // Get alert details
        $stmt = $this->db->prepare("
            SELECT a.*, p.product_code, p.product_name, p.current_stock, p.reorder_level, p.expiry_date
            FROM alerts a
            JOIN products p ON a.product_id = p.id
            WHERE a.id = ?
        ");
        $stmt->bind_param("i", $alert_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $alert = $result->fetch_assoc();

        if (!$alert) {
            return false;
        }

        // Compose email message
        $subject = "Alert: ";
        $body = "";

        if ($alert['alert_type'] == 'EXPIRY') {
            $subject .= "Product Expiry Warning - " . $alert['product_name'];
            $days = intval($alert['expiry_date'] ? (strtotime($alert['expiry_date']) - time()) / (24 * 3600) : 0);
            $body = "EXPIRY ALERT\n\n";
            $body .= "Product: " . $alert['product_name'] . " (" . $alert['product_code'] . ")\n";
            $body .= "Expiry Date: " . $alert['expiry_date'] . "\n";
            $body .= "Days Until Expiry: " . $days . " days\n";
            $body .= "Current Stock: " . $alert['current_stock'] . " units\n\n";
            $body .= "ACTION REQUIRED: Please take appropriate action to manage this product before expiry.\n";
        } else if ($alert['alert_type'] == 'REORDER') {
            $subject .= "Stock Reorder Alert - " . $alert['product_name'];
            $body = "REORDER ALERT\n\n";
            $body .= "Product: " . $alert['product_name'] . " (" . $alert['product_code'] . ")\n";
            $body .= "Current Stock: " . $alert['current_stock'] . " units\n";
            $body .= "Reorder Level: " . $alert['reorder_level'] . " units\n";
            $body .= "Units Short: " . ($alert['reorder_level'] - $alert['current_stock']) . " units\n\n";
            $body .= "ACTION REQUIRED: Please place a purchase order to replenish stock.\n";
        }

        // In production, use PHPMailer or similar
        // For now, log the email sending
        $is_delivered = $this->logAlertNotification($alert_id, 'EMAIL', $email, null, $body);

        // Mark email as sent
        $stmt = $this->db->prepare("UPDATE alerts SET email_sent = 1 WHERE id = ?");
        $stmt->bind_param("i", $alert_id);
        $stmt->execute();

        return $is_delivered;
    }

    /**
     * Send SMS notification for alert
     * @param int $alert_id
     * @param string $phone_number
     * @return bool
     */
    public function sendSMSAlert($alert_id, $phone_number)
    {
        // Get alert details
        $stmt = $this->db->prepare("
            SELECT a.*, p.product_code, p.product_name, p.current_stock, p.reorder_level, p.expiry_date
            FROM alerts a
            JOIN products p ON a.product_id = p.id
            WHERE a.id = ?
        ");
        $stmt->bind_param("i", $alert_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $alert = $result->fetch_assoc();

        if (!$alert) {
            return false;
        }

        // Compose SMS message  (keep brief for SMS)
        $message = "";

        if ($alert['alert_type'] == 'EXPIRY') {
            $days = intval($alert['expiry_date'] ? (strtotime($alert['expiry_date']) - time()) / (24 * 3600) : 0);
            $message = "EXPIRY ALERT: " . $alert['product_name'] . " expires in " . $days . " days. Stock: " . $alert['current_stock'] . " units.";
        } else if ($alert['alert_type'] == 'REORDER') {
            $message = "REORDER ALERT: " . $alert['product_name'] . " stock is " . $alert['current_stock'] . " units (reorder level: " . $alert['reorder_level'] . ").";
        }

        // In production, use Twilio or similar SMS provider
        // For now, log the SMS sending
        $is_delivered = $this->logAlertNotification($alert_id, 'SMS', null, $phone_number, $message);

        // Mark SMS as sent
        $stmt = $this->db->prepare("UPDATE alerts SET sms_sent = 1 WHERE id = ?");
        $stmt->bind_param("i", $alert_id);
        $stmt->execute();

        return $is_delivered;
    }

    /**
     * Log alert notification
     * @param int $alert_id
     * @param string $notification_type
     * @param string $email
     * @param string $phone
     * @param string $message
     * @return bool
     */
    private function logAlertNotification($alert_id, $notification_type, $email = null, $phone = null, $message = null)
    {
        $stmt = $this->db->prepare("
            INSERT INTO alert_logs (alert_id, notification_type, recipient_email, recipient_phone, message_content, is_delivered)
            VALUES (?, ?, ?, ?, ?, 1)
        ");

        $is_delivered = true;
        $stmt->bind_param("issss", $alert_id, $notification_type, $email, $phone, $message);
        
        if (!$stmt->execute()) {
            $is_delivered = false;
        }

        return $is_delivered;
    }

    /**
     * Acknowledge an alert
     * @param int $alert_id
     * @param int $user_id
     * @return bool
     */
    public function acknowledgeAlert($alert_id, $user_id)
    {
        $stmt = $this->db->prepare("
            UPDATE alerts 
            SET alert_status = 'ACKNOWLEDGED', 
                acknowledged_by = ?, 
                acknowledged_at = NOW()
            WHERE id = ?
        ");

        $stmt->bind_param("ii", $user_id, $alert_id);
        return $stmt->execute();
    }

    /**
     * Resolve an alert
     * @param int $alert_id
     * @return bool
     */
    public function resolveAlert($alert_id)
    {
        $stmt = $this->db->prepare("
            UPDATE alerts 
            SET alert_status = 'RESOLVED', 
                resolved_at = NOW()
            WHERE id = ?
        ");

        $stmt->bind_param("i", $alert_id);
        return $stmt->execute();
    }

    /**
     * Delete an alert
     * @param int $alert_id
     * @return bool
     */
    public function deleteAlert($alert_id)
    {
        $stmt = $this->db->prepare("DELETE FROM alerts WHERE id = ?");
        $stmt->bind_param("i", $alert_id);
        return $stmt->execute();
    }

    /**
     * Get count of active alerts by type
     * @return array
     */
    public function getAlertCounts()
    {
        $result = $this->db->query("
            SELECT 
                alert_type,
                COUNT(*) as count
            FROM alerts
            WHERE alert_status IN ('ACTIVE', 'ACKNOWLEDGED')
            GROUP BY alert_type
        ");

        $counts = array('EXPIRY' => 0, 'REORDER' => 0, 'TOTAL' => 0);
        
        while ($row = $result->fetch_assoc()) {
            $counts[$row['alert_type']] = intval($row['count']);
        }

        $counts['TOTAL'] = $counts['EXPIRY'] + $counts['REORDER'];

        return $counts;
    }

    /**
     * Get critical alerts (requires immediate action)
     * @return array
     */
    public function getCriticalAlerts()
    {
        $result = $this->db->query("
            SELECT 
                a.id,
                a.product_id,
                a.alert_type,
                p.product_code,
                p.product_name,
                p.current_stock,
                p.reorder_level,
                p.expiry_date,
                DATEDIFF(p.expiry_date, CURDATE()) as days_until_expiry,
                CASE 
                    WHEN a.alert_type = 'EXPIRY' AND DATEDIFF(p.expiry_date, CURDATE()) <= 0 THEN 'EXPIRED'
                    WHEN a.alert_type = 'EXPIRY' AND DATEDIFF(p.expiry_date, CURDATE()) <= 30 THEN 'CRITICAL'
                    WHEN a.alert_type = 'REORDER' AND p.current_stock = 0 THEN 'OUT_OF_STOCK'
                    WHEN a.alert_type = 'REORDER' AND p.current_stock <= (p.reorder_level * 0.5) THEN 'CRITICAL'
                    ELSE 'WARNING'
                END as severity
            FROM alerts a
            JOIN products p ON a.product_id = p.id
            WHERE a.alert_status IN ('ACTIVE', 'ACKNOWLEDGED')
            AND (
                (a.alert_type = 'EXPIRY' AND DATEDIFF(p.expiry_date, CURDATE()) <= 30)
                OR (a.alert_type = 'REORDER' AND p.current_stock <= (p.reorder_level * 0.5))
            )
            ORDER BY a.created_at DESC
            LIMIT 10
        ");

        $alerts = array();
        while ($row = $result->fetch_assoc()) {
            $alerts[] = $row;
        }

        return $alerts;
    }
}
?>
