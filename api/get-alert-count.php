<?php
/**
 * Get Alert Count API
 * Returns active alert count for navbar badge
 */

require_once '../config/database.php';
require_once '../config/session.php';
require_once '../classes/Alerts.php';

// Only process AJAX requests
if (!isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
    http_response_code(403);
    exit(json_encode(['error' => 'Forbidden']));
}

try {
    $alerts = new Alerts($conn);
    $alertCounts = $alerts->getAlertCounts();
    
    echo json_encode([
        'success' => true,
        'count' => $alertCounts['active'],
        'critical' => $alertCounts['critical']
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
