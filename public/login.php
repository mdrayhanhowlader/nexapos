<?php
require_once dirname(__DIR__) . '/bootstrap.php';
if (Auth::check()) Response::redirect(app_url('public/dashboard.php'));
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $v = Validator::make($_POST, ['email' => 'required|email', 'password' => 'required|min:4']);
    if ($v->fails()) { $error = $v->firstError(); }
    else {
        $result = Auth::attempt(trim($_POST['email']), $_POST['password']);
        if ($result['success']) Response::redirect(app_url('public/dashboard.php'));
        $error = $result['message'];
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
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{
  --bg:#f0f2f5;
  --white:#ffffff;
  --border:#e2e5eb;
  --accent:#2563eb;
  --accent-h:#1d4ed8;
  --accent-bg:#eff6ff;
  --text1:#111827;
  --text2:#6b7280;
  --text3:#9ca3af;
  --red:#dc2626;
  --red-bg:#fef2f2;
  --amber:#d97706;
  --amber-bg:#fffbeb;
  --font:'Inter',-apple-system,sans-serif;
  --r:8px;
  --r-lg:14px;
}
html,body{height:100%;font-family:var(--font);-webkit-font-smoothing:antialiased;background:var(--bg)}
body{display:flex;min-height:100vh}

/* LEFT */
.l-panel{
  flex:1;
  background:linear-gradient(160deg,#1e40af 0%,#2563eb 50%,#3b82f6 100%);
  display:flex;align-items:center;justify-content:center;
  padding:60px 80px;position:relative;overflow:hidden;
}
.l-panel::before{
  content:'';position:absolute;
  width:600px;height:600px;border-radius:50%;
  background:rgba(255,255,255,0.05);
  top:-200px;right:-200px;
}
.l-panel::after{
  content:'';position:absolute;
  width:400px;height:400px;border-radius:50%;
  background:rgba(255,255,255,0.04);
  bottom:-150px;left:-100px;
}
.l-inner{position:relative;z-index:1;max-width:460px;color:#fff}
.l-brand{display:flex;align-items:center;gap:12px;margin-bottom:56px}
.l-brand .ico{
  width:46px;height:46px;border-radius:12px;
  background:rgba(255,255,255,0.18);
  display:grid;place-items:center;
  backdrop-filter:blur(8px);
}
.l-brand .ico svg{width:24px;height:24px;fill:#fff}
.l-brand .txt{font-size:20px;font-weight:700;letter-spacing:-0.3px}
.l-headline{font-size:36px;font-weight:700;line-height:1.2;letter-spacing:-1px;margin-bottom:16px}
.l-sub{font-size:15px;line-height:1.65;opacity:0.72;margin-bottom:52px}
.l-features{display:flex;flex-direction:column;gap:16px}
.l-feat{display:flex;align-items:center;gap:14px;font-size:14px;opacity:0.88}
.l-feat-ic{
  width:32px;height:32px;border-radius:8px;
  background:rgba(255,255,255,0.14);
  display:grid;place-items:center;flex-shrink:0;
}
.l-feat-ic svg{width:16px;height:16px;fill:#fff}

/* RIGHT */
.r-panel{
  width:500px;background:var(--white);
  display:flex;align-items:center;justify-content:center;
  padding:48px 56px;
  box-shadow:-1px 0 0 0 var(--border);
}
.r-inner{width:100%}

.r-head{margin-bottom:32px}
.r-head h1{font-size:24px;font-weight:700;color:var(--text1);letter-spacing:-0.4px;margin-bottom:6px}
.r-head p{font-size:14px;color:var(--text2)}

/* Alerts */
.alert{
  display:flex;align-items:flex-start;gap:10px;
  padding:12px 14px;border-radius:var(--r);
  margin-bottom:18px;font-size:13.5px;font-weight:500;
  animation:shake .3s ease;
}
.alert svg{width:16px;height:16px;flex-shrink:0;margin-top:1px}
.alert-err{background:var(--red-bg);border:1px solid #fecaca;color:var(--red)}
.alert-warn{background:var(--amber-bg);border:1px solid #fde68a;color:var(--amber)}
.alert-warn a{color:var(--amber);font-weight:600}
@keyframes shake{0%,100%{transform:translateX(0)}25%,75%{transform:translateX(-4px)}50%{transform:translateX(4px)}}

/* Fields */
.field{margin-bottom:18px}
.field label{display:block;font-size:13px;font-weight:500;color:var(--text1);margin-bottom:6px}
.inp-wrap{position:relative}
.inp-ico{position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--text3);display:flex;pointer-events:none}
.inp-ico svg{width:16px;height:16px}
.inp{
  width:100%;height:44px;
  background:var(--white);border:1.5px solid var(--border);
  border-radius:var(--r);color:var(--text1);
  font-family:var(--font);font-size:14px;
  padding:0 42px;outline:none;
  transition:border-color .15s,box-shadow .15s;
  -webkit-appearance:none;
}
.inp::placeholder{color:var(--text3)}
.inp:focus{border-color:var(--accent);box-shadow:0 0 0 3px rgba(37,99,235,.1)}
.inp-eye{
  position:absolute;right:10px;top:50%;transform:translateY(-50%);
  background:none;border:none;color:var(--text3);cursor:pointer;
  padding:5px;border-radius:5px;display:grid;place-items:center;
  transition:color .15s;
}
.inp-eye:hover{color:var(--text2)}
.inp-eye svg{width:15px;height:15px}

/* Remember */
.remember{display:flex;align-items:center;gap:8px;cursor:pointer;margin-bottom:22px;user-select:none}
.remember input{width:15px;height:15px;accent-color:var(--accent);cursor:pointer;padding:0}
.remember span{font-size:13.5px;color:var(--text2)}

/* Submit */
.btn-submit{
  width:100%;height:44px;border:none;border-radius:var(--r);
  background:var(--accent);color:#fff;
  font-family:var(--font);font-size:14px;font-weight:600;
  cursor:pointer;display:flex;align-items:center;justify-content:center;gap:8px;
  transition:all .15s;box-shadow:0 2px 8px rgba(37,99,235,.25);
  -webkit-appearance:none;
}
.btn-submit:hover{background:var(--accent-h);transform:translateY(-1px);box-shadow:0 4px 16px rgba(37,99,235,.3)}
.btn-submit:active{transform:translateY(0)}
.btn-submit:disabled{opacity:.55;cursor:not-allowed;transform:none}
.btn-submit svg{width:16px;height:16px}

/* Footer */
.r-foot{margin-top:28px;padding-top:20px;border-top:1px solid var(--border);font-size:12px;color:var(--text3);text-align:center;line-height:1.6}

/* Spinner */
.spin{width:15px;height:15px;border:2px solid rgba(255,255,255,.35);border-top-color:#fff;border-radius:50%;animation:rot .6s linear infinite}
@keyframes rot{to{transform:rotate(360deg)}}

/* RESPONSIVE */
@media(max-width:1024px){
  .l-panel{padding:48px 48px}
  .r-panel{width:440px;padding:40px 44px}
}
@media(max-width:768px){
  .l-panel{display:none}
  .r-panel{width:100%;box-shadow:none;padding:0;background:var(--bg);align-items:flex-start;padding-top:0}
  body{align-items:stretch}
  .r-inner{background:var(--white);border-radius:0;padding:40px 24px;min-height:100vh;display:flex;flex-direction:column;justify-content:center}
  .m-logo{display:flex !important;align-items:center;gap:10px;margin-bottom:32px}
  .m-logo .ico{width:38px;height:38px;background:var(--accent);border-radius:10px;display:grid;place-items:center}
  .m-logo .ico svg{width:20px;height:20px;fill:#fff}
  .m-logo .nm{font-size:17px;font-weight:700;color:var(--text1)}
}
@media(min-width:769px){.m-logo{display:none}}
@media(max-width:420px){
  .r-inner{padding:32px 20px}
  .r-head h1{font-size:21px}
}
</style>
</head>
<body>

<div class="l-panel">
  <div class="l-inner">
    <div class="l-brand">
      <div class="ico"><svg viewBox="0 0 24 24"><path d="M20 7H4c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V9c0-1.1-.9-2-2-2zm-1 11H5V10h14v8zM7 15h2v2H7zm4 0h2v2h-2zm4 0h2v2h-2zM3 5h18V3H3z"/></svg></div>
      <span class="txt">NexaPOS</span>
    </div>
    <div class="l-headline">The smarter way<br>to run your store</div>
    <div class="l-sub">A complete point of sale system for restaurants, retail, fashion outlets, and factories — built for speed and simplicity.</div>
    <div class="l-features">
      <div class="l-feat">
        <div class="l-feat-ic"><svg viewBox="0 0 24 24"><path d="M20 2H4c-1 0-2 .9-2 2v3.01c0 .72.43 1.34 1 1.72V20c0 1.1 1.1 2 2 2h14c.9 0 2-.9 2-2V8.72c.57-.38 1-.99 1-1.71V4c0-1.1-1-2-2-2zm-5 12H9v-2h6v2zm3-8H6V4h12v2z"/></svg></div>
        Barcode scanning &amp; instant product lookup
      </div>
      <div class="l-feat">
        <div class="l-feat-ic"><svg viewBox="0 0 24 24"><path d="M20 4H4c-1.11 0-2 .89-2 2v12c0 1.11.89 2 2 2h16c1.11 0 2-.89 2-2V6c0-1.11-.89-2-2-2zm0 14H4v-6h16v6zm0-10H4V6h16v2z"/></svg></div>
        Cash, card, bKash, Nagad &amp; split payments
      </div>
      <div class="l-feat">
        <div class="l-feat-ic"><svg viewBox="0 0 24 24"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-7 3c1.93 0 3.5 1.57 3.5 3.5S13.93 13 12 13s-3.5-1.57-3.5-3.5S10.07 6 12 6zm7 13H5v-.23c0-.62.28-1.2.76-1.58C7.47 15.82 9.64 15 12 15s4.53.82 6.24 2.19c.48.38.76.97.76 1.58V19z"/></svg></div>
        Customer management &amp; loyalty points
      </div>
      <div class="l-feat">
        <div class="l-feat-ic"><svg viewBox="0 0 24 24"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zM9 17H7v-7h2v7zm4 0h-2V7h2v10zm4 0h-2v-4h2v4z"/></svg></div>
        Real-time reports &amp; profit analytics
      </div>
      <div class="l-feat">
        <div class="l-feat-ic"><svg viewBox="0 0 24 24"><path d="M17 1.01L7 1c-1.1 0-2 .9-2 2v18c0 1.1.9 2 2 2h10c1.1 0 2-.9 2-2V3c0-1.1-.9-1.99-2-1.99zM17 19H7V5h10v14z"/></svg></div>
        Works on PC, tablet &amp; mobile as an app
      </div>
    </div>
  </div>
</div>

<div class="r-panel">
  <div class="r-inner">

    <div class="m-logo">
      <div class="ico"><svg viewBox="0 0 24 24"><path d="M20 7H4c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V9c0-1.1-.9-2-2-2zm-1 11H5V10h14v8zM7 15h2v2H7zm4 0h2v2h-2zm4 0h2v2h-2zM3 5h18V3H3z"/></svg></div>
      <span class="nm">NexaPOS</span>
    </div>

    <div class="r-head">
      <h1>Welcome back</h1>
      <p>Sign in to your NexaPOS account to continue</p>
    </div>

    <?php if (!file_exists(dirname(__DIR__) . '/install/installed.lock')): ?>
    <div class="alert alert-warn">
      <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/></svg>
      Not installed yet. <a href="../install/install.php">Run installer →</a>
    </div>
    <?php endif ?>

    <?php if ($error): ?>
    <div class="alert alert-err">
      <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/></svg>
      <?= htmlspecialchars($error) ?>
    </div>
    <?php endif ?>

    <form method="POST" id="loginForm" novalidate autocomplete="on">
      <div class="field">
        <label for="femail">Email address</label>
        <div class="inp-wrap">
          <span class="inp-ico"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M20 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/></svg></span>
          <input class="inp" type="email" id="femail" name="email" value="admin@nexapos.com" placeholder="you@example.com" autocomplete="email" required>
        </div>
      </div>

      <div class="field">
        <label for="fpass">Password</label>
        <div class="inp-wrap">
          <span class="inp-ico"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm-6 9c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm3.1-9H8.9V6c0-1.71 1.39-3.1 3.1-3.1 1.71 0 3.1 1.39 3.1 3.1v2z"/></svg></span>
          <input class="inp" type="password" id="fpass" name="password" placeholder="••••••••" autocomplete="current-password" required>
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

      <button type="submit" class="btn-submit" id="subBtn">
        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M11 7L9.6 8.4l2.6 2.6H2v2h10.2l-2.6 2.6L11 17l5-5-5-5zm9 12h-8v2h8c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2h-8v2h8v14z"/></svg>
        Sign In
      </button>
    </form>

    <div class="r-foot">
      NexaPOS v2.0 &copy; <?= date('Y') ?><br>
      Default: admin@nexapos.com / password
    </div>
  </div>
</div>

<script>
function togglePass(){
  const p=document.getElementById('fpass');
  const e1=document.getElementById('e1');
  const e2=document.getElementById('e2');
  if(p.type==='password'){p.type='text';e1.style.display='none';e2.style.display='';}
  else{p.type='password';e1.style.display='';e2.style.display='none';}
}
document.getElementById('loginForm').addEventListener('submit',function(){
  const b=document.getElementById('subBtn');
  b.disabled=true;
  b.innerHTML='<div class="spin"></div> Signing in...';
});
if('serviceWorker' in navigator){navigator.serviceWorker.register('../sw.js').catch(()=>{});}
</script>
</body>
</html>
