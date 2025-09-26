<?php
// Returns all active locations for the logged-in user so checkout & other pages
// can stay in sync with the profile's saved addresses without duplication.
if (session_status() === PHP_SESSION_NONE) session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../database.php';
require_once __DIR__ . '/../models/location.php';

$userId = $_SESSION['user']['users_id'] ?? null;
if (!$userId) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'auth']);
    exit;
}

try {
    $rows = location_get_all_by_user($connections, (int)$userId);
    $addresses = array_map(function($r){
        return [
            'id' => (int)$r['location_id'],
            'label' => (string)$r['location_label'],
            'recipient' => (string)$r['location_recipient_name'],
            'phone' => (string)($r['location_phone'] ?? ''),
            'full' => trim(
                ($r['location_address_line1'] ?? '') . ' ' .
                ($r['location_address_line2'] ?? '') . ', ' .
                ($r['location_barangay'] ?? '') . ', ' .
                ($r['location_city'] ?? '') . ', ' .
                ($r['location_province'] ?? '')
            ),
            'is_default' => (int)$r['location_is_default']
        ];
    }, $rows ?? []);
    echo json_encode(['ok' => true, 'locations' => $addresses]);
} catch(Throwable $e){
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'server']);
}
<?php // end file
