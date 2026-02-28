<?php
// ============================================================
// ojt/login.php — OJT Trainee Login & Registration
// ============================================================
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
startSession();

$user = getCurrentUser();
if ($user && $user['role'] !== 'admin') {
    header('Location: dashboard.php');
    exit;
}
?>
<!doctype html>
<html lang="en">
 <head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>OJT Trainee Login — DTR System</title>
  <link rel="stylesheet" href="../assets/css/style.css">
 </head>
 <body class="min-h-screen bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900 font-sans flex items-center justify-center p-4">
  <div class="w-full max-w-md animate-slide-up">

   <!-- Logo -->
   <div class="text-center mb-8">
    <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-gradient-to-br from-primary-500 to-primary-700 shadow-lg shadow-primary-500/30 mb-4">
     <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
     </svg>
    </div>
    <h1 class="text-2xl font-bold text-white">OJT DTR Monitoring</h1>
    <p class="text-slate-400 text-sm mt-1">Daily Time Record System</p>
   </div>

   <!-- Card -->
   <div class="bg-white rounded-2xl shadow-2xl overflow-hidden">

    <!-- Tabs -->
    <div class="flex border-b border-slate-100">
     <button id="tab-login-btn" onclick="switchTab('login')"
      class="flex-1 py-3 text-sm font-semibold text-primary-600 border-b-2 border-primary-500 transition-all">
      Sign In
     </button>
     <button id="tab-reg-btn" onclick="switchTab('register')"
      class="flex-1 py-3 text-sm font-semibold text-slate-400 border-b-2 border-transparent hover:text-slate-600 transition-all">
      Create Account
     </button>
    </div>

    <div class="p-8">

     <!-- ── SIGN IN ─────────────────────────────────────── -->
     <div id="panel-login">
      <form id="login-form" class="space-y-4">
       <div>
        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Email or Username</label>
        <input type="text" id="login-identifier" required autocomplete="username"
         class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 outline-none transition-all text-sm"
         placeholder="your@email.com or username">
       </div>
       <div>
        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Password</label>
        <div class="relative">
         <input type="password" id="login-password" required autocomplete="current-password"
          class="w-full px-4 py-3 pr-11 rounded-xl border border-slate-200 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 outline-none transition-all text-sm"
          placeholder="••••••••">
         <button type="button" onclick="togglePw('login-password','eye-li-show','eye-li-hide')"
          class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600">
          <svg id="eye-li-show" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
          <svg id="eye-li-hide" class="w-5 h-5 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
         </button>
        </div>
       </div>
       <div id="login-error" class="hidden text-red-600 text-sm bg-red-50 p-3 rounded-xl border border-red-100"></div>
       <button type="submit" id="login-btn"
        class="w-full py-3 px-4 bg-gradient-to-r from-primary-600 to-primary-700 text-white font-medium rounded-xl hover:from-primary-700 hover:to-primary-800 transition-all shadow-lg shadow-primary-500/30">
        Sign In
       </button>
      </form>
     </div>

     <!-- ── CREATE ACCOUNT ─────────────────────────────── -->
     <div id="panel-register" class="hidden">
      <form id="register-form" class="space-y-4">
       <div>
        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Full Name</label>
        <input type="text" id="reg-name" required
         class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 outline-none transition-all text-sm"
         placeholder="e.g. Juan Dela Cruz">
       </div>
       <div class="grid grid-cols-2 gap-3">
        <div>
         <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Username</label>
         <input type="text" id="reg-username" required
          class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 outline-none transition-all text-sm"
          placeholder="Choose a username">
        </div>
        <div>
         <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Email Address</label>
         <input type="email" id="reg-email" required
          class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 outline-none transition-all text-sm"
          placeholder="you@email.com">
        </div>
       </div>
       <div class="grid grid-cols-2 gap-3">
        <div>
         <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Password</label>
         <div class="relative">
          <input type="password" id="reg-password" required minlength="6"
           class="w-full px-4 py-3 pr-10 rounded-xl border border-slate-200 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 outline-none transition-all text-sm"
           placeholder="Min. 6 chars">
          <button type="button" onclick="togglePw('reg-password','eye-rp-show','eye-rp-hide')"
           class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600">
           <svg id="eye-rp-show" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
           <svg id="eye-rp-hide" class="w-4 h-4 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
          </button>
         </div>
        </div>
        <div>
         <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Required Hours</label>
         <select id="reg-hours" class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:border-primary-500 outline-none transition-all text-sm bg-white">
          <option value="486">486 hours</option>
          <option value="500">500 hours</option>
          <option value="600">600 hours</option>
          <option value="custom">Custom...</option>
         </select>
        </div>
       </div>
       <div id="custom-hours-wrap" class="hidden">
        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Custom Hours</label>
        <input type="number" id="reg-custom-hours" min="1"
         class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 outline-none transition-all text-sm"
         placeholder="Enter total hours">
       </div>
       <div class="flex items-start gap-2 bg-slate-50 p-3 rounded-xl">
        <input type="checkbox" id="reg-terms" required class="mt-0.5 accent-primary-600">
        <label for="reg-terms" class="text-xs text-slate-600 leading-relaxed">
         I agree to the <a href="../terms.php" target="_blank" class="text-primary-600 hover:underline font-medium">Terms &amp; Privacy Policy</a>
         and acknowledge this is an unofficial practice tool.
        </label>
       </div>
       <div id="reg-error" class="hidden text-red-600 text-sm bg-red-50 p-3 rounded-xl border border-red-100"></div>
       <div id="reg-success" class="hidden text-emerald-700 text-sm bg-emerald-50 p-3 rounded-xl border border-emerald-100"></div>
       <button type="submit" id="reg-btn"
        class="w-full py-3 px-4 bg-gradient-to-r from-primary-600 to-primary-700 text-white font-medium rounded-xl hover:from-primary-700 hover:to-primary-800 transition-all shadow-lg shadow-primary-500/30">
        Create Account
       </button>
      </form>
     </div>

    </div>

    <!-- Footer -->
    <div class="border-t border-slate-100 px-8 py-4 text-center">
     <p class="text-xs text-slate-400">Are you an admin?
      <a href="../admin/login.php" class="text-rose-500 font-medium hover:underline">Admin Portal &rarr;</a>
     </p>
    </div>
   </div>

  </div>

  <script>
  // ── Tab switcher ──────────────────────────────────────────
  function switchTab(tab) {
    const isLogin = tab === 'login';
    document.getElementById('panel-login').classList.toggle('hidden', !isLogin);
    document.getElementById('panel-register').classList.toggle('hidden', isLogin);
    const tL = document.getElementById('tab-login-btn');
    const tR = document.getElementById('tab-reg-btn');
    if (isLogin) {
      tL.className = 'flex-1 py-3 text-sm font-semibold text-primary-600 border-b-2 border-primary-500 transition-all';
      tR.className = 'flex-1 py-3 text-sm font-semibold text-slate-400 border-b-2 border-transparent hover:text-slate-600 transition-all';
    } else {
      tR.className = 'flex-1 py-3 text-sm font-semibold text-primary-600 border-b-2 border-primary-500 transition-all';
      tL.className = 'flex-1 py-3 text-sm font-semibold text-slate-400 border-b-2 border-transparent hover:text-slate-600 transition-all';
    }
  }

  // ── Password eye toggle ───────────────────────────────────
  function togglePw(inputId, showId, hideId) {
    const pw = document.getElementById(inputId);
    const hidden = pw.type === 'password';
    pw.type = hidden ? 'text' : 'password';
    document.getElementById(showId).classList.toggle('hidden', hidden);
    document.getElementById(hideId).classList.toggle('hidden', !hidden);
  }

  // ── API helper ────────────────────────────────────────────
  async function post(action, body) {
    const fd = new FormData();
    fd.append('action', action);
    Object.entries(body).forEach(([k, v]) => fd.append(k, v ?? ''));
    const r = await fetch('../api/auth.php', { method: 'POST', body: fd });
    return r.json();
  }

  // ── Required hours custom toggle ──────────────────────────
  document.getElementById('reg-hours').addEventListener('change', function() {
    document.getElementById('custom-hours-wrap').classList.toggle('hidden', this.value !== 'custom');
  });

  // ── Login ─────────────────────────────────────────────────
  document.getElementById('login-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    const identifier = document.getElementById('login-identifier').value.trim();
    const password   = document.getElementById('login-password').value;
    const errorEl    = document.getElementById('login-error');
    const btn        = document.getElementById('login-btn');

    errorEl.classList.add('hidden');
    btn.disabled = true; btn.textContent = 'Signing in...';

    const data = await post('login', { email: identifier, password });
    btn.disabled = false; btn.textContent = 'Sign In';

    if (data.ok) {
      window.location.href = 'dashboard.php';
    } else {
      errorEl.textContent = data.message || 'Invalid credentials.';
      errorEl.classList.remove('hidden');
    }
  });

  // ── Register ──────────────────────────────────────────────
  document.getElementById('register-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    const name     = document.getElementById('reg-name').value.trim();
    const username = document.getElementById('reg-username').value.trim();
    const email    = document.getElementById('reg-email').value.trim();
    const password = document.getElementById('reg-password').value;
    const hoursEl  = document.getElementById('reg-hours');
    let required_hours = hoursEl.value;
    if (required_hours === 'custom') {
      required_hours = document.getElementById('reg-custom-hours').value;
      if (!required_hours || parseInt(required_hours) < 1) {
        document.getElementById('reg-error').textContent = 'Enter a valid custom hour value.';
        document.getElementById('reg-error').classList.remove('hidden');
        return;
      }
    }
    const errorEl   = document.getElementById('reg-error');
    const successEl = document.getElementById('reg-success');
    const btn       = document.getElementById('reg-btn');

    errorEl.classList.add('hidden');
    successEl.classList.add('hidden');
    btn.disabled = true; btn.textContent = 'Creating...';

    const data = await post('register', { name, username, email, password, required_hours });
    btn.disabled = false; btn.textContent = 'Create Account';

    if (data.ok) {
      document.getElementById('register-form').reset();
      document.getElementById('custom-hours-wrap').classList.add('hidden');
      successEl.textContent = '✓ Account created! You can now sign in.';
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
