<?php
require_once dirname(__DIR__) . '/bootstrap.php';
if (Auth::check()) Response::redirect(app_url('public/dashboard.php'));

$error  = '';
$method = '';   // 'password' | 'pin'

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $method = $_POST['method'] ?? 'password';

    if ($method === 'pin') {
        $pin    = trim($_POST['pin'] ?? '');
        $result = Auth::attemptPin($pin);
        if ($result['success']) Response::redirect(app_url('public/dashboard.php'));
        $error = $result['message'];
    } else {
        $v = Validator::make($_POST, ['email' => 'required|email', 'password' => 'required|min:4']);
        if ($v->fails()) { $error = $v->firstError(); }
        else {
            $result = Auth::attempt(trim($_POST['email']), $_POST['password']);
            if ($result['success']) Response::redirect(app_url('public/dashboard.php'));
            $error = $result['message'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<?php include __DIR__ . '/includes/pwa.php'; ?>
<title>Sign In — NexaPOS</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{
  --bg:#0a0f1e;
  --card:#111827;
  --card2:#1a2236;
  --border:rgba(255,255,255,.08);
  --border2:rgba(255,255,255,.14);
  --accent:#6366f1;
  --accent2:#818cf8;
  --accent-glow:rgba(99,102,241,.35);
  --green:#10b981;
  --red:#ef4444;
  --text1:#f9fafb;
  --text2:#9ca3af;
  --text3:#4b5563;
  --font:'Inter',-apple-system,sans-serif;
  --r:12px;
}
html,body{height:100%;font-family:var(--font);-webkit-font-smoothing:antialiased;background:var(--bg);color:var(--text1)}

/* ── Animated background ── */
.bg-wrap{position:fixed;inset:0;z-index:0;overflow:hidden}
.orb{position:absolute;border-radius:50%;filter:blur(80px);opacity:.35;animation:drift 12s ease-in-out infinite alternate}
.orb-1{width:600px;height:600px;background:radial-gradient(circle,#6366f1,transparent);top:-200px;left:-100px;animation-duration:14s}
.orb-2{width:500px;height:500px;background:radial-gradient(circle,#8b5cf6,transparent);bottom:-150px;right:-100px;animation-duration:10s;animation-delay:-4s}
.orb-3{width:300px;height:300px;background:radial-gradient(circle,#06b6d4,transparent);top:40%;left:50%;animation-duration:16s;animation-delay:-8s}
@keyframes drift{
  0%{transform:translate(0,0) scale(1)}
  100%{transform:translate(40px,30px) scale(1.08)}
}

/* ── Grid ── */
.page{position:relative;z-index:1;min-height:100vh;display:flex;align-items:stretch}

/* ── Left panel ── */
.lp{
  flex:1;display:flex;align-items:center;justify-content:center;
  padding:60px 72px;
}
.lp-inner{max-width:480px;width:100%}

.brand{display:flex;align-items:center;gap:12px;margin-bottom:64px}
.brand-ico{
  width:44px;height:44px;border-radius:12px;
  background:linear-gradient(135deg,#6366f1,#8b5cf6);
  display:grid;place-items:center;
  box-shadow:0 0 20px var(--accent-glow);
}
.brand-ico svg{width:22px;height:22px;fill:#fff}
.brand-name{font-size:19px;font-weight:700;letter-spacing:-.3px}

.lp-headline{
  font-size:42px;font-weight:800;line-height:1.15;letter-spacing:-1.5px;margin-bottom:16px;
  background:linear-gradient(135deg,#f9fafb 0%,#a5b4fc 100%);
  -webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;
}
.lp-sub{font-size:15px;line-height:1.7;color:var(--text2);margin-bottom:52px;max-width:380px}

.features{display:flex;flex-direction:column;gap:14px}
.feat{display:flex;align-items:center;gap:14px}
.feat-dot{
  width:34px;height:34px;border-radius:9px;flex-shrink:0;
  background:rgba(99,102,241,.15);border:1px solid rgba(99,102,241,.3);
  display:grid;place-items:center;
}
.feat-dot svg{width:15px;height:15px;fill:#a5b4fc}
.feat-txt{font-size:13.5px;color:var(--text2)}
.feat-txt strong{color:var(--text1);font-weight:600}

/* ── Right panel ── */
.rp{
  width:520px;display:flex;align-items:center;justify-content:center;
  padding:48px 56px;
  background:rgba(17,24,39,.6);
  border-left:1px solid var(--border);
  backdrop-filter:blur(24px);
}
.rp-inner{width:100%}

/* ── Tabs ── */
.tabs{
  display:flex;background:rgba(255,255,255,.05);border-radius:10px;
  padding:4px;gap:4px;margin-bottom:32px;border:1px solid var(--border);
}
.tab{
  flex:1;height:38px;border:none;border-radius:7px;cursor:pointer;
  font-family:var(--font);font-size:13.5px;font-weight:600;
  color:var(--text2);background:transparent;
  transition:all .2s;display:flex;align-items:center;justify-content:center;gap:7px;
}
.tab svg{width:14px;height:14px;fill:currentColor}
.tab.active{background:var(--card2);color:var(--text1);box-shadow:0 2px 8px rgba(0,0,0,.3)}

/* ── Form header ── */
.form-head{margin-bottom:28px}
.form-head h1{font-size:22px;font-weight:700;letter-spacing:-.4px;margin-bottom:5px}
.form-head p{font-size:13.5px;color:var(--text2)}

/* ── Alert ── */
.alert{
  display:flex;align-items:center;gap:10px;
  padding:11px 14px;border-radius:9px;margin-bottom:20px;
  font-size:13px;font-weight:500;
  background:rgba(239,68,68,.12);border:1px solid rgba(239,68,68,.25);color:#fca5a5;
  animation:shake .35s ease;
}
.alert svg{width:15px;height:15px;fill:currentColor;flex-shrink:0}
@keyframes shake{0%,100%{transform:translateX(0)}20%,60%{transform:translateX(-5px)}40%,80%{transform:translateX(5px)}}

/* ── Fields ── */
.field{margin-bottom:16px}
.field label{display:block;font-size:12.5px;font-weight:600;color:var(--text2);margin-bottom:7px;text-transform:uppercase;letter-spacing:.5px}
.inp-wrap{position:relative}
.inp-ico{position:absolute;left:13px;top:50%;transform:translateY(-50%);color:var(--text3);display:flex;pointer-events:none}
.inp-ico svg{width:15px;height:15px}
.inp{
  width:100%;height:46px;
  background:rgba(255,255,255,.04);
  border:1.5px solid var(--border2);
  border-radius:var(--r);color:var(--text1);
  font-family:var(--font);font-size:14px;
  padding:0 44px;outline:none;
  transition:border-color .15s,box-shadow .15s,background .15s;
  -webkit-appearance:none;
}
.inp::placeholder{color:var(--text3)}
.inp:focus{
  border-color:var(--accent2);
  background:rgba(99,102,241,.06);
  box-shadow:0 0 0 3px var(--accent-glow);
}
.inp-eye{
  position:absolute;right:10px;top:50%;transform:translateY(-50%);
  background:none;border:none;color:var(--text3);cursor:pointer;
  padding:6px;border-radius:6px;display:grid;place-items:center;transition:color .15s;
}
.inp-eye:hover{color:var(--text2)}
.inp-eye svg{width:15px;height:15px}

/* ── Remember ── */
.remember{display:flex;align-items:center;gap:8px;cursor:pointer;margin-bottom:20px;user-select:none}
.remember input{width:15px;height:15px;accent-color:var(--accent);cursor:pointer}
.remember span{font-size:13px;color:var(--text2)}

/* ── Submit button ── */
.btn-sub{
  width:100%;height:46px;border:none;border-radius:var(--r);
  background:linear-gradient(135deg,#6366f1,#8b5cf6);
  color:#fff;font-family:var(--font);font-size:14px;font-weight:700;
  cursor:pointer;display:flex;align-items:center;justify-content:center;gap:8px;
  transition:all .2s;
  box-shadow:0 4px 20px rgba(99,102,241,.4);
  letter-spacing:.2px;
}
.btn-sub:hover{transform:translateY(-1px);box-shadow:0 6px 28px rgba(99,102,241,.55)}
.btn-sub:active{transform:translateY(0)}
.btn-sub:disabled{opacity:.55;cursor:not-allowed;transform:none;box-shadow:none}
.btn-sub svg{width:15px;height:15px}

/* ── PIN pad ── */
.pin-display{
  display:flex;justify-content:center;gap:12px;margin:20px 0 24px;
}
.pin-dot{
  width:14px;height:14px;border-radius:50%;
  background:rgba(255,255,255,.1);border:2px solid var(--border2);
  transition:all .2s;
}
.pin-dot.filled{
  background:var(--accent);border-color:var(--accent);
  box-shadow:0 0 10px var(--accent-glow);
  transform:scale(1.15);
}

.pin-grid{
  display:grid;grid-template-columns:repeat(3,1fr);gap:10px;
  margin-bottom:20px;
}
.pin-key{
  height:58px;border:1.5px solid var(--border2);border-radius:var(--r);
  background:rgba(255,255,255,.04);color:var(--text1);
  font-family:var(--font);font-size:20px;font-weight:600;
  cursor:pointer;display:grid;place-items:center;
  transition:all .15s;
  -webkit-tap-highlight-color:transparent;
  user-select:none;
}
.pin-key:hover{background:rgba(99,102,241,.15);border-color:rgba(99,102,241,.4)}
.pin-key:active{transform:scale(.93);background:rgba(99,102,241,.25)}
.pin-key.del{font-size:15px;color:var(--text2)}
.pin-key.zero{grid-column:2}

/* ── Footer ── */
.r-foot{
  margin-top:24px;padding-top:18px;border-top:1px solid var(--border);
  font-size:11.5px;color:var(--text3);text-align:center;line-height:1.7;
}

/* ── Spinner ── */
.spin{width:15px;height:15px;border:2px solid rgba(255,255,255,.3);border-top-color:#fff;border-radius:50%;animation:rot .6s linear infinite}
@keyframes rot{to{transform:rotate(360deg)}}

/* ── Mobile logo ── */
.m-logo{display:none;align-items:center;gap:10px;margin-bottom:28px}
.m-logo .ico{width:38px;height:38px;background:linear-gradient(135deg,#6366f1,#8b5cf6);border-radius:10px;display:grid;place-items:center}
.m-logo .ico svg{width:20px;height:20px;fill:#fff}
.m-logo .nm{font-size:17px;font-weight:700}

/* ── Responsive ── */
@media(max-width:1024px){
  .lp{padding:48px 48px}
  .rp{width:460px;padding:40px 40px}
  .lp-headline{font-size:36px}
}
@media(max-width:768px){
  .lp{display:none}
  .rp{width:100%;border:none;padding:0;background:transparent;backdrop-filter:none}
  .page{background:var(--bg)}
  .rp-inner{background:rgba(17,24,39,.85);backdrop-filter:blur(20px);border:1px solid var(--border);border-radius:20px;padding:32px 24px;margin:auto;max-width:420px;width:calc(100% - 32px)}
  .rp{align-items:center;justify-content:center;padding:24px 0;min-height:100vh}
  .m-logo{display:flex}
}
@media(max-width:420px){
  .pin-key{height:52px;font-size:18px}
  .rp-inner{padding:28px 20px}
}

/* ── Page entry animation ── */
.rp-inner{animation:fadeUp .4s ease both}
.lp-inner{animation:fadeUp .5s ease both .1s}
@keyframes fadeUp{from{opacity:0;transform:translateY(18px)}to{opacity:1;transform:none}}
</style>
</head>
<body>

<!-- Animated background -->
<div class="bg-wrap">
  <div class="orb orb-1"></div>
  <div class="orb orb-2"></div>
  <div class="orb orb-3"></div>
</div>

<div class="page">

  <!-- LEFT -->
  <div class="lp">
    <div class="lp-inner">
      <div class="brand">
        <div class="brand-ico">
          <svg viewBox="0 0 24 24"><path d="M20 7H4c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V9c0-1.1-.9-2-2-2zm-1 11H5V10h14v8zM7 15h2v2H7zm4 0h2v2h-2zm4 0h2v2h-2zM3 5h18V3H3z"/></svg>
        </div>
        <span class="brand-name">NexaPOS</span>
      </div>

      <div class="lp-headline">The smarter<br>way to sell.</div>
      <div class="lp-sub">A complete point of sale system for restaurants, retail, fashion &amp; factories — built for speed and simplicity.</div>

      <div class="features">
        <div class="feat">
          <div class="feat-dot"><svg viewBox="0 0 24 24"><path d="M2 4h2v16H2zm3 0h1v16H5zm2 0h2v16H7zm3 0h1v16h-1zm3 0h2v16h-2zm3 0h1v16h-1zm2 0h2v16h-2z"/></svg></div>
          <div class="feat-txt"><strong>Barcode scanning</strong> &amp; instant product lookup</div>
        </div>
        <div class="feat">
          <div class="feat-dot"><svg viewBox="0 0 24 24"><path d="M20 4H4c-1.11 0-2 .89-2 2v12c0 1.11.89 2 2 2h16c1.11 0 2-.89 2-2V6c0-1.11-.89-2-2-2zm0 14H4v-6h16v6zm0-10H4V6h16v2z"/></svg></div>
          <div class="feat-txt"><strong>Cash, bKash, Nagad, Card</strong> &amp; split payments</div>
        </div>
        <div class="feat">
          <div class="feat-dot"><svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/></svg></div>
          <div class="feat-txt"><strong>Works offline</strong> — syncs when back online</div>
        </div>
        <div class="feat">
          <div class="feat-dot"><svg viewBox="0 0 24 24"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zM9 17H7v-7h2v7zm4 0h-2V7h2v10zm4 0h-2v-4h2v4z"/></svg></div>
          <div class="feat-txt"><strong>Real-time reports</strong> &amp; profit analytics</div>
        </div>
      </div>
    </div>
  </div>

  <!-- RIGHT -->
  <div class="rp">
    <div class="rp-inner">

      <div class="m-logo">
        <div class="ico"><svg viewBox="0 0 24 24"><path d="M20 7H4c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V9c0-1.1-.9-2-2-2zm-1 11H5V10h14v8zM7 15h2v2H7zm4 0h2v2h-2zm4 0h2v2h-2zM3 5h18V3H3z"/></svg></div>
        <span class="nm">NexaPOS</span>
      </div>

      <?php if (!file_exists(dirname(__DIR__) . '/install/installed.lock')): ?>
      <div class="alert" style="background:rgba(245,158,11,.12);border-color:rgba(245,158,11,.3);color:#fcd34d">
        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M1 21h22L12 2 1 21zm12-3h-2v-2h2v2zm0-4h-2v-4h2v4z"/></svg>
        Not installed. <a href="../install/install.php" style="color:#fbbf24;font-weight:600;margin-left:4px">Run installer →</a>
      </div>
      <?php endif ?>

      <?php if ($error): ?>
      <div class="alert" id="errAlert">
        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/></svg>
        <?= htmlspecialchars($error) ?>
      </div>
      <?php endif ?>

      <!-- Mode tabs -->
      <div class="tabs" role="tablist">
        <button class="tab <?= ($method !== 'pin') ? 'active' : '' ?>" id="tabPass" onclick="switchTab('password')" role="tab">
          <svg viewBox="0 0 24 24"><path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm-6 9c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm3.1-9H8.9V6c0-1.71 1.39-3.1 3.1-3.1 1.71 0 3.1 1.39 3.1 3.1v2z"/></svg>
          Password
        </button>
        <button class="tab <?= ($method === 'pin') ? 'active' : '' ?>" id="tabPin" onclick="switchTab('pin')" role="tab">
          <svg viewBox="0 0 24 24"><path d="M17 12h-5v5h5v-5zM16 1v2H8V1H6v2H5c-1.11 0-1.99.9-1.99 2L3 19c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2h-1V1h-2zm3 18H5V8h14v11z"/></svg>
          PIN
        </button>
      </div>

      <!-- ── PASSWORD form ── -->
      <div id="formPassword" style="display:<?= ($method !== 'pin') ? 'block' : 'none' ?>">
        <div class="form-head">
          <h1>Welcome back</h1>
          <p>Sign in with your email &amp; password</p>
        </div>
        <form method="POST" id="frmPass" novalidate>
          <input type="hidden" name="method" value="password">
          <div class="field">
            <label>Email</label>
            <div class="inp-wrap">
              <span class="inp-ico"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M20 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/></svg></span>
              <input class="inp" type="email" name="email" id="femail"
                     value="admin@nexapos.com" placeholder="you@example.com"
                     autocomplete="email" required>
            </div>
          </div>
          <div class="field">
            <label>Password</label>
            <div class="inp-wrap">
              <span class="inp-ico"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm-6 9c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm3.1-9H8.9V6c0-1.71 1.39-3.1 3.1-3.1 1.71 0 3.1 1.39 3.1 3.1v2z"/></svg></span>
              <input class="inp" type="password" name="password" id="fpass"
                     placeholder="••••••••" autocomplete="current-password" required>
              <button type="button" class="inp-eye" onclick="togglePass()">
                <svg id="e1" viewBox="0 0 24 24" fill="currentColor"><path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/></svg>
                <svg id="e2" style="display:none" viewBox="0 0 24 24" fill="currentColor"><path d="M12 7c2.76 0 5 2.24 5 5 0 .65-.13 1.26-.36 1.83l2.92 2.92c1.51-1.26 2.7-2.89 3.43-4.75-1.73-4.39-6-7.5-11-7.5-1.4 0-2.74.25-3.98.7l2.16 2.16C10.74 7.13 11.35 7 12 7zM2 4.27l2.28 2.28.46.46C3.08 8.3 1.78 10.02 1 12c1.73 4.39 6 7.5 11 7.5 1.55 0 3.03-.3 4.38-.84l.42.42L19.73 22 21 20.73 3.27 3 2 4.27zM7.53 9.8l1.55 1.55c-.05.21-.08.43-.08.65 0 1.66 1.34 3 3 3 .22 0 .44-.03.65-.08l1.55 1.55c-.67.33-1.41.53-2.2.53-2.76 0-5-2.24-5-5 0-.79.2-1.53.53-2.2zm4.31-.78l3.15 3.15.02-.16c0-1.66-1.34-3-3-3l-.17.01z"/></svg>
              </button>
            </div>
          </div>
          <label class="remember">
            <input type="checkbox" name="remember">
            <span>Keep me signed in</span>
          </label>
          <button type="submit" class="btn-sub" id="subBtnPass">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M11 7L9.6 8.4l2.6 2.6H2v2h10.2l-2.6 2.6L11 17l5-5-5-5zm9 12h-8v2h8c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2h-8v2h8v14z"/></svg>
            Sign In
          </button>
        </form>
      </div>

      <!-- ── PIN form ── -->
      <div id="formPin" style="display:<?= ($method === 'pin') ? 'block' : 'none' ?>">
        <div class="form-head">
          <h1>Enter PIN</h1>
          <p>Use your 4–6 digit PIN to sign in</p>
        </div>
        <form method="POST" id="frmPin">
          <input type="hidden" name="method" value="pin">
          <input type="hidden" name="pin"    id="pinValue">

          <!-- Dot indicators -->
          <div class="pin-display" id="pinDots">
            <div class="pin-dot" id="pd0"></div>
            <div class="pin-dot" id="pd1"></div>
            <div class="pin-dot" id="pd2"></div>
            <div class="pin-dot" id="pd3"></div>
            <div class="pin-dot" id="pd4"></div>
            <div class="pin-dot" id="pd5"></div>
          </div>

          <!-- Number pad -->
          <div class="pin-grid">
            <?php foreach([1,2,3,4,5,6,7,8,9] as $n): ?>
            <button type="button" class="pin-key" onclick="pinPress(<?=$n?>)"><?=$n?></button>
            <?php endforeach ?>
            <button type="button" class="pin-key del" onclick="pinDel()">
              <svg viewBox="0 0 24 24" fill="currentColor" style="width:20px;height:20px"><path d="M22 3H7c-.69 0-1.23.35-1.59.88L0 12l5.41 8.11c.36.53.9.89 1.59.89h15c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-3 12.59L17.59 17 14 13.41 10.41 17 9 15.59 12.59 12 9 8.41 10.41 7 14 10.59 17.59 7 19 8.41 15.41 12 19 15.59z"/></svg>
            </button>
            <button type="button" class="pin-key zero" onclick="pinPress(0)">0</button>
          </div>

          <button type="submit" class="btn-sub" id="subBtnPin" disabled>
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M11 7L9.6 8.4l2.6 2.6H2v2h10.2l-2.6 2.6L11 17l5-5-5-5zm9 12h-8v2h8c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2h-8v2h8v14z"/></svg>
            Sign In with PIN
          </button>
        </form>
      </div>

      <div class="r-foot">
        NexaPOS v2.0 &copy; <?= date('Y') ?>
      </div>
    </div>
  </div>
</div>

<script>
// ── Tab switching ──────────────────────────────
function switchTab(mode) {
  const isPass = mode === 'password';
  document.getElementById('formPassword').style.display = isPass ? 'block' : 'none';
  document.getElementById('formPin').style.display      = isPass ? 'none'  : 'block';
  document.getElementById('tabPass').classList.toggle('active', isPass);
  document.getElementById('tabPin').classList.toggle('active', !isPass);
  if (!isPass) { pinReset(); }
  else { setTimeout(() => document.getElementById('femail').focus(), 80); }
}

// ── Password toggle ────────────────────────────
function togglePass() {
  const p = document.getElementById('fpass');
  const e1 = document.getElementById('e1'), e2 = document.getElementById('e2');
  if (p.type === 'password') { p.type = 'text';     e1.style.display = 'none'; e2.style.display = ''; }
  else                       { p.type = 'password'; e1.style.display = '';     e2.style.display = 'none'; }
}

// ── Password form submit ───────────────────────
document.getElementById('frmPass').addEventListener('submit', function() {
  const b = document.getElementById('subBtnPass');
  b.disabled = true;
  b.innerHTML = '<div class="spin"></div> Signing in…';
});

// ── PIN logic ──────────────────────────────────
let pinVal = '';

function pinPress(n) {
  if (pinVal.length >= 6) return;
  pinVal += String(n);
  updatePinUI();
  // Auto-submit at 4–6 digits if user presses submit, or auto at 6
  if (pinVal.length === 6) {
    submitPin();
  }
}

function pinDel() {
  pinVal = pinVal.slice(0, -1);
  updatePinUI();
}

function pinReset() {
  pinVal = '';
  updatePinUI();
}

function updatePinUI() {
  for (let i = 0; i < 6; i++) {
    const d = document.getElementById('pd' + i);
    if (d) d.classList.toggle('filled', i < pinVal.length);
  }
  const btn = document.getElementById('subBtnPin');
  if (btn) btn.disabled = pinVal.length < 4;
}

function submitPin() {
  document.getElementById('pinValue').value = pinVal;
  const btn = document.getElementById('subBtnPin');
  btn.disabled = true;
  btn.innerHTML = '<div class="spin"></div> Verifying…';
  document.getElementById('frmPin').submit();
}

document.getElementById('frmPin').addEventListener('submit', function(e) {
  e.preventDefault();
  if (pinVal.length < 4) return;
  submitPin();
});

// ── Physical keyboard → PIN pad ───────────────
document.addEventListener('keydown', e => {
  const pinVisible = document.getElementById('formPin').style.display !== 'none';
  if (!pinVisible) return;
  if (e.key >= '0' && e.key <= '9') { e.preventDefault(); pinPress(parseInt(e.key)); }
  if (e.key === 'Backspace')         { e.preventDefault(); pinDel(); }
  if (e.key === 'Enter' && pinVal.length >= 4) { e.preventDefault(); submitPin(); }
});

// ── Open on PIN tab if last login was PIN ──────
if (localStorage.getItem('nexapos_last_login') === 'pin') {
  switchTab('pin');
}
document.getElementById('frmPin').addEventListener('submit', function() {
  localStorage.setItem('nexapos_last_login', 'pin');
});
document.getElementById('frmPass').addEventListener('submit', function() {
  localStorage.setItem('nexapos_last_login', 'password');
});

// ── Service Worker ─────────────────────────────
if ('serviceWorker' in navigator) navigator.serviceWorker.register('../sw.js').catch(() => {});

// ── Auto-focus on load ─────────────────────────
window.addEventListener('DOMContentLoaded', () => {
  const pinVisible = document.getElementById('formPin').style.display !== 'none';
  if (!pinVisible) document.getElementById('femail').focus();
});
</script>
</body>
</html>
