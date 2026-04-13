<?php
require_once dirname(__DIR__) . '/bootstrap.php';
Auth::requireAuth();
$user = Auth::user();
$appName = DB::fetch("SELECT value FROM settings WHERE `key`='business_name'")['value'] ?? Config::get('app.name', 'NexaPOS');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Categories — <?= htmlspecialchars($appName) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/layout.css">
<link rel="stylesheet" href="assets/css/pages.css">
</head>
<body>
<?php include dirname(__DIR__) . '/resources/views/partials/sidebar.php'; ?>
<div class="main">
<?php include dirname(__DIR__) . '/resources/views/partials/topbar.php'; ?>
<div class="content">

  <div class="page-header">
    <div>
      <h2 class="page-title">Categories</h2>
      <p class="page-sub">Manage product categories</p>
    </div>
    <button class="btn btn-primary" onclick="openAdd()">
      <svg viewBox="0 0 24 24"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>
      Add Category
    </button>
  </div>

  <!-- Stats -->
  <div class="kpi-grid" style="grid-template-columns:repeat(3,1fr);margin-bottom:24px">
    <div class="kpi-card">
      <div class="kpi-top"><span class="kpi-label">Total Categories</span><div class="kpi-icon" style="background:#eff6ff;color:#2563eb"><svg viewBox="0 0 24 24"><path d="M10 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V8c0-1.1-.9-2-2-2h-8l-2-2z"/></svg></div></div>
      <div class="kpi-value" id="kTotal">—</div>
      <div class="kpi-sub">Active categories</div>
    </div>
    <div class="kpi-card">
      <div class="kpi-top"><span class="kpi-label">Most Products</span><div class="kpi-icon" style="background:#ecfdf5;color:#059669"><svg viewBox="0 0 24 24"><path d="M20 2H4c-1 0-2 .9-2 2v3.01c0 .72.43 1.34 1 1.72V20c0 1.1 1.1 2 2 2h14c.9 0 2-.9 2-2V8.72c.57-.38 1-.99 1-1.71V4c0-1.1-1-2-2-2zm-5 12H9v-2h6v2zm3-8H6V4h12v2z"/></svg></div></div>
      <div class="kpi-value" id="kTop" style="font-size:16px">—</div>
      <div class="kpi-sub">Top category</div>
    </div>
    <div class="kpi-card">
      <div class="kpi-top"><span class="kpi-label">Total Products</span><div class="kpi-icon" style="background:#f5f3ff;color:#8b5cf6"><svg viewBox="0 0 24 24"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-7 3c1.93 0 3.5 1.57 3.5 3.5S13.93 13 12 13s-3.5-1.57-3.5-3.5S10.07 6 12 6zm7 13H5v-.23c0-.62.28-1.2.76-1.58C7.47 15.82 9.64 15 12 15s4.53.82 6.24 2.19c.48.38.76.97.76 1.58V19z"/></svg></div></div>
      <div class="kpi-value" id="kProds">—</div>
      <div class="kpi-sub">Across all categories</div>
    </div>
  </div>

  <!-- Filter + Table -->
  <div class="filter-row">
    <div class="search-wrap">
      <svg viewBox="0 0 24 24" fill="currentColor"><path d="M15.5 14h-.79l-.28-.27A6.471 6.471 0 0016 9.5 6.5 6.5 0 109.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></svg>
      <input type="text" id="searchInput" placeholder="Search categories…" oninput="filterCats()">
    </div>
  </div>

  <div class="table-card">
    <div class="table-wrap">
      <table>
        <thead><tr>
          <th>Name</th>
          <th>Slug</th>
          <th>Products</th>
          <th>Status</th>
          <th></th>
        </tr></thead>
        <tbody id="catBody"><tr><td colspan="5" class="tbl-loading"><div class="spin"></div> Loading...</td></tr></tbody>
      </table>
    </div>
    <div class="pagination" id="pagination" style="display:none"></div>
  </div>
</div>
</div>

<!-- Add/Edit Modal -->
<div class="modal-backdrop" id="catModal">
  <div class="modal">
    <div class="modal-head">
      <h3 id="modalTitle">Add Category</h3>
      <button class="modal-close" onclick="closeModal()"><svg viewBox="0 0 24 24"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg></button>
    </div>
    <div class="modal-body">
      <input type="hidden" id="editId">
      <div class="form-group">
        <label>Category Name *</label>
        <input class="form-control" id="catName" placeholder="e.g. Beverages">
      </div>
      <div class="form-group">
        <label>Description</label>
        <textarea class="form-control" id="catDesc" placeholder="Optional description" rows="3"></textarea>
      </div>
      <div class="modal-foot">
        <button class="btn btn-ghost" onclick="closeModal()">Cancel</button>
        <button class="btn btn-primary" id="saveBtn" onclick="saveCat()">Save Category</button>
      </div>
    </div>
  </div>
</div>

<script src="assets/js/app.js"></script>
<script>
const API = '../routes/api.php';
let allCats = [], currentPage = 1, PER = 20, saving = false;

async function load() {
  const res = await api(`${API}?module=products&action=categories`);
  allCats = res.data || [];
  // Get product counts per category
  const pres = await api(`${API}?module=products&action=list&per_page=500&status=active`);
  const prods = pres.data?.products || [];
  const counts = {};
  prods.forEach(p => { if(p.category_id) counts[p.category_id] = (counts[p.category_id]||0)+1; });
  allCats.forEach(c => c._count = counts[c.id] || 0);
  // KPIs
  document.getElementById('kTotal').textContent = allCats.length;
  document.getElementById('kProds').textContent = prods.length;
  const top = allCats.sort((a,b)=>b._count-a._count)[0];
  document.getElementById('kTop').textContent = top ? `${top.name} (${top._count})` : '—';
  render();
}

function filterCats() {
  currentPage = 1;
  render();
}

function render() {
  const q = document.getElementById('searchInput').value.toLowerCase();
  const filtered = allCats.filter(c => !q || c.name.toLowerCase().includes(q) || (c.slug||'').toLowerCase().includes(q));
  const total = filtered.length;
  const start = (currentPage-1)*PER;
  const rows = filtered.slice(start, start+PER);

  if (!rows.length) {
    document.getElementById('catBody').innerHTML = `<tr><td colspan="5" class="tbl-empty">No categories found</td></tr>`;
    document.getElementById('pagination').style.display = 'none';
    return;
  }

  document.getElementById('catBody').innerHTML = rows.map(c => `
    <tr>
      <td><strong>${c.name}</strong></td>
      <td style="color:var(--text3);font-size:12px">${c.slug||'—'}</td>
      <td><span class="badge badge-blue">${c._count} products</span></td>
      <td><span class="badge ${c.status==='active'?'badge-green':'badge-amber'}">${c.status||'active'}</span></td>
      <td>
        <div class="action-btns">
          <button class="action-btn" onclick="editCat(${c.id})" title="Edit"><svg viewBox="0 0 24 24"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34a1 1 0 00-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg></button>
          <button class="action-btn action-btn-danger" onclick="deleteCat(${c.id},'${c.name.replace(/'/g,"\\'")}',${c._count})" title="Delete"><svg viewBox="0 0 24 24"><path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/></svg></button>
        </div>
      </td>
    </tr>`).join('');

  // Pagination
  const pages = Math.ceil(total/PER);
  const pag = document.getElementById('pagination');
  if (pages > 1) {
    pag.style.display = 'flex';
    const from = start+1, to = Math.min(start+PER, total);
    pag.innerHTML = `<span>Showing ${from}–${to} of ${total}</span><div class="page-btns">
      <button class="page-btn" onclick="currentPage--;render()" ${currentPage<=1?'disabled':''}>‹</button>
      ${Array.from({length:Math.min(5,pages)},(_,i)=>{const p=Math.max(1,Math.min(currentPage-2,pages-4))+i;return p<=pages?`<button class="page-btn ${p===currentPage?'active':''}" onclick="currentPage=${p};render()">${p}</button>`:''}).join('')}
      <button class="page-btn" onclick="currentPage++;render()" ${currentPage>=pages?'disabled':''}>›</button>
    </div>`;
  } else { pag.style.display = 'none'; }
}

function openAdd() {
  document.getElementById('modalTitle').textContent = 'Add Category';
  document.getElementById('editId').value = '';
  document.getElementById('catName').value = '';
  document.getElementById('catDesc').value = '';
  document.getElementById('catModal').classList.add('open');
  setTimeout(() => document.getElementById('catName').focus(), 100);
}

function editCat(id) {
  const c = allCats.find(x => x.id == id);
  if (!c) return;
  document.getElementById('modalTitle').textContent = 'Edit Category';
  document.getElementById('editId').value = c.id;
  document.getElementById('catName').value = c.name;
  document.getElementById('catDesc').value = c.description || '';
  document.getElementById('catModal').classList.add('open');
  setTimeout(() => document.getElementById('catName').focus(), 100);
}

function closeModal() {
  document.getElementById('catModal').classList.remove('open');
}

async function saveCat() {
  if (saving) return;
  const name = document.getElementById('catName').value.trim();
  if (!name) { toast('Category name is required', 'error'); return; }
  saving = true;
  document.getElementById('saveBtn').disabled = true;
  const fd = new FormData();
  fd.append('name', name);
  fd.append('description', document.getElementById('catDesc').value);
  const id = document.getElementById('editId').value;
  if (id) fd.append('id', id);
  const res = await apiFD(`${API}?module=products&action=save_category`, fd);
  saving = false;
  document.getElementById('saveBtn').disabled = false;
  if (res.success) {
    toast(id ? 'Category updated' : 'Category added', 'success');
    closeModal();
    load();
  } else {
    toast(res.message || 'Failed to save', 'error');
  }
}

async function deleteCat(id, name, count) {
  if (count > 0 && !confirm(`"${name}" has ${count} product(s). Deleting this category will unassign them. Continue?`)) return;
  if (count === 0 && !confirm(`Delete category "${name}"?`)) return;
  const fd = new FormData(); fd.append('id', id);
  const res = await apiFD(`${API}?module=products&action=delete_category`, fd);
  if (res.success) { toast('Category deleted', 'success'); load(); }
  else toast(res.message || 'Failed', 'error');
}

// Close on backdrop click
document.getElementById('catModal').addEventListener('click', e => { if(e.target===e.currentTarget) closeModal(); });

// Enter key in name field
document.getElementById('catName')?.addEventListener('keydown', e => { if(e.key==='Enter') saveCat(); });

load();
</script>
</body>
</html>
