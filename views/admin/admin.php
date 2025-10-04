<?php
// --- Access Control & Admin Session Bootstrap ---
session_start();
require_once __DIR__ . '/../../utils/auth_persist.php'; // maintain login across visits
require_once __DIR__ . '/../../database.php';

$admin = [
    'users_id' => null,
    'users_firstname' => '',
    'users_lastname' => '',
    'users_username' => '',
    'users_email' => '',
    'users_image_url' => '',
    'users_role' => ''
];

// 1. If NO logged-in user -> redirect guest to public landing (root index)
if (empty($_SESSION['users_id'])) {
    header('Location: ../../error.php');
    exit; // stop further processing
}

$currentUserId = (int)$_SESSION['users_id'];

// 2. Fetch the current user record (role required for gate)
if ($stmt = mysqli_prepare($connections, "SELECT users_id, users_firstname, users_lastname, users_username, users_email, users_image_url, users_role FROM users WHERE users_id = ? LIMIT 1")) {
    mysqli_stmt_bind_param($stmt, 'i', $currentUserId);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $uid, $fn, $ln, $un, $em, $img, $role);
    if (mysqli_stmt_fetch($stmt)) {
        $admin['users_id'] = $uid;
        $admin['users_firstname'] = $fn;
        $admin['users_lastname'] = $ln;
        $admin['users_username'] = $un;
        $admin['users_email'] = $em;
        $admin['users_image_url'] = $img;
        $admin['users_role'] = (string)$role;
    }
    mysqli_stmt_close($stmt);
}

// 3. If logged-in user is NOT an admin (role != '1') -> redirect to user homepage (no error page flash)
if (($admin['users_role'] ?? '') !== '1') {
    header('Location: ../users/error403.php');
    exit;
}

// (Removed insecure fallback that auto-selected the first admin account.)

$admin_fullname = trim(($admin['users_firstname'] ?? '') . ' ' . ($admin['users_lastname'] ?? '')) ?: 'Admin User';
$admin_initial = strtoupper(substr(($admin['users_firstname'] ?? '') !== '' ? $admin['users_firstname'] : ($admin['users_username'] ?? 'A'), 0, 1));

// ================= PROMOTIONS (simple CRUD) =================
$promoFeedback = ['success'=>'','error'=>''];
$promotions = [];
if(isset($connections) && $connections){
    if(!empty($_POST['promo_action'])){
        $act = $_POST['promo_action'];
        if($act==='add'){
            $cols = ['promo_type','promo_code','promo_name','promo_description','promo_discount_type','promo_discount_value','promo_points_cost','promo_min_purchase_amount','promo_usage_limit','promo_per_user_limit','promo_require_active_subscription','promo_starts_at','promo_ends_at','promo_active'];
            $d=[]; foreach($cols as $c){ $d[$c]= isset($_POST[$c])?trim($_POST[$c]):null; }
            if($d['promo_name']===''){ $promoFeedback['error']='Promo name required.'; }
            if($promoFeedback['error']===''){
                foreach(['promo_discount_value','promo_min_purchase_amount'] as $nf){ if($d[$nf]==='') $d[$nf]=null; }
                foreach(['promo_points_cost','promo_usage_limit','promo_per_user_limit','promo_require_active_subscription','promo_active'] as $if){ $d[$if]= ($d[$if]===''||$d[$if]===null)? null : (int)$d[$if]; }
                foreach(['promo_starts_at','promo_ends_at'] as $dt){ if(!empty($d[$dt])) $d[$dt]=str_replace('T',' ',$d[$dt]); }
                $sql="INSERT INTO promotions (promo_type,promo_code,promo_name,promo_description,promo_discount_type,promo_discount_value,promo_points_cost,promo_min_purchase_amount,promo_usage_limit,promo_per_user_limit,promo_require_active_subscription,promo_starts_at,promo_ends_at,promo_active) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
                $stmt = mysqli_prepare($connections,$sql);
                if($stmt){
                    $order=['promo_type','promo_code','promo_name','promo_description','promo_discount_type','promo_discount_value','promo_points_cost','promo_min_purchase_amount','promo_usage_limit','promo_per_user_limit','promo_require_active_subscription','promo_starts_at','promo_ends_at','promo_active'];
                    $types=''; $vals=[];
                    foreach($order as $c){
                        $v=$d[$c];
                        if(in_array($c,['promo_discount_value','promo_min_purchase_amount'])){ $types.='d'; $v=$v!==null?(float)$v:null; }
                        elseif(in_array($c,['promo_points_cost','promo_usage_limit','promo_per_user_limit','promo_require_active_subscription','promo_active'])){ $types.='i'; $v=$v!==null?(int)$v:0; }
                        else { $types.='s'; }
                        $vals[]=$v;
                    }
                    $refs=[]; foreach($vals as $k=>$v){ $refs[$k]=$vals[$k]; }
                    $bind = array_merge([$stmt,$types],$refs); $tmp=[]; foreach($bind as $k=>$v){ $tmp[$k]=&$bind[$k]; }
                    call_user_func_array('mysqli_stmt_bind_param',$tmp);
                    if(mysqli_stmt_execute($stmt)){ $promoFeedback['success']='Promo added.'; } else { $promoFeedback['error']='Insert failed: '.mysqli_error($connections); }
                    mysqli_stmt_close($stmt);
                } else { $promoFeedback['error']='Prepare failed: '.mysqli_error($connections); }
            }
        } elseif($act==='toggle' && isset($_POST['promo_id'])){
            $pid=(int)$_POST['promo_id']; $to = (isset($_POST['to']) && $_POST['to']=='1')?1:0;
            if($stmt=mysqli_prepare($connections,"UPDATE promotions SET promo_active=? WHERE promo_id=?")){
                mysqli_stmt_bind_param($stmt,'ii',$to,$pid);
                if(mysqli_stmt_execute($stmt)){ $promoFeedback['success']='Promo state updated.'; } else { $promoFeedback['error']='Toggle failed.'; }
                mysqli_stmt_close($stmt);
            }
        } elseif($act==='delete' && isset($_POST['promo_id'])){
            $pid=(int)$_POST['promo_id'];
            if($stmt=mysqli_prepare($connections,"DELETE FROM promotions WHERE promo_id=?")){
                mysqli_stmt_bind_param($stmt,'i',$pid);
                if(mysqli_stmt_execute($stmt)){ $promoFeedback['success']='Promo deleted.'; } else { $promoFeedback['error']='Delete failed.'; }
                mysqli_stmt_close($stmt);
            }
        }
    }
    if($res=mysqli_query($connections,"SELECT * FROM promotions ORDER BY promo_created_at DESC")){
        while($row=mysqli_fetch_assoc($res)) $promotions[]=$row; mysqli_free_result($res);
    }
}

// ====== Dashboard KPIs (database-driven) ======
// Metrics: Total Sales (sum transactions_amount for product & subscription), Appointments Booked (count appointments),
// Pet Sitters (active sitters), Pet Owners (non-admin users), Active Users (distinct users having recent activity: transaction OR appointment in last 30 days)
$kpi = [
    'total_sales' => 0.00,
    'appointments' => 0,
    'sitters' => 0,
    'owners' => 0,
    'active_users' => 0
];
$topSellingProducts = [];
if(isset($connections) && $connections){
    // Total sales
    if($res = mysqli_query($connections, "SELECT COALESCE(SUM(transactions_amount),0) s FROM transactions")){
        if($row = mysqli_fetch_assoc($res)) $kpi['total_sales'] = (float)$row['s'];
        mysqli_free_result($res);
    }
    // Appointments booked
    if($res = mysqli_query($connections, "SELECT COUNT(*) c FROM appointments")){
        if($row = mysqli_fetch_assoc($res)) $kpi['appointments'] = (int)$row['c'];
        mysqli_free_result($res);
    }
    // Active sitters (sitters_active=1)
    if($res = mysqli_query($connections, "SELECT COUNT(*) c FROM sitters WHERE sitters_active=1")){
        if($row = mysqli_fetch_assoc($res)) $kpi['sitters'] = (int)$row['c'];
        mysqli_free_result($res);
    }
    // Pet owners (users_role='0')
    if($res = mysqli_query($connections, "SELECT COUNT(*) c FROM users WHERE users_role='0'")){
        if($row = mysqli_fetch_assoc($res)) $kpi['owners'] = (int)$row['c'];
        mysqli_free_result($res);
    }
    // Active users (any transaction or appointment in last 30 days)
    if($res = mysqli_query($connections, "SELECT COUNT(DISTINCT u.users_id) c FROM users u LEFT JOIN transactions t ON t.users_id=u.users_id AND t.transactions_created_at >= (NOW() - INTERVAL 30 DAY) LEFT JOIN appointments a ON a.users_id=u.users_id AND a.appointments_created_at >= (NOW() - INTERVAL 30 DAY) WHERE (t.transactions_id IS NOT NULL OR a.appointments_id IS NOT NULL)")){
        if($row = mysqli_fetch_assoc($res)) $kpi['active_users'] = (int)$row['c'];
        mysqli_free_result($res);
    }
    // Top selling products (by total quantity ordered) for modal
    $sqlTop = "SELECT p.products_id, p.products_name, p.products_image_url, SUM(tp.tp_quantity) total_qty
               FROM transaction_products tp
               JOIN transactions t ON t.transactions_id = tp.transactions_id AND t.transactions_type='product'
               JOIN products p ON p.products_id = tp.products_id
               GROUP BY p.products_id, p.products_name, p.products_image_url
               ORDER BY total_qty DESC, p.products_name ASC
               LIMIT 100"; // safety limit
    if($res = mysqli_query($connections,$sqlTop)){
        while($r = mysqli_fetch_assoc($res)) $topSellingProducts[] = $r;
        mysqli_free_result($res);
    }
}

// ---- Subscribers (Subscription) Stats + List Preparation ----
// We treat any record in user_subscriptions as a subscriber. Active defined by status 'active' and (no end date or future end date).
$subscriberStats = [ 'total' => 0, 'active' => 0, 'this_month' => 0 ];
$subscribersList = [];
if(isset($connections) && $connections){
    // Total distinct users subscribed at least once
    if($res = mysqli_query($connections, "SELECT COUNT(DISTINCT users_id) c FROM user_subscriptions")){
        if($row = mysqli_fetch_assoc($res)) $subscriberStats['total'] = (int)$row['c'];
        mysqli_free_result($res);
    }
    // Active distinct users
    if($res = mysqli_query($connections, "SELECT COUNT(DISTINCT users_id) c FROM user_subscriptions WHERE us_status='active' AND (us_end_date IS NULL OR us_end_date >= NOW())")){
        if($row = mysqli_fetch_assoc($res)) $subscriberStats['active'] = (int)$row['c'];
        mysqli_free_result($res);
    }
    // Started this month (distinct)
    if($res = mysqli_query($connections, "SELECT COUNT(DISTINCT users_id) c FROM user_subscriptions WHERE YEAR(us_start_date)=YEAR(CURDATE()) AND MONTH(us_start_date)=MONTH(CURDATE())")){
        if($row = mysqli_fetch_assoc($res)) $subscriberStats['this_month'] = (int)$row['c'];
        mysqli_free_result($res);
    }
    // Latest subscription per user for table
    $sqlSubs = "SELECT u.users_id, CONCAT(COALESCE(u.users_firstname,''),' ',COALESCE(u.users_lastname,'')) full_name, u.users_email, us.us_id, us.us_start_date, us.us_end_date, us.us_status
                FROM user_subscriptions us
                JOIN users u ON u.users_id = us.users_id
                INNER JOIN (
                    SELECT users_id, MAX(us_start_date) latest_start
                    FROM user_subscriptions
                    GROUP BY users_id
                ) latest ON latest.users_id = us.users_id AND latest.latest_start = us.us_start_date
                ORDER BY us.us_start_date DESC";
    if($res = mysqli_query($connections, $sqlSubs)){
        while($row = mysqli_fetch_assoc($res)) $subscribersList[] = $row;
        mysqli_free_result($res);
    }
}

function resolveImageUrl($path) {
    if (!$path) return '';
    if (preg_match('/^https?:/i', $path)) return $path;
    if (strpos($path, '/') === 0) return $path;
    if (strpos($path, '../') === 0) return $path;
    return '../../' . ltrim($path, '/');
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Pawhabilin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=La+Belle+Aurore&display=swap" rel="stylesheet">
    <link href="styles/globals.css" rel="stylesheet">
    
    <style>
        .chart-bar {
            transition: all 0.3s ease;
        }
        
        .chart-bar:hover {
            transform: scaleY(1.1);
        }
        
        .stats-card {
            transition: all 0.3s ease;
            position: relative;
            isolation: isolate;
        }
        .stats-card:hover { transform: translateY(-2px); box-shadow: 0 10px 25px rgba(0,0,0,0.1); }
        /* Dark Mode Base */
        body.dark { background-color:#0f172a; }
        body.dark h1,body.dark h2,body.dark h3,body.dark p,body.dark label,body.dark span { color:#e2e8f0; }
        body.dark .stats-card { border-color:rgba(255,255,255,0.08)!important; }
        body.dark .stats-card::before { content:""; position:absolute; inset:0; background:radial-gradient(circle at 30% 25%,rgba(255,255,255,0.08),transparent 70%); opacity:.85; z-index:0; }
        body.dark .stats-card > * { position:relative; z-index:1; }
        /* Metric Themes */
        body.dark .stats-card[data-metric="sales"] { background:linear-gradient(135deg,#1e293b,#0f172a); box-shadow:0 4px 18px -2px rgba(249,115,22,0.35); }
        body.dark .stats-card[data-metric="sales"] .metric-number { color:#fb923c!important; }
        body.dark .stats-card[data-metric="sales"] p { color:#fed7aa!important; }
        body.dark .stats-card[data-metric="appointments"] { background:linear-gradient(135deg,#1e3a8a,#172554); box-shadow:0 4px 18px -2px rgba(59,130,246,0.35); }
        body.dark .stats-card[data-metric="appointments"] .metric-number { color:#93c5fd!important; }
        body.dark .stats-card[data-metric="appointments"] p { color:#bfdbfe!important; }
        body.dark .stats-card[data-metric="sitters"] { background:linear-gradient(135deg,#064e3b,#022c22); box-shadow:0 4px 18px -2px rgba(16,185,129,0.35); }
        body.dark .stats-card[data-metric="sitters"] .metric-number { color:#6ee7b7!important; }
        body.dark .stats-card[data-metric="sitters"] p { color:#a7f3d0!important; }
        body.dark .stats-card[data-metric="owners"] { background:linear-gradient(135deg,#4c1d95,#312e81); box-shadow:0 4px 18px -2px rgba(139,92,246,0.35); }
        body.dark .stats-card[data-metric="owners"] .metric-number { color:#c4b5fd!important; }
        body.dark .stats-card[data-metric="owners"] p { color:#ddd6fe!important; }
        body.dark .stats-card[data-metric="active"] { background:linear-gradient(135deg,#831843,#500724); box-shadow:0 4px 18px -2px rgba(244,63,94,0.35); }
        body.dark .stats-card[data-metric="active"] .metric-number { color:#fda4af!important; }
        body.dark .stats-card[data-metric="active"] p { color:#fecdd3!important; }
        body.dark .stats-card .icon-circle { box-shadow:0 0 0 2px rgba(255,255,255,0.05),0 4px 10px -2px rgba(0,0,0,0.6); }
        /* Toggle */
        .dark-toggle-btn { border:1px solid #e2e8f0; }
        body.dark .dark-toggle-btn { border-color:#334155; background:#1e293b; color:#f1f5f9; }
        body.dark .dark-toggle-btn:hover { background:#334155; }

        /* Smooth scaling/transition for sidebar + content shift */
        .sidebar-transition {
            transition: width 0.22s ease, transform 0.22s ease, box-shadow 0.22s ease;
            transform-origin: left center;
            will-change: width, transform;
        }
        .sidebar-transition:hover {
            transform: scaleX(1.01);
            box-shadow: 0 8px 24px rgba(0,0,0,0.06);
        }
        .content-transition {
            transition: margin-left 0.22s ease;
        }

        /* Smooth hover for sidebar nav */
        .sidebar-item {
            position: relative;
            overflow: hidden;
            transition: background-color 0.25s ease, color 0.25s ease, transform 0.2s ease;
        }
        .sidebar-item i {
            transition: transform 0.2s ease, color 0.25s ease;
        }
        .sidebar-item::before {
            content: '';
            position: absolute;
            left: 0.375rem; /* ~px-1.5 from left edge */
            top: 18%;
            bottom: 18%;
            width: 3px;
            border-radius: 9999px;
            background: linear-gradient(180deg, #f97316, #f59e0b); /* orange-500 to amber-500 */
            transform: scaleY(0);
            transform-origin: top;
            transition: transform 0.25s ease;
        }
        .sidebar-item:hover {
            background-color: #fff7ed; /* orange-50 */
            color: #ea580c; /* orange-600 */
        }
        .sidebar-item:hover::before {
            transform: scaleY(1);
        }
        .sidebar-item:hover i {
            color: #ea580c; /* orange-600 */
            transform: translateX(2px);
        }
        /* Preserve active state styling set via JS: show accent bar */
        .sidebar-item.bg-gradient-to-r::before {
            transform: scaleY(1);
            background: linear-gradient(180deg, #f97316, #f59e0b);
        }

         /* Existing styles omitted for brevity ... */
        /* --- Dark Mode Badge Readability Enhancements --- */
        body.dark .badge, body.dark .status-badge { filter:none; }
        body.dark .badge-green, body.dark .status-active { background:rgba(16,185,129,0.18); color:#6ee7b7; border:1px solid rgba(16,185,129,0.35); }
        body.dark .badge-red, body.dark .status-inactive { background:rgba(244,63,94,0.16); color:#fda4af; border:1px solid rgba(244,63,94,0.35); }
        body.dark .badge-amber { background:rgba(245,158,11,0.18); color:#fcd34d; border:1px solid rgba(245,158,11,0.35); }
        body.dark .badge-blue { background:rgba(59,130,246,0.18); color:#93c5fd; border:1px solid rgba(59,130,246,0.35); }
        body.dark .badge-purple { background:rgba(139,92,246,0.18); color:#d8b4fe; border:1px solid rgba(139,92,246,0.35); }
        body.dark .badge-rose { background:rgba(244,114,182,0.18); color:#f9a8d4; border:1px solid rgba(244,114,182,0.35); }
    /* --- Pet Sitter Specialty Badges (Dark Mode Readability) --- */
    /* Add explicit overrides for all Tailwind utility classes used by specClass() so text is pastel & background translucent. */
    body.dark .bg-orange-100 { background:rgba(249,115,22,0.18)!important; }
    body.dark .text-orange-800 { color:#fdba74!important; }
    body.dark .border-orange-200 { border-color:rgba(249,115,22,0.40)!important; }
    body.dark .bg-purple-100 { background:rgba(139,92,246,0.18)!important; }
    body.dark .text-purple-800 { color:#d8b4fe!important; }
    body.dark .border-purple-200 { border-color:rgba(139,92,246,0.40)!important; }
    body.dark .bg-blue-100 { background:rgba(59,130,246,0.18)!important; }
    body.dark .text-blue-800 { color:#93c5fd!important; }
    body.dark .border-blue-200 { border-color:rgba(59,130,246,0.40)!important; }
    body.dark .bg-cyan-100 { background:rgba(6,182,212,0.20)!important; }
    body.dark .text-cyan-800 { color:#a5f3fc!important; }
    body.dark .border-cyan-200 { border-color:rgba(6,182,212,0.45)!important; }
    body.dark .bg-emerald-100 { background:rgba(16,185,129,0.22)!important; }
    body.dark .text-emerald-800 { color:#6ee7b7!important; }
    body.dark .border-emerald-200 { border-color:rgba(16,185,129,0.45)!important; }
    body.dark .bg-pink-100 { background:rgba(236,72,153,0.20)!important; }
    body.dark .text-pink-800 { color:#f9a8d4!important; }
    body.dark .border-pink-200 { border-color:rgba(236,72,153,0.45)!important; }
    body.dark .bg-yellow-100 { background:rgba(250,204,21,0.20)!important; }
    body.dark .text-yellow-800 { color:#fde68a!important; }
    body.dark .border-yellow-200 { border-color:rgba(250,204,21,0.45)!important; }
    body.dark .bg-lime-100 { background:rgba(132,204,22,0.22)!important; }
    body.dark .text-lime-800 { color:#bef264!important; }
    body.dark .border-lime-200 { border-color:rgba(132,204,22,0.45)!important; }
    body.dark .bg-amber-100 { background:rgba(217,119,6,0.22)!important; }
    body.dark .text-amber-800 { color:#fcd34d!important; }
    body.dark .border-amber-200 { border-color:rgba(217,119,6,0.45)!important; }
    body.dark .bg-rose-100 { background:rgba(244,63,94,0.20)!important; }
    body.dark .text-rose-800 { color:#fda4af!important; }
    body.dark .border-rose-200 { border-color:rgba(244,63,94,0.45)!important; }
    body.dark .bg-indigo-100 { background:rgba(99,102,241,0.22)!important; }
    body.dark .text-indigo-800 { color:#c7d2fe!important; }
    body.dark .border-indigo-200 { border-color:rgba(99,102,241,0.50)!important; }
    body.dark .bg-gray-100 { background:rgba(71,85,105,0.35)!important; }
    body.dark .text-gray-800 { color:#e2e8f0!important; }
    body.dark .border-gray-200 { border-color:#475569!important; }
    .specialty-badge { font-weight:500; letter-spacing:.25px; }
    body.dark .specialty-badge { box-shadow:0 0 0 1px rgba(255,255,255,0.03), 0 1px 2px rgba(0,0,0,0.4); }
    /* Additional generic light badge utility remaps for dark mode readability */
    body.dark .bg-yellow-100 { background:rgba(245,158,11,0.18)!important; }
    body.dark .text-yellow-700 { color:#fcd34d!important; }
    body.dark .bg-red-100 { background:rgba(239,68,68,0.20)!important; }
    body.dark .text-red-700 { color:#fca5a5!important; }
    body.dark .border-red-200 { border-color:rgba(239,68,68,0.40)!important; }
    body.dark .bg-indigo-100 { background:rgba(99,102,241,0.20)!important; }
    body.dark .text-indigo-700 { color:#a5b4fc!important; }
    body.dark .border-indigo-200 { border-color:rgba(99,102,241,0.45)!important; }
    body.dark .bg-sky-100 { background:rgba(14,165,233,0.22)!important; }
    body.dark .text-sky-700 { color:#7dd3fc!important; }
    body.dark .border-sky-200 { border-color:rgba(14,165,233,0.45)!important; }
    body.dark .bg-emerald-100 { background:rgba(16,185,129,0.22)!important; }
    body.dark .text-emerald-700 { color:#6ee7b7!important; }
    body.dark .border-emerald-200 { border-color:rgba(16,185,129,0.40)!important; }

    /* Order status semantic classes (light) */
    .order-status { display:inline-flex; align-items:center; gap:4px; font-weight:500; font-size:11px; letter-spacing:.25px; padding:4px 10px; border-radius:9999px; border:1px solid transparent; text-transform:capitalize; }
    .order-status-processing { background:#fefce8; border-color:#fde68a; color:#b45309; }
    .order-status-out_for_delivery { background:#eef2ff; border-color:#c7d2fe; color:#4338ca; }
    .order-status-delivered { background:#ecfdf5; border-color:#a7f3d0; color:#047857; }
    .order-status-cancelled { background:#fef2f2; border-color:#fecaca; color:#b91c1c; }
    .order-status-pending { background:#f1f5f9; border-color:#cbd5e1; color:#475569; }
    /* Dark variants */
    body.dark .order-status-processing { background:rgba(245,158,11,0.18); border-color:rgba(245,158,11,0.45); color:#fcd34d; }
    body.dark .order-status-out_for_delivery { background:rgba(99,102,241,0.22); border-color:rgba(99,102,241,0.45); color:#c7d2fe; }
    body.dark .order-status-delivered { background:rgba(16,185,129,0.22); border-color:rgba(16,185,129,0.45); color:#6ee7b7; }
    body.dark .order-status-cancelled { background:rgba(239,68,68,0.22); border-color:rgba(239,68,68,0.45); color:#fca5a5; }
    body.dark .order-status-pending { background:rgba(71,85,105,0.35); border-color:#475569; color:#cbd5e1; }
        body.dark table thead th { background:#1e293b; color:#e2e8f0; }
        body.dark table tbody tr { border-color:#24324b; }
        body.dark .table-divider { border-color:#24324b; }

        /* Generic auto badge classes if existing markup uses utility backgrounds */
        body.dark .bg-green-50 { background:rgba(16,185,129,0.12)!important; }
        body.dark .text-green-700 { color:#6ee7b7!important; }
    /* Status badge (Active) uses bg-green-100 text-green-700 border-green-200 in light; give a clearer dark variant */
    body.dark .bg-green-100 { background:rgba(16,185,129,0.22)!important; }
    body.dark .border-green-200 { border-color:rgba(16,185,129,0.40)!important; }
    /* Status badge (Inactive) often bg-gray-100 text-gray-600 border-gray-200 */
    body.dark .bg-gray-100 { background:rgba(75,85,99,0.35)!important; }
    body.dark .text-gray-600 { color:#cbd5e1!important; }
    body.dark .border-gray-200 { border-color:#475569!important; }
        body.dark .bg-indigo-50 { background:rgba(99,102,241,0.12)!important; }
        body.dark .text-indigo-700 { color:#a5b4fc!important; }
        body.dark .bg-amber-50 { background:rgba(245,158,11,0.12)!important; }
        body.dark .text-amber-700 { color:#fcd34d!important; }
        body.dark .bg-sky-50 { background:rgba(14,165,233,0.15)!important; }
        body.dark .text-sky-700 { color:#7dd3fc!important; }
        body.dark .bg-rose-50 { background:rgba(244,114,182,0.15)!important; }
        body.dark .text-rose-700 { color:#f9a8d4!important; }
        body.dark .bg-purple-50 { background:rgba(139,92,246,0.14)!important; }
        body.dark .text-purple-700 { color:#d8b4fe!important; }

       

        @keyframes 
        fadeInUp { 
            from { opacity:0; transform: translateY(4px);} 
            to { opacity:1; transform: translateY(0);} }
            
        .animate-fade-in { animation: fadeInUp .25s ease-out; transition: opacity .25s, transform .25s; }

        /* --- Dark Mode: Make date/time inputs readable (appointments & promos edit) --- */
        /* Target common date/time inputs used across admin (filters + modals) */
        body.dark input[type="date"],
        body.dark input[type="time"],
        body.dark input[type="datetime-local"],
        body.dark select {
            background-color: #1f2937; /* slate-800 */
            color: #e2e8f0;            /* slate-200 */
            border-color: #334155;     /* slate-700 */
        }
        body.dark input[type="date"]::placeholder,
        body.dark input[type="time"]::placeholder,
        body.dark input[type="datetime-local"]::placeholder {
            color: #94a3b8;           /* slate-400 */
        }
        body.dark input[type="date"]:focus,
        body.dark input[type="time"]:focus,
        body.dark input[type="datetime-local"]:focus,
        body.dark select:focus {
            outline: none;
            box-shadow: 0 0 0 2px rgba(249, 115, 22, 0.35); /* orange-500 ring */
            border-color: #f97316;
        }
        /* Calendar/time pickers (webkit) internal text color */
        body.dark input[type="date"]::-webkit-datetime-edit,
        body.dark input[type="time"]::-webkit-datetime-edit,
        body.dark input[type="datetime-local"]::-webkit-datetime-edit {
            color: #e2e8f0;
        }
        body.dark input[type="date"]::-webkit-calendar-picker-indicator,
        body.dark input[type="time"]::-webkit-calendar-picker-indicator,
        body.dark input[type="datetime-local"]::-webkit-calendar-picker-indicator {
            filter: invert(0.85);
        }

        /* --- Dark Mode: Header buttons near profile hover effect --- */
        /* Applies subtle hover on header action buttons (notifications, profile, toggles) */
        body.dark header button,
        body.dark header a.button,
        body.dark header .icon-button,
        body.dark header .dark-toggle-btn {
            transition: background-color .2s ease, color .2s ease, box-shadow .2s ease;
        }
        body.dark header button:hover,
        body.dark header a.button:hover,
        body.dark header .icon-button:hover,
        body.dark header .dark-toggle-btn:hover {
            background-color: #334155; /* slate-700 */
            color: #e2e8f0;            /* readable on dark */
            box-shadow: 0 2px 8px rgba(0,0,0,0.35);
        }
    </style>
       
</head>
<body class="bg-gray-50 font-sans">
    <!-- Admin Dashboard Container -->
    <div class="min-h-screen bg-gray-50 flex">
        <!-- Sidebar -->
        <div id="sidebar" class="fixed left-0 top-0 h-full bg-white border-r border-gray-200 sidebar-transition z-40 w-16" 
             onmouseenter="expandSidebar()" onmouseleave="collapseSidebar()">
            
            <!-- Logo -->
            <div class="h-16 flex items-center justify-center border-b border-gray-200">
                <div id="sidebarLogoExpanded" class="hidden items-center gap-2 px-4">
                    <div class="w-8 h-8 rounded-lg overflow-hidden">
                        <img src="<?php echo htmlspecialchars(resolveImageUrl('pictures/Pawhabilin logo.png')); ?>" alt="Pawhabilin Logo" class="w-full h-full object-cover">
                    </div>
                    <span class="font-semibold text-orange-600">Pawhabilin</span>
                    <span class="text-xs bg-orange-100 text-orange-600 px-2 py-1 rounded-full">Admin</span>
                </div>
                <div id="sidebarLogoCollapsed" class="w-8 h-8 rounded-lg overflow-hidden">
                    <img src="<?php echo htmlspecialchars(resolveImageUrl('pictures/Pawhabilin logo.png')); ?>" alt="Pawhabilin Logo" class="w-full h-full object-cover">
                </div>
            </div>

            <!-- Toggle Lock Button -->
            <div id="sidebarToggle" class="hidden px-4 py-2 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-600">Lock Sidebar</span>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" id="sidebarLock" class="sr-only peer" onchange="toggleSidebarLock()">
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-orange-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-orange-600"></div>
                    </label>
                </div>
            </div>

            <!-- Navigation -->
            <nav class="p-2 space-y-1">
                <button onclick="setActiveSection('dashboard')" class="sidebar-item w-full flex items-center gap-3 px-3 py-2 rounded-lg transition-all duration-200 text-gray-700 hover:bg-gray-100" data-section="dashboard">
                    <i data-lucide="bar-chart-3" class="w-5 h-5 flex-shrink-0"></i>
                    <span class="sidebar-label font-medium hidden">Dashboard</span>
                </button>
                <button onclick="setActiveSection('products')" class="sidebar-item w-full flex items-center gap-3 px-3 py-2 rounded-lg transition-all duration-200 text-gray-700 hover:bg-gray-100" data-section="products">
                    <i data-lucide="package" class="w-5 h-5 flex-shrink-0"></i>
                    <span class="sidebar-label font-medium hidden">Products</span>
                </button>
                <button onclick="setActiveSection('orders')" class="sidebar-item w-full flex items-center gap-3 px-3 py-2 rounded-lg transition-all duration-200 text-gray-700 hover:bg-gray-100" data-section="orders">
                    <i data-lucide="shopping-bag" class="w-5 h-5 flex-shrink-0"></i>
                    <span class="sidebar-label font-medium hidden">Orders</span>
                </button>
                <button onclick="setActiveSection('sitters')" class="sidebar-item w-full flex items-center gap-3 px-3 py-2 rounded-lg transition-all duration-200 text-gray-700 hover:bg-gray-100" data-section="sitters">
                    <i data-lucide="users" class="w-5 h-5 flex-shrink-0"></i>
                    <span class="sidebar-label font-medium hidden">Pet Sitters</span>
                </button>
                <button onclick="setActiveSection('appointments')" class="sidebar-item w-full flex items-center gap-3 px-3 py-2 rounded-lg transition-all duration-200 text-gray-700 hover:bg-gray-100" data-section="appointments">
                    <i data-lucide="calendar" class="w-5 h-5 flex-shrink-0"></i>
                    <span class="sidebar-label font-medium hidden">Appointments</span>
                </button>
                <button onclick="setActiveSection('pets')" class="sidebar-item w-full flex items-center gap-3 px-3 py-2 rounded-lg transition-all duration-200 text-gray-700 hover:bg-gray-100" data-section="pets">
                    <i data-lucide="paw-print" class="w-5 h-5 flex-shrink-0"></i>
                    <span class="sidebar-label font-medium hidden">Pet Owners</span>
                </button>
                <button onclick="setActiveSection('subscribers')" class="sidebar-item w-full flex items-center gap-3 px-3 py-2 rounded-lg transition-all duration-200 text-gray-700 hover:bg-gray-100" data-section="subscribers">
                    <i data-lucide="bell" class="w-5 h-5 flex-shrink-0"></i>
                    <span class="sidebar-label font-medium hidden">Subscribers</span>
                </button>
                <button onclick="setActiveSection('promos')" class="sidebar-item w-full flex items-center gap-3 px-3 py-2 rounded-lg transition-all duration-200 text-gray-700 hover:bg-gray-100" data-section="promos">
                    <i data-lucide="percent" class="w-5 h-5 flex-shrink-0"></i>
                    <span class="sidebar-label font-medium hidden">Promos</span>
                </button>
                <button onclick="setActiveSection('audit')" class="sidebar-item w-full flex items-center gap-3 px-3 py-2 rounded-lg transition-all duration-200 text-gray-700 hover:bg-gray-100" data-section="audit">
                    <i data-lucide="activity" class="w-5 h-5 flex-shrink-0"></i>
                    <span class="sidebar-label font-medium hidden">Audit Logs</span>
                </button>
                <button onclick="setActiveSection('settings')" class="sidebar-item w-full flex items-center gap-3 px-3 py-2 rounded-lg transition-all duration-200 text-gray-700 hover:bg-gray-100" data-section="settings">
                    <i data-lucide="settings" class="w-5 h-5 flex-shrink-0"></i>
                    <span class="sidebar-label font-medium hidden">Settings</span>
                </button>
            </nav>

            <!-- Admin Info -->
            <div id="adminInfo" class="hidden absolute bottom-0 left-0 right-0 p-4 border-t border-gray-200">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 bg-gradient-to-br from-orange-400 to-amber-500 rounded-full flex items-center justify-center text-white font-semibold text-sm overflow-hidden">
                        <?php if (!empty($admin['users_image_url'])): ?>
                            <img src="<?php echo htmlspecialchars($admin['users_image_url']); ?>" alt="Avatar" class="w-full h-full object-cover">
                        <?php else: ?>
                            <?php echo htmlspecialchars($admin_initial); ?>
                        <?php endif; ?>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-900 truncate"><?php echo htmlspecialchars($admin['users_username'] ?: $admin_fullname); ?></p>
                        <p class="text-xs text-gray-500 truncate"><?php echo htmlspecialchars($admin['users_email'] ?? ''); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
    <div id="mainContent" class="flex-1 content-transition ml-16 h-screen overflow-y-auto">
            <!-- Top Header -->
            <header class="h-16 bg-white border-b border-gray-200 flex items-center justify-between px-6">
                <div class="flex items-center gap-4">
                    <button onclick="toggleSidebarLock()" class="lg:hidden p-2 rounded-md hover:bg-gray-100">
                        <i data-lucide="menu" class="w-4 h-4"></i>
                    </button>
                    <div class="relative w-80">
                        <i data-lucide="search" class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 w-4 h-4"></i>
                        <input type="text" placeholder="Search anything..." class="w-full pl-10 pr-4 py-2 bg-gray-50 border border-gray-200 rounded-md focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent">
                    </div>
                </div>

                <div class="flex items-center gap-4">
                    <button id="notificationsBtn" class="relative p-2 rounded-md hover:bg-gray-100" title="Recent Activity">
                        <i data-lucide="bell" class="w-4 h-4"></i>
                        <div id="activityBadge" class="absolute -top-1 -right-1 w-3 h-3 bg-red-500 rounded-full text-xs flex items-center justify-center text-white font-semibold hidden"></div>
                    </button>
                    <!-- Quick Dark Mode Toggle (synchronized with Settings + Section button) -->
                    <button id="darkModeQuickBtn" class="p-2 rounded-md hover:bg-gray-100" title="Toggle dark mode">
                        <i data-lucide="moon" class="w-4 h-4"></i>
                    </button>
                    <button id="openSettingsBtn" class="p-2 rounded-md hover:bg-gray-100" title="Go to Settings">
                        <i data-lucide="settings" class="w-4 h-4"></i>
                    </button>
                    <div id="profileMenuWrapper" class="relative">
                        <button id="profileButton" class="w-8 h-8 rounded-full flex items-center justify-center text-white font-semibold focus:outline-none bg-gradient-to-br from-orange-400 to-amber-500 overflow-hidden">
                            <?php if (!empty($admin['users_image_url'])): ?>
                                <img src="<?php echo htmlspecialchars(resolveImageUrl($admin['users_image_url'])); ?>" alt="Avatar" class="w-full h-full object-cover object-center">
                            <?php else: ?>
                                <?php echo htmlspecialchars($admin_initial); ?>
                            <?php endif; ?>
                        </button>
                        <div id="profileMenu" class="profile-menu hidden absolute right-0 mt-2 w-64 bg-white border border-gray-200 rounded-lg shadow-lg z-50">
                            <div class="p-4 border-b border-gray-100 flex items-center gap-3">
                                <div class="w-10 h-10 rounded-full overflow-hidden bg-gray-200 flex items-center justify-center">
                                    <?php if (!empty($admin['users_image_url'])): ?>
                                        <img src="<?php echo htmlspecialchars(resolveImageUrl($admin['users_image_url'])); ?>" alt="Admin Avatar" class="w-full h-full object-cover object-center">
                                    <?php else: ?>
                                        <span class="text-sm font-semibold text-gray-700"><?php echo htmlspecialchars($admin_initial); ?></span>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <p class="text-sm font-semibold text-gray-900"><?php echo htmlspecialchars($admin_fullname); ?></p>
                                    <p class="text-xs text-gray-600"><?php echo htmlspecialchars($admin['users_email'] ?? ''); ?></p>
                                </div>
                            </div>
                            <div class="p-2">
                                <a href="../users/logout.php" class="block w-full text-left px-3 py-2 rounded-md text-red-600 hover:bg-red-50">Logout</a>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Recent Activity Panel -->
            <div id="recentActivityPanel" class="hidden absolute right-6 top-16 mt-2 w-96 bg-white dark:bg-[#1f2937] border border-gray-200 dark:border-[#334155] rounded-lg shadow-xl z-50 overflow-hidden">
                <div class="flex items-center justify-between px-4 py-3 border-b border-gray-200 dark:border-[#334155] bg-gray-50 dark:bg-[#1e293b]">
                    <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-200">Recent Activity</h3>
                    <button id="closeActivityBtn" class="p-1 rounded hover:bg-gray-100 dark:hover:bg-[#334155]">
                        <i data-lucide="x" class="w-4 h-4"></i>
                    </button>
                </div>
                <div id="activityList" class="max-h-80 overflow-y-auto divide-y divide-gray-100 dark:divide-[#334155]">
                    <!-- Filled dynamically -->
                </div>
                <div class="px-4 py-2 text-xs text-gray-500 dark:text-gray-400 bg-gray-50 dark:bg-[#1e293b] flex justify-between items-center">
                    <span id="activityMeta">0 items</span>
                    <button id="clearActivityBtn" class="text-orange-600 hover:text-orange-700 dark:text-orange-400 dark:hover:text-orange-300 font-medium">Clear</button>
                </div>
            </div>

            <!-- Page Content -->
            <main class="p-6">
                <!-- Dashboard Section -->
                <div id="dashboard-section" class="space-y-6">
                    <!-- Dashboard Header -->
                    <div class="flex items-center justify-between">
                        <div>
                            <h1 class="text-3xl font-bold bg-gradient-to-r from-orange-600 to-amber-600 bg-clip-text text-transparent">
                                Dashboard Overview
                            </h1>
                            <p class="text-gray-600 mt-1">
                                Welcome back! Here's what's happening with pawhabilin today.
                            </p>
                        </div>
                        <div class="flex items-center gap-3">
                            <!-- Section Header Dark Mode Button (synchronized) -->
                            <button id="darkModeSectionBtn" type="button" class="dark-toggle-btn flex items-center gap-2 px-3 py-2 rounded-md text-sm font-medium bg-white hover:bg-gray-100 text-gray-700 transition" title="Toggle dark mode">
                                <i data-lucide="moon" class="w-4 h-4"></i>
                                <span class="hidden sm:inline mode-label">Dark</span>
                            </button>
                            <select id="timeFilter" onchange="updateTimeFilter()" class="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-orange-500">
                                <option value="daily">Daily</option>
                                <option value="weekly">Weekly</option>
                                <option value="monthly" selected>Monthly</option>
                                <option value="annual">Annual</option>
                            </select>
                            <button class="bg-gradient-to-r from-orange-500 to-amber-600 hover:from-orange-600 hover:to-amber-700 text-white px-4 py-2 rounded-md flex items-center gap-2 transition-all duration-200">
                                <i data-lucide="download" class="w-4 h-4"></i>
                                Export
                            </button>
                        </div>
                    </div>

                    <!-- Stats Cards (Dynamic) -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-6">
                        <!-- Total Sales -->
                        <div id="totalSalesCard" data-metric="sales" class="cursor-pointer stats-card bg-gradient-to-br from-orange-50 to-amber-50 border border-orange-200 rounded-lg relative overflow-hidden hover:shadow-md transition-shadow" title="View top selling products">
                            <div class="p-6 space-y-2">
                                <div class="flex items-start justify-between">
                                    <div>
                                        <p class="text-xs font-semibold tracking-wide text-orange-600">TOTAL SALES</p>
                                        <p class="text-2xl font-bold text-orange-700 metric-number">₱<?php echo number_format($kpi['total_sales'],2); ?></p>
                                    </div>
                                    <div class="w-12 h-12 bg-orange-500 rounded-full flex items-center justify-center shadow-inner">
                                        <i data-lucide="dollar-sign" class="w-6 h-6 text-white"></i>
                                    </div>
                                </div>
                                <p class="text-[11px] text-orange-600">Sum of all transactions</p>
                            </div>
                        </div>
                        <!-- Appointments Booked -->
                        <div class="stats-card bg-gradient-to-br from-blue-50 to-indigo-50 border border-blue-200 rounded-lg relative overflow-hidden" data-metric="appointments">
                            <div class="p-6 space-y-2">
                                <div class="flex items-start justify-between">
                                    <div>
                                        <p class="text-xs font-semibold tracking-wide text-blue-600">APPOINTMENTS BOOKED</p>
                                        <p class="text-2xl font-bold text-blue-700 metric-number"><?php echo number_format($kpi['appointments']); ?></p>
                                    </div>
                                    <div class="w-12 h-12 bg-blue-500 rounded-full flex items-center justify-center shadow-inner">
                                        <i data-lucide="calendar-check" class="w-6 h-6 text-white"></i>
                                    </div>
                                </div>
                                <p class="text-[11px] text-blue-600">All time total</p>
                            </div>
                        </div>
                        <!-- Pet Sitters -->
                        <div class="stats-card bg-gradient-to-br from-green-50 to-emerald-50 border border-green-200 rounded-lg relative overflow-hidden" data-metric="sitters">
                            <div class="p-6 space-y-2">
                                <div class="flex items-start justify-between">
                                    <div>
                                        <p class="text-xs font-semibold tracking-wide text-green-600">PET SITTERS</p>
                                        <p class="text-2xl font-bold text-green-700 metric-number"><?php echo number_format($kpi['sitters']); ?></p>
                                    </div>
                                    <div class="w-12 h-12 bg-green-500 rounded-full flex items-center justify-center shadow-inner">
                                        <i data-lucide="users" class="w-6 h-6 text-white"></i>
                                    </div>
                                </div>
                                <p class="text-[11px] text-green-600">Active providers</p>
                            </div>
                        </div>
                        <!-- Pet Owners -->
                        <div class="stats-card bg-gradient-to-br from-purple-50 to-violet-50 border border-purple-200 rounded-lg relative overflow-hidden" data-metric="owners">
                            <div class="p-6 space-y-2">
                                <div class="flex items-start justify-between">
                                    <div>
                                        <p class="text-xs font-semibold tracking-wide text-purple-600">PET OWNERS</p>
                                        <p class="text-2xl font-bold text-purple-700 metric-number"><?php echo number_format($kpi['owners']); ?></p>
                                    </div>
                                    <div class="w-12 h-12 bg-purple-500 rounded-full flex items-center justify-center shadow-inner">
                                        <i data-lucide="paw-print" class="w-6 h-6 text-white"></i>
                                    </div>
                                </div>
                                <p class="text-[11px] text-purple-600">Registered users</p>
                            </div>
                        </div>
                        <!-- Active Users -->
                        <div class="stats-card bg-gradient-to-br from-rose-50 to-pink-50 border border-rose-200 rounded-lg relative overflow-hidden" data-metric="active">
                            <div class="p-6 space-y-2">
                                <div class="flex items-start justify-between">
                                    <div>
                                        <p class="text-xs font-semibold tracking-wide text-rose-600">ACTIVE USERS (30D)</p>
                                        <p class="text-2xl font-bold text-rose-700 metric-number"><?php echo number_format($kpi['active_users']); ?></p>
                                    </div>
                                    <div class="w-12 h-12 bg-rose-500 rounded-full flex items-center justify-center shadow-inner">
                                        <i data-lucide="user-check" class="w-6 h-6 text-white"></i>
                                    </div>
                                </div>
                                <p class="text-[11px] text-rose-600">Recent engaged users</p>
                            </div>
                        </div>
                    </div>

                    <!-- Charts and Recent Activity -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <!-- Revenue Chart -->
                        <div class="bg-white rounded-lg border border-gray-200">
                            <div class="p-6 border-b border-gray-200">
                                <h3 class="text-lg font-semibold">Revenue Trends</h3>
                                <p class="text-sm text-gray-600">Monthly revenue overview</p>
                            </div>
                            <div class="p-6">
                                <div id="revenueChart" class="h-64 flex items-end justify-between space-x-2">
                                    <!-- Chart bars will be generated by JavaScript -->
                                </div>
                            </div>
                        </div>

                        <!-- Recent Activity -->
                        <div class="bg-white rounded-lg border border-gray-200">
                            <div class="p-6 border-b border-gray-200">
                                <h3 class="text-lg font-semibold">Recent Activity</h3>
                                <p class="text-sm text-gray-600">Latest platform activities</p>
                            </div>
                            <div class="p-6">
                                <div class="space-y-4">
                                    <div class="flex items-center gap-4 p-3 rounded-lg bg-gray-50">
                                        <div class="w-8 h-8 rounded-full flex items-center justify-center bg-orange-100 text-orange-600">
                                            <i data-lucide="paw-print" class="w-4 h-4"></i>
                                        </div>
                                        <div class="flex-1">
                                            <p class="text-sm font-medium">Pet sitting booking</p>
                                            <p class="text-xs text-gray-600">John Doe - Buddy</p>
                                        </div>
                                        <span class="px-2 py-1 bg-blue-100 text-blue-800 text-xs rounded-full">confirmed</span>
                                    </div>
                                    <div class="flex items-center gap-4 p-3 rounded-lg bg-gray-50">
                                        <div class="w-8 h-8 rounded-full flex items-center justify-center bg-blue-100 text-blue-600">
                                            <i data-lucide="heart" class="w-4 h-4"></i>
                                        </div>
                                        <div class="flex-1">
                                            <p class="text-sm font-medium">Grooming booking</p>
                                            <p class="text-xs text-gray-600">Jane Smith - Whiskers</p>
                                        </div>
                                        <span class="px-2 py-1 bg-yellow-100 text-yellow-800 text-xs rounded-full">pending</span>
                                    </div>
                                    <div class="flex items-center gap-4 p-3 rounded-lg bg-gray-50">
                                        <div class="w-8 h-8 rounded-full flex items-center justify-center bg-green-100 text-green-600">
                                            <i data-lucide="activity" class="w-4 h-4"></i>
                                        </div>
                                        <div class="flex-1">
                                            <p class="text-sm font-medium">Vet booking</p>
                                            <p class="text-xs text-gray-600">Mike Johnson - Max</p>
                                        </div>
                                        <span class="px-2 py-1 bg-gray-100 text-gray-800 text-xs rounded-full">completed</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Products Section -->
                <div id="products-section" class="space-y-6 hidden">
                    <div class="flex items-center justify-between">
                        <div>
                            <h1 class="text-3xl font-bold">Products Management</h1>
                            <p class="text-gray-600 mt-1">Manage your pet care products and inventory</p>
                        </div>
                        <button onclick="openAddProductModal()" class="bg-gradient-to-r from-orange-500 to-amber-600 hover:from-orange-600 hover:to-amber-700 text-white px-4 py-2 rounded-md flex items-center gap-2">
                            <i data-lucide="plus" class="w-4 h-4"></i>
                            Add Product
                        </button>
                    </div>

                    <div class="bg-white rounded-lg border border-gray-200">
                        <div class="p-6 border-b border-gray-200">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <h3 class="text-lg font-semibold">Product Inventory</h3>
                                    <p class="text-sm text-gray-600">Filter by pet types, category, status and stock</p>
                                </div>
                                <div class="flex items-center gap-3">
                                    <div class="relative">
                                        <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 w-4 h-4"></i>
                                        <input id="productsSearch" type="text" placeholder="Search products..." class="pl-9 pr-3 py-2 border border-gray-300 rounded-md w-72" />
                                    </div>
                                </div>
                            </div>
                            <div class="mt-4 grid grid-cols-1 lg:grid-cols-4 gap-4">
                                <div>
                                    <p class="text-xs font-medium text-gray-500 mb-2">Pet Types</p>
                                    <div class="flex flex-wrap gap-3 text-sm">
                                        <label class="inline-flex items-center gap-2"><input type="checkbox" class="form-checkbox" name="petType" value="Dog"> Dog</label>
                                        <label class="inline-flex items-center gap-2"><input type="checkbox" class="form-checkbox" name="petType" value="Cat"> Cat</label>
                                        <label class="inline-flex items-center gap-2"><input type="checkbox" class="form-checkbox" name="petType" value="Bird"> Bird</label>
                                        <label class="inline-flex items-center gap-2"><input type="checkbox" class="form-checkbox" name="petType" value="Fish"> Fish</label>
                                        <label class="inline-flex items-center gap-2"><input type="checkbox" class="form-checkbox" name="petType" value="Small Pet"> Small Pet</label>
                                    </div>
                                </div>
                                <div>
                                    <p class="text-xs font-medium text-gray-500 mb-2">Category</p>
                                    <div class="flex flex-wrap gap-3 text-sm">
                                        <label class="inline-flex items-center gap-2"><input type="checkbox" class="form-checkbox" name="category" value="food"> Food</label>
                                        <label class="inline-flex items-center gap-2"><input type="checkbox" class="form-checkbox" name="category" value="accessory"> Accessories</label>
                                        <label class="inline-flex items-center gap-2"><input type="checkbox" class="form-checkbox" name="category" value="necessity"> Necessity</label>
                                        <label class="inline-flex items-center gap-2"><input type="checkbox" class="form-checkbox" name="category" value="toy"> Toys</label>
                                    </div>
                                </div>
                                <div>
                                    <p class="text-xs font-medium text-gray-500 mb-2">Status</p>
                                    <div class="flex flex-wrap gap-3 text-sm">
                                        <label class="inline-flex items-center gap-2"><input type="checkbox" class="form-checkbox" name="active" value="1"> Active</label>
                                        <label class="inline-flex items-center gap-2"><input type="checkbox" class="form-checkbox" name="active" value="0"> Inactive</label>
                                    </div>
                                </div>
                                <div>
                                    <p class="text-xs font-medium text-gray-500 mb-2">Stock</p>
                                    <div class="flex flex-wrap gap-3 text-sm">
                                        <label class="inline-flex items-center gap-2"><input type="checkbox" class="form-checkbox" name="stock" value="in"> In stock (>=1)</label>
                                        <label class="inline-flex items-center gap-2"><input type="checkbox" class="form-checkbox" name="stock" value="out"> Out of stock (0)</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead class="bg-gray-50 border-b">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Price</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Stock</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="productsTableBody" class="bg-white divide-y divide-gray-200">
                                    <?php
                                    // Server-render products from DB
                                    $rows = [];
                                    if (isset($connections) && $connections) {
                                        $q = "SELECT products_id, products_name, products_pet_type, products_category, products_price, products_stock, products_image_url, products_active FROM products ORDER BY products_created_at DESC, products_id DESC";
                                        if ($res = mysqli_query($connections, $q)) {
                                            while ($r = mysqli_fetch_assoc($res)) { $rows[] = $r; }
                                            mysqli_free_result($res);
                                        }
                                    }
                                    $catLabel = function($c){
                                        switch ($c) {
                                            case 'food': return 'Food';
                                            case 'accessory': return 'Accessories';
                                            case 'necessity': return 'Grooming';
                                            case 'toy': return 'Treats';
                                            default: return htmlspecialchars((string)$c);
                                        }
                                    };
                                    if (empty($rows)):
                                    ?>
                                        <tr><td colspan="6" class="px-6 py-6 text-center text-gray-500">No products found.</td></tr>
                                    <?php else: foreach ($rows as $p): ?>
                                        <tr data-id="<?php echo (int)$p['products_id']; ?>"
                                            data-name="<?php echo htmlspecialchars(strtolower($p['products_name'])); ?>"
                                            data-pet-type="<?php echo htmlspecialchars($p['products_pet_type'] ?? ''); ?>"
                                            data-category="<?php echo htmlspecialchars($p['products_category'] ?? ''); ?>"
                                            data-active="<?php echo (int)($p['products_active'] ?? 0); ?>"
                                            data-stock="<?php echo is_numeric($p['products_stock']) ? (int)$p['products_stock'] : 0; ?>">
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="flex items-center gap-3">
                                                    <div class="w-10 h-10 rounded-lg overflow-hidden bg-gray-100 flex items-center justify-center">
                                                        <?php if (!empty($p['products_image_url'])): ?>
                                                            <img src="<?php echo htmlspecialchars(resolveImageUrl($p['products_image_url'])); ?>" alt="<?php echo htmlspecialchars($p['products_name']); ?>" class="w-full h-full object-cover">
                                                        <?php else: ?>
                                                            <i data-lucide="image" class="w-4 h-4 text-gray-400"></i>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div>
                                                        <p class="font-medium"><?php echo htmlspecialchars($p['products_name']); ?></p>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo $catLabel($p['products_category']); ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">₱<?php echo number_format((float)$p['products_price'], 2); ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars((string)$p['products_stock']); ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="px-2 py-1 text-xs rounded-full <?php echo (int)$p['products_active'] === 1 ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                                    <?php echo (int)$p['products_active'] === 1 ? 'Active' : 'Inactive'; ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <div class="flex items-center gap-2">
                                                    <button class="p-1 text-gray-400 hover:text-gray-600 btn-edit" data-action="edit" title="Edit">
                                                        <i data-lucide="edit" class="w-4 h-4"></i>
                                                    </button>
                                                    <button class="p-1 text-red-400 hover:text-red-600 btn-delete" data-action="delete" title="Delete">
                                                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; endif; ?>
                                </tbody>
                            </table>
                        </div>
                        <div id="productsPagination" class="p-4 border-t border-gray-100 flex items-center justify-between hidden">
                            <div id="productsPageInfo" class="text-sm text-gray-600"></div>
                            <div class="flex items-center gap-2">
                                <button id="productsPrev" class="px-3 py-1.5 border border-gray-300 rounded-md text-sm text-gray-700 hover:bg-gray-50">Prev</button>
                                <div id="productsPageNums" class="flex items-center gap-1"></div>
                                <button id="productsNext" class="px-3 py-1.5 border border-gray-300 rounded-md text-sm text-gray-700 hover:bg-gray-50">Next</button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Orders Section (Delivery Only) -->
                <div id="orders-section" class="space-y-6 hidden">
                    <?php
                    $orders = [];
                    $itemsByTxn = [];
                    if(isset($connections) && $connections){
            $sql = "SELECT t.transactions_id, t.users_id, u.users_firstname, u.users_lastname, t.transactions_amount, t.transactions_payment_method, t.transactions_created_at,
                    d.deliveries_delivery_status, d.deliveries_estimated_delivery_date, d.deliveries_actual_delivery_date, d.deliveries_recipient_signature,
                    l.location_address_line1, l.location_address_line2, l.location_city, l.location_province, l.location_barangay, l.location_recipient_name
                                FROM transactions t
                                JOIN users u ON u.users_id = t.users_id
                                LEFT JOIN deliveries d ON d.transactions_id = t.transactions_id
                                LEFT JOIN locations l ON l.location_id = d.location_id
                                WHERE t.transactions_type='product'
                                ORDER BY t.transactions_created_at DESC, t.transactions_id DESC";
                        if($res = mysqli_query($connections,$sql)){
                            while($r = mysqli_fetch_assoc($res)){ $orders[] = $r; }
                            mysqli_free_result($res);
                        }
                        if(!empty($orders)){
                            $ids = array_column($orders,'transactions_id');
                            $idList = implode(',', array_map('intval',$ids));
                            if($idList!==''){
                                $lineSql = "SELECT tp.transactions_id, tp.products_id, tp.tp_quantity, pr.products_name, pr.products_image_url
                                            FROM transaction_products tp
                                            JOIN products pr ON pr.products_id = tp.products_id
                                            WHERE tp.transactions_id IN ($idList)";
                                if($res2 = mysqli_query($connections,$lineSql)){
                                    while($r2 = mysqli_fetch_assoc($res2)){
                                        $tid=(int)$r2['transactions_id'];
                                        if(!isset($itemsByTxn[$tid])) $itemsByTxn[$tid]=[];
                                        $itemsByTxn[$tid][]=$r2;
                                    }
                                    mysqli_free_result($res2);
                                }
                            }
                        }
                    }
                    if(!function_exists('o_e')){ function o_e($v){ return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8'); } }
                    ?>
                    <div>
                        <h2 class="text-xl font-semibold flex items-center gap-2 text-gray-800"><i data-lucide="shopping-bag" class="w-5 h-5 text-orange-500"></i> Product Orders (Delivery)</h2>
                        <p class="text-sm text-gray-500">All product transactions with delivery details.</p>
                    </div>
                    <div class="bg-white border border-gray-200 rounded-lg shadow-sm">
                        <div class="p-4 flex flex-col md:flex-row md:items-center md:justify-between gap-4 border-b border-gray-200">
                            <div class="flex items-center gap-2">
                                <h3 class="font-medium text-gray-700 flex items-center gap-1"><i data-lucide="package" class="w-4 h-4 text-blue-500"></i> Orders (<?php echo count($orders); ?>)</h3>
                            </div>
                            <div class="flex flex-wrap items-center gap-3">
                                <input id="ordersSearch" type="text" placeholder="Search buyer, item, address..." class="px-3 py-2 text-sm border border-gray-300 rounded-md w-64 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <select id="ordersStatusFilter" class="px-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <option value="">All Status</option>
                                    <option value="processing">Processing</option>
                                    <option value="out_for_delivery">Out for Delivery</option>
                                    <option value="delivered">Delivered</option>
                                    <option value="cancelled">Cancelled</option>
                                </select>
                                <select id="ordersPaymentFilter" class="px-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <option value="">All Payments</option>
                                    <option value="cod">COD</option>
                                    <option value="gcash">GCash</option>
                                    <option value="maya">Maya</option>
                                </select>
                            </div>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full text-sm" id="ordersTable">
                                <thead class="bg-gray-50 text-[11px] uppercase text-gray-600">
                                    <tr>
                                        <th class="px-4 py-3 text-left font-medium">Buyer</th>
                                        <th class="px-4 py-3 text-left font-medium">Order Items</th>
                                        <th class="px-4 py-3 text-left font-medium">Address</th>
                                        <th class="px-4 py-3 text-left font-medium">Payment</th>
                                        <th class="px-4 py-3 text-left font-medium">Status</th>
                                        <th class="px-4 py-3 text-left font-medium">Estimated</th>
                                        <th class="px-4 py-3 text-left font-medium">Actual</th>
                                        <th class="px-4 py-3 text-left font-medium">Signature</th>
                                        <th class="px-4 py-3 text-left font-medium">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="ordersTableBody" class="divide-y divide-gray-100">
                                <?php if(empty($orders)): ?>
                                    <tr><td colspan="9" class="px-4 py-6 text-center text-gray-500">No orders found.</td></tr>
                                <?php else: foreach($orders as $ord): $tid=(int)$ord['transactions_id']; $recipient=trim($ord['location_recipient_name'] ?? ''); $buyer= $recipient !== '' ? $recipient : trim(($ord['users_firstname']??'').' '.($ord['users_lastname']??'')); $items=$itemsByTxn[$tid]??[]; $status=$ord['deliveries_delivery_status']??''; $addressParts=array_filter([$ord['location_address_line1']??'', $ord['location_barangay']??'', $ord['location_city']??'', $ord['location_province']??'']); $address=implode(', ',$addressParts); $eta=$ord['deliveries_estimated_delivery_date']??''; ?>
                                    <?php $itemsSearch = strtolower(implode(' ', array_map(fn($x)=>$x['products_name'],$items))); ?>
                                    <tr data-tid="<?php echo $tid; ?>" data-buyer="<?php echo o_e(strtolower($buyer)); ?>" data-status="<?php echo o_e($status); ?>" data-payment="<?php echo o_e(strtolower($ord['transactions_payment_method']??'')); ?>" data-address="<?php echo o_e(strtolower($address)); ?>" data-items="<?php echo o_e($itemsSearch); ?>">
                                        <td class="px-4 py-3 align-top">
                                            <div class="font-medium text-gray-800"><?php echo o_e($buyer ?: 'User #'.$ord['users_id']); ?></div>
                                            <div class="text-[11px] text-gray-500">#<?php echo $tid; ?> • ₱<?php echo number_format((float)$ord['transactions_amount'],2); ?></div>
                                            <div class="text-[10px] text-gray-400"><?php echo o_e(date('Y-m-d H:i',strtotime($ord['transactions_created_at']))); ?></div>
                                        </td>
                                        <td class="px-4 py-3 align-top">
                                            <ul class="space-y-1 max-w-[170px]">
                                                <?php foreach($items as $it): ?>
                                                    <li class="flex items-center gap-2 text-xs">
                                                        <span class="text-gray-700 truncate" title="<?php echo o_e($it['products_name']); ?>"><?php echo o_e($it['products_name']); ?></span>
                                                        <span class="text-gray-400">x<?php echo (int)$it['tp_quantity']; ?></span>
                                                    </li>
                                                <?php endforeach; ?>
                                            </ul>
                                        </td>
                                        <td class="px-4 py-3 text-xs max-w-[220px] truncate" title="<?php echo o_e($address); ?>"><?php echo o_e($address); ?></td>
                                        <td class="px-4 py-3 text-xs"><span class="px-2 py-1 rounded-full bg-orange-50 text-orange-600"><?php echo o_e(strtoupper($ord['transactions_payment_method']??'')); ?></span></td>
                                        <td class="px-4 py-3 text-xs"><?php if($status): ?><span class="px-2 py-1 rounded-full bg-gray-100 text-gray-700"><?php echo o_e(ucwords(str_replace('_',' ',$status))); ?></span><?php endif; ?></td>
                                        <td class="px-4 py-3 text-xs text-gray-700"><?php echo o_e($eta); ?></td>
                                        <td class="px-4 py-3 text-xs text-gray-700"><?php echo o_e($ord['deliveries_actual_delivery_date'] ?? ''); ?></td>
                                        <td class="px-4 py-3 text-xs text-gray-700">
                                            <?php
                                            $sig = $ord['deliveries_recipient_signature'] ?? '';
                                            $st  = strtolower($ord['deliveries_delivery_status'] ?? '');
                                            if ($sig) {
                                                echo '<span class="text-emerald-600 font-semibold">Received</span>';
                                            } elseif ($st === 'cancelled') {
                                                echo '<span class="text-red-600 font-semibold">Cancelled</span>';
                                            } else {
                                                echo '<span class="text-gray-400">Pending</span>';
                                            }
                                            ?>
                                        </td>
                                        <td class="px-4 py-3 text-xs">
                                            <div class="flex items-center gap-2">
                                                <button type="button" class="text-blue-600 hover:text-blue-700 order-edit-btn" data-id="<?php echo $tid; ?>" title="Edit"><i data-lucide="edit" class="w-4 h-4"></i></button>
                                                <form method="post" action="../../controllers/admin/ordercontroller.php" class="inline order-delete-form" onsubmit="return false;" data-id="<?php echo $tid; ?>">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="transactions_id" value="<?php echo $tid; ?>">
                                                    <button class="text-red-600 hover:text-red-700" title="Delete"><i data-lucide="trash" class="w-4 h-4"></i></button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; endif; ?>
                                </tbody>
                            </table>
                        </div>
                        <div id="ordersPagination" class="p-3 border-t border-gray-100 flex items-center justify-between hidden">
                            <div id="ordersPageInfo" class="text-xs text-gray-600"></div>
                            <div class="flex items-center gap-1">
                                <button id="ordersPrev" class="px-2 py-1.5 border border-gray-300 rounded-md text-xs text-gray-700 hover:bg-gray-50">Prev</button>
                                <div id="ordersPageNums" class="flex items-center gap-1"></div>
                                <button id="ordersNext" class="px-2 py-1.5 border border-gray-300 rounded-md text-xs text-gray-700 hover:bg-gray-50">Next</button>
                            </div>
                        </div>
                    </div>

                    <!-- Order Edit Modal -->
                    <div id="orderEditModal" class="fixed inset-0 bg-black bg-opacity-30 hidden items-center justify-center z-50">
                        <div class="bg-white rounded-lg shadow-lg w-full max-w-md p-5">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-lg font-semibold">Edit Delivery Order</h3>
                                <button type="button" data-close-order class="text-gray-400 hover:text-gray-600"><i data-lucide="x" class="w-5 h-5"></i></button>
                            </div>
                            <form id="orderEditForm" method="post" action="../../controllers/admin/ordercontroller.php" class="space-y-4">
                                <input type="hidden" name="action" value="update_delivery">
                                <input type="hidden" name="transactions_id" id="order_edit_tid">
                                <div>
                                    <label class="text-xs font-medium text-gray-600">Status</label>
                                    <select name="deliveries_delivery_status" id="order_edit_status" class="mt-1 w-full border border-gray-300 rounded-md px-2 py-1 text-sm" required>
                                        <option value="processing">Processing</option>
                                        <option value="out_for_delivery">Out for Delivery</option>
                                        <option value="delivered">Delivered</option>
                                        <option value="cancelled">Cancelled</option>
                                    </select>
                                </div>
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="text-xs font-medium text-gray-600">Estimated Date</label>
                                        <input type="date" name="deliveries_estimated_delivery_date" id="order_edit_eta" class="mt-1 w-full border border-gray-300 rounded-md px-2 py-1 text-sm">
                                    </div>
                                    <div>
                                        <label class="text-xs font-medium text-gray-600">Actual Date</label>
                                        <input type="date" name="deliveries_actual_delivery_date" id="order_edit_actual" class="mt-1 w-full border border-gray-300 rounded-md px-2 py-1 text-sm">
                                    </div>
                                </div>
                                <div>
                                    <label class="inline-flex items-center gap-2 text-xs text-gray-600">
                                        <input type="checkbox" name="signature_received" id="order_edit_signature" value="1" class="rounded"> Mark as Received (Signature)
                                    </label>
                                </div>
                                <div class="flex justify-end gap-2 pt-2">
                                    <div id="orderEditFeedback" class="mr-auto text-xs text-gray-500"></div>
                                    <button type="button" data-close-order class="px-3 py-1.5 text-sm border rounded-md">Cancel</button>
                                    <button id="orderEditSaveBtn" type="submit" class="px-4 py-1.5 text-sm rounded-md bg-blue-600 text-white hover:bg-blue-700">Save</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Sitters Section -->
                <div id="sitters-section" class="space-y-6 hidden">
                    <div class="flex items-center justify-between">
                        <div>
                            <h1 class="text-3xl font-bold">Pet Sitters Management</h1>
                            <p class="text-gray-600 mt-1">Manage registered pet sitters and their profiles</p>
                        </div>
                        <button id="openAddSitter" class="bg-gradient-to-r from-orange-500 to-amber-600 hover:from-orange-600 hover:to-amber-700 text-white px-4 py-2 rounded-md flex items-center gap-2">
                            <i data-lucide="plus" class="w-4 h-4"></i>
                            Add Sitter
                        </button>
                    </div>

                    <div class="bg-white rounded-lg border border-gray-200">
                        <div class="p-6 border-b border-gray-200">
                            <div class="flex items-center justify-between">
                                <h3 class="text-lg font-semibold">Pet Sitters Directory</h3>
                                <div class="flex items-center gap-2">
                                    <input id="sittersSearch" type="text" placeholder="Search sitters..." class="px-3 py-2 border border-gray-300 rounded-md w-64">
                                </div>
                            </div>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead class="bg-gray-50 border-b">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Contact</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Specialties</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Bio</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Experience</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="sittersTableBody" class="bg-white divide-y divide-gray-200">
                                    <?php
                                    $sitterRows = [];
                                    if (isset($connections) && $connections) {
                                        $hasVerified = false;
                                        if ($chk = @mysqli_query($connections, "SHOW COLUMNS FROM sitters LIKE 'sitters_verified'")) {
                                            $hasVerified = mysqli_num_rows($chk) > 0; mysqli_free_result($chk);
                                        }
                                        $qs = $hasVerified
                                            ? "SELECT sitters_id, sitters_name, sitters_bio, sitter_email, sitters_contact, sitter_specialty, sitters_image_url, sitters_active, years_experience, sitters_verified FROM sitters ORDER BY sitters_id DESC"
                                            : "SELECT sitters_id, sitters_name, sitters_bio, sitter_email, sitters_contact, sitter_specialty, sitters_image_url, sitters_active, years_experience, sitters_verified FROM sitters ORDER BY sitters_id DESC";
                                        if ($res = mysqli_query($connections, $qs)) {
                                            while ($r = mysqli_fetch_assoc($res)) { 
                                                if (!isset($r['sitters_verified'])) { $r['sitters_verified'] = 0; }
                                                $sitterRows[] = $r; 
                                            }
                                            mysqli_free_result($res);
                                        }
                                    }
                                    if (empty($sitterRows)):
                                    ?>
                                        <tr><td colspan="7" class="px-6 py-6 text-center text-gray-500">No sitters found.</td></tr>
                                    <?php else: foreach ($sitterRows as $s):
                                        $email = $s['sitter_email'] ?? '';
                                        $phone = $s['sitters_contact'] ?? '';
                                        $yearsExp = isset($s['years_experience']) ? (int)$s['years_experience'] : 0;
                                        $experience = $yearsExp > 0 ? ($yearsExp . ' yrs') : '';
                                        $bioText = trim($s['sitters_bio'] ?? '');
                                        $bioFull = $bioText;
                                        $bioShort = strlen($bioText) > 60 ? substr($bioText, 0, 60) . '…' : $bioText;
                                        $specStr = $s['sitter_specialty'] ?? '';
                                        $specs = array_filter(array_map('trim', explode(',', (string)$specStr)), function($v){ return $v !== ''; });
                                        $specColorMap = [
                                            'dog' => 'bg-orange-100 text-orange-800 border border-orange-200',
                                            'dogs' => 'bg-orange-100 text-orange-800 border border-orange-200',
                                            'cat' => 'bg-purple-100 text-purple-800 border border-purple-200',
                                            'cats' => 'bg-purple-100 text-purple-800 border border-purple-200',
                                            'bird' => 'bg-blue-100 text-blue-800 border border-blue-200',
                                            'birds' => 'bg-blue-100 text-blue-800 border border-blue-200',
                                            'fish' => 'bg-cyan-100 text-cyan-800 border border-cyan-200',
                                            'fishes' => 'bg-cyan-100 text-cyan-800 border border-cyan-200',
                                            'small pet' => 'bg-emerald-100 text-emerald-800 border border-emerald-200',
                                            'small pets' => 'bg-emerald-100 text-emerald-800 border border-emerald-200',
                                            'rabbit' => 'bg-pink-100 text-pink-800 border border-pink-200',
                                            'rabbits' => 'bg-pink-100 text-pink-800 border border-pink-200',
                                            'hamster' => 'bg-yellow-100 text-yellow-800 border border-yellow-200',
                                            'hamsters' => 'bg-yellow-100 text-yellow-800 border border-yellow-200',
                                            'guinea pig' => 'bg-lime-100 text-lime-800 border border-lime-200',
                                            'guinea pigs' => 'bg-lime-100 text-lime-800 border border-lime-200',
                                            'reptile' => 'bg-amber-100 text-amber-800 border border-amber-200',
                                            'reptiles' => 'bg-amber-100 text-amber-800 border border-amber-200',
                                            'ferret' => 'bg-rose-100 text-rose-800 border border-rose-200',
                                            'ferrets' => 'bg-rose-100 text-rose-800 border border-rose-200',
                                            'exotic pet' => 'bg-indigo-100 text-indigo-800 border border-indigo-200',
                                            'exotic pets' => 'bg-indigo-100 text-indigo-800 border border-indigo-200'
                                        ];
                                    ?>
                                        <tr data-id="<?php echo (int)$s['sitters_id']; ?>">
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="flex items-center gap-3">
                                                    <div class="w-10 h-10 rounded-full overflow-hidden bg-gray-100 flex items-center justify-center">
                                                        <?php if (!empty($s['sitters_image_url'])): ?>
                                                            <img src="<?php echo htmlspecialchars(resolveImageUrl($s['sitters_image_url'])); ?>" alt="<?php echo htmlspecialchars($s['sitters_name']); ?>" class="w-full h-full object-cover">
                                                        <?php else: ?>
                                                            <i data-lucide="user" class="w-4 h-4 text-gray-400"></i>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div>
                                                        <p class="font-medium"><?php echo htmlspecialchars($s['sitters_name']); ?></p>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                <div class="space-y-1">
                                                    <p><?php echo htmlspecialchars($email); ?></p>
                                                    <p class="text-gray-600"><?php echo htmlspecialchars($phone); ?></p>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="flex flex-wrap gap-1">
                                                    <?php foreach($specs as $sp): $k = strtolower($sp); $cls = $specColorMap[$k] ?? 'bg-gray-50 text-gray-700 border border-gray-200'; ?>
                                                        <span class="px-2 py-1 text-xs rounded-full <?php echo $cls; ?>"><?php echo htmlspecialchars($sp); ?></span>
                                                    <?php endforeach; ?>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                <?php if ($bioFull !== ''): ?>
                                                    <span title="<?php echo htmlspecialchars($bioFull); ?>"><?php echo htmlspecialchars($bioShort); ?></span>
                                                <?php else: ?>
                                                    -
                                                <?php endif; ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($experience ?: '-'); ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <?php $verified = isset($s['sitters_verified']) ? (int)$s['sitters_verified'] : 0; ?>
                                                <div class="flex items-center gap-2">
                                                    <span class="px-2 py-1 text-xs rounded-full <?php echo (int)$s['sitters_active'] === 1 ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>"><?php echo (int)$s['sitters_active'] === 1 ? 'Active' : 'Inactive'; ?></span>
                                                    <span class="px-2 py-1 text-xs rounded-full <?php echo $verified===1 ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800'; ?>"><?php echo $verified===1 ? 'Verified' : 'Unverified'; ?></span>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <div class="flex items-center gap-2">
                                                    <button class="p-1 text-gray-400 hover:text-gray-600 btn-sitter-edit" title="Edit"><i data-lucide="edit" class="w-4 h-4"></i></button>
                                                    <button class="p-1 text-red-400 hover:text-red-600 btn-sitter-delete" title="Delete"><i data-lucide="trash-2" class="w-4 h-4"></i></button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    

                    <!-- Add Sitter Modal -->
                    <div id="addSitterModal" class="fixed inset-0 bg-black/40 hidden items-center justify-center z-50">
                        <div class="bg-white rounded-lg shadow-lg w-full max-w-2xl p-6">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-lg font-semibold">Add Pet Sitter</h3>
                                <button type="button" id="closeAddSitter" class="text-gray-400 hover:text-gray-600"><i data-lucide="x" class="w-5 h-5"></i></button>
                            </div>
                            <form id="addSitterForm" enctype="multipart/form-data" class="space-y-4">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Sitter Name</label>
                                        <input type="text" name="sitters_name" required class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2" placeholder="Full name">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Email</label>
                                        <input type="email" name="sitters_email" required class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2" placeholder="example@mail.com">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Contact Number</label>
                                        <input type="text" name="sitters_phone" required class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2" placeholder="09xxxxxxxxx" maxlength="11">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Years of Experience</label>
                                        <input type="number" name="years_experience" min="0" step="1" class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2" placeholder="0">
                                    </div>
                                </div>
                                <!-- Specialties: categorized checkboxes with dynamic Other input -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Specialties <span class="text-xs text-gray-500 font-normal">(select all that apply)</span></label>
                                    <div id="addSpecialtiesGroup" class="mt-2 grid grid-cols-2 md:grid-cols-3 gap-2 text-sm">
                                        <label class="inline-flex items-center gap-1"><input type="checkbox" name="sitters_specialty[]" value="Dog" class="rounded border-gray-300"> <span>Dog</span></label>
                                        <label class="inline-flex items-center gap-1"><input type="checkbox" name="sitters_specialty[]" value="Cat" class="rounded border-gray-300"> <span>Cat</span></label>
                                        <label class="inline-flex items-center gap-1"><input type="checkbox" name="sitters_specialty[]" value="Bird" class="rounded border-gray-300"> <span>Bird</span></label>
                                        <label class="inline-flex items-center gap-1"><input type="checkbox" name="sitters_specialty[]" value="Fish" class="rounded border-gray-300"> <span>Fish</span></label>
                                        <label class="inline-flex items-center gap-1"><input type="checkbox" name="sitters_specialty[]" value="Small Pet" class="rounded border-gray-300"> <span>Small Pet</span></label>
                                        <label class="inline-flex items-center gap-1"><input id="addOtherSpec" type="checkbox" value="Other" class="rounded border-gray-300"> <span>Other</span></label>
                                    </div>
                                    <div id="addOtherWrapper" class="mt-2 hidden">
                                        <input type="text" name="sitters_specialties_extra" class="block w-full border border-gray-300 rounded-md px-3 py-2 text-sm" placeholder="Specify other pets, comma-separated e.g. Iguana, Farm Animals">
                                        <p class="mt-1 text-xs text-gray-500">Separate multiple with commas. These will be saved along with the selected specialties.</p>
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Bio</label>
                                    <textarea name="sitters_bio" rows="3" class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2" placeholder="Short profile shown to pet owners"></textarea>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Image</label>
                                    <div class="mt-1 flex items-center gap-4">
                                        <div class="w-20 h-20 rounded-md bg-gray-100 overflow-hidden flex items-center justify-center">
                                            <img id="sitterImagePreview" src="" alt="Preview" class="hidden w-full h-full object-cover">
                                            <i id="sitterImageIcon" data-lucide="image" class="w-5 h-5 text-gray-400"></i>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <input id="sitterImageInput" type="file" name="sitters_image" accept="image" class="text-sm">
                                            <button id="clearSitterImage" type="button" class="px-2 py-1 text-sm border border-gray-300 rounded-md hover:bg-gray-50">Clear</button>
                                        </div>
                                    </div>
                                </div>
                                    <div class="flex items-center justify-between gap-2 pt-2">
                                    <div class="flex items-center gap-4">
                                        <label class="inline-flex items-center gap-2 text-sm">
                                            <input type="checkbox" name="sitters_active" value="1" class="rounded border-gray-300"> Active
                                        </label>
                                        <label class="inline-flex items-center gap-2 text-sm">
                                            <input type="checkbox" name="sitters_verified" value="1" class="rounded border-gray-300"> Verified
                                        </label>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <button type="button" id="cancelAddSitter" class="px-4 py-2 border border-gray-300 rounded-md hover:bg-gray-50">Cancel</button>
                                        <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">Save Sitter</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Edit Sitter Modal -->
                    <div id="editSitterModal" class="fixed inset-0 bg-black bg-opacity-30 hidden items-center justify-center z-50">
                        <div class="bg-white rounded-lg shadow-lg w-full max-w-2xl">
                            <div class="flex items-center justify-between p-4 border-b">
                                <h3 class="text-lg font-semibold">Edit Pet Sitter</h3>
                                <button id="closeEditSitter" class="text-gray-500 hover:text-gray-700">
                                    <i data-lucide="x" class="w-5 h-5"></i>
                                </button>
                            </div>
                            <form id="editSitterForm" enctype="multipart/form-data" class="p-6 space-y-4">
                                <input type="hidden" name="sitters_id" id="edit_sitters_id">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Sitter Name</label>
                                        <input type="text" name="sitters_name" id="edit_sitters_name" required class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Email</label>
                                        <input type="email" name="sitters_email" id="edit_sitters_email" required class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Contact Number</label>
                                        <input type="text" name="sitters_phone" id="edit_sitters_phone" required class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Years of Experience</label>
                                        <input type="number" name="years_experience" id="edit_years_experience" min="0" step="1" class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2">
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Specialties <span class="text-xs text-gray-500 font-normal">(update selections)</span></label>
                                    <div id="editSpecialtiesGroup" class="mt-2 grid grid-cols-2 md:grid-cols-3 gap-2 text-sm">
                                        <label class="inline-flex items-center gap-1"><input type="checkbox" name="sitters_specialty[]" value="Dog" class="rounded border-gray-300"> <span>Dog</span></label>
                                        <label class="inline-flex items-center gap-1"><input type="checkbox" name="sitters_specialty[]" value="Cat" class="rounded border-gray-300"> <span>Cat</span></label>
                                        <label class="inline-flex items-center gap-1"><input type="checkbox" name="sitters_specialty[]" value="Bird" class="rounded border-gray-300"> <span>Bird</span></label>
                                        <label class="inline-flex items-center gap-1"><input type="checkbox" name="sitters_specialty[]" value="Fish" class="rounded border-gray-300"> <span>Fish</span></label>
                                        <label class="inline-flex items-center gap-1"><input type="checkbox" name="sitters_specialty[]" value="Small Pet" class="rounded border-gray-300"> <span>Small Pet</span></label>
                                        <label class="inline-flex items-center gap-1"><input id="editOtherSpec" type="checkbox" value="Other" class="rounded border-gray-300"> <span>Other</span></label>
                                    </div>
                                    <div id="editOtherWrapper" class="mt-2 hidden">
                                        <input type="text" name="sitters_specialties_extra" id="edit_other_specialties" class="block w-full border border-gray-300 rounded-md px-3 py-2 text-sm" placeholder="Other pets, comma-separated">
                                        <p class="mt-1 text-xs text-gray-500">Unlisted pets retained here.</p>
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Bio</label>
                                    <textarea name="sitters_bio" id="edit_sitters_bio" rows="3" class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2" placeholder="Short profile shown to pet owners"></textarea>
                                </div>
                                <div class="flex items-center justify-between gap-2 pt-2">
                                    <div class="flex items-center gap-4">
                                        <label class="inline-flex items-center gap-2">
                                            <input type="checkbox" name="sitters_active" id="edit_sitters_active" value="1" class="rounded border-gray-300">
                                            <span>Active</span>
                                        </label>
                                        <label class="inline-flex items-center gap-2">
                                            <input type="checkbox" name="sitters_verified" id="edit_sitters_verified" value="1" class="rounded border-gray-300">
                                            <span>Verified</span>
                                        </label>
                                    </div>
                                    <div class="flex gap-2">
                                        <button type="button" id="cancelEditSitter" class="px-4 py-2 border border-gray-300 rounded-md hover:bg-gray-50">Cancel</button>
                                        <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">Update Sitter</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <script>
                        (function(){
                            const openBtn = document.getElementById('openAddSitter');
                            const modal = document.getElementById('addSitterModal');
                            const closeBtn = document.getElementById('closeAddSitter');
                            const cancelBtn = document.getElementById('cancelAddSitter');
                            const form = document.getElementById('addSitterForm');
                            const imgInput = document.getElementById('sitterImageInput');
                            const imgPrev = document.getElementById('sitterImagePreview');
                            const imgIcon = document.getElementById('sitterImageIcon');
                            const clearImg = document.getElementById('clearSitterImage');
                            const tbody = document.getElementById('sittersTableBody');
                            const search = document.getElementById('sittersSearch');
                            // Edit modal refs
                            const editModal = document.getElementById('editSitterModal');
                            const editCloseBtn = document.getElementById('closeEditSitter');
                            const editCancelBtn = document.getElementById('cancelEditSitter');
                            const editForm = document.getElementById('editSitterForm');
                            const editId = document.getElementById('edit_sitters_id');
                            const editName = document.getElementById('edit_sitters_name');
                            const editEmail = document.getElementById('edit_sitters_email');
                            const editPhone = document.getElementById('edit_sitters_phone');
                            const editYears = document.getElementById('edit_years_experience');
                            const editBio = document.getElementById('edit_sitters_bio');
                            const editDescription = document.getElementById('edit_description');
                            const editActive = document.getElementById('edit_sitters_active');
                            const editVerified = document.getElementById('edit_sitters_verified');
                            const editVerifiedText = document.getElementById('edit_sitter_verified_text');
                            // Specialties elements (add)
                            const addOtherSpec = document.getElementById('addOtherSpec');
                            const addOtherWrapper = document.getElementById('addOtherWrapper');
                            // Specialties elements (edit)
                            const editOtherSpec = document.getElementById('editOtherSpec');
                            const editOtherWrapper = document.getElementById('editOtherWrapper');
                            const editOtherInput = document.getElementById('edit_other_specialties');

                            const predefinedSpecs = ['dog','cat','bird','fish','small pet','rabbit','hamster','guinea pig','reptile','ferret','exotic pet'];

                            function toggleAddOther(){
                                if (addOtherSpec && addOtherWrapper) {
                                    addOtherWrapper.classList.toggle('hidden', !addOtherSpec.checked);
                                    if (!addOtherSpec.checked) {
                                        const extra = addOtherWrapper.querySelector('input[name="sitters_specialties_extra"]');
                                        if (extra) extra.value='';
                                    }
                                }
                            }
                            function toggleEditOther(){
                                if (editOtherSpec && editOtherWrapper) {
                                    editOtherWrapper.classList.toggle('hidden', !editOtherSpec.checked);
                                    if (!editOtherSpec.checked && editOtherInput) editOtherInput.value='';
                                }
                            }
                            if (addOtherSpec) addOtherSpec.addEventListener('change', toggleAddOther);
                            if (editOtherSpec) editOtherSpec.addEventListener('change', toggleEditOther);

                            function openEdit(){ editModal.classList.remove('hidden'); editModal.classList.add('flex'); }
                            function closeEdit(){ editModal.classList.add('hidden'); editModal.classList.remove('flex'); editForm.reset(); }

                            function open(){ modal.classList.remove('hidden'); modal.classList.add('flex'); }
                            function close(){ modal.classList.add('hidden'); modal.classList.remove('flex'); form.reset(); resetImage(); }
                            function resetImage(){ imgPrev.src=''; imgPrev.classList.add('hidden'); imgIcon.classList.remove('hidden'); imgInput.value=''; }

                            if (openBtn) openBtn.addEventListener('click', open);
                            if (closeBtn) closeBtn.addEventListener('click', close);
                            if (cancelBtn) cancelBtn.addEventListener('click', close);
                            if (modal) modal.addEventListener('click', (e)=>{ if(e.target===modal) close(); });

                            if (imgInput) imgInput.addEventListener('change', (e)=>{
                                const f = e.target.files && e.target.files[0];
                                if (!f) return resetImage();
                                const url = URL.createObjectURL(f);
                                imgPrev.src = url; imgPrev.classList.remove('hidden'); imgIcon.classList.add('hidden');
                            });
                            if (clearImg) clearImg.addEventListener('click', resetImage);
                            if (editCloseBtn) editCloseBtn.addEventListener('click', closeEdit);
                            if (editCancelBtn) editCancelBtn.addEventListener('click', closeEdit);

                            function addr(text){ const td = document.createElement('td'); td.className='px-6 py-4 whitespace-nowrap'; td.innerHTML=text; return td; }
                            function specClass(name){
                                const k = String(name||'').trim().toLowerCase();
                                switch(k){
                                    case 'dog':
                                    case 'dogs': return 'specialty-badge bg-orange-100 text-orange-800 border border-orange-200';
                                    case 'cat':
                                    case 'cats': return 'specialty-badge bg-purple-100 text-purple-800 border border-purple-200';
                                    case 'bird':
                                    case 'birds': return 'specialty-badge bg-blue-100 text-blue-800 border border-blue-200';
                                    case 'fish':
                                    case 'fishes': return 'specialty-badge bg-cyan-100 text-cyan-800 border border-cyan-200';
                                    case 'small pet':
                                    case 'small pets': return 'specialty-badge bg-emerald-100 text-emerald-800 border border-emerald-200';
                                    case 'rabbit':
                                    case 'rabbits': return 'specialty-badge bg-pink-100 text-pink-800 border border-pink-200';
                                    case 'hamster':
                                    case 'hamsters': return 'specialty-badge bg-yellow-100 text-yellow-800 border border-yellow-200';
                                    case 'guinea pig':
                                    case 'guinea pigs': return 'specialty-badge bg-lime-100 text-lime-800 border border-lime-200';
                                    case 'reptile':
                                    case 'reptiles': return 'specialty-badge bg-amber-100 text-amber-800 border border-amber-200';
                                    case 'ferret':
                                    case 'ferrets': return 'specialty-badge bg-rose-100 text-rose-800 border border-rose-200';
                                    case 'exotic pet':
                                    case 'exotic pets': return 'specialty-badge bg-indigo-100 text-indigo-800 border border-indigo-200';
                                    default: return 'specialty-badge bg-gray-100 text-gray-800 border border-gray-200';
                                }
                            }
                            function esc(s){ const d=document.createElement('div'); d.textContent=s??''; return d.innerHTML; }

                            if (form) form.addEventListener('submit', async (e)=>{
                                e.preventDefault();
                                const fd = new FormData(form);
                                fd.append('action','add');
                                try {
                                    const res = await fetch('../../controllers/admin/sittercontroller.php', { method:'POST', body: fd });
                                    const data = await res.json();
                                    if (!data.success){ alert(data.error||data.message||'Failed to add sitter'); return; }
                                    const s = data.item;
                                    const tr = document.createElement('tr');
                                    tr.setAttribute('data-id', String(s.id||''));
                                    // Name with image
                                    const nameHTML = `
                                        <div class="flex items-center gap-3">
                                            <div class="w-10 h-10 rounded-full overflow-hidden bg-gray-100 flex items-center justify-center">
                                                ${s.image ? `<img src="${s.image}" alt="${esc(s.name)}" class="w-full h-full object-cover">` : '<i data-lucide="user" class="w-4 h-4 text-gray-400"></i>'}
                                            </div>
                                            <div><p class="font-medium">${esc(s.name)}</p></div>
                                        </div>`;
                                    tr.appendChild(addr(nameHTML));
                                    // Contact
                                    const contactHTML = `<div class=\"space-y-1\"><p>${esc(s.email||'')}</p><p class=\"text-gray-600\">${esc(s.phone||'')}</p></div>`;
                                    tr.appendChild(addr(contactHTML));
                                    // Specialties
                                    const specs = (s.specialties||[]).map(x=>`<span class="px-2 py-1 text-xs rounded-full ${specClass(x)}">${esc(x)}</span>`).join(' ');
                                    tr.appendChild(addr(`<div class="flex flex-wrap gap-1">${specs}</div>`));
                                    // Bio (new column)
                                    const bio = (s.bio||'').trim();
                                    const bioShort = bio.length>60 ? bio.slice(0,60)+'…' : bio;
                                    tr.appendChild(addr(bio ? `<span title="${esc(bio)}">${esc(bioShort)}</span>` : '-'));
                                    // Experience
                                    tr.appendChild(addr(esc((s.years_experience? (String(s.years_experience)+ ' yrs') : (s.experience||'')))))
                                    // Status (merged: Active + Verified)
                                    const active = String(s.active)==='1' || s.active===1 || s.active===true;
                                    const verified = String(s.verified)==='1' || s.verified===1 || s.verified===true;
                                    tr.appendChild(addr(`
                                        <div class="flex items-center gap-2">
                                            <span class="px-2 py-1 text-xs rounded-full ${active?'bg-green-100 text-green-800':'bg-red-100 text-red-800'}">${active?'Active':'Inactive'}</span>
                                            <span class="px-2 py-1 text-xs rounded-full ${verified?'bg-blue-100 text-blue-800':'bg-gray-100 text-gray-800'}">${verified?'Verified':'Unverified'}</span>
                                        </div>`));
                                    // Actions
                                    const actions = document.createElement('td');
                                    actions.className='px-6 py-4 whitespace-nowrap text-sm text-gray-500';
                                    actions.innerHTML = '<div class="flex items-center gap-2"><button class="p-1 text-gray-400 hover:text-gray-600 btn-sitter-edit" title="Edit"><i data-lucide="edit" class="w-4 h-4"></i></button><button class="p-1 text-red-400 hover:text-red-600 btn-sitter-delete" title="Delete"><i data-lucide="trash-2" class="w-4 h-4"></i></button></div>';
                                    tr.appendChild(actions);
                                    tbody.prepend(tr);
                                    if (window.lucide && lucide.createIcons) lucide.createIcons();
                                    close();
                                } catch (err){ console.error(err); alert('Network error adding sitter'); }
                            });

                            if (search) search.addEventListener('input', ()=>{
                                const q = search.value.trim().toLowerCase();
                                [...tbody.querySelectorAll('tr')].forEach(tr=>{
                                    const text = tr.textContent.toLowerCase();
                                    tr.style.display = text.includes(q) ? '' : 'none';
                                });
                            });

                            // Edit/Delete delegation
                            tbody.addEventListener('click', async (e)=>{
                                const editBtn = e.target.closest('.btn-sitter-edit');
                                const delBtn = e.target.closest('.btn-sitter-delete');
                                const tr = e.target.closest('tr');
                                if (!tr) return;
                                const id = tr.getAttribute('data-id');
                                if (editBtn) {
                                    try {
                                        const res = await fetch(`../../controllers/admin/sittercontroller.php?action=get&id=${encodeURIComponent(id)}`);
                                        const data = await res.json();
                                        if (!data.success) { alert(data.error||'Failed to load sitter'); return; }
                                        const raw = data.item || {};
                                        // Normalize fields coming from backend (supports existing schema names)
                                        const norm = {
                                            id: raw.id ?? raw.sitters_id ?? id,
                                            name: raw.name ?? raw.sitters_name ?? '',
                                            email: raw.email ?? raw.sitter_email ?? '',
                                            phone: raw.phone ?? raw.sitters_contact ?? '',
                                            years_experience: raw.years_experience ?? raw.experience ?? 0,
                                            bio: raw.bio ?? raw.sitters_bio ?? '',
                                            active: raw.active ?? raw.sitters_active ?? 0,
                                            verified: raw.verified ?? raw.sitters_verified ?? 0,
                                            verified_text: raw.verified_text ?? raw.sitter_verified_text ?? '',
                                            specialties: (function(){
                                                if (Array.isArray(raw.specialties)) return raw.specialties;
                                                const spec = raw.sitter_specialty || raw.sitters_specialty || '';
                                                if (!spec) return [];
                                                return String(spec).split(/[,|]/).map(s=>s.trim()).filter(Boolean);
                                            })()
                                        };
                                        editId.value = norm.id;
                                        editName.value = norm.name;
                                        editEmail.value = norm.email;
                                        editPhone.value = norm.phone;
                                        editYears.value = norm.years_experience || 0;
                                        editBio.value = norm.bio;
                                        editActive.checked = String(norm.active)==='1' || norm.active===1 || norm.active===true;
                                        if (editVerified) editVerified.checked = String(norm.verified)==='1' || norm.verified===1 || norm.verified===true;
                                        if (editVerifiedText) editVerifiedText.value = norm.verified_text || (String(norm.verified)==='1' ? 'Verified' : '');
                                        // Populate specialties checkboxes in edit form
                                        try {
                                            const specBoxes = editModal.querySelectorAll('#editSpecialtiesGroup input[type="checkbox"][name="sitters_specialty[]"]');
                                            specBoxes.forEach(cb => cb.checked = false);
                                            if (editOtherSpec) editOtherSpec.checked = false;
                                            if (editOtherInput) editOtherInput.value='';
                                            if (editOtherWrapper) editOtherWrapper.classList.add('hidden');
                                            const extras = [];
                                            (norm.specialties||[]).forEach(val => {
                                                const norm = String(val).trim().toLowerCase();
                                                const matched = [...specBoxes].find(cb => cb.value.toLowerCase() === norm);
                                                if (matched) { matched.checked = true; }
                                                else { if (!predefinedSpecs.includes(norm)) extras.push(val); }
                                            });
                                            if (extras.length>0) {
                                                if (editOtherSpec) editOtherSpec.checked = true;
                                                if (editOtherWrapper) editOtherWrapper.classList.remove('hidden');
                                                if (editOtherInput) editOtherInput.value = extras.join(', ');
                                            }
                                        } catch(err){ console.warn('Failed to populate specialties', err); }
                                        if (window.lucide && lucide.createIcons) lucide.createIcons();
                                        openEdit();
                                    } catch(err){ console.error(err); alert('Network error loading sitter'); }
                                } else if (delBtn) {
                                    if (!confirm('Delete this sitter?')) return;
                                    try {
                                        const fd = new FormData();
                                        fd.append('action','delete');
                                        fd.append('sitters_id', id);
                                        const res = await fetch('../../controllers/admin/sittercontroller.php', { method: 'POST', body: fd });
                                        const data = await res.json();
                                        if (!data.success) { alert(data.error||'Failed to delete'); return; }
                                        tr.remove();
                                    } catch(err){ console.error(err); alert('Network error deleting sitter'); }
                                }
                            });

                            if (editForm) editForm.addEventListener('submit', async (e)=>{
                                e.preventDefault();
                                const fd = new FormData(editForm);
                                fd.append('action','update');
                                try {
                                    const res = await fetch('../../controllers/admin/sittercontroller.php', { method:'POST', body: fd });
                                    const data = await res.json();
                                    if (!data.success){ alert(data.error||'Failed to update sitter'); return; }
                                    const s = data.item;
                                    // Update row
                                    const row = tbody.querySelector(`tr[data-id="${CSS.escape(String(s.id))}"]`);
                                    if (row) {
                                        const tds = row.querySelectorAll('td');
                                        // Name cell (0): update image and name
                                        const nameCell = tds[0];
                                        const imgEl = nameCell.querySelector('img');
                                        const iconEl = nameCell.querySelector('i[data-lucide="user"]');
                                        if (s.image) {
                                            if (imgEl) { imgEl.src = s.image; }
                                            else {
                                                const ph = nameCell.querySelector('div.w-10.h-10');
                                                if (ph) ph.innerHTML = `<img src="${s.image}" alt="${s.name}" class="w-full h-full object-cover">`;
                                            }
                                            if (iconEl) iconEl.remove();
                                        } else {
                                            if (imgEl) imgEl.remove();
                                            const holder = nameCell.querySelector('div.w-10.h-10');
                                            if (holder && !holder.querySelector('i[data-lucide="user"]')) holder.innerHTML = '<i data-lucide="user" class="w-4 h-4 text-gray-400"></i>';
                                        }
                                        const nameP = nameCell.querySelector('p.font-medium');
                                        if (nameP) nameP.textContent = s.name||'';
                                        // Contact (1) with years badge
                                        const contactCell = tds[1];
                                        contactCell.innerHTML = `<div class=\"space-y-1\"><p>${esc(s.email||'')}</p><p class=\"text-gray-600\">${esc(s.phone||'')}</p></div>`;
                                        // Specialties (2)
                                        const specsHTML = (s.specialties||[]).map(x=>`<span class="px-2 py-1 text-xs rounded-full ${specClass(x)}">${esc(x)}</span>`).join(' ');
                                        tds[2].innerHTML = `<div class="flex flex-wrap gap-1">${specsHTML}</div>`;
                                        // Bio (3)
                                        const bio2 = (s.bio||'').trim();
                                        const bioShort2 = bio2.length>60 ? bio2.slice(0,60)+'…' : bio2;
                                        tds[3].innerHTML = bio2 ? `<span title="${esc(bio2)}">${esc(bioShort2)}</span>` : '-';
                                        // Experience years (4)
                                        tds[4].textContent = (s.years_experience && Number(s.years_experience)>0) ? `${Number(s.years_experience)} yrs` : (s.experience||'-');
                                        // Status (5) merged: Active + Verified
                                        const isActive2 = (String(s.active)==='1'||s.active===1||s.active===true);
                                        const isVerified2 = (String(s.verified)==='1'||s.verified===1||s.verified===true);
                                        tds[5].innerHTML = `
                                            <div class="flex items-center gap-2">
                                                <span class="px-2 py-1 text-xs rounded-full ${ isActive2 ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }">${ isActive2 ? 'Active' : 'Inactive' }</span>
                                                <span class="px-2 py-1 text-xs rounded-full ${ isVerified2 ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800' }">${ isVerified2 ? 'Verified' : 'Unverified' }</span>
                                            </div>`;
                                        if (window.lucide && lucide.createIcons) lucide.createIcons();
                                    }
                                    closeEdit();
                                } catch(err){ console.error(err); alert('Network error updating sitter'); }
                            });
                        })();
                    </script>
                </div>

                <!-- Appointments Section -->
                <div id="appointments-section" class="space-y-6 hidden">
                    <?php
                    // Fetch all appointments with address details if available
                    $appointments = [];
                    $counts = ['pet_sitting' => 0, 'grooming' => 0, 'vet' => 0];
                    if (isset($connections) && $connections) {
        $sql = "SELECT a.appointments_id, a.users_id, a.appointments_full_name, a.appointments_email, a.appointments_phone,
            a.appointments_pet_name, a.appointments_pet_type, a.appointments_pet_breed, a.appointments_pet_age_years,
            a.appointments_type, a.appointments_date, a.appointments_status, a.appointments_created_at,
            aa.aa_type, aa.aa_address, aa.aa_city, aa.aa_province, aa.aa_postal_code, aa.aa_notes
                FROM appointments a
                LEFT JOIN appointment_address aa ON aa.aa_id = a.aa_id
                ORDER BY a.appointments_created_at DESC, a.appointments_id DESC";
                        if ($res = mysqli_query($connections, $sql)) {
                            while ($row = mysqli_fetch_assoc($res)) {
                                $appointments[] = $row;
                                $t = $row['appointments_type'];
                                if (isset($counts[$t])) $counts[$t]++;
                            }
                            mysqli_free_result($res);
                        }
                    }

                    function e($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
                    // Lightweight partial refresh for appointments (JSON: tbody HTML + counts summary) similar to orders
                    if(isset($_GET['appointments_partial']) && $_GET['appointments_partial']=='1'){
                        header('Content-Type: application/json; charset=UTF-8');
                        $rowsHtml = '';
                        if(empty($appointments)){
                            $rowsHtml .= '<tr><td colspan="11" class="px-6 py-6 text-center text-gray-500">No appointments found.</td></tr>';
                        } else {
                            foreach($appointments as $ap){
                                $type = (string)($ap['appointments_type'] ?? '');
                                $aaType = (string)($ap['aa_type'] ?? '');
                                $addr = trim(implode(', ', array_filter([
                                    $ap['aa_address'] ?? '',
                                    $ap['aa_city'] ?? '',
                                    $ap['aa_province'] ?? '',
                                ])), ', ');
                                $typeDisplay = '';
                                if ($type === 'pet_sitting') {
                                    if ($aaType === 'home-sitting') {
                                        $typeDisplay = 'Home-sitting' . ($addr !== '' ? ' — '. e($addr) : '');
                                    } elseif ($aaType === 'drop_off') {
                                        $typeDisplay = 'Drop Off';
                                    } else { $typeDisplay = 'Pet Sitting'; }
                                } elseif ($type === 'grooming') {
                                    $typeDisplay = 'Grooming';
                                } elseif ($type === 'vet') {
                                    $typeDisplay = 'Veterinary';
                                } else { $typeDisplay = ucfirst(str_replace('_',' ', $type)); }
                                $dt = $ap['appointments_date'] ?? '';
                                $iso=''; if($dt){ $iso = str_replace(' ', 'T', $dt); }
                                $notes = trim((string)($ap['aa_notes'] ?? ''));
                                $searchIndex = strtolower(($ap['appointments_full_name'] ?? '') . ' ' . ($ap['appointments_email'] ?? '') . ' ' . ($ap['appointments_phone'] ?? '') . ' ' . ($ap['appointments_pet_name'] ?? '') . ' ' . ($ap['appointments_pet_type'] ?? '') . ' ' . ($ap['appointments_pet_breed'] ?? ''));
                                $rowsHtml .= '<tr data-id="'.(int)$ap['appointments_id'].'" data-type="'.e($type).'" data-status="'.e($ap['appointments_status'] ?? '').'" data-datetime="'.e($iso).'" data-search="'.e($searchIndex).'">';
                                $rowsHtml .= '<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">'.e($ap['appointments_full_name'] ?? '').'</td>';
                                $rowsHtml .= '<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">'.e(($ap['appointments_email'] ?? '') . '<br>' . ($ap['appointments_phone'] ?? '')).'</td>';
                                $rowsHtml .= '<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">'.e($ap['appointments_pet_name'] ?? '').'</td>';
                                $rowsHtml .= '<td class="px-6 py-4 whitespace-nowrap text-sm">'.e($ap['appointments_pet_type'] ?? '').'</td>';
                                $rowsHtml .= '<td class="px-6 py-4 whitespace-nowrap text-sm">'.e($ap['appointments_pet_breed'] ?? '').'</td>';
                                $rowsHtml .= '<td class="px-6 py-4 whitespace-nowrap text-sm">'.e($ap['appointments_pet_age_years'] ?? '').'</td>';
                                $rowsHtml .= '<td class="px-6 py-4 whitespace-nowrap text-sm">'.e($typeDisplay).'</td>';
                                $rowsHtml .= '<td class="px-6 py-4 whitespace-nowrap text-sm">'.e($dt).'</td>';
                                $rowsHtml .= '<td class="px-6 py-4 whitespace-nowrap text-sm max-w-xs truncate" title="'.e($notes).'">'.e($notes).'</td>';
                                $status = (string)($ap['appointments_status'] ?? '');
                                $statusClassMap = [
                                    'pending'   => 'bg-gray-100 text-gray-700 border-gray-300',
                                    'confirmed' => 'bg-blue-100 text-blue-700 border-blue-300',
                                    'completed' => 'bg-green-100 text-green-700 border-green-300',
                                    'cancelled' => 'bg-red-100 text-red-700 border-red-300'
                                ];
                                $cls = $statusClassMap[$status] ?? 'bg-gray-100 text-gray-700 border-gray-300';
                                $rowsHtml .= '<td class="px-6 py-4 whitespace-nowrap text-sm"><span class="px-2 py-1 text-xs rounded-full border '.$cls.'">'.e(ucfirst($status)).'</span></td>';
                                $rowsHtml .= '<td class="px-6 py-4 whitespace-nowrap text-sm">';
                                $rowsHtml .= '<button data-edit-appt="'.(int)$ap['appointments_id'].'" class="px-2 py-1 text-xs bg-indigo-50 text-indigo-700 hover:bg-indigo-100 rounded border border-indigo-200">Edit</button> ';
                                $rowsHtml .= '<button data-cancel-appt="'.(int)$ap['appointments_id'].'" class="px-2 py-1 text-xs bg-red-50 text-red-700 hover:bg-red-100 rounded border border-red-200">Cancel</button>';
                                $rowsHtml .= '</td>';
                                $rowsHtml .= '</tr>';
                            }
                        }
                        echo json_encode([
                            'ok' => true,
                            'tbody' => $rowsHtml,
                            'counts' => $counts,
                            'total' => count($appointments)
                        ]);
                        exit;
                    }
                    function type_badge_class($t){
                        switch ($t){
                            case 'pet_sitting': return 'bg-orange-100 text-orange-800 border border-orange-200';
                            case 'grooming': return 'bg-blue-100 text-blue-800 border border-blue-200';
                            case 'vet': return 'bg-green-100 text-green-800 border border-green-200';
                            default: return 'bg-gray-100 text-gray-800 border border-gray-200';
                        }
                    }
                    // Lightweight partial refresh handler
                    if(isset($_GET['orders_partial']) && $_GET['orders_partial']=='1'){
                        ob_start();
                        if(empty($orders)){
                            echo '<tr><td colspan="9" class="px-4 py-6 text-center text-gray-500">No orders found.</td></tr>';
                        } else {
                            foreach($orders as $ord){
                                $tid=(int)$ord['transactions_id'];
                                $recipient=trim($ord['location_recipient_name'] ?? '');
                                $buyer=$recipient !== '' ? $recipient : trim(($ord['users_firstname']??'').' '.($ord['users_lastname']??''));
                                $items=$itemsByTxn[$tid]??[];
                                $status=$ord['deliveries_delivery_status']??'';
                                $addressParts=array_filter([$ord['location_address_line1']??'', $ord['location_barangay']??'', $ord['location_city']??'', $ord['location_province']??'']);
                                $address=implode(', ',$addressParts);
                                $eta=$ord['deliveries_estimated_delivery_date']??'';
                                $itemsSearch = strtolower(implode(' ', array_map(function($x){ return $x['products_name']; }, $items)));
                                echo '<tr data-tid="'.o_e($tid).'" data-buyer="'.o_e(strtolower($buyer)).'" data-status="'.o_e($status).'" data-payment="'.o_e(strtolower($ord['transactions_payment_method']??'')).'" data-address="'.o_e(strtolower($address)).'" data-items="'.o_e($itemsSearch).'">';
                                echo '<td class="px-4 py-3 align-top"><div class="font-medium text-gray-800">'.o_e($buyer ?: 'User #'.$ord['users_id']).'</div><div class="text-[11px] text-gray-500">#'.$tid.' • ₱'.number_format((float)$ord['transactions_amount'],2).'</div><div class="text-[10px] text-gray-400">'.o_e(date('Y-m-d H:i',strtotime($ord['transactions_created_at']))).'</div></td>';
                                // Items cell
                                echo '<td class="px-4 py-3 align-top"><ul class="space-y-1 max-w-[170px]">';
                                foreach($items as $it){
                                    echo '<li class="flex items-center gap-2 text-xs"><span class="text-gray-700 truncate" title="'.o_e($it['products_name']).'">'.o_e($it['products_name']).'</span><span class="text-gray-400">x'.(int)$it['tp_quantity'].'</span></li>';
                                }
                                echo '</ul></td>';
                                // Address
                                echo '<td class="px-4 py-3 align-top"><div class="text-xs text-gray-700 max-w-[180px] truncate" title="'.o_e($address).'">'.o_e($address).'</div></td>';
                                // Payment
                                $pay = strtolower($ord['transactions_payment_method']??'');
                                echo '<td class="px-4 py-3 align-top"><span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-medium border '.($pay==='cod'?'bg-yellow-50 text-yellow-700 border-yellow-200':($pay==='gcash'?'bg-emerald-50 text-emerald-700 border-emerald-200':'bg-indigo-50 text-indigo-700 border-indigo-200')).'">'.o_e($pay?:'n/a').'</span></td>';
                                // Status
                                $st = $status; $cls = 'order-status order-status-'.o_e($st);
                                echo '<td class="px-4 py-3 align-top"><span class="'.$cls.'">'.o_e(str_replace('_',' ', $st)).'</span></td>';
                                // Estimated, Actual, Signature
                                echo '<td class="px-4 py-3 align-top text-xs">'.o_e($eta).'</td>';
                                echo '<td class="px-4 py-3 align-top text-xs">'.o_e($ord['deliveries_actual_delivery_date']??'').'</td>';
                                $sig = !empty($ord['deliveries_recipient_signature']);
                                echo '<td class="px-4 py-3 align-top text-xs">'.($sig?'<span class="text-emerald-600 font-semibold">Received</span>':'<span class="text-gray-400">Pending</span>').'</td>';
                                // Actions (reuse existing edit button minimal)
                                echo '<td class="px-4 py-3 align-top"><button class="order-edit-btn inline-flex items-center gap-1 px-2 py-1 text-xs border border-gray-300 rounded hover:bg-gray-50" data-tid="'.$tid.'"><i data-lucide="edit" class="w-3 h-3"></i>Edit</button></td>';
                                echo '</tr>';
                            }
                        }
                        $tbodyHtml = ob_get_clean();
                        header('Content-Type: application/json');
                        echo json_encode(['ok'=>true,'count'=>count($orders),'tbody'=>$tbodyHtml]);
                        exit;
                    }
                    ?>
                    <div class="flex items-center justify-between">
                        <div>
                            <h1 class="text-3xl font-bold">Appointments Management</h1>
                            <p class="text-gray-600 mt-1">Track and manage pet care appointments</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="flex items-center gap-2">
                                <label class="text-sm text-gray-600">Date:</label>
                                <input type="date" id="apptDateFrom" class="px-2 py-1 border border-gray-300 rounded-md">
                                <span class="text-gray-400">to</span>
                                <input type="date" id="apptDateTo" class="px-2 py-1 border border-gray-300 rounded-md">
                            </div>
                            <div class="flex items-center gap-2 ml-4">
                                <label class="text-sm text-gray-600">Time:</label>
                                <input type="time" id="apptTimeFrom" class="px-2 py-1 border border-gray-300 rounded-md">
                                <span class="text-gray-400">to</span>
                                <input type="time" id="apptTimeTo" class="px-2 py-1 border border-gray-300 rounded-md">
                            </div>
                            <button id="resetApptFilters" class="ml-2 px-3 py-2 border border-gray-300 rounded-md hover:bg-gray-50">Reset</button>
                        </div>
                    </div>

                    <div class="bg-white rounded-lg border border-gray-200">
                        <div class="p-6 border-b border-gray-200">
                            <div class="flex flex-col gap-3">
                                <div class="flex items-center justify-between gap-3 flex-wrap">
                                    <h3 class="text-lg font-semibold">All Appointments</h3>
                                    <div class="flex items-center gap-3">
                                        <div class="relative">
                                            <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 w-4 h-4"></i>
                                            <input id="appointmentsSearch" type="text" placeholder="Search appointments..." class="pl-9 pr-3 py-2 border border-gray-300 rounded-md w-72" />
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <label for="apptStatusFilter" class="text-sm text-gray-600">Status:</label>
                                            <select id="apptStatusFilter" class="px-2 py-1 border border-gray-300 rounded-md text-sm">
                                                <option value="">All</option>
                                                <option value="pending">Pending</option>
                                                <option value="confirmed">Confirmed</option>
                                                <option value="completed">Completed</option>
                                                <option value="cancelled">Cancelled</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div id="apptTabs" class="flex items-center gap-2">
                                    <button data-appt-filter="all" class="appt-tab px-3 py-1.5 rounded-full border text-sm bg-gray-900 text-white border-gray-900">All</button>
                                    <button data-appt-filter="pet_sitting" class="appt-tab px-3 py-1.5 rounded-full border text-sm border-orange-300 text-orange-700 bg-orange-50">Pet Sitting</button>
                                    <button data-appt-filter="grooming" class="appt-tab px-3 py-1.5 rounded-full border text-sm border-blue-300 text-blue-700 bg-blue-50">Grooming</button>
                                    <button data-appt-filter="vet" class="appt-tab px-3 py-1.5 rounded-full border text-sm border-green-300 text-green-700 bg-green-50">Veterinary</button>
                                </div>
                            </div>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full" id="allAppointmentsTable">
                                <thead class="bg-gray-50 border-b">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Full Name</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Contact</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pet Name</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pet Type</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Breed</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Age</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Appointment Type</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date & Time</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Notes</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                                    </tr>
                                </thead>
                                <tbody id="appointmentsTableBody" class="bg-white divide-y divide-gray-200">
                                <?php if (empty($appointments)): ?>
                                    <tr><td colspan="11" class="px-6 py-6 text-center text-gray-500">No appointments found.</td></tr>
                                <?php else: foreach ($appointments as $ap):
                                    $type = (string)($ap['appointments_type'] ?? '');
                                    $aaType = (string)($ap['aa_type'] ?? '');
                                    $addr = trim(implode(', ', array_filter([
                                        $ap['aa_address'] ?? '',
                                        $ap['aa_city'] ?? '',
                                        $ap['aa_province'] ?? '',
                                    ])), ', ');
                                    $typeDisplay = '';
                                    if ($type === 'pet_sitting') {
                                        if ($aaType === 'home-sitting') {
                                            $typeDisplay = 'Home-sitting' . ($addr !== '' ? ' — ' . e($addr) : '');
                                        } elseif ($aaType === 'drop_off') {
                                            $typeDisplay = 'Drop Off';
                                        } else {
                                            $typeDisplay = 'Pet Sitting';
                                        }
                                    } elseif ($type === 'grooming') {
                                        $typeDisplay = 'Grooming';
                                    } elseif ($type === 'vet') {
                                        $typeDisplay = 'Veterinary';
                                    } else {
                                        $typeDisplay = ucfirst(str_replace('_',' ', $type));
                                    }
                                    $dt = $ap['appointments_date'] ?? '';
                                    $iso = '';
                                    if ($dt) { $iso = str_replace(' ', 'T', $dt); }
                                    // Notes: from appointment_address. Column appointments_notes removed.
                                    $notes = trim((string)($ap['aa_notes'] ?? ''));
                                ?>
                                    <tr data-id="<?php echo (int)$ap['appointments_id']; ?>" data-type="<?php echo e($type); ?>" data-status="<?php echo e($ap['appointments_status'] ?? ''); ?>" data-datetime="<?php echo e($iso); ?>" data-search="<?php echo e(strtolower(($ap['appointments_full_name'] ?? '') . ' ' . ($ap['appointments_email'] ?? '') . ' ' . ($ap['appointments_phone'] ?? '') . ' ' . ($ap['appointments_pet_name'] ?? '') . ' ' . ($ap['appointments_pet_type'] ?? '') . ' ' . ($ap['appointments_pet_breed'] ?? ''))); ?>">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo e($ap['appointments_full_name'] ?? ''); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                            <div class="space-y-0.5">
                                                <div><?php echo e($ap['appointments_phone'] ?? ''); ?></div>
                                                <div class="text-gray-500 text-xs"><?php echo e($ap['appointments_email'] ?? ''); ?></div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo e($ap['appointments_pet_name'] ?? ''); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-xs">
                                            <span class="px-2 py-1 rounded-full <?php echo type_badge_class($ap['appointments_pet_type'] ?? ''); ?>"><?php echo e(ucfirst((string)($ap['appointments_pet_type'] ?? ''))); ?></span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo e($ap['appointments_pet_breed'] ?? ''); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo e($ap['appointments_pet_age_years'] ?? ''); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-xs">
                                            <span class="px-2 py-1 rounded-full <?php echo type_badge_class($type); ?>"><?php echo e($typeDisplay); ?></span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo e($dt ? date('M d, Y h:i A', strtotime($dt)) : ''); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 truncate max-w-xs" title="<?php echo e($notes); ?>"><?php echo e($notes); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-xs">
                                            <?php $st = (string)($ap['appointments_status'] ?? '');
                                                $cls = $st==='confirmed'?'bg-indigo-100 text-indigo-800 border border-indigo-200':($st==='completed'?'bg-green-100 text-green-800 border border-green-200':($st==='cancelled'?'bg-red-100 text-red-800 border border-red-200':'bg-yellow-100 text-yellow-800 border border-yellow-200'));
                                            ?>
                                            <span class="px-2 py-1 rounded-full <?php echo $cls; ?>"><?php echo e(ucfirst($st)); ?></span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <div class="flex items-center gap-2">
                                                <button class="p-1 text-gray-400 hover:text-gray-600 btn-appt-edit" title="Edit"><i data-lucide="edit" class="w-4 h-4"></i></button>
                                                <button class="p-1 text-red-400 hover:text-red-600 btn-appt-delete" title="Delete"><i data-lucide="trash-2" class="w-4 h-4"></i></button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                </div>

                <!-- Pet Owners Section -->
                <div id="pets-section" class="space-y-6 hidden">
                    <div class="flex items-center justify-between">
                        <div>
                            <h1 class="text-3xl font-bold">Pet Owners & Pets</h1>
                            <p class="text-gray-600 mt-1">Manage registered pet owners and their beloved pets</p>
                        </div>
                    </div>

                    <div class="bg-white rounded-lg border border-gray-200">
                        <div class="p-6 border-b border-gray-200">
                            <div class="flex items-center justify-between">
                                <h3 class="text-lg font-semibold">Pet Owners Directory</h3>
                                <div class="flex items-center gap-2">
                                    <input id="petOwnersSearch" type="text" placeholder="Search owners..." class="px-3 py-2 border border-gray-300 rounded-md w-64">
                                    <button class="px-3 py-2 border border-gray-300 rounded-md hover:bg-gray-50" title="No filters available yet">
                                        <i data-lucide="filter" class="w-4 h-4"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="p-4">
                            <?php
                            // Build owners with nested pets
                            $owners = [];
                            if (isset($connections) && $connections) {
                                $sqlOwners = "SELECT u.users_id, u.users_firstname, u.users_lastname, u.users_username, u.users_email, u.users_created_at,
                                                  CASE WHEN EXISTS (
                                                    SELECT 1 FROM user_subscriptions us
                                                    WHERE us.users_id = u.users_id AND us.us_status='active' AND (us.us_end_date IS NULL OR us.us_end_date >= NOW())
                                                  ) THEN 1 ELSE 0 END AS has_subscription
                                              FROM users u
                                              WHERE COALESCE(u.users_role,'0') <> '1'
                                              ORDER BY u.users_created_at DESC, u.users_id DESC";
                                $ownersById = [];
                                if ($res = mysqli_query($connections, $sqlOwners)) {
                                    while ($row = mysqli_fetch_assoc($res)) {
                                        $row['pets'] = [];
                                        $owners[] = $row;
                                        $ownersById[(int)$row['users_id']] = &$owners[array_key_last($owners)];
                                    }
                                    mysqli_free_result($res);
                                }
                                if (!empty($ownersById)) {
                                    $ids = implode(',', array_map('intval', array_keys($ownersById)));
                                    $sqlPets = "SELECT users_id, pets_name, pets_species, COALESCE(pets_breed,'') AS pets_breed
                                                FROM pets WHERE users_id IN ($ids) ORDER BY pets_name";
                                    if ($rp = mysqli_query($connections, $sqlPets)) {
                                        while ($p = mysqli_fetch_assoc($rp)) {
                                            $uid = (int)$p['users_id'];
                                            if (isset($ownersById[$uid])) { $ownersById[$uid]['pets'][] = $p; }
                                        }
                                        mysqli_free_result($rp);
                                    }
                                }
                            }
                            ?>

                            <div id="petOwnersList" class="space-y-4">
                                <?php if (!empty($owners)): foreach ($owners as $o): ?>
                                    <?php
                                        $fn = (string)($o['users_firstname'] ?? '');
                                        $ln = (string)($o['users_lastname'] ?? '');
                                        $full = trim($fn . ' ' . $ln);
                                        if ($full === '') $full = (string)($o['users_username'] ?? '');
                                        $memberSince = '';
                                        $createdRaw = (string)($o['users_created_at'] ?? '');
                                        if ($createdRaw !== '') { $memberSince = date('n/j/Y', strtotime($createdRaw)); }
                                        $hasSub = intval($o['has_subscription'] ?? 0) === 1;
                                        $initials = strtoupper(substr($full,0,1));
                                        $searchHay = strtolower($full.' '.($o['users_username']??'').' '.($o['users_email']??''));
                                        foreach (($o['pets'] ?? []) as $pp) { $searchHay .= ' '.strtolower(($pp['pets_name']??'').' '.($pp['pets_species']??'').' '.($pp['pets_breed']??'')); }
                                        $searchHay .= ' '.($hasSub?'active':'none');
                                    ?>
                                    <div class="owner-card bg-white border border-gray-200 rounded-lg p-4" data-search="<?php echo htmlspecialchars($searchHay, ENT_QUOTES, 'UTF-8'); ?>">
                                        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                                            <!-- Owner Info -->
                                            <div class="space-y-2">
                                                <div class="flex items-center gap-3">
                                                    <div class="w-12 h-12 bg-gradient-to-br from-orange-400 to-amber-500 rounded-full flex items-center justify-center text-white font-semibold">
                                                        <?php echo htmlspecialchars($initials, ENT_QUOTES, 'UTF-8'); ?>
                                                    </div>
                                                    <div class="min-w-0">
                                                        <h4 class="font-semibold truncate"><?php echo htmlspecialchars($full, ENT_QUOTES, 'UTF-8'); ?></h4>
                                                    </div>
                                                </div>
                                                <div class="text-sm text-gray-600">Member since <?php echo htmlspecialchars($memberSince, ENT_QUOTES, 'UTF-8'); ?></div>
                                                <div class="text-sm text-gray-600 flex items-center gap-2"><i data-lucide="mail" class="w-4 h-4"></i><span class="truncate"><?php echo htmlspecialchars((string)($o['users_email'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></span></div>
                                                <div>
                                                    <?php if ($hasSub): ?>
                                                        <span class="px-2 py-1 bg-green-100 text-green-800 text-xs rounded-full border border-green-200">Active</span>
                                                    <?php else: ?>
                                                        <span class="px-2 py-1 bg-gray-100 text-gray-800 text-xs rounded-full border border-gray-200">None</span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>

                                            <!-- Pets -->
                                            <div class="lg:col-span-2">
                                                <div class="flex items-center gap-2 mb-2 text-sm text-gray-700">
                                                    <i data-lucide="paw-print" class="w-4 h-4"></i>
                                                    <span>Registered Pets (<?php echo count($o['pets'] ?? []); ?>)</span>
                                                </div>
                                                <?php if (!empty($o['pets'])): ?>
                                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                                        <?php foreach ($o['pets'] as $pet): ?>
                                                            <div class="border rounded-lg p-3 bg-gray-50">
                                                                <div class="font-semibold"><?php echo htmlspecialchars((string)$pet['pets_name'], ENT_QUOTES, 'UTF-8'); ?></div>
                                                                <div class="text-sm text-gray-600">Type: <?php echo htmlspecialchars((string)$pet['pets_species'], ENT_QUOTES, 'UTF-8'); ?></div>
                                                                <div class="text-sm text-gray-600">Breed: <?php echo htmlspecialchars((string)$pet['pets_breed'], ENT_QUOTES, 'UTF-8'); ?></div>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    </div>
                                                <?php else: ?>
                                                    <div class="text-sm text-gray-500">No registered pets.</div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; else: ?>
                                    <div class="text-center text-gray-500 py-8">No pet owners found.</div>
                                <?php endif; ?>
                            </div>

                            <!-- Pagination -->
                            <div id="petOwnersPager" class="flex items-center justify-between mt-4">
                                <div id="ownersPageInfo" class="text-sm text-gray-600"></div>
                                <div class="flex items-center gap-2">
                                    <button id="ownersPrev" class="px-3 py-1.5 border rounded-md text-sm disabled:opacity-50">Prev</button>
                                    <div id="ownersPageNums" class="flex items-center gap-1"></div>
                                    <button id="ownersNext" class="px-3 py-1.5 border rounded-md text-sm disabled:opacity-50">Next</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Subscribers Section (Dynamic) -->
                <div id="subscribers-section" class="space-y-6 hidden">
                    <div class="flex items-center justify-between">
                        <div>
                            <h2 class="text-3xl font-bold">Subscribers</h2>
                            <p class="text-gray-600 mt-1">Manage user subscription statuses</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <button id="refreshSubscribers" class="bg-gradient-to-r from-orange-500 to-amber-600 hover:from-orange-600 hover:to-amber-700 text-white px-4 py-2 rounded-md flex items-center gap-2 text-sm"><i data-lucide="rotate-cw" class="w-4 h-4"></i>Refresh</button>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="bg-white rounded-lg border border-gray-200 p-6">
                            <div class="text-center">
                                <div class="text-2xl font-bold text-orange-600" id="subsTotal"><?= (int)$subscriberStats['total'] ?></div>
                                <div class="text-sm text-gray-600">Total Subscribers</div>
                            </div>
                        </div>
                        <div class="bg-white rounded-lg border border-gray-200 p-6">
                            <div class="text-center">
                                <div class="text-2xl font-bold text-green-600" id="subsActive"><?= (int)$subscriberStats['active'] ?></div>
                                <div class="text-sm text-gray-600">Active Subscribers</div>
                            </div>
                        </div>
                        <div class="bg-white rounded-lg border border-gray-200 p-6">
                            <div class="text-center">
                                <div class="text-2xl font-bold text-blue-600" id="subsMonth"><?= (int)$subscriberStats['this_month'] ?></div>
                                <div class="text-sm text-gray-600">This Month</div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
                        <div class="p-4 border-b border-gray-200 flex items-center justify-between gap-4 flex-wrap">
                            <div class="flex items-center gap-2">
                                <i data-lucide="list" class="w-4 h-4 text-gray-500"></i>
                                <h3 class="font-semibold text-gray-700">Subscribers List</h3>
                            </div>
                            <div class="flex items-center gap-3 w-full md:w-auto">
                                <div class="relative flex-1 md:flex-initial">
                                    <i data-lucide="search" class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2"></i>
                                    <input id="subsSearch" type="text" placeholder="Search user/email" class="pl-9 pr-3 py-2 text-sm rounded-md border border-gray-300 focus:ring-2 focus:ring-orange-500 focus:border-orange-500 w-full" />
                                </div>
                                <select id="subsStatusFilter" class="text-sm border-gray-300 rounded-md focus:ring-orange-500 focus:border-orange-500">
                                    <option value="">All Status</option>
                                    <option value="active">Active</option>
                                    <option value="cancelled">Cancelled</option>
                                    <option value="expired">Expired</option>
                                </select>
                            </div>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead class="bg-gray-50 text-xs uppercase text-gray-500">
                                    <tr>
                                        <th class="py-2 px-3 text-left">User</th>
                                        <th class="py-2 px-3 text-left">Start Date</th>
                                        <th class="py-2 px-3 text-left">End Date</th>
                                        <th class="py-2 px-3 text-left">Status</th>
                                        <th class="py-2 px-3 text-left">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="subsTableBody" class="divide-y divide-gray-100">
                                    <?php if(empty($subscribersList)): ?>
                                        <tr><td colspan="5" class="py-6 px-3 text-center text-gray-500 text-sm" data-empty="1">No subscribers found.</td></tr>
                                    <?php else: foreach($subscribersList as $s):
                                        $status = $s['us_status'];
                                        if($status === 'active' && !empty($s['us_end_date']) && strtotime($s['us_end_date']) < time()) { $status = 'expired'; }
                                        $badgeClass = match($status){
                                            'active' => 'bg-green-100 text-green-700 border-green-300',
                                            'cancelled' => 'bg-red-100 text-red-700 border-red-300',
                                            'expired' => 'bg-gray-200 text-gray-700 border-gray-300',
                                            default => 'bg-gray-100 text-gray-600 border-gray-200'
                                        };
                                    ?>
                                    <tr data-status="<?= htmlspecialchars($status,ENT_QUOTES) ?>" data-user="<?= (int)$s['users_id'] ?>" data-name="<?= htmlspecialchars($s['full_name'] ?: $s['users_email'],ENT_QUOTES) ?>" data-email="<?= htmlspecialchars($s['users_email'],ENT_QUOTES) ?>">
                                        <td class="py-2 px-3">
                                            <div class="flex flex-col">
                                                <span class="font-medium text-gray-800 leading-tight"><?= htmlspecialchars($s['full_name'] ?: $s['users_email'],ENT_QUOTES) ?></span>
                                                <span class="text-[11px] text-gray-500 leading-tight"><?= htmlspecialchars($s['users_email'],ENT_QUOTES) ?></span>
                                            </div>
                                        </td>
                                        <td class="py-2 px-3 text-gray-600 text-xs"><?= $s['us_start_date'] ? date('Y-m-d', strtotime($s['us_start_date'])) : '—' ?></td>
                                        <td class="py-2 px-3 text-gray-600 text-xs"><?= $s['us_end_date'] ? date('Y-m-d', strtotime($s['us_end_date'])) : '—' ?></td>
                                        <td class="py-2 px-3">
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full border text-[11px] font-medium <?= $badgeClass ?> capitalize">
                                                <i data-lucide="dot" class="w-3 h-3"></i><?= htmlspecialchars($status,ENT_QUOTES) ?>
                                            </span>
                                        </td>
                                        <td class="py-2 px-3">
                                            <div class="flex items-center gap-2">
                                                <button class="subs-message-btn inline-flex items-center gap-1 px-2 py-1 text-xs font-medium rounded-md border border-gray-300 hover:border-orange-400 hover:text-orange-600" data-email="<?= htmlspecialchars($s['users_email'],ENT_QUOTES) ?>" data-name="<?= htmlspecialchars($s['full_name'],ENT_QUOTES) ?>"><i data-lucide="mail" class="w-3 h-3"></i>Message</button>
                                                <button class="subs-delete-btn inline-flex items-center gap-1 px-2 py-1 text-xs font-medium rounded-md border border-red-300 text-red-600 hover:bg-red-50" data-us="<?= (int)$s['us_id'] ?>"><i data-lucide="trash-2" class="w-3 h-3"></i>Delete</button>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; endif; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="p-3 border-t border-gray-200 flex items-center justify-between text-xs text-gray-500">
                            <div id="subsCountSummary">Showing <span id="subsShownCount"></span> of <span id="subsTotalCount"></span></div>
                            <div class="flex items-center gap-2" id="subsPager" style="display:none;">
                                <button id="subsPrev" class="px-2 py-1 rounded border border-gray-300 disabled:opacity-40">Prev</button>
                                <div id="subsPageNums" class="flex items-center gap-1"></div>
                                <button id="subsNext" class="px-2 py-1 rounded border border-gray-300 disabled:opacity-40">Next</button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Promos Section -->
                <div id="promos-section" class="space-y-6 hidden">
                    <div class="flex items-center justify-between">
                        <div>
                            <h2 class="text-3xl font-bold">Promos</h2>
                            <p class="text-gray-600 mt-1">Manage promotional offers</p>
                        </div>
                        <button id="openAddPromoBtn" type="button" class="bg-gradient-to-r from-orange-500 to-amber-600 hover:from-orange-600 hover:to-amber-700 text-white px-4 py-2 rounded-md flex items-center gap-2 text-sm"><i data-lucide="plus" class="w-4 h-4"></i>Add Promo</button>
                    </div>
                    <?php if($promoFeedback['success']): ?>
                        <div class="p-3 rounded-md bg-green-50 border border-green-200 text-green-700 text-sm flex items-center gap-2"><i data-lucide="check-circle" class="w-4 h-4"></i><span><?= htmlspecialchars($promoFeedback['success']) ?></span></div>
                    <?php elseif($promoFeedback['error']): ?>
                        <div class="p-3 rounded-md bg-red-50 border border-red-200 text-red-700 text-sm flex items-center gap-2"><i data-lucide="alert-triangle" class="w-4 h-4"></i><span><?= htmlspecialchars($promoFeedback['error']) ?></span></div>
                    <?php endif; ?>
                    <div class="bg-white border border-gray-200 rounded-lg overflow-hidden" id="promosTableWrapper">
                        <div class="p-4 border-b flex items-center justify-between">
                            <h3 class="font-semibold text-gray-700 flex items-center gap-2"><i data-lucide="list" class="w-4 h-4 text-gray-500"></i>Promo List</h3>
                            <div class="text-xs text-gray-500">Total: <?= count($promotions) ?></div>
                        </div>
                        <!-- Filters Bar -->
                        <div class="p-4 border-b bg-gray-50/60 flex flex-wrap gap-3 text-xs items-end promo-filters-dark" id="promoFiltersBar">
                            <div class="flex flex-col">
                                <label for="promoFilterType" class="text-[11px] font-medium text-gray-600">Type</label>
                                <select id="promoFilterType" class="px-2 py-1 border rounded-md text-xs bg-white">
                                    <option value="">All</option>
                                </select>
                            </div>
                            <div class="flex flex-col">
                                <label for="promoFilterDiscount" class="text-[11px] font-medium text-gray-600">Discount</label>
                                <select id="promoFilterDiscount" class="px-2 py-1 border rounded-md text-xs bg-white">
                                    <option value="">All</option>
                                    <option value="percent">Percent</option>
                                    <option value="fixed">Fixed</option>
                                    <option value="points_bonus">Points Bonus</option>
                                    <option value="free_item">Free Item</option>
                                    <option value="none">None</option>
                                </select>
                            </div>
                            <div class="flex flex-col">
                                <label for="promoFilterPoints" class="text-[11px] font-medium text-gray-600">Points</label>
                                <select id="promoFilterPoints" class="px-2 py-1 border rounded-md text-xs bg-white">
                                    <option value="">All</option>
                                    <option value="required">Requires Points</option>
                                    <option value="free">No Points</option>
                                </select>
                            </div>
                            <div class="flex flex-col">
                                <label for="promoFilterLimits" class="text-[11px] font-medium text-gray-600">Limits</label>
                                <select id="promoFilterLimits" class="px-2 py-1 border rounded-md text-xs bg-white" title="Usage / Per-user limits classification">
                                    <option value="">All</option>
                                    <option value="limited">Limited</option>
                                    <option value="global_unlimited">Global ∞</option>
                                    <option value="user_unlimited">Per-user ∞</option>
                                    <option value="fully_unlimited">Both ∞</option>
                                </select>
                            </div>
                            <div class="flex flex-col">
                                <label for="promoFilterWindow" class="text-[11px] font-medium text-gray-600">Window</label>
                                <select id="promoFilterWindow" class="px-2 py-1 border rounded-md text-xs bg-white">
                                    <option value="">All</option>
                                    <option value="active">Active Now</option>
                                    <option value="upcoming">Upcoming</option>
                                    <option value="expired">Expired</option>
                                    <option value="perpetual">Perpetual</option>
                                </select>
                            </div>
                            <div class="flex flex-col">
                                <label for="promoFilterStatus" class="text-[11px] font-medium text-gray-600">Status</label>
                                <select id="promoFilterStatus" class="px-2 py-1 border rounded-md text-xs bg-white">
                                    <option value="">All</option>
                                    <option value="1">Active</option>
                                    <option value="0">Inactive</option>
                                </select>
                            </div>
                            <div class="ml-auto flex items-center gap-2">
                                <button id="promoFiltersReset" type="button" class="text-[11px] px-3 py-1.5 rounded-md border border-gray-300 bg-white hover:bg-gray-100 promo-reset-btn">Reset</button>
                            </div>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead class="bg-gray-50 text-xs uppercase text-gray-500">
                                    <tr>
                                        <th class="px-3 py-2 text-left">Name / Code</th>
                                        <th class="px-3 py-2 text-left">Type</th>
                                        <th class="px-3 py-2 text-left">Discount</th>
                                        <th class="px-3 py-2 text-left">Points</th>
                                        <th class="px-3 py-2 text-left">Limits</th>
                                        <th class="px-3 py-2 text-left">Window</th>
                                        <th class="px-3 py-2 text-left">Status</th>
                                        <th class="px-3 py-2 text-left">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="promosTbody">
                                    <?php if(!count($promotions)): ?>
                                        <tr><td colspan="8" class="px-3 py-6 text-center text-gray-500">No promos found.</td></tr>
                                    <?php else: foreach($promotions as $pr): ?>
                                        <?php
                                            $active=(int)($pr['promo_active']??0)===1;
                                            $discT=$pr['promo_discount_type']??'none';
                                            $discV=$pr['promo_discount_value'];
                                            if($discT==='percent' && $discV!==null) $discV=rtrim(rtrim(number_format((float)$discV,2,'.',''),'0'),'.').'%';
                                            elseif($discV!==null) $discV='₱'.number_format((float)$discV,2);
                                            $limits=[]; $limits[]=$pr['promo_usage_limit']? 'G:'.$pr['promo_usage_limit']:'G:∞'; $limits[]=$pr['promo_per_user_limit']? 'U:'.$pr['promo_per_user_limit']:'U:∞';
                                            $win=htmlspecialchars(substr($pr['promo_starts_at'],0,16)).' → '.htmlspecialchars(substr($pr['promo_ends_at'],0,16));
                                        ?>
                                        <tr class="border-b last:border-b-0 hover:bg-orange-50/50">
                                            <td class="px-3 py-2 align-top"><div class="font-medium text-gray-800 truncate max-w-[160px]" title="<?= htmlspecialchars($pr['promo_name']) ?>"><?= htmlspecialchars($pr['promo_name']) ?></div><div class="text-xs text-gray-500">Code: <?= htmlspecialchars($pr['promo_code']?:'—') ?></div></td>
                                            <td class="px-3 py-2 align-top text-xs capitalize"><?= htmlspecialchars($pr['promo_type']) ?></td>
                                            <td class="px-3 py-2 align-top text-xs"><div class="font-medium"><?= htmlspecialchars($discT) ?></div><div class="text-gray-500"><?= $discV? htmlspecialchars($discV):'—' ?></div></td>
                                            <td class="px-3 py-2 align-top text-xs"><?= $pr['promo_points_cost'] ? (int)$pr['promo_points_cost'] : '—' ?></td>
                                            <td class="px-3 py-2 align-top text-xs"><?= htmlspecialchars(implode(' / ',$limits)) ?></td>
                                            <td class="px-3 py-2 align-top text-xs leading-tight"><?= $win ?></td>
                                            <td class="px-3 py-2 align-top"><span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium <?= $active? 'bg-green-100 text-green-700 border border-green-200':'bg-gray-100 text-gray-600 border border-gray-200' ?>"><?= $active? 'Active':'Inactive' ?></span></td>
                                            <td class="px-3 py-2 align-top text-xs"><div class="flex items-center gap-2 flex-wrap">
                                                <form method="POST" onsubmit="return confirm('Toggle this promo?');"><input type="hidden" name="promo_action" value="toggle"><input type="hidden" name="promo_id" value="<?= (int)$pr['promo_id'] ?>"><input type="hidden" name="to" value="<?= $active? '0':'1' ?>"><button class="px-2 py-1 rounded border text-[11px] <?= $active? 'bg-white hover:bg-gray-50':'bg-green-600 border-green-600 text-white hover:bg-green-700' ?>" type="submit"><?= $active? 'Deactivate':'Activate' ?></button></form>
                                                <form method="POST" onsubmit="return confirm('Delete this promo?');"><input type="hidden" name="promo_action" value="delete"><input type="hidden" name="promo_id" value="<?= (int)$pr['promo_id'] ?>"><button type="submit" class="px-2 py-1 rounded border border-red-300 bg-red-50 text-red-600 hover:bg-red-100 text-[11px]">Delete</button></form>
                                            </div></td>
                                        </tr>
                                    <?php endforeach; endif; ?>
                                </tbody>
                            </table>
                        </div>
                        <!-- Pagination Footer -->
                        <div class="p-3 border-t border-gray-200 flex flex-col md:flex-row md:items-center gap-3 md:justify-between text-[11px] text-gray-600" id="promosFooter">
                            <div id="promosCountSummary" class="leading-snug">Showing <span id="promosShown">0</span> of <span id="promosTotalFiltered">0</span><span class="hidden md:inline"> (Total <span id="promosTotalAll">0</span>)</span></div>
                            <div class="flex items-center gap-2" id="promoPager" style="display:none;">
                                <button id="promoPrev" class="px-2 py-1 rounded border border-gray-300 disabled:opacity-40">Prev</button>
                                <div id="promoPageNums" class="flex items-center gap-1"></div>
                                <button id="promoNext" class="px-2 py-1 rounded border border-gray-300 disabled:opacity-40">Next</button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Settings Section -->
                <div id="settings-section" class="space-y-6 hidden">
                    <div>
                        <h2 class="text-xl font-semibold flex items-center gap-2 text-gray-800"><i data-lucide="settings" class="w-5 h-5 text-orange-500"></i> Settings</h2>
                        <p class="text-sm text-gray-500">Manage admin preferences and booking availability.</p>
                    </div>
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <!-- Dark Mode Card -->
                        <div class="bg-white border border-gray-200 rounded-lg shadow-sm p-5 flex flex-col gap-4" id="darkModeCard">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h3 class="font-medium text-gray-800 flex items-center gap-2"><i data-lucide="moon" class="w-4 h-4 text-indigo-500"></i> Dark Mode</h3>
                                    <p class="text-xs text-gray-500">Toggle dark theme for the admin dashboard.</p>
                                </div>
                                <label class="inline-flex items-center cursor-pointer select-none">
                                    <input type="checkbox" id="darkModeMasterToggle" class="sr-only">
                                    <span class="w-10 h-5 flex items-center bg-gray-300 rounded-full p-1 transition-all" id="darkModeSlider">
                                        <span class="bg-white w-4 h-4 rounded-full shadow transition-all" id="darkModeKnob"></span>
                                    </span>
                                </label>
                            </div>
                            <div class="text-xs text-gray-500" id="darkModeStatus">Theme: Light</div>
                        </div>
                        <!-- Appointment Blocking Card (Single or Range) -->
                        <div class="bg-white border border-gray-200 rounded-lg shadow-sm p-5 flex flex-col gap-4" id="blockApptCard">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h3 class="font-medium text-gray-800 flex items-center gap-2"><i data-lucide="calendar-x" class="w-4 h-4 text-red-500"></i> Appointment Availability</h3>
                                    <p class="text-xs text-gray-500">Block accepting new appointments for one day or a date range.</p>
                                </div>
                            </div>
                            <div class="flex flex-col gap-4">
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <div class="flex flex-col gap-1">
                                        <label for="blockDateStart" class="text-xs font-medium text-gray-600">Start Date</label>
                                        <input type="date" id="blockDateStart" class="border border-gray-300 rounded-md px-2 py-1 text-sm" />
                                    </div>
                                    <div class="flex flex-col gap-1">
                                        <label for="blockDateEnd" class="text-xs font-medium text-gray-600">End Date</label>
                                        <input type="date" id="blockDateEnd" class="border border-gray-300 rounded-md px-2 py-1 text-sm" />
                                    </div>
                                    <div class="flex flex-col gap-1">
                                        <label for="blockDate" class="text-xs font-medium text-gray-600">(Legacy) Single Date</label>
                                        <input type="date" id="blockDate" class="border border-gray-300 rounded-md px-2 py-1 text-sm" />
                                    </div>
                                </div>
                                <div class="flex flex-wrap gap-2">
                                    <button id="blockTodayBtn" class="px-3 py-2 bg-red-600 hover:bg-red-700 text-white text-xs rounded-md">Block Today</button>
                                    <button id="blockDateBtn" class="px-3 py-2 bg-orange-600 hover:bg-orange-700 text-white text-xs rounded-md">Block Single Date</button>
                                    <button id="blockRangeBtn" class="px-3 py-2 bg-amber-600 hover:bg-amber-700 text-white text-xs rounded-md">Block Range</button>
                                    <button id="clearBlockBtn" class="px-3 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 text-xs rounded-md">Clear</button>
                                </div>
                            </div>
                            <div id="blockStatus" class="text-xs text-gray-500">No blocked dates set.</div>
                            <div class="text-[11px] text-gray-400">Users selecting a blocked date (or any within a blocked range) will see a notice.</div>
                        </div>
                    </div>
                </div>

                <!-- Audit Logs Section (visual feed, no table) -->
                <div id="audit-section" class="space-y-6 hidden">
                    <div class="flex items-center justify-between">
                        <div>
                            <h2 class="text-xl font-semibold text-gray-900">Audit Logs</h2>
                            <p class="text-sm text-gray-500">Transparent history of admin actions</p>
                        </div>
                        <div class="text-xs text-gray-500">Last refreshed: <span id="auditRefreshedAt"></span></div>
                    </div>

                    <!-- Small stat cards -->
                    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3" id="auditStats">
                        <div class="audit-card p-3 rounded-lg border border-gray-200 bg-white" data-metric="all">
                            <div class="text-[11px] text-gray-500 label">All</div>
                            <div class="text-xl font-semibold metric-num" id="auditStatAll">0</div>
                        </div>
                        <div class="audit-card p-3 rounded-lg border border-green-200 bg-green-50" data-metric="additions">
                            <div class="text-[11px] text-green-700 label">Additions</div>
                            <div class="text-xl font-semibold text-green-700 metric-num" id="auditStatAdd">0</div>
                        </div>
                        <div class="audit-card p-3 rounded-lg border border-indigo-200 bg-indigo-50" data-metric="updates">
                            <div class="text-[11px] text-indigo-700 label">Updates</div>
                            <div class="text-xl font-semibold text-indigo-700 metric-num" id="auditStatUpd">0</div>
                        </div>
                        <div class="audit-card p-3 rounded-lg border border-amber-200 bg-amber-50" data-metric="price">
                            <div class="text-[11px] text-amber-700 label">Price</div>
                            <div class="text-xl font-semibold text-amber-700 metric-num" id="auditStatPrice">0</div>
                        </div>
                        <div class="audit-card p-3 rounded-lg border border-sky-200 bg-sky-50" data-metric="stock">
                            <div class="text-[11px] text-sky-700 label">Stock</div>
                            <div class="text-xl font-semibold text-sky-700 metric-num" id="auditStatStock">0</div>
                        </div>
                        <div class="audit-card p-3 rounded-lg border border-gray-200 bg-white" data-metric="other">
                            <div class="text-[11px] text-gray-700 label">Other</div>
                            <div class="text-xl font-semibold text-gray-800 metric-num" id="auditStatOther">0</div>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl border border-gray-200 p-4">
                        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                            <div class="flex flex-wrap items-center gap-2" id="auditTabs"></div>
                            <div class="flex items-center gap-2">
                                <input id="auditFrom" type="date" class="px-3 py-2 rounded-lg border border-gray-300">
                                <span class="text-xs text-gray-500">to</span>
                                <input id="auditTo" type="date" class="px-3 py-2 rounded-lg border border-gray-300">
                                <input id="auditSearch" type="text" placeholder="Search by user, action, product..." class="w-64 px-3 py-2 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-orange-400" />
                                <button id="auditSearchBtn" class="inline-flex items-center gap-2 px-3 py-2 rounded-lg bg-orange-600 text-white hover:bg-orange-700">
                                    <i data-lucide="search" class="w-4 h-4"></i>
                                    <span>Search</span>
                                </button>
                                <button id="auditResetBtn" class="inline-flex items-center gap-2 px-3 py-2 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50">
                                    <i data-lucide="rotate-ccw" class="w-4 h-4"></i>
                                    <span>Reset</span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center justify-between text-sm text-gray-600">
                        <div>Showing <span id="auditShowing">0</span> of <span id="auditTotal">0</span> activities</div>
                        <div class="flex items-center gap-2">
                            <button id="auditPrev" class="px-3 py-1.5 rounded-lg border border-gray-200 hover:bg-gray-50">Previous</button>
                            <span id="auditPageInfo">Page 1 / 1</span>
                            <button id="auditNext" class="px-3 py-1.5 rounded-lg border border-gray-200 hover:bg-gray-50">Next</button>
                        </div>
                    </div>

                    <div id="auditFeed" class="space-y-3"></div>
                </div>
            </main>
        </div>
    </div>

    <!-- Modals -->
    <!-- Add Promo Modal (Floating Form) -->
    <div id="addPromoModal" class="fixed inset-0 bg-black/40 hidden items-center justify-center z-50">
        <div class="bg-white rounded-lg shadow-lg w-full max-w-3xl mx-4 flex flex-col max-h-[90vh]">
            <div class="flex items-center justify-between px-5 py-4 border-b border-gray-200">
                <div class="flex items-center gap-2">
                    <i data-lucide="percent" class="w-5 h-5 text-orange-500"></i>
                    <h3 class="text-lg font-semibold">Add Promotion</h3>
                </div>
                <button type="button" id="closeAddPromoBtn" class="text-gray-400 hover:text-gray-600" aria-label="Close">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>
            <form class="overflow-y-auto p-5 space-y-6" id="addPromoForm">
                <div id="addPromoFeedback" class="hidden text-sm rounded-md p-3"></div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div class="space-y-1.5">
                        <label class="text-sm font-medium text-gray-700">Type</label>
                        <select name="promo_type" class="w-full border-gray-300 rounded-md text-sm focus:ring-orange-500 focus:border-orange-500" required>
                            <option value="product">Product</option>
                            <option value="appointment">Appointment</option>
                            <option value="general">General</option>
                        </select>
                    </div>
                    <div class="space-y-1.5">
                        <label class="text-sm font-medium text-gray-700 flex items-center gap-1">Code <span class="text-xs text-gray-400">(blank = auto)</span></label>
                        <input name="promo_code" type="text" maxlength="64" class="w-full border-gray-300 rounded-md text-sm focus:ring-orange-500 focus:border-orange-500" />
                    </div>
                    <div class="space-y-1.5 md:col-span-2">
                        <label class="text-sm font-medium text-gray-700">Name *</label>
                        <input name="promo_name" type="text" maxlength="120" required class="w-full border-gray-300 rounded-md text-sm focus:ring-orange-500 focus:border-orange-500" />
                    </div>
                    <div class="space-y-1.5 md:col-span-2">
                        <label class="text-sm font-medium text-gray-700">Description</label>
                        <textarea name="promo_description" rows="3" class="w-full border-gray-300 rounded-md text-sm focus:ring-orange-500 focus:border-orange-500" placeholder="Short description"></textarea>
                    </div>
                    <div class="space-y-1.5">
                        <label class="text-sm font-medium text-gray-700">Discount Type</label>
                        <select name="promo_discount_type" id="promoDiscountType" class="w-full border-gray-300 rounded-md text-sm focus:ring-orange-500 focus:border-orange-500">
                            <option value="percent">Percent %</option>
                            <option value="fixed">Fixed Amount</option>
                            <option value="points_bonus">Points Bonus</option>
                            <option value="free_item">Free Item</option>
                            <option value="none">None</option>
                        </select>
                    </div>
                    <div class="space-y-1.5">
                        <label class="text-sm font-medium text-gray-700">Discount Value</label>
                        <input name="promo_discount_value" id="promoDiscountValue" type="number" step="0.01" min="0" class="w-full border-gray-300 rounded-md text-sm focus:ring-orange-500 focus:border-orange-500" />
                    </div>
                    <div class="space-y-1.5">
                        <label class="text-sm font-medium text-gray-700">Points Cost</label>
                        <input name="promo_points_cost" type="number" min="0" class="w-full border-gray-300 rounded-md text-sm focus:ring-orange-500 focus:border-orange-500" placeholder="Blank/0 = none" />
                    </div>
                    <div class="space-y-1.5">
                        <label class="text-sm font-medium text-gray-700">Min Purchase (₱)</label>
                        <input name="promo_min_purchase_amount" type="number" step="0.01" min="0" class="w-full border-gray-300 rounded-md text-sm focus:ring-orange-500 focus:border-orange-500" />
                    </div>
                    <div class="space-y-1.5">
                        <label class="text-sm font-medium text-gray-700">Usage Limit</label>
                        <input name="promo_usage_limit" type="number" min="0" class="w-full border-gray-300 rounded-md text-sm focus:ring-orange-500 focus:border-orange-500" placeholder="Blank = unlimited" />
                    </div>
                    <div class="space-y-1.5">
                        <label class="text-sm font-medium text-gray-700">Per User Limit</label>
                        <input name="promo_per_user_limit" type="number" min="0" class="w-full border-gray-300 rounded-md text-sm focus:ring-orange-500 focus:border-orange-500" placeholder="Blank = unlimited" />
                    </div>
                    <div class="space-y-1.5">
                        <label class="text-sm font-medium text-gray-700">Starts At</label>
                        <input name="promo_starts_at" type="datetime-local" class="w-full border-gray-300 rounded-md text-sm focus:ring-orange-500 focus:border-orange-500" />
                    </div>
                    <div class="space-y-1.5">
                        <label class="text-sm font-medium text-gray-700">Ends At</label>
                        <input name="promo_ends_at" type="datetime-local" class="w-full border-gray-300 rounded-md text-sm focus:ring-orange-500 focus:border-orange-500" />
                    </div>
                    <div class="space-y-2 md:col-span-2">
                        <label class="inline-flex items-center gap-2 text-sm font-medium text-gray-700">
                            <input type="checkbox" name="promo_require_active_subscription" value="1" class="rounded border-gray-300 text-orange-600 focus:ring-orange-500" />
                            Require Active Subscription
                        </label>
                        <label class="inline-flex items-center gap-2 text-sm font-medium text-gray-700">
                            <input type="checkbox" name="promo_active" value="1" class="rounded border-gray-300 text-orange-600 focus:ring-orange-500" checked />
                            Active Immediately
                        </label>
                    </div>
                </div>
                <div class="flex items-center justify-end gap-3 pt-2 border-t border-gray-200">
                    <button type="button" id="cancelAddPromoBtn" class="px-4 py-2 text-sm rounded-md bg-gray-100 hover:bg-gray-200 text-gray-700">Cancel</button>
                    <button type="submit" class="px-5 py-2.5 text-sm font-medium rounded-md bg-orange-600 hover:bg-orange-700 text-white shadow-sm">Save Promo</button>
                </div>
            </form>
        </div>
    </div>
    <!-- Edit Promo Modal -->
    <div id="editPromoModal" class="fixed inset-0 bg-black/40 hidden items-center justify-center z-50">
        <div class="bg-white rounded-lg shadow-lg w-full max-w-3xl mx-4 flex flex-col max-h-[90vh]">
            <div class="flex items-center justify-between px-5 py-4 border-b border-gray-200">
                <div class="flex items-center gap-2">
                    <i data-lucide="pencil" class="w-5 h-5 text-blue-500"></i>
                    <h3 class="text-lg font-semibold">Edit Promotion</h3>
                </div>
                <button type="button" id="closeEditPromoBtn" class="text-gray-400 hover:text-gray-600" aria-label="Close">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>
            <form class="overflow-y-auto p-5 space-y-6" id="editPromoForm">
                <input type="hidden" name="promo_id" id="edit_promo_id" />
                <div id="editPromoFeedback" class="hidden text-sm rounded-md p-3"></div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div class="space-y-1.5">
                        <label class="text-sm font-medium text-gray-700">Type</label>
                        <select name="promo_type" id="edit_promo_type" class="w-full border-gray-300 rounded-md text-sm focus:ring-blue-500 focus:border-blue-500" required>
                            <option value="product">Product</option>
                            <option value="appointment">Appointment</option>
                            <option value="general">General</option>
                        </select>
                    </div>
                    <div class="space-y-1.5">
                        <label class="text-sm font-medium text-gray-700">Code</label>
                        <input name="promo_code" id="edit_promo_code" type="text" maxlength="64" class="w-full border-gray-300 rounded-md text-sm focus:ring-blue-500 focus:border-blue-500" />
                    </div>
                    <div class="space-y-1.5 md:col-span-2">
                        <label class="text-sm font-medium text-gray-700">Name *</label>
                        <input name="promo_name" id="edit_promo_name" type="text" maxlength="120" required class="w-full border-gray-300 rounded-md text-sm focus:ring-blue-500 focus:border-blue-500" />
                    </div>
                    <div class="space-y-1.5 md:col-span-2">
                        <label class="text-sm font-medium text-gray-700">Description</label>
                        <textarea name="promo_description" id="edit_promo_description" rows="3" class="w-full border-gray-300 rounded-md text-sm focus:ring-blue-500 focus:border-blue-500"></textarea>
                    </div>
                    <div class="space-y-1.5">
                        <label class="text-sm font-medium text-gray-700">Discount Type</label>
                        <select name="promo_discount_type" id="edit_promo_discount_type" class="w-full border-gray-300 rounded-md text-sm focus:ring-blue-500 focus:border-blue-500">
                            <option value="percent">Percent %</option>
                            <option value="fixed">Fixed Amount</option>
                            <option value="points_bonus">Points Bonus</option>
                            <option value="free_item">Free Item</option>
                            <option value="none">None</option>
                        </select>
                    </div>
                    <div class="space-y-1.5">
                        <label class="text-sm font-medium text-gray-700">Discount Value</label>
                        <input name="promo_discount_value" id="edit_promo_discount_value" type="number" step="0.01" min="0" class="w-full border-gray-300 rounded-md text-sm focus:ring-blue-500 focus:border-blue-500" />
                    </div>
                    <div class="space-y-1.5">
                        <label class="text-sm font-medium text-gray-700">Points Cost</label>
                        <input name="promo_points_cost" id="edit_promo_points_cost" type="number" min="0" class="w-full border-gray-300 rounded-md text-sm focus:ring-blue-500 focus:border-blue-500" />
                    </div>
                    <div class="space-y-1.5">
                        <label class="text-sm font-medium text-gray-700">Min Purchase (₱)</label>
                        <input name="promo_min_purchase_amount" id="edit_promo_min_purchase_amount" type="number" step="0.01" min="0" class="w-full border-gray-300 rounded-md text-sm focus:ring-blue-500 focus:border-blue-500" />
                    </div>
                    <div class="space-y-1.5">
                        <label class="text-sm font-medium text-gray-700">Usage Limit</label>
                        <input name="promo_usage_limit" id="edit_promo_usage_limit" type="number" min="0" class="w-full border-gray-300 rounded-md text-sm focus:ring-blue-500 focus:border-blue-500" />
                    </div>
                    <div class="space-y-1.5">
                        <label class="text-sm font-medium text-gray-700">Per User Limit</label>
                        <input name="promo_per_user_limit" id="edit_promo_per_user_limit" type="number" min="0" class="w-full border-gray-300 rounded-md text-sm focus:ring-blue-500 focus:border-blue-500" />
                    </div>
                    <div class="space-y-1.5">
                        <label class="text-sm font-medium text-gray-700">Starts At</label>
                        <input name="promo_starts_at" id="edit_promo_starts_at" type="datetime-local" class="w-full border-gray-300 rounded-md text-sm focus:ring-blue-500 focus:border-blue-500" />
                    </div>
                    <div class="space-y-1.5">
                        <label class="text-sm font-medium text-gray-700">Ends At</label>
                        <input name="promo_ends_at" id="edit_promo_ends_at" type="datetime-local" class="w-full border-gray-300 rounded-md text-sm focus:ring-blue-500 focus:border-blue-500" />
                    </div>
                    <div class="space-y-2 md:col-span-2">
                        <label class="inline-flex items-center gap-2 text-sm font-medium text-gray-700">
                            <input type="checkbox" name="promo_require_active_subscription" id="edit_promo_require_active_subscription" value="1" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500" />
                            Require Active Subscription
                        </label>
                        <label class="inline-flex items-center gap-2 text-sm font-medium text-gray-700">
                            <input type="checkbox" name="promo_active" id="edit_promo_active" value="1" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500" />
                            Active
                        </label>
                    </div>
                </div>
                <div class="flex items-center justify-end gap-3 pt-2 border-t border-gray-200">
                    <button type="button" id="cancelEditPromoBtn" class="px-4 py-2 text-sm rounded-md bg-gray-100 hover:bg-gray-200 text-gray-700">Cancel</button>
                    <button type="submit" class="px-5 py-2.5 text-sm font-medium rounded-md bg-blue-600 hover:bg-blue-700 text-white shadow-sm">Update Promo</button>
                </div>
            </form>
        </div>
    </div>
    <!-- Top Selling Products Modal -->
    <div id="topSellingModal" class="fixed inset-0 bg-black/40 hidden items-center justify-center z-50">
        <div class="bg-white rounded-lg shadow-lg w-full max-w-3xl mx-4 flex flex-col max-h-[90vh]">
            <div class="flex items-center justify-between px-5 py-4 border-b border-gray-200">
                <div>
                    <h3 class="text-lg font-semibold text-gray-800">Top Selling Products</h3>
                    <p class="text-xs text-gray-500 mt-0.5">Ranked by total quantity ordered</p>
                </div>
                <button id="topSellingClose" class="text-gray-400 hover:text-gray-600" aria-label="Close"><i data-lucide="x" class="w-5 h-5"></i></button>
            </div>
            <div class="p-4 overflow-y-auto">
                <table class="w-full text-sm">
                    <thead class="text-xs uppercase text-gray-500 bg-gray-50">
                        <tr>
                            <th class="py-2 px-3 text-left">#</th>
                            <th class="py-2 px-3 text-left">Product</th>
                            <th class="py-2 px-3 text-left">Times Ordered</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php if(empty($topSellingProducts)): ?>
                            <tr><td colspan="3" class="py-6 text-center text-gray-500">No product orders yet.</td></tr>
                        <?php else: $rank=1; foreach($topSellingProducts as $p): ?>
                            <tr>
                                <td class="py-2 px-3 font-medium text-gray-700"><?= $rank++ ?></td>
                                <td class="py-2 px-3">
                                    <div class="flex items-center gap-3">
                                        <?php $img = htmlspecialchars($p['products_image_url'] ?? '',ENT_QUOTES); ?>
                                        <div class="w-12 h-12 rounded-md overflow-hidden bg-gray-100 flex items-center justify-center border border-gray-200">
                                            <?php if($img): ?>
                                                <img src="<?= htmlspecialchars(resolveImageUrl($img),ENT_QUOTES) ?>" alt="<?= htmlspecialchars($p['products_name'],ENT_QUOTES) ?>" class="w-full h-full object-cover"/>
                                            <?php else: ?>
                                                <i data-lucide="image" class="w-5 h-5 text-gray-400"></i>
                                            <?php endif; ?>
                                        </div>
                                        <span class="font-medium text-gray-800 leading-tight"><?= htmlspecialchars($p['products_name'],ENT_QUOTES) ?></span>
                                    </div>
                                </td>
                                <td class="py-2 px-3 text-gray-700 font-semibold"><?= (int)$p['total_qty'] ?></td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="px-5 py-3 border-t border-gray-200 flex justify-end">
                <button id="topSellingClose2" class="px-4 py-2 text-sm rounded-md bg-gray-100 hover:bg-gray-200 text-gray-700">Close</button>
            </div>
        </div>
    </div>
    
    <!-- Edit Appointment Modal -->
    <div id="editAppointmentModal" class="fixed inset-0 bg-black bg-opacity-40 hidden items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 w-full max-w-2xl mx-4">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold">Edit Appointment</h3>
                <button id="editApptClose" class="text-gray-400 hover:text-gray-600"><i data-lucide="x" class="w-5 h-5"></i></button>
            </div>
            <form id="editAppointmentForm" class="space-y-4">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="appointments_id" id="edit_appt_id">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Full Name</label>
                        <input type="text" name="appointments_full_name" id="edit_appt_full_name" class="mt-1 w-full border border-gray-300 rounded-md px-3 py-2">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Email</label>
                        <input type="email" name="appointments_email" id="edit_appt_email" class="mt-1 w-full border border-gray-300 rounded-md px-3 py-2">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Phone</label>
                        <input type="text" name="appointments_phone" id="edit_appt_phone" class="mt-1 w-full border border-gray-300 rounded-md px-3 py-2">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Pet Name</label>
                        <input type="text" name="appointments_pet_name" id="edit_appt_pet_name" class="mt-1 w-full border border-gray-300 rounded-md px-3 py-2">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Pet Type</label>
                        <input type="text" name="appointments_pet_type" id="edit_appt_pet_type" class="mt-1 w-full border border-gray-300 rounded-md px-3 py-2">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Breed</label>
                        <input type="text" name="appointments_pet_breed" id="edit_appt_pet_breed" class="mt-1 w-full border border-gray-300 rounded-md px-3 py-2">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Age (years)</label>
                        <input type="number" step="1" min="0" name="appointments_pet_age_years" id="edit_appt_pet_age" class="mt-1 w-full border border-gray-300 rounded-md px-3 py-2">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Type</label>
                        <select name="appointments_type" id="edit_appt_type" class="mt-1 w-full border border-gray-300 rounded-md px-3 py-2">
                            <option value="pet_sitting">Pet Sitting</option>
                            <option value="grooming">Grooming</option>
                            <option value="vet">Veterinary</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Date & Time</label>
                        <input type="datetime-local" name="appointments_date" id="edit_appt_datetime" class="mt-1 w-full border border-gray-300 rounded-md px-3 py-2">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Status</label>
                        <select name="appointments_status" id="edit_appt_status" class="mt-1 w-full border border-gray-300 rounded-md px-3 py-2">
                            <option value="pending">Pending</option>
                            <option value="confirmed">Confirmed</option>
                            <option value="completed">Completed</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Notes</label>
                    <textarea name="aa_notes" id="edit_appt_notes" rows="3" class="mt-1 w-full border border-gray-300 rounded-md px-3 py-2"></textarea>
                </div>
                <div class="flex items-center justify-end gap-2 pt-2">
                    <button type="button" id="editApptCancel" class="px-4 py-2 border border-gray-300 rounded-md hover:bg-gray-50">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Add Product Modal -->
    <div id="addProductModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 w-full max-w-md mx-4">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold">Add New Product</h3>
                <button onclick="closeAddProductModal()" class="text-gray-400 hover:text-gray-600">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>
            <form id="addProductForm" class="space-y-4" enctype="multipart/form-data">
                <input type="hidden" name="action" value="add">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Product Name</label>
                    <input type="text" name="products_name" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-orange-500">
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Product Pet Type</label>
                        <select name="products_pet_type" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-orange-500">
                            <option value="">Select pet type</option>
                            <option value="Dog">Dog</option>
                            <option value="Cat">Cat</option>
                            <option value="Bird">Bird</option>
                            <option value="Fish">Fish</option>
                            <option value="Small Pet">Small Pet</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Product Category</label>
                        <select name="products_category" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-orange-500">
                            <option value="">Select category</option>
                            <option value="food">Food</option>
                            <option value="accessories">Accessories</option>
                            <option value="grooming">Grooming</option>
                            <option value="treats">Treats</option>
                        </select>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Product Price (₱)</label>
                        <input type="number" name="products_price" required min="0" step="0.01" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-orange-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Product Stock</label>
                        <input type="number" name="products_stock" required min="0" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-orange-500">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Product Description</label>
                    <textarea name="products_description" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-orange-500"></textarea>
                </div>
                <div class="grid grid-cols-1 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Product Image (jpeg, png)</label>
                        <input type="file" name="products_image" id="products_image" accept="image/jpeg,image/png" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                        <div class="mt-2 flex items-center gap-3">
                            <div class="w-20 h-20 rounded border border-gray-200 overflow-hidden bg-gray-50 flex items-center justify-center">
                                <img id="imagePreview" alt="Preview" class="w-full h-full object-cover hidden" />
                                <span id="imagePlaceholder" class="text-xs text-gray-400">No image</span>
                            </div>
                            <button type="button" id="clearImageBtn" class="text-sm text-gray-600 underline hidden">Remove</button>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <input type="checkbox" id="products_active" name="products_active" class="h-4 w-4 text-orange-600 border-gray-300 rounded" checked>
                        <label for="products_active" class="text-sm text-gray-700">Product Active</label>
                    </div>
                </div>
                <div class="flex gap-2 pt-4">
                    <button type="submit" class="flex-1 bg-gradient-to-r from-orange-500 to-amber-600 hover:from-orange-600 hover:to-amber-700 text-white py-2 rounded-md">Add Product</button>
                    <button type="button" onclick="closeAddProductModal()" class="flex-1 border border-gray-300 text-gray-700 py-2 rounded-md hover:bg-gray-50">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Product Modal -->
    <div id="editProductModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 w-full max-w-md mx-4">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold">Edit Product</h3>
                <button onclick="closeEditProductModal()" class="text-gray-400 hover:text-gray-600">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>
            <form id="editProductForm" class="space-y-4" enctype="multipart/form-data">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="products_id" id="edit_products_id">
                <input type="hidden" name="current_image_url" id="edit_current_image_url">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Product Name</label>
                    <input type="text" name="products_name" id="edit_products_name" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-orange-500">
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Product Pet Type</label>
                        <select name="products_pet_type" id="edit_products_pet_type" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-orange-500">
                            <option value="">Select pet type</option>
                            <option value="Dog">Dog</option>
                            <option value="Cat">Cat</option>
                            <option value="Bird">Bird</option>
                            <option value="Fish">Fish</option>
                            <option value="Small Pet">Small Pet</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Product Category</label>
                        <select name="products_category" id="edit_products_category" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-orange-500">
                            <option value="">Select category</option>
                            <option value="food">Food</option>
                            <option value="accessories">Accessories</option>
                            <option value="grooming">Grooming</option>
                            <option value="treats">Treats</option>
                        </select>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Product Price (₱)</label>
                        <input type="number" name="products_price" id="edit_products_price" required min="0" step="0.01" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-orange-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Product Stock</label>
                        <input type="number" name="products_stock" id="edit_products_stock" required min="0" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-orange-500">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Product Description</label>
                    <textarea name="products_description" id="edit_products_description" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-orange-500"></textarea>
                </div>
                <div class="grid grid-cols-1 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Product Image (jpeg, png)</label>
                        <input type="file" name="products_image" id="edit_products_image" accept="image/jpeg,image/png" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                        <div class="mt-2 flex items-center gap-3">
                            <div class="w-20 h-20 rounded border border-gray-200 overflow-hidden bg-gray-50 flex items-center justify-center">
                                <img id="edit_imagePreview" alt="Preview" class="w-full h-full object-cover hidden" />
                                <span id="edit_imagePlaceholder" class="text-xs text-gray-400">No image</span>
                            </div>
                            <button type="button" id="edit_clearImageBtn" class="text-sm text-gray-600 underline hidden">Remove</button>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <input type="checkbox" id="edit_products_active" name="products_active" class="h-4 w-4 text-orange-600 border-gray-300 rounded">
                        <label for="edit_products_active" class="text-sm text-gray-700">Product Active</label>
                    </div>
                </div>
                <div class="flex gap-2 pt-4">
                    <button type="submit" class="flex-1 bg-gradient-to-r from-orange-500 to-amber-600 hover:from-orange-600 hover:to-amber-700 text-white py-2 rounded-md">Update Product</button>
                    <button type="button" onclick="closeEditProductModal()" class="flex-1 border border-gray-300 text-gray-700 py-2 rounded-md hover:bg-gray-50">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Mock data
        const transactionData = {
            daily: {
                revenue: 15420,
                transactions: 45,
                growth: 12.5,
                chartData: [
                    { name: 'Mon', value: 2400 },
                    { name: 'Tue', value: 1398 },
                    { name: 'Wed', value: 9800 },
                    { name: 'Thu', value: 3908 },
                    { name: 'Fri', value: 4800 },
                    { name: 'Sat', value: 3800 },
                    { name: 'Sun', value: 4300 }
                ]
            },
            weekly: {
                revenue: 87550,
                transactions: 312,
                growth: 8.3,
                chartData: [
                    { name: 'W1', value: 24000 },
                    { name: 'W2', value: 13980 },
                    { name: 'W3', value: 23800 },
                    { name: 'W4', value: 25770 }
                ]
            },
            monthly: {
                revenue: 342680,
                transactions: 1247,
                growth: 15.7,
                chartData: [
                    { name: 'Jan', value: 24000 },
                    { name: 'Feb', value: 13980 },
                    { name: 'Mar', value: 29800 },
                    { name: 'Apr', value: 33908 },
                    { name: 'May', value: 48000 },
                    { name: 'Jun', value: 38000 },
                    { name: 'Jul', value: 43000 },
                    { name: 'Aug', value: 35000 },
                    { name: 'Sep', value: 42000 },
                    { name: 'Oct', value: 38000 },
                    { name: 'Nov', value: 45000 },
                    { name: 'Dec', value: 41992 }
                ]
            },
            annual: {
                revenue: 4112160,
                transactions: 14964,
                growth: 22.1,
                chartData: [
                    { name: '2021', value: 2400000 },
                    { name: '2022', value: 3200000 },
                    { name: '2023', value: 3800000 },
                    { name: '2024', value: 4112160 }
                ]
            }
        };

       

        const mockAppointments = [
            {
                id: 1,
                petOwner: "John Doe",
                petName: "Buddy",
                sitter: "Mari Santos",
                service: "pet-sitting",
                date: "2025-01-25",
                time: "09:00 AM",
                duration: "4 hours",
                status: "confirmed",
                amount: 800
            },
            {
                id: 2,
                petOwner: "Jane Smith",
                petName: "Whiskers",
                sitter: "Anna Cruz",
                service: "grooming",
                date: "2025-01-26",
                time: "02:00 PM",
                duration: "2 hours",
                status: "pending",
                amount: 650
            },
            {
                id: 3,
                petOwner: "Mike Johnson",
                petName: "Max",
                sitter: "Mari Santos",
                service: "vet",
                date: "2025-01-24",
                time: "11:00 AM",
                duration: "1 hour",
                status: "completed",
                amount: 1200
            }
        ];

        const mockPetOwners = [
            {
                id: 1,
                name: "John Doe",
                email: "john@email.com",
                phone: "+63 912 345 6789",
                pets: [
                    { name: "Buddy", type: "Dog", breed: "Golden Retriever", age: 3 },
                    { name: "Luna", type: "Cat", breed: "Persian", age: 2 }
                ],
                joinDate: "2024-03-15",
                totalBookings: 12,
                status: "active"
            },
            {
                id: 2,
                name: "Jane Smith",
                email: "jane@email.com",
                phone: "+63 917 234 5678",
                pets: [
                    { name: "Whiskers", type: "Cat", breed: "Maine Coon", age: 4 }
                ],
                joinDate: "2024-06-20",
                totalBookings: 8,
                status: "active"
            }
        ];

        const mockSubscribers = [
            {
                id: 1,
                email: "subscriber1@email.com",
                subscribeDate: "2024-12-01",
                status: "active",
                source: "website"
            },
            {
                id: 2,
                email: "subscriber2@email.com",
                subscribeDate: "2024-11-28",
                status: "active",
                source: "social_media"
            }
        ];

        // Global state with persistence of last active section
        let currentActiveSection = (function(){
            try { return localStorage.getItem('admin_active_section') || 'dashboard'; } catch(e){ return 'dashboard'; }
        })();
        let sidebarExpanded = false;
        let sidebarLocked = false;
        let currentTimeFilter = 'monthly';

        // Initialize the app
        document.addEventListener('DOMContentLoaded', function() {
            lucide.createIcons();
            updateChart();

            // Initialize all interactive sections
            initProductFilters();
            initOrdersSectionFilters();
            initOwnersDirectory();
            initAppointments();
            // (Removed appointments refresh button & handler per request)
            initSubscribersFilters();
            initSitters();
            initTopSellingModal();
            initSettingsSection();
            // Unified Dark Mode (quick button + section button + settings switch)
            (function(){
                const STORAGE_KEY='admin_theme_mode';
                const quickBtn = document.getElementById('darkModeQuickBtn');
                const sectionBtn = document.getElementById('darkModeSectionBtn');
                const masterToggle = document.getElementById('darkModeMasterToggle');
                const knob = document.getElementById('darkModeKnob');
                const slider = document.getElementById('darkModeSlider');
                const statusEl = document.getElementById('darkModeStatus');

                function applyState(on){
                    document.body.classList.toggle('dark', on);
                    document.documentElement.classList.toggle('dark', on);
                    if(masterToggle) masterToggle.checked = on;
                    if(knob && slider){
                        knob.style.transform = on ? 'translateX(1.25rem)' : 'translateX(0)';
                        slider.classList.toggle('bg-indigo-600', on);
                        slider.classList.toggle('bg-gray-300', !on);
                    }
                    [quickBtn, sectionBtn].forEach(btn=>{
                        if(!btn) return;
                        const icon = btn.querySelector('i');
                        if(icon){ icon.setAttribute('data-lucide', on ? 'sun' : 'moon'); }
                        const lbl = btn.querySelector('.mode-label');
                        if(lbl) lbl.textContent = on ? 'Light' : 'Dark';
                        btn.classList.toggle('bg-gray-800', on);
                        btn.classList.toggle('text-white', on);
                    });
                    if(statusEl) statusEl.textContent = 'Theme: ' + (on?'Dark':'Light');
                    try{ localStorage.setItem(STORAGE_KEY, on?'dark':'light'); }catch(e){}
                    if(window.lucide){ lucide.createIcons(); }
                }
                function toggle(){ applyState(!document.body.classList.contains('dark')); }
                let saved=null; try{ saved=localStorage.getItem(STORAGE_KEY);}catch(e){}
                applyState(saved==='dark');
                quickBtn?.addEventListener('click', toggle);
                sectionBtn?.addEventListener('click', toggle);
                masterToggle?.addEventListener('change', ()=> applyState(masterToggle.checked));
            })();
            // Recent Activity & Notifications
            (function(){
                const STORAGE_KEY='admin_recent_activity';
                const POLL_KEY='admin_recent_activity_latest_ts';
                const panel = document.getElementById('recentActivityPanel');
                const listEl = document.getElementById('activityList');
                const metaEl = document.getElementById('activityMeta');
                const badge = document.getElementById('activityBadge');
                const btn = document.getElementById('notificationsBtn');
                const closeBtn = document.getElementById('closeActivityBtn');
                const clearBtn = document.getElementById('clearActivityBtn');
                const openSettingsBtn = document.getElementById('openSettingsBtn');

                function load(){
                    try { return JSON.parse(localStorage.getItem(STORAGE_KEY)||'[]'); } catch(e){ return []; }
                }
                function save(items){ try { localStorage.setItem(STORAGE_KEY, JSON.stringify(items.slice(0,100))); } catch(e){} }
                function add(type, message, meta={}){
                    const items = load();
                    items.unshift({ id:Date.now()+Math.random().toString(36).slice(2), type, message, meta, ts: Date.now(), read:false });
                    save(items); render();
                }
                function addRawEvent(ev){
                    // Avoid duplicates by composite key (type-id)
                    const key = `${ev.type}-${ev.id}`;
                    const items = load();
                    if(items.some(i=>i.meta && i.meta.key === key)) return; // skip existing
                    const labelMap = {
                        appointment: 'New appointment booked: '+ev.label,
                        order: 'Products checkout completed: '+ev.label,
                        user: 'New user registered: '+ev.label,
                        subscriber: 'New subscriber activated: '+ev.label,
                        sitter: 'New pet sitter: '+ev.label,
                        pet: 'New pet registered: '+ev.label
                    };
                    items.unshift({ id: key+ '-' + ev.ts, type: ev.type, message: labelMap[ev.type]||ev.label, meta:{key}, ts: ev.ts, read:false });
                    save(items); render();
                }
                function timeAgo(ts){
                    const diff = Date.now()-ts; const sec=Math.floor(diff/1000);
                    if(sec<60) return sec+'s ago'; const m=Math.floor(sec/60); if(m<60) return m+'m ago'; const h=Math.floor(m/60); if(h<24) return h+'h ago'; const d=Math.floor(h/24); return d+'d ago';
                }
                function iconFor(type){
                    switch(type){
                        case 'order': return 'package';
                        case 'appointment': return 'calendar';
                        case 'promo': return 'ticket';
                        case 'user': return 'user';
                        default: return 'activity';
                    }
                }
                function render(){
                    if(!listEl) return; const items = load();
                    const unread = items.filter(i=>!i.read).length;
                    if(badge){ badge.textContent = unread>9?'9+':String(unread); badge.classList.toggle('hidden', unread===0); }
                    if(metaEl) metaEl.textContent = items.length+ ' item'+(items.length===1?'':'s');
                    if(items.length===0){ listEl.innerHTML = '<div class="p-6 text-sm text-gray-500 dark:text-gray-400 text-center">No recent activity.</div>'; return; }
                    listEl.innerHTML = items.map(i=>`
                        <button data-id="${i.id}" class="w-full text-left px-4 py-3 flex gap-3 hover:bg-gray-50 dark:hover:bg-[#273349] focus:outline-none transition group ${i.read?'opacity-70':''}">
                            <div class="mt-0.5 w-9 h-9 rounded-full flex items-center justify-center bg-gray-100 dark:bg-[#273349] group-hover:scale-105 transition">
                                <i data-lucide="${iconFor(i.type)}" class="w-4 h-4 text-orange-600 dark:text-orange-400"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-xs font-medium text-gray-700 dark:text-gray-200 line-clamp-2">${i.message}</p>
                                <p class="mt-0.5 text-[10px] uppercase tracking-wide text-gray-400 dark:text-gray-500 flex items-center gap-2">${timeAgo(i.ts)} ${i.read?'':'<span class="inline-block w-1.5 h-1.5 rounded-full bg-orange-500"></span>'}</p>
                            </div>
                        </button>`).join('');
                    if(window.lucide){ lucide.createIcons(); }
                }
                function markAllRead(){ const items = load().map(i=>({...i,read:true})); save(items); render(); }
                function togglePanel(show){ if(!panel) return; panel.classList.toggle('hidden', show===false ? true : panel.classList.contains('hidden')?false:true); if(!panel.classList.contains('hidden')){ markAllRead(); } }

                // Seed sample events on first load
                (function seed(){ const items = load(); if(items.length===0){
                    add('order','New order placed (Ref #'+Math.floor(Math.random()*10000)+')');
                    add('appointment','Appointment booked for tomorrow');
                    add('promo','Promo SPRING10 was created');
                }} )();
                render();

                // Poll server for new domain events
                let latestTs = 0; try { latestTs = parseInt(localStorage.getItem(POLL_KEY)||'0'); } catch(e){}
                async function poll(){
                    try {
                        const r = await fetch('../../controllers/admin/activitypoll.php?since='+latestTs+'&_=' + Date.now());
                        if(!r.ok) return; const data = await r.json(); if(!data.success) return;
                        if(Array.isArray(data.items)) data.items.reverse().forEach(addRawEvent); // reverse to keep chronological insertion
                        if(data.latest && data.latest>latestTs){ latestTs = data.latest; try { localStorage.setItem(POLL_KEY,String(latestTs)); } catch(e){} }
                    } catch(e){ /* silent */ }
                }
                poll();
                setInterval(poll, 15000);

                btn?.addEventListener('click', (e)=>{ e.stopPropagation(); togglePanel(); });
                closeBtn?.addEventListener('click', ()=> togglePanel(false));
                clearBtn?.addEventListener('click', ()=>{ save([]); render(); });
                document.addEventListener('click', (e)=>{ if(panel && !panel.contains(e.target) && e.target!==btn && !btn.contains(e.target)){ panel.classList.add('hidden'); } });
                listEl?.addEventListener('click', (e)=>{ const b=e.target.closest('button[data-id]'); if(!b) return; /* potential item action later */ });
                // Settings navigation
                openSettingsBtn?.addEventListener('click', ()=>{ if(typeof showSection==='function'){ showSection('settings'); } });
                // Expose helper to global (optional future use)
                window.AdminActivity = { add };
            })();
            // Section isolation logic: ensure only active section's UI is visible
            function showSection(id){
                // Hide all existing *-section containers dynamically
                document.querySelectorAll('[id$="-section"]').forEach(el => el.classList.add('hidden'));
                const el = document.getElementById(id+'-section');
                if (el) el.classList.remove('hidden');
                currentActiveSection = id;
                // Persist
                try { localStorage.setItem('admin_active_section', id); } catch(e){}
                // Update sidebar active classes
                document.querySelectorAll('.sidebar-item').forEach(item => {
                    item.classList.remove('bg-gradient-to-r','from-orange-500','to-amber-600','text-white','shadow-md');
                    item.classList.add('text-gray-700','hover:bg-gray-100');
                });
                const activeItem = document.querySelector(`[data-section="${id}"]`);
                if (activeItem) {
                    activeItem.classList.add('bg-gradient-to-r','from-orange-500','to-amber-600','text-white','shadow-md');
                    activeItem.classList.remove('text-gray-700','hover:bg-gray-100');
                }
                // Initialize Audit UI if navigating to Audit section
                if (id === 'audit') {
                    if (typeof initAuditSectionOnce === 'function') initAuditSectionOnce();
                    if (typeof refreshAudit === 'function') refreshAudit();
                }
            }
            // Bind nav items marked with data-section
            document.querySelectorAll('[data-section]')?.forEach(btn=>{
                btn.addEventListener('click', e=>{
                    const id = btn.getAttribute('data-section');
                    if(id) showSection(id);
                });
            });
            // Prefer section from URL (?section=...), fallback to last stored, then dashboard
            try {
                const urlSec = new URLSearchParams(window.location.search).get('section');
                if (urlSec && document.getElementById(urlSec+'-section')) {
                    currentActiveSection = urlSec;
                }
            } catch(e){}
            // Initialize to existing or default
            if(!document.getElementById(currentActiveSection+'-section')) currentActiveSection='dashboard';
            showSection(currentActiveSection);
            // Floating Promo Modal + AJAX
            const addPromoModal = document.getElementById('addPromoModal');
            const openAddPromoBtn = document.getElementById('openAddPromoBtn');
            const closeAddPromoBtn = document.getElementById('closeAddPromoBtn');
            const cancelAddPromoBtn = document.getElementById('cancelAddPromoBtn');
            const addPromoForm = document.getElementById('addPromoForm');
            const addPromoFeedback = document.getElementById('addPromoFeedback');
            const promosTableWrapper = document.getElementById('promosTableWrapper');

            function openAddPromo(){ if(addPromoModal){ addPromoModal.classList.remove('hidden'); addPromoModal.classList.add('flex'); } }
            function closeAddPromo(){ if(addPromoModal){ addPromoModal.classList.add('hidden'); addPromoModal.classList.remove('flex'); addPromoForm?.reset(); hidePromoFeedback(); } }
            function showPromoFeedback(msg,type='error'){
                if(!addPromoFeedback) return;
                addPromoFeedback.textContent = msg;
                addPromoFeedback.className = 'block text-sm rounded-md p-3 ' + (type==='success' ? 'bg-green-50 border border-green-200 text-green-700' : 'bg-red-50 border border-red-200 text-red-700');
            }
            function hidePromoFeedback(){ if(addPromoFeedback){ addPromoFeedback.classList.add('hidden'); addPromoFeedback.textContent=''; } }

            openAddPromoBtn?.addEventListener('click', openAddPromo);
            closeAddPromoBtn?.addEventListener('click', closeAddPromo);
            cancelAddPromoBtn?.addEventListener('click', (e)=>{ e.preventDefault(); closeAddPromo(); });
            addPromoModal?.addEventListener('click', (e)=>{ if(e.target === addPromoModal) closeAddPromo(); });

            async function refreshPromos(){
                try {
                    const res = await fetch('../../controllers/admin/promocontroller.php?action=list',{credentials:'same-origin'});
                    const data = await res.json();
                    if(!data.success) return;
                    window.__allPromos = data.promotions || [];
                    buildPromoTypeFilter();
                    applyPromoFilters();
                } catch(e){ console.error('Failed to refresh promos', e); }
            }

            // === Promo Filtering & Pagination ===
            const promoFilterType = document.getElementById('promoFilterType');
            const promoFilterDiscount = document.getElementById('promoFilterDiscount');
            const promoFilterPoints = document.getElementById('promoFilterPoints');
            const promoFilterLimits = document.getElementById('promoFilterLimits');
            const promoFilterWindow = document.getElementById('promoFilterWindow');
            const promoFilterStatus = document.getElementById('promoFilterStatus');
            const promoFiltersReset = document.getElementById('promoFiltersReset');
            const promosShownEl = document.getElementById('promosShown');
            const promosTotalFilteredEl = document.getElementById('promosTotalFiltered');
            const promosTotalAllEl = document.getElementById('promosTotalAll');
            const promoPrev = document.getElementById('promoPrev');
            const promoNext = document.getElementById('promoNext');
            const promoPageNums = document.getElementById('promoPageNums');
            const promoPager = document.getElementById('promoPager');
            let promoPage = 1; const PROMO_PAGE_SIZE = 15;

            function buildPromoTypeFilter(){
                if(!promoFilterType) return;
                const types = Array.from(new Set((window.__allPromos||[]).map(p=>p.promo_type||'').filter(v=>v!==''))).sort();
                const current = promoFilterType.value;
                promoFilterType.innerHTML = '<option value="">All</option>' + types.map(t=>`<option value="${escapeHtml(t)}">${escapeHtml(t)}</option>`).join('');
                if(types.includes(current)) promoFilterType.value=current; // preserve
            }

            function classifyLimits(p){
                const g = p.promo_usage_limit == null ? '∞' : 'L';
                const u = p.promo_per_user_limit == null ? '∞' : 'L';
                if(g==='∞' && u==='∞') return 'fully_unlimited';
                if(g==='∞' && u==='L') return 'global_unlimited';
                if(g==='L' && u==='∞') return 'user_unlimited';
                return 'limited';
            }
            function classifyWindow(p){
                const now = new Date();
                const start = p.promo_starts_at? new Date(p.promo_starts_at.replace(' ','T')): null;
                const end = p.promo_ends_at? new Date(p.promo_ends_at.replace(' ','T')): null;
                if(!start && !end) return 'perpetual';
                if(start && now < start) return 'upcoming';
                if(end && now > end) return 'expired';
                return 'active';
            }

            function applyPromoFilters(){
                const all = window.__allPromos || [];
                promosTotalAllEl && (promosTotalAllEl.textContent = all.length.toString());
                let filtered = all.filter(p=>{
                    if(promoFilterType?.value && p.promo_type!==promoFilterType.value) return false;
                    if(promoFilterDiscount?.value && (p.promo_discount_type||'')!==promoFilterDiscount.value) return false;
                    if(promoFilterPoints?.value){
                        if(promoFilterPoints.value==='required' && (p.promo_points_cost==null || p.promo_points_cost==='')) return false;
                        if(promoFilterPoints.value==='free' && (p.promo_points_cost!=null && p.promo_points_cost!=='')) return false;
                    }
                    if(promoFilterLimits?.value && classifyLimits(p)!==promoFilterLimits.value) return false;
                    if(promoFilterWindow?.value && classifyWindow(p)!==promoFilterWindow.value) return false;
                    if(promoFilterStatus?.value!=='' && String(p.promo_active)!==promoFilterStatus.value) return false;
                    return true;
                });
                const totalFiltered = filtered.length;
                if(promoPage>Math.max(1,Math.ceil(totalFiltered / PROMO_PAGE_SIZE))) promoPage = 1; // reset if overflow
                promosTotalFilteredEl && (promosTotalFilteredEl.textContent = totalFiltered.toString());

                const start = (promoPage-1)*PROMO_PAGE_SIZE;
                const pageItems = filtered.slice(start, start + PROMO_PAGE_SIZE);
                const tbody = document.getElementById('promosTbody');
                if(!tbody) return;
                tbody.innerHTML = '';
                if(pageItems.length===0){
                    tbody.innerHTML = '<tr><td colspan="8" class="px-3 py-6 text-center text-gray-500">No promos match the filters.</td></tr>';
                } else {
                    pageItems.forEach(p=>{
                        const limitsTxt = `${p.promo_usage_limit?p.promo_usage_limit:'∞'} / ${p.promo_per_user_limit?p.promo_per_user_limit:'∞'}`;
                        const windowTxt = `${p.promo_starts_at?p.promo_starts_at.split(' ')[0]:'—'} → ${p.promo_ends_at?p.promo_ends_at.split(' ')[0]:'—'}`;
                        const activeBadge = p.promo_active==1 ? '<span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium bg-green-100 text-green-700 border border-green-200">Active</span>' : '<span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium bg-gray-100 text-gray-600 border border-gray-200">Inactive</span>';
                        const tr = document.createElement('tr');
                        tr.className='border-b last:border-b-0 hover:bg-orange-50/50';
                        tr.innerHTML = `
                            <td class='px-3 py-2 align-top'><div class="font-medium text-gray-800 truncate max-w-[160px]" title="${escapeHtml(p.promo_name||'')}">${escapeHtml(p.promo_name||'')}</div><div class="text-xs text-gray-500">Code: ${escapeHtml(p.promo_code||'—')}</div></td>
                            <td class='px-3 py-2 align-top text-xs capitalize'>${escapeHtml(p.promo_type||'')}</td>
                            <td class='px-3 py-2 align-top text-xs'><div class="font-medium">${escapeHtml(p.promo_discount_type||'none')}</div><div class="text-gray-500">${p.promo_discount_value?escapeHtml(p.promo_discount_value):'—'}</div></td>
                            <td class='px-3 py-2 align-top text-xs'>${p.promo_points_cost || '—'}</td>
                            <td class='px-3 py-2 align-top text-xs'>${limitsTxt}</td>
                            <td class='px-3 py-2 align-top text-xs leading-tight'>${windowTxt}</td>
                            <td class='px-3 py-2 align-top'>${activeBadge}</td>
                            <td class='px-3 py-2 align-top text-xs'><div class='flex items-center gap-2 flex-wrap'>
                                <button data-promo-edit='${p.promo_id}' class='px-2 py-1 rounded border border-blue-300 bg-blue-50 text-blue-600 hover:bg-blue-100 text-[11px]'>Edit</button>
                                <button data-promo-toggle='${p.promo_id}' data-to='${p.promo_active==1?0:1}' class='px-2 py-1 rounded border text-[11px] ${p.promo_active==1?'bg-white hover:bg-gray-50':'bg-green-600 border-green-600 text-white hover:bg-green-700'}'>${p.promo_active==1?'Deactivate':'Activate'}</button>
                                <button data-promo-delete='${p.promo_id}' class='px-2 py-1 rounded border border-red-300 bg-red-50 text-red-600 hover:bg-red-100 text-[11px]'>Delete</button>
                            </div></td>`;
                        tbody.appendChild(tr);
                    });
                }
                // counts
                promosShownEl && (promosShownEl.textContent = totalFiltered===0? '0' : `${start+1}-${start+pageItems.length}`);
                buildPromoPagination(totalFiltered);
                lucide.createIcons();
            }

            function buildPromoPagination(total){
                if(!promoPager || !promoPageNums) return;
                const pages = Math.max(1, Math.ceil(total / PROMO_PAGE_SIZE));
                if(total <= PROMO_PAGE_SIZE){ promoPager.style.display='none'; return; }
                promoPager.style.display='flex';
                promoPrev.disabled = promoPage<=1; promoNext.disabled = promoPage>=pages;
                promoPageNums.innerHTML='';
                const range = promoPaginationRange(promoPage, pages, 7);
                range.forEach(r=>{
                    const b = document.createElement('button');
                    b.className = 'px-2 py-1 rounded border text-[11px] ' + (r===promoPage?'bg-orange-600 border-orange-600 text-white':'border-gray-300 bg-white hover:bg-gray-100');
                    b.textContent = r==='...' ? '...' : r;
                    if(r!=='...') b.addEventListener('click', ()=>{ promoPage = r; applyPromoFilters(); });
                    promoPageNums.appendChild(b);
                });
            }
            function promoPaginationRange(current,total,maxButtons){
                const out=[]; if(total<=maxButtons){ for(let i=1;i<=total;i++) out.push(i); return out; }
                const half=Math.floor(maxButtons/2); let start=Math.max(1,current-half); let end=start+maxButtons-1; if(end>total){ end=total; start=end-maxButtons+1; }
                if(start>1){ out.push(1); if(start>2) out.push('...'); }
                for(let i=start;i<=end;i++) out.push(i);
                if(end<total){ if(end<total-1) out.push('...'); out.push(total); }
                return out;
            }

            // Event bindings for filters
            [promoFilterType,promoFilterDiscount,promoFilterPoints,promoFilterLimits,promoFilterWindow,promoFilterStatus].forEach(el=>{
                el && el.addEventListener('change', ()=>{ promoPage=1; applyPromoFilters(); });
            });
            promoFiltersReset?.addEventListener('click', ()=>{
                [promoFilterType,promoFilterDiscount,promoFilterPoints,promoFilterLimits,promoFilterWindow,promoFilterStatus].forEach(el=>{ if(el) el.value=''; });
                promoPage=1; applyPromoFilters();
            });
            promoPrev?.addEventListener('click', ()=>{ if(promoPage>1){ promoPage--; applyPromoFilters(); }});
            promoNext?.addEventListener('click', ()=>{ promoPage++; applyPromoFilters(); });

            addPromoForm?.addEventListener('submit', async function(e){
                e.preventDefault();
                hidePromoFeedback();
                const fd = new FormData(addPromoForm);
                fd.append('action','add');
                try {
                    const res = await fetch('../../controllers/admin/promocontroller.php', { method:'POST', body:fd, credentials:'same-origin' });
                    const data = await res.json();
                    if(!data.success){ showPromoFeedback(data.message||'Failed to add promo','error'); return; }
                    showPromoFeedback('Promo saved!','success');
                    await refreshPromos();
                    setTimeout(()=>{ closeAddPromo(); }, 600);
                } catch(err){ console.error(err); showPromoFeedback('Network or server error','error'); }
            });

            document.addEventListener('click', async function(e){
                const toggleBtn = e.target.closest('[data-promo-toggle]');
                const delBtn = e.target.closest('[data-promo-delete]');
                const editBtn = e.target.closest('[data-promo-edit]');
                if(toggleBtn){
                    const id = toggleBtn.getAttribute('data-promo-toggle');
                    const to = toggleBtn.getAttribute('data-to');
                    const fd = new FormData(); fd.append('action','toggle'); fd.append('promo_id',id); fd.append('to',to);
                    try { await fetch('../../controllers/admin/promocontroller.php', { method:'POST', body:fd, credentials:'same-origin' }); refreshPromos(); } catch(err){ console.error(err); }
                } else if(delBtn){
                    if(!confirm('Delete this promotion?')) return;
                    const id = delBtn.getAttribute('data-promo-delete');
                    const fd = new FormData(); fd.append('action','delete'); fd.append('promo_id',id);
                    try { await fetch('../../controllers/admin/promocontroller.php', { method:'POST', body:fd, credentials:'same-origin' }); refreshPromos(); } catch(err){ console.error(err); }
                } else if(editBtn){
                    const id = editBtn.getAttribute('data-promo-edit');
                    await openEditPromo(id);
                }
            });

            // Edit promo modal logic
            const editPromoModal = document.getElementById('editPromoModal');
            const closeEditPromoBtn = document.getElementById('closeEditPromoBtn');
            const cancelEditPromoBtn = document.getElementById('cancelEditPromoBtn');
            const editPromoForm = document.getElementById('editPromoForm');
            const editPromoFeedback = document.getElementById('editPromoFeedback');

            function showEditModal(){ editPromoModal.classList.remove('hidden'); editPromoModal.classList.add('flex'); }
            function hideEditModal(){ editPromoModal.classList.add('hidden'); editPromoModal.classList.remove('flex'); editPromoForm.reset(); hideEditFeedback(); }
            function showEditFeedback(msg,type='error'){
                if(!editPromoFeedback) return;
                editPromoFeedback.textContent = msg;
                editPromoFeedback.className = 'block text-sm rounded-md p-3 ' + (type==='success' ? 'bg-green-50 border border-green-200 text-green-700' : 'bg-red-50 border border-red-200 text-red-700');
            }
            function hideEditFeedback(){ if(editPromoFeedback){ editPromoFeedback.classList.add('hidden'); editPromoFeedback.textContent=''; } }
            closeEditPromoBtn?.addEventListener('click', hideEditModal);
            cancelEditPromoBtn?.addEventListener('click', (e)=>{ e.preventDefault(); hideEditModal(); });
            editPromoModal?.addEventListener('click', (e)=>{ if(e.target===editPromoModal) hideEditModal(); });

            async function openEditPromo(id){
                hideEditFeedback();
                try {
                    const res = await fetch('../../controllers/admin/promocontroller.php?action=get&promo_id='+encodeURIComponent(id), {credentials:'same-origin'});
                    const data = await res.json();
                    if(!data.success){ alert(data.message||'Failed to load promo'); return; }
                    const p = data.promo;
                    document.getElementById('edit_promo_id').value = p.promo_id;
                    document.getElementById('edit_promo_type').value = p.promo_type||'';
                    document.getElementById('edit_promo_code').value = p.promo_code||'';
                    document.getElementById('edit_promo_name').value = p.promo_name||'';
                    document.getElementById('edit_promo_description').value = p.promo_description||'';
                    document.getElementById('edit_promo_discount_type').value = p.promo_discount_type||'none';
                    document.getElementById('edit_promo_discount_value').value = p.promo_discount_value!==null? p.promo_discount_value:'';
                    document.getElementById('edit_promo_points_cost').value = p.promo_points_cost!==null? p.promo_points_cost:'';
                    document.getElementById('edit_promo_min_purchase_amount').value = p.promo_min_purchase_amount!==null? p.promo_min_purchase_amount:'';
                    document.getElementById('edit_promo_usage_limit').value = p.promo_usage_limit!==null? p.promo_usage_limit:'';
                    document.getElementById('edit_promo_per_user_limit').value = p.promo_per_user_limit!==null? p.promo_per_user_limit:'';
                    document.getElementById('edit_promo_require_active_subscription').checked = p.promo_require_active_subscription==1;
                    document.getElementById('edit_promo_active').checked = p.promo_active==1;
                    // Convert timestamps to datetime-local value (YYYY-MM-DDTHH:MM)
                    function toLocal(dt){ if(!dt) return ''; return dt.replace(' ','T').substring(0,16); }
                    document.getElementById('edit_promo_starts_at').value = toLocal(p.promo_starts_at);
                    document.getElementById('edit_promo_ends_at').value = toLocal(p.promo_ends_at);
                    showEditModal();
                    lucide.createIcons();
                } catch(err){ console.error(err); alert('Network error loading promo'); }
            }

            editPromoForm?.addEventListener('submit', async function(e){
                e.preventDefault();
                hideEditFeedback();
                const fd = new FormData(editPromoForm);
                fd.append('action','update');
                // Adjust checkboxes (explicit 0 if unchecked)
                if(!fd.get('promo_require_active_subscription')) fd.append('promo_require_active_subscription','0');
                if(!fd.get('promo_active')) fd.append('promo_active','0');
                try {
                    const res = await fetch('../../controllers/admin/promocontroller.php', { method:'POST', body:fd, credentials:'same-origin' });
                    const data = await res.json();
                    if(!data.success){ showEditFeedback(data.message||'Update failed','error'); return; }
                    showEditFeedback('Updated successfully','success');
                    await refreshPromos();
                    setTimeout(()=>{ hideEditModal(); },600);
                } catch(err){ console.error(err); showEditFeedback('Network or server error','error'); }
            });

            function escapeHtml(str){ return (str||'').replace(/[&<>"']/g, c=>({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;','\'':'&#39;' }[c]||c)); }
            // Load promos when navigating to section
            const promosNav = document.querySelector('[data-section="promos"]');
            promosNav?.addEventListener('click', ()=>{ refreshPromos(); });
            // Orders edit/delete handlers
            function initOrders(){
                const editModal = document.getElementById('orderEditModal');
                const form = document.getElementById('orderEditForm');
                const saveBtn = document.getElementById('orderEditSaveBtn');
                const feedback = document.getElementById('orderEditFeedback');
                const tidInput = document.getElementById('order_edit_tid');
                const statusSel = document.getElementById('order_edit_status');
                const etaInput = document.getElementById('order_edit_eta');
                const actualInput = document.getElementById('order_edit_actual');
                const sigInput = document.getElementById('order_edit_signature');
                const closeBtns = document.querySelectorAll('[data-close-order]');
                // Toast helper
                function showToast(msg,type='success'){
                    let wrap = document.getElementById('adminToastWrap');
                    if(!wrap){
                        wrap = document.createElement('div');
                        wrap.id='adminToastWrap';
                        wrap.className='fixed z-[999] top-4 right-4 space-y-2 w-72';
                        document.body.appendChild(wrap);
                    }
                    const el = document.createElement('div');
                    el.className='px-4 py-3 rounded-md shadow border text-sm flex items-start gap-2 animate-fade-in '+(type==='error'?'bg-red-50 border-red-200 text-red-700':'bg-emerald-50 border-emerald-200 text-emerald-800');
                    el.innerHTML = '<span class="flex-1">'+msg+'</span><button class="text-xs text-gray-400 hover:text-gray-700">&times;</button>';
                    el.querySelector('button').onclick=()=>{ el.remove(); };
                    wrap.appendChild(el);
                    setTimeout(()=>{ el.classList.add('opacity-0','translate-x-2'); setTimeout(()=>el.remove(),300); }, 3500);
                }

                function open(){ editModal.classList.remove('hidden'); editModal.classList.add('flex'); }
                function close(){ editModal.classList.add('hidden'); editModal.classList.remove('flex'); form.reset(); feedback.textContent=''; }
                closeBtns.forEach(b=> b.addEventListener('click', close));
                editModal?.addEventListener('click', e=>{ if(e.target===editModal) close(); });

                async function fetchOrder(tid){
                    feedback.textContent='Loading...';
                    try {
                        const res = await fetch(`../../controllers/admin/ordercontroller.php?action=get&transactions_id=${tid}`, {headers:{'Accept':'application/json'}});
                        const data = await res.json();
                        if(!data.success) throw new Error(data.error||'Failed');
                        const o = data.order;
                        statusSel.value = o.deliveries_delivery_status || 'processing';
                        etaInput.value = o.deliveries_estimated_delivery_date ? o.deliveries_estimated_delivery_date.substring(0,10) : '';
                        // Leave blank if no actual date yet so submitting without change keeps it NULL
                        actualInput.value = o.deliveries_actual_delivery_date ? o.deliveries_actual_delivery_date.substring(0,10) : '';
                        sigInput.checked = !!parseInt(o.deliveries_recipient_signature||0);
                        feedback.textContent='';
                    } catch(err){
                        feedback.textContent='Error loading order: '+err.message;
                    }
                }

                document.querySelectorAll('.order-edit-btn').forEach(btn => {
                    btn.addEventListener('click', ()=>{
                        const tid = btn.getAttribute('data-id');
                        tidInput.value = tid;
                        open();
                        fetchOrder(tid);
                    });
                });

                // AJAX delete for orders
                const tbody = document.getElementById('ordersTableBody');
                tbody?.addEventListener('click', async (e)=>{
                    const delBtn = e.target.closest('form.order-delete-form button');
                    if(!delBtn) return;
                    const formEl = delBtn.closest('form.order-delete-form');
                    if(!formEl) return;
                    const tid = formEl.getAttribute('data-id');
                    if(!confirm('Delete this order? This cannot be undone.')) return;
                    try {
                        const fd = new FormData();
                        fd.append('action','delete');
                        fd.append('transactions_id', tid);
                        const res = await fetch('../../controllers/admin/ordercontroller.php', {method:'POST', body:fd, headers:{'Accept':'application/json'}});
                        const data = await res.json();
                        if(!data.success) throw new Error(data.error||'Delete failed');
                        // remove row
                        const row = formEl.closest('tr');
                        if(row){ row.remove(); }
                        showToast('Order #'+tid+' deleted');
                    } catch(err){ showToast(err.message||'Delete failed','error'); }
                });

                if(form){
                form.addEventListener('submit', async e=>{
                    e.preventDefault();
                    feedback.textContent='Saving...';
                    saveBtn.disabled=true;
                    try {
                        const fd = new FormData(form);
                        const res = await fetch('../../controllers/admin/ordercontroller.php', {method:'POST', body:fd, headers:{'Accept':'application/json'}});
                        const data = await res.json();
                        if(!data.success) throw new Error(data.error||'Update failed');
                        feedback.textContent='Saved. Refreshing row...';
                        const tid = tidInput.value;
                        const targetRow = document.querySelector(`#ordersTableBody tr[data-tid="${tid}"]`);
                        if(targetRow){
                            targetRow.setAttribute('data-status', statusSel.value);
                            const statusCell = targetRow.querySelector('td:nth-child(5)');
                            if(statusCell){
                                const st = statusSel.value;
                                const cls = `order-status order-status-${st}`;
                                statusCell.innerHTML = `<span class=\"${cls}\">${st.replace(/_/g,' ').replace(/\b\w/g,c=>c.toUpperCase())}</span>`;
                            }
                            const etaCell = targetRow.querySelector('td:nth-child(6)');
                            if(etaCell) etaCell.textContent = etaInput.value;
                            const actualCell = targetRow.querySelector('td:nth-child(7)');
                            if(actualCell) actualCell.textContent = actualInput.value;
                            const sigCell = targetRow.querySelector('td:nth-child(8)');
                            if(sigCell) sigCell.innerHTML = sigInput.checked? '<span class="text-emerald-600 font-semibold">Received</span>' : '<span class="text-gray-400">Pending</span>';
                        }
                        showToast('Order updated');
                        setTimeout(()=>{ close(); }, 600);
                    } catch(err){
                        feedback.textContent='Error: '+err.message;
                        showToast(err.message||'Update failed','error');
                    } finally {
                        saveBtn.disabled=false;
                    }
                });
                }
            }

            // Invoke orders initialization (formerly self-invoked IIFE)
            initOrders();

            // Orders Section: filtering (product names, status, payment method) + pagination (10/page)
            function initOrdersSectionFilters(){
                const tbody = document.querySelector('#ordersTableBody');
                if(!tbody) return; // safety
                const search = document.getElementById('ordersSearch'); // search over product names only
                const statusFilter = document.getElementById('ordersStatusFilter');
                const paymentFilter = document.getElementById('ordersPaymentFilter');
                const rows = Array.from(tbody.querySelectorAll('tr[data-status]'));
                const pagWrap = document.getElementById('ordersPagination');
                const prevBtn = document.getElementById('ordersPrev');
                const nextBtn = document.getElementById('ordersNext');
                const pageNums = document.getElementById('ordersPageNums');
                const pageInfo = document.getElementById('ordersPageInfo');
                const pageSize = 10;
                let page = 1;
                let filtered = rows.slice();

                function apply(){
                    const q = (search?.value || '').trim().toLowerCase();
                    const st = (statusFilter?.value || '').toLowerCase();
                    const pm = (paymentFilter?.value || '').toLowerCase();
                    filtered = rows.filter(r => {
                        const rs = (r.dataset.status||'').toLowerCase();
                        const rp = (r.dataset.payment||'').toLowerCase();
                        const items = (r.dataset.items||'').toLowerCase();
                        const buyer = (r.dataset.buyer||'').toLowerCase();
                        const addr = (r.dataset.address||'').toLowerCase();
                        if(st && rs !== st) return false;
                        if(pm && rp !== pm) return false;
                        if(q && !(items.includes(q) || buyer.includes(q) || addr.includes(q))) return false; // search over items, buyer, address
                        return true;
                    });
                    page = 1;
                    render();
                }

                function render(){
                    rows.forEach(r => r.classList.add('hidden'));
                    const total = filtered.length;
                    if(total === 0){
                        pagWrap?.classList.add('hidden');
                        if(pageInfo) pageInfo.textContent = 'No orders found';
                        if(pageNums) pageNums.innerHTML='';
                        prevBtn && (prevBtn.disabled = true);
                        nextBtn && (nextBtn.disabled = true);
                        return;
                    }
                    const totalPages = Math.max(1, Math.ceil(total / pageSize));
                    if(page > totalPages) page = totalPages;
                    const start = (page - 1) * pageSize;
                    const end = start + pageSize;
                    filtered.slice(start, end).forEach(r => r.classList.remove('hidden'));

                    pagWrap?.classList.remove('hidden');
                    if(pageInfo){
                        pageInfo.textContent = `Showing ${start+1}-${Math.min(end,total)} of ${total} orders`;
                    }

                    if(pageNums){
                        pageNums.innerHTML='';
                        const totalPagesFinal = Math.ceil(total / pageSize);
                        if(totalPagesFinal > 1){
                            const range = typeof paginationRange === 'function' ? paginationRange(page, totalPagesFinal, 5) : Array.from({length: totalPagesFinal}, (_,i)=>i+1);
                            range.forEach(n => {
                                const btn = document.createElement('button');
                                btn.className = 'px-2 py-1 text-xs rounded-md border';
                                if(n === '...'){
                                    btn.textContent = '...';
                                    btn.disabled = true;
                                    btn.classList.add('opacity-50','cursor-default');
                                } else {
                                    btn.textContent = n;
                                    if(n === page) btn.classList.add('bg-blue-600','text-white','border-blue-600');
                                    btn.addEventListener('click', ()=>{ page = n; render(); });
                                }
                                pageNums.appendChild(btn);
                            });
                        }
                    }

                    if(prevBtn){ prevBtn.disabled = page <= 1; prevBtn.classList.toggle('opacity-50', prevBtn.disabled); }
                    if(nextBtn){ nextBtn.disabled = page >= Math.ceil(total / pageSize); nextBtn.classList.toggle('opacity-50', nextBtn.disabled); }
                }

                // Event bindings
                search?.addEventListener('input', apply);
                statusFilter?.addEventListener('change', apply);
                paymentFilter?.addEventListener('change', apply);
                prevBtn?.addEventListener('click', ()=>{ if(page>1){ page--; render(); }});
                nextBtn?.addEventListener('click', ()=>{ if(page < Math.ceil(filtered.length / pageSize)){ page++; render(); }});

                apply();
            }

            // Pet Owners directory: search + pagination (10/page)
            function initOwnersDirectory(){
                const list = document.getElementById('petOwnersList');
                const search = document.getElementById('petOwnersSearch');
                const prev = document.getElementById('ownersPrev');
                const next = document.getElementById('ownersNext');
                const pageInfo = document.getElementById('ownersPageInfo');
                if (!list) return;
                const allCards = Array.from(list.querySelectorAll('.owner-card'));
                let filtered = allCards.slice();
                const pageSizeOwners = 10;
                let page = 1;

                function render(){
                    // hide all
                    allCards.forEach(c => c.style.display = 'none');
                    const total = filtered.length;
                    const totalPages = Math.max(1, Math.ceil(total / pageSizeOwners));
                    if (page > totalPages) page = totalPages;
                    const start = (page - 1) * pageSizeOwners;
                    const end = start + pageSizeOwners;
                    filtered.slice(start, end).forEach(c => c.style.display = '');
                    // controls
                    if (pageInfo) pageInfo.textContent = total ? `Showing ${start+1}-${Math.min(end,total)} of ${total}` : 'No results';
                    if (prev) prev.disabled = page <= 1;
                    if (next) next.disabled = page >= totalPages;
                    const numsWrap = document.getElementById('ownersPageNums');
                    if (numsWrap) {
                        numsWrap.innerHTML='';
                        const pages = paginationRange(page,totalPages,5);
                        pages.forEach(p=>{
                            if(p==='...') { const span=document.createElement('span'); span.textContent='...'; span.className='px-1 text-gray-500'; numsWrap.appendChild(span); }
                            else { const b=document.createElement('button'); b.textContent=p; b.className='px-2.5 py-1 border rounded text-sm '+(p===page?'bg-gray-900 text-white border-gray-900':'hover:bg-gray-100'); b.addEventListener('click',()=>{ page=p; render(); }); numsWrap.appendChild(b);} });
                    }
                }

                function applyFilter(){
                    const q = (search?.value || '').trim().toLowerCase();
                    filtered = q ? allCards.filter(c => (c.getAttribute('data-search')||'').includes(q)) : allCards.slice();
                    page = 1; render();
                }

                if (search) search.addEventListener('input', applyFilter);
                if (prev) prev.addEventListener('click', ()=>{ if (page>1){ page--; render(); }});
                if (next) next.addEventListener('click', ()=>{ page++; render(); });

                applyFilter();
            }

            // Appointments filtering and actions (single All table)
            function initAppointments(){
                const table = document.getElementById('allAppointmentsTable');
                const allRows = Array.from(table.querySelectorAll('tbody tr'));
                const tabs = Array.from(document.querySelectorAll('#apptTabs .appt-tab'));
                const search = document.getElementById('appointmentsSearch');
                const dateFrom = document.getElementById('apptDateFrom');
                const dateTo = document.getElementById('apptDateTo');
                const timeFrom = document.getElementById('apptTimeFrom');
                const timeTo = document.getElementById('apptTimeTo');
                const resetBtn = document.getElementById('resetApptFilters');
                const statusFilter = document.getElementById('apptStatusFilter');

                let currentService = 'all';

                function matchesFilters(tr){
                    // Service
                    if (currentService !== 'all' && tr.getAttribute('data-type') !== currentService) return false;
                    // Date/time
                    const iso = tr.getAttribute('data-datetime') || '';
                    if (iso) {
                        const [dPart, tPart] = iso.split('T');
                        const tVal = tPart ? tPart.substring(0,5) : '';
                        if (dateFrom && dateFrom.value && dPart < dateFrom.value) return false;
                        if (dateTo && dateTo.value && dPart > dateTo.value) return false;
                        if (timeFrom && timeFrom.value && tVal && tVal < timeFrom.value) return false;
                        if (timeTo && timeTo.value && tVal && tVal > timeTo.value) return false;
                    }
                    // Search
                    const q = (search?.value || '').trim().toLowerCase();
                    if (q) {
                        const hay = tr.getAttribute('data-search') || '';
                        if (!hay.includes(q)) return false;
                    }
                    // Status filter
                    if (statusFilter && statusFilter.value) {
                        const rowStatus = (tr.getAttribute('data-status') || '').toLowerCase();
                        if (rowStatus !== statusFilter.value.toLowerCase()) return false;
                    }
                    return true;
                }

                // Appointments filtering + pagination (10/page)
                const apptPageSize = 10;
                let apptPage = 1;

                function visibleRows(){ return allRows.filter(tr => tr.style.display !== 'none'); }

                function paginateAppointments(){
                    const rows = allRows.filter(tr => tr.__matches || false);
                    const total = rows.length;
                    const totalPages = Math.max(1, Math.ceil(total / apptPageSize));
                    if (apptPage > totalPages) apptPage = totalPages;
                    // hide all
                    allRows.forEach(r => r.style.display = 'none');
                    const start = (apptPage - 1) * apptPageSize;
                    const end = start + apptPageSize;
                    rows.slice(start, end).forEach(r => r.style.display = '');
                    // Empty state row when no visible
                    const anyVisible = rows.slice(start,end).length > 0;
                    const colSpan = table.querySelector('thead tr').children.length;
                    let emptyRow = table.querySelector('tbody tr[data-empty]');
                    if (!anyVisible) {
                        if (!emptyRow) {
                            emptyRow = document.createElement('tr');
                            emptyRow.setAttribute('data-empty','1');
                            emptyRow.innerHTML = `<td colspan="${colSpan}" class="px-6 py-6 text-center text-gray-500">No matching appointments.</td>`;
                            table.querySelector('tbody').appendChild(emptyRow);
                        }
                    } else if (emptyRow) {
                        emptyRow.remove();
                    }
                    updateApptPager(total, apptPage, totalPages, start, end);
                }

                function updateApptPager(total, page, totalPages, start, end){
                    let pager = document.getElementById('appointmentsPager');
                    if (!pager) return; // pager may be injected below
                    const info = pager.querySelector('#appointmentsPageInfo');
                    const prev = pager.querySelector('#appointmentsPrev');
                    const next = pager.querySelector('#appointmentsNext');
                    const numsWrap = pager.querySelector('#appointmentsPageNums');
                    if (info) info.textContent = total ? `Showing ${start+1}-${Math.min(end,total)} of ${total}` : 'No results';
                    if (prev) prev.disabled = page <= 1;
                    if (next) next.disabled = page >= totalPages;
                    if (numsWrap) {
                        numsWrap.innerHTML='';
                        const pages = paginationRange(page,totalPages,5);
                        pages.forEach(p=>{ if(p==='...'){ const span=document.createElement('span'); span.textContent='...'; span.className='px-1 text-gray-500'; numsWrap.appendChild(span);} else { const b=document.createElement('button'); b.textContent=p; b.className='px-2.5 py-1 border rounded text-sm '+(p===page?'bg-gray-900 text-white border-gray-900':'hover:bg-gray-100'); b.addEventListener('click',()=>{ apptPage=p; paginateAppointments(); }); numsWrap.appendChild(b);} });
                    }
                }

                function ensureApptPager(){
                    if (document.getElementById('appointmentsPager')) return;
                    const wrapper = table.parentElement?.parentElement; // card body -> table wrapper
                    const pager = document.createElement('div');
                    pager.id = 'appointmentsPager';
                    pager.className = 'flex items-center justify-between px-4 py-3 border-t';
                    pager.innerHTML = `
                        <div id=\"appointmentsPageInfo\" class=\"text-sm text-gray-600\"></div>
                        <div class=\"flex items-center gap-2\">
                            <button id=\"appointmentsPrev\" class=\"px-3 py-1.5 border rounded-md text-sm disabled:opacity-50\">Prev</button>
                            <div id=\"appointmentsPageNums\" class=\"flex items-center gap-1\"></div>
                            <button id=\"appointmentsNext\" class=\"px-3 py-1.5 border rounded-md text-sm disabled:opacity-50\">Next</button>
                        </div>`;
                    wrapper.appendChild(pager);
                    pager.querySelector('#appointmentsPrev').addEventListener('click', ()=>{ if (apptPage>1){ apptPage--; paginateAppointments(); } });
                    pager.querySelector('#appointmentsNext').addEventListener('click', ()=>{ apptPage++; paginateAppointments(); });
                }

                function applyFilters(){
                    let anyVisible = false;
                    allRows.forEach(tr => {
                        const show = matchesFilters(tr);
                        tr.__matches = !!show;
                        if (show) anyVisible = true;
                    });
                    ensureApptPager();
                    apptPage = 1;
                    paginateAppointments();
                }

                tabs.forEach(btn => btn.addEventListener('click', () => {
                    tabs.forEach(b => b.classList.remove('bg-gray-900','text-white','border-gray-900'));
                    tabs.forEach(b => b.classList.remove('ring-1','ring-gray-900'));
                    tabs.forEach(b => b.classList.add('opacity-90'));
                    btn.classList.add('bg-gray-900','text-white','border-gray-900');
                    btn.classList.remove('opacity-90');
                    currentService = btn.getAttribute('data-appt-filter');
                    applyFilters();
                }));

                [search, dateFrom, dateTo, timeFrom, timeTo].forEach(el => { if (el) el.addEventListener('input', applyFilters); });
                [dateFrom, dateTo, timeFrom, timeTo].forEach(el => { if (el) el.addEventListener('change', applyFilters); });
                if (statusFilter) statusFilter.addEventListener('change', applyFilters);
                if (resetBtn) resetBtn.addEventListener('click', () => {
                    if (dateFrom) dateFrom.value = '';
                    if (dateTo) dateTo.value = '';
                    if (timeFrom) timeFrom.value = '';
                    if (timeTo) timeTo.value = '';
                    if (search) search.value = '';
                    if (statusFilter) statusFilter.value = '';
                    currentService = 'all';
                    // activate All tab
                    const allTab = document.querySelector('#apptTabs [data-appt-filter="all"]');
                    if (allTab) allTab.click(); else applyFilters();
                });

                // Actions: edit/delete
                const editModal = document.getElementById('editAppointmentModal');
                const editClose = document.getElementById('editApptClose');
                const editCancel = document.getElementById('editApptCancel');
                const editForm = document.getElementById('editAppointmentForm');
                // Reuse toast from orders if exists; else lightweight local
                function showApptToast(msg,type='success'){
                    if(typeof showToast === 'function') return showToast(msg,type);
                    let wrap = document.getElementById('adminToastWrap');
                    if(!wrap){wrap=document.createElement('div');wrap.id='adminToastWrap';wrap.className='fixed z-[999] top-4 right-4 space-y-2 w-72';document.body.appendChild(wrap);}                    const el=document.createElement('div');
                    el.className='px-4 py-3 rounded-md shadow border text-sm flex items-start gap-2 animate-fade-in '+(type==='error'?'bg-red-50 border-red-200 text-red-700':'bg-indigo-50 border-indigo-200 text-indigo-800');
                    el.innerHTML='<span class="flex-1">'+msg+'</span><button class="text-xs text-gray-400 hover:text-gray-700">&times;</button>'; el.querySelector('button').onclick=()=>el.remove(); wrap.appendChild(el); setTimeout(()=>{ el.classList.add('opacity-0'); setTimeout(()=>el.remove(),300); },3500);
                }
                function openEdit(){ editModal.classList.remove('hidden'); editModal.classList.add('flex'); }
                function closeEdit(){ editModal.classList.add('hidden'); editModal.classList.remove('flex'); editForm.reset(); }
                if (editClose) editClose.addEventListener('click', closeEdit);
                if (editCancel) editCancel.addEventListener('click', closeEdit);
                if (editModal) editModal.addEventListener('click', (e)=>{ if(e.target===editModal) closeEdit(); });

                function fillFormFromRow(tr){
                    const id = tr.getAttribute('data-id');
                    document.getElementById('edit_appt_id').value = id;
                    document.getElementById('edit_appt_full_name').value = tr.children[0].textContent.trim();
                    const phone = tr.children[1].querySelector('div>div:first-child')?.textContent.trim() || '';
                    const email = tr.children[1].querySelector('div>div.text-xs')?.textContent.trim() || '';
                    document.getElementById('edit_appt_phone').value = phone;
                    document.getElementById('edit_appt_email').value = email;
                    document.getElementById('edit_appt_pet_name').value = tr.children[2].textContent.trim();
                    document.getElementById('edit_appt_pet_type').value = tr.getAttribute('data-search')?.split(' ')?.[0] || '';
                    document.getElementById('edit_appt_pet_breed').value = tr.children[4].textContent.trim();
                    document.getElementById('edit_appt_pet_age').value = tr.children[5].textContent.trim();
                    document.getElementById('edit_appt_type').value = tr.getAttribute('data-type') || 'pet_sitting';
                    const iso = tr.getAttribute('data-datetime') || '';
                    document.getElementById('edit_appt_datetime').value = iso;
                    const statusCell = tr.querySelector('td:nth-last-child(2)');
                    const statusText = (statusCell?.innerText || statusCell?.textContent || '').trim().toLowerCase();
                    const stSel = document.getElementById('edit_appt_status');
                    if (stSel) stSel.value = statusText || 'pending';
                    document.getElementById('edit_appt_notes').value = tr.children[8].getAttribute('title') || tr.children[8].textContent.trim();
                }

                table.addEventListener('click', async (e)=>{
                    const editBtn = e.target.closest('.btn-appt-edit');
                    const delBtn = e.target.closest('.btn-appt-delete');
                    const tr = e.target.closest('tr');
                    if (!tr) return;
                    const id = tr.getAttribute('data-id');
                    if (editBtn) {
                        try {
                            // Try to fetch latest record
                            const res = await fetch(`../../controllers/admin/appointmentcontroller.php?action=get&id=${encodeURIComponent(id)}`);
                            let ok = res.ok; let data = null;
                            try { data = await res.json(); } catch(_){}
                            if (ok && data && data.success && data.item) {
                                const a = data.item;
                                document.getElementById('edit_appt_id').value = a.id;
                                document.getElementById('edit_appt_full_name').value = a.full_name||'';
                                document.getElementById('edit_appt_email').value = a.email||'';
                                document.getElementById('edit_appt_phone').value = a.phone||'';
                                document.getElementById('edit_appt_pet_name').value = a.pet_name||'';
                                document.getElementById('edit_appt_pet_type').value = a.pet_type||'';
                                document.getElementById('edit_appt_pet_breed').value = a.pet_breed||'';
                                document.getElementById('edit_appt_pet_age').value = a.pet_age||'';
                                document.getElementById('edit_appt_type').value = a.type||'pet_sitting';
                                // Ensure format yyyy-MM-ddTHH:MM (strip seconds if present)
                                let dt = a.datetime||''; if(dt && dt.length>16) dt = dt.substring(0,16); document.getElementById('edit_appt_datetime').value = dt;
                                document.getElementById('edit_appt_status').value = a.status||'pending';
                                document.getElementById('edit_appt_notes').value = a.notes||'';
                            } else {
                                fillFormFromRow(tr);
                            }
                            openEdit();
                        } catch(err) {
                            fillFormFromRow(tr); openEdit();
                        }
                    } else if (delBtn) {
                        if (!confirm('Delete this appointment? This action cannot be undone.')) return;
                        try {
                            const fd = new FormData();
                            fd.append('action','delete');
                            fd.append('appointments_id', id);
                            const res = await fetch('../../controllers/admin/appointmentcontroller.php', { method:'POST', body: fd });
                            const data = await res.json();
                            if (!data.success) { alert(data.error||'Delete failed'); return; }
                            tr.remove();
                            allRows.splice(allRows.indexOf(tr),1);
                            applyFilters();
                            showApptToast('Appointment deleted');
                        } catch(err){ alert('Network error deleting'); }
                    }
                });

                if (editForm) editForm.addEventListener('submit', async (e)=>{
                    e.preventDefault();
                    const fd = new FormData(editForm);
                    try {
                        const res = await fetch('../../controllers/admin/appointmentcontroller.php', { method:'POST', body: fd });
                        const data = await res.json();
                        if (!data.success) { alert(data.error||'Update failed'); return; }
                        // Update row inline
                        const id = data.item.id;
                        // Polyfill CSS.escape if not defined (older browsers)
                        if(typeof CSS === 'undefined' || typeof CSS.escape !== 'function'){
                            window.CSS = window.CSS||{}; CSS.escape = function(v){ return String(v).replace(/[^a-zA-Z0-9_-]/g, '_'); };
                        }
                        const row = table.querySelector(`tbody tr[data-id="${CSS.escape(String(id))}"]`);
                        if (row) {
                            row.children[0].textContent = data.item.full_name || '';
                            row.children[1].querySelector('div>div:first-child').textContent = data.item.phone || '';
                            row.children[1].querySelector('div>div.text-xs').textContent = data.item.email || '';
                            row.children[2].textContent = data.item.pet_name || '';
                            row.children[4].textContent = data.item.pet_breed || '';
                            row.children[5].textContent = data.item.pet_age || '';
                            row.setAttribute('data-type', data.item.type || 'pet_sitting');
                            row.setAttribute('data-datetime', data.item.datetime || '');
                            // Date cell
                            row.children[7].textContent = data.item.datetime_fmt || '';
                            // Notes
                            row.children[8].textContent = data.item.notes || '';
                            row.children[8].setAttribute('title', data.item.notes || '');
                            // Status chip (second-to-last cell)
                            const statusCell2 = row.querySelector('td:nth-last-child(2)');
                            if (statusCell2) statusCell2.innerHTML = data.item.status_chip_html;
                        }
                        closeEdit();
                        applyFilters();
                        showApptToast('Appointment updated');
                        try {
                            if (data.points_awarded && parseInt(data.points_awarded) > 0) {
                                const awarded = parseInt(data.points_awarded);
                                const newBal = (data.new_points_balance !== undefined && data.new_points_balance !== null) ? parseInt(data.new_points_balance) : null;
                                // Distinct toast styling (reuse showApptToast fallback)
                                const msg = `+${awarded} PawPoints awarded to user` + (newBal!==null? ` (New Balance: ${newBal.toLocaleString()})`:'');
                                showApptToast(msg,'success');
                                // Mark row visually as awarded
                                if (row) {
                                    row.classList.add('ring-2','ring-amber-400','ring-offset-1');
                                    if(!row.querySelector('.appt-awarded-badge')){
                                        const badge = document.createElement('span');
                                        badge.className='appt-awarded-badge ml-2 inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-gradient-to-r from-orange-500 to-amber-500 text-white text-[10px] font-semibold tracking-wide shadow';
                                        badge.innerHTML='<svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>POINTS';
                                        const statusCell = row.querySelector('td:nth-last-child(2)');
                                        if(statusCell) statusCell.appendChild(badge);
                                    }
                                }
                            }
                        } catch(_){ /* ignore toast errors */ }
                    } catch(err){ alert('Network error updating'); }
                });

                // Initial apply
                applyFilters();
            }

            // --- Award Badge Polling for Appointments ---
            (function initAwardPolling(){
                const table = document.querySelector('#appointmentsTable');
                if(!table) return;
                async function syncAwarded(){
                    try {
                        const r = await fetch('../../controllers/admin/appointmentcontroller.php?action=award_status');
                        const j = await r.json();
                        if(!j.success) return;
                        const awarded = new Set(j.awarded || []);
                        table.querySelectorAll('tbody tr[data-id]').forEach(tr=>{
                            const id = parseInt(tr.getAttribute('data-id')||'0',10);
                            if(awarded.has(id)){
                                if(!tr.querySelector('.appt-awarded-badge')){
                                    const badge = document.createElement('span');
                                    badge.className='appt-awarded-badge ml-2 inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-gradient-to-r from-orange-500 to-amber-500 text-white text-[10px] font-semibold tracking-wide shadow';
                                    badge.innerHTML='<svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>POINTS';
                                    const statusCell = tr.querySelector('td:nth-last-child(2)');
                                    if(statusCell) statusCell.appendChild(badge);
                                }
                            }
                        });
                    } catch(e){}
                }
                syncAwarded();
                setInterval(syncAwarded, 15000);
            })();

            function initSitters(){
                const search = document.getElementById('sittersSearch');
                const statusFilter = document.getElementById('sittersStatusFilter');
                const specialtyFilter = document.getElementById('sittersSpecialtyFilter');
                const tbody = document.getElementById('sittersTableBody');
                const rows = Array.from(tbody ? tbody.querySelectorAll('tr[data-search]') : []);
                const pagWrap = document.getElementById('sittersPagination');
                const prevBtn = document.getElementById('sittersPrev');
                const nextBtn = document.getElementById('sittersNext');
                const pageNums = document.getElementById('sittersPageNums');
                const pageInfo = document.getElementById('sittersPageInfo');
                const pageSize = 10;
                let page = 1;
                let filtered = rows.slice();

                function apply(){
                    const q = (search?.value || '').trim().toLowerCase();
                    const status = (statusFilter?.value || '').toLowerCase();
                    const specialty = (specialtyFilter?.value || '').toLowerCase();
                    
                    filtered = rows.filter(r => {
                        const searchData = (r.dataset.search || '').toLowerCase();
                        const r_status = (r.dataset.status || '').toLowerCase();
                        const r_specialty = (r.dataset.specialty || '').toLowerCase();

                        if (q && !searchData.includes(q)) return false;
                        if (status && r_status !== status) return false;
                        if (specialty && !r_specialty.split(',').includes(specialty)) return false;
                        
                        return true;
                    });
                    page = 1;
                    render();
                }

                function render(){
                    rows.forEach(r => r.classList.add('hidden'));
                    const total = filtered.length;
                    const emptyRow = tbody.querySelector('tr[data-empty]');

                    if(total === 0){
                        pagWrap?.classList.add('hidden');
                        if(pageInfo) pageInfo.textContent = 'No sitters found';
                        if(pageNums) pageNums.innerHTML='';
                        prevBtn && (prevBtn.disabled = true);
                        nextBtn && (nextBtn.disabled = true);
                        if(emptyRow) emptyRow.classList.remove('hidden');
                        return;
                    }
                    
                    if(emptyRow) emptyRow.classList.add('hidden');

                    const totalPages = Math.max(1, Math.ceil(total / pageSize));
                    if(page > totalPages) page = totalPages;
                    const start = (page - 1) * pageSize;
                    const end = start + pageSize;
                    filtered.slice(start, end).forEach(r => r.classList.remove('hidden'));

                    pagWrap?.classList.remove('hidden');
                    if(pageInfo){
                        pageInfo.textContent = `Showing ${start+1}-${Math.min(end,total)} of ${total} sitters`;
                    }

                    if(pageNums){
                        pageNums.innerHTML='';
                        const range = paginationRange(page, totalPages, 5);
                        range.forEach(n => {
                            const btn = document.createElement('button');
                            btn.className = 'px-2 py-1 text-xs rounded-md border';
                            if(n === '...'){
                                btn.textContent = '...';
                                btn.disabled = true;
                            } else {
                                btn.textContent = n;
                                if(n === page) btn.classList.add('bg-blue-600','text-white','border-blue-600');
                                btn.addEventListener('click', ()=>{ page = n; render(); });
                            }
                            pageNums.appendChild(btn);
                        });
                    }

                    if(prevBtn){ prevBtn.disabled = page <= 1; prevBtn.classList.toggle('opacity-50', prevBtn.disabled); }
                    if(nextBtn){ nextBtn.disabled = page >= totalPages; nextBtn.classList.toggle('opacity-50', nextBtn.disabled); }
                }

                search?.addEventListener('input', apply);
                statusFilter?.addEventListener('change', apply);
                specialtyFilter?.addEventListener('change', apply);
                prevBtn?.addEventListener('click', ()=>{ if(page>1){ page--; render(); }});
                nextBtn?.addEventListener('click', ()=>{ if(page < Math.ceil(filtered.length / pageSize)){ page++; render(); }});

                apply();
            }

            // ===== Orders (Delivery Only) Filtering / Pagination / Modal ====
            
        });

        // Sidebar functions
        function expandSidebar() {
            if (!sidebarLocked) {
                sidebarExpanded = true;
                updateSidebarState();
            }
        }

        function collapseSidebar() {
            if (!sidebarLocked) {
                sidebarExpanded = false;
                updateSidebarState();
            }
        }

        function toggleSidebarLock() {
            sidebarLocked = !sidebarLocked;
            document.getElementById('sidebarLock').checked = sidebarLocked;
            updateSidebarState();
        }

        function updateSidebarState() {
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.getElementById('mainContent');
            const logoExpanded = document.getElementById('sidebarLogoExpanded');
            const logoCollapsed = document.getElementById('sidebarLogoCollapsed');
            const sidebarToggle = document.getElementById('sidebarToggle');
            const adminInfo = document.getElementById('adminInfo');
            const sidebarLabels = document.querySelectorAll('.sidebar-label');

            if (sidebarExpanded || sidebarLocked) {
                sidebar.classList.remove('w-16');
                sidebar.classList.add('w-64');
                mainContent.classList.remove('ml-16');
                mainContent.classList.add('ml-64');
                logoExpanded.classList.remove('hidden');
                logoExpanded.classList.add('flex');
                logoCollapsed.classList.add('hidden');
                sidebarToggle.classList.remove('hidden');
                adminInfo.classList.remove('hidden');
                sidebarLabels.forEach(label => label.classList.remove('hidden'));
            } else {
                sidebar.classList.add('w-16');
                sidebar.classList.remove('w-64');
                mainContent.classList.add('ml-16');
                mainContent.classList.remove('ml-64');
                logoExpanded.classList.add('hidden');
                logoExpanded.classList.remove('flex');
                logoCollapsed.classList.remove('hidden');
                sidebarToggle.classList.add('hidden');
                adminInfo.classList.add('hidden');
                sidebarLabels.forEach(label => label.classList.add('hidden'));
            }
        }

        // Section navigation
        function setActiveSection(section) {
            // Hide all sections dynamically (any element with id ending in -section)
            document.querySelectorAll('[id$="-section"]').forEach(el => el.classList.add('hidden'));

            // Show active section
            const target = document.getElementById(`${section}-section`);
            if (target) target.classList.remove('hidden');

            // Update sidebar active state
            document.querySelectorAll('.sidebar-item').forEach(item => {
                item.classList.remove('bg-gradient-to-r', 'from-orange-500', 'to-amber-600', 'text-white', 'shadow-md');
                item.classList.add('text-gray-700', 'hover:bg-gray-100');
            });

            const activeItem = document.querySelector(`[data-section="${section}"]`);
            if (activeItem) {
                activeItem.classList.add('bg-gradient-to-r', 'from-orange-500', 'to-amber-600', 'text-white', 'shadow-md');
                activeItem.classList.remove('text-gray-700', 'hover:bg-gray-100');
            }

            currentActiveSection = section;

            if (section === 'audit') {
                initAuditSectionOnce();
                refreshAudit();
            }
        }

        // Time filter functions
        function updateTimeFilter() {
            const select = document.getElementById('timeFilter');
            currentTimeFilter = select.value;
            updateChart();
            updateStats();
        }

        function updateStats() {
            const data = transactionData[currentTimeFilter];
            document.getElementById('totalRevenue').textContent = `₱${data.revenue.toLocaleString()}`;
            document.getElementById('totalTransactions').textContent = data.transactions.toLocaleString();
        }

        function updateChart() {
            const data = transactionData[currentTimeFilter];
            const chartContainer = document.getElementById('revenueChart');
            const maxValue = Math.max(...data.chartData.map(d => d.value));

            chartContainer.innerHTML = data.chartData.map((item, index) => {
                const height = (item.value / maxValue) * 200;
                const minHeight = 4;
                const actualHeight = Math.max(height, minHeight);

                return `
                    <div class="flex flex-col items-center space-y-2 flex-1">
                        <div class="w-full bg-gradient-to-t from-orange-500 to-amber-400 rounded-t chart-bar" 
                             style="height: ${actualHeight}px; min-height: 4px;"></div>
                        <span class="text-xs text-gray-600">${item.name}</span>
                    </div>
                `;
            }).join('');
        }

        // Modal functions
        function openAddProductModal() {
            document.getElementById('addProductModal').classList.remove('hidden');
            document.getElementById('addProductModal').classList.add('flex');
        }

        function closeAddProductModal() {
            document.getElementById('addProductModal').classList.add('hidden');
            document.getElementById('addProductModal').classList.remove('flex');
            document.getElementById('addProductForm').reset();
        }

        

        function populateAppointments() {
            const tbody = document.getElementById('appointmentsTableBody');
            tbody.innerHTML = mockAppointments.map(appointment => {
                const serviceIcon = appointment.service === 'pet-sitting' ? 'paw-print' : 
                                   appointment.service === 'grooming' ? 'heart' : 'activity';
                const serviceColor = appointment.service === 'pet-sitting' ? 'text-orange-500' : 
                                    appointment.service === 'grooming' ? 'text-blue-500' : 'text-green-500';
                
                return `
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <p class="font-medium">${appointment.petOwner}</p>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${appointment.petName}</td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex items-center gap-2">
                            <i data-lucide="${serviceIcon}" class="w-4 h-4 ${serviceColor}"></i>
                            <span class="capitalize">${appointment.service.replace('-', ' ')}</span>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${appointment.sitter}</td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div>
                            <p class="text-sm">${appointment.date}</p>
                            <p class="text-xs text-gray-600">${appointment.time}</p>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">₱${appointment.amount}</td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-2 py-1 text-xs rounded-full ${
                            appointment.status === 'confirmed' ? 'bg-blue-100 text-blue-800' :
                            appointment.status === 'pending' ? 'bg-yellow-100 text-yellow-800' :
                            appointment.status === 'completed' ? 'bg-gray-100 text-gray-800' :
                            'bg-red-100 text-red-800'
                        }">
                            ${appointment.status.charAt(0).toUpperCase() + appointment.status.slice(1)}
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        <div class="flex items-center gap-2">
                            <button class="p-1 text-gray-400 hover:text-gray-600">
                                <i data-lucide="eye" class="w-4 h-4"></i>
                            </button>
                            <button class="p-1 text-gray-400 hover:text-gray-600">
                                <i data-lucide="edit" class="w-4 h-4"></i>
                            </button>
                            <button class="p-1 text-gray-400 hover:text-gray-600">
                                <i data-lucide="more-vertical" class="w-4 h-4"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                `;
            }).join('');
            lucide.createIcons();
        }

        // removed legacy populatePetOwners(); owners are server-rendered and paginated

        function populateSubscribers() {
            const tbody = document.getElementById('subscribersTableBody');
            tbody.innerHTML = mockSubscribers.map(subscriber => `
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${subscriber.email}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${new Date(subscriber.subscribeDate).toLocaleDateString()}</td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-2 py-1 bg-gray-100 text-gray-800 text-xs rounded-full">
                            ${subscriber.source.replace('_', ' ')}
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-2 py-1 bg-green-100 text-green-800 text-xs rounded-full">Active</span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        <div class="flex items-center gap-2">
                            <button class="p-1 text-gray-400 hover:text-gray-600">
                                <i data-lucide="mail" class="w-4 h-4"></i>
                            </button>
                            <button class="p-1 text-red-400 hover:text-red-600">
                                <i data-lucide="trash-2" class="w-4 h-4"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            `).join('');
            lucide.createIcons();
        }

        // Form handlers
        // Add product form handler (AJAX)
        (function(){
            const form = document.getElementById('addProductForm');
            const imgInput = document.getElementById('products_image');
            const preview = document.getElementById('imagePreview');
            const placeholder = document.getElementById('imagePlaceholder');
            const clearBtn = document.getElementById('clearImageBtn');

            function resetPreview(){
                preview.src = '';
                preview.classList.add('hidden');
                placeholder.classList.remove('hidden');
                clearBtn.classList.add('hidden');
            }

            imgInput.addEventListener('change', function(){
                const file = this.files && this.files[0];
                if (file) {
                    const url = URL.createObjectURL(file);
                    preview.src = url;
                    preview.classList.remove('hidden');
                    placeholder.classList.add('hidden');
                    clearBtn.classList.remove('hidden');
                } else {
                    resetPreview();
                }
            });

            clearBtn.addEventListener('click', function(){
                imgInput.value = '';
                resetPreview();
            });

            form.addEventListener('submit', async function(e){
                e.preventDefault();
                const fd = new FormData(form);
                try {
                    const res = await fetch('../../controllers/admin/productcontroller.php?action=add', {
                        method: 'POST',
                        body: fd
                    });
                    const data = await res.json();
                    if (!data.success) throw new Error(data.error || 'Failed to add product');

                    // Update table without full reload
                    const tbody = document.getElementById('productsTableBody');
                    const p = data.item;
                    const row = document.createElement('tr');
                    row.setAttribute('data-id', String(p.id));
                    row.setAttribute('data-name', (p.name || '').toLowerCase());
                    row.setAttribute('data-pet-type', p.pet_type || '');
                    row.setAttribute('data-category', p.category_value || '');
                    row.setAttribute('data-active', p.active ? '1' : '0');
                    row.setAttribute('data-stock', String(p.stock_int ?? 0));
                    row.innerHTML = `
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-lg overflow-hidden">
                                    ${p.image ? `<img src="${p.image}" alt="${p.name}" class="w-full h-full object-cover">` : ''}
                                </div>
                                <div><p class="font-medium">${p.name}</p></div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${p.category}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">₱${Number(p.price).toLocaleString()}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${p.stock}</td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 py-1 text-xs rounded-full ${p.status === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'}">
                                ${p.status === 'active' ? 'Active' : 'Inactive'}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <div class="flex items-center gap-2">
                                <button class="p-1 text-gray-400 hover:text-gray-600 btn-edit" data-action="edit" title="Edit">
                                    <i data-lucide="edit" class="w-4 h-4"></i>
                                </button>
                                <button class="p-1 text-red-400 hover:text-red-600 btn-delete" data-action="delete" title="Delete">
                                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                                </button>
                            </div>
                        </td>`;
                    tbody.prepend(row);
                    lucide.createIcons();

                    // Re-apply filters after adding
                    applyProductFilters();

                    alert('Product added successfully!');
                    closeAddProductModal();
                    form.reset();
                    resetPreview();
                } catch (err) {
                    alert(err.message);
                }
            });
        })();

        

        // Close modals when clicking outside
        document.addEventListener('click', function(e) {
            const productModal = document.getElementById('addProductModal');
            const sitterModal = document.getElementById('addSitterModal');
            const editModal = document.getElementById('editProductModal');
            
            if (e.target === productModal) {
                closeAddProductModal();
            }
            if (e.target === sitterModal) {
                closeAddSitterModal();
            }
            if (e.target === editModal) {
                closeEditProductModal();
            }
        });

        // Profile menu interactions
        (function(){
            const btn = document.getElementById('profileButton');
            const menu = document.getElementById('profileMenu');
            const wrapper = document.getElementById('profileMenuWrapper');
            if (!btn || !menu || !wrapper) return;
            function toggleMenu(show){
                const shouldShow = typeof show === 'boolean' ? show : menu.classList.contains('hidden');
                if (shouldShow){
                    menu.classList.remove('hidden');
                } else {
                    menu.classList.add('hidden');
                }
            }
            btn.addEventListener('click', (e)=>{
                e.stopPropagation();
                toggleMenu();
            });
            document.addEventListener('click', (e)=>{
                if (!wrapper.contains(e.target)) toggleMenu(false);
            });
            document.addEventListener('keydown', (e)=>{
                if (e.key === 'Escape') toggleMenu(false);
            });
        })();

        // Edit profile modal removed; no helper functions needed

        // Edit Product modal helpers
        function openEditProductModal() {
            const modal = document.getElementById('editProductModal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }
        function closeEditProductModal() {
            const modal = document.getElementById('editProductModal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            document.getElementById('editProductForm').reset();
            resetEditPreview();
        }
        function resetEditPreview(){
            const preview = document.getElementById('edit_imagePreview');
            const placeholder = document.getElementById('edit_imagePlaceholder');
            const clearBtn = document.getElementById('edit_clearImageBtn');
            preview.src='';
            preview.classList.add('hidden');
            placeholder.classList.remove('hidden');
            clearBtn.classList.add('hidden');
        }

        (function initEditModal(){
            const imgInput = document.getElementById('edit_products_image');
            const preview = document.getElementById('edit_imagePreview');
            const placeholder = document.getElementById('edit_imagePlaceholder');
            const clearBtn = document.getElementById('edit_clearImageBtn');
            imgInput.addEventListener('change', function(){
                const file = this.files && this.files[0];
                if (file){
                    const url = URL.createObjectURL(file);
                    preview.src = url;
                    preview.classList.remove('hidden');
                    placeholder.classList.add('hidden');
                    clearBtn.classList.remove('hidden');
                } else {
                    resetEditPreview();
                }
            });
            clearBtn.addEventListener('click', function(){
                imgInput.value = '';
                resetEditPreview();
            });
        })();

        // Delegate edit/delete buttons
        document.addEventListener('click', async function(e){
            const editBtn = e.target.closest('.btn-edit');
            const delBtn = e.target.closest('.btn-delete');
            if (editBtn) {
                const row = editBtn.closest('tr');
                const id = row?.getAttribute('data-id');
                if (!id) return;
                try {
                    const res = await fetch(`../../controllers/admin/productcontroller.php?action=get&id=${encodeURIComponent(id)}`);
                    const data = await res.json();
                    if (!data.success) throw new Error(data.error || 'Failed to fetch product');
                    const p = data.item;
                    // Fill form
                    document.getElementById('edit_products_id').value = p.id;
                    document.getElementById('edit_products_name').value = p.name || '';
                    document.getElementById('edit_products_pet_type').value = p.pet_type || '';
                    // Map enum to input choices for category
                    const inputCatMap = { accessory: 'accessories', necessity: 'grooming', toy: 'treats', food: 'food' };
                    document.getElementById('edit_products_category').value = inputCatMap[p.category_value] || p.category_value || '';
                    document.getElementById('edit_products_price').value = p.price ?? '';
                    document.getElementById('edit_products_stock').value = p.stock ?? '';
                    document.getElementById('edit_products_description').value = p.description || '';
                    document.getElementById('edit_products_active').checked = !!p.active;
                    document.getElementById('edit_current_image_url').value = p.db_image_url || '';
                    const preview = document.getElementById('edit_imagePreview');
                    const placeholder = document.getElementById('edit_imagePlaceholder');
                    const clearBtn = document.getElementById('edit_clearImageBtn');
                    if (p.image) {
                        preview.src = p.image;
                        preview.classList.remove('hidden');
                        placeholder.classList.add('hidden');
                        clearBtn.classList.remove('hidden');
                    } else {
                        resetEditPreview();
                    }
                    openEditProductModal();
                } catch(err) {
                    alert(err.message);
                }
            } else if (delBtn) {
                const row = delBtn.closest('tr');
                const id = row?.getAttribute('data-id');
                if (!id) return;
                if (!confirm('Delete this product? This action cannot be undone.')) return;
                try {
                    const fd = new FormData();
                    fd.append('action', 'delete');
                    fd.append('products_id', id);
                    const res = await fetch('../../controllers/admin/productcontroller.php?action=delete', { method: 'POST', body: fd });
                    const data = await res.json();
                    if (!data.success) throw new Error(data.error || 'Delete failed');
                    row.remove();
                    applyProductFilters();
                } catch(err) {
                    alert(err.message);
                }
            }
        });

        // Submit edit form
        (function(){
            const form = document.getElementById('editProductForm');
            form.addEventListener('submit', async function(e){
                e.preventDefault();
                const fd = new FormData(form);
                try {
                    const res = await fetch('../../controllers/admin/productcontroller.php?action=update', { method: 'POST', body: fd });
                    const data = await res.json();
                    if (!data.success) throw new Error(data.error || 'Update failed');
                    const p = data.item;
                    const row = document.querySelector(`#productsTableBody tr[data-id="${p.id}"]`);
                    if (row) {
                        row.setAttribute('data-name', (p.name || '').toLowerCase());
                        row.setAttribute('data-pet-type', p.pet_type || '');
                        row.setAttribute('data-category', p.category_value || '');
                        row.setAttribute('data-active', p.active ? '1' : '0');
                        row.setAttribute('data-stock', String(p.stock_int ?? 0));
                        // Update visible cells
                        const nameEl = row.querySelector('td:nth-child(1) .font-medium');
                        if (nameEl) nameEl.textContent = p.name;
                        const imgEl = row.querySelector('td:nth-child(1) img');
                        if (imgEl && p.image) imgEl.src = p.image;
                        const catEl = row.querySelector('td:nth-child(2)');
                        if (catEl) catEl.textContent = p.category;
                        const priceEl = row.querySelector('td:nth-child(3)');
                        if (priceEl) priceEl.textContent = `₱${Number(p.price).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
                        const stockEl = row.querySelector('td:nth-child(4)');
                        if (stockEl) stockEl.textContent = p.stock;
                        const statusChip = row.querySelector('td:nth-child(5) span');
                        if (statusChip) {
                            statusChip.textContent = p.active ? 'Active' : 'Inactive';
                            statusChip.className = `px-2 py-1 text-xs rounded-full ${p.active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'}`;
                        }
                    }
                    applyProductFilters();
                    alert('Product updated successfully!');
                    closeEditProductModal();
                } catch(err) {
                    alert(err.message);
                }
            });
        })();

        // Products filtering and search
        function initProductFilters() {
            const search = document.getElementById('productsSearch');
            const filterInputs = Array.from(document.querySelectorAll('input[name="petType"], input[name="category"], input[name="active"], input[name="stock"]'));

            let debounceTimer;
            function onSearchInput() {
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(() => { currentProductsPage = 1; applyProductFilters(); }, 120);
            }

            if (search) search.addEventListener('input', onSearchInput);
            filterInputs.forEach(el => el.addEventListener('change', () => { currentProductsPage = 1; applyProductFilters(); }));

            applyProductFilters();
        }

        function getCheckedValues(name) {
            return Array.from(document.querySelectorAll(`input[name="${name}"]:checked`)).map(el => el.value);
        }

        // Pagination state
        let currentProductsPage = 1;
        const pageSize = 10;

        function applyProductFilters() {
            const searchVal = (document.getElementById('productsSearch')?.value || '').trim().toLowerCase();
            const petTypes = getCheckedValues('petType');
            const categories = getCheckedValues('category');
            const actives = getCheckedValues('active');
            const stockFilters = getCheckedValues('stock');

            const rows = Array.from(document.querySelectorAll('#productsTableBody tr'));
            // Compute which rows match
            const matches = [];
            rows.forEach(row => {
                if (row.querySelector('td')?.getAttribute('colspan') === '6') {
                    // Skip the "No products" row
                    return;
                }

                const name = row.getAttribute('data-name') || '';
                const pet = row.getAttribute('data-pet-type') || '';
                const cat = row.getAttribute('data-category') || '';
                const active = row.getAttribute('data-active') || '';
                const stockVal = parseInt(row.getAttribute('data-stock') || '0', 10);

                let visible = true;

                // Search: show if name includes any letter typed (even single letter)
                if (searchVal && !name.includes(searchVal)) visible = false;

                // Pet type filter
                if (visible && petTypes.length > 0 && !petTypes.includes(pet)) visible = false;

                // Category filter (values match enum values in DB)
                if (visible && categories.length > 0 && !categories.includes(cat)) visible = false;

                // Active filter (row has '1' or '0')
                if (visible && actives.length > 0 && !actives.includes(active)) visible = false;

                // Stock filter
                if (visible && stockFilters.length > 0) {
                    const inSelected = stockFilters.includes('in');
                    const outSelected = stockFilters.includes('out');
                    const isIn = stockVal >= 1;
                    const isOut = stockVal === 0;
                    if (!( (inSelected && isIn) || (outSelected && isOut) )) visible = false;
                }

                if (visible) matches.push(row);
            });

            // Toggle empty state row
            const tbody = document.getElementById('productsTableBody');
            const dataRows = rows.filter(r => !(r.querySelector('td')?.getAttribute('colspan') === '6'));
            const anyVisible = matches.length > 0;
            let emptyRow = tbody.querySelector('tr[data-empty]');
            if (!anyVisible) {
                if (!emptyRow) {
                    emptyRow = document.createElement('tr');
                    emptyRow.setAttribute('data-empty', '1');
                    emptyRow.innerHTML = '<td colspan="6" class="px-6 py-6 text-center text-gray-500">No products match your filters.</td>';
                    tbody.appendChild(emptyRow);
                }
            } else if (emptyRow) {
                emptyRow.remove();
            }

            // Apply pagination to matches
            const pagination = document.getElementById('productsPagination');
            const pageInfo = document.getElementById('productsPageInfo');
            const prevBtn = document.getElementById('productsPrev');
            const nextBtn = document.getElementById('productsNext');

            // Hide all rows first
            dataRows.forEach(r => { r.style.display = 'none'; });

            const total = matches.length;
            const totalPages = Math.max(1, Math.ceil(total / pageSize));
            if (currentProductsPage > totalPages) currentProductsPage = totalPages;
            const start = (currentProductsPage - 1) * pageSize;
            const end = start + pageSize;
            const pageRows = matches.slice(start, end);
            pageRows.forEach(r => { r.style.display = ''; });

            const numsWrap = document.getElementById('productsPageNums');
            if (numsWrap) numsWrap.innerHTML = '';
            if (total > pageSize) {
                pagination.classList.remove('hidden');
                pageInfo.textContent = `Page ${currentProductsPage} of ${totalPages} • ${total} item${total === 1 ? '' : 's'}`;
                // numeric buttons range
                const pages = paginationRange(currentProductsPage, totalPages, 5);
                pages.forEach(p => {
                    if (p === '...') {
                        const span = document.createElement('span');
                        span.textContent = '...';
                        span.className = 'px-1 text-gray-500';
                        numsWrap.appendChild(span);
                    } else {
                        const btn = document.createElement('button');
                        btn.textContent = p;
                        btn.className = 'px-2.5 py-1 border rounded text-sm ' + (p === currentProductsPage ? 'bg-gray-900 text-white border-gray-900' : 'hover:bg-gray-100');
                        btn.addEventListener('click', () => { currentProductsPage = p; applyProductFilters(); });
                        numsWrap.appendChild(btn);
                    }
                });
                prevBtn.disabled = currentProductsPage === 1;
                nextBtn.disabled = currentProductsPage === totalPages;
                prevBtn.classList.toggle('opacity-50', prevBtn.disabled);
                nextBtn.classList.toggle('opacity-50', nextBtn.disabled);
            } else { pagination.classList.add('hidden'); }
        }

        // Pagination controls
        document.addEventListener('click', function(e){
            const prev = e.target.closest('#productsPrev');
            const next = e.target.closest('#productsNext');
            if (prev) { if (currentProductsPage > 1) { currentProductsPage--; applyProductFilters(); } }
            if (next) { currentProductsPage++; applyProductFilters(); }
        });
        function paginationRange(current,total,maxButtons){
            const range=[]; if(total<=maxButtons){ for(let i=1;i<=total;i++) range.push(i); return range; }
            const half=Math.floor(maxButtons/2); let start=Math.max(1,current-half); let end=start+maxButtons-1; if(end>total){end=total; start=end-maxButtons+1;} if(start>1){ range.push(1); if(start>2) range.push('...'); } for(let i=start;i<=end;i++) range.push(i); if(end<total){ if(end<total-1) range.push('...'); range.push(total);} return range;
        }

    // ================= Subscribers Filtering & Search (Isolated) =================
    function initSubscribersFilters(){
            const tbody = document.getElementById('subsTableBody');
            if(!tbody) return; // safety if section removed
            const searchInput = document.getElementById('subsSearch');
            const statusFilter = document.getElementById('subsStatusFilter');
            const shownEl = document.getElementById('subsShownCount');
            const totalEl = document.getElementById('subsTotalCount');
            const pagerWrap = document.getElementById('subsPager');
            const prevBtn = document.getElementById('subsPrev');
            const nextBtn = document.getElementById('subsNext');
            const pageNumsWrap = document.getElementById('subsPageNums');
            const pageSize = 15;
            let page = 1;
            const allRows = Array.from(tbody.querySelectorAll('tr[data-status]'));
            if(totalEl) totalEl.textContent = allRows.length.toString();

            function ensureEmptyRow(){
                let emptyRow = tbody.querySelector('tr[data-empty]');
                if(!emptyRow){
                    emptyRow = document.createElement('tr');
                    emptyRow.setAttribute('data-empty','1');
                    emptyRow.innerHTML = '<td colspan="5" class="py-6 px-3 text-center text-gray-500 text-sm">No subscribers match.</td>';
                }
                return emptyRow;
            }

            function apply(){
                const q = (searchInput?.value || '').trim().toLowerCase();
                const statusVal = statusFilter?.value || '';
                const filtered = allRows.filter(r => {
                    const name = (r.getAttribute('data-name')||'').toLowerCase();
                    const email = (r.getAttribute('data-email')||'').toLowerCase();
                    const st = (r.getAttribute('data-status')||'').toLowerCase();
                    if(statusVal && st !== statusVal) return false;
                    if(q && !(name.includes(q) || email.includes(q))) return false;
                    return true;
                });
                // Hide all first
                allRows.forEach(r => r.style.display='none');
                // Remove existing empty placeholder(s)
                tbody.querySelectorAll('tr[data-empty]')?.forEach(er => er.remove());

                const total = filtered.length;
                const totalPages = Math.max(1, Math.ceil(total / pageSize));
                if(page > totalPages) page = totalPages; // clamp
                const start = (page-1)*pageSize;
                const end = start + pageSize;
                const pageRows = filtered.slice(start,end);
                pageRows.forEach(r => r.style.display='');

                if(shownEl) shownEl.textContent = total.toString() === '0' ? '0' : `${start+1}-${start+pageRows.length}`;
                if(totalEl && !totalEl.textContent) totalEl.textContent = allRows.length.toString();
                // If no matches show empty row
                if(total === 0){ tbody.appendChild(ensureEmptyRow()); }

                // Pager visibility
                if(total > pageSize){
                    pagerWrap && (pagerWrap.style.display='flex');
                    const totalPagesReal = Math.ceil(total / pageSize);
                    prevBtn.disabled = page <= 1;
                    nextBtn.disabled = page >= totalPagesReal;
                    prevBtn.classList.toggle('opacity-40', prevBtn.disabled);
                    nextBtn.classList.toggle('opacity-40', nextBtn.disabled);
                    if(pageNumsWrap){
                        pageNumsWrap.innerHTML = '';
                        const nums = paginationRange(page, totalPagesReal, 5);
                        nums.forEach(n => {
                            const btn = document.createElement('button');
                            btn.className = 'px-2 py-1 rounded text-xs border';
                            if(n === '...'){
                                btn.textContent = '...';
                                btn.disabled = true;
                                btn.classList.add('opacity-50');
                            } else {
                                btn.textContent = n;
                                if(n === page){
                                    btn.classList.add('bg-orange-500','text-white','border-orange-500');
                                } else {
                                    btn.classList.add('bg-white','hover:bg-orange-50');
                                    btn.addEventListener('click', ()=>{ page = n; apply(); });
                                }
                            }
                            pageNumsWrap.appendChild(btn);
                        });
                    }
                } else {
                    pagerWrap && (pagerWrap.style.display='none');
                }
            }

            searchInput?.addEventListener('input', () => { page = 1; apply(); });
            statusFilter?.addEventListener('change', () => { page = 1; apply(); });
            prevBtn?.addEventListener('click', () => { if(page>1){ page--; apply(); }});
            nextBtn?.addEventListener('click', () => { page++; apply(); });

            apply();
        }

        // ===== Top Selling Modal (Lightweight, no conflicts) =====
        function initTopSellingModal(){
            const card = document.getElementById('totalSalesCard');
            const modal = document.getElementById('topSellingModal');
            const closeBtns = [document.getElementById('topSellingClose'), document.getElementById('topSellingClose2')];
            if(!card || !modal) return;
            function open(){ modal.classList.remove('hidden'); modal.classList.add('flex'); }
            function close(){ modal.classList.add('hidden'); modal.classList.remove('flex'); }
            card.addEventListener('click', open);
            closeBtns.forEach(btn=> btn && btn.addEventListener('click', close));
            modal.addEventListener('click', (e)=>{ if(e.target===modal) close(); });
        }

        // ========== Settings Section (Dark Mode + Block Appointments) ==========
        function initSettingsSection(){
            // Dark mode is now managed by unified IIFE; only handle appointment blocking UI here.
            const blockDateInput = document.getElementById('blockDate'); // legacy single
            const blockDateStart = document.getElementById('blockDateStart');
            const blockDateEnd = document.getElementById('blockDateEnd');
            const blockTodayBtn = document.getElementById('blockTodayBtn');
            const blockDateBtn = document.getElementById('blockDateBtn');
            const blockRangeBtn = document.getElementById('blockRangeBtn');
            const clearBlockBtn = document.getElementById('clearBlockBtn');
            const blockStatus = document.getElementById('blockStatus');
            // Persistence keys
            const BLOCK_KEY = 'appointments_block_date';
            const BLOCK_RANGE_KEY = 'appointments_block_range';

            // Basic dark mode CSS injection if not already present (legacy support)
            if(!document.getElementById('adminDarkModeStyles')){
                const style = document.createElement('style');
                style.id='adminDarkModeStyles';
                style.textContent = `
                /* Base surfaces & typography */
                .dark body { background-color:#0f172a; color:#e2e8f0; }
                .dark h1,.dark h2,.dark h3,.dark h4,.dark h5,.dark h6 { color:#f8fafc; }
                .dark .bg-white { background-color:#1f2937 !important; }
                .dark .bg-gray-50 { background-color:#1e293b !important; }
                .dark .bg-gray-100 { background-color:#243044 !important; }
                .dark .bg-gray-200 { background-color:#2f3b52 !important; }
                .dark .bg-gray-300 { background-color:#3a475e !important; }
                .dark .text-black,
                .dark .text-gray-900 { color:#f1f5f9 !important; }
                .dark .text-gray-800 { color:#f1f5f9 !important; }
                .dark .text-gray-700 { color:#e2e8f0 !important; }
                .dark .text-gray-600 { color:#cbd5e1 !important; }
                .dark .text-gray-500 { color:#94a3b8 !important; }
                .dark .border-gray-200 { border-color:#334155 !important; }
                .dark .border-gray-300 { border-color:#475569 !important; }
                .dark .divide-gray-200 > :not([hidden]) ~ :not([hidden]) { border-color:#334155 !important; }
                .dark .shadow-sm { box-shadow:0 1px 2px 0 rgba(0,0,0,0.7),0 2px 4px -1px rgba(0,0,0,0.6); }

                /* Sidebar & navigation */
                .dark #sidebar { background:#1e293b; border-color:#334155; }
                .dark .sidebar-item { color:#cbd5e1; }
                .dark .sidebar-item:hover { background:#334155; color:#fff8eb; }
                .dark .sidebar-item:hover i { color:#fbbf24; }
                .dark .sidebar-item.bg-gradient-to-r { background:linear-gradient(to right,#f97316,#ea580c); color:#fff !important; }
                .dark .sidebar-item.bg-gradient-to-r i { color:#fff8eb !important; }

                /* Cards & panels */
                .dark .stats-card,
                .dark .rounded-lg.bg-white,
                .dark .border.border-gray-200 { background:#1f2937 !important; border-color:#334155 !important; }
                .dark .hover\:bg-gray-100:hover { background-color:#334155 !important; }
                .dark .hover\:bg-white:hover { background-color:#334155 !important; }

                /* Tables */
                .dark table thead th { background:#1e293b !important; color:#f1f5f9 !important; border-color:#334155 !important; }
                .dark table tbody tr { background:#1f2937; border-color:#334155; }
                .dark table tbody tr:nth-child(even) { background:#233146; }
                .dark table tbody tr:hover { background:#334155; }
                .dark table td { color:#e2e8f0; }

                /* Forms */
                .dark input[type=text],
                .dark input[type=number],
                .dark input[type=email],
                .dark input[type=date],
                .dark input[type=time],
                .dark input[type=password],
                .dark select,
                .dark textarea { background:#0f172a; border:1px solid #334155; color:#f1f5f9; }
                .dark input::placeholder,
                .dark textarea::placeholder { color:#64748b; }
                .dark input:focus,
                .dark select:focus,
                .dark textarea:focus { outline:none; border-color:#f59e0b; box-shadow:0 0 0 1px #f59e0b; }

                /* Buttons */
                .dark button,
                .dark .btn { color:#f1f5f9; }
                .dark .bg-gray-100 { background:#243044 !important; }
                .dark .bg-gray-200 { background:#2f3b52 !important; }
                .dark .bg-gray-100:hover,
                .dark .bg-gray-200:hover { background:#334155 !important; }
                .dark .bg-orange-50 { background:rgba(251,146,60,0.15) !important; color:#fdba74 !important; }
                .dark .hover\:bg-orange-50:hover { background:rgba(251,146,60,0.25) !important; }

                /* Badges & status chips */
                .dark .bg-green-50 { background:rgba(34,197,94,0.15) !important; color:#4ade80 !important; }
                .dark .bg-red-50 { background:rgba(248,113,113,0.15) !important; color:#f87171 !important; }
                .dark .bg-yellow-50 { background:rgba(234,179,8,0.15) !important; color:#facc15 !important; }
                .dark .bg-blue-50 { background:rgba(59,130,246,0.15) !important; color:#60a5fa !important; }

                /* Modals */
                .dark .fixed.inset-0 .bg-white { background:#1f2937 !important; }
                .dark .fixed.inset-0 .border-gray-200 { border-color:#334155 !important; }

                /* Scrollbars (Webkit) */
                .dark ::-webkit-scrollbar-track { background:#1e293b; }
                .dark ::-webkit-scrollbar-thumb { background:#334155; }
                .dark ::-webkit-scrollbar-thumb:hover { background:#475569; }

                /* Links */
                .dark a { color:#93c5fd; }
                .dark a:hover { color:#bfdbfe; }

                /* Accent highlights */
                .dark .ring-1 { --tw-ring-color:#334155; }
                .dark .focus\:ring-orange-500:focus { box-shadow:0 0 0 2px rgba(249,115,22,0.6); }
                .dark .focus\:ring:focus { box-shadow:0 0 0 2px #f59e0b66; }

                /* Tables empty state / subtle */
                .dark tr[data-empty] td { color:#94a3b8 !important; }

                /* Pagination */
                .dark .pagination button { background:#243044; border-color:#334155; color:#e2e8f0; }
                .dark .pagination button:hover { background:#334155; }
                .dark .pagination button[disabled] { opacity:.4; }

                /* Search inputs inside light cards */
                .dark .relative.w-80 input[type=text] { background:#0f172a; border-color:#334155; color:#f1f5f9; }

                /* Toast override for dark mode */
                .dark #adminToastWrap .bg-gray-800 { background:#1e293b !important; border-color:#334155 !important; }

                /* Promo Filters Bar */
                .dark #promoFiltersBar { background:linear-gradient(135deg,#1e293b,#0f172a) !important; border-color:#334155 !important; }
                .dark #promoFiltersBar label { color:#cbd5e1 !important; }
                .dark #promoFiltersBar select { background:#0f172a !important; border-color:#334155 !important; color:#f1f5f9 !important; }
                .dark #promoFiltersBar select:focus { border-color:#f59e0b !important; box-shadow:0 0 0 1px #f59e0b; }
                .dark #promoFiltersBar .promo-reset-btn { background:#1f2937 !important; border-color:#334155 !important; color:#e2e8f0 !important; }
                .dark #promoFiltersBar .promo-reset-btn:hover { background:#273349 !important; }
                .dark #promosFooter { background:#1e293b !important; border-color:#334155 !important; }
                .dark #promosFooter button { background:#1f2937; border-color:#334155; color:#e2e8f0; }
                .dark #promosFooter button:hover { background:#273349; }
                .dark #promosFooter button[disabled] { opacity:.35; }
                `;
                document.head.appendChild(style);
            }

            // Appointment blocking logic (client-side + localStorage for now)
            function updateBlockStatus(){
                let single=null; let range=null;
                try { single = localStorage.getItem(BLOCK_KEY) || null; } catch(e){}
                try {
                    const raw = localStorage.getItem(BLOCK_RANGE_KEY);
                    if(raw){ const obj = JSON.parse(raw); if(obj && obj.start && obj.end){ range = obj; } }
                } catch(e){}
                if(range){
                    blockStatus.textContent = 'Blocked range: '+range.start+' to '+range.end+' (inclusive)';
                    blockStatus.classList.remove('text-gray-500');
                    blockStatus.classList.add('text-red-600');
                    if(blockDateStart) blockDateStart.value = range.start;
                    if(blockDateEnd) blockDateEnd.value = range.end;
                } else if(single){
                    blockStatus.textContent = 'Blocked date: '+single+' (users cannot book this day)';
                    blockStatus.classList.remove('text-gray-500');
                    blockStatus.classList.add('text-red-600');
                    if(blockDateInput) blockDateInput.value = single;
                } else {
                    blockStatus.textContent = 'No blocked dates set.';
                    blockStatus.classList.add('text-gray-500');
                    blockStatus.classList.remove('text-red-600');
                    if(blockDateInput) blockDateInput.value = '';
                    if(blockDateStart) blockDateStart.value='';
                    if(blockDateEnd) blockDateEnd.value='';
                }
            }
            updateBlockStatus();
            // --- Server sync (lightweight) ---
            const API_URL = '../../controllers/admin/blockdate.php';
            async function syncFromServer(){
                try {
                    const r = await fetch(API_URL + '?_=' + Date.now());
                    if(!r.ok) return;
                    const data = await r.json();
                    // Server may send: blocked_date or blocked_range {start,end}
                    if(data){
                        if(data.blocked_range && data.blocked_range.start && data.blocked_range.end){
                            try { localStorage.setItem(BLOCK_RANGE_KEY, JSON.stringify(data.blocked_range)); } catch(e){}
                            try { localStorage.removeItem(BLOCK_KEY); } catch(e){}
                        } else if(data.blocked_date){
                            try { localStorage.setItem(BLOCK_KEY, data.blocked_date); } catch(e){}
                            try { localStorage.removeItem(BLOCK_RANGE_KEY); } catch(e){}
                        } else {
                            try { localStorage.removeItem(BLOCK_KEY); localStorage.removeItem(BLOCK_RANGE_KEY); } catch(e){}
                        }
                    }
                    updateBlockStatus();
                } catch(e){ /* silent */ }
            }
            async function persistBlock(payload){
                try {
                    const fd = new FormData();
                    if(payload.type==='single') fd.append('date', payload.date || '');
                    if(payload.type==='range') { fd.append('start', payload.start||''); fd.append('end', payload.end||''); }
                    await fetch(API_URL, { method: 'POST', body: fd });
                } catch(e){ /* silent */ }
            }
            function setSingle(dateStr){
                if(!dateStr) return;
                try { localStorage.setItem(BLOCK_KEY, dateStr); localStorage.removeItem(BLOCK_RANGE_KEY); } catch(e){}
                updateBlockStatus();
                persistBlock({type:'single', date:dateStr});
                toast('Blocked single date '+dateStr, 'warn');
            }
            function setRange(start,end){
                if(!start || !end) return;
                if(end < start){ toast('End date cannot be before start date','warn'); return; }
                try { localStorage.setItem(BLOCK_RANGE_KEY, JSON.stringify({start,end})); localStorage.removeItem(BLOCK_KEY); } catch(e){}
                updateBlockStatus();
                persistBlock({type:'range', start, end});
                toast('Blocked range '+start+' → '+end,'warn');
            }
            function clearBlock(){
                try { localStorage.removeItem(BLOCK_KEY); localStorage.removeItem(BLOCK_RANGE_KEY); } catch(e){}
                updateBlockStatus();
                persistBlock({type:'single', date:''});
                toast('Cleared blocked dates','info');
            }
            blockTodayBtn?.addEventListener('click', (e)=>{ e.preventDefault(); const today = new Date(); const ds=today.toISOString().substring(0,10); setSingle(ds); });
            blockDateBtn?.addEventListener('click', (e)=>{ e.preventDefault(); if(blockDateInput.value) setSingle(blockDateInput.value); });
            blockRangeBtn?.addEventListener('click', (e)=>{ e.preventDefault(); if(blockDateStart.value && blockDateEnd.value) setRange(blockDateStart.value, blockDateEnd.value); });
            clearBlockBtn?.addEventListener('click', (e)=>{ e.preventDefault(); clearBlock(); });

            // Lightweight toast (reuse existing if available)
            function toast(msg,type='info'){
                if(typeof showToast==='function'){ showToast(msg, type==='warn'?'error':'success'); return; }
                let wrap=document.getElementById('adminToastWrap');
                if(!wrap){ wrap=document.createElement('div'); wrap.id='adminToastWrap'; wrap.className='fixed z-[999] top-4 right-4 space-y-2 w-72'; document.body.appendChild(wrap);}                const el=document.createElement('div');
                const base='px-3 py-2 rounded-md text-xs shadow border animate-fade-in';
                const cls= type==='warn' ? 'bg-red-50 border-red-200 text-red-700' : 'bg-gray-800 border-gray-700 text-gray-100';
                el.className=base+' '+cls; el.textContent=msg; wrap.appendChild(el); setTimeout(()=>{ el.classList.add('opacity-0'); setTimeout(()=>el.remove(),300); },3000);
            }
            // Initial sync (non-blocking)
            syncFromServer();
        }

        // ===== Audit Logs (embedded) =====
        let auditOnce = false;
        let audit = { tab: 'all', q: '', page: 1, per: 15, total: 0, from: '', to: '' };
        function initAuditSectionOnce(){ if(auditOnce) return; auditOnce=true; initAuditUI(); bindAuditEvents(); }
        function initAuditUI(){
            const tabsCfg = [
                {k:'all', label:'All'},
                {k:'additions', label:'Additions'},
                {k:'updates', label:'Updates'},
                {k:'price_changes', label:'Price'},
                {k:'stock_changes', label:'Stock'},
                {k:'sitters', label:'Sitters'},
                {k:'orders', label:'Orders'}
            ];
            const tabsWrap = document.getElementById('auditTabs');
            tabsWrap.innerHTML = '';
            tabsCfg.forEach(t => {
                const a = document.createElement('button');
                a.textContent = t.label;
                a.className = 'px-3 py-1.5 rounded-full border text-sm';
                a.dataset.tab = t.k;
                a.addEventListener('click', ()=>{ audit.tab = t.k; audit.page = 1; styleAuditTabs(); refreshAudit(); });
                tabsWrap.appendChild(a);
            });
            styleAuditTabs();
        }
        function styleAuditTabs(){
            const tabsWrap = document.getElementById('auditTabs');
            tabsWrap.querySelectorAll('button').forEach(btn => {
                const active = btn.dataset.tab === audit.tab;
                btn.className = 'px-3 py-1.5 rounded-full border text-sm ' + (active ? 'bg-orange-50 border-orange-300 text-orange-700' : 'bg-white border-gray-200 text-gray-700 hover:bg-gray-50');
            });
        }
        function bindAuditEvents(){
            const search = document.getElementById('auditSearch');
            const btn = document.getElementById('auditSearchBtn');
            let t; search.addEventListener('input', ()=>{ clearTimeout(t); t=setTimeout(()=>{ audit.q = search.value.trim(); audit.page = 1; refreshAudit(); }, 200); });
            btn.addEventListener('click', ()=>{ audit.q = search.value.trim(); audit.page = 1; refreshAudit(); });
            const f = document.getElementById('auditFrom');
            const to = document.getElementById('auditTo');
            f.addEventListener('change', ()=>{ audit.from = f.value; audit.page=1; refreshAudit(); });
            to.addEventListener('change', ()=>{ audit.to = to.value; audit.page=1; refreshAudit(); });
            const reset = document.getElementById('auditResetBtn');
            reset.addEventListener('click', ()=>{
                audit.q=''; audit.from=''; audit.to=''; audit.page=1; audit.tab='all';
                if (search) search.value='';
                if (f) f.value='';
                if (to) to.value='';
                styleAuditTabs();
                refreshAudit();
            });
            document.getElementById('auditPrev').addEventListener('click', ()=>{ if(audit.page>1){ audit.page--; refreshAudit(); }});
            document.getElementById('auditNext').addEventListener('click', ()=>{ const pages = Math.max(1, Math.ceil(audit.total / audit.per)); if(audit.page<pages){ audit.page++; refreshAudit(); }});
        }
        async function refreshAudit(){
            try{
                document.getElementById('auditRefreshedAt').textContent = new Date().toLocaleString();
                const url = `../../controllers/admin/logs.php?tab=${encodeURIComponent(audit.tab)}&q=${encodeURIComponent(audit.q)}&page=${audit.page}&per=${audit.per}&from=${encodeURIComponent(audit.from||'')}&to=${encodeURIComponent(audit.to||'')}`;
                const r = await fetch(url);
                const d = await r.json();
                if(!d.success){ throw new Error(d.error||'Failed to load logs'); }
                audit.total = d.total||0;
                renderAudit(d.items||[]);
                const pages = Math.max(1, Math.ceil(audit.total / audit.per));
                document.getElementById('auditPageInfo').textContent = `Page ${audit.page} / ${pages}`;
                document.getElementById('auditTotal').textContent = String(audit.total);
                document.getElementById('auditShowing').textContent = String((d.items||[]).length);
                // update stat cards if present
                if(d.stats){
                    const s = d.stats;
                    document.getElementById('auditStatAll').textContent = s.all ?? 0;
                    document.getElementById('auditStatAdd').textContent = s.additions ?? 0;
                    document.getElementById('auditStatUpd').textContent = s.updates ?? 0;
                    document.getElementById('auditStatPrice').textContent = s.price_changes ?? 0;
                    document.getElementById('auditStatStock').textContent = s.stock_changes ?? 0;
                    const other = (s.sitters??0) + (s.orders??0);
                    document.getElementById('auditStatOther').textContent = other;
                }
            } catch(e){
                renderAudit([]);
                document.getElementById('auditPageInfo').textContent = 'Page 1 / 1';
                document.getElementById('auditTotal').textContent = '0';
                document.getElementById('auditShowing').textContent = '0';
            } finally {
                if(window.lucide) window.lucide.createIcons();
            }
        }
        function summarizeChange(prev, next){
            if(!prev && !next) return [];
            const p = prev||{}; const n = next||{}; const keys = Array.from(new Set([...Object.keys(p), ...Object.keys(n)]));
            const out=[]; for(const k of keys){ const pv = typeof p[k]==='object'? JSON.stringify(p[k]) : (p[k]??''); const nv = typeof n[k]==='object'? JSON.stringify(n[k]) : (n[k]??''); if(String(pv)!==String(nv)) out.push({key:k, prev:String(pv), next:String(nv)}); if(out.length>=8) break; }
            return out;
        }
        function badgeClass(t){
            switch(t){
                case 'additions': return 'bg-green-100 text-green-800 border border-green-200';
                case 'updates': return 'bg-indigo-100 text-indigo-800 border border-indigo-200';
                case 'price_changes': return 'bg-amber-100 text-amber-800 border border-amber-200';
                case 'stock_changes': return 'bg-sky-100 text-sky-800 border border-sky-200';
                case 'auth_login': return 'bg-emerald-100 text-emerald-800 border border-emerald-200';
                case 'auth_logout': return 'bg-stone-100 text-stone-800 border border-stone-200';
                case 'auth_login_failed': return 'bg-red-100 text-red-800 border border-red-200';
                default: return 'bg-gray-100 text-gray-800 border border-gray-200';
            }
        }
        function esc(s){ const d = document.createElement('div'); d.textContent = s==null? '' : String(s); return d.innerHTML; }
        function renderAudit(items){
            const feed = document.getElementById('auditFeed');
            feed.innerHTML = '';
            if(!items || items.length===0){ feed.innerHTML = '<div class="bg-white border border-gray-200 rounded-xl p-6 text-center text-gray-500">No activities found.</div>'; return; }
            items.forEach(row => {
                const ts = row.timestamp||'';
                const user = row.user_email || (row.users_id? `User #${row.users_id}` : 'Unknown user');
                const ip = row.ip_address || '';
                const action = row.action_type || 'updates';
                const target = [row.target||'', row.target_id||''].join(' ').trim();
                const details = row.details||{}; const message = details.message || '';
                const changes = summarizeChange(row.previous, row.new);
                const fieldsChanged = Array.isArray(details.fields_changed) ? details.fields_changed : [];
                const chip = badgeClass(action);
                const card = document.createElement('div');
                card.className = 'bg-white border border-gray-200 rounded-xl p-4';
                card.innerHTML = `
                    <div class="flex items-start gap-4">
                        <div class="mt-1"><span class="inline-block w-3 h-3 rounded-full bg-orange-500"></span></div>
                        <div class="flex-1">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="text-sm text-gray-500">${esc(new Date(ts).toLocaleString())}</span>
                                    <span class="text-sm text-gray-400">•</span>
                                    <span class="text-sm text-gray-700">By ${esc(user)}</span>
                                    ${ip? `<span class="text-xs text-gray-400">(${esc(ip)})</span>`:''}
                                </div>
                                <span class="text-xs px-2 py-1 rounded-full ${chip} capitalize">${esc(action.replaceAll('_',' '))}</span>
                            </div>
                            <div class="mt-2 text-sm text-gray-900">
                                ${message? `<p class="font-medium">${esc(message)}</p>` : `<p class="font-medium">Updated <span class="text-gray-600">${esc(target||'record')}</span></p>`}
                            </div>
                            ${fieldsChanged.length? `<div class="mt-2 flex flex-wrap gap-1">${fieldsChanged.map(f=>`<span class=\"text-xs px-2 py-0.5 rounded-full bg-gray-100 text-gray-700 border border-gray-200\">${esc(f)}</span>`).join('')}</div>`:''}
                            ${changes.length? `<div class=\"mt-3 grid grid-cols-1 md:grid-cols-2 gap-3\">${changes.map(ch=>`<div class=\"p-3 rounded-lg bg-gray-50 border border-gray-200\"><div class=\"text-xs font-medium text-gray-500 mb-1\">${esc(ch.key)}</div><div class=\"text-xs text-gray-700\"><span class=\"text-gray-500\">Previous:</span> ${esc(ch.prev)}</div><div class=\"text-xs text-gray-700\"><span class=\"text-gray-500\">New:</span> ${esc(ch.next)}</div></div>`).join('')}</div>`:''}
                            ${target? `<div class=\"mt-3 text-xs text-gray-500\">Target: ${esc(target)}</div>`:''}
                        </div>
                    </div>`;
                feed.appendChild(card);
            });
        }
    </script>
    <!-- Tailwind safelist (hidden) to ensure dynamic specialty classes are included by CDN JIT -->
    <div class="hidden">
        <span class="bg-orange-100 text-orange-800 border border-orange-200"></span>
        <span class="bg-purple-100 text-purple-800 border border-purple-200"></span>
        <span class="bg-blue-100 text-blue-800 border border-blue-200"></span>
        <span class="bg-cyan-100 text-cyan-800 border border-cyan-200"></span>
        <span class="bg-emerald-100 text-emerald-800 border border-emerald-200"></span>
        <span class="bg-pink-100 text-pink-800 border border-pink-200"></span>
        <span class="bg-yellow-100 text-yellow-800 border border-yellow-200"></span>
        <span class="bg-lime-100 text-lime-800 border border-lime-200"></span>
        <span class="bg-amber-100 text-amber-800 border border-amber-200"></span>
        <span class="bg-rose-100 text-rose-800 border border-rose-200"></span>
        <span class="bg-indigo-100 text-indigo-800 border border-indigo-200"></span>
        <span class="bg-gray-100 text-gray-800 border border-gray-200"></span>
    </div>
</body>
</html>