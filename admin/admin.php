<?php
// Simple admin dashboard page
// Note: adjust session/auth checks as needed for your project
session_start();
// Example: require admin login - change to your existing auth logic
// if (!isset($_SESSION['admin_logged_in'])) {
//     header('Location: ../login.php');
//     exit;
// }
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Admin Dashboard - Pawhabilin</title>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap">
  <link rel="stylesheet" href="admin.css">
</head>
<body>
  <div class="app">
    <aside class="sidebar" aria-label="Main navigation">
      <div class="brand">
        <img src="../pictures/Pawhabilin logo.png" alt="logo">
        <h1>Pawhabilin Admin</h1>
      </div>
      <nav class="nav" role="navigation">
        <div class="nav-item active" data-section="home">
          <span class="icon"><svg viewBox="0 0 24 24" fill="none"><path d="M3 11.5L12 4l9 7.5V20a1 1 0 0 1-1 1h-5v-6H9v6H4a1 1 0 0 1-1-1V11.5z" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
          <span class="label">HOME</span>
        </div>
        <div class="nav-item" data-section="appointments">
          <span class="icon"><svg viewBox="0 0 24 24" fill="none"><path d="M8 7V3M16 7V3M3 11h18M5 21h14a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v11a2 2 0 0 0 2 2z" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
          <span class="label">APPOINTMENTS</span>
        </div>
        <div class="nav-item" data-section="products">
          <span class="icon"><svg viewBox="0 0 24 24" fill="none"><path d="M3 3h18v6H3zM3 13h18v8H3z" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
          <span class="label">PRODUCTS</span>
        </div>
        <div class="nav-item" data-section="orders">
          <span class="icon"><svg viewBox="0 0 24 24" fill="none"><path d="M3 3h18v4H3zM3 11h18v10H3z" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
          <span class="label">ORDERS</span>
        </div>
        <div class="nav-item" data-section="pets">
          <span class="icon"><svg viewBox="0 0 24 24" fill="none"><path d="M12 2c1.1 0 2 .9 2 2v1h2a2 2 0 0 1 2 2v3h-2v7H8V10H6V7a2 2 0 0 1 2-2h2V4c0-1.1.9-2 2-2z" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
          <span class="label">PETs</span>
        </div>
      </nav>
    </aside>
    <main class="content">
      <div class="topbar">
        <h2 id="section-title">Dashboard</h2>
        <div class="actions"><button class="btn" id="logoutBtn">Logout</button></div>
      </div>

      <section id="home" class="section">
        <div class="cards">
          <div class="card"><h3>Total Appointments</h3><p class="small">34</p></div>
          <div class="card"><h3>Products</h3><p class="small">12</p></div>
          <div class="card"><h3>Orders</h3><p class="small">7</p></div>
          <div class="card"><h3>Registered Pets</h3><p class="small">58</p></div>
        </div>
        <div class="card"><h3>Analytics (sample)</h3>
          <canvas id="chart" style="width:100%;height:180px"></canvas>
        </div>
      </section>

      <section id="appointments" class="section" style="display:none">
        <div class="card"><h3>Appointments</h3>
          <p class="small">Monitor booked services here (server integration required to fetch live data).</p>
          <table id="appt-table"><thead><tr><th>Client</th><th>Service</th><th>Date</th><th>Status</th></tr></thead><tbody>
            <tr><td>Jane</td><td>Grooming</td><td>2025-09-05</td><td><span class="muted">Confirmed</span></td></tr>
          </tbody></table>
        </div>
      </section>

      <section id="products" class="section" style="display:none">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px">
          <h3>Products</h3>
          <div><button class="btn" id="addProductBtn">Add Product</button></div>
        </div>
        <div class="card">
          <table id="products-table"><thead><tr><th>Image</th><th>Name</th><th>Price</th><th>Actions</th></tr></thead><tbody>
          </tbody></table>
        </div>
      </section>

      <section id="orders" class="section" style="display:none">
        <div class="card"><h3>Orders</h3>
          <p class="small">Order monitoring placeholder. Integrate with orders table to see order details.</p>
        </div>
      </section>

      <section id="pets" class="section" style="display:none">
        <div class="card"><h3>Registered Pets</h3>
          <p class="small">List of registered pets. Connect to user database for live data.</p>
        </div>
      </section>

    </main>
  </div>

  <!-- Modals -->
  <div class="modal" id="productModal" aria-hidden="true">
    <div class="panel">
      <h3 id="modalTitle">Add Product</h3>
      <form id="productForm">
        <input type="hidden" name="id" id="productId">
        <label>Name <input name="name" id="productName" required></label>
        <label>Price <input name="price" id="productPrice" type="number" step="0.01" required></label>
        <label>Image <input name="image" id="productImage" type="file" accept="image/*"></label>
        <div style="display:flex;gap:8px;justify-content:flex-end">
          <button type="button" class="btn" id="saveProduct">Save</button>
          <button type="button" id="cancelProduct" class="muted">Cancel</button>
        </div>
      </form>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script>
    // Simple SPA navigation
    const items = document.querySelectorAll('.nav-item')
    const sections = document.querySelectorAll('.section')
    const title = document.getElementById('section-title')
    items.forEach(it=>it.addEventListener('click', ()=>{
      items.forEach(i=>i.classList.remove('active'))
      it.classList.add('active')
      const s = it.dataset.section
      sections.forEach(sec=>sec.style.display = sec.id === s ? '' : 'none')
      title.textContent = it.querySelector('.label').textContent
      if (s === 'products') loadProducts()
    }))

    // Chart sample
    const ctx = document.getElementById('chart').getContext('2d')
    new Chart(ctx, {type:'line',data:{labels:['Mon','Tue','Wed','Thu','Fri','Sat','Sun'],datasets:[{label:'Visits',data:[12,19,8,15,22,18,24],borderColor:'#10b981',backgroundColor:'rgba(16,185,129,0.08)'}]}})

    // Product CRUD UI
    const productsTable = document.querySelector('#products-table tbody')
    const productModal = document.getElementById('productModal')
    const productForm = document.getElementById('productForm')
    document.getElementById('addProductBtn').addEventListener('click', ()=>{openProductModal()})
    document.getElementById('cancelProduct').addEventListener('click', ()=>closeProductModal())
    document.getElementById('saveProduct').addEventListener('click', saveProduct)

    async function loadProducts(){
      productsTable.innerHTML = '<tr><td colspan="4" class="small muted">Loading...</td></tr>'
      try{
        const res = await fetch('products_action.php?action=list')
        const data = await res.json()
        renderProducts(data)
      }catch(e){
        productsTable.innerHTML = '<tr><td colspan="4" class="small muted">Error loading products</td></tr>'
      }
    }

    function renderProducts(list){
      if(!Array.isArray(list)||list.length===0){
        productsTable.innerHTML = '<tr><td colspan="4" class="small muted">No products</td></tr>'
        return
      }
      productsTable.innerHTML = ''
      list.forEach(p=>{
        const tr = document.createElement('tr')
        tr.innerHTML = `<td>${p.image?`<img src="../${p.image}" class="preview">`:'-'}</td><td>${escapeHtml(p.name)}</td><td>${escapeHtml(p.price)}</td><td><button class="btn" data-id="${p.id}" data-action="edit">Edit</button> <button data-id="${p.id}" data-action="delete" class="muted">Delete</button></td>`
        productsTable.appendChild(tr)
      })
      productsTable.querySelectorAll('button').forEach(b=>{
        b.addEventListener('click', async e=>{
          const id = b.dataset.id; const action = b.dataset.action
          if(action==='edit'){ openProductModal(id) }
          if(action==='delete'){ if(confirm('Delete product?')){ await deleteProduct(id); loadProducts() } }
        })
      })
    }

    function openProductModal(id=null){
      productForm.reset(); document.getElementById('productId').value = id||''
      document.getElementById('modalTitle').textContent = id ? 'Edit Product' : 'Add Product'
      if(id){ // load single product
        fetch('products_action.php?action=get&id='+encodeURIComponent(id)).then(r=>r.json()).then(p=>{
          document.getElementById('productName').value = p.name
          document.getElementById('productPrice').value = p.price
        })
      }
      productModal.classList.add('show')
    }
    function closeProductModal(){ productModal.classList.remove('show') }

    async function saveProduct(){
      const fd = new FormData(productForm)
      const id = fd.get('id')
      const action = id ? 'edit' : 'add'
      fd.append('action', action)
      const res = await fetch('products_action.php', {method:'POST',body:fd})
      const data = await res.json()
      if(data.success){ closeProductModal(); loadProducts() } else alert(data.error||'Save failed')
    }

    async function deleteProduct(id){
      const fd = new FormData(); fd.append('action','delete'); fd.append('id',id)
      const res = await fetch('products_action.php',{method:'POST',body:fd}); return res.json()
    }

    // helper
    function escapeHtml(s){ return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;') }

    // load products on page ready
    if(document.querySelector('.nav-item.active').dataset.section === 'products') loadProducts()

    // simple logout hook
    document.getElementById('logoutBtn').addEventListener('click', ()=>{ window.location.href = '../logout.php' })
  </script>
</body>
</html>
