<?php
// ============================================================
// admin/login.php — Admin Portal (Login + Create Account)
// ============================================================
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
startSession();

// If already logged in as admin, redirect to main dashboard
$user = getCurrentUser();
if ($user && $user['role'] === 'admin') {
    header('Location: dashboard.php');
    exit;
}
?>
<!doctype html>
<html lang="en">
 <head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Portal — OJT DTR Monitoring System</title>
  <link rel="stylesheet" href="../assets/css/style.css">
 </head>
 <body class="min-h-screen bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900 font-sans flex items-center justify-center p-4">

  <div class="w-full max-w-md animate-slide-up">

   <!-- Logo -->
   <div class="text-center mb-8">
    <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-gradient-to-br from-rose-500 to-rose-700 shadow-lg shadow-rose-500/30 mb-4">
     <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
       d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
     </svg>
    </div>
    <h1 class="text-2xl font-bold text-white">Admin Portal</h1>
    <p class="text-slate-400 text-sm mt-1">OJT DTR Monitoring System</p>
   </div>

   <!-- Card -->
   <div class="glass-effect rounded-2xl shadow-xl overflow-hidden">

    <!-- Restricted banner -->
    <div class="bg-rose-50 border-b border-rose-200 px-6 py-3 flex items-center gap-2">
     
     </div>

    <!-- Tabs -->
    <div class="flex border-b border-slate-200">
     <button id="tab-login" onclick="switchTab('login')"
      class="flex-1 py-3 text-sm font-semibold text-rose-600 border-b-2 border-rose-500 transition-all">
      Sign In
     </button>
     <button id="tab-register" onclick="switchTab('register')"
      class="flex-1 py-3 text-sm font-semibold text-slate-400 border-b-2 border-transparent hover:text-slate-600 transition-all">
      Create Admin Account
     </button>
    </div>

    <div class="p-8">

     <!-- ── LOGIN FORM ─────────────────────────────────────── -->
     <div id="panel-login">
      <form id="admin-login-form" class="space-y-4">
       <div>
        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Email Address</label>
        <input type="email" id="admin-email" required
         class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:border-rose-500 focus:ring-2 focus:ring-rose-500/20 outline-none transition-all text-sm"
         placeholder="admin@ojt.com">
       </div>
       <div>
        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Password</label>
        <div class="relative">
         <input type="password" id="admin-password" required
          class="w-full px-4 py-3 pr-11 rounded-xl border border-slate-200 focus:border-rose-500 focus:ring-2 focus:ring-rose-500/20 outline-none transition-all text-sm"
          placeholder="••••••••">
         <button type="button" onclick="togglePw('admin-password','eye-login-show','eye-login-hide')"
          class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 transition-colors">
          <svg id="eye-login-show" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
           <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
           <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
          </svg>
          <svg id="eye-login-hide" class="w-5 h-5 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
           <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
          </svg>
         </button>
        </div>
       </div>
       <div id="login-error" class="hidden text-red-600 text-sm bg-red-50 p-3 rounded-xl border border-red-100"></div>
       <button type="submit" id="login-btn"
        class="w-full py-3 px-4 bg-gradient-to-r from-rose-600 to-rose-700 text-white font-medium rounded-xl hover:from-rose-700 hover:to-rose-800 focus:ring-2 focus:ring-rose-500/50 transition-all shadow-lg shadow-rose-500/30">
        Sign In as Administrator
       </button>
      </form>
     </div>

     <!-- ── CREATE ADMIN ACCOUNT FORM ─────────────────────── -->
     <div id="panel-register" class="hidden">
      <form id="admin-register-form" class="space-y-4">
       <div>
        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Full Name</label>
        <input type="text" id="reg-name" required
         class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:border-rose-500 focus:ring-2 focus:ring-rose-500/20 outline-none transition-all text-sm"
         placeholder="e.g. Maria Santos">
       </div>
       <div class="grid grid-cols-2 gap-3">
        <div>
         <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Username</label>
         <input type="text" id="reg-username" required
          class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:border-rose-500 focus:ring-2 focus:ring-rose-500/20 outline-none transition-all text-sm"
          placeholder="username">
        </div>
        <div>
         <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Email Address</label>
         <input type="email" id="reg-email" required
          class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:border-rose-500 focus:ring-2 focus:ring-rose-500/20 outline-none transition-all text-sm"
          placeholder="admin@ojt.com">
        </div>
       </div>
       <div>
        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Password</label>
        <div class="relative">
         <input type="password" id="reg-password" required minlength="6"
          class="w-full px-4 py-3 pr-11 rounded-xl border border-slate-200 focus:border-rose-500 focus:ring-2 focus:ring-rose-500/20 outline-none transition-all text-sm"
          placeholder="Min. 6 characters">
         <button type="button" onclick="togglePw('reg-password','eye-reg-show','eye-reg-hide')"
          class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 transition-colors">
          <svg id="eye-reg-show" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
           <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
           <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
          </svg>
          <svg id="eye-reg-hide" class="w-5 h-5 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
           <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
          </svg>
         </button>
        </div>
       </div>
       <div>
        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Confirm Password</label>
        <input type="password" id="reg-confirm" required minlength="6"
         class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:border-rose-500 focus:ring-2 focus:ring-rose-500/20 outline-none transition-all text-sm"
         placeholder="Re-enter password">
       </div>
       <div id="reg-error" class="hidden text-red-600 text-sm bg-red-50 p-3 rounded-xl border border-red-100"></div>
       <div id="reg-success" class="hidden text-emerald-700 text-sm bg-emerald-50 p-3 rounded-xl border border-emerald-200"></div>
       <button type="submit" id="reg-btn"
        class="w-full py-3 px-4 bg-gradient-to-r from-rose-600 to-rose-700 text-white font-medium rounded-xl hover:from-rose-700 hover:to-rose-800 focus:ring-2 focus:ring-rose-500/50 transition-all shadow-lg shadow-rose-500/30">
        Create Admin Account
       </button>
      </form>
     </div>

    </div><!-- /p-8 -->

    <!-- Footer -->
    <div class="border-t border-slate-100 px-8 py-4 text-center">
     <a href="../index.php"
      class="inline-flex items-center gap-1.5 text-sm text-slate-500 hover:text-slate-700 transition-colors">
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
       <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
      </svg>
      Back to Trainee Login
     </a>
    </div>

   </div><!-- /card -->

   <p class="text-center text-slate-500 text-xs mt-6">OJT DTR Monitoring System &mdash; Admin Access Only</p>
  </div>

  <script>
  // ── Tab switcher ──────────────────────────────────────────
  function switchTab(tab) {
    const isLogin = tab === 'login';
    document.getElementById('panel-login').classList.toggle('hidden', !isLogin);
    document.getElementById('panel-register').classList.toggle('hidden', isLogin);

    const tLogin = document.getElementById('tab-login');
    const tReg   = document.getElementById('tab-register');
    if (isLogin) {
      tLogin.classList.add('text-rose-600','border-rose-500');
      tLogin.classList.remove('text-slate-400','border-transparent');
      tReg.classList.add('text-slate-400','border-transparent');
      tReg.classList.remove('text-rose-600','border-rose-500');
    } else {
      tReg.classList.add('text-rose-600','border-rose-500');
      tReg.classList.remove('text-slate-400','border-transparent');
      tLogin.classList.add('text-slate-400','border-transparent');
      tLogin.classList.remove('text-rose-600','border-rose-500');
    }
  }

  // ── Password toggle ───────────────────────────────────────
  function togglePw(inputId, showId, hideId) {
    const pw = document.getElementById(inputId);
    const isHidden = pw.type === 'password';
    pw.type = isHidden ? 'text' : 'password';
    document.getElementById(showId).classList.toggle('hidden', isHidden);
    document.getElementById(hideId).classList.toggle('hidden', !isHidden);
  }

  // ── API helper ────────────────────────────────────────────
  async function post(action, body) {
    const fd = new FormData();
    fd.append('action', action);
    Object.entries(body).forEach(([k,v]) => fd.append(k, v));
    const res = await fetch('../api/auth.php', { method:'POST', body:fd });
    return res.json();
  }

  // ── Admin Login ───────────────────────────────────────────
  document.getElementById('admin-login-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    const email    = document.getElementById('admin-email').value.trim();
    const password = document.getElementById('admin-password').value;
    const errorEl  = document.getElementById('login-error');
    const btn      = document.getElementById('login-btn');

    errorEl.classList.add('hidden');
    btn.disabled    = true;
    btn.textContent = 'Verifying...';

    const data = await post('admin_login', { email, password });

    btn.disabled    = false;
    btn.textContent = 'Sign In as Administrator';

    if (data.ok) {
      window.location.href = 'dashboard.php';
    } else {
      errorEl.textContent = data.message || 'Invalid credentials or insufficient privileges.';
      errorEl.classList.remove('hidden');
    }
  });

  // ── Admin Register ────────────────────────────────────────
  document.getElementById('admin-register-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    const name     = document.getElementById('reg-name').value.trim();
    const username = document.getElementById('reg-username').value.trim();
    const email    = document.getElementById('reg-email').value.trim();
    const password = document.getElementById('reg-password').value;
    const confirm  = document.getElementById('reg-confirm').value;
    const errorEl  = document.getElementById('reg-error');
    const successEl= document.getElementById('reg-success');
    const btn      = document.getElementById('reg-btn');

    errorEl.classList.add('hidden');
    successEl.classList.add('hidden');

    if (password !== confirm) {
      errorEl.textContent = 'Passwords do not match.';
      errorEl.classList.remove('hidden');
      return;
    }

    btn.disabled    = true;
    btn.textContent = 'Creating...';

    const data = await post('admin_register', { name, username, email, password });

    btn.disabled    = false;
    btn.textContent = 'Create Admin Account';

    if (data.ok) {
      document.getElementById('admin-register-form').reset();
      successEl.textContent = '✓ Admin account created! You can now sign in.';
      successEl.classList.remove('hidden');
      setTimeout(() => switchTab('login'), 2500);
    } else {
      errorEl.textContent = data.message || 'Registration failed.';
      errorEl.classList.remove('hidden');
    }
  });
  </script>
 </body>
</html>
