<?php
// Admin Reports Controller: Revenue Trends API
session_start();
require_once __DIR__ . '/../../database.php';
header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

if (!isset($connections) || !$connections) {
    echo json_encode(['success' => false, 'error' => 'DB connection unavailable']);
    exit;
}

// Basic admin gate (same semantics as admin.php)
$uid = isset($_SESSION['users_id']) ? (int)$_SESSION['users_id'] : 0;
if ($uid <= 0) { echo json_encode(['success'=>false,'error'=>'unauthorized']); exit; }
$role = null;
if ($stmt = mysqli_prepare($connections, 'SELECT users_role FROM users WHERE users_id = ? LIMIT 1')) {
    mysqli_stmt_bind_param($stmt, 'i', $uid);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $role);
    mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);
}
if ((string)$role !== '1') { echo json_encode(['success'=>false,'error'=>'forbidden']); exit; }

$action = isset($_GET['action']) ? $_GET['action'] : '';
$period = isset($_GET['period']) ? strtolower(trim($_GET['period'])) : 'monthly';
if ($action !== 'revenue_trends') { echo json_encode(['success'=>false,'error'=>'invalid_action']); exit; }

function zeroFill(&$arr, $keys, $default = 0) {
    foreach ($keys as $k => $label) {
        if (!isset($arr[$k])) $arr[$k] = 0.0;
    }
}

// Build datasets per period
$series = [];
$totalRevenue = 0.0;
$totalCount = 0;

if ($period === 'daily') {
    // Last 7 days including today
    $startSql = "DATE(NOW()) - INTERVAL 6 DAY";
    $sql = "SELECT DATE(transactions_created_at) d,
                    COALESCE(SUM(transactions_amount),0) s,
                    COUNT(*) c,
                    COALESCE(SUM(CASE WHEN transactions_type='product' THEN transactions_amount ELSE 0 END),0) s_prod,
                    COALESCE(SUM(CASE WHEN transactions_type='subscription' THEN transactions_amount ELSE 0 END),0) s_sub,
                    SUM(CASE WHEN transactions_type='product' THEN 1 ELSE 0 END) c_prod,
                    SUM(CASE WHEN transactions_type='subscription' THEN 1 ELSE 0 END) c_sub
            FROM transactions
            WHERE transactions_created_at >= $startSql
            GROUP BY DATE(transactions_created_at)
            ORDER BY d ASC";
    $data = [];
    if ($res = mysqli_query($connections, $sql)) {
        while ($row = mysqli_fetch_assoc($res)) {
            $data[$row['d']] = [
                's' => (float)$row['s'], 'c' => (int)$row['c'],
                's_prod' => (float)$row['s_prod'], 's_sub' => (float)$row['s_sub'],
                'c_prod' => (int)$row['c_prod'], 'c_sub' => (int)$row['c_sub'],
            ];
            $totalRevenue += (float)$row['s'];
            $totalCount += (int)$row['c'];
        }
        mysqli_free_result($res);
    }
    // Prepare 7 sequential dates
    $labels = [];
    for ($i = 6; $i >= 0; $i--) {
        $d = date('Y-m-d', strtotime("-$i day"));
        $labels[$d] = date('D', strtotime($d));
    }
    foreach ($labels as $d => $lbl) {
        $row = $data[$d] ?? ['s'=>0.0,'c'=>0,'s_prod'=>0.0,'s_sub'=>0.0,'c_prod'=>0,'c_sub'=>0];
        $series[] = [
            'name' => $lbl,
            'value' => (float)$row['s'],
            'count' => (int)$row['c'],
            'causes' => [ 'product' => (float)$row['s_prod'], 'subscription' => (float)$row['s_sub'] ],
        ];
    }
} elseif ($period === 'weekly') {
    // Last 4 ISO weeks (Mon-based) using mode 3
    $sql = "SELECT YEARWEEK(transactions_created_at, 3) yw,
                    COALESCE(SUM(transactions_amount),0) s,
                    COUNT(*) c,
                    COALESCE(SUM(CASE WHEN transactions_type='product' THEN transactions_amount ELSE 0 END),0) s_prod,
                    COALESCE(SUM(CASE WHEN transactions_type='subscription' THEN transactions_amount ELSE 0 END),0) s_sub,
                    SUM(CASE WHEN transactions_type='product' THEN 1 ELSE 0 END) c_prod,
                    SUM(CASE WHEN transactions_type='subscription' THEN 1 ELSE 0 END) c_sub
            FROM transactions
            WHERE transactions_created_at >= (DATE(NOW()) - INTERVAL 28 DAY)
            GROUP BY YEARWEEK(transactions_created_at, 3)
            ORDER BY yw ASC";
    $data = [];
    if ($res = mysqli_query($connections, $sql)) {
        while ($row = mysqli_fetch_assoc($res)) {
            $data[$row['yw']] = [
                's' => (float)$row['s'], 'c' => (int)$row['c'],
                's_prod' => (float)$row['s_prod'], 's_sub' => (float)$row['s_sub'],
                'c_prod' => (int)$row['c_prod'], 'c_sub' => (int)$row['c_sub'],
            ];
            $totalRevenue += (float)$row['s'];
            $totalCount += (int)$row['c'];
        }
        mysqli_free_result($res);
    }
    // Determine the last 4 groups we have (ascending), label as W1..W4
    $wlabels = ['W1','W2','W3','W4'];
    $keys = array_keys($data);
    sort($keys, SORT_NUMERIC);
    $slice = count($keys) > 4 ? array_slice($keys, -4) : $keys;
    // Left-pad with empty groups to always show 4
    $padCount = max(0, 4 - count($slice));
    for ($i=0; $i<$padCount; $i++) { array_unshift($slice, null); }
    foreach ($slice as $i => $k) {
        $row = $k !== null ? $data[$k] : ['s'=>0.0,'c'=>0,'s_prod'=>0.0,'s_sub'=>0.0];
        $series[] = [
            'name' => $wlabels[$i],
            'value' => (float)$row['s'],
            'count' => (int)$row['c'],
            'causes' => [ 'product' => (float)$row['s_prod'], 'subscription' => (float)$row['s_sub'] ],
        ];
    }
} elseif ($period === 'monthly') {
    // Current year, 12 months
    $sql = "SELECT MONTH(transactions_created_at) m,
                    COALESCE(SUM(transactions_amount),0) s,
                    COUNT(*) c,
                    COALESCE(SUM(CASE WHEN transactions_type='product' THEN transactions_amount ELSE 0 END),0) s_prod,
                    COALESCE(SUM(CASE WHEN transactions_type='subscription' THEN transactions_amount ELSE 0 END),0) s_sub,
                    SUM(CASE WHEN transactions_type='product' THEN 1 ELSE 0 END) c_prod,
                    SUM(CASE WHEN transactions_type='subscription' THEN 1 ELSE 0 END) c_sub
            FROM transactions
            WHERE YEAR(transactions_created_at) = YEAR(NOW())
            GROUP BY MONTH(transactions_created_at)
            ORDER BY m ASC";
    $data = array_fill(1, 12, ['s'=>0.0,'c'=>0,'s_prod'=>0.0,'s_sub'=>0.0,'c_prod'=>0,'c_sub'=>0]);
    if ($res = mysqli_query($connections, $sql)) {
        while ($row = mysqli_fetch_assoc($res)) {
            $m = (int)$row['m'];
            $data[$m] = [
                's' => (float)$row['s'], 'c' => (int)$row['c'],
                's_prod' => (float)$row['s_prod'], 's_sub' => (float)$row['s_sub'],
                'c_prod' => (int)$row['c_prod'], 'c_sub' => (int)$row['c_sub'],
            ];
            $totalRevenue += (float)$row['s'];
            $totalCount += (int)$row['c'];
        }
        mysqli_free_result($res);
    }
    $months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
    for ($m = 1; $m <= 12; $m++) {
        $row = $data[$m];
        $series[] = [
            'name' => $months[$m-1],
            'value' => (float)$row['s'],
            'count' => (int)$row['c'],
            'causes' => [ 'product' => (float)$row['s_prod'], 'subscription' => (float)$row['s_sub'] ],
        ];
    }
} elseif ($period === 'annual') {
    // Last 4 years including current
    $sql = "SELECT YEAR(transactions_created_at) y,
                    COALESCE(SUM(transactions_amount),0) s,
                    COUNT(*) c,
                    COALESCE(SUM(CASE WHEN transactions_type='product' THEN transactions_amount ELSE 0 END),0) s_prod,
                    COALESCE(SUM(CASE WHEN transactions_type='subscription' THEN transactions_amount ELSE 0 END),0) s_sub,
                    SUM(CASE WHEN transactions_type='product' THEN 1 ELSE 0 END) c_prod,
                    SUM(CASE WHEN transactions_type='subscription' THEN 1 ELSE 0 END) c_sub
            FROM transactions
            WHERE YEAR(transactions_created_at) >= YEAR(NOW())-3
            GROUP BY YEAR(transactions_created_at)
            ORDER BY y ASC";
    $data = [];
    if ($res = mysqli_query($connections, $sql)) {
        while ($row = mysqli_fetch_assoc($res)) {
            $data[(int)$row['y']] = [
                's' => (float)$row['s'], 'c' => (int)$row['c'],
                's_prod' => (float)$row['s_prod'], 's_sub' => (float)$row['s_sub'],
                'c_prod' => (int)$row['c_prod'], 'c_sub' => (int)$row['c_sub'],
            ];
            $totalRevenue += (float)$row['s'];
            $totalCount += (int)$row['c'];
        }
        mysqli_free_result($res);
    }
    $years = [];
    for ($i = 3; $i >= 0; $i--) {
        $y = (int)date('Y') - $i;
        $years[] = $y;
        $row = $data[$y] ?? ['s'=>0.0,'c'=>0,'s_prod'=>0.0,'s_sub'=>0.0];
        $series[] = [
            'name' => (string)$y,
            'value' => (float)$row['s'],
            'count' => (int)$row['c'],
            'causes' => [ 'product' => (float)$row['s_prod'], 'subscription' => (float)$row['s_sub'] ],
        ];
    }
} else {
    echo json_encode(['success'=>false,'error'=>'invalid_period']);
    exit;
}

echo json_encode([
    'success' => true,
    'period' => $period,
    'revenue' => (float)$totalRevenue,
    'transactions' => (int)$totalCount,
    'chartData' => $series,
]);
