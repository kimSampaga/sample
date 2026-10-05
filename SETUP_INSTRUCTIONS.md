# Phase 8 Setup Instructions
## Bootstrap 5 + Chart.js + Email System Setup Guide

**Last Updated**: 2024
**Version**: 2.0 (Complete)
**Estimated Setup Time**: 10-15 minutes

---

## Quick Start (5 Minutes)

### Step 1: Verify Bootstrap 5 is Loaded
The system uses CDN-based Bootstrap 5, so NO installation is needed!

**Check in browser**:
1. Go to http://localhost/inventorysystem/dashboard.php
2. You should see:
   - Left sidebar with menu items
   - Top navbar with user info
   - Colored stat cards
   - Responsive layout

If CSS is missing, check browser console (F12) for CDN errors.

### Step 2: Test Chart.js
1. Navigate to http://localhost/inventorysystem/analytics-dashboard.php
2. You should see charts rendering:
   - Line chart (sales trend)
   - Doughnut chart (inventory composition)

If charts don't show, check console for JavaScript errors.

### Step 3: (Optional) Setup Email - Skip if Not Needed
For alerts to send emails, install PHPMailer:
```bash
cd inventorysystem
composer require phpmailer/phpmailer
```

---

## Full Setup Guide

### Part 1: Bootstrap 5 Layout System (Already Done ✅)

**What was setup**:
- ✅ layout-header.php - Sidebar + Top navbar
- ✅ layout-footer.php - JavaScript utilities
- ✅ Custom responsive CSS (inline in header)
- ✅ Font Awesome icons (CDN)
- ✅ Bootstrap 5 (CDN)

**To use in your pages**:
```php
<?php
// At the TOP of each page (after DB includes)
$page_title = 'Your Page Title';
$active_menu = 'your-menu-item'; // From: dashboard, inventory, stock, purchase, sales, analytics, alerts

include 'includes/layout-header.php';
?>

<!-- Your page content here -->

<?php include 'includes/layout-footer.php'; ?>
```

**Active menu values** (for highlighting):
- `dashboard` - Dashboard page
- `inventory` - Products/Inventory Master
- `stock` - Stock Movement
- `purchase` - Purchase Record
- `sales` - Sales Record
- `analytics` - Analytics Dashboard
- `alerts` - Alerts Dashboard

---

### Part 2: Chart.js Setup (Already Done ✅)

**What was setup**:
- ✅ Chart.js 4.4.0 CDN (loaded in layout-header.php)
- ✅ ChartUtils class for data preparation
- ✅ Dashboard with charts
- ✅ Analytics with charts

**Charts implemented**:
1. **Inventory Composition** (Doughnut chart)
2. **Monthly Sales Trend** (Line chart)

**To add charts to new pages**:

#### Example: Add a Line Chart
```html
<!-- Add canvas element -->
<div class="chart-container">
    <h5>Sales by Month</h5>
    <canvas id="myChart"></canvas>
</div>

<script>
    const ctx = document.getElementById('myChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
            datasets: [{
                label: 'Sales',
                data: [1000, 1200, 1500, 1800, 1600, 1900],
                borderColor: '#667eea',
                backgroundColor: 'transparent',
                borderWidth: 2,
                tension: 0.4,
                pointRadius: 5,
                fill: false
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom' }
            },
            scales: {
                y: { beginAtZero: true }
            }
        }
    });
</script>
```

#### Using ChartUtils Class
```php
<?php
require_once 'classes/ChartUtils.php';

// Prepare data
$labels = ['Clothing', 'Electronics', 'Food', 'Books'];
$data = [150, 200, 300, 100];

// Get configuration
$chartConfig = ChartUtils::preparePieChartData($labels, $data, 'doughnut');
?>

<canvas id="myChart"></canvas>
<script>
    const config = <?php echo json_encode($chartConfig); ?>;
    const options = <?php echo json_encode(ChartUtils::getCommonOptions('doughnut')); ?>;
    
    new Chart(document.getElementById('myChart'), {
        type: config.type,
        data: config.data,
        options: options
    });
</script>
```

---

### Part 3: Email System Setup (PHPMailer)

#### 3A. Install PHPMailer

**Option 1: Composer (Recommended)**
```bash
cd c:\xampp\htdocs\inventorysystem
composer require phpmailer/phpmailer
```

**Option 2: Manual Download**
1. Go to https://github.com/PHPMailer/PHPMailer/releases
2. Download latest ZIP
3. Extract to: `inventorysystem/vendor/PHPMailer/`
4. In your code, add:
```php
require_once 'vendor/autoload.php';
```

**Option 3: Check if Installed**
```bash
cd inventorysystem
composer show | grep phpmailer
```

#### 3B. Configure SMTP Settings

**Method 1: Environment Variables (Recommended for Security)**

Create `.env` file in inventorysystem root:
```
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USER=your-email@gmail.com
SMTP_PASS=your-app-password
SMTP_SECURE=tls
FROM_EMAIL=your-email@gmail.com
FROM_NAME=Inventory System
```

Then load in config/database.php:
```php
// Load environment variables
if (file_exists(__DIR__ . '/../.env')) {
    $config = parse_ini_file(__DIR__ . '/../.env');
    foreach ($config as $key => $value) {
        putenv("$key=$value");
    }
}
```

**Method 2: Pass Configuration to Class**

```php
require_once 'classes/EmailService.php';

$emailService = new EmailService([
    'smtp_host' => 'smtp.gmail.com',
    'smtp_port' => 587,
    'smtp_user' => 'your-email@gmail.com',
    'smtp_pass' => 'your-app-password',
    'smtp_secure' => 'tls',
    'from_email' => 'your-email@gmail.com',
    'from_name' => 'Inventory System'
]);
```

#### 3C: Gmail Setup (Free)

Gmail requires an "App Password" for SMTP. Steps:

1. **Enable 2-Step Verification**:
   - Go to https://myaccount.google.com/security
   - Click "2-Step Verification"
   - Follow the wizard

2. **Create App Password**:
   - Go to https://myaccount.google.com/apppasswords
   - Select "Mail" and "Windows Computer"
   - Google generates a 16-character password
   - Use this password in `SMTP_PASS`

3. **Test Connection**:
```php
$emailService = new EmailService([
    'smtp_host' => 'smtp.gmail.com',
    'smtp_port' => 587,
    'smtp_user' => 'your-email@gmail.com',
    'smtp_pass' => 'xxxx xxxx xxxx xxxx', // 16-char app password
    'smtp_secure' => 'tls'
]);

if ($emailService->testConnection()) {
    echo "✅ Gmail SMTP connected successfully!";
} else {
    echo "❌ Failed: " . $emailService->getLastError();
}
```

#### 3D: Test Sending Email

```php
require_once 'classes/EmailService.php';

$emailService = new EmailService();

$success = $emailService->sendExpiryAlertEmail(
    'your-email@gmail.com',  // Send to your email
    'Test Product ABC-123',
    '2024-03-15',
    50
);

if ($success) {
    echo "✅ Email sent successfully!";
} else {
    echo "❌ Failed to send: " . $emailService->getLastError();
}
```

---

## Configuration Examples

### Gmail Configuration
```php
[
    'smtp_host' => 'smtp.gmail.com',
    'smtp_port' => 587,
    'smtp_user' => 'your-email@gmail.com',
    'smtp_pass' => 'xxxx xxxx xxxx xxxx', // App password
    'smtp_secure' => 'tls'
]
```

### Office 365 / Outlook Configuration
```php
[
    'smtp_host' => 'smtp.office365.com',
    'smtp_port' => 587,
    'smtp_user' => 'your-email@company.com',
    'smtp_pass' => 'your-password',
    'smtp_secure' => 'tls'
]
```

### SendGrid Configuration
```php
[
    'smtp_host' => 'smtp.sendgrid.net',
    'smtp_port' => 587,
    'smtp_user' => 'apikey',
    'smtp_pass' => 'SG.xxxxxxxxxxxxxxxxxxxxx',
    'smtp_secure' => 'tls'
]
```

### Local Testing (Mailhog)
```php
[
    'smtp_host' => 'localhost',
    'smtp_port' => 1025,
    'smtp_user' => '', // No auth needed
    'smtp_pass' => '',
    'smtp_secure' => ''
]
```

---

## Email Usage Examples

### Send Expiry Alert
```php
$emailService->sendExpiryAlertEmail(
    'manager@company.com',
    'Milk (Product-001)',
    '2024-03-15',
    50,
    'BATCH-2024-001'
);
```

### Send Reorder Alert
```php
$emailService->sendReorderAlertEmail(
    'purchasing@company.com', 
    'Rice 1kg',
    5,     // Current stock
    20,    // Reorder level
    'RICE-1KG'
);
```

### Send Custom HTML Email
```php
$html = '<h1>Sales Report</h1>';
$html .= '<p>Total: $5,234.50</p>';
$html .= '<p>Transactions: 42</p>';

$emailService->sendHtmlEmail(
    'admin@company.com',
    'Daily Report - ' . date('M d, Y'),
    $html
);
```

### Send to Multiple Recipients
```php
$recipients = [
    'admin@company.com',
    'manager@company.com',
    'supervisor@company.com'
];

$results = $emailService->sendBulkEmail(
    $recipients,
    'Important Notification',
    '<h1>System Update</h1>...'
);

// Check results
foreach ($results as $email => $success) {
    echo $email . ': ' . ($success ? '✅' : '❌');
}
```

---

## Troubleshooting

### Issue: "PHPMailer library not found"
**Solution**:
```bash
cd inventorysystem
composer require phpmailer/phpmailer
```

### Issue: Charts not showing
**Solution**:
1. Check browser console (F12)
2. Verify Chart.js CDN is loaded:
   - Look for: `https://cdn.jsdelivr.net/npm/chart.js@4.4.0`
3. Check if you have internet connection (CDN requires it)
4. Clear browser cache (Ctrl+Shift+Del)

### Issue: "SMTP connection failed"
**Solutions**:
1. Check SMTP credentials are correct
2. Verify SMTP server is available:
   ```bash
   ping smtp.gmail.com
   ```
3. Check firewall isn't blocking port 587
4. Enable "Less secure app access" (Gmail only):
   - https://myaccount.google.com/lesssecureapps
5. Check app password (Gmail) has correct format

### Issue: "SMTP Error: Could not authenticate"
**Solutions**:
1. Gmail: Make sure you're using App Password (16 chars), not regular password
2. Office 365: Your full email address must be username
3. Test with a simple telnet command:
   ```bash
   telnet smtp.gmail.com 587
   ```

### Issue: Sidebar not showing
**Solution**:
1. Make sure you included layout-header.php:
   ```php
   include 'includes/layout-header.php';
   ```
2. Check all three files exist:
   - includes/layout-header.php ✓
   - includes/layout-footer.php ✓
   - includes/layout-header.php includes Bootstrap CDN ✓
3. Clear browser cache

### Issue: Mobile layout broken
**Solution**:
1. Make sure viewport meta tag is in layout-header.php:
   ```html
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   ```
2. Test with actual mobile device or DevTools (F12)
3. Check responsive design kicks in at 992px width

---

## Checklist for Full Setup

- [ ] Bootstrap layout renders correctly
- [ ] Sidebar appears on desktop, hides on mobile
- [ ] Top navbar displays with user info
- [ ] Charts render on dashboard
- [ ] Charts render on analytics
- [ ] PHPMailer installed (or marked as optional)
- [ ] SMTP configuration set up
- [ ] Test email sent successfully
- [ ] All pages using layout-header and layout-footer
- [ ] Active menu highlighting works
- [ ] Color scheme consistent across pages

---

## File Verification

Check that these files exist:

```
inventorysystem/
├── includes/
│   ├── layout-header.php       ✓ (580 lines)
│   ├── layout-footer.php       ✓ (150 lines)
│   └── alert-popup.php         ✓ (existing)
├── api/
│   └── get-alert-count.php     ✓ (25 lines)
├── classes/
│   ├── ChartUtils.php          ✓ (280 lines)
│   ├── EmailService.php        ✓ (350 lines)
│   ├── Product.php             ✓ (existing)
│   ├── Analytics.php           ✓ (existing)
│   ├── Alerts.php              ✓ (existing)
│   └── ... (others)
├── dashboard.php               ✓ (modernized)
├── analytics-dashboard.php     ✓ (modernized)
├── PHASE_8_WAVE_1.md           ✓ (documentation)
├── PHASE_8_WAVE_2.md           ✓ (documentation)
└── .env                        (create this for email config)
```

---

## Getting Help

### Bootstrap 5 Documentation
https://getbootstrap.com/docs/5.3/

### Chart.js Documentation
https://www.chartjs.org/docs/latest/

### PHPMailer Documentation
https://github.com/PHPMailer/PHPMailer/blob/master/README.md

### Troubleshooting Email
https://github.com/PHPMailer/PHPMailer/wiki/Troubleshooting

---

## Next Steps

1. ✅ Complete current setup
2. ⏳ Update remaining pages with new layout
3. ⏳ Integrate email into alerts system
4. ⏳ Add more charts to reports
5. ⏳ Test all features thoroughly

---

**Setup Version**: 2.0
**Last Updated**: 2024
**Status**: Ready for Production
**Support**: See troubleshooting section above
