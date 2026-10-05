# 🚀 Alerts System - Quick Start Guide

## ⚡ 5-Minute Setup

### Step 1: Verify Database Tables
The alerts tables are automatically created when you run `setup.php`. If not already done:

```bash
1. Open http://localhost/inventorysystem/setup.php
2. Scroll down and verify ✓ alerts table created
3. Scroll down and verify ✓ alert_logs table created
```

### Step 2: Navigate to Products Dashboard
```
1. Login to inventory system
2. Visit http://localhost/inventorysystem/products.php
3. You should see "🔔 Alerts" button in header
```

### Step 3: Trigger Alert Creation
Alerts are **automatically created** when:
- Product has expiry date within 4 months, OR
- Product current stock ≤ reorder level

To manually trigger:
```php
// Add this to any PHP page
$alerts->createExpiryAlerts();
$alerts->createReorderAlerts();
```

### Step 4: View Alerts
**Option A: Dashboard Popup**
- If critical alerts exist, red banner appears at top
- Click "View Details" to see popup

**Option B: Alerts Dashboard**
- Click "🔔 Alerts" button in header
- See all active alerts in table view

---

## 🧪 Test Scenarios

### Create a Test Product with Expiry Alert

```
1. Click "📦 Inventory Master" → "Add New Product"
2. Fill in product details:
   - Product Name: "Test Expiry Product"
   - Reorder Level: 10
   - Expiry Date: Select 2 months from now
   - Current Stock: 25
3. Click "Add Product"
4. Go back to Products dashboard
5. You should see red banner: "🔔 1 Critical Alert(s)"
6. Click "View Details" to see alert popup
```

### Create a Test Product with Reorder Alert

```
1. Click "📦 Inventory Master" → "Add New Product"
2. Fill in product details:
   - Product Name: "Test Reorder Product"
   - Reorder Level: 20
   - Current Stock: 5 (less than reorder level)
   - Expiry Date: Leave blank or set far in future
3. Click "Add Product"
4. Go back to Products dashboard
5. After 5 seconds, you should see alert badge update
6. Click "🔔 Alerts" button to see reorder alert
```

---

## 📊 Alert Types Explained

### 🔴 EXPIRED
- **When**: Product expiry date has passed
- **Action**: Remove from stock immediately
- **Severity**: Critical - do not sell

### 🟠 CRITICAL (Stock)
- **When**: Stock ≤ 50% of reorder level
- **Action**: Urgent purchase order needed
- **Severity**: High priority

### 🟡 WARNING
- **When**: Stock ≤ reorder level
- **Action**: Place purchase order soon
- **Severity**: Medium priority

### 🔵 NOTICE (Expiry)
- **When**: 60-120 days until expiry
- **Action**: Monitor and plan for expiry
- **Severity**: Low priority

---

## 🎯 Common Actions

### Acknowledge an Alert
```
1. Go to Alerts Dashboard (Click "🔔 Alerts")
2. Find the alert you want to acknowledge
3. Click "✓ Acknowledge" button
4. Alert status changes to ACKNOWLEDGED
5. Your username is recorded
```

### Resolve an Alert
```
1. After acknowledging, click "✓ Resolve" button
2. Alert status changes to RESOLVED
3. Can be deleted next
```

### Delete an Alert
```
1. Click "✕ Delete" button
2. Confirm deletion in popup
3. Alert removed from active list
4. Logged in alert_logs for history
```

### View Critical Alerts Only
```
1. Go to Alerts Dashboard
2. See "⚠️ CRITICAL ALERT!" banner at top
3. Below that, see all active alerts
4. Critical ones sorted first (all marked with severity badge)
```

---

## 🔧 Configuration

### Enable Email Notifications (Optional)
For production, configure email sending:

```php
// In config/config.php, add:
define('MAIL_ENABLED', true);
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'your-email@gmail.com');
define('SMTP_PASS', 'your-app-password');
```

Then in `classes/Alerts.php`, uncomment email sending:

```php
public function sendEmailAlert($alert_id, $email) {
    // ... existing code ...
    
    // Enable email sending:
    // if (ini_get('sendmail_path') || ini_get('SMTP')) {
    //     mail($email, $subject, $body);
    // }
}
```

### Enable SMS Notifications (Optional)
For production, configure SMS provider:

```php
// Install Twilio: composer require twilio/sdk
// In classes/Alerts.php, add:

use Twilio\Rest\Client;

public function sendSMSAlert($alert_id, $phone_number) {
    $account_sid = 'your-account-sid';
    $auth_token = 'your-auth-token';
    $twilio = new Client($account_sid, $auth_token);
    
    // Send SMS
    $twilio->messages->create($phone_number, [
        "body" => $message
    ]);
}
```

---

## 📱 Dashboard Notifications

### Alert Banner (Top of Page)
```
🔔 2 Critical Alert(s) - Immediate Action Required!
                          [View Details]
```

**Appears when:** Critical alerts exist  
**Click:** "View Details" to open popup

### Alert Popup Modal
```
┌─────────────────────────────────────────┐
│ 🔴 Critical Alerts          [Badge: 2]  │ ✕
├─────────────────────────────────────────┤
│                                         │
│ ❌ PRODUCT HAS EXPIRED!                 │
│ Test Expiry Product (CODE-001)          │
│ Product has expired! Remove from stock. │
│                                         │
│ 🔴 OUT OF STOCK - URGENT REORDER        │
│ Test Reorder Product (CODE-002)         │
│ Current stock is 0 units.               │
│                                         │
├─────────────────────────────────────────┤
│ [📊 View All Alerts]  [Dismiss]        │
└─────────────────────────────────────────┘
```

---

## 📈 Alert Dashboard Features

### Metric Cards (Top)
Shows 4 key numbers:
1. **Total Active Alerts** - Sum of all active + acknowledged
2. **Expiry Alerts** - Count of expiry alerts only
3. **Reorder Alerts** - Count of reorder alerts only
4. **Critical Alerts** - Count of CRITICAL and EXPIRED severity

### Alert Table Columns
| Alert Type | Product Code | Product Name | Stock/Expiry | Days/Level | Severity | Status | Notifications | Actions |
|---|---|---|---|---|---|---|---|---|

### Action Buttons
- **✓ Acknowledge** - Mark as acknowledged by you
- **✓ Resolve** - Mark as resolved
- **✕ Delete** - Remove alert

---

## 🎯 Best Practices

1. **Check Dashboard Daily**
   - Review alerts every morning
   - Take action on critical items first

2. **Acknowledge When Notified**
   - Acknowledge alerts when you see them
   - Helps track who handled what

3. **Resolve When Fixed**
   - Only mark RESOLVED when issue fixed
   - Provides audit trail of actions

4. **Delete Cleared Alerts**
   - After resolving, delete to keep list clean
   - Logged in alert_logs for history

5. **Monitor Critical Alerts**
   - Red banner means immediate action
   - Check those first

---

## ❓ Troubleshooting

### Alerts Not Appearing
**Problem**: I added a low-stock product but don't see alert

**Solution**:
1. Refresh products.php page
2. Go to Alerts Dashboard
3. If still missing, check:
   - Is current_stock ≤ reorder_level?
   - Is product marked is_active = 1?
   - Click browser refresh

### Alert Popup Didn't Show
**Problem**: I see banner but popup won't open

**Solution**:
1. Check JavaScript is enabled in browser
2. Check browser console for errors (F12)
3. Try clicking "View Details" button again
4. Refresh page

### Counter Badge Wrong
**Problem**: Badge shows wrong number

**Solution**:
1. Refresh products.php page
2. Clear browser cache (Ctrl+Shift+Delete)
3. Check if alerts missing products
4. Go to Alerts Dashboard to verify count

### Dismissed Banner Won't Return
**Problem**: I dismissed alert but won't see banner again

**Solution**:
1. That's expected (session behavior)
2. Refresh page to see banner again
3. Or go directly to Alerts Dashboard

---

## 🔗 Quick Links

- **Alerts Dashboard**: `http://localhost/inventorysystem/alerts-dashboard.php`
- **Products Dashboard**: `http://localhost/inventorysystem/products.php`
- **Full Documentation**: `ALERTS_SYSTEM.md` in project root
- **Phase 7 Summary**: `PHASE_7_SUMMARY.md` in project root

---

## 💡 Tips & Tricks

### Tip 1: Keyboard Shortcut
- Press `Ctrl+K` then type "Alerts" to jump to alerts dashboard

### Tip 2: Bookmark Alert Page
- Bookmark alerts-dashboard.php for quick access
- Check daily to manage inventory

### Tip 3: Mobile View
- Works on mobile browsers
- Responsive design adapts to screen size

### Tip 4: Export Alerts
- Go to Alerts Dashboard
- Take screenshot of table
- Or copy/paste data to Excel

### Tip 5: Set Up Email Notifications
- Follow Configuration section above
- Enables email reminders for alerts

---

## 📞 Support

For detailed information, see:
- **Setup Issues**: See `QUICK_START.md`
- **Feature Details**: See `ALERTS_SYSTEM.md`
- **Implementation**: See `PHASE_7_SUMMARY.md`
- **All Features**: See `ARCHITECTURE.md`

---

## ✅ You're Ready!

You now have a fully functional Alerts System that will:
- ✅ Auto-detect expiring products
- ✅ Auto-detect low stock items
- ✅ Display critical alerts prominently
- ✅ Track alert acknowledgment
- ✅ Log all alert actions
- ✅ Support email/SMS notifications (when configured)

**Start using it now by creating test products and triggering alerts!**

---

*Last Updated: March 4, 2026*

