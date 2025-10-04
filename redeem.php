<?php
// Public QR redemption endpoint.
require_once __DIR__ . '/utils/session.php';
require_once __DIR__ . '/database.php';
session_start_if_needed();

function render_page($title, $message, $ok=false){
    ?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title><?php echo htmlspecialchars($title); ?></title>
<link rel="stylesheet" href="globals.css" />
<script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-gradient-to-br from-orange-50 via-amber-50 to-yellow-50 flex items-center justify-center p-6">
  <div class="bg-white border border-orange-200 rounded-2xl shadow-xl max-w-md w-full p-6 text-center">
    <div class="mx-auto w-16 h-16 rounded-full flex items-center justify-center <?php echo $ok ? 'bg-emerald-50' : 'bg-red-50'; ?> mb-3">
      <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8 <?php echo $ok ? 'text-emerald-600' : 'text-red-600'; ?>" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <?php if($ok){ ?>
          <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14" />
          <polyline points="22 4 12 14.01 9 11.01" />
        <?php } else { ?>
          <circle cx="12" cy="12" r="10" />
          <line x1="15" y1="9" x2="9" y2="15" />
          <line x1="9" y1="9" x2="15" y2="15" />
        <?php } ?>
      </svg>
    </div>
    <h1 class="text-xl font-semibold mb-2"><?php echo htmlspecialchars($title); ?></h1>
    <p class="text-gray-600 text-sm"><?php echo htmlspecialchars($message); ?></p>
    <div class="mt-5">
      <a href="/" class="inline-block px-4 py-2 rounded-md bg-orange-500 hover:bg-orange-600 text-white text-sm">Go to Home</a>
    </div>
  </div>
</body>
</html><?php
}

if(!isset($connections) || !$connections){ render_page('Not available','Database connection is unavailable.'); exit; }

$token = isset($_GET['t']) ? trim($_GET['t']) : '';
if($token===''){ render_page('Invalid QR','This QR link is missing or invalid.'); exit; }

// Look up user_promos by token
$stmt = mysqli_prepare($connections, 'SELECT up.up_id, up.users_id, up.promo_id, up.up_qr_token_redeemed_at, p.promo_type, p.promo_name, p.promo_per_user_limit FROM user_promos up JOIN promotions p ON p.promo_id = up.promo_id WHERE up.up_qr_token = ? LIMIT 1');
if(!$stmt){ render_page('Invalid QR','Could not validate QR.'); exit; }
mysqli_stmt_bind_param($stmt,'s',$token);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$row = $res? mysqli_fetch_assoc($res) : null;
mysqli_stmt_close($stmt);
if(!$row){ render_page('Invalid QR','This QR is not valid.'); exit; }

$up_id = (int)$row['up_id'];
$uid = (int)$row['users_id'];
$promo_id = (int)$row['promo_id'];
$promo_type = strtolower((string)$row['promo_type']);
$already = $row['up_qr_token_redeemed_at'] ? true : false;

if($already){
  render_page('Already Redeemed','This QR has already been redeemed.');
  exit;
}

// Only appointment type QR is allowed
if($promo_type !== 'appointment'){
  render_page('Not Valid','This QR is not valid for redemption.');
  exit;
}

// Mark token redeemed and record redemption usage (per-user limit enforcement similar to API)
// Create promotion_redemptions if needed
@mysqli_query($connections, "CREATE TABLE IF NOT EXISTS promotion_redemptions (
    pr_id INT AUTO_INCREMENT PRIMARY KEY,
    promo_id INT NOT NULL,
    users_id INT NOT NULL,
    up_id INT NULL,
    pr_status ENUM('applied','rejected') NOT NULL DEFAULT 'applied',
    pr_created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_user_promo(users_id, promo_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

// Get usage and limit
$limit = isset($row['promo_per_user_limit']) ? (int)$row['promo_per_user_limit'] : 1;
if($limit<=0) $limit = 1;
$used = 0; $r2 = mysqli_query($connections, "SELECT COUNT(*) c FROM promotion_redemptions WHERE users_id=$uid AND promo_id=$promo_id AND pr_status='applied'");
if($r2 && $rr = mysqli_fetch_assoc($r2)){ $used = (int)$rr['c']; mysqli_free_result($r2);} 
if($used >= $limit){
  render_page('Limit Reached','Usage limit for this promotion has been reached.');
  exit;
}

// Mark user_promos token redeemed
mysqli_query($connections, "UPDATE user_promos SET up_qr_token_redeemed_at = NOW(), up_redeemed_at = IFNULL(up_redeemed_at, NOW()) WHERE up_id=$up_id LIMIT 1");
// Record redemption
$stmt2 = mysqli_prepare($connections, 'INSERT INTO promotion_redemptions (promo_id, users_id, up_id, pr_status) VALUES (?,?,?,\'applied\')');
if($stmt2){ mysqli_stmt_bind_param($stmt2,'iii',$promo_id,$uid,$up_id); mysqli_stmt_execute($stmt2); mysqli_stmt_close($stmt2);} 

render_page('Success','Your coupon has been redeemed!', true);
