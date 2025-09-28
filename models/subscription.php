<?php
// Subscription model utilities

if (!function_exists('subscription_get_or_create_default_plan')) {
    function subscription_get_or_create_default_plan(mysqli $conn): ?array {
        // Default plan definition
        $name = 'Premium';
        $price = 299.00; // PHP
        $durationDays = 30;
        $desc = 'Premium Plan: Priority booking, premium sitters, support, and discounts';

        // Check if exists
        $stmt = $conn->prepare("SELECT subscriptions_id, subscriptions_name, subscriptions_description, subscriptions_price, subscriptions_duration_days, subscriptions_active FROM subscriptions WHERE subscriptions_name = ? LIMIT 1");
        if (!$stmt) return null;
        $stmt->bind_param('s', $name);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res && $row = $res->fetch_assoc()) {
            $stmt->close();
            return $row;
        }
        $stmt->close();
        // Insert default
        $stmt2 = $conn->prepare("INSERT INTO subscriptions (subscriptions_name, subscriptions_description, subscriptions_price, subscriptions_duration_days, subscriptions_active) VALUES (?,?,?,?,1)");
        if (!$stmt2) return null;
        $stmt2->bind_param('ssdi', $name, $desc, $price, $durationDays);
        if (!$stmt2->execute()) { $stmt2->close(); return null; }
        $id = $stmt2->insert_id;
        $stmt2->close();
        return [
            'subscriptions_id' => $id,
            'subscriptions_name' => $name,
            'subscriptions_description' => $desc,
            'subscriptions_price' => $price,
            'subscriptions_duration_days' => $durationDays,
            'subscriptions_active' => 1,
        ];
    }
}

if (!function_exists('subscription_get_active_for_user')) {
    function subscription_get_active_for_user(mysqli $conn, int $userId): ?array {
        $sql = "SELECT us.*, s.subscriptions_name, s.subscriptions_price, s.subscriptions_duration_days
                FROM user_subscriptions us
                JOIN subscriptions s ON s.subscriptions_id = us.subscriptions_id
                WHERE us.users_id = ? AND us.us_status = 'active' AND (us.us_end_date IS NULL OR us.us_end_date >= NOW())
                ORDER BY us.us_start_date DESC LIMIT 1";
        $stmt = $conn->prepare($sql);
        if (!$stmt) return null;
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res ? $res->fetch_assoc() : null;
        $stmt->close();
        return $row ?: null;
    }
}

if (!function_exists('subscription_create_for_user')) {
    function subscription_create_for_user(mysqli $conn, int $userId, int $planId, string $paymentMethod, ?float $overrideAmount = null, ?int $overrideDurationDays = null): array {
        // Validate payment method (accept common wallets and cards as placeholders)
        $allowed = ['cod','gcash','maya','card','paypal','applepay','bank_transfer'];
        if (!in_array($paymentMethod, $allowed, true)) {
            return ['ok' => false, 'error' => 'invalid_payment_method'];
        }
        // Check if plan exists
    $stmtPlan = $conn->prepare("SELECT subscriptions_id, subscriptions_price, subscriptions_duration_days FROM subscriptions WHERE subscriptions_id = ? AND subscriptions_active = 1");
        if (!$stmtPlan) return ['ok' => false, 'error' => 'server_error'];
        $stmtPlan->bind_param('i', $planId);
        $stmtPlan->execute();
        $resPlan = $stmtPlan->get_result();
        $plan = $resPlan ? $resPlan->fetch_assoc() : null;
        $stmtPlan->close();
        if (!$plan) return ['ok' => false, 'error' => 'plan_not_found'];

        // Check existing active subscription
        $existing = subscription_get_active_for_user($conn, $userId);
        if ($existing) {
            // Already active
            return [
                'ok' => true,
                'alreadyActive' => true,
                'us_id' => (int)$existing['us_id'],
                'start_at' => $existing['us_start_date'],
                'end_at' => $existing['us_end_date'],
                'amount' => (float)$plan['subscriptions_price'],
            ];
        }

        // Begin transaction
        $conn->begin_transaction();
        try {
            $amount = $overrideAmount !== null ? (float)$overrideAmount : (float)$plan['subscriptions_price'];
            $duration = $overrideDurationDays !== null ? (int)$overrideDurationDays : (int)$plan['subscriptions_duration_days'];
            $start = date('Y-m-d H:i:s');
            $end = date('Y-m-d H:i:s', strtotime("+{$duration} days"));

            // Create user_subscriptions row
            $stmtUs = $conn->prepare("INSERT INTO user_subscriptions (users_id, subscriptions_id, us_start_date, us_end_date, us_status) VALUES (?,?,?,?, 'active')");
            if (!$stmtUs) throw new Exception('stmt_us');
            $stmtUs->bind_param('iiss', $userId, $planId, $start, $end);
            if (!$stmtUs->execute()) throw new Exception('exec_us');
            $usId = $stmtUs->insert_id;
            $stmtUs->close();

            // Create transaction
            $type = 'subscription';
            $stmtTx = $conn->prepare("INSERT INTO transactions (users_id, transactions_amount, transactions_type, transactions_payment_method) VALUES (?,?,?,?)");
            if (!$stmtTx) throw new Exception('stmt_tx');
            $stmtTx->bind_param('idss', $userId, $amount, $type, $paymentMethod);
            if (!$stmtTx->execute()) throw new Exception('exec_tx');
            $txId = $stmtTx->insert_id;
            $stmtTx->close();

            // Map transaction to subscription
            $stmtMap = $conn->prepare("INSERT INTO transaction_subscriptions (transactions_id, us_id) VALUES (?,?)");
            if (!$stmtMap) throw new Exception('stmt_map');
            $stmtMap->bind_param('ii', $txId, $usId);
            if (!$stmtMap->execute()) throw new Exception('exec_map');
            $stmtMap->close();

            $conn->commit();
            return [
                'ok' => true,
                'us_id' => (int)$usId,
                'transaction_id' => (int)$txId,
                'start_at' => $start,
                'end_at' => $end,
                'amount' => $amount,
            ];
        } catch (Throwable $e) {
            $conn->rollback();
            return ['ok' => false, 'error' => 'db_error'];
        }
    }
}

if (!function_exists('subscription_cancel_active_for_user')) {
    function subscription_cancel_active_for_user(mysqli $conn, int $userId): array {
        // Find active subscription
        $active = subscription_get_active_for_user($conn, $userId);
        if (!$active) {
            return ['ok' => false, 'error' => 'no_active_subscription'];
        }
        $usId = (int)$active['us_id'];
        // Cancel immediately: set status to cancelled and end_date to now
        $sql = "UPDATE user_subscriptions SET us_status='cancelled', us_end_date=NOW() WHERE us_id = ? AND users_id = ?";
        $stmt = $conn->prepare($sql);
        if (!$stmt) return ['ok' => false, 'error' => 'server_error'];
        $stmt->bind_param('ii', $usId, $userId);
        $ok = $stmt->execute();
        $stmt->close();
        if (!$ok) return ['ok' => false, 'error' => 'db_error'];
        return [
            'ok' => true,
            'us_id' => $usId,
            'end_at' => date('Y-m-d H:i:s'),
        ];
    }
}

?>
