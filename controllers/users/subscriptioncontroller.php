<?php
if (session_status() === PHP_SESSION_NONE) session_start();
header('X-Controller: subscription');

require_once dirname(__DIR__, 2) . '/database.php';
require_once dirname(__DIR__, 2) . '/models/subscription.php';
require_once dirname(__DIR__, 2) . '/utils/session.php';

function json_out($arr, int $code = 200) {
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode($arr);
    exit;
}

$user = get_current_user_session();
if (!$user) {
    json_out(['ok' => false, 'error' => 'unauthorized'], 401);
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';
switch ($action) {
    case 'subscribe':
        $payment = $_POST['payment_method'] ?? 'cod';
        $planId = isset($_POST['plan_id']) ? (int)$_POST['plan_id'] : 0;
        $billingCycle = strtolower(trim($_POST['billing_cycle'] ?? 'monthly'));
        if ($billingCycle !== 'yearly') { $billingCycle = 'monthly'; }
        // Ensure a default plan exists if none specified
        if ($planId <= 0) {
            $plan = subscription_get_or_create_default_plan($connections);
            if (!$plan) json_out(['ok' => false, 'error' => 'plan_init_failed'], 500);
            $planId = (int)$plan['subscriptions_id'];
        }
        // Fetch plan details for computing amount/duration
        $stmt = $connections->prepare("SELECT subscriptions_price, subscriptions_duration_days FROM subscriptions WHERE subscriptions_id = ? AND subscriptions_active = 1");
        if (!$stmt) json_out(['ok' => false, 'error' => 'server_error'], 500);
        $stmt->bind_param('i', $planId);
        $stmt->execute();
        $rs = $stmt->get_result();
        $planRow = $rs ? $rs->fetch_assoc() : null;
        $stmt->close();
        if (!$planRow) json_out(['ok' => false, 'error' => 'plan_not_found'], 404);

        $basePrice = (float)$planRow['subscriptions_price'];
        $baseDuration = (int)$planRow['subscriptions_duration_days']; // typically 30
        if ($billingCycle === 'yearly') {
            $overrideAmount = max(0, $basePrice * 12 - 600); // ₱600 savings
            $overrideDuration = 365; // 1 year
        } else {
            $overrideAmount = $basePrice;
            $overrideDuration = $baseDuration > 0 ? $baseDuration : 30;
        }

        $res = subscription_create_for_user($connections, (int)$user['users_id'], $planId, $payment, $overrideAmount, $overrideDuration);
        if (!$res['ok']) json_out($res, 400);
        json_out($res);
        break;

    case 'status':
        $active = subscription_get_active_for_user($connections, (int)$user['users_id']);
        json_out(['ok' => true, 'active' => $active ? true : false, 'data' => $active]);
        break;

    case 'cancel':
        $res = subscription_cancel_active_for_user($connections, (int)$user['users_id']);
        if (!$res['ok']) json_out($res, 400);
        json_out($res);
        break;

    default:
        json_out(['ok' => false, 'error' => 'unknown_action'], 400);
}

?>
