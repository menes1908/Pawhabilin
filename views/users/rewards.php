<?php
// Unified session + header pattern like other user pages
require_once __DIR__ . '/../../utils/session.php';
require_once __DIR__ . '/../../database.php';
session_start_if_needed();

// Existing session structure across app stores user info in $_SESSION['user'] (see get_current_user_session)
$__sessionUser = get_current_user_session();
if (!$__sessionUser) {
    // keep relative path consistent with other views (redirect back to root login)
    header('Location: ../../login.php');
    exit();
}

// Base user info (extend session structure with points + member since)
$user = [
    'name' => trim((($__sessionUser['users_firstname'] ?? '') . ' ' . ($__sessionUser['users_lastname'] ?? ''))) ?: ($__sessionUser['users_username'] ?? 'User'),
    'email' => $__sessionUser['users_email'] ?? '',
    'points' => 0,
    'member_since' => ''
];

// Helper: safely fetch single value
function db_single_val($conn,$sql){ if(!$conn) return null; if($res = mysqli_query($conn,$sql)){ $row = mysqli_fetch_row($res); mysqli_free_result($res); return $row? $row[0]: null; } return null; }

$uid = (int)($__sessionUser['users_id'] ?? 0);
if($uid>0 && isset($connections) && $connections){
    // Points balance table (user_points_balance) expected; fallback 0 if missing/not set
    $points = db_single_val($connections, "SELECT upb_points FROM user_points_balance WHERE users_id=".$uid." LIMIT 1");
    if($points === null){ $points = 0; }
    $user['points'] = (int)$points;
    // Member since: prefer earliest of user_subscriptions or users.created_at if present
    $since = db_single_val($connections, "SELECT MIN(us_start_date) FROM user_subscriptions WHERE users_id=".$uid);
    if(!$since){
        $since = db_single_val($connections, "SELECT users_created_at FROM users WHERE users_id=".$uid." LIMIT 1");
    }
    if($since){ $user['member_since'] = substr($since,0,10); }
}
if(!$user['member_since']){ $user['member_since'] = date('Y-m-d'); }

// (Removed legacy redeem code & mock arrays; promos are now dynamic via AJAX controller.)

function getStatusColor($status) {
    switch ($status) {
        case 'active': return 'bg-green-100 text-green-800 border-green-200';
        case 'used': return 'bg-gray-100 text-gray-800 border-gray-200';
        case 'redeemed': return 'bg-blue-100 text-blue-800 border-blue-200';
        case 'expired': return 'bg-red-100 text-red-800 border-red-200';
        default: return 'bg-gray-100 text-gray-800 border-gray-200';
    }
}

function getTypeIcon($type) {
    switch ($type) {
        case 'discount': return 'percent';
        case 'credit': return 'banknote';
        case 'freebie': return 'gift';
        case 'service': return 'stethoscope';
        case 'subscription': return 'crown';
        default: return 'gift';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Rewards - pawhabilin</title>
    
    <!-- Tailwind CSS v4.0 -->
        <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="../../globals.css">
    
    <!-- Google Fonts - La Belle Aurore -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=La+Belle+Aurore&display=swap" rel="stylesheet">
    
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    
    <style>
        /* Focus styles for accessibility */
        .focus-ring:focus {
            outline: 2px solid #f97316;
            outline-offset: 2px;
        }
        
        /* Reward card hover effects */
        .reward-card {
            transition: all 0.3s ease;
        }
        
        .reward-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }
        
        /* Points animation */
        .points-counter {
            background: linear-gradient(135deg, #f97316, #d97706);
            background-size: 200% 200%;
            animation: shimmer 3s ease-in-out infinite;
        }
        
        @keyframes shimmer {
            0%, 100% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
        }

        @keyframes pop-fade-in {
            0% { opacity:0; transform:scale(.85) translateY(20px); }
            60% { opacity:1; transform:scale(1.03) translateY(-4px); }
            100% { opacity:1; transform:scale(1) translateY(0); }
        }
        .animate-fade-in { animation: pop-fade-in .6s cubic-bezier(.16,.8,.3,1) both; }
        
        /* Code input styling */
        .code-input {
            font-family: 'Courier New', monospace;
            letter-spacing: 1px;
            text-transform: uppercase;
        }
        
        /* Mobile responsive */
        @media (max-width: 768px) {
            .mobile-padding {
                padding-left: 16px;
                padding-right: 16px;
            }
        }
    </style>
    <!-- Inject shared authenticated header (provides dropdown + assets) -->
    <?php $basePrefix = '../..'; include __DIR__ . '/../../utils/header-users.php'; ?>
</head>
<body class="min-h-screen bg-gradient-to-br from-orange-50 via-amber-50 to-yellow-50">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8 mobile-padding">
        <!-- Floating Center Notification Container -->
        <div id="pointsCenterNotify" class="pointer-events-none fixed top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 z-[200] hidden"></div>
        <!-- Page Header -->
        <div class="mb-8">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                <div>
                    <h1 class="text-3xl mb-2">My Rewards</h1>
                    <p class="text-gray-600">Redeem codes and claim exclusive rewards for your pets</p>
                </div>
                <div class="mt-4 md:mt-0">
                    <div class="points-counter text-white px-6 py-4 rounded-lg text-center" id="pointsBalanceCard">
                        <div class="text-sm opacity-90">PawPoints Balance</div>
                        <div class="text-2xl font-bold" id="pointsBalanceValue" data-points="<?php echo (int)$user['points']; ?>"><?php echo number_format($user['points']); ?></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- (Legacy success/error messages removed) -->

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8" id="rewardsGrid">
            <!-- Left Column: Available Rewards (dynamic promos) -->
            <div class="lg:col-span-2 space-y-8" id="availableRewardsColumn">
                <div class="bg-white border border-orange-200 rounded-lg p-6" id="availableRewardsPanel">
                    <div class="flex items-start justify-between mb-4">
                        <div>
                            <h2 class="text-xl flex items-center gap-2"><i data-lucide="shopping-bag" class="w-5 h-5 text-orange-600"></i><span>Available Rewards</span></h2>
                            <p class="text-gray-600 text-sm mt-1">Claim active promos using your PawPoints.</p>
                        </div>
                        <button id="refreshPromosBtn" class="text-xs px-3 py-1.5 rounded-md border border-gray-300 bg-white hover:bg-gray-100 flex items-center gap-1"><i data-lucide="refresh-cw" class="w-3 h-3"></i>Refresh</button>
                    </div>
                    <div id="availablePromosFeedback" class="hidden text-sm mb-4"></div>
                    <div id="availablePromosList" class="grid grid-cols-1 md:grid-cols-2 gap-4 min-h-[60px]">
                        <div class="col-span-full text-center text-gray-500 text-sm" id="promosLoading">Loading promos...</div>
                    </div>
                    <!-- Pagination for available promos -->
                    <div id="availablePromosPagination" class="mt-5 pt-4 border-t border-gray-200 hidden">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs text-gray-500">
                            <div id="availablePromosSummary" class="leading-snug">Showing 0</div>
                            <div class="flex items-center gap-2" id="availablePromosPagerControls">
                                <button id="promosPrev" class="px-2 py-1 rounded border border-gray-300 disabled:opacity-40 bg-white hover:bg-gray-100">Prev</button>
                                <div id="promosPageNums" class="flex items-center gap-1"></div>
                                <button id="promosNext" class="px-2 py-1 rounded border border-gray-300 disabled:opacity-40 bg-white hover:bg-gray-100">Next</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column: My Rewards -->
            <div class="space-y-6">
                <!-- Points Summary -->
                <div class="bg-white border border-orange-200 rounded-lg p-6">
                    <h3 class="text-lg mb-4 flex items-center">
                        <i data-lucide="star" class="w-5 h-5 mr-2 text-orange-600"></i>
                        Points Summary
                    </h3>
                    
                    <div class="space-y-3">
                        <div class="flex justify-between">
                            <span class="text-gray-600">Current Balance:</span>
                            <span class="font-medium text-orange-600" id="pointsSummaryValue" data-points="<?php echo (int)$user['points']; ?>"><?php echo number_format($user['points']); ?> points</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Member Since:</span>
                            <span class="text-gray-800"><?php echo date('M Y', strtotime($user['member_since'])); ?></span>
                        </div>
                        <div class="pt-3 border-t border-gray-200">
                            <p class="text-xs text-gray-500">
                                Earn points by booking services, shopping, and referring friends!
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Points Activity (Ledger) -->
                <div class="bg-white border border-orange-200 rounded-lg p-6" id="pointsActivityPanel">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-lg flex items-center gap-2"><i data-lucide="activity" class="w-5 h-5 text-orange-600"></i><span>Recent Points Activity</span></h3>
                        <button id="refreshLedgerBtn" class="text-xs px-3 py-1.5 rounded-md border border-gray-300 bg-white hover:bg-gray-100 flex items-center gap-1"><i data-lucide="refresh-cw" class="w-3 h-3"></i>Refresh</button>
                    </div>
                    <div id="pointsLedgerList" class="space-y-2 max-h-60 overflow-y-auto text-sm">
                        <div class="text-center text-gray-400 text-xs py-4" id="ledgerLoading">Loading...</div>
                    </div>
                </div>

                <!-- My Claimed Rewards -->
                <div class="bg-white border border-orange-200 rounded-lg p-6" id="claimedRewardsPanel">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg flex items-center gap-2"><i data-lucide="ticket" class="w-5 h-5 text-orange-600"></i><span>My Claimed Coupons</span></h3>
                        <button id="refreshClaimedBtn" class="text-xs px-3 py-1.5 rounded-md border border-gray-300 bg-white hover:bg-gray-100 flex items-center gap-1"><i data-lucide="refresh-cw" class="w-3 h-3"></i>Refresh</button>
                    </div>
                    <div id="claimedFeedback" class="hidden text-sm mb-3"></div>
                    <div id="claimedRewardsList" class="space-y-3 max-h-96 overflow-y-auto">
                        <div class="text-center text-gray-500 text-sm" id="claimedLoading">Loading claimed rewards...</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- How to Earn Points -->
        <div class="mt-12 bg-white border border-orange-200 rounded-lg p-6">
            <h2 class="text-xl mb-4 flex items-center">
                <i data-lucide="help-circle" class="w-5 h-5 mr-2 text-orange-600"></i>
                How to Earn PawPoints
            </h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="text-center p-4 bg-gradient-to-br from-blue-50 to-indigo-50 rounded-lg">
                    <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-lg flex items-center justify-center mx-auto mb-3">
                        <i data-lucide="calendar" class="w-6 h-6 text-white"></i>
                    </div>
                    <h3 class="font-medium mb-2">Book Services</h3>
                    <p class="text-sm text-gray-600">Earn 30 points for every ₱100 spent on pet sitting and grooming services.</p>
                </div>
                
                <div class="text-center p-4 bg-gradient-to-br from-green-50 to-emerald-50 rounded-lg">
                    <div class="w-12 h-12 bg-gradient-to-br from-green-500 to-emerald-600 rounded-lg flex items-center justify-center mx-auto mb-3">
                        <i data-lucide="shopping-cart" class="w-6 h-6 text-white"></i>
                    </div>
                    <h3 class="font-medium mb-2">Shop Products</h3>
                    <p class="text-sm text-gray-600">Get 15 points for every ₱100 spent on pet supplies and accessories.</p>
                </div>
                
                <div class="text-center p-4 bg-gradient-to-br from-purple-50 to-violet-50 rounded-lg">
                    <div class="w-12 h-12 bg-gradient-to-br from-purple-500 to-violet-600 rounded-lg flex items-center justify-center mx-auto mb-3">
                        <i data-lucide="users" class="w-6 h-6 text-white"></i>
                    </div>
                    <h3 class="font-medium mb-2">Refer Friends</h3>
                    <p class="text-sm text-gray-600">Earn 500 points when a friend signs up and completes their first booking.</p>
                </div>
                
                <div class="text-center p-4 bg-gradient-to-br from-orange-50 to-amber-50 rounded-lg">
                    <div class="w-12 h-12 bg-gradient-to-br from-orange-500 to-amber-600 rounded-lg flex items-center justify-center mx-auto mb-3">
                        <i data-lucide="star" class="w-6 h-6 text-white"></i>
                    </div>
                    <h3 class="font-medium mb-2">Leave Reviews</h3>
                    <p class="text-sm text-gray-600">Get 50 points for every detailed review you leave for our sitters.</p>
                </div>
            </div>
        </div>
    </div>

    <script>
        lucide.createIcons();
        const promosEndpoint = '../../controllers/users/userpromoscontroller.php';
        const availableList = document.getElementById('availablePromosList');
        const claimedList = document.getElementById('claimedRewardsList');
        const availFb = document.getElementById('availablePromosFeedback');
        const claimedFb = document.getElementById('claimedFeedback');
        const promosLoading = document.getElementById('promosLoading');
        const claimedLoading = document.getElementById('claimedLoading');
        const refreshPromosBtn = document.getElementById('refreshPromosBtn');
        const refreshClaimedBtn = document.getElementById('refreshClaimedBtn');

    function fb(el,msg,type='error'){ if(!el) return; el.textContent=msg; el.className='block rounded-md px-3 py-2 text-sm '+(type==='success'?'bg-green-50 border border-green-200 text-green-700':'bg-red-50 border border-red-200 text-red-700'); }
        function hideFb(el){ if(!el) return; el.classList.add('hidden'); el.textContent=''; }
        function pointsBadge(cost){ if(!cost) return '<span class="text-xs text-gray-400">Free</span>'; return `<span class="inline-flex items-center gap-1 text-xs font-medium text-orange-600"><i data-lucide="star" class="w-3 h-3"></i>${cost} pts</span>`; }
        function discountLabel(p){
            const t = p.promo_discount_type||'none';
            if(t==='percent' && p.promo_discount_value) return p.promo_discount_value+'% off';
            if(t==='fixed' && p.promo_discount_value) return '₱'+parseFloat(p.promo_discount_value).toFixed(2)+' off';
            if(t==='points_bonus' && p.promo_discount_value) return '+'+parseInt(p.promo_discount_value)+' pts';
            if(t==='free_item') return 'Free Item';
            return 'Special Offer';
        }
        function windowLabel(p){
            const s = p.promo_starts_at? p.promo_starts_at.substring(0,10):''; const e = p.promo_ends_at? p.promo_ends_at.substring(0,10):'';
            if(!s && !e) return 'No expiry';
            return `${s||'Now'} → ${e||'∞'}`;
        }
        // Pagination state for available promos
        let availablePromos = [];
        let promosPage = 1;
        const PROMOS_PAGE_SIZE = 10;
        let currentUserPts = 0;

        async function loadAvailable(){
            hideFb(availFb); promosLoading && (promosLoading.style.display='block');
            availableList.innerHTML=''; availableList.appendChild(promosLoading);
            try {
                const r = await fetch(promosEndpoint+'?action=list',{credentials:'same-origin'}); const j = await r.json();
                promosLoading.style.display='none';
                if(!j.success){ fb(availFb,j.message||'Failed'); return; }
                availablePromos = j.promotions||[]; 
                // Reorder: unclaimed promos first, claimed promos (where up_id present) pushed to bottom.
                availablePromos.sort((a,b)=>{
                    const aClaimed = a.up_id != null;
                    const bClaimed = b.up_id != null;
                    if(aClaimed !== bClaimed) return aClaimed - bClaimed; // false (0) before true (1)
                    // Secondary sort: earlier end date first to surface expiring promos
                    const aEnd = a.promo_ends_at || '';
                    const bEnd = b.promo_ends_at || '';
                    if(aEnd && bEnd && aEnd !== bEnd) return aEnd.localeCompare(bEnd);
                    // Tertiary: lower points cost first (easier claims)
                    const aCost = parseInt(a.promo_points_cost||0,10);
                    const bCost = parseInt(b.promo_points_cost||0,10);
                    if(aCost !== bCost) return aCost - bCost;
                    // Finally alphabetical by name for stability
                    return (a.promo_name||'').localeCompare(b.promo_name||'');
                });
                currentUserPts = j.user_points||0; updatePointsDisplay(currentUserPts); promosPage = 1; renderPromosPage();
            } catch(e){ promosLoading.style.display='none'; fb(availFb,'Network error'); }
        }
        function renderPromosPage(){
            const pagerWrap = document.getElementById('availablePromosPagination');
            const summaryEl = document.getElementById('availablePromosSummary');
            const numsWrap = document.getElementById('promosPageNums');
            const prevBtn = document.getElementById('promosPrev');
            const nextBtn = document.getElementById('promosNext');
            availableList.innerHTML='';
            const total = availablePromos.length;
            if(total===0){
                availableList.innerHTML='<div class="col-span-full text-center text-gray-500 text-sm">No active promos right now.</div>';
                pagerWrap.classList.add('hidden');
                return;
            }
            const pages = Math.max(1, Math.ceil(total / PROMOS_PAGE_SIZE));
            if(promosPage>pages) promosPage = pages;
            const start = (promosPage-1)*PROMOS_PAGE_SIZE;
            const slice = availablePromos.slice(start, start + PROMOS_PAGE_SIZE);
            slice.forEach(p=>{
                const disabled = p.promo_points_cost && currentUserPts < p.promo_points_cost;
                const claimed = p.up_id != null;
                const card = document.createElement('div');
                card.className='reward-card border border-gray-200 rounded-lg p-4 hover:border-orange-200 flex flex-col gap-3';
                card.innerHTML=`<div class='flex items-start justify-between'>
                    <div>
                      <div class='text-sm font-semibold text-gray-800 line-clamp-1'>${escapeHtml(p.promo_name||'')}</div>
                      <div class='text-[11px] text-gray-500 mt-0.5'>${discountLabel(p)}</div>
                    </div>
                    <div class='text-right space-y-1'>${pointsBadge(p.promo_points_cost)}<div class='text-[10px] text-gray-400'>${windowLabel(p)}</div></div>
                </div>
                <div class='text-xs text-gray-600 min-h-[28px]'>${escapeHtml(p.promo_description||'')}</div>
                <div class='mt-auto'>${ claimed ? `<div class='text-[11px] text-green-600 font-medium flex items-center gap-1'><i data-lucide="check" class="w-3 h-3"></i>Claimed</div>` : `<button data-claim='${p.promo_id}' class='w-full px-3 py-2 rounded-md text-sm font-medium ${disabled?"bg-gray-300 text-gray-600 cursor-not-allowed":"bg-orange-500 hover:bg-orange-600 text-white"}'>${disabled? 'Not enough points':'Claim'}</button>`}</div>`;
                availableList.appendChild(card);
            });
            lucide.createIcons();
            if(summaryEl){ summaryEl.textContent = `Showing ${start+1}-${start+slice.length} of ${total}`; }
            if(total > PROMOS_PAGE_SIZE){
                pagerWrap.classList.remove('hidden');
                numsWrap.innerHTML='';
                const range = promoPaginationRange(promosPage, pages, 5);
                range.forEach(r=>{
                    const b = document.createElement('button');
                    b.className = 'px-2 py-1 rounded border text-[11px] '+(r===promosPage?'bg-orange-600 border-orange-600 text-white':'bg-white border-gray-300 hover:bg-gray-100');
                    b.textContent = r==='...' ? '...' : r;
                    if(r!=='...') b.addEventListener('click', ()=>{ promosPage = r; renderPromosPage(); });
                    numsWrap.appendChild(b);
                });
                prevBtn.disabled = promosPage<=1; nextBtn.disabled = promosPage>=pages;
            } else { pagerWrap.classList.add('hidden'); }
        }
        function promoPaginationRange(current,total,maxButtons){
            const out=[]; if(total<=maxButtons){ for(let i=1;i<=total;i++) out.push(i); return out; }
            const half=Math.floor(maxButtons/2); let start=Math.max(1,current-half); let end=start+maxButtons-1; if(end>total){ end=total; start=end-maxButtons+1; }
            if(start>1){ out.push(1); if(start>2) out.push('...'); }
            for(let i=start;i<=end;i++) out.push(i);
            if(end<total){ if(end<total-1) out.push('...'); out.push(total); }
            return out;
        }
        async function loadClaimed(){
            hideFb(claimedFb); claimedLoading && (claimedLoading.style.display='block');
            claimedList.innerHTML=''; claimedList.appendChild(claimedLoading);
            try {
                const r = await fetch(promosEndpoint+'?action=claimed',{credentials:'same-origin'}); const j = await r.json();
                claimedLoading.style.display='none';
                if(!j.success){ fb(claimedFb,j.message||'Failed'); return; }
                const rows = j.claimed||[];
                if(rows.length===0){ claimedList.innerHTML='<div class="text-center text-gray-500 text-sm">No claimed coupons yet.</div>'; return; }
                rows.forEach(c=>{
                    const div = document.createElement('div');
                    div.className='border border-gray-200 rounded-lg p-4';
                    div.innerHTML = `<div class='flex items-start justify-between mb-2'>
                        <div class='min-w-0 flex-1'>
                            <div class='font-medium text-sm truncate'>${escapeHtml(c.promo_name||'')}</div>
                            <div class='text-[11px] text-gray-500 mt-0.5'>Code: <code class='bg-gray-100 px-1 rounded'>${escapeHtml(c.up_code||'')}</code></div>
                        </div>
                        <button data-qr='${c.up_id}' class='px-2 py-1 text-[11px] rounded-md border border-gray-300 hover:border-orange-400 hover:text-orange-600 flex items-center gap-1'><i data-lucide="qr-code" class="w-3 h-3"></i>QR</button>
                    </div>
                    <div class='flex justify-between items-center text-[11px] text-gray-500'><span>Claimed: ${escapeHtml((c.up_claimed_at||'').substring(0,10))}</span><span>${c.up_redeemed_at? 'Redeemed':'Not redeemed'}</span></div>`;
                    claimedList.appendChild(div);
                });
                lucide.createIcons();
            } catch(e){ claimedLoading.style.display='none'; fb(claimedFb,'Network error'); }
        }
        function escapeHtml(str){ return (str||'').replace(/[&<>"']/g,c=>({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;','\'':'&#39;' }[c]||c)); }
        function updatePointsDisplay(val){
            const balance = document.getElementById('pointsBalanceValue');
            const summary = document.getElementById('pointsSummaryValue');
            if(balance){ balance.dataset.points = val; balance.textContent = new Intl.NumberFormat().format(val); }
            if(summary){ summary.dataset.points = val; summary.textContent = new Intl.NumberFormat().format(val)+' points'; }
        }

        // Fancy center notification for points gain
        function showPointsGain(points){
            const wrap = document.getElementById('pointsCenterNotify');
            if(!wrap) return; wrap.innerHTML='';
            const card = document.createElement('div');
            card.className='pointer-events-auto bg-white/90 backdrop-blur-xl border border-orange-300 shadow-[0_0_0_4px_rgba(255,255,255,0.4),0_20px_40px_-10px_rgba(249,115,22,0.4)] rounded-2xl px-10 py-8 flex flex-col items-center gap-4 animate-fade-in relative overflow-hidden';
            card.innerHTML = `
                <div class='absolute inset-0 opacity-20 bg-[radial-gradient(circle_at_30%_20%,#f97316,transparent_60%),radial-gradient(circle_at_70%_80%,#fb923c,transparent_60%)]'></div>
                <div class='relative z-10 flex flex-col items-center gap-3'>
                    <div class='w-20 h-20 rounded-full bg-gradient-to-br from-orange-500 to-amber-500 flex items-center justify-center text-white shadow-lg animate-pulse'>
                        <i data-lucide="star" class="w-10 h-10"></i>
                    </div>
                    <div class='text-center'>
                        <p class='text-xs tracking-widest font-medium text-orange-600 mb-1'>PAWPOINTS AWARDED</p>
                        <h3 class='text-5xl font-extrabold bg-gradient-to-br from-orange-600 to-amber-600 bg-clip-text text-transparent drop-shadow-sm'>+${points}</h3>
                        <p class='mt-2 text-sm text-gray-600 max-w-xs'>Thanks for completing your appointment! Keep caring for your pets to earn more rewards.</p>
                    </div>
                    <button id='pointsNotifyClose' class='mt-2 px-5 py-2.5 rounded-full bg-gradient-to-r from-orange-500 to-amber-500 text-white text-sm font-medium shadow hover:shadow-lg transition focus:outline-none focus:ring-4 focus:ring-orange-300'>Awesome!</button>
                </div>`;
            wrap.appendChild(card);
            wrap.classList.remove('hidden');
            lucide.createIcons();
            function hide(){ card.classList.add('opacity-0','scale-95','transition','duration-300'); setTimeout(()=>{ wrap.classList.add('hidden'); wrap.innerHTML=''; },300); }
            document.getElementById('pointsNotifyClose')?.addEventListener('click', hide);
            setTimeout(hide, 8000);
        }

        // Polling for updated points (every 10s) to reflect awards from other actions (e.g., completed appointment)
        let lastPoints = parseInt(document.getElementById('pointsBalanceValue')?.dataset.points || '0',10);
        async function pollPoints(){
            try {
                const r = await fetch(promosEndpoint+'?action=points',{credentials:'same-origin'});
                const j = await r.json();
                if(j.success && typeof j.points === 'number'){
                    if(j.points > lastPoints){
                        showPointsGain(j.points - lastPoints);
                        updatePointsDisplay(j.points);
                    }
                    lastPoints = j.points;
                }
            } catch(e){}
        }
        setInterval(pollPoints, 10000);

        // Points ledger loader
        async function loadLedger(){
            const list = document.getElementById('pointsLedgerList');
            const loading = document.getElementById('ledgerLoading');
            if(!list) return; if(loading) loading.style.display='block'; list.querySelectorAll('.ledger-row').forEach(r=>r.remove());
            try {
                const r = await fetch(promosEndpoint+'?action=ledger',{credentials:'same-origin'}); const j = await r.json();
                if(!j.success){ if(loading){ loading.textContent='Failed to load'; } return; }
                if(loading) loading.style.display='none';
                const entries = j.entries||[]; if(entries.length===0){ if(loading){ loading.style.display='block'; loading.textContent='No activity yet.'; } return; }
                entries.forEach(en=>{
                    const row = document.createElement('div');
                    row.className='ledger-row flex items-center justify-between px-3 py-2 rounded-md border bg-orange-50/40 border-orange-100 hover:bg-orange-50 transition';
                    const pts = en.upl_points>=0? '+'+en.upl_points: en.upl_points;
                    row.innerHTML = `<div class='flex flex-col'><span class='font-medium text-gray-700'>${escapeHtml(en.upl_reason||'Points')}</span><span class='text-[11px] text-gray-400 tracking-wide'>${new Date(en.upl_created_at.replace(' ','T')).toLocaleString()}</span></div><div class='text-right font-semibold ${en.upl_points>=0?'text-orange-600':'text-red-600'}'>${pts}</div>`;
                    list.appendChild(row);
                });
            } catch(e){ if(loading){ loading.style.display='block'; loading.textContent='Network error'; } }
        }
        document.getElementById('refreshLedgerBtn')?.addEventListener('click', loadLedger);
        loadLedger();

        document.addEventListener('click', async (e)=>{
            const claimBtn = e.target.closest('[data-claim]');
            if(claimBtn){
                const id = claimBtn.getAttribute('data-claim');
                claimBtn.disabled = true; claimBtn.textContent='Claiming...';
                const fd = new FormData(); fd.append('action','claim'); fd.append('promo_id',id);
                try { const r = await fetch(promosEndpoint,{method:'POST',body:fd,credentials:'same-origin'}); const j=await r.json(); if(!j.success){ alert(j.message||'Claim failed'); } else { if(typeof j.new_points==='number'){ updatePointsDisplay(j.new_points); lastPoints=j.new_points; } await loadAvailable(); await loadClaimed(); } } catch(err){ alert('Network error'); }
            }
            const qrBtn = e.target.closest('[data-qr]');
            if(qrBtn){
                const up = qrBtn.getAttribute('data-qr'); qrBtn.disabled=true; qrBtn.textContent='...';
                try { const r = await fetch(promosEndpoint+'?action=qr&up_id='+encodeURIComponent(up),{credentials:'same-origin'}); const j=await r.json(); if(!j.success){ alert(j.message||'QR failed'); } else { showQrModal(j); } } catch(err){ alert('Network error'); } finally { qrBtn.disabled=false; qrBtn.textContent='QR'; }
            }
            if(e.target.id==='qrClose' || e.target.id==='qrDownload'){
                if(e.target.id==='qrDownload'){ const svg = document.querySelector('#qrModal svg'); if(svg){ const blob = new Blob([svg.outerHTML],{type:'image/svg+xml'}); const a=document.createElement('a'); a.href=URL.createObjectURL(blob); a.download='promo-qr.svg'; document.body.appendChild(a); a.click(); setTimeout(()=>URL.revokeObjectURL(a.href),500); a.remove(); } }
                document.getElementById('qrModal').remove();
            }
        });

        function showQrModal(payload){
            const existing = document.getElementById('qrModal'); if(existing) existing.remove();
            const wrap = document.createElement('div');
            wrap.id='qrModal';
            wrap.className='fixed inset-0 bg-black/50 flex items-center justify-center z-50';
            wrap.innerHTML = `<div class='bg-white rounded-lg p-6 w-full max-w-sm relative'>
                <button id='qrClose' class='absolute top-2 right-2 text-gray-400 hover:text-gray-600'>&times;</button>
                <h4 class='text-lg font-semibold mb-4 flex items-center gap-2'><i data-lucide="qr-code" class="w-5 h-5 text-orange-600"></i><span>${escapeHtml(payload.promo_name||'Coupon')}</span></h4>
                <div class='flex items-center justify-center mb-4 bg-gray-50 border border-gray-200 rounded-md p-4'>${payload.svg}</div>
                <div class='text-center text-sm mb-4'><span class='font-mono bg-gray-100 px-2 py-1 rounded'>${escapeHtml(payload.code||'')}</span></div>
                <button id='qrDownload' class='w-full bg-orange-500 hover:bg-orange-600 text-white py-2 rounded-md text-sm font-medium'>Download QR</button>
            </div>`;
            document.body.appendChild(wrap); lucide.createIcons();
        }

        // Unified refresh: refreshes available promos + claimed coupons (and optional ledger)
        async function refreshAllRewards(){
            if(!refreshPromosBtn) return; 
            const original = refreshPromosBtn.innerHTML;
            refreshPromosBtn.disabled = true;
            refreshPromosBtn.innerHTML = '<i data-lucide="refresh-cw" class="w-3 h-3 animate-spin"></i>Refreshing';
            try {
                await Promise.all([loadAvailable(), loadClaimed()]);
                // Optionally also refresh ledger points summary so new points costs reflect (uncomment if desired)
                // await loadLedger();
            } finally {
                refreshPromosBtn.disabled = false;
                refreshPromosBtn.innerHTML = original;
                lucide.createIcons();
            }
        }
        refreshPromosBtn?.addEventListener('click', refreshAllRewards);
        document.addEventListener('click', (e)=>{
            if(e.target.id==='promosPrev'){ if(promosPage>1){ promosPage--; renderPromosPage(); } }
            if(e.target.id==='promosNext'){ promosPage++; renderPromosPage(); }
        });
        refreshClaimedBtn?.addEventListener('click', ()=>{ loadClaimed(); });
        // Initial load
        loadAvailable(); loadClaimed();
    </script>
</body>
</html>