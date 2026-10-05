<?php
/**
 * Email Service Class
 * Provides email sending functionality using PHPMailer
 * 
 * Installation:
 * 1. Via Composer: composer require phpmailer/phpmailer
 * 2. Manual: Download from https://github.com/PHPMailer/PHPMailer
 * 
 * Configuration in __construct() or use environment variables
 */

class EmailService
{
    private $smtp_host;
    private $smtp_port;
    private $smtp_user;
    private $smtp_pass;
    private $smtp_secure;  // 'tls' or 'ssl'
    private $from_email;
    private $from_name;
    private $mail;          // PHPMailer instance

    /**
     * Constructor
     * Configure SMTP settings here or pass as parameters
     */
    public function __construct($config = [])
    {
        // Default configuration (Gmail SMTP)
        // For Gmail: Enable "Less secure app access" or use App Password
        $this->smtp_host = $config['smtp_host'] ?? getenv('SMTP_HOST') ?? 'smtp.gmail.com';
        $this->smtp_port = $config['smtp_port'] ?? getenv('SMTP_PORT') ?? 587;
        $this->smtp_user = $config['smtp_user'] ?? getenv('SMTP_USER') ?? 'your-email@gmail.com';
        $this->smtp_pass = $config['smtp_pass'] ?? getenv('SMTP_PASS') ?? 'your-app-password';
        $this->smtp_secure = $config['smtp_secure'] ?? getenv('SMTP_SECURE') ?? 'tls';
        $this->from_email = $config['from_email'] ?? getenv('FROM_EMAIL') ?? $this->smtp_user;
        $this->from_name = $config['from_name'] ?? getenv('FROM_NAME') ?? 'Inventory System';

        // Initialize PHPMailer
        $this->initializeMailer();
    }

    /**
     * Initialize PHPMailer instance
     */
    private function initializeMailer()
    {
        try {
            // Check if PHPMailer is available
            if (!class_exists('\PHPMailer\PHPMailer\PHPMailer')) {
                throw new Exception('PHPMailer library not found. Please install via Composer: composer require phpmailer/phpmailer');
            }

            $this->mail = new \PHPMailer\PHPMailer\PHPMailer(true);

            // SMTP Configuration
            $this->mail->isSMTP();
            $this->mail->Host = $this->smtp_host;
            $this->mail->Port = $this->smtp_port;
            $this->mail->SMTPAuth = true;
            $this->mail->Username = $this->smtp_user;
            $this->mail->Password = $this->smtp_pass;
            $this->mail->SMTPSecure = $this->smtp_secure === 'ssl' ? \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS : \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $this->mail->SMTPOptions = [
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true,
                ]
            ];

            // Default sender
            $this->mail->setFrom($this->from_email, $this->from_name);

            // Character set
            $this->mail->CharSet = 'UTF-8';

        } catch (\Exception $e) {
            throw new Exception('Failed to initialize PHPMailer: ' . $e->getMessage());
        }
    }

    /**
     * Send a simple text email
     */
    public function sendTextEmail($to, $subject, $body, $replyTo = null)
    {
        try {
            $this->mail->clearAddresses();
            $this->mail->clearReplyTos();

            $this->mail->addAddress($to);
            if ($replyTo) {
                $this->mail->addReplyTo($replyTo);
            }

            $this->mail->isHTML(false);
            $this->mail->Subject = $subject;
            $this->mail->Body = $body;

            return $this->mail->send();

        } catch (\Exception $e) {
            error_log('Email send error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Send HTML email
     */
    public function sendHtmlEmail($to, $subject, $htmlBody, $altBody = null, $replyTo = null)
    {
        try {
            $this->mail->clearAddresses();
            $this->mail->clearReplyTos();

            $this->mail->addAddress($to);
            if ($replyTo) {
                $this->mail->addReplyTo($replyTo);
            }

            $this->mail->isHTML(true);
            $this->mail->Subject = $subject;
            $this->mail->Body = $htmlBody;

            if ($altBody) {
                $this->mail->AltBody = $altBody;
            }

            return $this->mail->send();

        } catch (\Exception $e) {
            error_log('Email send error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Send email to multiple recipients
     */
    public function sendBulkEmail($recipients, $subject, $htmlBody, $altBody = null)
    {
        $results = [];

        foreach ($recipients as $email) {
            $success = $this->sendHtmlEmail($email, $subject, $htmlBody, $altBody);
            $results[$email] = $success;
        }

        return $results;
    }

    /**
     * Send expiry alert email
     */
    public function sendExpiryAlertEmail($to, $productName, $expiryDate, $quantity, $batchNo = null)
    {
        $subject = "Product Expiry Alert: {$productName}";

        $htmlBody = $this->renderTemplate('expiry-alert', [
            'productName' => $productName,
            'expiryDate' => $expiryDate,
            'quantity' => $quantity,
            'batchNo' => $batchNo
        ]);

        return $this->sendHtmlEmail($to, $subject, $htmlBody);
    }

    /**
     * Send reorder alert email
     */
    public function sendReorderAlertEmail($to, $productName, $currentStock, $reorderLevel, $productCode = null)
    {
        $subject = "Stock Reorder Alert: {$productName}";

        $htmlBody = $this->renderTemplate('reorder-alert', [
            'productName' => $productName,
            'productCode' => $productCode,
            'currentStock' => $currentStock,
            'reorderLevel' => $reorderLevel
        ]);

        return $this->sendHtmlEmail($to, $subject, $htmlBody);
    }

    /**
     * Send sales report email
     */
    public function sendSalesReportEmail($to, $reportData)
    {
        $subject = "Daily Sales Report - " . date('M d, Y');

        $htmlBody = $this->renderTemplate('sales-report', [
            'totalSales' => $reportData['totalSales'] ?? 0,
            'transactionCount' => $reportData['transactionCount'] ?? 0,
            'totalProfit' => $reportData['totalProfit'] ?? 0,
            'topProduct' => $reportData['topProduct'] ?? 'N/A'
        ]);

        return $this->sendHtmlEmail($to, $subject, $htmlBody);
    }

    /**
     * Render email template
     */
    private function renderTemplate($templateName, $data = [])
    {
        $templatesDir = dirname(__FILE__) . '/../../email-templates/';

        if (!file_exists($templatesDir . $templateName . '.html')) {
            return $this->renderFallbackTemplate($templateName, $data);
        }

        ob_start();
        include $templatesDir . $templateName . '.html';
        return ob_get_clean();
    }

    /**
     * Render fallback template (inline HTML)
     */
    private function renderFallbackTemplate($templateName, $data)
    {
        $html = '<html><head><style>';
        $html .= 'body { font-family: Arial, sans-serif; background: #f5f5f5; }';
        $html .= '.container { max-width: 600px; margin: 20px auto; background: white; padding: 20px; border-radius: 8px; }';
        $html .= '.header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; border-radius: 4px; margin-bottom: 20px; }';
        $html .= '.footer { margin-top: 20px; padding-top: 20px; border-top: 1px solid #eee; font-size: 12px; color: #666; }';
        $html .= 'h2 { color: #333; }';
        $html .= 'p { color: #555; line-height: 1.6; }';
        $html .= '.alert { background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; border-radius: 4px; margin: 15px 0; }';
        $html .= '.success { background: #d4edda; border-left: 4px solid #28a745; padding: 15px; border-radius: 4px; margin: 15px 0; }';
        $html .= '.btn { display: inline-block; background: #667eea; color: white; padding: 10px 20px; border-radius: 4px; text-decoration: none; margin: 10px 0; }';
        $html .= '</style></head><body>';
        $html .= '<div class="container">';
        $html .= '<div class="header"><h2>Inventory System Notification</h2></div>';

        // Template-specific content
        if ($templateName === 'expiry-alert') {
            $html .= '<h2>⚠️ Product Expiry Alert</h2>';
            $html .= '<p>Dear User,</p>';
            $html .= '<p>The following product is expiring soon:</p>';
            $html .= '<div class="alert">';
            $html .= '<p><strong>Product:</strong> ' . htmlspecialchars($data['productName']) . '</p>';
            $html .= '<p><strong>Expiry Date:</strong> ' . htmlspecialchars($data['expiryDate']) . '</p>';
            $html .= '<p><strong>Quantity:</strong> ' . htmlspecialchars($data['quantity']) . ' units</p>';
            if ($data['batchNo']) {
                $html .= '<p><strong>Batch No:</strong> ' . htmlspecialchars($data['batchNo']) . '</p>';
            }
            $html .= '</div>';
            $html .= '<p>Please take appropriate action to manage this expired inventory.</p>';

        } elseif ($templateName === 'reorder-alert') {
            $html .= '<h2>📦 Stock Reorder Alert</h2>';
            $html .= '<p>Dear User,</p>';
            $html .= '<p>The following product is running low on stock:</p>';
            $html .= '<div class="alert">';
            $html .= '<p><strong>Product:</strong> ' . htmlspecialchars($data['productName']) . '</p>';
            if ($data['productCode']) {
                $html .= '<p><strong>Product Code:</strong> ' . htmlspecialchars($data['productCode']) . '</p>';
            }
            $html .= '<p><strong>Current Stock:</strong> ' . htmlspecialchars($data['currentStock']) . ' units</p>';
            $html .= '<p><strong>Reorder Level:</strong> ' . htmlspecialchars($data['reorderLevel']) . ' units</p>';
            $html .= '</div>';
            $html .= '<p>Please place a purchase order to replenish this item.</p>';

        } elseif ($templateName === 'sales-report') {
            $html .= '<h2>📊 Daily Sales Report</h2>';
            $html .= '<p>Dear User,</p>';
            $html .= '<p>Here is today\'s sales summary:</p>';
            $html .= '<div class="success">';
            $html .= '<p><strong>Total Sales:</strong> $' . number_format($data['totalSales'], 2) . '</p>';
            $html .= '<p><strong>Transactions:</strong> ' . htmlspecialchars($data['transactionCount']) . '</p>';
            $html .= '<p><strong>Gross Profit:</strong> $' . number_format($data['totalProfit'], 2) . '</p>';
            if ($data['topProduct'] !== 'N/A') {
                $html .= '<p><strong>Top Product:</strong> ' . htmlspecialchars($data['topProduct']) . '</p>';
            }
            $html .= '</div>';

        } else {
            $html .= '<h2>System Notification</h2>';
            $html .= '<p>Hello,</p>';
            $html .= '<p>You have received a notification from the Inventory System.</p>';
        }

        $html .= '<p style="margin-top: 30px;">Best regards,<br/>Inventory Management System</p>';
        $html .= '<div class="footer">';
        $html .= '<p>This is an automated email. Please do not reply to this address.</p>';
        $html .= '<p>© ' . date('Y') . ' Inventory System. All rights reserved.</p>';
        $html .= '</div>';
        $html .= '</div></body></html>';

        return $html;
    }

    /**
     * Check SMTP connection
     */
    public function testConnection()
    {
        try {
            $this->mail->smtpConnect();
            $this->mail->smtpClose();
            return true;
        } catch (\Exception $e) {
            error_log('SMTP connection test failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get last error
     */
    public function getLastError()
    {
        return $this->mail ? $this->mail->ErrorInfo : 'PHPMailer not initialized';
    }

    /**
     * Set custom SMTP configuration
     */
    public function setSmtpConfig($host, $port, $user, $pass, $secure = 'tls')
    {
        $this->smtp_host = $host;
        $this->smtp_port = $port;
        $this->smtp_user = $user;
        $this->smtp_pass = $pass;
        $this->smtp_secure = $secure;

        // Reinitialize with new config
        $this->initializeMailer();
    }
}
?>
