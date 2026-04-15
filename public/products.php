<?php
require_once __DIR__ . '/../bootstrap.php';
Auth::requireAuth();
$user    = Auth::user();
$appName = DB::fetch("SELECT value FROM settings WHERE `key`='business_name'")['value'] ?? Config::get('app.name', 'NexaPOS');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<?php include __DIR__ . '/includes/pwa.php'; ?>
<title>Products — <?= htmlspecialchars($appName) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{
  --bg:#f0f2f5;--white:#fff;--accent:#2563eb;--accent-d:#1d4ed8;--accent-l:#eff6ff;
  --sidebar:#111827;--t1:#111827;--t2:#6b7280;--t3:#9ca3af;--border:#e2e5eb;
  --red:#ef4444;--redbg:#fef2f2;--green:#10b981;--greenbg:#ecfdf5;--amber:#f59e0b;
  --r:8px;--rl:12px;--sh:0 1px 3px rgba(0,0,0,.06);--shm:0 4px 16px rgba(0,0,0,.08);
  font-family:'Inter',-apple-system,sans-serif;
}
html,body{height:100%;font-size:14px;color:var(--t1);background:var(--bg);-webkit-font-smoothing:antialiased}
body{display:flex;min-height:100vh}
a{text-decoration:none;color:inherit}
.topbar{background:var(--white);border-bottom:1px solid var(--border);padding:0 24px;height:60px;display:flex;align-items:center;gap:12px;flex-shrink:0}
.topbar h1{font-size:16px;font-weight:700;flex:1}
.btn{display:inline-flex;align-items:center;gap:7px;padding:8px 14px;border-radius:var(--r);border:1.5px solid var(--border);background:var(--white);color:var(--t2);cursor:pointer;font-family:inherit;font-size:13px;font-weight:500;transition:all .15s}
.btn:hover{border-color:var(--accent);color:var(--accent)}
.btn svg{width:15px;height:15px;fill:currentColor}
.btn-primary{background:var(--accent);border-color:var(--accent);color:#fff;box-shadow:0 2px 8px rgba(37,99,235,.25)}
.btn-primary:hover{background:var(--accent-d);border-color:var(--accent-d);color:#fff}

.content{flex:1;overflow-y:auto;padding:24px}
.content::-webkit-scrollbar{width:5px}
.content::-webkit-scrollbar-thumb{background:var(--border);border-radius:5px}

/* Stats */
.stats{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:24px}
.sc{background:var(--white);border:1px solid var(--border);border-radius:var(--rl);padding:20px;transition:box-shadow .15s}
.sc:hover{box-shadow:var(--shm)}
.sc-l{font-size:12px;font-weight:600;color:var(--t2);text-transform:uppercase;letter-spacing:.4px;margin-bottom:8px}
.sc-v{font-size:26px;font-weight:700;letter-spacing:-.5px;margin-bottom:4px}
.sc-s{font-size:12px;color:var(--t3)}

/* Filters */
.fb{display:flex;align-items:center;gap:10px;margin-bottom:20px;flex-wrap:wrap}
.fs{position:relative;flex:1;min-width:200px}
.fs svg{position:absolute;left:10px;top:50%;transform:translateY(-50%);width:15px;height:15px;fill:var(--t3);pointer-events:none}
.fs input{width:100%;height:38px;padding:0 12px 0 36px;border:1.5px solid var(--border);border-radius:var(--r);font-size:13px;font-family:inherit;outline:none;color:var(--t1);background:var(--white);transition:border-color .15s}
.fs input:focus{border-color:var(--accent)}
.fsel{height:38px;padding:0 12px;border:1.5px solid var(--border);border-radius:var(--r);font-size:13px;font-family:inherit;color:var(--t1);background:var(--white);outline:none;cursor:pointer;transition:border-color .15s}
.fsel:focus{border-color:var(--accent)}

/* Table */
.card{background:var(--white);border:1px solid var(--border);border-radius:var(--rl);overflow:hidden}
.card-head{display:flex;align-items:center;justify-content:space-between;padding:16px 20px;border-bottom:1px solid var(--border)}
.card-head h3{font-size:14px;font-weight:700}
.card-meta{font-size:12px;color:var(--t3)}
table{width:100%;border-collapse:collapse}
th{text-align:left;font-size:11px;font-weight:600;color:var(--t3);text-transform:uppercase;letter-spacing:.5px;padding:10px 16px;border-bottom:1px solid var(--border);white-space:nowrap;background:var(--bg)}
td{padding:12px 16px;font-size:13px;border-bottom:1px solid #f3f4f6;vertical-align:middle}
tr:last-child td{border-bottom:none}
tr:hover td{background:#fafafa}

/* Product thumbnail */
.pthumb{width:42px;height:42px;border-radius:var(--r);border:1px solid var(--border);object-fit:cover;display:block;background:var(--bg)}
.pthumb-empty{width:42px;height:42px;border-radius:var(--r);border:1px solid var(--border);background:var(--bg);display:flex;align-items:center;justify-content:center}
.pthumb-empty svg{width:20px;height:20px;fill:var(--t3)}

.badge{display:inline-flex;padding:3px 9px;border-radius:20px;font-size:11px;font-weight:600}
.b-green{background:var(--greenbg);color:#059669}
.b-gray{background:var(--bg);color:var(--t3)}
.b-red{background:var(--redbg);color:var(--red)}
.stk-ok{color:#059669;font-weight:600}
.stk-low{color:var(--amber);font-weight:600}
.stk-zero{color:var(--red);font-weight:600}
.act{display:flex;gap:6px}
.act button{width:30px;height:30px;border-radius:var(--r);border:1.5px solid var(--border);background:var(--white);cursor:pointer;display:flex;align-items:center;justify-content:center;color:var(--t2);transition:all .15s}
.act button:hover{border-color:var(--accent);color:var(--accent);background:var(--accent-l)}
.act button.del:hover{border-color:var(--red);color:var(--red);background:var(--redbg)}
.act button svg{width:14px;height:14px;fill:currentColor}

/* Pagination */
.pag{display:flex;align-items:center;justify-content:space-between;padding:14px 20px;border-top:1px solid var(--border)}
.pag-info{font-size:12px;color:var(--t3)}
.pag-btns{display:flex;gap:4px}
.pag-btns button{width:32px;height:32px;border:1.5px solid var(--border);border-radius:var(--r);background:var(--white);cursor:pointer;font-size:12px;font-weight:600;color:var(--t2);display:flex;align-items:center;justify-content:center;transition:all .15s}
.pag-btns button:hover{border-color:var(--accent);color:var(--accent)}
.pag-btns button.on{background:var(--accent);border-color:var(--accent);color:#fff}
.pag-btns button:disabled{opacity:.4;cursor:not-allowed}

.empty,.loading{display:flex;flex-direction:column;align-items:center;justify-content:center;padding:60px;color:var(--t3);gap:12px}
.empty svg{width:40px;height:40px;fill:currentColor;opacity:.3}
.spin{width:20px;height:20px;border:2.5px solid var(--border);border-top-color:var(--accent);border-radius:50%;animation:rot .7s linear infinite}
@keyframes rot{to{transform:rotate(360deg)}}

/* Modal */
.modal{display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:200;align-items:center;justify-content:center;padding:20px}
.modal.open{display:flex}
.mc{background:var(--white);border-radius:var(--rl);width:100%;max-width:560px;max-height:92vh;display:flex;flex-direction:column;overflow:hidden;box-shadow:0 20px 60px rgba(0,0,0,.2)}
.mh{display:flex;align-items:center;padding:18px 22px;border-bottom:1px solid var(--border);flex-shrink:0}
.mh h3{font-size:15px;font-weight:700;flex:1}
.mx{width:30px;height:30px;border:none;background:none;cursor:pointer;color:var(--t2);border-radius:var(--r);display:flex;align-items:center;justify-content:center;font-size:20px;line-height:1;transition:background .15s}
.mx:hover{background:var(--bg)}
.mb{padding:22px;overflow-y:auto;flex:1}
.mb::-webkit-scrollbar{width:4px}
.mb::-webkit-scrollbar-thumb{background:var(--border);border-radius:4px}
.mf{display:flex;gap:8px;justify-content:flex-end;padding:16px 22px;border-top:1px solid var(--border);flex-shrink:0;background:var(--bg)}
.fg{margin-bottom:16px}
.fg label{display:block;font-size:12px;font-weight:600;color:var(--t2);margin-bottom:6px}
.fg .hint{font-size:11px;color:var(--t3);margin-top:4px}
.fc{width:100%;height:38px;padding:0 12px;border:1.5px solid var(--border);border-radius:var(--r);font-size:13px;font-family:inherit;color:var(--t1);background:var(--white);outline:none;transition:border-color .15s}
.fc:focus{border-color:var(--accent)}
.fct{width:100%;padding:10px 12px;border:1.5px solid var(--border);border-radius:var(--r);font-size:13px;font-family:inherit;color:var(--t1);outline:none;resize:vertical;min-height:70px;transition:border-color .15s}
.fct:focus{border-color:var(--accent)}
.r2{display:grid;grid-template-columns:1fr 1fr;gap:14px}
.r3{display:grid;grid-template-columns:1fr 1fr 1fr;gap:14px}

/* Image upload box */
.imgbox{
  width:100%;height:130px;border:2px dashed var(--border);border-radius:var(--r);
  display:flex;flex-direction:column;align-items:center;justify-content:center;gap:6px;
  cursor:pointer;background:var(--bg);transition:all .15s;overflow:hidden;position:relative;
}
.imgbox:hover{border-color:var(--accent);background:var(--accent-l)}
.imgbox svg.up-ico{width:28px;height:28px;fill:var(--t3)}
.imgbox span{font-size:12px;color:var(--t3)}
.imgbox small{font-size:10px;color:var(--t3)}
.imgbox img.preview{position:absolute;inset:0;width:100%;height:100%;object-fit:contain;pointer-events:none}
.rm-img{
  position:absolute;top:8px;right:8px;
  width:26px;height:26px;border-radius:50%;
  background:var(--red);border:none;cursor:pointer;
  display:none;align-items:center;justify-content:center;
  z-index:2;box-shadow:0 2px 6px rgba(0,0,0,.2);
}
.imgbox.has .rm-img{display:flex}
.rm-img svg{width:12px;height:12px;fill:#fff;pointer-events:none}

/* Toggle */
.trow{display:flex;align-items:center;justify-content:space-between;padding:10px 0}
.trow span{font-size:13px;font-weight:500;color:var(--t1)}
.tw{position:relative;width:42px;height:24px;flex-shrink:0}
.tw input{opacity:0;width:0;height:0;position:absolute}
.ts{position:absolute;inset:0;border-radius:24px;background:var(--border);cursor:pointer;transition:background .2s}
.ts::before{content:'';position:absolute;width:18px;height:18px;border-radius:50%;background:#fff;top:3px;left:3px;transition:transform .2s;box-shadow:0 1px 4px rgba(0,0,0,.2)}
.tw input:checked+.ts{background:var(--accent)}
.tw input:checked+.ts::before{transform:translateX(18px)}

/* Toast */
#toasts{position:fixed;bottom:24px;right:24px;display:flex;flex-direction:column;gap:8px;z-index:9999;pointer-events:none}
.toast{padding:11px 18px;border-radius:var(--r);color:#fff;font-size:13px;font-weight:500;box-shadow:var(--shm);animation:tin .2s ease;background:var(--t1)}
.toast.ok{background:#059669}.toast.err{background:var(--red)}.toast.warn{background:var(--amber)}
@keyframes tin{from{opacity:0;transform:translateY(8px)}to{opacity:1;transform:translateY(0)}}

@media(max-width:1100px){.stats{grid-template-columns:1fr 1fr}}
@media(max-width:900px){.stats{grid-template-columns:1fr 1fr}}
</style>
</head>
<body>
<?php require_once __DIR__ . '/includes/sidebar.php'; ?>

<div class="sb-main">
  <header class="topbar">
    <h1>Products</h1>
    <button class="btn" onclick="openCats()"><svg viewBox="0 0 24 24"><path d="M10 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V8c0-1.1-.9-2-2-2h-8l-2-2z"/></svg>Categories</button>
    <button class="btn" id="dupBtn" onclick="removeDuplicates()" style="display:none;color:var(--red);border-color:#fecaca"><svg viewBox="0 0 24 24"><path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/></svg>Remove Duplicates</button>
    <button class="btn btn-primary" onclick="openAdd()"><svg viewBox="0 0 24 24"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>Add Product</button>
  </header>

  <div class="content">
    <div class="stats">
      <div class="sc"><div class="sc-l">Total Active</div><div class="sc-v" id="sv1" style="color:var(--accent)">—</div><div class="sc-s">Active products</div></div>
      <div class="sc"><div class="sc-l">Low Stock</div><div class="sc-v" id="sv2" style="color:var(--amber)">—</div><div class="sc-s">Below alert qty</div></div>
      <div class="sc"><div class="sc-l">Out of Stock</div><div class="sc-v" id="sv3" style="color:var(--red)">—</div><div class="sc-s">Zero quantity</div></div>
      <div class="sc"><div class="sc-l">Categories</div><div class="sc-v" id="sv4" style="color:#059669">—</div><div class="sc-s">Active categories</div></div>
    </div>

    <div class="fb">
      <div class="fs">
        <svg viewBox="0 0 24 24"><path d="M15.5 14h-.79l-.28-.27A6.471 6.471 0 0016 9.5 6.5 6.5 0 109.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></svg>
        <input type="text" id="fq" placeholder="Search name, SKU, barcode…" oninput="doFilter()">
      </div>
      <select class="fsel" id="fcat" onchange="doFilter()"><option value="">All Categories</option></select>
      <select class="fsel" id="fsts" onchange="doFilter()"><option value="">All Status</option><option value="active">Active</option><option value="inactive">Inactive</option></select>
      <select class="fsel" id="fstk" onchange="doFilter()"><option value="">All Stock</option><option value="ok">In Stock</option><option value="low">Low Stock</option><option value="out">Out of Stock</option></select>
      <button class="btn" onclick="clearF()">Clear</button>
    </div>

    <div class="card">
      <div class="card-head"><h3>Product List</h3><span class="card-meta" id="cmeta">—</span></div>
      <div id="tbody"><div class="loading"><div class="spin"></div></div></div>
      <div class="pag" id="pagWrap" style="display:none">
        <span class="pag-info" id="pagInfo"></span>
        <div class="pag-btns" id="pagBtns"></div>
      </div>
    </div>
  </div>
</div>

<!-- Product Modal -->
<div class="modal" id="prodModal">
  <div class="mc">
    <div class="mh"><h3 id="mtitle">Add Product</h3><button class="mx" onclick="closeM('prodModal')">×</button></div>
    <div class="mb">
      <input type="hidden" id="prodId" value="">
      <div class="fg">
        <label>Product Image</label>
        <div class="imgbox" id="imgbox" onclick="document.getElementById('imgInp').click()">
          <svg class="up-ico" viewBox="0 0 24 24"><path d="M21 19V5c0-1.1-.9-2-2-2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2zM8.5 13.5l2.5 3.01L14.5 12l4.5 6H5l3.5-4.5z"/></svg>
          <span>Click to upload image</span>
          <small>JPG, PNG, WebP — max 5MB</small>
          <button type="button" class="rm-img" onclick="rmImg(event)">
            <svg viewBox="0 0 24 24"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>
          </button>
        </div>
        <input type="file" id="imgInp" accept="image/jpeg,image/png,image/gif,image/webp" style="display:none" onchange="onImg(this)">
      </div>
      <div class="r2">
        <div class="fg"><label>Name *</label><input class="fc" id="fn" placeholder="Product name"></div>
        <div class="fg"><label>Category</label><select class="fc" id="fcat2"><option value="">— None —</option></select></div>
      </div>
      <div class="r3">
        <div class="fg"><label>SKU</label><input class="fc" id="fsku" placeholder="Auto if blank"></div>
        <div class="fg"><label>Barcode</label><input class="fc" id="fbc"></div>
        <div class="fg"><label>Unit</label>
          <select class="fc" id="funit"><option value="pcs">pcs</option><option value="kg">kg</option><option value="g">g</option><option value="ltr">ltr</option><option value="ml">ml</option><option value="box">box</option><option value="pack">pack</option><option value="dozen">dozen</option></select>
        </div>
      </div>
      <div class="r3">
        <div class="fg"><label>Cost (৳)</label><input class="fc" id="fcost" type="number" min="0" step="0.01" placeholder="0.00"></div>
        <div class="fg"><label>Selling Price (৳) *</label><input class="fc" id="fprice" type="number" min="0" step="0.01" placeholder="0.00"></div>
        <div class="fg"><label>Wholesale (৳)</label><input class="fc" id="fwhole" type="number" min="0" step="0.01" placeholder="0.00"></div>
      </div>
      <div class="r2">
        <div class="fg" id="fstockWrap"><label id="fstockLbl">Opening Stock</label><input class="fc" id="fstock" type="number" min="0" placeholder="0"><div class="hint" id="fstockHint">Leave blank to keep unchanged when editing</div></div>
        <div class="fg"><label>Stock Alert Qty</label><input class="fc" id="falert" type="number" min="0" placeholder="5"></div>
      </div>
      <div class="r2">
        <div class="fg"><label>Tax Rate (%)</label><input class="fc" id="ftax" type="number" min="0" step="0.01" placeholder="0"></div>
        <div class="fg"><label>Status</label><select class="fc" id="fstat"><option value="active">Active</option><option value="inactive">Inactive</option><option value="out_of_stock">Out of Stock</option></select></div>
      </div>
      <div class="fg"><label>Description</label><textarea class="fct" id="fdesc" placeholder="Optional…"></textarea></div>
      <div class="trow"><span>Track Stock</span><label class="tw"><input type="checkbox" id="ftrack" checked><span class="ts"></span></label></div>
    </div>
    <div class="mf">
      <button class="btn" onclick="closeM('prodModal')">Cancel</button>
      <button class="btn btn-primary" id="saveBtn" onclick="doSave()">Save Product</button>
    </div>
  </div>
</div>

<!-- Category Modal -->
<div class="modal" id="catModal">
  <div class="mc" style="max-width:400px">
    <div class="mh"><h3>Categories</h3><button class="mx" onclick="closeM('catModal')">×</button></div>
    <div class="mb">
      <div style="display:flex;gap:8px;margin-bottom:16px">
        <input class="fc" id="newcat" placeholder="Category name" style="flex:1">
        <button class="btn btn-primary" onclick="addCat()">Add</button>
      </div>
      <div id="catlist"></div>
    </div>
  </div>
</div>

<div id="toasts"></div>

<script>
const API     = '../routes/api.php';
const IMGBASE = '/nexapos/public/uploads/products/';

let prods=[], cats=[], filtered=[], pg=1, PER=25, eid=null, saving=false;

// Image
function onImg(inp) {
  const f=inp.files[0]; if(!f) return;
  if(f.size>5*1024*1024){toast('Max 5MB','err');inp.value='';return;}
  const r=new FileReader();
  r.onload=e=>setPreview(e.target.result);
  r.readAsDataURL(f);
}
function setPreview(src) {
  const box=document.getElementById('imgbox');
  let img=box.querySelector('img.preview');
  if(!img){img=document.createElement('img');img.className='preview';box.appendChild(img);}
  img.src=src;
  box.classList.add('has');
}
function rmImg(e) {
  e.stopPropagation();
  const box=document.getElementById('imgbox');
  const img=box.querySelector('img.preview');
  if(img)img.remove();
  document.getElementById('imgInp').value='';
  box.classList.remove('has');
}
function resetImg(){rmImg({stopPropagation:()=>{}});}

// Load categories
async function loadCats() {
  const d=await get('products','categories');
  cats=d.data||[];
  const s1=document.getElementById('fcat');
  const s2=document.getElementById('fcat2');
  const opts=cats.map(c=>`<option value="${c.id}">${c.name}</option>`).join('');
  s1.innerHTML='<option value="">All Categories</option>'+opts;
  s2.innerHTML='<option value="">— None —</option>'+opts;
  document.getElementById('sv4').textContent=cats.length;
}

// Load products
async function loadProds() {
  document.getElementById('tbody').innerHTML='<div class="loading"><div class="spin"></div></div>';
  const d=await get('products','list','per_page=500');
  prods=d.data?.products||[];
  // Stats
  const active=prods.filter(p=>p.status==='active');
  document.getElementById('sv1').textContent=active.length;
  document.getElementById('sv2').textContent=active.filter(p=>parseFloat(p.stock||0)>0&&parseFloat(p.stock||0)<=p.stock_alert_qty).length;
  document.getElementById('sv3').textContent=active.filter(p=>parseFloat(p.stock||0)<=0&&p.track_stock).length;
  // Show "Remove Duplicates" button if duplicates exist
  const names=prods.map(p=>p.name);
  const hasDups=names.some((n,i)=>names.indexOf(n)!==i);
  document.getElementById('dupBtn').style.display=hasDups?'inline-flex':'none';
  doFilter();
}

async function removeDuplicates(){
  if(!confirm('This will keep the product with the highest stock and remove all duplicates with the same name. Continue?'))return;
  const fd=new FormData();
  const r=await fetch(`${API}?module=products&action=delete_duplicates`,{method:'POST',body:fd}).then(r=>r.json());
  if(r.success){toast(r.message,'ok');loadProds();}
  else toast(r.message||'Failed','err');
}

// Filter
function doFilter() {
  const q=document.getElementById('fq').value.toLowerCase();
  const fc=document.getElementById('fcat').value;
  const fs=document.getElementById('fsts').value;
  const fk=document.getElementById('fstk').value;
  filtered=prods.filter(p=>{
    if(q&&!p.name?.toLowerCase().includes(q)&&!p.sku?.toLowerCase().includes(q)&&!p.barcode?.toLowerCase().includes(q))return false;
    if(fc&&p.category_id!=fc)return false;
    if(fs&&p.status!==fs)return false;
    if(fk){
      const s=parseFloat(p.stock||0);
      if(fk==='out'&&s>0)return false;
      if(fk==='low'&&!(s>0&&s<=p.stock_alert_qty))return false;
      if(fk==='ok'&&!(s>p.stock_alert_qty))return false;
    }
    return true;
  });
  pg=1;render();
}
function clearF(){['fq','fcat','fsts','fstk'].forEach(id=>document.getElementById(id).value='');doFilter();}

// Render
function render() {
  const total=filtered.length, start=(pg-1)*PER, rows=filtered.slice(start,start+PER);
  document.getElementById('cmeta').textContent=total+' products';
  if(!total){
    document.getElementById('tbody').innerHTML='<div class="empty"><svg viewBox="0 0 24 24"><path d="M20 2H4c-1 0-2 .9-2 2v3.01c0 .72.43 1.34 1 1.72V20c0 1.1 1.1 2 2 2h14c.9 0 2-.9 2-2V8.72c.57-.38 1-.99 1-1.71V4c0-1.1-1-2-2-2zm-5 12H9v-2h6v2zm3-8H6V4h12v2z"/></svg><p>No products found</p></div>';
    document.getElementById('pagWrap').style.display='none';return;
  }
  document.getElementById('tbody').innerHTML=`<table>
    <thead><tr><th>Image</th><th>Name</th><th>SKU</th><th>Category</th><th>Price</th><th>Stock</th><th>Status</th><th></th></tr></thead>
    <tbody>${rows.map(p=>{
      const s=parseFloat(p.stock||0),sc=s<=0?'stk-zero':s<=p.stock_alert_qty?'stk-low':'stk-ok',sl=s<=0?'Out':s;
      const bc={active:'b-green',inactive:'b-gray',out_of_stock:'b-red'}[p.status]||'b-gray';
      // Image — p.image is just filename (e.g. prod_123.jpg)
      const imgSrc=p.image?IMGBASE+p.image:'';
      const imgTag=imgSrc
        ?`<img class="pthumb" src="${imgSrc}" alt="${p.name}" onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">`+
          `<div class="pthumb-empty" style="display:none"><svg viewBox="0 0 24 24"><path d="M21 19V5c0-1.1-.9-2-2-2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2zM8.5 13.5l2.5 3.01L14.5 12l4.5 6H5l3.5-4.5z"/></svg></div>`
        :`<div class="pthumb-empty"><svg viewBox="0 0 24 24"><path d="M21 19V5c0-1.1-.9-2-2-2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2zM8.5 13.5l2.5 3.01L14.5 12l4.5 6H5l3.5-4.5z"/></svg></div>`;
      return`<tr>
        <td>${imgTag}</td>
        <td><div style="font-weight:600;color:var(--t1)">${p.name}</div>${p.barcode?`<div style="font-size:11px;color:var(--t3)">${p.barcode}</div>`:''}</td>
        <td style="color:var(--t2);font-size:12px">${p.sku||'—'}</td>
        <td style="font-size:12px;color:var(--t2)">${p.category_name||'—'}</td>
        <td style="font-weight:700">৳${parseFloat(p.selling_price||0).toFixed(2)}</td>
        <td class="${sc}">${p.track_stock?sl:'∞'}</td>
        <td><span class="badge ${bc}">${p.status}</span></td>
        <td><div class="act">
          <button onclick="editProd(${p.id})" title="Edit"><svg viewBox="0 0 24 24"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34a1 1 0 00-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg></button>
          <button class="del" onclick="delProd(${p.id})" title="Delete"><svg viewBox="0 0 24 24"><path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/></svg></button>
        </div></td></tr>`;
    }).join('')}</tbody></table>`;

  const pages=Math.ceil(total/PER);
  if(pages>1){
    document.getElementById('pagWrap').style.display='flex';
    document.getElementById('pagInfo').textContent=`${start+1}–${Math.min(start+PER,total)} of ${total}`;
    const pb=document.getElementById('pagBtns');pb.innerHTML='';
    const ab=(l,p2,d,a)=>{const b=document.createElement('button');if(a)b.className='on';b.textContent=l;b.disabled=d;b.onclick=()=>{pg=p2;render()};pb.appendChild(b)};
    ab('‹',pg-1,pg===1,false);
    for(let i=Math.max(1,pg-2);i<=Math.min(pages,pg+2);i++)ab(i,i,false,i===pg);
    ab('›',pg+1,pg===pages,false);
  }else{document.getElementById('pagWrap').style.display='none';}
}

// Helper: get current editing ID from hidden input (empty = new product)
function getProdId(){ return document.getElementById('prodId').value; }

// Open add
function openAdd(){
  document.getElementById('prodId').value=''; // CLEAR — this is the authoritative ID store
  eid=null;
  document.getElementById('mtitle').textContent='Add Product';
  ['fn','fsku','fbc','fcost','fprice','fwhole','fstock','falert','ftax','fdesc'].forEach(i=>{const el=document.getElementById(i);if(el)el.value='';});
  document.getElementById('funit').value='pcs';
  document.getElementById('fstat').value='active';
  document.getElementById('fcat2').value='';
  document.getElementById('falert').value='5';
  document.getElementById('ftrack').checked=true;
  document.getElementById('fstockLbl').textContent='Opening Stock';
  document.getElementById('fstockHint').textContent='Initial quantity (new products only)';
  resetImg();
  document.getElementById('prodModal').classList.add('open');
  setTimeout(()=>document.getElementById('fn').focus(),120);
}

// Edit
async function editProd(id){
  // Open modal immediately so user sees loading state
  document.getElementById('prodId').value=''; // clear while loading
  eid=null;
  document.getElementById('mtitle').textContent='Loading…';
  ['fn','fsku','fbc','fcost','fprice','fwhole','fstock','falert','ftax','fdesc'].forEach(i=>{const el=document.getElementById(i);if(el)el.value='';});
  document.getElementById('fstockLbl').textContent='Set Stock To';
  document.getElementById('fstockHint').textContent='Enter new qty, or leave blank to keep unchanged';
  resetImg();
  document.getElementById('prodModal').classList.add('open');

  const d=await get('products','get',`id=${id}`);
  if(!d.success){toast('Failed to load product','err');closeM('prodModal');return;}
  const p=d.data;

  // Set the authoritative ID AFTER data loaded — prevents saving with wrong ID
  document.getElementById('prodId').value=p.id;
  eid=p.id; // keep eid in sync too
  document.getElementById('mtitle').textContent='Edit Product';
  document.getElementById('fn').value     =p.name||'';
  document.getElementById('fsku').value   =p.sku||'';
  document.getElementById('fbc').value    =p.barcode||'';
  document.getElementById('fcost').value  =p.cost_price||'';
  document.getElementById('fprice').value =p.selling_price||'';
  document.getElementById('fwhole').value =p.wholesale_price||'';
  document.getElementById('fstock').value =''; // blank = keep existing
  document.getElementById('falert').value =p.stock_alert_qty||5;
  document.getElementById('ftax').value   =p.tax_rate||0;
  document.getElementById('fdesc').value  =p.description||'';
  document.getElementById('funit').value  =p.unit||'pcs';
  document.getElementById('fstat').value  =p.status||'active';
  document.getElementById('fcat2').value  =p.category_id||'';
  document.getElementById('ftrack').checked=!!parseInt(p.track_stock??1);
  if(p.image)setPreview(IMGBASE+p.image);
}

// Save
async function doSave(){
  if(saving)return;
  const name=document.getElementById('fn').value.trim();
  const price=document.getElementById('fprice').value;
  if(!name){toast('Name required','warn');return;}
  if(!price){toast('Price required','warn');return;}
  saving=true;
  const btn=document.getElementById('saveBtn');
  btn.disabled=true;btn.textContent='Saving…';

  // Read ID from the hidden input — this is the ONLY authoritative source
  const currentId=document.getElementById('prodId').value;
  const fd=new FormData();
  if(currentId)fd.append('id',currentId);
  fd.append('name',           name);
  fd.append('category_id',    document.getElementById('fcat2').value||'');
  fd.append('sku',            document.getElementById('fsku').value);
  fd.append('barcode',        document.getElementById('fbc').value);
  fd.append('unit',           document.getElementById('funit').value);
  fd.append('cost_price',     document.getElementById('fcost').value||0);
  fd.append('selling_price',  price);
  fd.append('wholesale_price',document.getElementById('fwhole').value||0);
  const stockVal=document.getElementById('fstock').value;
  if(currentId){
    // On edit: send as stock_qty (allows updating existing stock)
    if(stockVal!=='')fd.append('stock_qty',stockVal);
  }else{
    // On add: send as opening_stock
    fd.append('opening_stock',stockVal||0);
  }
  fd.append('stock_alert_qty',document.getElementById('falert').value||5);
  fd.append('tax_rate',       document.getElementById('ftax').value||0);
  fd.append('description',    document.getElementById('fdesc').value);
  fd.append('status',         document.getElementById('fstat').value);
  fd.append('track_stock',    document.getElementById('ftrack').checked?1:0);

  const imgFile=document.getElementById('imgInp').files[0];
  if(imgFile)fd.append('image',imgFile);

  try{
    const r=await fetch(`${API}?module=products&action=save`,{method:'POST',body:fd});
    const d=await r.json();
    if(d.success){toast(currentId?'Product updated':'Product added','ok');closeM('prodModal');loadProds();}
    else toast(d.message||'Save failed','err');
  }catch(e){toast('Network error','err');}

  saving=false;btn.disabled=false;btn.textContent='Save Product';
}

// Delete
async function delProd(id){
  if(!confirm('Delete this product?'))return;
  const fd=new FormData();fd.append('id',id);
  const d=await fetch(`${API}?module=products&action=delete`,{method:'POST',body:fd}).then(r=>r.json());
  if(d.success){toast('Deleted','ok');loadProds();}
  else toast(d.message||'Failed','err');
}

// Categories
async function openCats(){document.getElementById('catModal').classList.add('open');renderCats();}
async function renderCats(){
  const d=await get('products','categories');
  const list=d.data||[];
  document.getElementById('catlist').innerHTML=list.length
    ?list.map(c=>`<div style="display:flex;align-items:center;justify-content:space-between;padding:10px 0;border-bottom:1px solid #f3f4f6">
        <span style="font-size:13px;font-weight:500;color:var(--t1)">${c.name}</span>
        <button onclick="delCat(${c.id})" style="width:28px;height:28px;border-radius:var(--r);border:1.5px solid var(--border);background:var(--white);cursor:pointer;display:flex;align-items:center;justify-content:center;color:var(--t2);transition:all .15s" onmouseover="this.style.borderColor='var(--red)';this.style.color='var(--red)'" onmouseout="this.style.borderColor='var(--border)';this.style.color='var(--t2)'">
          <svg viewBox="0 0 24 24" style="width:13px;height:13px;fill:currentColor"><path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/></svg>
        </button>
      </div>`).join('')
    :'<p style="color:var(--t3);font-size:13px;padding:20px 0;text-align:center">No categories yet</p>';
}
async function addCat(){
  const name=document.getElementById('newcat').value.trim();
  if(!name)return;
  const fd=new FormData();fd.append('name',name);
  const d=await fetch(`${API}?module=products&action=save_category`,{method:'POST',body:fd}).then(r=>r.json());
  if(d.success){document.getElementById('newcat').value='';renderCats();loadCats();toast('Category added','ok');}
  else toast(d.message,'err');
}
async function delCat(id){
  if(!confirm('Delete?'))return;
  const fd=new FormData();fd.append('id',id);
  await fetch(`${API}?module=products&action=delete_category`,{method:'POST',body:fd});
  renderCats();loadCats();
}

// Utils
async function get(mod,act,qs=''){const r=await fetch(`${API}?module=${mod}&action=${act}${qs?'&'+qs:''}`);return r.json();}
function closeM(id){
  document.getElementById(id).classList.remove('open');
  if(id==='prodModal'){
    // Always clear the ID when closing — prevents stale ID on next open
    document.getElementById('prodId').value='';
    eid=null;
  }
}
document.querySelectorAll('.modal').forEach(m=>m.addEventListener('click',e=>{
  if(e.target===m){
    m.classList.remove('open');
    if(m.id==='prodModal'){document.getElementById('prodId').value='';eid=null;}
  }
}));
// CRITICAL: Block Enter key in product modal to prevent duplicate saves
document.getElementById('prodModal').addEventListener('keydown',e=>{
  if(e.key==='Enter'&&e.target.tagName!=='TEXTAREA'){e.preventDefault();e.stopPropagation();}
},true);
function toast(msg,type='',dur=3200){
  const w=document.getElementById('toasts');
  const el=document.createElement('div');el.className='toast '+type;el.textContent=msg;
  w.appendChild(el);
  setTimeout(()=>{el.style.cssText='opacity:0;transform:translateY(4px);transition:all .25s';setTimeout(()=>el.remove(),260)},dur);
}

// Fix controller PHP syntax error - $w=$imagesx=$src should be $w=imagesx($src)
// Already fixed in controller above

loadCats();
loadProds();
</script>
</body>
</html>
