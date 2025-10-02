<?php
// User Promos Controller: list available promos, claim, list claimed, generate QR
header('Content-Type: application/json; charset=UTF-8');
require_once __DIR__ . '/../../utils/session.php';
require_once __DIR__ . '/../../database.php';
session_start_if_needed();

if(!isset($connections) || !$connections){ echo json_encode(['success'=>false,'message'=>'DB unavailable']); exit; }
$user = get_current_user_session();
if(!$user || empty($user['users_id'])){ echo json_encode(['success'=>false,'message'=>'Not authenticated']); exit; }
$uid = (int)$user['users_id'];

$action = isset($_REQUEST['action'])? trim($_REQUEST['action']) : 'list';

function respond($ok,$msg,$extra=[]) { echo json_encode(array_merge(['success'=>$ok,'message'=>$msg],$extra)); exit; }

// Ensure user_promos table (simple) & user_promo_qr temp generation (promo claim storage)
// Expect promotions table already exists.
@mysqli_query($connections, "CREATE TABLE IF NOT EXISTS user_promos (
	up_id INT AUTO_INCREMENT PRIMARY KEY,
	users_id INT NOT NULL,
	promo_id INT NOT NULL,
	up_code VARCHAR(64) NOT NULL,
	up_claimed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
	up_redeemed_at DATETIME NULL,
	up_qr_svg MEDIUMTEXT NULL,
	UNIQUE KEY uniq_user_promo(users_id,promo_id),
	KEY idx_user(up_id, users_id),
	CONSTRAINT fk_up_user FOREIGN KEY (users_id) REFERENCES users(users_id) ON DELETE CASCADE,
	CONSTRAINT fk_up_promo FOREIGN KEY (promo_id) REFERENCES promotions(promo_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

// Helper to fetch user's point balance (assuming user_points_balance table optional)
function get_user_points($conn,$uid){
	$pts = 0; $res = mysqli_query($conn, 'SELECT upb_points FROM user_points_balance WHERE users_id='.(int)$uid.' LIMIT 1');
	if($res && $row = mysqli_fetch_assoc($res)){ $pts = (int)$row['upb_points']; mysqli_free_result($res);} return $pts;
}

if($action==='list'){
	// List active promos user can see + claimed ones
	$now = date('Y-m-d H:i:s');
	$rows = [];
	$sql = "SELECT p.*, up.up_id, up.up_redeemed_at, up.up_code AS user_code
			FROM promotions p
			LEFT JOIN user_promos up ON up.promo_id = p.promo_id AND up.users_id = $uid
			WHERE p.promo_active=1
			  AND (p.promo_starts_at IS NULL OR p.promo_starts_at <= '$now')
			  AND (p.promo_ends_at IS NULL OR p.promo_ends_at >= '$now')
			ORDER BY p.promo_points_cost ASC, p.promo_created_at DESC";
	if($res = mysqli_query($connections,$sql)){
		while($r = mysqli_fetch_assoc($res)) $rows[]=$r; mysqli_free_result($res);
	}
	$userPoints = get_user_points($connections,$uid);
	respond(true,'OK',[ 'promotions'=>$rows, 'user_points'=>$userPoints ]);
}
elseif($action==='claimed'){
	$rows=[]; $res = mysqli_query($connections, "SELECT up.*, p.promo_name, p.promo_code, p.promo_discount_type, p.promo_discount_value FROM user_promos up JOIN promotions p ON p.promo_id=up.promo_id WHERE up.users_id=$uid ORDER BY up.up_claimed_at DESC");
	if($res){ while($r=mysqli_fetch_assoc($res)) $rows[]=$r; mysqli_free_result($res);} respond(true,'OK',['claimed'=>$rows]);
}
elseif($action==='claim'){
	$promo_id = isset($_POST['promo_id'])? (int)$_POST['promo_id'] : 0; if($promo_id<=0) respond(false,'Invalid promo');
	// Fetch promo
	$now = date('Y-m-d H:i:s');
	$res = mysqli_query($connections, "SELECT * FROM promotions WHERE promo_id=$promo_id AND promo_active=1 LIMIT 1");
	$promo = $res? mysqli_fetch_assoc($res) : null; if($res) mysqli_free_result($res);
	if(!$promo) respond(false,'Promo not found or inactive');
	if(($promo['promo_starts_at'] && $promo['promo_starts_at'] > $now) || ($promo['promo_ends_at'] && $promo['promo_ends_at'] < $now)) respond(false,'Promo not in valid window');
	// Points check
	$cost = (int)($promo['promo_points_cost'] ?? 0); $userPoints = get_user_points($connections,$uid);
	if($cost>0 && $userPoints < $cost) respond(false,'Not enough points');
	// Already claimed?
	$res2 = mysqli_query($connections, "SELECT up_id, up_code FROM user_promos WHERE users_id=$uid AND promo_id=$promo_id LIMIT 1");
	if($res2 && $already = mysqli_fetch_assoc($res2)){ mysqli_free_result($res2); respond(true,'Already claimed',[ 'claimed'=>true,'code'=>$already['up_code'] ]); }
	if($res2) mysqli_free_result($res2);
	// Generate user-specific code
	$base = $promo['promo_code'] ?: 'PROMO';
	$userCode = $base.'-U'.$uid.'-'.substr(strtoupper(bin2hex(random_bytes(4))),0,6);
	$stmt = mysqli_prepare($connections, 'INSERT INTO user_promos (users_id,promo_id,up_code) VALUES (?,?,?)');
	if(!$stmt) respond(false,'Insert failed');
	mysqli_stmt_bind_param($stmt,'iis',$uid,$promo_id,$userCode);
	if(!mysqli_stmt_execute($stmt)){ $err = mysqli_error($connections); mysqli_stmt_close($stmt); respond(false,'Claim failed: '.$err); }
	mysqli_stmt_close($stmt);
	// Deduct points if cost > 0 (ledger + balance)
	if($cost>0){
		@mysqli_query($connections, "CREATE TABLE IF NOT EXISTS user_points_balance (users_id INT PRIMARY KEY, upb_points INT NOT NULL DEFAULT 0, upb_updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
		@mysqli_query($connections, "CREATE TABLE IF NOT EXISTS user_points_ledger (upl_id INT AUTO_INCREMENT PRIMARY KEY, users_id INT NOT NULL, upl_points INT NOT NULL, upl_reason VARCHAR(100), upl_source_type VARCHAR(50), upl_source_id INT, upl_created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, UNIQUE KEY uniq_source(users_id,upl_source_type,upl_source_id), KEY idx_user(users_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
		// Ensure balance row
		mysqli_query($connections, "INSERT IGNORE INTO user_points_balance (users_id, upb_points) VALUES ($uid, 0)");
		// Deduct safely only if sufficient points (double-check concurrency)
		mysqli_query($connections, "UPDATE user_points_balance SET upb_points = upb_points - $cost WHERE users_id=$uid AND upb_points >= $cost");
		// Verify deduction
		$newPts = get_user_points($connections,$uid);
		if($newPts !== $userPoints - $cost){
			// Rollback promo claim if deduction failed (rare race condition)
			mysqli_query($connections, "DELETE FROM user_promos WHERE users_id=$uid AND promo_id=$promo_id LIMIT 1");
			respond(false,'Insufficient points (race)');
		}
		// Insert ledger (-cost)
		$stmtL = mysqli_prepare($connections, 'INSERT IGNORE INTO user_points_ledger (users_id,upl_points,upl_reason,upl_source_type,upl_source_id) VALUES (?,?,?,?,?)');
		if($stmtL){ $neg = -$cost; $reason='Promo Claim'; $stype='promo'; $src=$promo_id; mysqli_stmt_bind_param($stmtL,'iissi',$uid,$neg,$reason,$stype,$promo_id); mysqli_stmt_execute($stmtL); mysqli_stmt_close($stmtL);} 
		respond(true,'Claimed', ['claimed'=>true,'code'=>$userCode,'new_points'=>$newPts]);
	} else {
		respond(true,'Claimed', ['claimed'=>true,'code'=>$userCode,'new_points'=>get_user_points($connections,$uid)]);
	}
}
elseif($action==='qr'){
	$up_id = isset($_GET['up_id'])? (int)$_GET['up_id'] : 0; if($up_id<=0) respond(false,'Invalid');
	$res = mysqli_query($connections, "SELECT up.*, p.promo_name FROM user_promos up JOIN promotions p ON p.promo_id=up.promo_id WHERE up.up_id=$up_id AND up.users_id=$uid LIMIT 1");
	$row = $res? mysqli_fetch_assoc($res):null; if($res) mysqli_free_result($res);
	if(!$row) respond(false,'Not found');
	// Simple inline SVG QR (tiny fallback manual encoding of code as blocks). For production, integrate a QR library.
	$code = $row['up_code'];
	$hash = md5($code);
	$size = 21; // pseudo size
	$svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 '+$size+' '+$size+'" shape-rendering="crispEdges">';
	for($y=0;$y<$size;$y++){
		for($x=0;$x<$size;$x++){
			$bit = hexdec($hash[($x+$y*$size)%strlen($hash)]); if($bit % 3 === 0){ $svg.='<rect x="'.$x.'" y="'.$y.'" width="1" height="1" fill="#0f172a"/>'; }
		}
	}
	$svg.='</svg>';
	// Optionally cache
	$stmt = mysqli_prepare($connections,'UPDATE user_promos SET up_qr_svg=? WHERE up_id=?');
	if($stmt){ mysqli_stmt_bind_param($stmt,'si',$svg,$up_id); mysqli_stmt_execute($stmt); mysqli_stmt_close($stmt); }
	respond(true,'OK',['svg'=>$svg,'code'=>$code,'promo_name'=>$row['promo_name']]);
}
elseif($action==='points'){
	$pts = get_user_points($connections,$uid);
	respond(true,'OK',['points'=>$pts]);
}
elseif($action==='ledger'){
	$rows=[]; @mysqli_query($connections, "CREATE TABLE IF NOT EXISTS user_points_ledger (upl_id INT AUTO_INCREMENT PRIMARY KEY, users_id INT NOT NULL, upl_points INT NOT NULL, upl_reason VARCHAR(100), upl_source_type VARCHAR(50), upl_source_id INT, upl_created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, UNIQUE KEY uniq_source(users_id,upl_source_type,upl_source_id), KEY idx_user(users_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
	if($res = mysqli_query($connections, "SELECT upl_id,upl_points,upl_reason,upl_source_type,upl_source_id,upl_created_at FROM user_points_ledger WHERE users_id=$uid ORDER BY upl_created_at DESC,upl_id DESC LIMIT 20")){
		while($r=mysqli_fetch_assoc($res)) $rows[]=$r; mysqli_free_result($res);
	}
	respond(true,'OK',['entries'=>$rows]);
}
else { respond(false,'Unsupported action'); }

?>
