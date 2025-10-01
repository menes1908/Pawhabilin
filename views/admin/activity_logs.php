<?php
// Visual Audit Log feed (no table) with filters, search, and pagination
// Uses admin_activity_logs created via utils/helper.php
session_start();
require_once __DIR__ . '/../../utils/helper.php';

function qstr($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Audit Logs - Pawhabilin Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=La+Belle+Aurore&display=swap" rel="stylesheet">
    <link href="../../globals.css" rel="stylesheet">
</head>
<body class="bg-gray-50">
    <div class="min-h-screen">
        <!-- Header -->
        <header class="bg-white border-b border-gray-200 sticky top-0 z-30">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg overflow-hidden">
                        <img src="<?php echo qstr('../../pictures/Pawhabilin logo.png'); ?>" alt="Pawhabilin" class="w-full h-full object-cover" />
                    </div>
                    <div>
                        <h1 class="text-xl font-semibold text-gray-900">Audit Logs</h1>
                        <p class="text-xs text-gray-500">Last refreshed: <?php echo qstr(date('M d, Y h:i A')); ?></p>
                    </div>
                </div>
                <div class="flex gap-2">
                    <a href="admin.php" class="inline-flex items-center gap-2 px-3 py-2 rounded-lg border border-gray-200 text-gray-700 hover:bg-gray-50">
                        <i data-lucide="arrow-left" class="w-4 h-4"></i>
                        <span class="hidden sm:inline">Back to Dashboard</span>
                    </a>
                </div>
            </div>
        </header>

        <!-- Content -->
        <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <!-- Filters & Search -->
            <div class="bg-white rounded-xl border border-gray-200 p-4 mb-4">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                    <div id="tabsWrap" class="flex flex-wrap items-center gap-2"></div>
                    <div class="flex items-center gap-2">
                        <input id="from" type="date" class="px-3 py-2 rounded-lg border border-gray-300">
                        <span class="text-xs text-gray-500">to</span>
                        <input id="to" type="date" class="px-3 py-2 rounded-lg border border-gray-300">
                        <input id="q" type="text" placeholder="Search by user, action, product..." class="w-64 px-3 py-2 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-orange-400" />
                        <button id="searchBtn" class="inline-flex items-center gap-2 px-3 py-2 rounded-lg bg-orange-600 text-white hover:bg-orange-700">
                            <i data-lucide="search" class="w-4 h-4"></i>
                            <span>Search</span>
                        </button>
                        <button id="resetBtn" class="inline-flex items-center gap-2 px-3 py-2 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50">
                            <i data-lucide="rotate-ccw" class="w-4 h-4"></i>
                            <span>Reset</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Stat Cards -->
            <div id="statsWrap" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-7 gap-3 mb-4"></div>

            <!-- Summary -->
            <div class="flex items-center justify-between text-sm text-gray-600 mb-3">
                <div>Showing <span class="font-medium" id="showing">0</span> of <span class="font-medium" id="total">0</span> activities</div>
                <div class="flex items-center gap-2">
                    <button id="prev" class="px-3 py-1.5 rounded-lg border border-gray-200 hover:bg-gray-50">Previous</button>
                    <span id="pageInfo">Page 1 / 1</span>
                    <button id="next" class="px-3 py-1.5 rounded-lg border border-gray-200 hover:bg-gray-50">Next</button>
                </div>
            </div>

            <!-- Activity Feed (no table) -->
            <div id="feed" class="space-y-3"></div>
        </main>
    </div>

    <script>
        const state = { tab: 'all', q: '', page: 1, per: 15, from: '', to: '' };
        function statDefs(){
            return [
                {k:'all', label:'All', icon:'activity', ring:'ring-orange-200', pill:'bg-orange-50 text-orange-700 border-orange-200'},
                {k:'additions', label:'Additions', icon:'plus', ring:'ring-green-200', pill:'bg-green-50 text-green-700 border-green-200'},
                {k:'updates', label:'Updates', icon:'pencil', ring:'ring-indigo-200', pill:'bg-indigo-50 text-indigo-700 border-indigo-200'},
                {k:'price_changes', label:'Price', icon:'tag', ring:'ring-amber-200', pill:'bg-amber-50 text-amber-700 border-amber-200'},
                {k:'stock_changes', label:'Stock', icon:'package', ring:'ring-sky-200', pill:'bg-sky-50 text-sky-700 border-sky-200'},
                {k:'sitters', label:'Sitters', icon:'user', ring:'ring-pink-200', pill:'bg-pink-50 text-pink-700 border-pink-200'},
                {k:'orders', label:'Orders', icon:'shopping-bag', ring:'ring-violet-200', pill:'bg-violet-50 text-violet-700 border-violet-200'}
            ];
        }
        function tabs(){
            return [
                {k:'all', label:'All'},
                {k:'additions', label:'Additions'},
                {k:'updates', label:'Updates'},
                {k:'price_changes', label:'Price'},
                {k:'stock_changes', label:'Stock'},
                {k:'sitters', label:'Sitters'},
                {k:'orders', label:'Orders'}
            ];
        }
        function styleTabs(){
            const wrap = document.getElementById('tabsWrap');
            wrap.querySelectorAll('button').forEach(b=>{
                const active = b.dataset.tab === state.tab;
                b.className = 'px-3 py-1.5 rounded-full border text-sm ' + (active? 'bg-orange-50 border-orange-300 text-orange-700' : 'bg-white border-gray-200 text-gray-700 hover:bg-gray-50');
            });
        }
        function renderStats(stats){
            const wrap = document.getElementById('statsWrap');
            if(!wrap) return;
            const defs = statDefs();
            wrap.innerHTML = defs.map(d => {
                const count = (stats && typeof stats[d.k] !== 'undefined') ? stats[d.k] : 0;
                const active = state.tab === d.k;
                const ringClass = active ? `ring-2 ${d.ring}` : 'ring-1 ring-gray-100';
                return `
                <button data-stat="${d.k}" class="text-left bg-white border border-gray-200 rounded-xl p-3 hover:shadow-sm transition ${ringClass}">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="inline-flex items-center justify-center w-8 h-8 rounded-lg border ${d.pill}">
                                <i data-lucide="${d.icon}" class="w-4 h-4"></i>
                            </span>
                            <span class="text-sm text-gray-600">${d.label}</span>
                        </div>
                        <span class="text-lg font-semibold text-gray-900">${count}</span>
                    </div>
                </button>`;
            }).join('');
            // Click handlers
            wrap.querySelectorAll('button[data-stat]').forEach(btn => {
                btn.addEventListener('click', () => {
                    const key = btn.getAttribute('data-stat');
                    state.tab = key;
                    state.page = 1;
                    styleTabs();
                    refresh();
                });
            });
            if(window.lucide) window.lucide.createIcons();
        }
        function initUI(){
            const wrap = document.getElementById('tabsWrap');
            wrap.innerHTML='';
            tabs().forEach(t=>{
                const b=document.createElement('button'); b.textContent=t.label; b.dataset.tab=t.k; b.className='px-3 py-1.5 rounded-full border text-sm';
                b.addEventListener('click', ()=>{ state.tab=t.k; state.page=1; styleTabs(); refresh(); });
                wrap.appendChild(b);
            });
            styleTabs();
            const q=document.getElementById('q'); const s=document.getElementById('searchBtn');
            let h; q.addEventListener('input', ()=>{ clearTimeout(h); h=setTimeout(()=>{ state.q=q.value.trim(); state.page=1; refresh(); },200); });
            s.addEventListener('click', ()=>{ state.q=q.value.trim(); state.page=1; refresh(); });
            const f=document.getElementById('from'); const to=document.getElementById('to');
            f.addEventListener('change', ()=>{ state.from=f.value; state.page=1; refresh(); });
            to.addEventListener('change', ()=>{ state.to=to.value; state.page=1; refresh(); });
            document.getElementById('resetBtn').addEventListener('click', ()=>{ state.tab='all'; state.q=''; state.from=''; state.to=''; state.page=1; q.value=''; f.value=''; to.value=''; styleTabs(); refresh(); });
            document.getElementById('prev').addEventListener('click', ()=>{ if(state.page>1){ state.page--; refresh(); }});
            document.getElementById('next').addEventListener('click', ()=>{ const pages = Math.max(1, Math.ceil((window.__lastTotal||0)/state.per)); if(state.page<pages){ state.page++; refresh(); }});
        }
        function esc(s){ const d=document.createElement('div'); d.textContent=s==null? '' : String(s); return d.innerHTML; }
        function summarize(prev, next){ if(!prev && !next) return []; const p=prev||{}; const n=next||{}; const keys=[...new Set([...Object.keys(p),...Object.keys(n)])]; const out=[]; for(const k of keys){ const pv = typeof p[k]==='object'? JSON.stringify(p[k]) : (p[k]??''); const nv = typeof n[k]==='object'? JSON.stringify(n[k]) : (n[k]??''); if(String(pv)!==String(nv)) out.push({key:k, prev:String(pv), next:String(nv)}); if(out.length>=8) break; } return out; }
        function badgeClass(t){ switch(t){ case 'additions': return 'bg-green-100 text-green-800 border border-green-200'; case 'updates': return 'bg-indigo-100 text-indigo-800 border border-indigo-200'; case 'price_changes': return 'bg-amber-100 text-amber-800 border border-amber-200'; case 'stock_changes': return 'bg-sky-100 text-sky-800 border border-sky-200'; case 'auth_login': return 'bg-emerald-100 text-emerald-800 border border-emerald-200'; case 'auth_logout': return 'bg-stone-100 text-stone-800 border border-stone-200'; case 'auth_login_failed': return 'bg-red-100 text-red-800 border border-red-200'; default: return 'bg-gray-100 text-gray-800 border border-gray-200'; } }
        function render(items){
            const feed=document.getElementById('feed'); feed.innerHTML='';
            if(!items || items.length===0){ feed.innerHTML='<div class="bg-white border border-gray-200 rounded-xl p-6 text-center text-gray-500">No activities found.</div>'; return; }
            items.forEach(row=>{
                const ts = row.timestamp||''; const user = row.user_email || (row.users_id? `User #${row.users_id}` : 'Unknown user'); const ip=row.ip_address||''; const action=row.action_type||'updates';
                const target=[row.target||'', row.target_id||''].join(' ').trim(); const details=row.details||{}; const message=details.message||''; const changes=summarize(row.previous,row.new);
                const fieldsChanged = Array.isArray(details.fields_changed) ? details.fields_changed : [];
                const chip=badgeClass(action);
                const card=document.createElement('div'); card.className='bg-white border border-gray-200 rounded-xl p-4';
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
        async function refresh(){
            const url = `../../controllers/admin/logs.php?tab=${encodeURIComponent(state.tab)}&q=${encodeURIComponent(state.q)}&page=${state.page}&per=${state.per}&from=${encodeURIComponent(state.from||'')}&to=${encodeURIComponent(state.to||'')}`;
            const r = await fetch(url);
            const d = await r.json();
            if(!d.success){ render([]); return; }
            renderStats(d.stats||{});
            window.__lastTotal = d.total||0; render(d.items||[]);
            const pages = Math.max(1, Math.ceil((d.total||0)/state.per));
            document.getElementById('pageInfo').textContent = `Page ${state.page} / ${pages}`;
            document.getElementById('total').textContent = String(d.total||0);
            document.getElementById('showing').textContent = String((d.items||[]).length);
            if(window.lucide) window.lucide.createIcons();
        }
        window.addEventListener('DOMContentLoaded', ()=>{ initUI(); refresh(); });
    </script>
</body>
</html>
