<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once dirname(__DIR__, 2) . '/database.php';
require_once dirname(__DIR__, 2) . '/models/subscription.php';
require_once dirname(__DIR__, 2) . '/utils/session.php';

$user = get_current_user_session();
if (!$user) { header('Location: ../../login.php?redirect=views/users/subscription-success.php'); exit; }

// Accept details from query for immediate success view
$tx = isset($_GET['tx']) ? (int)$_GET['tx'] : 0;
$amount = isset($_GET['amount']) ? (float)$_GET['amount'] : 0.0;
$endAt = isset($_GET['end']) ? (string)$_GET['end'] : '';

// As a fallback, read the active subscription from DB if params missing
$active = subscription_get_active_for_user($connections, (int)$user['users_id']);
if (!$amount && $active && isset($active['subscriptions_price'])) $amount = (float)$active['subscriptions_price'];
if ($endAt === '' && $active && !empty($active['us_end_date'])) $endAt = (string)$active['us_end_date'];

function h($v){ return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Subscription Successful - Pawhabilin</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="../../globals.css" />
  <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
</head>
<body class="min-h-screen bg-gradient-to-br from-orange-50 via-amber-50 to-yellow-50">
  <?php $basePrefix = '../..'; include __DIR__ . '/../../utils/header-users.php'; ?>

  <main class="max-w-3xl mx-auto px-4 py-16">
    <div id="success-card" class="bg-white rounded-2xl border border-green-200 shadow p-8 text-center">
      <div class="w-20 h-20 bg-gradient-to-br from-green-500 to-emerald-600 rounded-2xl flex items-center justify-center mx-auto mb-6 shadow-inner">
        <i data-lucide="check" class="w-10 h-10 text-white"></i>
      </div>
      <h1 class="text-3xl font-extrabold text-gray-800 mb-3">Subscription Activated</h1>
      <p class="text-gray-600 mb-8">Thank you for upgrading to Premium. You now have access to all premium features.</p>

      <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-left">
        <div class="p-4 rounded-xl bg-green-50 border border-green-200">
          <div class="text-xs text-green-700 font-semibold mb-1">Plan</div>
          <div class="text-lg font-bold text-green-900">Premium</div>
        </div>
        <div class="p-4 rounded-xl bg-amber-50 border border-amber-200">
          <div class="text-xs text-amber-700 font-semibold mb-1">Amount</div>
          <div class="text-lg font-bold text-amber-900">₱<?php echo number_format($amount ?: 299, 2); ?></div>
        </div>
        <div class="p-4 rounded-xl bg-blue-50 border border-blue-200">
          <div class="text-xs text-blue-700 font-semibold mb-1">Renews On</div>
          <div class="text-lg font-bold text-blue-900"><?php echo $endAt ? h(date('M d, Y', strtotime($endAt))) : '—'; ?></div>
        </div>
      </div>

      <?php if ($tx): ?>
      <p class="mt-4 text-xs text-gray-500">Transaction ID: <span class="font-mono bg-gray-100 px-2 py-0.5 rounded">#<?php echo (int)$tx; ?></span></p>
      <?php endif; ?>

      <div class="mt-10 grid grid-cols-1 gap-6 text-left">
        <div class="rounded-2xl border border-gray-200 p-6">
          <div class="flex items-center justify-between mb-3">
            <h2 class="text-xl font-bold flex items-center gap-2"><i data-lucide="settings" class="w-5 h-5 text-gray-600"></i> Manage Plan</h2>
            <a href="subscriptions.php" class="text-orange-600 hover:underline text-sm">View details</a>
          </div>
          <p class="text-gray-600 mb-4">You can cancel your subscription anytime. Cancelling will end your premium access immediately.</p>
          <div class="flex flex-col sm:flex-row gap-3">
            <a href="buy_products.php" class="inline-flex items-center justify-center gap-2 px-5 py-3 rounded-lg bg-gradient-to-r from-orange-500 to-amber-600 text-white hover:from-orange-600 hover:to-amber-700 transition">
              <i data-lucide="shopping-bag" class="w-5 h-5"></i>
              Go to Shop
            </a>
            <button id="cancel-btn" class="inline-flex items-center justify-center gap-2 px-5 py-3 rounded-lg border border-red-200 text-red-600 bg-red-50 hover:bg-red-100 transition">
              <i data-lucide="x-circle" class="w-5 h-5"></i>
              Cancel Subscription
            </button>
          </div>
          <p id="cancel-msg" class="hidden mt-3 text-sm"></p>
        </div>
      </div>
    </div>
  </main>

  <script>
    function toast(message, type='success'){
      let c=document.getElementById('toast-container'); if(!c){ c=document.createElement('div'); c.id='toast-container'; c.className='fixed top-4 right-4 z-[11000] flex flex-col gap-2 items-end pointer-events-none'; document.body.appendChild(c); }
      const n=document.createElement('div'); n.className=`pointer-events-auto px-4 py-3 rounded-lg shadow text-sm ${type==='success'?'bg-green-600 text-white':type==='error'?'bg-red-600 text-white':'bg-blue-600 text-white'}`; n.textContent=message; c.appendChild(n); setTimeout(()=>{ n.style.opacity='0'; setTimeout(()=>n.remove(),250); }, 2200);
    }

    function formatDateNice(date){
      try{
        const d = (date instanceof Date) ? date : new Date(date);
        return d.toLocaleDateString('en-US', { month: 'short', day: '2-digit', year: 'numeric' });
      }catch{ return '—'; }
    }

    function updateToCancelled(){
      const card = document.getElementById('success-card');
      if(!card) return;
      const now = new Date();
      card.className = 'bg-white rounded-2xl border border-red-200 shadow p-8 text-center';
      card.innerHTML = `
        <div class="w-20 h-20 bg-gradient-to-br from-red-500 to-rose-600 rounded-2xl flex items-center justify-center mx-auto mb-6 shadow-inner">
          <i data-lucide="x" class="w-10 h-10 text-white"></i>
        </div>
        <h1 class="text-3xl font-extrabold text-gray-800 mb-3">Subscription Cancelled</h1>
        <p class="text-gray-600 mb-8">Your premium access has ended. You can re-subscribe anytime.</p>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-left">
          <div class="p-4 rounded-xl bg-gray-50 border border-gray-200">
            <div class="text-xs text-gray-700 font-semibold mb-1">Plan</div>
            <div class="text-lg font-bold text-gray-900">Premium</div>
          </div>
          <div class="p-4 rounded-xl bg-amber-50 border border-amber-200">
            <div class="text-xs text-amber-700 font-semibold mb-1">Amount</div>
            <div class="text-lg font-bold text-amber-900">—</div>
          </div>
          <div class="p-4 rounded-xl bg-red-50 border border-red-200">
            <div class="text-xs text-red-700 font-semibold mb-1">Ended On</div>
            <div class="text-lg font-bold text-red-900">${formatDateNice(now)}</div>
          </div>
        </div>

        <div class="mt-10 grid grid-cols-1 gap-6 text-left">
          <div class="rounded-2xl border border-gray-200 p-6">
            <div class="flex items-center justify-between mb-3">
              <h2 class="text-xl font-bold flex items-center gap-2"><i data-lucide="settings" class="w-5 h-5 text-gray-600"></i> Manage Plan</h2>
              <a href="subscriptions.php" class="text-orange-600 hover:underline text-sm">View plans</a>
            </div>
            <p class="text-gray-600 mb-4">Your subscription is cancelled. Explore plans to subscribe again or continue shopping.</p>
            <div class="flex flex-col sm:flex-row gap-3">
              <a href="subscriptions.php" class="inline-flex items-center justify-center gap-2 px-5 py-3 rounded-lg border border-orange-200 text-orange-700 bg-orange-50 hover:bg-orange-100 transition">
                <i data-lucide="crown" class="w-5 h-5"></i>
                View Plans
              </a>
              <a href="buy_products.php" class="inline-flex items-center justify-center gap-2 px-5 py-3 rounded-lg bg-gradient-to-r from-orange-500 to-amber-600 text-white hover:from-orange-600 hover:to-amber-700 transition">
                <i data-lucide="shopping-bag" class="w-5 h-5"></i>
                Go to Shop
              </a>
            </div>
          </div>
        </div>
      `;
      if(window.lucide) lucide.createIcons();
    }

    document.addEventListener('DOMContentLoaded',()=>{
      if(window.lucide) lucide.createIcons();
      const btn = document.getElementById('cancel-btn');
      const msg = document.getElementById('cancel-msg');
      if(btn){
        btn.addEventListener('click', async ()=>{
          if(!confirm('Cancel subscription now? This will end your premium access immediately.')) return;
          btn.disabled = true;
          const old = btn.innerHTML;
          btn.innerHTML = '<span class="inline-flex items-center gap-2"><span class="w-4 h-4 rounded-full border-2 border-red-600 border-t-transparent animate-spin"></span> Processing...</span>';
          if(msg){ msg.classList.add('hidden'); msg.textContent = ''; }
          try{
            const fd = new FormData(); fd.append('action','cancel');
            const res = await fetch('../../controllers/users/subscriptioncontroller.php', { method: 'POST', body: fd, headers: { 'Accept': 'application/json' } });
            const data = await res.json();
            if(!res.ok || !data.ok) throw new Error(data.error || 'Cancellation failed');
            updateToCancelled();
            toast('Subscription cancelled', 'success');
          } catch(e){
            if(msg){ msg.className = 'mt-3 text-sm text-red-600'; msg.textContent = e.message || 'Cancellation failed'; }
          } finally {
            btn.disabled = false;
            btn.innerHTML = old;
          }
        });
      }
    });
  </script>
</body>
</html>
