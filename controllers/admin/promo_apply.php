<?php
// Admin QR Apply Endpoint (validates against booking user id and applies/claims)
header('Content-Type: application/json; charset=UTF-8');
session_start();
require_once __DIR__ . '/../../database.php';

function respond($ok, $msg, $extra = []) { echo json_encode(array_merge(['success'=>$ok,'message'=>$msg], $extra)); exit; }

if (!isset($connections) || !$connections) respond(false, 'Database unavailable');

// Auth: ensure current user is admin
if (empty($_SESSION['users_id'])) respond(false, 'Not authenticated');
$adminId = (int)$_SESSION['users_id'];
$isAdmin = false;
if ($res = mysqli_query($connections, 'SELECT users_role FROM users WHERE users_id=' . $adminId . ' LIMIT 1')) {
    if ($row = mysqli_fetch_assoc($res)) { $isAdmin = ((string)$row['users_role'] === '1'); }
    mysqli_free_result($res);
}
if (!$isAdmin) respond(false, 'Forbidden');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') respond(false, 'Method not allowed');

$payloadRaw = isset($_POST['payload']) ? trim((string)$_POST['payload']) : '';
$bookingUserId = isset($_POST['booking_user_id']) ? (int)$_POST['booking_user_id'] : 0;
if ($payloadRaw === '') respond(false, 'Missing payload');
if ($bookingUserId <= 0) respond(false, 'Missing booking user id');

$data = json_decode($payloadRaw, true);
if (!is_array($data)) respond(false, 'Invalid payload');

// Expected fields: t, kind, user, promo_id, code
$t = $data['t'] ?? '';
$kind = strtolower((string)($data['kind'] ?? ''));
$qrUserId = (int)($data['user'] ?? 0);
$code = trim((string)($data['code'] ?? ''));
if ($t !== 'promo' || $qrUserId <= 0 || $code === '') respond(false, 'Malformed payload');

// Enforce: booking user must match the QR user
if ($qrUserId !== $bookingUserId) respond(false, 'QR user does not match the booking user');

// Fetch user promo by code and user
$sql = "SELECT up.*, p.promo_id, p.promo_name, p.promo_type, p.promo_per_user_limit
        FROM user_promos up
        JOIN promotions p ON p.promo_id = up.promo_id
        WHERE up.users_id = $qrUserId AND up.up_code = '" . mysqli_real_escape_string($connections, $code) . "' LIMIT 1";
$row = null;
if ($res = mysqli_query($connections, $sql)) { $row = mysqli_fetch_assoc($res); mysqli_free_result($res); }
if (!$row) respond(false, 'Coupon not found for user');

// Appointment-only promo allowed as requested
if ($kind !== 'appointment' || strtolower((string)$row['promo_type']) !== 'appointment') respond(false, 'Coupon is not valid for appointments');

$promoId = (int)$row['promo_id'];
$upId = (int)$row['up_id'];
$limit = (int)($row['promo_per_user_limit'] ?? 1); if ($limit <= 0) $limit = 1;

// Current usage count
$used = 0;
if ($r2 = mysqli_query($connections, "SELECT COUNT(*) c FROM promotion_redemptions WHERE users_id=$qrUserId AND promo_id=$promoId AND pr_status='applied'")) {
    if ($ru = mysqli_fetch_assoc($r2)) $used = (int)$ru['c'];
    mysqli_free_result($r2);
}
if ($used >= $limit) respond(false, 'Promo already fully used', ['usage_count'=>$used,'limit'=>$limit,'user_id'=>$qrUserId,'promo_id'=>$promoId,'code'=>$code]);

// Apply (insert redemption) and mark first claimed timestamp
$stmt = mysqli_prepare($connections, 'INSERT INTO promotion_redemptions (promo_id, users_id, up_id, pr_status) VALUES (?,?,?,\'applied\')');
if (!$stmt) respond(false, 'Apply prepare failed: '.mysqli_error($connections));
mysqli_stmt_bind_param($stmt, 'iii', $promoId, $qrUserId, $upId);
if (!mysqli_stmt_execute($stmt)) { $err = mysqli_error($connections); mysqli_stmt_close($stmt); respond(false, 'Apply failed: '.$err); }
mysqli_stmt_close($stmt);
mysqli_query($connections, "UPDATE user_promos SET up_redeemed_at = IFNULL(up_redeemed_at, NOW()) WHERE up_id=$upId AND users_id=$qrUserId LIMIT 1");
$used++;

respond(true, 'Applied', [
    'usage_count' => $used,
    'limit' => $limit,
    'user_id' => $qrUserId,
    'promo_id' => $promoId,
    'promo_name' => $row['promo_name'],
    'code' => $code
]);
?>
