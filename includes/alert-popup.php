<?php
/**
 * Alert Popup Component
 * Displays critical alerts as a modal/popup notification
 * Include this in products.php dashboard
 */

// This file should be included at the top of products.php
// Include the Alerts class if not already included
if (!class_exists('Alerts')) {
    require_once 'classes/Alerts.php';
}

// Get alerts instance (assumes $conn is already defined)
$alerts_instance = new Alerts($conn);

// Get critical alerts
$critical_alerts_list = $alerts_instance->getCriticalAlerts();
$alert_counts_data = $alerts_instance->getAlertCounts();

// Auto-create alerts for expiring and reordered items
$alerts_instance->createExpiryAlerts();
$alerts_instance->createReorderAlerts();
?>

<!-- Alert Popup CSS -->
<style>
    .alert-popup-container {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.5);
        display: none;
        align-items: center;
        justify-content: center;
        z-index: 10000;
    }

    .alert-popup-container.active {
        display: flex;
    }

    .alert-popup {
        background: white;
        border-radius: 8px;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
        max-width: 600px;
        width: 90%;
        max-height: 80vh;
        overflow-y: auto;
        animation: slideDown 0.3s ease-out;
    }

    @keyframes slideDown {
        from {
            transform: translateY(-30px);
            opacity: 0;
        }
        to {
            transform: translateY(0);
            opacity: 1;
        }
    }

    .alert-popup-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 20px;
        border-radius: 8px 8px 0 0;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .alert-popup-header h2 {
        font-size: 18px;
        margin: 0;
    }

    .alert-popup-close {
        background: none;
        border: none;
        color: white;
        font-size: 24px;
        cursor: pointer;
        padding: 0;
        width: 30px;
        height: 30px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .alert-popup-close:hover {
        opacity: 0.8;
    }

    .alert-popup-content {
        padding: 20px;
    }

    .alert-item {
        padding: 15px;
        margin-bottom: 12px;
        border-radius: 6px;
        border-left: 5px solid;
    }

    .alert-item.expired {
        background: #fee2e2;
        border-left-color: #dc2626;
    }

    .alert-item.critical {
        background: #fed7aa;
        border-left-color: #f59e0b;
    }

    .alert-item.warning {
        background: #fef3c7;
        border-left-color: #eab308;
    }

    .alert-item-title {
        font-weight: 600;
        color: #1f2937;
        margin-bottom: 5px;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .alert-item.expired .alert-item-title {
        color: #992d2d;
    }

    .alert-item.critical .alert-item-title {
        color: #9a3412;
    }

    .alert-item.warning .alert-item-title {
        color: #854d0e;
    }

    .alert-item-message {
        font-size: 13px;
        margin-bottom: 8px;
        line-height: 1.5;
    }

    .alert-item.expired .alert-item-message {
        color: #7f1d1d;
    }

    .alert-item.critical .alert-item-message {
        color: #92400e;
    }

    .alert-item.warning .alert-item-message {
        color: #78350f;
    }

    .alert-popup-actions {
        display: flex;
        gap: 10px;
        padding: 15px 20px 20px;
        border-top: 1px solid #e5e7eb;
    }

    .alert-popup-btn {
        flex: 1;
        padding: 10px 15px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-weight: 600;
        transition: all 0.3s;
    }

    .alert-popup-btn-primary {
        background: #667eea;
        color: white;
    }

    .alert-popup-btn-primary:hover {
        background: #5568d3;
    }

    .alert-popup-btn-secondary {
        background: #e5e7eb;
        color: #374151;
    }

    .alert-popup-btn-secondary:hover {
        background: #d1d5db;
    }

    .alert-badge {
        display: inline-block;
        background: #dc2626;
        color: white;
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 12px;
        font-weight: 600;
        margin-left: 8px;
    }

    .alert-notification-bar {
        background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
        border-left: 5px solid #dc2626;
        padding: 12px 20px;
        margin-bottom: 20px;
        border-radius: 4px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .alert-notification-bar-text {
        color: #991b1b;
        font-weight: 600;
    }

    .alert-notification-bar-btn {
        background: #dc2626;
        color: white;
        border: none;
        padding: 6px 12px;
        border-radius: 4px;
        cursor: pointer;
        font-weight: 600;
        transition: background 0.3s;
    }

    .alert-notification-bar-btn:hover {
        background: #b91c1c;
    }
</style>

<!-- Alert Popup HTML -->
<?php if (!empty($critical_alerts_list)): ?>
<div class="alert-notification-bar">
    <span class="alert-notification-bar-text">
        🔔 <?php echo count($critical_alerts_list); ?> Critical Alert(s) - Immediate Action Required!
    </span>
    <button class="alert-notification-bar-btn" onclick="document.getElementById('alertPopup').classList.add('active');">View Details</button>
</div>

<div id="alertPopup" class="alert-popup-container">
    <div class="alert-popup">
        <div class="alert-popup-header">
            <h2>🔴 Critical Alerts <span class="alert-badge"><?php echo count($critical_alerts_list); ?></span></h2>
            <button class="alert-popup-close" onclick="document.getElementById('alertPopup').classList.remove('active');">×</button>
        </div>
        <div class="alert-popup-content">
            <?php foreach ($critical_alerts_list as $alert): ?>
                <?php
                    // Determine alert severity class
                    $severity_class = 'warning';
                    $severity_icon = '⚠️';
                    $severity_title = 'Warning';
                    
                    if ($alert['severity'] == 'EXPIRED') {
                        $severity_class = 'expired';
                        $severity_icon = '❌';
                        $severity_title = 'EXPIRED - TAKE ACTION IMMEDIATELY';
                    } elseif ($alert['severity'] == 'OUT_OF_STOCK') {
                        $severity_class = 'expired';
                        $severity_icon = '❌';
                        $severity_title = 'OUT OF STOCK - URGENT REORDER';
                    } elseif ($alert['severity'] == 'CRITICAL') {
                        $severity_class = 'critical';
                        $severity_icon = '🔴';
                        $severity_title = 'CRITICAL - Requires Action Soon';
                    }
                ?>
                <div class="alert-item <?php echo $severity_class; ?>">
                    <div class="alert-item-title">
                        <?php echo $severity_icon; ?> <?php echo htmlspecialchars($alert['product_name']); ?> (<?php echo htmlspecialchars($alert['product_code']); ?>)
                    </div>
                    <div class="alert-item-message">
                        <?php 
                            if ($alert['alert_type'] == 'EXPIRY') {
                                $days = intval($alert['days_until_expiry']);
                                if ($days <= 0) {
                                    echo "🚨 PRODUCT HAS EXPIRED! Expiry date was " . htmlspecialchars($alert['expiry_date']) . ". Remove from stock immediately.";
                                } else if ($days <= 30) {
                                    echo "Expires in " . $days . " days (" . htmlspecialchars($alert['expiry_date']) . "). Plan clearance or promotion.";
                                } else {
                                    echo "Expires in " . $days . " days (" . htmlspecialchars($alert['expiry_date']) . "). Set reminder for action.";
                                }
                            } else {
                                if ($alert['current_stock'] == 0) {
                                    echo "🚨 OUT OF STOCK! Reorder level is " . $alert['reorder_level'] . " units. Place purchase order immediately.";
                                } else {
                                    echo "Current stock is " . $alert['current_stock'] . " units, below reorder level of " . $alert['reorder_level'] . " units. Shortage: " . ($alert['reorder_level'] - $alert['current_stock']) . " units.";
                                }
                            }
                        ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="alert-popup-actions">
            <a href="alerts-dashboard.php" class="alert-popup-btn alert-popup-btn-primary">📊 View All Alerts</a>
            <button class="alert-popup-btn alert-popup-btn-secondary" onclick="document.getElementById('alertPopup').classList.remove('active');">Dismiss</button>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Notification Counter Badge CSS -->
<style>
    .alert-counter-badge {
        display: inline-block;
        background: #dc2626;
        color: white;
        padding: 4px 8px;
        border-radius: 12px;
        font-size: 12px;
        font-weight: 700;
        margin-left: 8px;
    }
</style>
