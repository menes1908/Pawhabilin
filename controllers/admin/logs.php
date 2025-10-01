<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../database.php';
require_once __DIR__ . '/../../utils/helper.php';

function out($ok, $data = [], $code = 200){ http_response_code($code); echo json_encode($ok? array_merge(['success'=>true],$data) : array_merge(['success'=>false],$data)); exit; }
if(!$connections){ out(false, ['error'=>'DB connection missing'], 500); }

ensure_admin_activity_logs_table($connections);

// Schema detection
function has_col($conn,$col){ $res=@mysqli_query($conn, "SHOW COLUMNS FROM admin_activity_logs LIKE '".mysqli_real_escape_string($conn,$col)."'"); if($res){ $n=mysqli_num_rows($res)>0; mysqli_free_result($res); return $n; } return false; }
$hasRich = has_col($connections,'action_type');
$hasLegacy = !$hasRich && has_col($connections,'admin_activity_logs_action');

$tab = isset($_GET['tab']) ? strtolower(trim($_GET['tab'])) : 'all';
$allowed = ['all','additions','updates','price_changes','stock_changes','sitters','orders'];
if(!in_array($tab, $allowed, true)) $tab='all';
$q = trim($_GET['q'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$per = max(5, min(50, (int)($_GET['per'] ?? 15)));
$offset = ($page-1)*$per;
$from = trim($_GET['from'] ?? ''); // YYYY-MM-DD
$to   = trim($_GET['to'] ?? '');   // YYYY-MM-DD

// Build base (tab-less) filters for search and date
$baseWhere = [];
if($q !== ''){
    $qs = '%'.mysqli_real_escape_string($connections,$q).'%';
    if($hasRich){
        $baseWhere[] = "(user_email LIKE '$qs' OR action_type LIKE '$qs' OR target LIKE '$qs' OR target_id LIKE '$qs' OR details LIKE '$qs' OR previous_value LIKE '$qs' OR new_value LIKE '$qs')";
    } elseif($hasLegacy){
        $baseWhere[] = "(admin_activity_logs_action LIKE '$qs' OR admin_activity_logs_details LIKE '$qs')";
    }
}
if($from !== '' || $to !== ''){
    // normalize to full-day bounds
    $fromDt = $from !== '' ? ($from.' 00:00:00') : null;
    $toDt = $to !== '' ? ($to.' 23:59:59') : null;
    if($hasRich){
        if($fromDt && $toDt){ $baseWhere[] = "timestamp BETWEEN '".mysqli_real_escape_string($connections,$fromDt)."' AND '".mysqli_real_escape_string($connections,$toDt)."'"; }
        elseif($fromDt){ $baseWhere[] = "timestamp >= '".mysqli_real_escape_string($connections,$fromDt)."'"; }
        elseif($toDt){ $baseWhere[] = "timestamp <= '".mysqli_real_escape_string($connections,$toDt)."'"; }
    } elseif($hasLegacy){
        if($fromDt && $toDt){ $baseWhere[] = "admin_activity_logs_created_at BETWEEN '".mysqli_real_escape_string($connections,$fromDt)."' AND '".mysqli_real_escape_string($connections,$toDt)."'"; }
        elseif($fromDt){ $baseWhere[] = "admin_activity_logs_created_at >= '".mysqli_real_escape_string($connections,$fromDt)."'"; }
        elseif($toDt){ $baseWhere[] = "admin_activity_logs_created_at <= '".mysqli_real_escape_string($connections,$toDt)."'"; }
    }
}

// Now clone and add tab-specific filters for the main list
$where = $baseWhere;
if($tab !== 'all'){
    if(in_array($tab, ['additions','updates','price_changes','stock_changes'], true)){
        if($hasRich) $where[] = "action_type='".mysqli_real_escape_string($connections,$tab)."'";
        elseif($hasLegacy) $where[] = "admin_activity_logs_action='".mysqli_real_escape_string($connections,$tab)."'";
    } elseif(in_array($tab, ['sitters','orders'], true)){
        if($hasRich){
            if($tab==='sitters'){
                $where[] = "(target LIKE 'sitter%' OR target LIKE 'sitters%')";
            } elseif($tab==='orders'){
                $where[] = "(target LIKE 'order%' OR target LIKE 'transaction%' OR target LIKE 'delivery%')";
            }
        } elseif($hasLegacy){
            // Fallback: keyword search within details JSON/text (less precise but compatible)
            if($tab==='sitters'){
                $where[] = "(admin_activity_logs_details LIKE '%sitter%')";
            } elseif($tab==='orders'){
                $where[] = "(admin_activity_logs_details LIKE '%order%' OR admin_activity_logs_details LIKE '%transaction%' OR admin_activity_logs_details LIKE '%delivery%')";
            }
        }
    }
}
$whereSql = $where? ('WHERE '.implode(' AND ',$where)) : '';

$countSql = $hasRich ? "SELECT COUNT(*) c FROM admin_activity_logs $whereSql" : ($hasLegacy ? "SELECT COUNT(*) c FROM admin_activity_logs $whereSql" : null);
$total = 0;
if($countSql){ if($res=mysqli_query($connections,$countSql)){ if($r=mysqli_fetch_assoc($res)) $total=(int)$r['c']; mysqli_free_result($res);} }

if($hasRich){
    $sql = "SELECT id, timestamp, users_id, user_email, ip_address, http_method, request_path, action_type, target, target_id, details, previous_value, new_value FROM admin_activity_logs $whereSql ORDER BY timestamp DESC, id DESC LIMIT $per OFFSET $offset";
} elseif($hasLegacy){
    $sql = "SELECT admin_activity_logs_id AS id, admin_activity_logs_created_at AS timestamp, admin_id AS users_id, NULL AS user_email, NULL AS ip_address, NULL AS http_method, NULL AS request_path, admin_activity_logs_action AS action_type, '' AS target, '' AS target_id, admin_activity_logs_details AS details, NULL AS previous_value, NULL AS new_value FROM admin_activity_logs $whereSql ORDER BY admin_activity_logs_created_at DESC, admin_activity_logs_id DESC LIMIT $per OFFSET $offset";
} else {
    out(true, ['total'=>0,'page'=>$page,'per'=>$per,'items'=>[]]);
}

$items=[];
if($res = mysqli_query($connections,$sql)){
    while($row=mysqli_fetch_assoc($res)){
        // Normalize output
        $details = $row['details'];
        $prev = $row['previous_value'];
        $new = $row['new_value'];
        $detailsObj = json_decode((string)$details,true); if(!is_array($detailsObj) && $details!==null) $detailsObj = ['message'=>(string)$details];
        $prevObj = json_decode((string)$prev,true); if(!is_array($prevObj)) $prevObj = null;
        $newObj = json_decode((string)$new,true); if(!is_array($newObj)) $newObj = null;

        // Legacy flattening: promote nested details/message/fields_changed and previous/new if present in details JSON
        if(!$hasRich && is_array($detailsObj)){
            // Promote nested details JSON if stored as string
            if(isset($detailsObj['details'])){
                $embedded = $detailsObj['details'];
                if(is_string($embedded)){
                    $decoded = json_decode($embedded, true);
                    if(is_array($decoded)) $embedded = $decoded;
                }
                if(is_array($embedded)){
                    if(!isset($detailsObj['message']) && isset($embedded['message'])) $detailsObj['message'] = $embedded['message'];
                    if(!isset($detailsObj['fields_changed']) && isset($embedded['fields_changed'])) $detailsObj['fields_changed'] = $embedded['fields_changed'];
                }
            }
            // previous/new
            if($prevObj===null && isset($detailsObj['previous'])) $prevObj = $detailsObj['previous'];
            if($newObj===null && isset($detailsObj['new'])) $newObj = $detailsObj['new'];
            // target fallback
            if((!isset($row['target']) || $row['target']==='') && isset($detailsObj['target'])) $row['target'] = $detailsObj['target'];
            if((!isset($row['target_id']) || $row['target_id']==='') && isset($detailsObj['target_id'])) $row['target_id'] = $detailsObj['target_id'];
        }
        $items[] = [
            'id' => (int)($row['id'] ?? 0),
            'timestamp' => $row['timestamp'] ?? '',
            'users_id' => isset($row['users_id'])? (int)$row['users_id'] : null,
            'user_email' => $row['user_email'] ?? null,
            'ip_address' => $row['ip_address'] ?? null,
            'http_method' => $row['http_method'] ?? null,
            'request_path' => $row['request_path'] ?? null,
            'action_type' => $row['action_type'] ?? 'updates',
            'target' => $row['target'] ?? '',
            'target_id' => $row['target_id'] ?? '',
            'details' => $detailsObj,
            'previous' => $prevObj,
            'new' => $newObj
        ];
    }
    mysqli_free_result($res);
}
// Build stats (totals per tab) with the same search/date filters
$stats = [];
$baseSqlWhere = $baseWhere? ('WHERE '.implode(' AND ', $baseWhere)) : '';
// Helper for count with extra condition
$countFor = function(string $extra) use ($connections, $hasRich, $hasLegacy, $baseSqlWhere){
    $tbl = 'admin_activity_logs';
    $whereParts = [];
    if($baseSqlWhere){ $whereParts[] = substr($baseSqlWhere, 6); } // remove leading WHERE
    if($extra!==''){ $whereParts[] = $extra; }
    $w = $whereParts? ('WHERE '.implode(' AND ',$whereParts)) : '';
    $sql = "SELECT COUNT(*) c FROM $tbl $w";
    $c = 0; if($res = mysqli_query($connections,$sql)){ if($r=mysqli_fetch_assoc($res)) $c=(int)$r['c']; mysqli_free_result($res);} return $c;
};
if($hasRich || $hasLegacy){
    // Action-based
    $stats['all'] = $countFor('');
    $stats['additions'] = $countFor($hasRich? "action_type='additions'" : "admin_activity_logs_action='additions'");
    $stats['updates'] = $countFor($hasRich? "action_type='updates'" : "admin_activity_logs_action='updates'");
    $stats['price_changes'] = $countFor($hasRich? "action_type='price_changes'" : "admin_activity_logs_action='price_changes'");
    $stats['stock_changes'] = $countFor($hasRich? "action_type='stock_changes'" : "admin_activity_logs_action='stock_changes'");
    // Target-based
    if($hasRich){
        $stats['sitters'] = $countFor("(target LIKE 'sitter%' OR target LIKE 'sitters%')");
        $stats['orders'] = $countFor("(target LIKE 'order%' OR target LIKE 'transaction%' OR target LIKE 'delivery%')");
    } else { // legacy: keyword search in details
        $stats['sitters'] = $countFor("(admin_activity_logs_details LIKE '%sitter%')");
        $stats['orders'] = $countFor("(admin_activity_logs_details LIKE '%order%' OR admin_activity_logs_details LIKE '%transaction%' OR admin_activity_logs_details LIKE '%delivery%')");
    }
}

out(true, ['total'=>$total,'page'=>$page,'per'=>$per,'items'=>$items,'stats'=>$stats,'filters'=>['tab'=>$tab,'q'=>$q,'from'=>$from,'to'=>$to]]);