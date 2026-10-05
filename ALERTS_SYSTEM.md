# 🔔 Alerts System - Phase 7

## Overview

The Alerts System is a comprehensive notification framework that automatically detects critical inventory situations and notifies users through multiple channels: email, SMS, and dashboard popups.

## Features Implemented

### 1. **Expiry Alert Detection**
- ✅ Automatically detects products expiring within 4 months
- ✅ Categorizes severity (EXPIRED, CRITICAL, WARNING, NOTICE)
- ✅ Tracks days until expiry
- ✅ Prevents duplicate alerts for same product

### 2. **Reorder Alert Detection**
- ✅ Automatically detects products with stock ≤ reorder level
- ✅ Categorizes by severity (OUT_OF_STOCK, CRITICAL, WARNING)
- ✅ Calculates units short
- ✅ Prevents duplicate alerts for same product

### 3. **Notification Channels**

#### Email Notifications
- Detailed product information
- Specific action recommendations
- Formatted message templates
- Delivery tracking

#### SMS Notifications
- Concise alert messages
- Product code and status
- Stock/expiry details
- Ready for Twilio/SMS provider integration

#### Dashboard Popups
- Real-time critical alert display
- Visual severity indicators
- Auto-trigger on page load
- Dismiss and action buttons

### 4. **Alert Management**
- ✅ View all active alerts on dashboard
- ✅ Acknowledge alerts
- ✅ Resolve alerts
- ✅ Delete alerts
- ✅ Alert status tracking (ACTIVE, ACKNOWLEDGED, RESOLVED)

### 5. **Critical Alert Highlighting**
- ✅ Separate critical alerts view
- ✅ Badge counter on navigation
- ✅ Notification banner at top of dashboard
- ✅ Auto-popup for urgent items

## Database Schema

### `alerts` Table
```sql
CREATE TABLE alerts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    alert_type ENUM('EXPIRY', 'REORDER'),
    alert_status ENUM('ACTIVE', 'ACKNOWLEDGED', 'RESOLVED'),
    is_notified BOOLEAN DEFAULT 0,
    email_sent BOOLEAN DEFAULT 0,
    sms_sent BOOLEAN DEFAULT 0,
    acknowledged_by INT,
    acknowledged_at TIMESTAMP NULL,
    resolved_at TIMESTAMP NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id),
    FOREIGN KEY (acknowledged_by) REFERENCES users(id)
);
```

### `alert_logs` Table
```sql
CREATE TABLE alert_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    alert_id INT NOT NULL,
    notification_type ENUM('EMAIL', 'SMS', 'POPUP'),
    recipient_email VARCHAR(100),
    recipient_phone VARCHAR(20),
    message_content TEXT,
    is_delivered BOOLEAN DEFAULT 0,
    delivery_error TEXT,
    created_at TIMESTAMP,
    FOREIGN KEY (alert_id) REFERENCES alerts(id)
);
```

## Class: Alerts

Located in: `classes/Alerts.php`

### Key Methods

#### Alert Detection
- `getExpiryAlerts()` - Get products expiring within 4 months
- `getReorderAlerts()` - Get products needing reorder
- `createExpiryAlerts()` - Create alert records for expiring products
- `createReorderAlerts()` - Create alert records for low stock

#### Alert Management
- `getActiveAlerts()` - Get all active and acknowledged alerts
- `getCriticalAlerts()` - Get only critical severity alerts
- `getAlertCounts()` - Get count by alert type
- `acknowledgeAlert($alert_id, $user_id)` - Mark alert as acknowledged
- `resolveAlert($alert_id)` - Mark alert as resolved
- `deleteAlert($alert_id)` - Delete alert record

#### Notifications
- `sendEmailAlert($alert_id, $email)` - Send email notification
- `sendSMSAlert($alert_id, $phone_number)` - Send SMS notification
- `logAlertNotification()` - Log notification sending

## Files Created/Modified

### New Files
1. **`classes/Alerts.php`** (390 lines)
   - Complete alerts functionality
   - Multi-channel notification support
   - Alert lifecycle management

2. **`alerts-dashboard.php`** (450 lines)
   - Central alerts management interface
   - Alert status display
   - Action buttons (acknowledge/resolve/delete)
   - Severity color coding
   - Critical alerts highlighting

3. **`includes/alert-popup.php`** (300 lines)
   - Alert popup component
   - Real-time notification display
   - Inline alerts in dashboard
   - Auto-trigger for critical items

### Modified Files
1. **`database/schema.sql`**
   - Added `alerts` table
   - Added `alert_logs` table

2. **`products.php`**
   - Added Alerts class include
   - Added alert popup component
   - Added alerts button with counter badge
   - Auto-trigger alert creation

## Usage Guide

### For System Administrators

#### Viewing Active Alerts
1. Click "🔔 Alerts" button in products dashboard
2. See all active, acknowledged, and resolved alerts
3. View alert severity and product details
4. Take recommended actions

#### Acknowledging Alerts
1. Click "✓ Acknowledge" button on alert
2. Alert marked as acknowledged by current user
3. Timestamp recorded for audit trail
4. Alert remains visible but status changes

#### Resolving Alerts
1. Click "✓ Resolve" button on acknowledged alert
2. Alert status changes to RESOLVED
3. Timestamp recorded
4. Alert can be deleted or archived

#### Deleting Alerts
1. Click "✕ Delete" button on alert
2. Confirm deletion
3. Alert removed from active list (logged in alert_logs)

### For Developers

#### Creating Alerts Programmatically
```php
$alerts = new Alerts($db);

// Create expiry alerts
$alerts->createExpiryAlerts();

// Create reorder alerts
$alerts->createReorderAlerts();

// Get alerts
$active_alerts = $alerts->getActiveAlerts();
$critical_alerts = $alerts->getCriticalAlerts();
```

#### Sending Notifications
```php
// Send email alert
$alerts->sendEmailAlert($alert_id, 'admin@example.com');

// Send SMS alert
$alerts->sendSMSAlert($alert_id, '+1234567890');
```

#### Alert Status Management
```php
// Acknowledge alert
$alerts->acknowledgeAlert($alert_id, $user_id);

// Resolve alert
$alerts->resolveAlert($alert_id);

// Delete alert
$alerts->deleteAlert($alert_id);
```

## Integration with XAMPP

### Email Configuration
For production, configure email sending:

```php
// Install PHPMailer or SwiftMailer
// composer require phpmailer/phpmailer

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$mail = new PHPMailer(true);
$mail->Host = 'your-smtp-server.com';
$mail->Username = 'your-email@example.com';
$mail->Password = 'your-password';
$mail->send();
```

### SMS Configuration
For production, configure SMS provider:

```php
// Install Twilio SDK
// composer require twilio/sdk

$twilio = new Twilio\Rest\Client($account_sid, $auth_token);
$message = $twilio->messages->create(
    $phone_number,
    array("body" => $sms_message)
);
```

## Alert Severity Levels

### Expiry Alerts
| Severity | Condition | Action |
|----------|-----------|--------|
| EXPIRED | Days until expiry ≤ 0 | Remove from stock immediately |
| CRITICAL | Days until expiry ≤ 30 | Plan clearance or promotion |
| WARNING | Days until expiry ≤ 60 | Set reminder for action |
| NOTICE | Days until expiry ≤ 120 | Monitor for upcoming expiry |

### Reorder Alerts
| Severity | Condition | Action |
|----------|-----------|--------|
| OUT_OF_STOCK | Current stock = 0 | Place purchase order immediately |
| CRITICAL | Current stock ≤ 50% of reorder level | Urgent reorder needed |
| WARNING | Current stock ≤ reorder level | Standard reorder process |

## Testing the Alerts System

### Test 1: Expiry Alert Creation
1. Add product with expiry date 4 months from today
2. Navigate to alerts dashboard
3. Verify expiry alert appears
4. Check severity level matches expected value

### Test 2: Reorder Alert Creation
1. Add product with reorder_level = 50
2. Reduce current_stock to 40
3. Navigate to alerts dashboard
4. Verify reorder alert appears

### Test 3: Alert Acknowledgment
1. Click "Acknowledge" on active alert
2. Verify alert status changes to ACKNOWLEDGED
3. Check acknowledged_by user is recorded
4. Check acknowledged_at timestamp is set

### Test 4: Alert Resolution
1. Click "Resolve" on acknowledged alert
2. Verify alert status changes to RESOLVED
3. Check resolved_at timestamp is set

### Test 5: Notification Panel
1. Navigate to products dashboard
2. If critical alerts exist, notification banner appears
3. Click "View Details" button
4. Verify alert popup modal displays
5. Verify "View All Alerts" link works

### Test 6: Alert Counter Badge
1. Navigate to products dashboard
2. Check "🔔 Alerts" button
3. If alerts exist, badge shows count
4. Badge disappears when count = 0

## Performance Optimization

### Indexes Created
```sql
CREATE INDEX idx_product_id ON alerts(product_id);
CREATE INDEX idx_alert_type ON alerts(alert_type);
CREATE INDEX idx_alert_status ON alerts(alert_status);
CREATE INDEX idx_is_notified ON alerts(is_notified);
CREATE INDEX idx_created_at ON alerts(created_at);
CREATE INDEX idx_product_type ON alerts(product_id, alert_type);
```

### Query Optimization
- Alerts checked via indexed queries
- Duplicate alerts prevented with WHERE NOT EXISTS
- Efficient sorting by severity
- Pagination ready for large datasets

## Future Enhancements

1. **Scheduled Alert Checks**
   - Cron job to run alert checks hourly
   - Automated email/SMS sending
   - Batch notification processing

2. **User Preferences**
   - Per-user alert settings
   - Alert type preferences
   - Notification channel preferences
   - Quiet hours configuration

3. **Advanced Analytics**
   - Alert trend analysis
   - Alert frequency by product
   - Time-to-resolution metrics
   - Alert effectiveness reports

4. **Mobile Notifications**
   - Push notifications via Firebase
   - Mobile app integration
   - Real-time updates

5. **Integration Hooks**
   - Slack/Discord notifications
   - Webhook support
   - Custom notification handlers
   - Integration with ERP systems

## Security Considerations

✅ **Implemented**
- All queries use prepared statements (SQL injection prevention)
- Alert data validated before insertion
- User authentication required for alert management
- Activity logging for all alert actions
- CSRF tokens on all forms
- Role-based access control

## Troubleshooting

### Alerts Not Appearing
1. Check if `alerts` and `alert_logs` tables exist
2. Verify products have expiry dates or low stock
3. Run `$alerts->createExpiryAlerts()` and `createReorderAlerts()`
4. Check alert_logs for error messages

### Notifications Not Sending
1. Verify email/SMS configuration is set
2. Check alert_logs for delivery status
3. Verify recipient email/phone is valid
4. Test email/SMS endpoint separately

### Popup Not Showing
1. Check if critical alerts exist
2. Verify JavaScript is enabled
3. Check browser console for errors
4. Clear browser cache and reload

## API Reference

### Alerts Class Constructor
```php
$alerts = new Alerts($database_connection);
```

### Method: getExpiryAlerts()
Returns: Array of products expiring within 4 months
```php
$expiring = $alerts->getExpiryAlerts();
// Returns: array with id, product_code, product_name, expiry_date, days_until_expiry, severity
```

### Method: getActiveAlerts()
Returns: Array of all active and acknowledged alerts
```php
$active = $alerts->getActiveAlerts();
// Returns: array with alert details, product info, severity, notification status
```

### Method: getCriticalAlerts()
Returns: Array of critical severity alerts only
```php
$critical = $alerts->getCriticalAlerts();
// Returns: array limited to critical and expired alerts
```

## Glossary

- **Alert**: A notification system alerting about inventory issue
- **Expiry Alert**: Alerts for products expiring within 4 months
- **Reorder Alert**: Alerts for products with low stock
- **Severity**: Level of urgency (EXPIRED, OUT_OF_STOCK, CRITICAL, WARNING, NOTICE)
- **Status**: Alert lifecycle state (ACTIVE, ACKNOWLEDGED, RESOLVED)
- **Notification Log**: Record of sent notifications (email, SMS, popup)

---

**Version**: 1.0  
**Status**: ✅ Complete and tested  
**Last Updated**: March 4, 2026

