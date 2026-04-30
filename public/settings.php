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
<title>Settings — <?= htmlspecialchars($appName) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/layout.css">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{
  --font:'Inter',sans-serif;
  --bg:#f0f2f5;--white:#fff;--accent:#2563eb;--accent-d:#1d4ed8;--accent-l:#eff6ff;
  --sidebar:#111827;--text1:#111827;--text2:#6b7280;--text3:#9ca3af;--border:#e5e7eb;
  --red:#dc2626;--red-bg:#fef2f2;--green:#16a34a;--green-bg:#f0fdf4;
  --yellow:#d97706;--yellow-bg:#fffbeb;
  --r:8px;--sh:0 1px 3px rgba(0,0,0,.08);--sh-md:0 4px 12px rgba(0,0,0,.1);
}
html,body{height:100%;font-family:var(--font);font-size:14px;color:var(--text1);background:var(--bg)}
a{text-decoration:none;color:inherit}

/* Settings main layout */
#main{flex:1;display:flex;flex-direction:column;overflow:hidden;height:100vh}
#topbar{display:flex;align-items:center;gap:12px;padding:0 24px;height:56px;background:var(--white);border-bottom:1px solid var(--border);flex-shrink:0}
.page-title{font-size:16px;font-weight:700}
.tb-sp{flex:1}
.save-btn{display:flex;align-items:center;gap:6px;height:36px;padding:0 18px;background:var(--accent);color:#fff;border:none;border-radius:6px;font-size:13px;font-weight:600;font-family:var(--font);cursor:pointer;transition:background .15s}
.save-btn:hover{background:var(--accent-d)}
.save-btn svg{width:15px;height:15px;fill:currentColor}

#content{flex:1;display:flex;overflow:hidden}

/* Settings sidebar tabs */
#settingsTabs{
  width:200px;flex-shrink:0;background:var(--white);
  border-right:1px solid var(--border);padding:12px 8px;
  overflow-y:auto;
}
.stab{
  display:flex;align-items:center;gap:9px;padding:9px 12px;border-radius:7px;
  font-size:13px;font-weight:500;color:var(--text2);cursor:pointer;
  transition:background .15s,color .15s;margin-bottom:2px;
}
.stab:hover{background:var(--bg);color:var(--text1)}
.stab.active{background:var(--accent-l);color:var(--accent);font-weight:600}
.stab svg{width:15px;height:15px;fill:currentColor;flex-shrink:0}

/* Settings panels */
#settingsBody{flex:1;overflow-y:auto;padding:24px}
#settingsBody::-webkit-scrollbar{width:5px}
#settingsBody::-webkit-scrollbar-thumb{background:var(--border);border-radius:5px}
.s-panel{display:none}
.s-panel.active{display:block}

/* Cards */
.s-card{background:var(--white);border-radius:var(--r);box-shadow:var(--sh);margin-bottom:16px;overflow:hidden}
.s-card-head{padding:14px 20px;border-bottom:1px solid var(--border);display:flex;align-items:center;gap:8px}
.s-card-head h3{font-size:14px;font-weight:700;flex:1}
.s-card-head p{font-size:12px;color:var(--text3);margin-top:2px}
.s-card-body{padding:20px}
.s-card-ico{width:32px;height:32px;border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0}
.s-card-ico svg{width:16px;height:16px;fill:#fff}

/* Form elements */
.fg{margin-bottom:16px}
.fg:last-child{margin-bottom:0}
.fg label{display:block;font-size:12px;font-weight:600;color:var(--text2);margin-bottom:6px}
.fg .hint{font-size:11px;color:var(--text3);margin-top:4px}
.fc{width:100%;height:38px;padding:0 12px;border:1px solid var(--border);border-radius:6px;font-size:13px;font-family:var(--font);color:var(--text1);background:var(--white);outline:none;transition:border-color .15s}
.fc:focus{border-color:var(--accent)}
.fc-ta{width:100%;padding:10px 12px;border:1px solid var(--border);border-radius:6px;font-size:13px;font-family:var(--font);color:var(--text1);background:var(--white);outline:none;resize:vertical;min-height:80px;transition:border-color .15s}
.fc-ta:focus{border-color:var(--accent)}
.fg-row{display:grid;grid-template-columns:1fr 1fr;gap:14px}
.fg-row3{display:grid;grid-template-columns:1fr 1fr 1fr;gap:14px}

/* Toggle switch */
.toggle-row{display:flex;align-items:center;justify-content:space-between;padding:12px 0;border-bottom:1px solid var(--border)}
.toggle-row:last-child{border-bottom:none}
.toggle-info{flex:1}
.toggle-title{font-size:13px;font-weight:600;color:var(--text1)}
.toggle-desc{font-size:11px;color:var(--text3);margin-top:2px}
.toggle-wrap{position:relative;width:44px;height:24px;flex-shrink:0;margin-left:16px}
.toggle-wrap input{opacity:0;width:0;height:0;position:absolute}
.toggle-slider{
  position:absolute;inset:0;border-radius:24px;
  background:var(--border);cursor:pointer;
  transition:background .2s;
}
.toggle-slider::before{
  content:'';position:absolute;
  width:18px;height:18px;border-radius:50%;
  background:#fff;top:3px;left:3px;
  transition:transform .2s;
  box-shadow:0 1px 3px rgba(0,0,0,.2);
}
.toggle-wrap input:checked + .toggle-slider{background:var(--accent)}
.toggle-wrap input:checked + .toggle-slider::before{transform:translateX(20px)}

/* Image upload */
.img-upload-wrap{display:flex;align-items:center;gap:14px}
.img-preview{
  width:80px;height:80px;border-radius:var(--r);border:2px dashed var(--border);
  display:flex;align-items:center;justify-content:center;overflow:hidden;
  background:var(--bg);flex-shrink:0;cursor:pointer;transition:border-color .15s;
}
.img-preview:hover{border-color:var(--accent)}
.img-preview img{width:100%;height:100%;object-fit:contain}
.img-preview svg{width:24px;height:24px;fill:var(--text3)}
.img-upload-info{flex:1}
.img-upload-info p{font-size:12px;color:var(--text2);margin-bottom:6px}
.img-upload-info small{font-size:11px;color:var(--text3)}
.upload-btn{
  display:inline-flex;align-items:center;gap:6px;height:32px;padding:0 14px;
  border:1px solid var(--border);border-radius:6px;background:var(--white);
  font-size:12px;font-weight:500;font-family:var(--font);cursor:pointer;
  color:var(--text1);transition:all .15s;
}
.upload-btn:hover{border-color:var(--accent);color:var(--accent)}
input[type=file]{display:none}

/* QR grid */
.qr-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:16px}
.qr-item{text-align:center}
.qr-item label{display:block;font-size:12px;font-weight:600;color:var(--text2);margin-bottom:8px}
.qr-box{
  width:100%;aspect-ratio:1;border:2px dashed var(--border);border-radius:var(--r);
  display:flex;flex-direction:column;align-items:center;justify-content:center;gap:6px;
  cursor:pointer;background:var(--bg);transition:border-color .15s,background .15s;
  overflow:hidden;position:relative;
}
.qr-box:hover{border-color:var(--accent);background:var(--accent-l)}
.qr-box img{width:100%;height:100%;object-fit:contain}
.qr-box svg{width:28px;height:28px;fill:var(--text3)}
.qr-box span{font-size:11px;color:var(--text3)}
.qr-box .qr-remove{
  position:absolute;top:4px;right:4px;
  width:20px;height:20px;border-radius:50%;background:var(--red);
  display:none;align-items:center;justify-content:center;cursor:pointer;
}
.qr-box:hover .qr-remove{display:flex}
.qr-box .qr-remove svg{width:12px;height:12px;fill:#fff}

/* Drawer condition box */
.drawer-cond{
  background:var(--yellow-bg);border:1px solid rgba(217,119,6,.2);
  border-radius:var(--r);padding:12px 14px;margin-top:12px;
  display:none;
}
.drawer-cond.show{display:block}
.drawer-cond p{font-size:12px;color:var(--yellow);font-weight:500;margin-bottom:10px}

/* Role table */
.role-table{width:100%;border-collapse:collapse}
.role-table th{padding:10px 12px;text-align:left;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.05em;color:var(--text3);background:var(--bg);border-bottom:1px solid var(--border)}
.role-table td{padding:11px 12px;border-bottom:1px solid var(--border);font-size:13px;vertical-align:middle}
.role-table tr:last-child td{border-bottom:none}
.perm-chip{display:inline-flex;align-items:center;padding:2px 8px;background:var(--accent-l);color:var(--accent);border-radius:12px;font-size:11px;font-weight:500;margin:2px}

/* Toast */
.toast-c{position:fixed;bottom:20px;right:20px;display:flex;flex-direction:column;gap:8px;z-index:9999;pointer-events:none}
.toast{display:flex;align-items:center;gap:8px;padding:10px 16px;background:var(--sidebar);color:#fff;border-radius:var(--r);font-size:13px;font-weight:500;box-shadow:var(--sh-md);animation:tin .2s ease;pointer-events:none}
.toast.success{background:var(--green)}.toast.error{background:var(--red)}.toast.warning{background:var(--yellow)}
@keyframes tin{from{opacity:0;transform:translateX(10px)}to{opacity:1;transform:translateX(0)}}

/* Save indicator */
.saving{display:none;align-items:center;gap:6px;font-size:12px;color:var(--text3)}
.saving.show{display:flex}
.spin{width:14px;height:14px;border:2px solid var(--border);border-top-color:var(--accent);border-radius:50%;animation:spin .7s linear infinite}
@keyframes spin{to{transform:rotate(360deg)}}

@media(max-width:900px){
  #sidebar{display:none}
  #settingsTabs{display:none}
}
</style>
</head>
<body>
<?php require_once __DIR__ . '/includes/sidebar.php'; ?>

<div class="sb-main">

<!-- SETTINGS CONTENT -->
<div id="main">
  <div id="topbar">
    <span class="page-title">Settings</span>
    <div class="tb-sp"></div>
    <div class="saving" id="savingInd"><div class="spin"></div> Saving…</div>
    <button class="save-btn" onclick="saveAll()">
      <svg viewBox="0 0 24 24"><path d="M17 3H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V7l-4-4zm-5 16c-1.66 0-3-1.34-3-3s1.34-3 3-3 3 1.34 3 3-1.34 3-3 3zm3-10H5V5h10v4z"/></svg>
      Save All
    </button>
  </div>

  <div id="content">
    <!-- Settings tabs -->
    <div id="settingsTabs">
      <div class="stab active" onclick="switchTab('general')">
        <svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 17.93c-3.95-.49-7-3.85-7-7.93 0-.62.08-1.21.21-1.79L9 15v1c0 1.1.9 2 2 2v1.93zm6.9-2.54c-.26-.81-1-1.39-1.9-1.39h-1v-3c0-.55-.45-1-1-1H8v-2h2c.55 0 1-.45 1-1V7h2c1.1 0 2-.9 2-2v-.41c2.93 1.19 5 4.06 5 7.41 0 2.08-.8 3.97-2.1 5.39z"/></svg>
        General
      </div>
      <div class="stab" onclick="switchTab('hardware')">
        <svg viewBox="0 0 24 24"><path d="M20 7H4c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V9c0-1.1-.9-2-2-2zm-1 11H5V10h14v8zM7 15h2v2H7zm4 0h2v2h-2zm4 0h2v2h-2zM3 5h18V3H3z"/></svg>
        Hardware
      </div>
      <div class="stab" onclick="switchTab('payment')">
        <svg viewBox="0 0 24 24"><path d="M20 4H4c-1.11 0-2 .89-2 2v12c0 1.11.89 2 2 2h16c1.11 0 2-.89 2-2V6c0-1.11-.89-2-2-2zm0 14H4v-6h16v6zm0-10H4V6h16v2z"/></svg>
        Payment & QR
      </div>
      <div class="stab" onclick="switchTab('tax')">
        <svg viewBox="0 0 24 24"><path d="M11.8 10.9c-2.27-.59-3-1.2-3-2.15 0-1.09 1.01-1.85 2.7-1.85 1.78 0 2.44.85 2.5 2.1h2.21c-.07-1.72-1.12-3.3-3.21-3.81V3h-3v2.16c-1.94.42-3.5 1.68-3.5 3.61 0 2.31 1.91 3.46 4.7 4.13 2.5.6 3 1.48 3 2.41 0 .69-.49 1.79-2.7 1.79-2.06 0-2.87-.92-2.98-2.1h-2.2c.12 2.19 1.76 3.42 3.68 3.83V21h3v-2.15c1.95-.37 3.5-1.5 3.5-3.55 0-2.84-2.43-3.81-4.7-4.4z"/></svg>
        Tax & Currency
      </div>
      <div class="stab" onclick="switchTab('receipt')">
        <svg viewBox="0 0 24 24"><path d="M19 3H4.99C3.89 3 3 3.9 3 5l.01 14c0 1.1.89 2 1.99 2H19c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-1 11h-4v4h-4v-4H6v-4h4V6h4v4h4v4z"/></svg>
        Receipt
      </div>
      <div class="stab" onclick="switchTab('loyalty')">
        <svg viewBox="0 0 24 24"><path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg>
        Loyalty
      </div>
      <div class="stab" onclick="switchTab('branding')">
        <svg viewBox="0 0 24 24"><path d="M12 3L1 9l11 6 9-4.91V17h2V9L12 3zM5 13.18v4L12 21l7-3.82v-4L12 17l-7-3.82z"/></svg>
        Branding
      </div>
      <div class="stab" onclick="switchTab('security')">
        <svg viewBox="0 0 24 24"><path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm-6 9c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm3.1-9H8.9V6c0-1.71 1.39-3.1 3.1-3.1 1.71 0 3.1 1.39 3.1 3.1v2z"/></svg>
        Security
      </div>
    </div>

    <!-- Settings panels -->
    <div id="settingsBody">

      <!-- ══ GENERAL ══ -->
      <div class="s-panel active" id="panel-general">
        <div class="s-card">
          <div class="s-card-head">
            <div class="s-card-ico" style="background:#2563eb"><svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 17.93c-3.95-.49-7-3.85-7-7.93 0-.62.08-1.21.21-1.79L9 15v1c0 1.1.9 2 2 2v1.93zm6.9-2.54c-.26-.81-1-1.39-1.9-1.39h-1v-3c0-.55-.45-1-1-1H8v-2h2c.55 0 1-.45 1-1V7h2c1.1 0 2-.9 2-2v-.41c2.93 1.19 5 4.06 5 7.41 0 2.08-.8 3.97-2.1 5.39z"/></svg></div>
            <div><h3>Business Information</h3><p>Your shop's basic details</p></div>
          </div>
          <div class="s-card-body">
            <div class="fg-row">
              <div class="fg">
                <label>Business Name</label>
                <input type="text" class="fc" name="business_name" id="s_business_name">
              </div>
              <div class="fg">
                <label>Business Email</label>
                <input type="email" class="fc" name="business_email" id="s_business_email">
              </div>
            </div>
            <div class="fg-row">
              <div class="fg">
                <label>Phone Number</label>
                <input type="text" class="fc" name="business_phone" id="s_business_phone">
              </div>
              <div class="fg">
                <label>Timezone</label>
                <select class="fc" name="timezone" id="s_timezone">
                  <option value="Asia/Dhaka">Asia/Dhaka (BST +6)</option>
                  <option value="Asia/Kolkata">Asia/Kolkata (IST +5:30)</option>
                  <option value="UTC">UTC</option>
                  <option value="Asia/Dubai">Asia/Dubai (+4)</option>
                  <option value="Asia/Singapore">Asia/Singapore (+8)</option>
                </select>
              </div>
            </div>
            <div class="fg">
              <label>Business Address</label>
              <textarea class="fc-ta" name="business_address" id="s_business_address" rows="2"></textarea>
            </div>
            <div class="fg">
              <div class="toggle-row">
                <div class="toggle-info">
                  <div class="toggle-title">Low Stock Alerts</div>
                  <div class="toggle-desc">Notify when product stock goes below alert quantity</div>
                </div>
                <label class="toggle-wrap">
                  <input type="checkbox" name="low_stock_alert" id="s_low_stock_alert">
                  <span class="toggle-slider"></span>
                </label>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- ══ HARDWARE ══ -->
      <div class="s-panel" id="panel-hardware">
        <div class="s-card">
          <div class="s-card-head">
            <div class="s-card-ico" style="background:#7c3aed"><svg viewBox="0 0 24 24"><path d="M20 7H4c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V9c0-1.1-.9-2-2-2zm-1 11H5V10h14v8zM7 15h2v2H7zm4 0h2v2h-2zm4 0h2v2h-2zM3 5h18V3H3z"/></svg></div>
            <div><h3>Cash Drawer</h3><p>Configure automatic drawer behaviour</p></div>
          </div>
          <div class="s-card-body">
            <div class="toggle-row">
              <div class="toggle-info">
                <div class="toggle-title">Enable Cash Drawer</div>
                <div class="toggle-desc">Connect a hardware cash drawer to this terminal</div>
              </div>
              <label class="toggle-wrap">
                <input type="checkbox" name="cash_drawer_enabled" id="s_cash_drawer_enabled"
                       onchange="toggleDrawerOpts()">
                <span class="toggle-slider"></span>
              </label>
            </div>

            <div id="drawerOpts" style="display:none;margin-top:14px">
              <div class="toggle-row">
                <div class="toggle-info">
                  <div class="toggle-title">Auto-Open on Cash Sale</div>
                  <div class="toggle-desc">Drawer opens automatically when a cash payment is completed</div>
                </div>
                <label class="toggle-wrap">
                  <input type="checkbox" name="cash_drawer_auto" id="s_cash_drawer_auto"
                         onchange="toggleDrawerManual()">
                  <span class="toggle-slider"></span>
                </label>
              </div>

              <!-- Manual billing mode notice -->
              <div class="drawer-cond" id="drawerManualNotice">
                <p>⚠️ Auto-open is OFF — cashiers can complete bills without the drawer opening. They must open the drawer manually.</p>
                <div class="toggle-row" style="padding:0">
                  <div class="toggle-info">
                    <div class="toggle-title">Allow Manual Drawer Open Button</div>
                    <div class="toggle-desc">Show a "Open Drawer" button on the POS screen</div>
                  </div>
                  <label class="toggle-wrap">
                    <input type="checkbox" name="cash_drawer_manual_btn" id="s_cash_drawer_manual_btn">
                    <span class="toggle-slider"></span>
                  </label>
                </div>
              </div>

              <div class="fg" style="margin-top:14px">
                <label>Drawer Port / Device Path <span style="color:var(--text3);font-weight:400">(optional)</span></label>
                <input type="text" class="fc" name="cash_drawer_port" id="s_cash_drawer_port"
                       placeholder="e.g. COM3 or /dev/ttyUSB0 — leave blank for USB/ESC-POS">
                <div class="hint">For ESC/POS USB printers with drawer kick — leave blank. For serial port drawers enter the port.</div>
              </div>
            </div>
          </div>
        </div>

        <div class="s-card">
          <div class="s-card-head">
            <div class="s-card-ico" style="background:#0369a1"><svg viewBox="0 0 24 24"><path d="M19 8H5c-1.66 0-3 1.34-3 3v6h4v4h12v-4h4v-6c0-1.66-1.34-3-3-3zm-3 11H8v-5h8v5zm3-7c-.55 0-1-.45-1-1s.45-1 1-1 1 .45 1 1-.45 1-1 1zm-1-9H6v4h12V3z"/></svg></div>
            <div><h3>Receipt Printer</h3><p>Thermal / network printer settings</p></div>
          </div>
          <div class="s-card-body">
            <div class="toggle-row">
              <div class="toggle-info">
                <div class="toggle-title">Thermal Printer</div>
                <div class="toggle-desc">Use ESC/POS thermal receipt printer</div>
              </div>
              <label class="toggle-wrap">
                <input type="checkbox" name="thermal_printer" id="s_thermal_printer">
                <span class="toggle-slider"></span>
              </label>
            </div>
            <div class="toggle-row">
              <div class="toggle-info">
                <div class="toggle-title">Auto-Print Receipt</div>
                <div class="toggle-desc">Automatically print receipt after every sale</div>
              </div>
              <label class="toggle-wrap">
                <input type="checkbox" name="receipt_auto_print" id="s_receipt_auto_print">
                <span class="toggle-slider"></span>
              </label>
            </div>
            <!-- Hardware test buttons -->
            <div style="margin-top:16px;padding-top:14px;border-top:1px solid var(--border)">
              <div style="font-size:12px;font-weight:600;color:var(--text2);margin-bottom:10px;text-transform:uppercase;letter-spacing:.5px">Hardware Test</div>
              <div style="display:flex;gap:10px;flex-wrap:wrap">
                <button type="button" class="btn btn-secondary" style="gap:6px;font-size:13px" onclick="hwTestPrint()">
                  <svg viewBox="0 0 24 24" fill="currentColor" style="width:14px;height:14px"><path d="M19 8H5c-1.66 0-3 1.34-3 3v6h4v4h12v-4h4v-6c0-1.66-1.34-3-3-3zm-3 11H8v-5h8v5zm3-7c-.55 0-1-.45-1-1s.45-1 1-1 1 .45 1 1-.45 1-1 1zm-1-9H6v4h12V3z"/></svg>
                  Test Print
                </button>
                <button type="button" class="btn btn-secondary" style="gap:6px;font-size:13px" onclick="hwTestDrawer()">
                  <svg viewBox="0 0 24 24" fill="currentColor" style="width:14px;height:14px"><path d="M20 7H4c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V9c0-1.1-.9-2-2-2zm-1 11H5V10h14v8zM7 15h2v2H7zm4 0h2v2h-2zm4 0h2v2h-2zM3 5h18V3H3z"/></svg>
                  Test Drawer
                </button>
              </div>
              <div id="hwTestResult" style="display:none;margin-top:10px;padding:8px 12px;border-radius:8px;font-size:13px"></div>
            </div>
          </div>
        </div>

        <!-- Barcode Scanner -->
        <div class="s-card">
          <div class="s-card-head">
            <div class="s-card-ico" style="background:#0e7490"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M2 4h2v16H2zm3 0h1v16H5zm2 0h2v16H7zm3 0h1v16h-1zm3 0h2v16h-2zm3 0h1v16h-1zm2 0h2v16h-2zM1 3v18h22V3H1zm20 16H3V5h18v14z"/></svg></div>
            <div><h3>Barcode Scanner</h3><p>Configure how barcodes are read in the POS</p></div>
          </div>
          <div class="s-card-body">
            <div class="toggle-row">
              <div class="toggle-info">
                <div class="toggle-title">Auto-Scan Mode</div>
                <div class="toggle-desc">USB/Bluetooth scanner adds items to cart automatically when barcode is scanned</div>
              </div>
              <label class="toggle-wrap">
                <input type="checkbox" name="barcode_auto_scan" id="s_barcode_auto_scan" checked>
                <span class="toggle-slider"></span>
              </label>
            </div>
            <div class="toggle-row">
              <div class="toggle-info">
                <div class="toggle-title">Manual Entry Mode</div>
                <div class="toggle-desc">Allow typing barcode/SKU/name in the search box to add products</div>
              </div>
              <label class="toggle-wrap">
                <input type="checkbox" name="barcode_manual_entry" id="s_barcode_manual_entry" checked>
                <span class="toggle-slider"></span>
              </label>
            </div>
            <!-- Scanner test -->
            <div style="margin-top:16px;padding-top:14px;border-top:1px solid var(--border)">
              <div style="font-size:12px;font-weight:600;color:var(--text2);margin-bottom:10px;text-transform:uppercase;letter-spacing:.5px">Scanner Test</div>
              <div style="font-size:13px;color:var(--text2);margin-bottom:8px">Click "Start Test", then scan a barcode — the result will appear below.</div>
              <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap">
                <button type="button" class="btn btn-secondary" style="font-size:13px" id="scanTestBtn" onclick="toggleScanTest()">Start Scanner Test</button>
                <div id="scanTestStatus" style="font-size:12px;color:var(--text2)"></div>
              </div>
              <div id="scanTestOutput" style="display:none;margin-top:10px;padding:10px 14px;background:var(--bg);
                   border:1px solid var(--border);border-radius:8px;font-family:monospace;font-size:15px;letter-spacing:1px;
                   min-height:36px;color:var(--accent)">Waiting for scan…</div>
            </div>
          </div>
        </div>
      </div>

      <!-- ══ PAYMENT & QR ══ -->
      <div class="s-panel" id="panel-payment">
        <div class="s-card">
          <div class="s-card-head">
            <div class="s-card-ico" style="background:#16a34a"><svg viewBox="0 0 24 24"><path d="M20 4H4c-1.11 0-2 .89-2 2v12c0 1.11.89 2 2 2h16c1.11 0 2-.89 2-2V6c0-1.11-.89-2-2-2zm0 14H4v-6h16v6zm0-10H4V6h16v2z"/></svg></div>
            <div><h3>Mobile Payment QR Codes</h3><p>Upload your bKash, Nagad, Rocket QR codes — shown to customers at checkout</p></div>
          </div>
          <div class="s-card-body">
            <div class="toggle-row" style="margin-bottom:20px">
              <div class="toggle-info">
                <div class="toggle-title">Enable QR Payment Display</div>
                <div class="toggle-desc">Show QR codes on the payment screen during checkout</div>
              </div>
              <label class="toggle-wrap">
                <input type="checkbox" name="qr_payment_enabled" id="s_qr_payment_enabled">
                <span class="toggle-slider"></span>
              </label>
            </div>

            <div class="qr-grid">
              <!-- bKash -->
              <div class="qr-item">
                <label>bKash QR</label>
                <div class="qr-box" id="bkash_preview" onclick="document.getElementById('bkash_file').click()">
                  <svg viewBox="0 0 24 24"><path d="M3 3h7v7H3zm0 11h7v7H3zm11-11h7v7h-7zm0 11h7v7h-7z"/></svg>
                  <span>Tap to upload</span>
                  <div class="qr-remove" onclick="removeQR(event,'bkash')">
                    <svg viewBox="0 0 24 24"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>
                  </div>
                </div>
                <input type="file" id="bkash_file" accept="image/*" onchange="previewQR(this,'bkash')">
                <input type="hidden" id="s_bkash_qr_image" name="bkash_qr_image">
              </div>
              <!-- Nagad -->
              <div class="qr-item">
                <label>Nagad QR</label>
                <div class="qr-box" id="nagad_preview" onclick="document.getElementById('nagad_file').click()">
                  <svg viewBox="0 0 24 24"><path d="M3 3h7v7H3zm0 11h7v7H3zm11-11h7v7h-7zm0 11h7v7h-7z"/></svg>
                  <span>Tap to upload</span>
                  <div class="qr-remove" onclick="removeQR(event,'nagad')">
                    <svg viewBox="0 0 24 24"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>
                  </div>
                </div>
                <input type="file" id="nagad_file" accept="image/*" onchange="previewQR(this,'nagad')">
                <input type="hidden" id="s_nagad_qr_image" name="nagad_qr_image">
              </div>
              <!-- Rocket -->
              <div class="qr-item">
                <label>Rocket QR</label>
                <div class="qr-box" id="rocket_preview" onclick="document.getElementById('rocket_file').click()">
                  <svg viewBox="0 0 24 24"><path d="M3 3h7v7H3zm0 11h7v7H3zm11-11h7v7h-7zm0 11h7v7h-7z"/></svg>
                  <span>Tap to upload</span>
                  <div class="qr-remove" onclick="removeQR(event,'rocket')">
                    <svg viewBox="0 0 24 24"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>
                  </div>
                </div>
                <input type="file" id="rocket_file" accept="image/*" onchange="previewQR(this,'rocket')">
                <input type="hidden" id="s_rocket_qr_image" name="rocket_qr_image">
              </div>
            </div>
            <p style="font-size:11px;color:var(--text3);margin-top:12px">
              Accepted: JPG, PNG, GIF — max 2MB each. These appear as a popup when the cashier selects bKash/Nagad/Rocket as payment method.
            </p>
          </div>
        </div>

        <!-- Payment Method Account Numbers -->
        <div class="s-card">
          <div class="s-card-head">
            <div class="s-card-ico" style="background:#0369a1"><svg viewBox="0 0 24 24"><path d="M11.8 10.9c-2.27-.59-3-1.2-3-2.15 0-1.09 1.01-1.85 2.7-1.85 1.78 0 2.44.85 2.5 2.1h2.21c-.07-1.72-1.12-3.3-3.21-3.81V3h-3v2.16c-1.94.42-3.5 1.68-3.5 3.61 0 2.31 1.91 3.46 4.7 4.13 2.5.6 3 1.48 3 2.41 0 .69-.49 1.79-2.7 1.79-2.06 0-2.87-.92-2.98-2.1h-2.2c.12 2.19 1.76 3.42 3.68 3.83V21h3v-2.15c1.95-.37 3.5-1.5 3.5-3.55 0-2.84-2.43-3.81-4.7-4.4z"/></svg></div>
            <div>
              <h3>Payment Method Setup</h3>
              <p>Account numbers & instructions shown to customers at checkout</p>
            </div>
          </div>
          <div class="s-card-body">
            <p style="font-size:12px;color:var(--text2);margin-bottom:16px;line-height:1.6">
              Enter your <strong>merchant numbers</strong> for bKash/Nagad/Rocket so cashiers can tell customers where to send money.
              For <strong>card payment</strong>, add instructions (e.g. "Use Visa/Mastercard terminal on counter").
              For <strong>bank transfer</strong>, enter your bank account number.
            </p>
            <div id="pmList" style="display:flex;flex-direction:column;gap:16px">
              <div style="text-align:center;padding:20px;color:var(--text3)">Loading…</div>
            </div>
          </div>
        </div>

        <!-- MFS API Integration -->
        <div class="s-card">
          <div class="s-card-head">
            <div class="s-card-ico" style="background:#e91e63"><svg viewBox="0 0 24 24"><path d="M17 1.01L7 1c-1.1 0-2 .9-2 2v18c0 1.1.9 2 2 2h10c1.1 0 2-.9 2-2V3c0-1.1-.9-1.99-2-1.99zM17 19H7V5h10v14z"/></svg></div>
            <div>
              <h3>MFS API Integration</h3>
              <p>Auto-confirm bKash &amp; Nagad payments without manual TrxID entry</p>
            </div>
          </div>
          <div class="s-card-body">

            <div style="background:#fffbeb;border:1px solid #fde68a;border-radius:8px;padding:12px 14px;margin-bottom:20px;font-size:12px;line-height:1.6">
              <strong>Live server required for callbacks.</strong> On localhost, use ngrok so bKash/Nagad can send payment confirmations.
              API credentials are obtained from your bKash merchant account or Nagad developer portal.
            </div>

            <!-- bKash -->
            <div style="border:1px solid var(--border);border-radius:10px;padding:16px;margin-bottom:16px">
              <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px">
                <div style="display:flex;align-items:center;gap:10px">
                  <div style="width:36px;height:36px;border-radius:8px;background:#e91e63;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:800;font-size:12px">bK</div>
                  <div>
                    <div style="font-weight:700;font-size:13px">bKash Merchant API</div>
                    <div style="font-size:11px;color:var(--text3)">Tokenized Checkout — customer pays, POS auto-confirms</div>
                  </div>
                </div>
                <label class="toggle-wrap">
                  <input type="checkbox" name="bkash_enabled" id="s_bkash_enabled">
                  <span class="toggle-slider"></span>
                </label>
              </div>
              <div id="bkashApiFields">
                <div class="toggle-row" style="margin-bottom:12px">
                  <div class="toggle-info">
                    <div class="toggle-title" style="font-size:12px">Sandbox Mode</div>
                    <div class="toggle-desc">Use bKash sandbox for testing (disable on live server)</div>
                  </div>
                  <label class="toggle-wrap">
                    <input type="checkbox" name="bkash_sandbox" id="s_bkash_sandbox">
                    <span class="toggle-slider"></span>
                  </label>
                </div>
                <div class="fg-row">
                  <div class="fg">
                    <label>App Key</label>
                    <input type="text" class="fc" name="bkash_app_key" id="s_bkash_app_key" placeholder="Your bKash App Key">
                  </div>
                  <div class="fg">
                    <label>App Secret</label>
                    <input type="password" class="fc" name="bkash_app_secret" id="s_bkash_app_secret" placeholder="Your bKash App Secret">
                  </div>
                </div>
                <div class="fg-row">
                  <div class="fg">
                    <label>Username</label>
                    <input type="text" class="fc" name="bkash_username" id="s_bkash_username" placeholder="Merchant API Username">
                  </div>
                  <div class="fg">
                    <label>Password</label>
                    <input type="password" class="fc" name="bkash_password" id="s_bkash_password" placeholder="Merchant API Password">
                  </div>
                </div>
                <div style="font-size:11px;color:var(--text3);margin-top:4px">
                  Get credentials: <strong>merchant.bkash.com</strong> → Developer → API Credentials
                </div>
              </div>
            </div>

            <!-- Nagad -->
            <div style="border:1px solid var(--border);border-radius:10px;padding:16px">
              <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px">
                <div style="display:flex;align-items:center;gap:10px">
                  <div style="width:36px;height:36px;border-radius:8px;background:#f97316;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:800;font-size:12px">NG</div>
                  <div>
                    <div style="font-weight:700;font-size:13px">Nagad Merchant API</div>
                    <div style="font-size:11px;color:var(--text3)">DFS Checkout API — RSA-based merchant payment</div>
                  </div>
                </div>
                <label class="toggle-wrap">
                  <input type="checkbox" name="nagad_enabled" id="s_nagad_enabled">
                  <span class="toggle-slider"></span>
                </label>
              </div>
              <div id="nagadApiFields">
                <div class="toggle-row" style="margin-bottom:12px">
                  <div class="toggle-info">
                    <div class="toggle-title" style="font-size:12px">Sandbox Mode</div>
                    <div class="toggle-desc">Use Nagad sandbox for testing</div>
                  </div>
                  <label class="toggle-wrap">
                    <input type="checkbox" name="nagad_sandbox" id="s_nagad_sandbox">
                    <span class="toggle-slider"></span>
                  </label>
                </div>
                <div class="fg-row">
                  <div class="fg">
                    <label>Merchant ID</label>
                    <input type="text" class="fc" name="nagad_merchant_id" id="s_nagad_merchant_id" placeholder="e.g. 683002007104225">
                  </div>
                </div>
                <div class="fg">
                  <label>Merchant Private Key (RSA, base64)</label>
                  <textarea class="fct" name="nagad_merchant_key" id="s_nagad_merchant_key" placeholder="Paste your RSA private key (without -----BEGIN/END----- headers)" style="height:80px;font-size:11px;font-family:monospace"></textarea>
                </div>
                <div class="fg">
                  <label>Nagad Public Key (base64)</label>
                  <textarea class="fct" name="nagad_public_key" id="s_nagad_public_key" placeholder="Paste Nagad's RSA public key (from developer portal)" style="height:80px;font-size:11px;font-family:monospace"></textarea>
                </div>
                <div style="font-size:11px;color:var(--text3);margin-top:4px">
                  Get credentials: <strong>nagad.com.bd</strong> → Developer → Merchant Account
                </div>
              </div>
            </div>

          </div>
        </div>
      </div>

      <!-- ══ VAT & CURRENCY ══ -->
      <div class="s-panel" id="panel-tax">
        <div class="s-card">
          <div class="s-card-head">
            <div class="s-card-ico" style="background:#d97706"><svg viewBox="0 0 24 24"><path d="M11.8 10.9c-2.27-.59-3-1.2-3-2.15 0-1.09 1.01-1.85 2.7-1.85 1.78 0 2.44.85 2.5 2.1h2.21c-.07-1.72-1.12-3.3-3.21-3.81V3h-3v2.16c-1.94.42-3.5 1.68-3.5 3.61 0 2.31 1.91 3.46 4.7 4.13 2.5.6 3 1.48 3 2.41 0 .69-.49 1.79-2.7 1.79-2.06 0-2.87-.92-2.98-2.1h-2.2c.12 2.19 1.76 3.42 3.68 3.83V21h3v-2.15c1.95-.37 3.5-1.5 3.5-3.55 0-2.84-2.43-3.81-4.7-4.4z"/></svg></div>
            <div><h3>VAT & Currency</h3><p>Value Added Tax rates and currency format</p></div>
          </div>
          <div class="s-card-body">
            <div class="toggle-row">
              <div class="toggle-info">
                <div class="toggle-title">Enable VAT</div>
                <div class="toggle-desc">Apply VAT on sales — per-product VAT rates override this default</div>
              </div>
              <label class="toggle-wrap">
                <input type="checkbox" name="tax_enabled" id="s_tax_enabled">
                <span class="toggle-slider"></span>
              </label>
            </div>
            <div class="fg-row" style="margin-top:14px">
              <div class="fg">
                <label>Default VAT Rate (%)</label>
                <input type="number" class="fc" name="tax_rate" id="s_tax_rate" min="0" max="100" step="0.01" placeholder="0">
                <div class="hint">Used when a product has no specific VAT rate set (e.g. standard 15%)</div>
              </div>
              <div class="fg">
                <label>VAT Label</label>
                <select class="fc" name="vat_label" id="s_vat_label">
                  <option value="VAT">VAT (Value Added Tax)</option>
                  <option value="Tax">Tax</option>
                  <option value="GST">GST</option>
                </select>
              </div>
            </div>
            <div class="toggle-row" style="margin-top:4px">
              <div class="toggle-info">
                <div class="toggle-title">VAT Inclusive by Default</div>
                <div class="toggle-desc">Prices already include VAT — it is shown separately but not added on top</div>
              </div>
              <label class="toggle-wrap">
                <input type="checkbox" name="tax_inclusive_default" id="s_tax_inclusive_default">
                <span class="toggle-slider"></span>
              </label>
            </div>
            <div class="fg-row" style="margin-top:14px">
              <div class="fg">
                <label>Currency Code</label>
                <input type="text" class="fc" name="currency" id="s_currency" placeholder="BDT" maxlength="5">
              </div>
              <div class="fg">
                <label>Currency Symbol</label>
                <input type="text" class="fc" name="currency_symbol" id="s_currency_symbol" placeholder="৳" maxlength="5">
              </div>
            </div>
            <p style="font-size:11px;color:var(--text3);margin-top:8px;line-height:1.5">
              Bangladesh standard VAT: <strong>15%</strong> (exclusive, added on top). Reduced rates: 5%, 7.5%. Zero-rated / exempt items set per product.
            </p>
          </div>
        </div>
      </div>

      <!-- ══ RECEIPT ══ -->
      <div class="s-panel" id="panel-receipt">
        <div class="s-card">
          <div class="s-card-head">
            <div class="s-card-ico" style="background:#0891b2"><svg viewBox="0 0 24 24"><path d="M19 3H4.99C3.89 3 3 3.9 3 5l.01 14c0 1.1.89 2 1.99 2H19c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-1 11h-4v4h-4v-4H6v-4h4V6h4v4h4v4z"/></svg></div>
            <div><h3>Receipt Settings</h3><p>Customise what appears on printed receipts</p></div>
          </div>
          <div class="s-card-body">
            <div class="fg-row">
              <div class="fg">
                <label>Invoice Number Prefix</label>
                <input type="text" class="fc" name="invoice_prefix" id="s_invoice_prefix" placeholder="INV-">
              </div>
              <div class="fg">
                <label>Starting Invoice Number</label>
                <input type="number" class="fc" name="invoice_start" id="s_invoice_start" min="1" placeholder="1000">
              </div>
            </div>
            <div class="toggle-row">
              <div class="toggle-info">
                <div class="toggle-title">Show Logo on Receipt</div>
                <div class="toggle-desc">Print business logo at the top of receipts</div>
              </div>
              <label class="toggle-wrap">
                <input type="checkbox" name="receipt_show_logo" id="s_receipt_show_logo">
                <span class="toggle-slider"></span>
              </label>
            </div>
            <div class="fg" style="margin-top:14px">
              <label>Receipt Footer Message</label>
              <textarea class="fc-ta" name="receipt_footer" id="s_receipt_footer" rows="2"
                        placeholder="Thank you for shopping with us!"></textarea>
              <div class="hint">Printed at the bottom of every receipt</div>
            </div>
          </div>
        </div>
      </div>

      <!-- ══ LOYALTY ══ -->
      <div class="s-panel" id="panel-loyalty">
        <div class="s-card">
          <div class="s-card-head">
            <div class="s-card-ico" style="background:#db2777"><svg viewBox="0 0 24 24"><path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg></div>
            <div><h3>Loyalty Programme</h3><p>Reward customers with points on purchases</p></div>
          </div>
          <div class="s-card-body">
            <div class="toggle-row">
              <div class="toggle-info">
                <div class="toggle-title">Enable Loyalty Points</div>
                <div class="toggle-desc">Customers earn points on every purchase</div>
              </div>
              <label class="toggle-wrap">
                <input type="checkbox" name="loyalty_enabled" id="s_loyalty_enabled">
                <span class="toggle-slider"></span>
              </label>
            </div>
            <div class="fg-row" style="margin-top:14px">
              <div class="fg">
                <label>Points per ৳1 spent</label>
                <input type="number" class="fc" name="points_per_amount" id="s_points_per_amount" min="0" step="0.1" placeholder="10">
                <div class="hint">e.g. 10 = customer earns 10 points per ৳1</div>
              </div>
              <div class="fg">
                <label>Points value (৳ per point)</label>
                <input type="number" class="fc" name="points_value" id="s_points_value" min="0" step="0.01" placeholder="0.01">
                <div class="hint">e.g. 0.01 = 100 points = ৳1</div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- ══ BRANDING ══ -->
      <div class="s-panel" id="panel-branding">
        <div class="s-card">
          <div class="s-card-head">
            <div class="s-card-ico" style="background:#059669"><svg viewBox="0 0 24 24"><path d="M12 3L1 9l11 6 9-4.91V17h2V9L12 3zM5 13.18v4L12 21l7-3.82v-4L12 17l-7-3.82z"/></svg></div>
            <div><h3>Business Logo</h3><p>Used on receipts and the POS header</p></div>
          </div>
          <div class="s-card-body">
            <div class="img-upload-wrap">
              <div class="img-preview" id="logo_preview" onclick="document.getElementById('logo_file').click()">
                <svg viewBox="0 0 24 24" fill="var(--text3)"><path d="M12 3L1 9l11 6 9-4.91V17h2V9L12 3z"/></svg>
              </div>
              <div class="img-upload-info">
                <p>Upload your business logo</p>
                <small>PNG or JPG, recommended 200×200px, max 2MB</small><br><br>
                <label class="upload-btn" for="logo_file">
                  <svg viewBox="0 0 24 24" fill="currentColor" style="width:13px;height:13px"><path d="M19 9h-4V3H9v6H5l7 7 7-7zM5 18v2h14v-2H5z"/></svg>
                  Choose File
                </label>
              </div>
            </div>
            <input type="file" id="logo_file" accept="image/*" onchange="previewLogo(this)">
            <input type="hidden" id="s_business_logo" name="business_logo">
          </div>
        </div>
      </div>

      <!-- ══ SECURITY ══ -->
      <div class="s-panel" id="panel-security">

        <!-- Change own password -->
        <div class="s-card">
          <div class="s-card-head">
            <div class="s-card-ico" style="background:#7c3aed"><svg viewBox="0 0 24 24"><path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm-6 9c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm3.1-9H8.9V6c0-1.71 1.39-3.1 3.1-3.1 1.71 0 3.1 1.39 3.1 3.1v2z"/></svg></div>
            <div><h3>Change Password</h3><p>Update your own login password</p></div>
          </div>
          <div class="s-card-body" style="max-width:420px">
            <div class="form-group" style="margin-bottom:14px">
              <label style="font-size:12px;font-weight:600;color:var(--text2);display:block;margin-bottom:5px">Current Password</label>
              <div style="position:relative">
                <input type="password" id="cpCurrent" class="form-control" placeholder="Enter current password" autocomplete="current-password">
                <button type="button" onclick="togglePw('cpCurrent',this)" style="position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:var(--text3)">
                  <svg viewBox="0 0 24 24" style="width:16px;height:16px;fill:currentColor"><path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/></svg>
                </button>
              </div>
            </div>
            <div class="form-group" style="margin-bottom:14px">
              <label style="font-size:12px;font-weight:600;color:var(--text2);display:block;margin-bottom:5px">New Password <span style="font-weight:400;color:var(--text3)">(min 6 characters)</span></label>
              <div style="position:relative">
                <input type="password" id="cpNew" class="form-control" placeholder="Enter new password" autocomplete="new-password" oninput="checkPwStrength(this.value)">
                <button type="button" onclick="togglePw('cpNew',this)" style="position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:var(--text3)">
                  <svg viewBox="0 0 24 24" style="width:16px;height:16px;fill:currentColor"><path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/></svg>
                </button>
              </div>
              <!-- Strength bar -->
              <div style="margin-top:6px;height:4px;border-radius:4px;background:var(--border);overflow:hidden">
                <div id="pwStrengthBar" style="height:100%;width:0;transition:width .3s,background .3s;border-radius:4px"></div>
              </div>
              <div id="pwStrengthLabel" style="font-size:11px;color:var(--text3);margin-top:3px"></div>
            </div>
            <div class="form-group" style="margin-bottom:20px">
              <label style="font-size:12px;font-weight:600;color:var(--text2);display:block;margin-bottom:5px">Confirm New Password</label>
              <input type="password" id="cpConfirm" class="form-control" placeholder="Re-enter new password" autocomplete="new-password">
            </div>
            <button class="btn btn-primary" id="cpBtn" onclick="changePassword()">
              <svg viewBox="0 0 24 24" style="width:14px;height:14px;fill:#fff;margin-right:4px"><path d="M17 3H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V7l-4-4zm-5 16c-1.66 0-3-1.34-3-3s1.34-3 3-3 3 1.34 3 3-1.34 3-3 3zm3-10H5V5h10v4z"/></svg>
              Update Password
            </button>
          </div>
        </div>

        <!-- Admin: reset other user's password + set PIN -->
        <?php if (Auth::can('all') || Auth::can('employees')): ?>
        <div class="s-card">
          <div class="s-card-head">
            <div class="s-card-ico" style="background:#dc2626"><svg viewBox="0 0 24 24"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg></div>
            <div><h3>Reset User Password / PIN</h3><p>Admin only — reset password or set PIN for any user</p></div>
          </div>
          <div class="s-card-body" style="max-width:480px">
            <div class="form-group" style="margin-bottom:14px">
              <label style="font-size:12px;font-weight:600;color:var(--text2);display:block;margin-bottom:5px">Select User</label>
              <select class="form-control" id="rpUser" onchange="loadUserPin()"></select>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:20px">
              <div class="form-group">
                <label style="font-size:12px;font-weight:600;color:var(--text2);display:block;margin-bottom:5px">New Password</label>
                <input type="password" id="rpPass" class="form-control" placeholder="Min 6 characters" autocomplete="new-password">
              </div>
              <div class="form-group">
                <label style="font-size:12px;font-weight:600;color:var(--text2);display:block;margin-bottom:5px">
                  PIN <span style="font-weight:400;color:var(--text3)">(4–6 digits)</span>
                </label>
                <input type="text" id="rpPin" class="form-control" placeholder="e.g. 1234"
                       maxlength="6" pattern="[0-9]*" inputmode="numeric" autocomplete="off">
              </div>
            </div>
            <div style="display:flex;gap:10px">
              <button class="btn" style="background:#dc2626;color:#fff" id="rpBtn" onclick="resetUserPassword()">
                Reset Password
              </button>
              <button class="btn" style="background:var(--accent);color:#fff" id="pinBtn" onclick="setUserPin()">
                Set PIN
              </button>
            </div>
            <div id="pinCurrentInfo" style="margin-top:10px;font-size:12px;color:var(--text2)"></div>
          </div>
        </div>
        <?php endif; ?>

        <!-- Roles & Permissions Matrix -->
        <?php if (Auth::can('all') || Auth::can('employees')): ?>
        <div class="s-card">
          <div class="s-card-head">
            <div class="s-card-ico" style="background:#0891b2"><svg viewBox="0 0 24 24"><path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4zm0 4l5 2.18V11c0 3.5-2.33 6.79-5 7.93-2.67-1.14-5-4.43-5-7.93V7.18L12 5zm-2 7v4h4v-4h-4zm0-4v3h4V8h-4z"/></svg></div>
            <div><h3>Roles & Permissions</h3><p>Control what each role can access</p></div>
          </div>
          <div class="s-card-body">
            <div id="rolesPermMatrix"><div style="display:flex;align-items:center;gap:8px;padding:8px 0;color:var(--text3);font-size:13px"><div class="spin"></div> Loading roles…</div></div>
          </div>
        </div>
        <?php endif; ?>

      </div><!-- /panel-security -->

    </div><!-- /#settingsBody -->
  </div><!-- /#content -->
</div><!-- /#main -->

<div class="toast-c" id="toastWrap"></div>

<script>
const API = '../routes/api.php';

// ── Tab switching ──
function switchTab(tab) {
  document.querySelectorAll('.stab').forEach(t => t.classList.remove('active'));
  document.querySelectorAll('.s-panel').forEach(p => p.classList.remove('active'));
  event.currentTarget.classList.add('active');
  document.getElementById('panel-' + tab).classList.add('active');
  if (tab === 'security') { loadSecurityUsers(); loadRolesPermissions(); }
}

// ── Drawer toggles ──
function toggleDrawerOpts() {
  const enabled = document.getElementById('s_cash_drawer_enabled').checked;
  document.getElementById('drawerOpts').style.display = enabled ? 'block' : 'none';
}
function toggleDrawerManual() {
  const auto = document.getElementById('s_cash_drawer_auto').checked;
  document.getElementById('drawerManualNotice').classList.toggle('show', !auto);
}

// ── Hardware test ──────────────────────────────
function showHwResult(msg, ok) {
  const el = document.getElementById('hwTestResult');
  if (!el) return;
  el.textContent = msg;
  el.style.display = 'block';
  el.style.background = ok ? 'rgba(16,185,129,.12)' : 'rgba(239,68,68,.12)';
  el.style.color      = ok ? '#10b981' : '#ef4444';
  el.style.border     = '1px solid ' + (ok ? '#10b98133' : '#ef444433');
}

async function hwTestPrint() {
  showHwResult('Sending test print…', true);
  try {
    // Try to use the POS printer object if we're on the POS page
    if (typeof POSPrinter !== 'undefined') {
      await POSPrinter.testPrint();
      showHwResult('Test print sent! Check your printer.', true);
      return;
    }
  } catch {}
  // Settings page — open simple browser print
  const html = `<!DOCTYPE html><html><head><title>Test</title>
    <style>body{font-family:'Courier New',monospace;font-size:12px;padding:10px;width:76mm}</style></head>
    <body>
      <div style="text-align:center"><strong>*** NEXAPOS TEST PRINT ***</strong></div>
      <hr><div>Printer is working correctly!</div>
      <div>${new Date().toLocaleString()}</div>
      <script>window.onload=()=>{window.print();setTimeout(()=>window.close(),600)}<\/script>
    </body></html>`;
  const w = window.open('', '_blank', 'width=400,height=300');
  if (w) { w.document.write(html); showHwResult('Test print dialog opened.', true); }
  else    showHwResult('Popup blocked — allow popups and try again.', false);
}

async function hwTestDrawer() {
  showHwResult('Sending drawer open command…', true);
  if (typeof POSPayment !== 'undefined') {
    POSPayment.triggerDrawer(true);
    showHwResult('Drawer open command sent! (Animation shown)', true);
    return;
  }
  // On settings page — use Web Serial directly
  if (!navigator.serial) {
    showHwResult('Web Serial not supported in this browser. Use Chrome or Edge.', false); return;
  }
  try {
    const port   = await navigator.serial.requestPort();
    await port.open({ baudRate: 9600 });
    const writer = port.writable.getWriter();
    await writer.write(new Uint8Array([0x1B, 0x70, 0x00, 0x19, 0xFA]));
    writer.releaseLock();
    await port.close();
    showHwResult('Drawer open command sent! The drawer should have opened.', true);
  } catch (e) {
    if (e.name !== 'NotFoundError') showHwResult('Error: ' + (e.message || e), false);
    else showHwResult('No port selected.', false);
  }
}

// ── Scanner test ───────────────────────────────
let _scanTestActive = false;
let _scanTestBuffer = '';
let _scanTestTimer  = null;
let _scanTestKeyHandler = null;

function toggleScanTest() {
  const btn    = document.getElementById('scanTestBtn');
  const output = document.getElementById('scanTestOutput');
  const status = document.getElementById('scanTestStatus');
  _scanTestActive = !_scanTestActive;

  if (_scanTestActive) {
    btn.textContent    = 'Stop Test';
    btn.style.background = 'var(--red)';
    btn.style.color      = '#fff';
    output.style.display = 'block';
    output.textContent   = 'Waiting for scan…';
    if (status) status.textContent = '🟢 Listening — scan a barcode now';

    _scanTestBuffer = '';
    _scanTestKeyHandler = e => {
      if (!_scanTestActive) return;
      const now = Date.now();
      clearTimeout(_scanTestTimer);

      if (e.key === 'Enter') {
        if (_scanTestBuffer.length >= 3) {
          output.textContent = '✓ Scanned: ' + _scanTestBuffer;
          output.style.color = 'var(--accent)';
        }
        _scanTestBuffer = '';
        return;
      }
      if (e.key.length === 1) _scanTestBuffer += e.key;

      // Show what's being typed
      output.textContent = 'Reading: ' + _scanTestBuffer + '…';
      output.style.color = 'var(--text2)';

      _scanTestTimer = setTimeout(() => {
        if (_scanTestBuffer.length >= 3) {
          output.textContent = '✓ Scanned: ' + _scanTestBuffer;
          output.style.color = 'var(--accent)';
        }
        _scanTestBuffer = '';
      }, 150);
    };
    document.addEventListener('keydown', _scanTestKeyHandler);
  } else {
    btn.textContent      = 'Start Scanner Test';
    btn.style.background = '';
    btn.style.color      = '';
    output.style.display = 'none';
    if (status) status.textContent = '';
    if (_scanTestKeyHandler) document.removeEventListener('keydown', _scanTestKeyHandler);
    _scanTestKeyHandler = null;
  }
}

// ── Load settings ──
async function loadSettings() {
  const res  = await fetch(`${API}?module=settings&action=get_all`);
  const data = await res.json();
  if (!data.success) return;
  const s = data.data;

  // Text / select fields
  const textFields = [
    'business_name','business_email','business_phone','business_address',
    'timezone','currency','currency_symbol','tax_rate','invoice_prefix',
    'invoice_start','receipt_footer','points_per_amount','points_value',
    'cash_drawer_port','vat_label',
    // bKash
    'bkash_app_key','bkash_app_secret','bkash_username','bkash_password',
    // Nagad
    'nagad_merchant_id','nagad_merchant_key','nagad_public_key',
  ];
  textFields.forEach(k => {
    const el = document.getElementById('s_' + k);
    if (el && s[k] !== undefined) el.value = s[k];
  });

  // Checkboxes
  const checkFields = [
    'tax_enabled','tax_inclusive_default',
    'loyalty_enabled','receipt_auto_print','thermal_printer',
    'cash_drawer_enabled','cash_drawer_auto','cash_drawer_manual_btn',
    'receipt_show_logo','low_stock_alert','qr_payment_enabled',
    'barcode_auto_scan','barcode_manual_entry',
    'bkash_enabled','bkash_sandbox','nagad_enabled','nagad_sandbox',
  ];
  checkFields.forEach(k => {
    const el = document.getElementById('s_' + k);
    if (el) el.checked = s[k] === '1' || s[k] === 1 || s[k] === true;
  });

  // QR images
  ['bkash','nagad','rocket'].forEach(method => {
    const path = s[method + '_qr_image'];
    if (path) {
      const box = document.getElementById(method + '_preview');
      box.innerHTML = `<img src="/${path}" alt="${method} QR">
        <div class="qr-remove" onclick="removeQR(event,'${method}')">
          <svg viewBox="0 0 24 24"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>
        </div>`;
      document.getElementById('s_' + method + '_qr_image').value = path;
    }
  });

  // Logo
  const logo = s['business_logo'];
  if (logo) {
    const prev = document.getElementById('logo_preview');
    prev.innerHTML = `<img src="/${logo}" alt="Logo">`;
    document.getElementById('s_business_logo').value = logo;
  }

  // Apply drawer state
  toggleDrawerOpts();
  toggleDrawerManual();
}

// ── QR image preview ──
function previewQR(input, method) {
  const file = input.files[0];
  if (!file) return;
  if (file.size > 2 * 1024 * 1024) { toast('Image must be under 2MB', 'error'); return; }
  const reader = new FileReader();
  reader.onload = e => {
    const box = document.getElementById(method + '_preview');
    box.innerHTML = `<img src="${e.target.result}" alt="${method} QR">
      <div class="qr-remove" onclick="removeQR(event,'${method}')">
        <svg viewBox="0 0 24 24"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>
      </div>`;
    // Store base64 temporarily for upload
    box.dataset.pendingFile = 'yes';
    box.dataset.fileInput   = method + '_file';
  };
  reader.readAsDataURL(file);
}

function removeQR(e, method) {
  e.stopPropagation();
  const box = document.getElementById(method + '_preview');
  box.innerHTML = `<svg viewBox="0 0 24 24"><path d="M3 3h7v7H3zm0 11h7v7H3zm11-11h7v7h-7zm0 11h7v7h-7z"/></svg><span>Tap to upload</span>
    <div class="qr-remove" onclick="removeQR(event,'${method}')">
      <svg viewBox="0 0 24 24"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>
    </div>`;
  document.getElementById(method + '_file').value = '';
  document.getElementById('s_' + method + '_qr_image').value = '';
}

// ── Logo preview ──
function previewLogo(input) {
  const file = input.files[0];
  if (!file) return;
  if (file.size > 2 * 1024 * 1024) { toast('Logo must be under 2MB', 'error'); return; }
  const reader = new FileReader();
  reader.onload = e => {
    const prev = document.getElementById('logo_preview');
    prev.innerHTML = `<img src="${e.target.result}" alt="Logo">`;
  };
  reader.readAsDataURL(file);
}

// ── Save all ──
async function saveAll() {
  const ind = document.getElementById('savingInd');
  ind.classList.add('show');

  const fd = new FormData();

  // Upload QR images first if pending
  for (const method of ['bkash','nagad','rocket']) {
    const fileInput = document.getElementById(method + '_file');
    if (fileInput.files[0]) {
      fd.append(method + '_qr_image', fileInput.files[0]);
    }
  }

  // Upload logo if changed
  const logoFile = document.getElementById('logo_file');
  if (logoFile.files[0]) fd.append('business_logo', logoFile.files[0]);

  // Text fields
  const textFields = [
    'business_name','business_email','business_phone','business_address',
    'timezone','currency','currency_symbol','tax_rate','invoice_prefix',
    'invoice_start','receipt_footer','points_per_amount','points_value',
    'cash_drawer_port',
  ];
  textFields.forEach(k => {
    const el = document.getElementById('s_' + k);
    if (el) fd.append(k, el.value);
  });

  // Checkboxes (send 1/0)
  const checkFields = [
    'tax_enabled','loyalty_enabled','receipt_auto_print','thermal_printer',
    'cash_drawer_enabled','cash_drawer_auto','cash_drawer_manual_btn',
    'receipt_show_logo','low_stock_alert','qr_payment_enabled',
  ];
  checkFields.forEach(k => {
    const el = document.getElementById('s_' + k);
    if (el) fd.append(k, el.checked ? '1' : '0');
  });

  fd.append('action', 'save_all');

  try {
    const res  = await fetch(`${API}?module=settings&action=save_all`, { method:'POST', body:fd });
    const data = await res.json();
    if (data.success) {
      toast('Settings saved successfully', 'success');
      // Reload after short delay so sidebar/logo/name refresh from server
      setTimeout(() => location.reload(), 1200);
    } else {
      toast(data.message || 'Save failed', 'error');
      ind.classList.remove('show');
    }
  } catch(e) {
    toast('Network error', 'error');
    ind.classList.remove('show');
  }
}

// ── Toast ──
function toast(msg, type='info', dur=3000) {
  const wrap = document.getElementById('toastWrap');
  const el   = document.createElement('div');
  el.className = `toast ${type}`;
  el.textContent = msg;
  wrap.appendChild(el);
  setTimeout(() => {
    el.style.cssText = 'opacity:0;transform:translateX(10px);transition:all .25s';
    setTimeout(() => el.remove(), 260);
  }, dur);
}

// ── Payment Methods ──────────────────────────────
const PM_ICONS = {
  cash:          '💵',
  mobile_banking:'📱',
  card:          '💳',
  bank_transfer: '🏦',
  credit:        '⭐',
  other:         '💰',
};
const PM_PLACEHOLDERS = {
  mobile_banking: '01XXXXXXXXX (merchant number)',
  card:           'e.g. Visa, Mastercard accepted',
  bank_transfer:  'Bank name + Account: XXXXXXXX',
  cash:           '',
  credit:         '',
  other:          '',
};

async function loadPaymentMethods() {
  const res = await fetch(`../routes/api.php?module=settings&action=get_payment_methods`).then(r => r.json());
  const methods = res.data || [];
  const list = document.getElementById('pmList');
  if (!list) return;
  list.innerHTML = methods.map(m => `
    <div style="border:1.5px solid var(--border);border-radius:10px;padding:16px;background:#fff" id="pmCard_${m.id}">
      <div style="display:flex;align-items:center;gap:10px;margin-bottom:12px">
        <span style="font-size:20px">${PM_ICONS[m.type]||'💰'}</span>
        <span style="font-size:14px;font-weight:700;flex:1">${m.name}</span>
        <label style="display:flex;align-items:center;gap:6px;font-size:12px;cursor:pointer">
          <input type="checkbox" id="pmActive_${m.id}" ${m.is_active?'checked':''}>
          Active
        </label>
      </div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
        <div>
          <label style="font-size:11px;font-weight:600;color:var(--text2);display:block;margin-bottom:4px">
            ${m.type==='mobile_banking'?'Merchant Number':m.type==='card'?'Accepted Cards':m.type==='bank_transfer'?'Account Details':'Account / Reference'}
          </label>
          <input type="text" id="pmNum_${m.id}"
            value="${m.account_number||''}"
            placeholder="${PM_PLACEHOLDERS[m.type]||'Optional'}"
            style="width:100%;height:36px;padding:0 10px;border:1.5px solid var(--border);border-radius:6px;font-size:13px;font-family:inherit;outline:none"
            onfocus="this.style.borderColor='var(--accent)'" onblur="this.style.borderColor='var(--border)'">
        </div>
        <div>
          <label style="font-size:11px;font-weight:600;color:var(--text2);display:block;margin-bottom:4px">Instructions for cashier</label>
          <input type="text" id="pmInst_${m.id}"
            value="${m.instructions||''}"
            placeholder="Shown at checkout (optional)"
            style="width:100%;height:36px;padding:0 10px;border:1.5px solid var(--border);border-radius:6px;font-size:13px;font-family:inherit;outline:none"
            onfocus="this.style.borderColor='var(--accent)'" onblur="this.style.borderColor='var(--border)'">
        </div>
      </div>
      <div style="margin-top:10px;text-align:right">
        <button onclick="savePM(${m.id})"
          style="padding:6px 16px;background:var(--accent);color:#fff;border:none;border-radius:6px;font-size:12px;font-weight:600;cursor:pointer">
          Save
        </button>
      </div>
    </div>`).join('');
}

async function savePM(id) {
  const fd = new FormData();
  fd.append('id',             id);
  fd.append('account_number', document.getElementById(`pmNum_${id}`).value.trim());
  fd.append('instructions',   document.getElementById(`pmInst_${id}`).value.trim());
  fd.append('is_active',      document.getElementById(`pmActive_${id}`).checked ? 1 : 0);
  const res = await fetch('../routes/api.php?module=settings&action=save_payment_method', { method:'POST', body:fd }).then(r=>r.json());
  if (res.success) toast('Saved', 'ok');
  else toast(res.message || 'Error', 'err');
}

// ── Security ──────────────────────────────────────
function togglePw(id, btn) {
  const inp = document.getElementById(id);
  inp.type = inp.type === 'password' ? 'text' : 'password';
}

function checkPwStrength(pw) {
  const bar = document.getElementById('pwStrengthBar');
  const lbl = document.getElementById('pwStrengthLabel');
  if (!bar) return;
  let score = 0;
  if (pw.length >= 6)  score++;
  if (pw.length >= 10) score++;
  if (/[A-Z]/.test(pw)) score++;
  if (/[0-9]/.test(pw)) score++;
  if (/[^A-Za-z0-9]/.test(pw)) score++;
  const levels = [
    { w:'0%',   bg:'transparent', t:'' },
    { w:'25%',  bg:'#ef4444', t:'Weak' },
    { w:'50%',  bg:'#f59e0b', t:'Fair' },
    { w:'75%',  bg:'#3b82f6', t:'Good' },
    { w:'100%', bg:'#10b981', t:'Strong' },
  ];
  const lv = levels[Math.min(score, 4)];
  bar.style.width = lv.w; bar.style.background = lv.bg;
  lbl.textContent = lv.t; lbl.style.color = lv.bg;
}

async function changePassword() {
  const current = document.getElementById('cpCurrent').value.trim();
  const newPw   = document.getElementById('cpNew').value.trim();
  const confirm = document.getElementById('cpConfirm').value.trim();
  if (!current || !newPw || !confirm) { toast('All fields are required', 'error'); return; }
  if (newPw !== confirm) { toast('New passwords do not match', 'error'); return; }
  if (newPw.length < 6)  { toast('Password must be at least 6 characters', 'error'); return; }
  const btn = document.getElementById('cpBtn');
  btn.disabled = true; btn.textContent = 'Updating…';
  const fd = new FormData();
  fd.append('current_password', current);
  fd.append('new_password',     newPw);
  fd.append('confirm_password', confirm);
  const res = await fetch('../routes/api.php?module=settings&action=change_password', { method:'POST', body:fd }).then(r=>r.json());
  btn.disabled = false; btn.textContent = 'Update Password';
  if (res.success) {
    toast('Password changed successfully', 'success');
    document.getElementById('cpCurrent').value = '';
    document.getElementById('cpNew').value     = '';
    document.getElementById('cpConfirm').value = '';
    document.getElementById('pwStrengthBar').style.width = '0';
    document.getElementById('pwStrengthLabel').textContent = '';
  } else {
    toast(res.message || 'Failed to change password', 'error');
  }
}

async function loadSecurityUsers() {
  const sel = document.getElementById('rpUser');
  if (!sel) return;
  const res = await fetch('../routes/api.php?module=employees&action=list&per_page=100').then(r=>r.json());
  const users = res.data?.employees || [];
  sel.innerHTML = '<option value="">— Select user —</option>' +
    users.map(u => `<option value="${u.id}">${u.name} (${u.role_name||u.role_slug||''})</option>`).join('');
}

// ── Roles & Permissions matrix ──
const PERM_DEFS = [
  { key:'all',       label:'All (Admin)' },
  { key:'pos',       label:'POS' },
  { key:'products',  label:'Products' },
  { key:'inventory', label:'Inventory' },
  { key:'orders',    label:'Orders' },
  { key:'customers', label:'Customers' },
  { key:'reports',   label:'Reports' },
  { key:'expenses',  label:'Expenses' },
  { key:'purchases', label:'Purchases' },
  { key:'suppliers', label:'Suppliers' },
  { key:'returns',   label:'Returns' },
  { key:'employees', label:'Employees' },
  { key:'settings',  label:'Settings' },
];

async function loadRolesPermissions() {
  const el = document.getElementById('rolesPermMatrix');
  if (!el) return;
  const res = await fetch('../routes/api.php?module=employees&action=roles').then(r=>r.json());
  const roles = res.data || [];
  if (!roles.length) { el.innerHTML = '<p style="color:var(--text3);font-size:13px">No roles found.</p>'; return; }

  el.innerHTML = `
    <div style="overflow-x:auto">
      <table class="role-table" style="min-width:700px">
        <thead>
          <tr>
            <th style="min-width:120px">Role</th>
            ${PERM_DEFS.map(p=>`<th style="text-align:center;min-width:70px;font-size:10px">${p.label}</th>`).join('')}
            <th style="text-align:center">Save</th>
          </tr>
        </thead>
        <tbody>
          ${roles.map(role => {
            const perms = typeof role.permissions === 'string' ? JSON.parse(role.permissions || '{}') : (role.permissions || {});
            return `<tr id="role-row-${role.id}">
              <td><strong>${role.name}</strong><br><span style="font-size:11px;color:var(--text3)">${role.slug}</span></td>
              ${PERM_DEFS.map(p => {
                const checked = perms[p.key] ? 'checked' : '';
                const isAll = p.key === 'all';
                return `<td style="text-align:center">
                  <input type="checkbox" class="perm-cb role-${role.id}-cb" data-role="${role.id}" data-perm="${p.key}" ${checked}
                    style="width:16px;height:16px;cursor:pointer;accent-color:var(--accent)"
                    onchange="if(this.dataset.perm==='all')toggleAllPerms(${role.id},this.checked)">
                </td>`;
              }).join('')}
              <td style="text-align:center">
                <button onclick="saveRolePerms(${role.id})" style="padding:4px 12px;background:var(--accent);color:#fff;border:none;border-radius:5px;font-size:12px;font-weight:600;cursor:pointer;transition:background .15s" onmouseover="this.style.background='#1d4ed8'" onmouseout="this.style.background='var(--accent)'">Save</button>
              </td>
            </tr>`;
          }).join('')}
        </tbody>
      </table>
    </div>
    <p style="font-size:11px;color:var(--text3);margin-top:10px">⚠ "All (Admin)" grants full access regardless of other permissions. Changes take effect on next login.</p>
  `;
}

function toggleAllPerms(roleId, isAll) {
  document.querySelectorAll(`.role-${roleId}-cb`).forEach(cb => {
    if (cb.dataset.perm !== 'all') {
      cb.disabled = isAll;
      if (isAll) cb.checked = false;
    }
  });
}

async function saveRolePerms(roleId) {
  const perms = {};
  document.querySelectorAll(`.role-${roleId}-cb:checked`).forEach(cb => {
    perms[cb.dataset.perm] = true;
  });
  const fd = new FormData();
  fd.append('role_id', roleId);
  fd.append('permissions', JSON.stringify(perms));
  const res = await fetch('../routes/api.php?module=employees&action=save_role_perms', { method:'POST', body:fd }).then(r=>r.json());
  if (res.success) toast('Permissions saved — users must re-login to see changes', 'success');
  else toast(res.message || 'Failed to save', 'error');
}

async function resetUserPassword() {
  const uid  = document.getElementById('rpUser').value;
  const pass = document.getElementById('rpPass').value.trim();
  if (!uid)          { toast('Select a user', 'error'); return; }
  if (pass.length<6) { toast('Password min 6 characters', 'error'); return; }
  if (!confirm('Reset password for this user?')) return;
  const btn = document.getElementById('rpBtn');
  btn.disabled = true; btn.textContent = 'Resetting…';
  const fd = new FormData();
  fd.append('id', uid); fd.append('password', pass);
  const res = await fetch('../routes/api.php?module=employees&action=reset_password', { method:'POST', body:fd }).then(r=>r.json());
  btn.disabled = false; btn.textContent = 'Reset Password';
  if (res.success) { toast('Password reset successfully', 'success'); document.getElementById('rpPass').value=''; }
  else toast(res.message || 'Failed', 'error');
}

async function setUserPin() {
  const uid = document.getElementById('rpUser').value;
  const pin = document.getElementById('rpPin').value.trim();
  if (!uid) { toast('Select a user', 'error'); return; }
  if (!/^\d{4,6}$/.test(pin)) { toast('PIN must be 4–6 digits', 'error'); return; }
  if (!confirm('Set PIN for this user?')) return;
  const btn = document.getElementById('pinBtn');
  btn.disabled = true; btn.textContent = 'Saving…';
  const fd = new FormData();
  fd.append('id', uid); fd.append('pin', pin);
  const res = await fetch('../routes/api.php?module=employees&action=set_pin', { method:'POST', body:fd }).then(r=>r.json());
  btn.disabled = false; btn.textContent = 'Set PIN';
  if (res.success) {
    toast('PIN set successfully', 'success');
    document.getElementById('rpPin').value = '';
    document.getElementById('pinCurrentInfo').textContent = 'PIN is set for this user.';
  } else toast(res.message || 'Failed', 'error');
}

async function loadUserPin() {
  const uid = document.getElementById('rpUser').value;
  const info = document.getElementById('pinCurrentInfo');
  if (!uid || !info) return;
  const res = await fetch(`../routes/api.php?module=employees&action=get&id=${uid}`).then(r=>r.json());
  if (res.success) {
    info.textContent = res.data?.pin ? '● PIN is already set for this user' : '○ No PIN set for this user';
  }
}

// Init
loadSettings();
loadPaymentMethods();
loadSecurityUsers();
</script>
</div><!-- /.sb-main -->
</body>
</html>
