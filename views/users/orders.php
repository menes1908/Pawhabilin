<?php
require_once __DIR__ . '/../../utils/session.php';
require_once __DIR__ . '/../../database.php';

session_start_if_needed();
$sessionUser = get_current_user_session();
if (!$sessionUser) {
    $redirect = urlencode('views/users/orders.php');
    header('Location: ../../login.php?redirect=' . $redirect);
    exit();
}

$usersId = (int)($sessionUser['users_id'] ?? 0);
if ($usersId <= 0) { header('Location: ../../login.php'); exit(); }

$flashMessage = '';
$flashType = 'success';

function set_orders_flash(string $msg, string $type='success'){ $_SESSION['orders_flash_message']=$msg; $_SESSION['orders_flash_type']=$type; }
if(isset($_SESSION['orders_flash_message'])){
    $flashMessage = (string)$_SESSION['orders_flash_message'];
    $flashType = (string)($_SESSION['orders_flash_type'] ?? 'success');
    unset($_SESSION['orders_flash_message'], $_SESSION['orders_flash_type']);
}

if(!function_exists('o_e')){ function o_e($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); } }

// Handle Cancel Order (Delivery) - user scope
if($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['action'] ?? '')==='cancel_order' && $usersId>0){
    $tid = (int)($_POST['transactions_id'] ?? 0);
    if($tid>0 && isset($connections) && $connections){
        // Check status
        if($st = mysqli_prepare($connections, "SELECT d.deliveries_delivery_status FROM transactions t JOIN deliveries d ON d.transactions_id=t.transactions_id WHERE t.transactions_id=? AND t.users_id=? LIMIT 1")){
            mysqli_stmt_bind_param($st,'ii',$tid,$usersId);
            mysqli_stmt_execute($st); $rs = mysqli_stmt_get_result($st);
            $ok=false; $status='';
            if($row = mysqli_fetch_assoc($rs)){ $status=strtolower((string)$row['deliveries_delivery_status']); $ok=in_array($status,['processing','pending'],true); }
            mysqli_stmt_close($st);
            if($ok){
                if($up = mysqli_prepare($connections, "UPDATE deliveries SET deliveries_delivery_status='cancelled' WHERE transactions_id=?")){
                    mysqli_stmt_bind_param($up,'i',$tid);
                    mysqli_stmt_execute($up);
                    mysqli_stmt_close($up);
                    set_orders_flash('Order cancelled successfully.','success');
                }
            } else {
                set_orders_flash('Order cannot be cancelled anymore.','error');
            }
        }
    }
    header('Location: orders.php');
    exit();
}

// Handle Mark Received
if($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['action'] ?? '')==='mark_received' && $usersId>0){
    $tid = (int)($_POST['transactions_id'] ?? 0);
    if($tid>0 && isset($connections) && $connections){
        if($st = mysqli_prepare($connections, "SELECT d.deliveries_recipient_signature, d.deliveries_delivery_status FROM transactions t JOIN deliveries d ON d.transactions_id=t.transactions_id WHERE t.transactions_id=? AND t.users_id=? LIMIT 1")){
            mysqli_stmt_bind_param($st,'ii',$tid,$usersId);
            mysqli_stmt_execute($st); $rs = mysqli_stmt_get_result($st);
            $sigged=false; $status='';
            if($row=mysqli_fetch_assoc($rs)){ $sigged = !empty($row['deliveries_recipient_signature']); $status = strtolower((string)$row['deliveries_delivery_status']); }
            mysqli_stmt_close($st);
            if(!$sigged){
                $changed=false;
                if($up = mysqli_prepare($connections, "UPDATE deliveries SET deliveries_recipient_signature=CONCAT('Received ',NOW()), deliveries_actual_delivery_date=IF(deliveries_actual_delivery_date IS NULL,NOW(),deliveries_actual_delivery_date), deliveries_delivery_status='delivered' WHERE transactions_id=?")){
                    mysqli_stmt_bind_param($up,'i',$tid);
                    mysqli_stmt_execute($up);
                    $changed = mysqli_stmt_affected_rows($up)>0;
                    mysqli_stmt_close($up);
                }
                // Points awarding if changed just now
                if($changed){
                    if($rsAmt = mysqli_query($connections, "SELECT transactions_amount FROM transactions WHERE transactions_id=$tid AND users_id=$usersId LIMIT 1")){
                        if($rowAmt=mysqli_fetch_assoc($rsAmt)){
                            $amount=(float)$rowAmt['transactions_amount'];
                            $has_sub=false; if($rsS=mysqli_query($connections, "SELECT 1 FROM user_subscriptions WHERE users_id=$usersId AND us_status='active' AND (us_end_date IS NULL OR us_end_date>=NOW()) LIMIT 1")){ if(mysqli_fetch_row($rsS)) $has_sub=true; mysqli_free_result($rsS);}                            
                            if($has_sub && $amount>0){
                                @mysqli_query($connections, "CREATE TABLE IF NOT EXISTS user_points_balance (users_id INT PRIMARY KEY, upb_points INT NOT NULL DEFAULT 0, upb_updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
                                @mysqli_query($connections, "CREATE TABLE IF NOT EXISTS user_points_ledger (upl_id INT AUTO_INCREMENT PRIMARY KEY, users_id INT NOT NULL, upl_points INT NOT NULL, upl_reason VARCHAR(100), upl_source_type VARCHAR(50), upl_source_id INT, upl_created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, UNIQUE KEY uniq_source(users_id,upl_source_type,upl_source_id), KEY idx_user(users_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
                                $calc = (int)floor($amount/100)*10;
                                if($calc>0){
                                    if($ins=mysqli_prepare($connections, "INSERT IGNORE INTO user_points_ledger (users_id,upl_points,upl_reason,upl_source_type,upl_source_id) VALUES (?,?,?,?,?)")){
                                        $reason='Order Received'; $stype='order'; $pts=$calc; $src=$tid; mysqli_stmt_bind_param($ins,'iissi',$usersId,$pts,$reason,$stype,$src);
                                        if(mysqli_stmt_execute($ins) && mysqli_stmt_affected_rows($ins)===1){
                                            mysqli_query($connections, "INSERT INTO user_points_balance (users_id,upb_points) VALUES ($usersId,$pts) ON DUPLICATE KEY UPDATE upb_points=upb_points+VALUES(upb_points)");
                                        }
                                        mysqli_stmt_close($ins);
                                    }
                                }
                            }
                        }
                        mysqli_free_result($rsAmt);
                    }
                }
                set_orders_flash('Order marked as received.','success');
            } else {
                set_orders_flash('Order already marked as received.','info');
            }
        }
    }
    header('Location: orders.php');
    exit();
}

// Fetch User Orders + Items
$orders = [];
$itemsByTxn=[];
if(isset($connections) && $connections && $usersId>0){
    $sql = "SELECT t.transactions_id, t.transactions_amount, t.transactions_payment_method, t.transactions_created_at,
                   d.deliveries_delivery_status, d.deliveries_estimated_delivery_date, d.deliveries_actual_delivery_date, d.deliveries_recipient_signature,
                   l.location_address_line1, l.location_address_line2, l.location_barangay, l.location_city, l.location_province
            FROM transactions t
            LEFT JOIN deliveries d ON d.transactions_id = t.transactions_id
            LEFT JOIN locations l ON l.location_id = d.location_id
            WHERE t.users_id = ? AND t.transactions_type='product'
            ORDER BY t.transactions_created_at DESC, t.transactions_id DESC";
    if($st = mysqli_prepare($connections,$sql)){
        mysqli_stmt_bind_param($st,'i',$usersId);
        mysqli_stmt_execute($st); $rs = mysqli_stmt_get_result($st);
        while($r = mysqli_fetch_assoc($rs)){ $orders[] = $r; }
        mysqli_stmt_close($st);
    }
    if(!empty($orders)){
        $ids = array_column($orders,'transactions_id');
        $idList = implode(',', array_map('intval',$ids));
        if($idList!==''){
            $lineSql = "SELECT tp.transactions_id, tp.products_id, tp.tp_quantity, pr.products_name, pr.products_image_url
                        FROM transaction_products tp
                        JOIN products pr ON pr.products_id = tp.products_id
                        WHERE tp.transactions_id IN ($idList)";
            if($rs2 = mysqli_query($connections,$lineSql)){
                while($r2 = mysqli_fetch_assoc($rs2)){
                    $tid=(int)$r2['transactions_id'];
                    if(!isset($itemsByTxn[$tid])) $itemsByTxn[$tid]=[];
                    $itemsByTxn[$tid][]=$r2;
                }
                mysqli_free_result($rs2);
            }
        }
    }
}

?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>My Orders - Pawhabilin</title>
<script src="https://cdn.tailwindcss.com"></script>
<script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
<link rel="stylesheet" href="../../globals.css" />
</head>
<body class="min-h-screen bg-gradient-to-br from-orange-50 via-amber-50 to-yellow-50">
<?php $basePrefix='../..'; include __DIR__ . '/../../utils/header-users.php'; ?>
<main class="relative z-10 py-8">
  <div class="container mx-auto px-4 max-w-7xl">
    <div class="mb-8 flex items-center justify-between flex-wrap gap-4">
        <div>
            <h1 class="text-3xl font-bold bg-gradient-to-r from-orange-600 to-amber-600 bg-clip-text text-transparent flex items-center gap-2"><i data-lucide="shopping-bag" class="w-8 h-8"></i> My Orders</h1>
            <p class="text-gray-600 text-sm">All your product checkout history (delivery orders).</p>
        </div>
    </div>

    <?php if($flashMessage!==''): ?>
      <div class="mb-6 px-4 py-3 rounded-md text-sm font-medium <?php echo $flashType==='success'?'bg-emerald-100 text-emerald-700':($flashType==='error'?'bg-red-100 text-red-700':'bg-blue-100 text-blue-700'); ?>">
        <?php echo o_e($flashMessage); ?>
      </div>
    <?php endif; ?>

    <div class="bg-white border border-orange-200/60 rounded-xl shadow-sm overflow-hidden">
        <div class="p-4 flex flex-col md:flex-row md:items-center md:justify-between gap-4 border-b border-gray-200">
            <h2 class="font-semibold text-gray-700 flex items-center gap-2"><i data-lucide="package" class="w-5 h-5 text-orange-500"></i> Orders (<?php echo count($orders); ?>)</h2>
            <div class="flex flex-wrap items-center gap-3">
                <div class="flex items-center gap-2">
                    <a href="profile.php" class="inline-flex items-center gap-2 px-4 py-2 rounded-md bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 text-sm"><i data-lucide="user" class="w-4 h-4"></i> Back to Profile</a>
                    <a href="buy_products.php" class="inline-flex items-center gap-2 px-4 py-2 rounded-md bg-orange-500 text-white hover:bg-orange-600 text-sm shadow-sm"><i data-lucide="shopping-cart" class="w-4 h-4"></i> Shop More</a>
                </div>
                <input id="ordersSearch" type="text" placeholder="Search item, address..." class="px-3 py-2 text-sm border border-gray-300 rounded-md w-64 focus:outline-none focus:ring-2 focus:ring-orange-500" />
                <select id="ordersStatusFilter" class="px-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-orange-500">
                    <option value="">All Status</option>
                    <option value="processing">Processing</option>
                    <option value="out_for_delivery">Out for Delivery</option>
                    <option value="delivered">Delivered</option>
                    <option value="cancelled">Cancelled</option>
                </select>
                <select id="ordersPaymentFilter" class="px-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-orange-500">
                    <option value="">All Payments</option>
                    <option value="cod">COD</option>
                    <option value="gcash">GCash</option>
                    <option value="maya">Maya</option>
                </select>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm md:text-base" id="ordersTable">
                <thead class="bg-gray-50 text-xs md:text-sm uppercase text-gray-600 tracking-wide">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium">Order # / Amount</th>
                        <th class="px-4 py-3 text-left font-medium">Items</th>
                        <th class="px-4 py-3 text-left font-medium">Address</th>
                        <th class="px-4 py-3 text-left font-medium">Payment</th>
                        <th class="px-4 py-3 text-left font-medium">Status</th>
                        <th class="px-4 py-3 text-left font-medium">Estimated</th>
                        <th class="px-4 py-3 text-left font-medium">Actual</th>
                        <th class="px-4 py-3 text-left font-medium">Signature</th>
                        <th class="px-4 py-3 text-left font-medium">Actions</th>
                    </tr>
                </thead>
                <tbody id="ordersBody" class="divide-y divide-gray-100">
                    <?php if(empty($orders)): ?>
                        <tr><td colspan="9" class="px-4 py-8 text-center text-gray-500">You have no orders yet.</td></tr>
                    <?php else: foreach($orders as $ord): $tid=(int)$ord['transactions_id']; $items=$itemsByTxn[$tid]??[]; $status=strtolower($ord['deliveries_delivery_status']??''); $addressParts=array_filter([$ord['location_address_line1']??'', $ord['location_barangay']??'', $ord['location_city']??'', $ord['location_province']??'']); $address=implode(', ',$addressParts); $eta=$ord['deliveries_estimated_delivery_date']??''; $sig = $ord['deliveries_recipient_signature']??''; ?>
                        <tr data-status="<?php echo o_e($status); ?>" data-payment="<?php echo o_e(strtolower($ord['transactions_payment_method']??'')); ?>" data-address="<?php echo o_e(strtolower($address)); ?>" data-items="<?php echo o_e(strtolower(implode(' ', array_map(fn($x)=>$x['products_name'],$items)))); ?>">
                            <td class="px-4 py-3 align-top">
                                <div class="font-medium text-gray-800">#<?php echo $tid; ?></div>
                                <div class="text-xs md:text-sm text-gray-500">₱<?php echo number_format((float)$ord['transactions_amount'],2); ?></div>
                                <div class="text-[11px] md:text-xs text-gray-400"><?php echo o_e(date('Y-m-d H:i',strtotime($ord['transactions_created_at']))); ?></div>
                            </td>
                            <td class="px-4 py-3 align-top">
                                <ul class="space-y-1 max-w-[200px]">
                                <?php foreach($items as $it): ?>
                                    <li class="flex items-center gap-2 text-sm md:text-base">
                                        <span class="text-gray-700 truncate" title="<?php echo o_e($it['products_name']); ?>"><?php echo o_e($it['products_name']); ?></span>
                                        <span class="text-gray-400 text-xs md:text-sm">x<?php echo (int)$it['tp_quantity']; ?></span>
                                    </li>
                                <?php endforeach; ?>
                                </ul>
                            </td>
                            <td class="px-4 py-3 max-w-[260px] truncate" title="<?php echo o_e($address); ?>"><?php echo o_e($address); ?></td>
                            <td class="px-4 py-3"><span class="px-2 py-1 rounded-full bg-orange-50 text-orange-600 text-xs md:text-sm font-medium"><?php echo o_e(strtoupper($ord['transactions_payment_method']??'')); ?></span></td>
                            <td class="px-4 py-3"><?php if($status): ?><span class="px-2 py-1 rounded-full bg-gray-100 text-gray-700 text-xs md:text-sm font-medium"><?php echo o_e(ucwords(str_replace('_',' ',$status))); ?></span><?php endif; ?></td>
                            <td class="px-4 py-3 text-gray-700 text-sm md:text-base"><?php echo o_e($eta); ?></td>
                            <td class="px-4 py-3 text-gray-700 text-sm md:text-base"><?php echo o_e($ord['deliveries_actual_delivery_date'] ?? ''); ?></td>
                            <td class="px-4 py-3 text-gray-700 text-sm md:text-base">
                                <?php
                                if ($sig) {
                                    echo '<span class="text-emerald-600 font-semibold">Received</span>';
                                } elseif ($status === 'cancelled') {
                                    echo '<span class="text-red-600 font-semibold">Cancelled</span>';
                                } else {
                                    echo '<span class="text-gray-400">Pending</span>';
                                }
                                ?>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex flex-wrap items-center gap-2">
                                    <?php if(!$sig): ?>
                                        <?php if(in_array($status,['processing','pending'])): ?>
                                            <form method="post" target="_self" onsubmit="return confirm('Cancel this order?');" class="inline">
                                                <input type="hidden" name="action" value="cancel_order" />
                                                <input type="hidden" name="transactions_id" value="<?php echo $tid; ?>" />
                                                <button type="submit" class="px-2 py-1 rounded-md border text-gray-600 hover:bg-gray-50">Cancel</button>
                                            </form>
                                        <?php elseif($status==='out_for_delivery'): ?>
                                            <button type="button" disabled title="Already out for delivery – cancellation disabled" class="px-2 py-1 rounded-md border text-gray-400 bg-gray-50 cursor-not-allowed opacity-60">Cancel</button>
                                        <?php endif; ?>
                                        <?php if(in_array($status,['out_for_delivery','processing','pending','delivered']) && !$sig): ?>
                                            <form method="post" target="_self" onsubmit="return confirm('Mark as received?');" class="inline">
                                                <input type="hidden" name="action" value="mark_received" />
                                                <input type="hidden" name="transactions_id" value="<?php echo $tid; ?>" />
                                                <button type="submit" class="px-2 py-1 rounded-md border text-emerald-600 hover:bg-emerald-50">Received</button>
                                            </form>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="text-emerald-600 font-medium">Done</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
        <!-- Pagination -->
        <div id="ordersPagination" class="px-4 py-3 border-t border-gray-100 flex flex-col md:flex-row md:items-center md:justify-between gap-3 hidden">
            <div id="ordersPageInfo" class="text-sm text-gray-600"></div>
            <div class="flex items-center gap-2">
                <button id="ordersPrev" class="px-3 py-1.5 text-sm border rounded-md bg-white hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed">Prev</button>
                <div id="ordersPageNums" class="flex items-center gap-1"></div>
                <button id="ordersNext" class="px-3 py-1.5 text-sm border rounded-md bg-white hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed">Next</button>
            </div>
        </div>
    </div>
  </div>
</main>
<script>
    document.addEventListener('DOMContentLoaded', ()=>{ lucide.createIcons(); });
    const searchInput = document.getElementById('ordersSearch');
    const statusSel = document.getElementById('ordersStatusFilter');
    const paySel = document.getElementById('ordersPaymentFilter');
    const rows = Array.from(document.querySelectorAll('#ordersBody tr'));
    const pageSize = 10;
    let currentPage = 1;
    const pagWrap = document.getElementById('ordersPagination');
    const pageInfo = document.getElementById('ordersPageInfo');
    const prevBtn = document.getElementById('ordersPrev');
    const nextBtn = document.getElementById('ordersNext');
    const numsWrap = document.getElementById('ordersPageNums');

    function paginationRange(current,total,maxButtons){
        const range=[]; if(total<=maxButtons){ for(let i=1;i<=total;i++) range.push(i); return range; }
        const half=Math.floor(maxButtons/2); let start=Math.max(1,current-half); let end=start+maxButtons-1; if(end>total){end=total; start=end-maxButtons+1;}
        if(start>1){ range.push(1); if(start>2) range.push('...'); }
        for(let i=start;i<=end;i++) range.push(i);
        if(end<total){ if(end<total-1) range.push('...'); range.push(total); }
        return range;
    }

    function paginate(filtered){
        const total = filtered.length;
        const totalPages = Math.max(1, Math.ceil(total / pageSize));
        if(currentPage > totalPages) currentPage = totalPages;
        const start = (currentPage - 1) * pageSize;
        const end = start + pageSize;
        // hide all
        rows.forEach(r=>{ if(r.id !== 'noRowsMsg') r.style.display='none'; });
        filtered.slice(start,end).forEach(r=> r.style.display='');
        if(total === 0){ pageInfo.textContent='0 orders'; }
        else { pageInfo.textContent=`Showing ${start+1}-${Math.min(end,total)} of ${total} order${total>1?'s':''}`; }
        if(total > pageSize){ pagWrap.classList.remove('hidden'); } else { pagWrap.classList.add('hidden'); }
        prevBtn.disabled = currentPage === 1;
        nextBtn.disabled = currentPage === totalPages;
        numsWrap.innerHTML='';
        if(total > pageSize){
            paginationRange(currentPage,totalPages,5).forEach(p=>{
                if(p==='...'){
                    const span=document.createElement('span'); span.textContent='...'; span.className='px-2 text-gray-400'; numsWrap.appendChild(span);
                } else {
                    const b=document.createElement('button'); b.textContent=p; b.dataset.page=p; b.className='w-8 h-8 flex items-center justify-center rounded-md border text-sm hover:bg-gray-50 '+(p===currentPage?'bg-orange-500 text-white border-orange-500 hover:bg-orange-500':'bg-white'); numsWrap.appendChild(b);
                }
            });
        }
    }

    function applyFilters(){
        const q = (searchInput.value||'').trim().toLowerCase();
        const st = statusSel.value.toLowerCase();
        const pay = paySel.value.toLowerCase();
        const filtered=[];
        rows.forEach(r=>{
            if(r.id==='noRowsMsg') return;
            const rSt = r.getAttribute('data-status')||'';
            const rPay = r.getAttribute('data-payment')||'';
            const addr = r.getAttribute('data-address')||'';
            const items = r.getAttribute('data-items')||'';
            let ok = true;
            if(st && rSt!==st) ok=false;
            if(pay && rPay!==pay) ok=false;
            if(q && !(addr.includes(q) || items.includes(q) || rSt.includes(q) || rPay.includes(q))) ok=false;
            if(ok) filtered.push(r);
        });
        const body = document.getElementById('ordersBody');
        let emptyRow = document.getElementById('noRowsMsg');
        if(filtered.length===0){
            rows.forEach(r=>{ if(r.id!=='noRowsMsg') r.style.display='none'; });
            if(!emptyRow){ emptyRow=document.createElement('tr'); emptyRow.id='noRowsMsg'; emptyRow.innerHTML='<td colspan="9" class="px-4 py-6 text-center text-gray-500">No matching orders.</td>'; body.appendChild(emptyRow);} 
            emptyRow.style.display='';
            pagWrap.classList.add('hidden'); pageInfo.textContent='0 orders'; numsWrap.innerHTML=''; return;
        } else if(emptyRow){ emptyRow.remove(); }
        paginate(filtered);
    }

    document.addEventListener('click', e=>{
        const prev = e.target.closest('#ordersPrev');
        const next = e.target.closest('#ordersNext');
        const num = e.target.closest('#ordersPageNums button[data-page]');
        if(prev){ if(currentPage>1){ currentPage--; applyFilters(); } }
        if(next){ currentPage++; applyFilters(); }
        if(num){ const p=parseInt(num.dataset.page); if(!isNaN(p) && p!==currentPage){ currentPage=p; applyFilters(); } }
    });

    [searchInput,statusSel,paySel].forEach(el=> el.addEventListener('input',()=>{ currentPage=1; applyFilters(); }));
    applyFilters();
</script>
</body>
</html>
