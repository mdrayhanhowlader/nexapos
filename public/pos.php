<?php
require_once __DIR__ . '/../bootstrap.php';
Auth::requireAuth();
$user     = Auth::user();
$appName  = DB::fetch("SELECT value FROM settings WHERE `key`='business_name'")['value'] ?? Config::get('app.name', 'NexaPOS');
$appLogo  = DB::fetch("SELECT value FROM settings WHERE `key`='business_logo'")['value'] ?? null;
$currency = DB::fetch("SELECT value FROM settings WHERE `key`='currency_symbol'")['value'] ?? Config::get('app.currency_symbol', '৳');
$taxRate  = DB::fetch("SELECT value FROM settings WHERE `key`='tax_rate'")['value'] ?? Config::get('app.tax_rate', 0);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<?php include __DIR__ . '/includes/pwa.php'; ?>
<meta name="theme-color" content="#111827">
<title>POS — <?= htmlspecialchars($appName) ?></title>
<link rel="manifest" href="/nexapos/manifest.json">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{
  --font:'Inter',sans-serif;
  --bg:#f0f2f5;
  --white:#fff;
  --accent:#2563eb;
  --accent-d:#1d4ed8;
  --accent-l:#eff6ff;
  --sidebar:#111827;
  --text1:#111827;
  --text2:#6b7280;
  --text3:#9ca3af;
  --border:#e5e7eb;
  --red:#dc2626;
  --red-bg:#fef2f2;
  --green:#16a34a;
  --green-bg:#f0fdf4;
  --yellow:#d97706;
  --yellow-bg:#fffbeb;
  --r:8px;
  --sh:0 1px 3px rgba(0,0,0,.08);
  --sh-md:0 4px 12px rgba(0,0,0,.1);
}
html,body{height:100%;font-family:var(--font);font-size:14px;color:var(--text1);background:var(--bg);overflow:hidden}

/* ── Layout ── */
#shell{display:flex;flex-direction:column;height:100vh;overflow:hidden}

/* ── Topbar ── */
#topbar{
  display:flex;align-items:center;gap:10px;
  height:52px;padding:0 14px;
  background:var(--sidebar);
  flex-shrink:0;
}
.brand{display:flex;align-items:center;gap:8px;color:#fff;text-decoration:none;font-weight:700;font-size:14px;white-space:nowrap}
.brand-ico{width:28px;height:28px;background:var(--accent);border-radius:7px;display:flex;align-items:center;justify-content:center;font-size:13px}
.tb-div{width:1px;height:26px;background:rgba(255,255,255,.1);margin:0 2px}
.tb-search{
  flex:1;max-width:480px;position:relative;
}
#posSearch{
  width:100%;height:34px;padding:0 34px 0 12px;
  background:rgba(255,255,255,.09);border:1px solid rgba(255,255,255,.12);
  border-radius:6px;color:#fff;font-size:13px;font-family:var(--font);outline:none;
  transition:border-color .15s,background .15s;
}
#posSearch::placeholder{color:rgba(255,255,255,.35)}
#posSearch:focus{border-color:var(--accent);background:rgba(255,255,255,.13)}
.scan-ico{position:absolute;right:9px;top:50%;transform:translateY(-50%);color:rgba(255,255,255,.4);cursor:pointer;font-size:14px;background:none;border:none;padding:0}
.scan-ico:hover{color:#fff}
.tb-actions{display:flex;align-items:center;gap:6px;margin-left:auto}
.tb-btn{
  display:flex;align-items:center;gap:5px;height:32px;padding:0 11px;
  border:1px solid rgba(255,255,255,.12);border-radius:6px;
  background:rgba(255,255,255,.07);color:rgba(255,255,255,.75);
  font-size:12px;font-family:var(--font);font-weight:500;cursor:pointer;white-space:nowrap;
  transition:background .15s,color .15s;
}
.tb-btn:hover{background:rgba(255,255,255,.14);color:#fff}
.tb-badge{background:var(--accent);color:#fff;font-size:10px;font-weight:700;padding:0 5px;border-radius:8px;line-height:16px;display:none}
.tb-badge.show{display:inline-block}
.tb-user{display:flex;align-items:center;gap:7px;padding:3px 9px 3px 3px;border:1px solid rgba(255,255,255,.12);border-radius:6px;background:rgba(255,255,255,.07);cursor:pointer;transition:background .15s}
.tb-user:hover{background:rgba(255,255,255,.13)}
.tb-ava{width:26px;height:26px;border-radius:50%;background:var(--accent);display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;color:#fff}
.tb-uname{font-size:12px;font-weight:500;color:rgba(255,255,255,.85)}

/* ── Main 3-col ── */
#main{display:flex;flex:1;overflow:hidden}

/* ── Left: Categories ── */
#leftPanel{
  width:230px;flex-shrink:0;
  background:var(--white);border-right:1px solid var(--border);
  display:flex;flex-direction:column;overflow:hidden;
}
.lp-head{padding:10px 12px 8px;border-bottom:1px solid var(--border);flex-shrink:0}
.lp-head h3{font-size:10px;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:var(--text3);margin-bottom:7px}

/* Customer button */
#custBtn{
  width:100%;display:flex;align-items:center;gap:8px;
  padding:8px 10px;background:var(--bg);
  border:1px solid var(--border);border-radius:6px;
  cursor:pointer;transition:border-color .15s,background .15s;text-align:left;
}
#custBtn:hover,#custBtn.has{border-color:var(--accent);background:var(--accent-l)}
#custBtn svg{width:16px;height:16px;fill:var(--accent);flex-shrink:0}
#custName{font-size:12px;font-weight:500;color:var(--text1);flex:1;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.cust-lbl{font-size:10px;color:var(--text3);display:block;margin-top:1px}

/* Category chips */
#catTabs{flex:1;overflow-y:auto;padding:8px}
#catTabs::-webkit-scrollbar{width:3px}
#catTabs::-webkit-scrollbar-thumb{background:var(--border);border-radius:3px}
.cat-chip{
  display:flex;align-items:center;gap:9px;
  padding:8px 10px;border-radius:6px;cursor:pointer;
  transition:background .15s,color .15s;user-select:none;margin-bottom:2px;
  font-size:13px;font-weight:500;color:var(--text2);
}
.cat-chip:hover{background:var(--bg);color:var(--text1)}
.cat-chip.active{background:var(--accent-l);color:var(--accent);font-weight:600}
.cat-chip-ico{width:30px;height:30px;border-radius:6px;background:var(--bg);display:flex;align-items:center;justify-content:center;font-size:13px;flex-shrink:0}
.cat-chip.active .cat-chip-ico{background:rgba(37,99,235,.1)}

/* Held orders */
.lp-foot{padding:8px;border-top:1px solid var(--border);flex-shrink:0}
#heldBtn{
  width:100%;display:flex;align-items:center;gap:7px;padding:8px 11px;
  background:var(--yellow-bg);border:1px solid rgba(217,119,6,.2);border-radius:6px;
  color:var(--yellow);font-size:12px;font-weight:600;font-family:var(--font);cursor:pointer;
  transition:background .15s;
}
#heldBtn:hover{background:#fef3c7}
#heldCount{margin-left:auto;background:var(--yellow);color:#fff;font-size:10px;font-weight:700;padding:0 6px;border-radius:8px;line-height:16px}

/* ── Center: Products ── */
#centerPanel{flex:1;display:flex;flex-direction:column;overflow:hidden;background:var(--bg)}
.cp-toolbar{
  display:flex;align-items:center;gap:8px;padding:9px 12px;
  background:var(--white);border-bottom:1px solid var(--border);flex-shrink:0;
}
#prodsCount{font-size:12px;color:var(--text2)}
.cp-toolbar .sp{flex:1}
.sort-sel{height:28px;padding:0 8px;border:1px solid var(--border);border-radius:6px;background:var(--white);color:var(--text1);font-size:12px;font-family:var(--font);outline:none;cursor:pointer}

/* Product grid */
#productGrid{
  flex:1;overflow-y:auto;padding:12px;
  display:grid;grid-template-columns:repeat(auto-fill,minmax(148px,1fr));gap:10px;align-content:start;
}
#productGrid::-webkit-scrollbar{width:4px}
#productGrid::-webkit-scrollbar-thumb{background:var(--border);border-radius:4px}
.prod-card{
  background:var(--white);border:1.5px solid var(--border);border-radius:var(--r);
  padding:10px;cursor:pointer;transition:border-color .15s,box-shadow .15s,transform .15s;
  position:relative;user-select:none;
}
.prod-card:hover{border-color:var(--accent);box-shadow:var(--sh-md);transform:translateY(-1px)}
.prod-card:active{transform:translateY(0)}
.prod-card.out-of-stock{opacity:.5;cursor:not-allowed}
.prod-card.out-of-stock:hover{border-color:var(--border);box-shadow:none;transform:none}
.prod-img{width:100%;aspect-ratio:1;background:var(--bg);border-radius:6px;display:flex;align-items:center;justify-content:center;margin-bottom:8px;overflow:hidden;position:relative}
.prod-img img{width:100%;height:100%;object-fit:cover}
.prod-img svg{width:32px;height:32px;fill:var(--text3)}
.stk-tag{position:absolute;top:5px;right:5px;font-size:10px;font-weight:700;padding:1px 5px;border-radius:4px;line-height:16px}
.stk-ok{background:#dcfce7;color:var(--green)}
.stk-low{background:#fef3c7;color:var(--yellow)}
.stk-zero{background:#fee2e2;color:var(--red)}
.prod-name{font-size:12px;font-weight:600;color:var(--text1);line-height:1.3;margin-bottom:4px;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
.prod-price{font-size:13px;font-weight:700;color:var(--accent)}
.prod-sku{font-size:10px;color:var(--text3);margin-top:2px}
.grid-loading,.grid-empty{
  grid-column:1/-1;display:flex;flex-direction:column;
  align-items:center;justify-content:center;gap:10px;
  padding:60px 20px;color:var(--text3);text-align:center;
}
.grid-loading .spin{width:28px;height:28px;border:3px solid var(--border);border-top-color:var(--accent);border-radius:50%;animation:spin .7s linear infinite}
.grid-empty svg{width:40px;height:40px;fill:currentColor;opacity:.3}
.grid-empty p{font-size:13px}
@keyframes spin{to{transform:rotate(360deg)}}

/* ── Right: Cart ── */
#cartPanel{
  width:340px;flex-shrink:0;
  background:var(--white);border-left:1px solid var(--border);
  display:flex;flex-direction:column;overflow:hidden;
}
.cart-hd{display:flex;align-items:center;gap:8px;padding:11px 14px;border-bottom:1px solid var(--border);flex-shrink:0}
.cart-hd h3{font-size:13px;font-weight:700;flex:1}
.cart-hd-actions{display:flex;align-items:center;gap:6px}
.icon-btn{
  width:28px;height:28px;border-radius:6px;border:1px solid var(--border);
  background:none;cursor:pointer;display:flex;align-items:center;justify-content:center;
  color:var(--text2);font-size:13px;transition:background .15s,color .15s;
}
.icon-btn:hover{background:var(--bg);color:var(--text1)}
.clr-btn{border-color:rgba(220,38,38,.25);color:var(--red)}
.clr-btn:hover{background:var(--red-bg);border-color:var(--red)}

/* Cart list */
#cartList{flex:1;overflow-y:auto;padding:6px 8px}
#cartList::-webkit-scrollbar{width:3px}
#cartList::-webkit-scrollbar-thumb{background:var(--border);border-radius:3px}
.cart-empty{display:flex;flex-direction:column;align-items:center;justify-content:center;height:100%;gap:10px;color:var(--text3);text-align:center;padding:20px}
.cart-empty svg{width:36px;height:36px;fill:currentColor;opacity:.25}
.cart-empty p{font-size:12px;line-height:1.6}
.ci{
  display:flex;justify-content:space-between;gap:8px;
  padding:8px;border-radius:6px;border:1px solid transparent;margin-bottom:3px;
  transition:background .15s,border-color .15s;
}
.ci:hover{background:var(--bg);border-color:var(--border)}
.ci-nm{font-size:12px;font-weight:600;color:var(--text1);line-height:1.3;margin-bottom:2px}
.ci-px{font-size:11px;color:var(--text2)}
.ci-disc{font-size:10px;color:var(--green);font-weight:500}
.ci-idiscbtn{font-size:10px;color:var(--accent);cursor:pointer;text-decoration:underline;display:inline-block;margin-top:2px}
.ci-r{display:flex;flex-direction:column;align-items:flex-end;gap:4px;flex-shrink:0}
.ci-ctrls{display:flex;align-items:center;gap:3px}
.q-btn{
  width:22px;height:22px;border:1px solid var(--border);border-radius:4px;
  background:none;cursor:pointer;font-size:13px;font-weight:700;color:var(--text2);
  display:flex;align-items:center;justify-content:center;
  transition:background .15s,color .15s,border-color .15s;
}
.q-btn:hover{background:var(--accent);color:#fff;border-color:var(--accent)}
.q-btn.del:hover{background:var(--red);border-color:var(--red)}
.q-inp{
  width:36px;height:22px;border:1px solid var(--border);border-radius:4px;
  text-align:center;font-size:12px;font-weight:600;font-family:var(--font);outline:none;
}
.q-inp:focus{border-color:var(--accent)}
.ci-sub{font-size:13px;font-weight:700;color:var(--text1)}

/* Cart summary */
#cartSummary{padding:10px 14px;border-top:1px solid var(--border);flex-shrink:0}
.sum-row{display:flex;justify-content:space-between;font-size:12px;color:var(--text2);padding:3px 0}
#discRow,#taxRow{display:none}
#discRow{color:var(--green)}
#taxRow{color:var(--yellow)}
.sum-tot{font-size:16px;font-weight:700;color:var(--text1);padding-top:8px;margin-top:4px;border-top:1px dashed var(--border)}

/* Cart actions */
#cartActions{padding:10px 12px;border-top:1px solid var(--border);display:flex;flex-direction:column;gap:7px;flex-shrink:0}
.ca-row{display:flex;gap:7px}
.btn-hold{flex:1;height:36px;background:var(--yellow-bg);border:1px solid rgba(217,119,6,.25);color:var(--yellow);border-radius:6px;font-size:12px;font-weight:600;font-family:var(--font);cursor:pointer;transition:background .15s}
.btn-hold:hover{background:#fef3c7}
.btn-disc{flex:1;height:36px;background:var(--green-bg);border:1px solid rgba(22,163,74,.25);color:var(--green);border-radius:6px;font-size:12px;font-weight:600;font-family:var(--font);cursor:pointer;transition:background .15s}
.btn-disc:hover{background:#dcfce7}
.btn-chk{
  width:100%;height:46px;background:var(--accent);color:#fff;
  border:none;border-radius:var(--r);font-size:14px;font-weight:700;font-family:var(--font);
  cursor:pointer;display:flex;align-items:center;justify-content:center;gap:8px;
  transition:background .15s;
}
.btn-chk:hover{background:var(--accent-d)}
.btn-chk:disabled{opacity:.5;cursor:not-allowed}
.btn-chk svg{width:18px;height:18px;fill:currentColor}

/* ── Modals ── */
.modal{display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:1000;align-items:center;justify-content:center;padding:16px}
.modal.open{display:flex}
.mc{background:var(--white);border-radius:12px;width:100%;max-height:90vh;display:flex;flex-direction:column;overflow:hidden}
.mc.sm{max-width:420px}
.mc.md{max-width:540px}
.mc.lg{max-width:680px}
.mc.rcp{max-width:360px}
.mh{display:flex;align-items:center;padding:16px 20px;border-bottom:1px solid var(--border);flex-shrink:0}
.mh h3{font-size:15px;font-weight:700;flex:1}
.mc-x{width:28px;height:28px;border:none;background:none;cursor:pointer;color:var(--text2);border-radius:6px;display:flex;align-items:center;justify-content:center;font-size:18px;transition:background .15s}
.mc-x:hover{background:var(--bg);color:var(--text1)}
.mb{padding:20px;overflow-y:auto;flex:1}
.mf{display:flex;gap:8px;justify-content:flex-end;padding:14px 20px;border-top:1px solid var(--border);flex-shrink:0}
.btn-p{height:38px;padding:0 18px;background:var(--accent);color:#fff;border:none;border-radius:6px;font-size:13px;font-weight:600;font-family:var(--font);cursor:pointer;transition:background .15s}
.btn-p:hover{background:var(--accent-d)}
.btn-s{height:38px;padding:0 16px;background:none;border:1px solid var(--border);border-radius:6px;font-size:13px;font-family:var(--font);color:var(--text2);cursor:pointer;transition:background .15s}
.btn-s:hover{background:var(--bg);color:var(--text1)}
.fg{margin-bottom:14px}
.fg label{display:block;font-size:12px;font-weight:500;color:var(--text2);margin-bottom:5px}
.fc{width:100%;height:38px;padding:0 12px;border:1px solid var(--border);border-radius:6px;font-size:13px;font-family:var(--font);outline:none;color:var(--text1);transition:border-color .15s}
.fc:focus{border-color:var(--accent)}

/* Discount tabs */
.disc-tabs{display:flex;gap:6px;margin-bottom:14px}
.disc-t{flex:1;height:34px;border:1px solid var(--border);border-radius:6px;background:none;font-size:12px;font-weight:500;font-family:var(--font);color:var(--text2);cursor:pointer;transition:all .15s}
.disc-t.on{background:var(--accent);border-color:var(--accent);color:#fff}

/* Payment modal */
.pay-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:8px;margin-bottom:14px}
.pay-m{
  display:flex;flex-direction:column;align-items:center;gap:6px;padding:12px 8px;
  border:1.5px solid var(--border);border-radius:var(--r);cursor:pointer;
  transition:border-color .15s,background .15s;text-align:center;font-size:12px;font-weight:500;
}
.pay-m:hover{border-color:var(--mc,var(--accent))}
.pay-m.sel{border-color:var(--mc,var(--accent));background:color-mix(in srgb,var(--mc,var(--accent)) 8%,#fff)}
.pay-m svg{width:24px;height:24px;fill:var(--mc,var(--accent))}
.numpad{display:grid;grid-template-columns:repeat(3,1fr);gap:6px;margin:12px 0}
.np-btn{height:40px;border:1px solid var(--border);border-radius:6px;background:var(--bg);font-size:14px;font-weight:600;font-family:var(--font);cursor:pointer;transition:background .15s}
.np-btn:hover{background:var(--border)}
.chg-row{display:flex;justify-content:space-between;align-items:center;padding:10px 14px;background:var(--bg);border-radius:var(--r);margin-top:10px}
.chg-lbl{font-size:12px;color:var(--text2)}
.chg-amt{font-size:20px;font-weight:700;color:var(--green)}
.chg-amt.due{color:var(--red)}

/* Receipt */
.rcp-paper{font-family:'Courier New',monospace;font-size:12px;line-height:1.6;padding:10px}
.rcp-hd{text-align:center;margin-bottom:8px}
.rcp-div{border:none;border-top:1px dashed var(--border);margin:8px 0}
.rcp-row{display:flex;justify-content:space-between;gap:8px}
.rcp-tot{font-weight:700;font-size:14px}

/* Customer search */
#custResults{margin-top:10px}

/* Held orders */
.held-c{padding:10px;border:1px solid var(--border);border-radius:var(--r);cursor:pointer;margin-bottom:8px;transition:border-color .15s}
.held-c:hover{border-color:var(--accent)}
.hl{font-size:13px;font-weight:600}
.hi{font-size:11px;color:var(--text2);margin:3px 0}
.ht{font-size:14px;font-weight:700;color:var(--accent)}

/* Toast */
.toast-c{position:fixed;bottom:20px;right:20px;display:flex;flex-direction:column;gap:8px;z-index:9999;pointer-events:none}
.toast{
  display:flex;align-items:center;gap:8px;padding:10px 14px;
  background:var(--sidebar);color:#fff;border-radius:var(--r);
  font-size:13px;font-weight:500;box-shadow:var(--sh-md);
  animation:toastIn .2s ease;
}
.toast svg{width:16px;height:16px;fill:currentColor;flex-shrink:0}
.toast.success{background:var(--green)}
.toast.error{background:var(--red)}
.toast.warning{background:var(--yellow)}
@keyframes toastIn{from{opacity:0;transform:translateX(10px)}to{opacity:1;transform:translateX(0)}}

/* Shortcut bar */
#shortcutBar{
  position:fixed;bottom:0;left:0;right:0;
  background:rgba(17,24,39,.9);backdrop-filter:blur(6px);
  display:flex;align-items:center;justify-content:center;gap:18px;
  padding:5px 16px;font-size:11px;color:rgba(255,255,255,.5);z-index:200;
}
.sc{display:flex;align-items:center;gap:5px}
.key{
  display:inline-flex;align-items:center;justify-content:center;
  height:17px;min-width:22px;padding:0 5px;
  background:rgba(255,255,255,.1);border:1px solid rgba(255,255,255,.15);
  border-radius:3px;font-size:10px;color:rgba(255,255,255,.65);font-family:monospace;
}

/* Drawer popup */
.drawer-pop{
  position:fixed;top:70px;left:50%;transform:translateX(-50%);
  background:var(--sidebar);color:#fff;padding:12px 24px;border-radius:var(--r);
  display:flex;align-items:center;gap:10px;font-weight:600;font-size:14px;
  box-shadow:var(--sh-md);z-index:8000;animation:toastIn .2s ease;
}
.drawer-pop svg{width:20px;height:20px;fill:currentColor}

/* Shift & cash modal extra */
.shift-stat{background:var(--bg);border-radius:var(--r);padding:14px;margin-bottom:14px}
.shift-row{display:flex;justify-content:space-between;padding:4px 0;font-size:13px;color:var(--text2)}

/* Scrollbar */
.mb::-webkit-scrollbar{width:4px}
.mb::-webkit-scrollbar-thumb{background:var(--border);border-radius:4px}

/* Order note */
#orderNote{
  width:100%;padding:10px 12px;border:1px solid var(--border);border-radius:6px;
  font-size:13px;font-family:var(--font);outline:none;resize:vertical;min-height:80px;
}
#orderNote:focus{border-color:var(--accent)}

@media(max-width:768px){
  #leftPanel{display:none}
  #cartPanel{display:none;position:fixed;inset:0;z-index:300;width:100%}
  #cartPanel.mob-open{display:flex}
  #shortcutBar{display:none}
  #mob-nav{display:flex}
}
@media(min-width:769px){#mob-nav{display:none}}
#mob-nav{
  position:fixed;bottom:0;left:0;right:0;height:56px;
  background:var(--sidebar);border-top:1px solid rgba(255,255,255,.08);
  flex-shrink:0;z-index:200;
}
.mn-btn{
  flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:2px;
  background:none;border:none;color:rgba(255,255,255,.5);font-size:10px;font-weight:500;
  font-family:var(--font);cursor:pointer;transition:color .15s;height:100%;
}
.mn-btn:hover,.mn-btn.active{color:#fff}
.mn-btn svg{width:18px;height:18px;fill:currentColor}
</style>
</head>
<body>

<div id="shell">

  <!-- TOPBAR -->
  <div id="topbar">
    <a href="/nexapos/public/dashboard.php" class="brand" title="Dashboard">
      <span class="brand-ico" style="<?= $appLogo ? 'background:transparent;overflow:hidden;padding:0' : '' ?>">
        <?php if ($appLogo): ?>
          <img src="/<?= htmlspecialchars($appLogo) ?>" alt="Logo" style="width:28px;height:28px;object-fit:contain;border-radius:6px">
        <?php else: ?>
          <svg viewBox="0 0 24 24" fill="#fff" style="width:15px;height:15px"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-7 14l-5-5 1.41-1.41L12 14.17l7.59-7.59L21 8l-9 9z"/></svg>
        <?php endif; ?>
      </span>
      <?= htmlspecialchars($appName) ?>
    </a>
    <div class="tb-div"></div>
    <div class="tb-search">
      <input type="text" id="posSearch" placeholder="Search products… (F3)" autocomplete="off" spellcheck="false"
             oninput="POSProducts.onSearch(this.value)">
      <button class="scan-ico" onclick="POSScanner?.open()" title="Scan (F2)">
        <svg viewBox="0 0 24 24" fill="currentColor" style="width:14px;height:14px"><path d="M2 4h2v16H2V4zm3 0h1v16H5V4zm2 0h2v16H7V4zm3 0h1v16h-1V4zm2 0h2v16h-2V4zm3 0h1v16h-1V4zm2 0h3v16h-3V4z"/></svg>
      </button>
    </div>
    <div class="tb-actions">
      <button class="tb-btn" onclick="POSModals.openHeldOrders()">
        <svg viewBox="0 0 24 24" fill="currentColor" style="width:13px;height:13px"><path d="M11.99 2C6.47 2 2 6.48 2 12s4.47 10 9.99 10C17.52 22 22 17.52 22 12S17.52 2 11.99 2zM12 20c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8zm.5-13H11v6l5.25 3.15.75-1.23-4.5-2.67V7z"/></svg>
        Hold
        <span class="tb-badge" id="heldBadge">0</span>
      </button>
      <button class="tb-btn" onclick="location.href='/nexapos/public/orders.php'">
        <svg viewBox="0 0 24 24" fill="currentColor" style="width:13px;height:13px"><path d="M19 3H4.99C3.89 3 3 3.9 3 5l.01 14c0 1.1.89 2 1.99 2H19c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-1 11h-4v4h-4v-4H6v-4h4V6h4v4h4v4z"/></svg>
        Orders
      </button>
      <button class="tb-btn" onclick="POSModals.openShift()">
        <svg viewBox="0 0 24 24" fill="currentColor" style="width:13px;height:13px"><path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4z"/></svg>
        Shift
      </button>
      <!-- Printer status (click to configure) -->
      <button class="tb-btn" onclick="POSPrinter.openModal()" title="Printer — click to configure">
        <svg viewBox="0 0 24 24" fill="currentColor" style="width:13px;height:13px"><path d="M19 8H5c-1.66 0-3 1.34-3 3v6h4v4h12v-4h4v-6c0-1.66-1.34-3-3-3zm-3 11H8v-5h8v5zm3-7c-.55 0-1-.45-1-1s.45-1 1-1 1 .45 1 1-.45 1-1 1zm-1-9H6v4h12V3z"/></svg>
        <span id="printerStatusTxt">Printer</span>
        <span id="printerDot" style="width:7px;height:7px;border-radius:50%;background:#6b7280;display:inline-block;flex-shrink:0"></span>
      </button>
      <!-- Cash drawer manual button (shown when drawerEnabled + drawerManualBtn) -->
      <button class="tb-btn" id="drawerBtn" onclick="POSPayment.triggerDrawer(true)" title="Open Cash Drawer" style="display:none">
        <svg viewBox="0 0 24 24" fill="currentColor" style="width:13px;height:13px"><path d="M20 7H4c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V9c0-1.1-.9-2-2-2zm-1 11H5V10h14v8zM7 15h2v2H7zm4 0h2v2h-2zm4 0h2v2h-2zM3 5h18V3H3z"/></svg>
        Drawer
      </button>
      <div class="tb-div"></div>
      <div class="tb-user">
        <div class="tb-ava"><?= strtoupper(substr($user['name'] ?? 'A', 0, 1)) ?></div>
        <span class="tb-uname"><?= htmlspecialchars($user['name'] ?? 'Admin') ?></span>
      </div>
    </div>
  </div>

  <!-- MAIN -->
  <div id="main">

    <!-- LEFT: Categories -->
    <div id="leftPanel">
      <div class="lp-head">
        <h3>Customer</h3>
        <button id="custBtn" onclick="POSModals.openCustomer()">
          <svg viewBox="0 0 24 24"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
          <div>
            <span id="custName">Walk-in Customer</span>
            <span class="cust-lbl">Tap to change</span>
          </div>
        </button>
      </div>

      <div id="catTabs">
        <div class="cat-chip active" onclick="POSProducts.filterByCat(null, this)">
          <div class="cat-chip-ico">🛒</div>
          All Products
        </div>
      </div>

      <div class="lp-foot">
        <button id="heldBtn" onclick="POSModals.openHeldOrders()">
          <svg viewBox="0 0 24 24" fill="currentColor" style="width:14px;height:14px"><path d="M11.99 2C6.47 2 2 6.48 2 12s4.47 10 9.99 10C17.52 22 22 17.52 22 12S17.52 2 11.99 2zM12 20c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8zm.5-13H11v6l5.25 3.15.75-1.23-4.5-2.67V7z"/></svg>
          Held Orders
          <span id="heldCount">0</span>
        </button>
      </div>
    </div>

    <!-- CENTER: Products -->
    <div id="centerPanel">
      <div class="cp-toolbar">
        <span id="prodsCount">Loading...</span>
        <div class="sp"></div>
        <select class="sort-sel" onchange="POSProducts.sortBy(this.value)">
          <option value="name_asc">Name A–Z</option>
          <option value="name_desc">Name Z–A</option>
          <option value="price_asc">Price ↑</option>
          <option value="price_desc">Price ↓</option>
        </select>
      </div>
      <div id="productGrid">
        <div class="grid-loading"><div class="spin"></div></div>
      </div>
    </div>

    <!-- RIGHT: Cart -->
    <div id="cartPanel">
      <div class="cart-hd">
        <svg viewBox="0 0 24 24" fill="var(--accent)" style="width:16px;height:16px"><path d="M7 18c-1.1 0-1.99.9-1.99 2S5.9 22 7 22s2-.9 2-2-.9-2-2-2zM1 2v2h2l3.6 7.59-1.35 2.45c-.16.28-.25.61-.25.96C5 16.1 6.1 17 7 17h12v-2H7.42c-.14 0-.25-.11-.25-.25l.03-.12.9-1.63H19c.75 0 1.41-.41 1.75-1.03l3.58-6.49A1 1 0 0023.44 4H5.21l-.94-2H1zm16 16c-1.1 0-1.99.9-1.99 2s.89 2 1.99 2 2-.9 2-2-.9-2-2-2z"/></svg>
        <h3>Order &nbsp;<span id="cartCount" style="background:var(--accent);color:#fff;font-size:10px;font-weight:700;padding:1px 7px;border-radius:8px;vertical-align:middle">0</span></h3>
        <div class="cart-hd-actions">
          <button class="icon-btn" title="Order note" onclick="document.getElementById('noteModal').classList.add('open')">
            <svg viewBox="0 0 24 24" fill="currentColor" style="width:13px;height:13px"><path d="M19 3H4.99C3.89 3 3 3.9 3 5l.01 14c0 1.1.89 2 1.99 2h10l6-6V5c0-1.1-.9-2-2-2zm-3 15v-4h4l-4 4z"/></svg>
          </button>
          <button class="icon-btn clr-btn" title="Clear cart" onclick="POSCart.clear()">
            <svg viewBox="0 0 24 24" fill="currentColor" style="width:13px;height:13px"><path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/></svg>
          </button>
        </div>
      </div>

      <div id="cartList"></div>

      <div id="cartSummary">
        <div class="sum-row"><span>Subtotal</span><span id="subtotalDisp">৳0.00</span></div>
        <div class="sum-row" id="discRow"><span>Discount</span><span id="discDisp">-৳0.00</span></div>
        <div class="sum-row" id="taxRow"><span>Tax (<?= $taxRate ?>%)</span><span id="taxDisp">৳0.00</span></div>
        <div class="sum-row sum-tot"><span>Total</span><span id="totalDisp">৳0.00</span></div>
      </div>

      <div id="cartActions">
        <div class="ca-row">
          <button class="btn-hold" onclick="POSModals.holdOrder()">⏸ Hold</button>
          <button class="btn-disc" onclick="POSModals.openDiscount()">🏷 Discount</button>
        </div>
        <button class="btn-chk" id="checkoutBtn" disabled onclick="POSPayment.open()">
          <svg viewBox="0 0 24 24" fill="currentColor"><path d="M20 4H4c-1.11 0-2 .89-2 2v12c0 1.11.89 2 2 2h16c1.11 0 2-.89 2-2V6c0-1.11-.89-2-2-2zm0 14H4v-6h16v6zm0-10H4V6h16v2z"/></svg>
          Charge &nbsp;<span id="chargeLbl">৳0.00</span>
        </button>
      </div>
    </div>

  </div><!-- /#main -->
</div><!-- /#shell -->

<!-- Mobile nav -->
<div id="mob-nav">
  <button class="mn-btn active" id="mn-prods" onclick="mobNav('prods')">
    <svg viewBox="0 0 24 24"><path d="M10 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V8c0-1.1-.9-2-2-2h-8l-2-2z"/></svg>
    Products
  </button>
  <button class="mn-btn" id="mn-cats" onclick="mobNav('cats')">
    <svg viewBox="0 0 24 24"><path d="M3 13h2v-2H3v2zm0 4h2v-2H3v2zm0-8h2V7H3v2zm4 4h14v-2H7v2zm0 4h14v-2H7v2zM7 7v2h14V7H7z"/></svg>
    Categories
  </button>
  <button class="mn-btn" id="mn-cart" onclick="mobNav('cart')" style="position:relative">
    <svg viewBox="0 0 24 24"><path d="M7 18c-1.1 0-1.99.9-1.99 2S5.9 22 7 22s2-.9 2-2-.9-2-2-2zM1 2v2h2l3.6 7.59-1.35 2.45c-.16.28-.25.61-.25.96C5 16.1 6.1 17 7 17h12v-2H7.42c-.14 0-.25-.11-.25-.25l.03-.12.9-1.63H19c.75 0 1.41-.41 1.75-1.03l3.58-6.49A1 1 0 0023.44 4H5.21l-.94-2H1zm16 16c-1.1 0-1.99.9-1.99 2s.89 2 1.99 2 2-.9 2-2-.9-2-2-2z"/></svg>
    Cart
  </button>
</div>

<!-- Shortcut bar -->
<div id="shortcutBar">
  <div class="sc"><span class="key">F2</span>Scan</div>
  <div class="sc"><span class="key">F3</span>Search</div>
  <div class="sc"><span class="key">F4</span>Hold</div>
  <div class="sc"><span class="key">F5</span>Discount</div>
  <div class="sc"><span class="key">F9</span>Pay</div>
  <div class="sc"><span class="key">Esc</span>Close</div>
</div>

<!-- ══ MODALS ══ -->

<!-- Payment -->
<div class="modal" id="payModal">
  <div class="mc md">
    <div class="mh">
      <h3>Payment</h3>
      <button class="mc-x" onclick="POS.closeModal('payModal')">×</button>
    </div>
    <div class="mb">
      <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px">
        <span style="font-size:13px;color:var(--text2)">Total Due</span>
        <span style="font-size:22px;font-weight:700;color:var(--accent)" id="payTotalDisp">৳0.00</span>
      </div>
      <div class="pay-grid" id="payGrid"></div>
      <!-- Merchant info: shown when bKash/Nagad/Card/Bank selected -->
      <div id="payMerchantInfo" style="display:none;margin:10px 0;padding:14px 16px;background:var(--accent-bg);border:1.5px solid var(--accent);border-radius:10px">
        <div style="font-size:10px;font-weight:700;color:var(--accent);text-transform:uppercase;letter-spacing:.8px;margin-bottom:4px" id="payMerchantLabel">Send to</div>
        <div style="font-size:20px;font-weight:700;color:var(--text1);letter-spacing:1px" id="payMerchantNumber"></div>
        <div style="font-size:12px;color:var(--text2);margin-top:4px" id="payMerchantInstructions"></div>
      </div>
      <div id="payRefArea" style="display:none" class="fg">
        <label>Reference / Transaction ID</label>
        <input type="text" class="fc" id="payRefInp" placeholder="e.g. TXN123456">
      </div>
      <div id="payMobileArea" style="display:none" class="fg">
        <label>Customer Mobile (optional)</label>
        <input type="text" class="fc" id="payMobileInp" placeholder="01XXXXXXXXX">
      </div>
      <div class="fg">
        <label>Amount Received</label>
        <input type="number" class="fc" id="receivedAmt" value="0" min="0" step="0.01" style="font-size:20px;font-weight:700;text-align:center">
      </div>
      <div class="numpad">
        <?php foreach(['1','2','3','4','5','6','7','8','9','exact','0','del'] as $k): ?>
        <button class="np-btn" onclick="POSPayment.numpad('<?= $k ?>')">
          <?= $k === 'exact' ? 'Exact' : ($k === 'del' ? '⌫' : $k) ?>
        </button>
        <?php endforeach; ?>
      </div>
      <div class="chg-row">
        <span class="chg-lbl" id="changeLbl">Change Due</span>
        <span class="chg-amt" id="changeAmt">৳0.00</span>
      </div>
    </div>
    <div class="mf">
      <button class="btn-s" onclick="POS.closeModal('payModal')">Cancel</button>
      <button class="btn-p" id="procBtn" onclick="POSPayment.process()">
        Complete Sale
      </button>
    </div>
  </div>
</div>

<!-- Discount -->
<div class="modal" id="discModal">
  <div class="mc sm">
    <div class="mh"><h3>Apply Discount</h3><button class="mc-x" onclick="POS.closeModal('discModal')">×</button></div>
    <div class="mb">
      <div class="disc-tabs">
        <button class="disc-t on" onclick="POSModals.switchDiscTab('amount',this)">Amount</button>
        <button class="disc-t" onclick="POSModals.switchDiscTab('percent',this)">Percent %</button>
        <button class="disc-t" onclick="POSModals.switchDiscTab('code',this)">Promo Code</button>
      </div>
      <div id="discAmtPanel" class="fg">
        <label>Discount Amount (৳)</label>
        <input type="number" class="fc" id="discAmtInp" placeholder="0.00" min="0">
      </div>
      <div id="discPctPanel" class="fg" style="display:none">
        <label>Discount Percent (%)</label>
        <input type="number" class="fc" id="discPctInp" placeholder="0" min="0" max="100">
      </div>
      <div id="discCodePanel" class="fg" style="display:none">
        <label>Promo Code</label>
        <div style="display:flex;gap:8px">
          <input type="text" class="fc" id="discCodeInp" placeholder="Enter code">
          <button class="btn-p" onclick="POSModals.applyPromoCode()">Apply</button>
        </div>
      </div>
    </div>
    <div class="mf">
      <button class="btn-s" onclick="POS.closeModal('discModal')">Cancel</button>
      <button class="btn-p" onclick="POSModals.applyDiscount()">Apply Discount</button>
    </div>
  </div>
</div>

<!-- Customer -->
<div class="modal" id="custModal">
  <div class="mc sm">
    <div class="mh"><h3>Select Customer</h3><button class="mc-x" onclick="POS.closeModal('custModal')">×</button></div>
    <div class="mb">
      <div class="fg">
        <input type="text" class="fc" id="custSearch" placeholder="Search by name or phone…"
               oninput="POSModals.searchCustomer(this.value)">
      </div>
      <div id="custResults"></div>
      <hr style="margin:14px 0;border:none;border-top:1px dashed var(--border)">
      <div style="font-size:12px;font-weight:600;color:var(--text2);margin-bottom:10px">Quick Add</div>
      <div class="fg"><input type="text" class="fc" id="newCustName" placeholder="Customer name *"></div>
      <div class="fg"><input type="text" class="fc" id="newCustPhone" placeholder="Phone number"></div>
      <button class="btn-p" style="width:100%" onclick="POSModals.quickAddCustomer()">Add & Select</button>
      <button class="btn-s" style="width:100%;margin-top:8px" onclick="POSModals.clearCustomer();POS.closeModal('custModal')">
        Walk-in Customer
      </button>
    </div>
  </div>
</div>

<!-- Held orders -->
<div class="modal" id="heldModal">
  <div class="mc md">
    <div class="mh"><h3>Held Orders</h3><button class="mc-x" onclick="POS.closeModal('heldModal')">×</button></div>
    <div class="mb" id="heldList"></div>
  </div>
</div>

<!-- Receipt -->
<div class="modal" id="rcpModal">
  <div class="mc rcp">
    <div class="mh"><h3>Receipt</h3><button class="mc-x" onclick="POS.closeModal('rcpModal')">×</button></div>
    <!-- Print target notice -->
    <div id="rcpPrintTarget" style="display:flex;align-items:center;gap:8px;padding:8px 14px;background:var(--bg);font-size:12px;color:var(--text2);border-bottom:1px solid var(--border)">
      <span id="rcpPrinterDot" style="width:8px;height:8px;border-radius:50%;background:#6b7280;flex-shrink:0"></span>
      <span id="rcpPrinterName">No printer configured</span>
      <button onclick="POSPrinter.openModal()" style="margin-left:auto;background:none;border:1px solid var(--border);border-radius:4px;padding:2px 8px;font-size:11px;cursor:pointer;color:var(--text2)">Change</button>
    </div>
    <div class="mb" id="rcpContent"></div>
    <div class="mf" style="justify-content:space-between">
      <button class="btn-s" onclick="POSModals.printReceipt()">
        🖨 Print Receipt
      </button>
      <button class="btn-p" onclick="POSModals.newSale()" style="background:var(--green)">
        ✓ New Sale
      </button>
    </div>
  </div>
</div>

<!-- Order note -->
<div class="modal" id="noteModal">
  <div class="mc sm">
    <div class="mh"><h3>Order Note</h3><button class="mc-x" onclick="POS.closeModal('noteModal')">×</button></div>
    <div class="mb">
      <textarea id="orderNote" class="fc" placeholder="Add a note for this order…"></textarea>
    </div>
    <div class="mf">
      <button class="btn-p" onclick="POS.closeModal('noteModal')">Save</button>
    </div>
  </div>
</div>

<!-- Printer Modal -->
<div class="modal" id="printerModal">
  <div class="mc sm">
    <div class="mh">
      <h3>🖨 Printer Connection</h3>
      <button class="mc-x" onclick="POSPrinter.closeModal()">×</button>
    </div>
    <div class="mb">
      <div id="pmStatus" style="margin-bottom:14px;font-size:13px;padding:10px;background:var(--bg);border-radius:6px">
        <span style="color:#6b7280;font-weight:600">○ Not Connected</span>
      </div>
      <div class="fg">
        <label>Connection Type</label>
        <select class="fc" id="pmType">
          <option value="browser">🖥 Browser (system print dialog)</option>
          <option value="serial">🔌 USB / Serial Cable</option>
          <option value="bluetooth">📶 Bluetooth</option>
        </select>
        <div style="font-size:11px;color:var(--text3);margin-top:5px">USB and Bluetooth require Chrome or Edge browser</div>
      </div>
      <div style="background:var(--bg);border-radius:6px;padding:10px;font-size:12px;color:var(--text2);margin-bottom:12px">
        <strong>How it works:</strong><br>
        • <b>Browser</b> — opens system print dialog (works everywhere)<br>
        • <b>USB/Cable</b> — direct ESC/POS to USB printer (Chrome/Edge)<br>
        • <b>Bluetooth</b> — pair with BT thermal printer (Chrome/Edge)
      </div>
    </div>
    <div class="mf" style="gap:8px;flex-wrap:wrap">
      <button class="btn-s" onclick="POSPrinter.testPrint()" style="flex:1">Test Print</button>
      <button class="btn-s" onclick="POSPrinter.disconnect()" style="flex:1">Disconnect</button>
      <button class="btn-p" onclick="POSPrinter.connectFromModal()" style="flex:1">Connect</button>
    </div>
  </div>
</div>

<!-- Shift -->
<div class="modal" id="shiftModal">
  <div class="mc sm">
    <div class="mh"><h3>Shift Management</h3><button class="mc-x" onclick="POS.closeModal('shiftModal')">×</button></div>
    <div class="mb" id="shiftContent"></div>
  </div>
</div>

<!-- Cash in/out -->
<div class="modal" id="cashModal">
  <div class="mc sm">
    <div class="mh"><h3>Cash In / Out</h3><button class="mc-x" onclick="POS.closeModal('cashModal')">×</button></div>
    <div class="mb">
      <div class="disc-tabs">
        <button class="disc-t on" onclick="POSModals.setCashType('cash_in',this)">Cash In</button>
        <button class="disc-t" onclick="POSModals.setCashType('cash_out',this)">Cash Out</button>
      </div>
      <div class="fg"><label>Amount (৳)</label><input type="number" class="fc" id="cashAmtInp" min="0" placeholder="0.00"></div>
      <div class="fg"><label>Reason</label><input type="text" class="fc" id="cashReasonInp" placeholder="Reason…"></div>
    </div>
    <div class="mf">
      <button class="btn-s" onclick="POS.closeModal('cashModal')">Cancel</button>
      <button class="btn-p" onclick="POSModals.recordCash()">Record</button>
    </div>
  </div>
</div>

<!-- Config -->
<script>
window.NEXAPOS = {
  currency:        <?= json_encode($currency) ?>,
  appName:         <?= json_encode($appName) ?>,
  appLogo:         <?= json_encode($appLogo ? '/' . $appLogo : null) ?>,
  taxRate:         <?= json_encode((float)$taxRate) ?>,
  drawerEnabled:   <?= (DB::fetch("SELECT value FROM settings WHERE `key`='cash_drawer_enabled'")['value'] ?? '0') === '1' ? 'true' : 'false' ?>,
  drawerAuto:      <?= (DB::fetch("SELECT value FROM settings WHERE `key`='cash_drawer_auto'")['value'] ?? '0') === '1' ? 'true' : 'false' ?>,
  drawerManualBtn: <?= (DB::fetch("SELECT value FROM settings WHERE `key`='cash_drawer_manual_btn'")['value'] ?? '0') === '1' ? 'true' : 'false' ?>,
  printerEnabled:  <?= (DB::fetch("SELECT value FROM settings WHERE `key`='thermal_printer'")['value'] ?? '0') === '1' ? 'true' : 'false' ?>,
  autoPrint:       <?= (DB::fetch("SELECT value FROM settings WHERE `key`='receipt_auto_print'")['value'] ?? '0') === '1' ? 'true' : 'false' ?>,
  qrEnabled:       <?= (DB::fetch("SELECT value FROM settings WHERE `key`='qr_payment_enabled'")['value'] ?? '0') === '1' ? 'true' : 'false' ?>,
  user: { id: <?= json_encode($user['id'] ?? 0) ?>, name: <?= json_encode($user['name'] ?? '') ?> }
};
</script>

<!-- JS modules -->
<script src="/nexapos/public/assets/js/pos-core.js?v=<?= time() ?>"></script>
<script src="/nexapos/public/assets/js/pos-products.js?v=<?= time() ?>"></script>
<script src="/nexapos/public/assets/js/pos-cart.js?v=<?= time() ?>"></script>
<script src="/nexapos/public/assets/js/pos-scanner.js?v=<?= time() ?>"></script>
<script src="/nexapos/public/assets/js/pos-payment.js?v=<?= time() ?>"></script>
<script src="/nexapos/public/assets/js/pos-modals.js?v=<?= time() ?>"></script>
<script src="/nexapos/public/assets/js/pos-printer.js?v=<?= time() ?>"></script>

<script>
document.addEventListener('DOMContentLoaded', () => {
  if ('serviceWorker' in navigator) navigator.serviceWorker.register('/nexapos/sw.js').catch(()=>{});

  // Printer init
  POSPrinter.init();

  // Drawer manual button visibility
  const drawerBtn = document.getElementById('drawerBtn');
  if (drawerBtn && window.NEXAPOS?.drawerEnabled && window.NEXAPOS?.drawerManualBtn) {
    drawerBtn.style.display = '';
  }

  // Load products & categories - single init
  const origRender = POSProducts.render.bind(POSProducts);
  POSProducts.render = function(products) {
    origRender(products);
    const el = document.getElementById('prodsCount');
    if (el) el.textContent = (products?.length || 0) + ' products';
  };
  POSProducts.loadCategories();
  POSPayment.loadMethods();
  POSModals.loadHeldCount();
  POSProducts.load();

  // Block Enter in payment modal
  document.getElementById('payModal').addEventListener('keydown', function(e) {
    if (e.key === 'Enter') e.stopPropagation();
  });

  // Keyboard shortcuts
  document.addEventListener('keydown', e => {
    const tag = e.target.tagName;
    if (tag === 'INPUT' || tag === 'TEXTAREA') return;
    switch(e.key) {
      case 'F2': e.preventDefault(); POSScanner?.open(); break;
      case 'F3': e.preventDefault(); document.getElementById('posSearch')?.focus(); break;
      case 'F4': e.preventDefault(); POSModals.holdOrder(); break;
      case 'F5': e.preventDefault(); POSModals.openDiscount(); break;
      case 'F9': e.preventDefault(); POSPayment.open(); break;
      case 'Escape': document.querySelectorAll('.modal.open').forEach(m => m.classList.remove('open')); break;
    }
  });

  // Sort
  window.POSProducts = window.POSProducts || {};
  POSProducts.sortBy = function(val) {
    let prods = [...POS.allProducts];
    const [key, dir] = val.split('_');
    const field = key === 'name' ? 'name' : 'selling_price';
    prods.sort((a,b) => {
      const av = field === 'name' ? a[field]?.toLowerCase() : parseFloat(a[field]||0);
      const bv = field === 'name' ? b[field]?.toLowerCase() : parseFloat(b[field]||0);
      return dir === 'asc' ? (av > bv ? 1 : -1) : (av < bv ? 1 : -1);
    });
    POSProducts.render(prods);
  };

  // Mobile nav
  window.mobNav = function(tab) {
    document.querySelectorAll('.mn-btn').forEach(b => b.classList.remove('active'));
    document.getElementById('mn-'+tab)?.classList.add('active');
    const cart = document.getElementById('cartPanel');
    const cats = document.getElementById('leftPanel');
    const prods = document.getElementById('centerPanel');
    if (tab === 'cart') {
      cart.classList.add('mob-open');
      cats.style.display = 'none';
      prods.style.display = 'none';
    } else if (tab === 'cats') {
      cart.classList.remove('mob-open');
      cats.style.display = 'flex';
      prods.style.display = 'none';
    } else {
      cart.classList.remove('mob-open');
      cats.style.display = 'none';
      prods.style.display = 'flex';
    }
  };
});
</script>
</body>
</html>
