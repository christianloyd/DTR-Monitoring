<?php
// ============================================================
// admin/dashboard.php — Admin-only Dashboard
// ============================================================
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
startSession();

$user = getCurrentUser();
if (!$user || $user['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}
?>
<!doctype html>
<html lang="en" class="h-full">
 <head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Dashboard — OJT DTR</title>
  <link rel="stylesheet" href="../assets/css/style.css?v=3">
  <style>
   @keyframes scanline {
     0%   { top: 0; }
     50%  { top: calc(100% - 2px); }
     100% { top: 0; }
   }
  </style>
  <script src="../assets/js/jsqr.min.js"></script>
 </head>
 <body class="h-full bg-slate-100 font-sans">
  <div class="h-full flex flex-col">

   <!-- ── TOP NAVBAR ──────────────────────────────────────── -->
   <header class="bg-slate-900 text-white shadow-xl flex-shrink-0">
    <div class="max-w-7xl mx-auto px-4 py-3 flex items-center justify-between">
     <div class="flex items-center gap-3">
      <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-rose-500 to-rose-700 flex items-center justify-center shadow shadow-rose-500/30">
       <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
         d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
       </svg>
      </div>
      <div>
       <p class="font-bold text-sm">OJT DTR — Admin Panel</p>
       <p class="text-xs text-slate-400">Welcome, <?= htmlspecialchars($user['name']) ?></p>
      </div>
     </div>
     <div class="flex items-center gap-3">
      <a href="../index.php"
       class="text-xs text-slate-400 hover:text-white transition-colors px-3 py-1.5 rounded-lg hover:bg-slate-800">
       Trainee View
      </a>
      <button id="logout-btn"
       class="text-xs bg-slate-700 hover:bg-slate-600 px-3 py-1.5 rounded-lg transition-colors flex items-center gap-1.5">
       <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
         d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
       </svg>
       Logout
      </button>
     </div>
    </div>

    <!-- Sub-nav tabs -->
    <nav class="max-w-7xl mx-auto px-4 pb-0">
     <div class="flex gap-1">
      <button data-tab="overview"  class="adm-tab active px-4 py-2.5 text-sm font-medium rounded-t-lg whitespace-nowrap transition-all">📊 Overview</button>
      <button data-tab="trainees"  class="adm-tab px-4 py-2.5 text-sm font-medium rounded-t-lg whitespace-nowrap transition-all">👥 Trainees</button>
      <button data-tab="records"   class="adm-tab px-4 py-2.5 text-sm font-medium rounded-t-lg whitespace-nowrap transition-all">📋 Attendance</button>
      <button data-tab="scanner"   class="adm-tab px-4 py-2.5 text-sm font-medium rounded-t-lg whitespace-nowrap transition-all">📷 Scanner</button>
      <button data-tab="reports"   class="adm-tab px-4 py-2.5 text-sm font-medium rounded-t-lg whitespace-nowrap transition-all">📄 Reports</button>
      <button data-tab="logs"      class="adm-tab px-4 py-2.5 text-sm font-medium rounded-t-lg whitespace-nowrap transition-all">🕵️ Audit Logs</button>
     </div>
    </nav>
   </header>

   <!-- ── MAIN CONTENT ────────────────────────────────────── -->
   <main class="flex-1 overflow-auto">
    <div class="max-w-7xl mx-auto px-4 py-6 space-y-6">

     <!-- ══ OVERVIEW TAB ══════════════════════════════════ -->
     <div id="tab-overview" class="adm-content space-y-6 animate-fade-in">

      <!-- Stat cards -->
      <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
       <div class="bg-white rounded-2xl p-5 shadow-sm border border-slate-100 col-span-1">
        <p class="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Trainees</p>
        <p id="stat-trainees" class="text-3xl font-bold text-slate-800">—</p>
       </div>
       <div class="bg-white rounded-2xl p-5 shadow-sm border border-slate-100 col-span-1">
        <p class="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Admins</p>
        <p id="stat-admins" class="text-3xl font-bold text-slate-800">—</p>
       </div>
       <div class="bg-white rounded-2xl p-5 shadow-sm border border-slate-100 col-span-1">
        <p class="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Total Records</p>
        <p id="stat-records" class="text-3xl font-bold text-slate-800">—</p>
       </div>
       <div class="bg-white rounded-2xl p-5 shadow-sm border border-slate-100 col-span-1">
        <p class="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Today</p>
        <p id="stat-today" class="text-3xl font-bold text-emerald-600">—</p>
       </div>
       <div class="bg-white rounded-2xl p-5 shadow-sm border border-slate-100 col-span-1">
        <p class="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Total Hours</p>
        <p id="stat-hours" class="text-3xl font-bold text-primary-600">—</p>
       </div>
       <div class="bg-white rounded-2xl p-5 shadow-sm border border-slate-100 col-span-1">
        <p class="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Late Count</p>
        <p id="stat-late" class="text-3xl font-bold text-rose-500">—</p>
       </div>
      </div>

      <!-- Today's attendance snapshot -->
      <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
       <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
        <h2 class="font-semibold text-slate-800">Today's Attendance</h2>
        <button onclick="loadTodaySnapshot()" class="text-xs text-primary-600 hover:underline">Refresh</button>
       </div>
       <div class="overflow-x-auto">
        <table class="w-full text-sm">
         <thead><tr class="bg-slate-50 text-left">
          <th class="py-3 px-4 font-semibold text-slate-500 text-xs uppercase">Trainee</th>
          <th class="py-3 px-4 font-semibold text-slate-500 text-xs uppercase">Time In</th>
          <th class="py-3 px-4 font-semibold text-slate-500 text-xs uppercase">Time Out</th>
          <th class="py-3 px-4 font-semibold text-slate-500 text-xs uppercase">Hours</th>
          <th class="py-3 px-4 font-semibold text-slate-500 text-xs uppercase">Status</th>
         </tr></thead>
         <tbody id="today-tbody">
          <tr><td colspan="5" class="py-8 text-center text-slate-400">Loading…</td></tr>
         </tbody>
        </table>
       </div>
      </div>
     </div>

     <!-- ══ TRAINEES TAB ═══════════════════════════════════ -->
     <div id="tab-trainees" class="adm-content hidden animate-fade-in">
      <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
       <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between flex-wrap gap-3">
        <h2 class="font-semibold text-slate-800">All Users</h2>
        <button onclick="loadUsers()" class="text-xs text-primary-600 hover:underline">Refresh</button>
       </div>
       <div class="overflow-x-auto">
        <table class="w-full text-sm">
         <thead><tr class="bg-slate-50 text-left">
          <th class="py-3 px-4 font-semibold text-slate-500 text-xs uppercase">Name</th>
          <th class="py-3 px-4 font-semibold text-slate-500 text-xs uppercase">Username</th>
          <th class="py-3 px-4 font-semibold text-slate-500 text-xs uppercase">Email</th>
          <th class="py-3 px-4 font-semibold text-slate-500 text-xs uppercase">Role</th>
          <th class="py-3 px-4 font-semibold text-slate-500 text-xs uppercase">Progress</th>
          <th class="py-3 px-4 font-semibold text-slate-500 text-xs uppercase">Days</th>
          <th class="py-3 px-4 font-semibold text-slate-500 text-xs uppercase">Actions</th>
         </tr></thead>
         <tbody id="users-tbody">
          <tr><td colspan="7" class="py-8 text-center text-slate-400">Loading…</td></tr>
         </tbody>
        </table>
       </div>
      </div>
     </div>

     <!-- ══ ATTENDANCE TAB ═════════════════════════════════ -->
     <div id="tab-records" class="adm-content hidden animate-fade-in space-y-4">
      <!-- Filters -->
      <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-4 flex flex-wrap gap-3 items-end">
       <div>
        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">From</label>
        <input type="date" id="rec-from" class="px-3 py-2 rounded-xl border border-slate-200 text-sm outline-none focus:border-primary-500">
       </div>
       <div>
        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">To</label>
        <input type="date" id="rec-to" class="px-3 py-2 rounded-xl border border-slate-200 text-sm outline-none focus:border-primary-500">
       </div>
       <button onclick="loadRecords()"
        class="px-5 py-2 bg-primary-600 text-white text-sm font-medium rounded-xl hover:bg-primary-700 transition-colors">
        Filter
       </button>
       <button onclick="document.getElementById('rec-from').value='';document.getElementById('rec-to').value='';loadRecords()"
        class="px-5 py-2 bg-slate-100 text-slate-600 text-sm font-medium rounded-xl hover:bg-slate-200 transition-colors">
        Clear
       </button>
      </div>
      <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
       <div class="overflow-x-auto">
        <table class="w-full text-sm">
         <thead><tr class="bg-slate-50 text-left">
          <th class="py-3 px-4 font-semibold text-slate-500 text-xs uppercase">Trainee</th>
          <th class="py-3 px-4 font-semibold text-slate-500 text-xs uppercase">Date</th>
          <th class="py-3 px-4 font-semibold text-slate-500 text-xs uppercase">Time In</th>
          <th class="py-3 px-4 font-semibold text-slate-500 text-xs uppercase">Time Out</th>
          <th class="py-3 px-4 font-semibold text-slate-500 text-xs uppercase">Hours</th>
          <th class="py-3 px-4 font-semibold text-slate-500 text-xs uppercase">Late</th>
          <th class="py-3 px-4 font-semibold text-slate-500 text-xs uppercase">Status</th>
          <th class="py-3 px-4 font-semibold text-slate-500 text-xs uppercase">Actions</th>
         </tr></thead>
         <tbody id="records-tbody">
          <tr><td colspan="8" class="py-8 text-center text-slate-400">Loading…</td></tr>
         </tbody>
        </table>
       </div>
      </div>
     </div>


     <!-- ══ SCANNER TAB ═══════════════════════════════════ -->
     <div id="tab-scanner" class="adm-content hidden animate-fade-in">
      <div class="max-w-lg mx-auto space-y-4">

       <!-- Camera Viewfinder Card -->
       <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
        <div class="bg-slate-900 relative" style="aspect-ratio:4/3;">
         <!-- Camera feed -->
         <video id="qr-video" class="w-full h-full object-cover" playsinline muted></video>
         <!-- Hidden canvas for frame capture -->
         <canvas id="qr-canvas" class="hidden"></canvas>
         <!-- Scan-line overlay -->
         <div id="qr-overlay" class="absolute inset-0 flex items-center justify-center">
          <div class="relative w-56 h-56">
           <!-- Corner guides -->
           <div class="absolute top-0 left-0 w-8 h-8 border-t-4 border-l-4 border-emerald-400 rounded-tl-lg"></div>
           <div class="absolute top-0 right-0 w-8 h-8 border-t-4 border-r-4 border-emerald-400 rounded-tr-lg"></div>
           <div class="absolute bottom-0 left-0 w-8 h-8 border-b-4 border-l-4 border-emerald-400 rounded-bl-lg"></div>
           <div class="absolute bottom-0 right-0 w-8 h-8 border-b-4 border-r-4 border-emerald-400 rounded-br-lg"></div>
           <!-- Animated scan line -->
           <div id="scan-line" class="absolute left-2 right-2 h-0.5 bg-emerald-400 shadow-lg shadow-emerald-400/80" style="top:0;animation:scanline 2s linear infinite;"></div>
          </div>
         </div>
         <!-- Camera off placeholder -->
         <div id="camera-placeholder" class="absolute inset-0 flex flex-col items-center justify-center text-white bg-slate-900">
          <svg class="w-16 h-16 text-slate-600 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
          <p class="text-slate-400 text-sm">Camera is off</p>
         </div>
        </div>
        <!-- Camera Controls -->
        <div class="p-4 flex gap-3">
         <button id="start-camera-btn" class="flex-1 py-3 bg-gradient-to-r from-primary-600 to-primary-700 text-white text-sm font-semibold rounded-xl hover:from-primary-700 hover:to-primary-800 transition-all shadow-md shadow-primary-500/30 flex items-center justify-center gap-2">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.069A1 1 0 0121 8.82v6.36a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
          Start Camera
         </button>
         <button id="stop-camera-btn" disabled class="flex-1 py-3 bg-slate-100 text-slate-500 text-sm font-semibold rounded-xl hover:bg-slate-200 transition-all flex items-center justify-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 10h6v4H9z"/></svg>
          Stop
         </button>
        </div>
       </div>

       <!-- Feedback -->
       <div id="admin-scanner-error"  class="hidden text-rose-600 text-sm bg-rose-50 p-4 rounded-xl border border-rose-100 flex items-center gap-2">
        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <span id="admin-scanner-error-text"></span>
       </div>
       <div id="admin-scanner-success" class="hidden text-emerald-700 text-sm bg-emerald-50 p-4 rounded-xl border border-emerald-100 flex items-center gap-2">
        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
        <span id="admin-scanner-success-text"></span>
       </div>

       <!-- Manual Fallback -->
       <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-5">
        <p class="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-3">Manual Input Fallback</p>
        <div class="flex gap-2">
         <input type="text" id="admin-scanner-input" class="flex-1 px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 outline-none transition-all" placeholder="Enter student email or paste QR token">
         <button id="admin-scanner-btn" class="px-5 py-2.5 bg-primary-600 text-white text-sm font-semibold rounded-xl hover:bg-primary-700 transition-colors">Submit</button>
        </div>
       </div>

      </div>
     </div>


     <!-- ══ REPORTS TAB ════════════════════════════════════ -->
     <div id="tab-reports" class="adm-content hidden animate-fade-in">
      <!-- Controls row -->
      <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-5 mb-4">
       <div class="flex flex-wrap items-end gap-4">
        <div class="flex-1 min-w-[180px]">
         <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Trainee</label>
         <select id="report-trainee" class="w-full px-3 py-2.5 rounded-xl border border-slate-200 text-sm focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 outline-none transition-all">
          <option value="">— Select Trainee —</option>
         </select>
        </div>
        <div>
         <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">From</label>
         <input type="date" id="report-from" class="px-3 py-2.5 rounded-xl border border-slate-200 text-sm focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 outline-none transition-all">
        </div>
        <div>
         <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">To</label>
         <input type="date" id="report-to" class="px-3 py-2.5 rounded-xl border border-slate-200 text-sm focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 outline-none transition-all">
        </div>
        <button id="report-generate-btn" class="px-5 py-2.5 bg-gradient-to-r from-primary-600 to-primary-700 text-white text-sm font-semibold rounded-xl hover:from-primary-700 hover:to-primary-800 transition-all shadow-md shadow-primary-500/30">
         Generate Preview
        </button>
        <button id="report-print-btn" disabled class="px-5 py-2.5 border border-slate-200 text-slate-600 text-sm font-semibold rounded-xl hover:bg-slate-50 transition-all flex items-center gap-2 disabled:opacity-40 disabled:cursor-not-allowed">
         <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
         Print Report
        </button>
       </div>
      </div>

      <!-- Summary cards (shown after generate) -->
      <div id="report-summary-row" class="hidden grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 mb-4">
       <div class="bg-white rounded-xl shadow-sm border border-slate-100 p-4 text-center">
        <div id="rs-days" class="text-2xl font-bold text-primary-600">—</div>
        <div class="text-xs text-slate-500 mt-1">Days Present</div>
       </div>
       <div class="bg-white rounded-xl shadow-sm border border-slate-100 p-4 text-center">
        <div id="rs-hours" class="text-2xl font-bold text-primary-600">—</div>
        <div class="text-xs text-slate-500 mt-1">Total Hours</div>
       </div>
       <div class="bg-white rounded-xl shadow-sm border border-slate-100 p-4 text-center">
        <div id="rs-progress" class="text-2xl font-bold text-emerald-600">—</div>
        <div class="text-xs text-slate-500 mt-1">Progress</div>
       </div>
       <div class="bg-white rounded-xl shadow-sm border border-slate-100 p-4 text-center">
        <div id="rs-remaining" class="text-2xl font-bold text-slate-600">—</div>
        <div class="text-xs text-slate-500 mt-1">Hrs Remaining</div>
       </div>
       <div class="bg-white rounded-xl shadow-sm border border-slate-100 p-4 text-center">
        <div id="rs-late" class="text-2xl font-bold text-amber-500">—</div>
        <div class="text-xs text-slate-500 mt-1">Late Days</div>
       </div>
       <div class="bg-white rounded-xl shadow-sm border border-slate-100 p-4 text-center">
        <div id="rs-ot" class="text-2xl font-bold text-violet-600">—</div>
        <div class="text-xs text-slate-500 mt-1">Overtime Hrs</div>
       </div>
      </div>

      <!-- Preview Table -->
      <div id="report-preview" class="hidden bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
       <div class="px-5 py-3 border-b border-slate-100 flex items-center justify-between">
        <div>
         <p id="report-preview-title" class="font-semibold text-slate-800 text-sm"></p>
         <p id="report-preview-sub" class="text-xs text-slate-500"></p>
        </div>
        <span class="text-xs text-slate-400">Preview — use Print Report for full formatted document</span>
       </div>
       <div class="overflow-x-auto">
        <table class="w-full text-xs">
         <thead>
          <tr class="bg-slate-800 text-white text-left">
           <th class="py-3 px-4 font-semibold">Date</th>
           <th class="py-3 px-3 font-semibold">Day</th>
           <th class="py-3 px-3 font-semibold text-center">AM In</th>
           <th class="py-3 px-3 font-semibold text-center">AM Out</th>
           <th class="py-3 px-3 font-semibold text-center">PM In</th>
           <th class="py-3 px-3 font-semibold text-center">PM Out</th>
           <th class="py-3 px-3 font-semibold text-center">Total Hrs</th>
           <th class="py-3 px-3 font-semibold text-center">Late (min)</th>
           <th class="py-3 px-3 font-semibold text-center">Status</th>
          </tr>
         </thead>
         <tbody id="report-table-body">
          <tr><td colspan="9" class="py-8 text-center text-slate-400">Click "Generate Preview" to load data.</td></tr>
         </tbody>
        </table>
       </div>
      </div>

      <!-- Empty state -->
      <div id="report-empty" class="hidden text-center py-16 text-slate-400">
       <svg class="w-12 h-12 mx-auto mb-3 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
       <p class="text-sm">No attendance records found for the selected period.</p>
      </div>
     </div>

     <!-- ══ AUDIT LOGS TAB ════════════════════════════════ -->
     <div id="tab-logs" class="adm-content hidden animate-fade-in">
      <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
       <div class="px-6 py-4 border-b border-slate-100">
        <h2 class="font-semibold text-slate-800">Edit Audit Log</h2>
       </div>
       <div class="overflow-x-auto">
        <table class="w-full text-sm">
         <thead><tr class="bg-slate-50 text-left">
          <th class="py-3 px-4 font-semibold text-slate-500 text-xs uppercase">Edited By</th>
          <th class="py-3 px-4 font-semibold text-slate-500 text-xs uppercase">Date/Time</th>
          <th class="py-3 px-4 font-semibold text-slate-500 text-xs uppercase">Old In</th>
          <th class="py-3 px-4 font-semibold text-slate-500 text-xs uppercase">New In</th>
          <th class="py-3 px-4 font-semibold text-slate-500 text-xs uppercase">Old Out</th>
          <th class="py-3 px-4 font-semibold text-slate-500 text-xs uppercase">New Out</th>
          <th class="py-3 px-4 font-semibold text-slate-500 text-xs uppercase">Reason</th>
         </tr></thead>
         <tbody id="logs-tbody">
          <tr><td colspan="7" class="py-8 text-center text-slate-400">Loading…</td></tr>
         </tbody>
        </table>
       </div>
      </div>
     </div>

    </div>
   </main>

  </div><!-- /flex -->

  <!-- ── EDIT RECORD MODAL ──────────────────────────────── -->
  <div id="edit-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
   <div id="edit-modal-backdrop" class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm"></div>
   <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-sm p-7 animate-slide-up">
    <h3 class="text-lg font-bold text-slate-800 mb-5">Edit Attendance Record</h3>
    <form id="edit-record-form" class="space-y-4">
     <input type="hidden" id="edit-rec-id">
     <div>
      <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Date</label>
      <input type="text" id="edit-rec-date" disabled class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm bg-slate-50 text-slate-500">
     </div>
     <div class="grid grid-cols-2 gap-3">
      <div>
       <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Time In</label>
       <input type="time" id="edit-rec-in" required class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:border-rose-500 outline-none">
      </div>
      <div>
       <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Time Out</label>
       <input type="time" id="edit-rec-out" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:border-rose-500 outline-none">
      </div>
     </div>
     <div>
      <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Reason for Edit</label>
      <textarea id="edit-rec-reason" required rows="2"
       class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:border-rose-500 outline-none resize-none"
       placeholder="State the reason…"></textarea>
     </div>
     <div id="edit-rec-error" class="hidden text-red-600 text-sm bg-red-50 p-3 rounded-xl"></div>
     <div class="flex gap-3 pt-1">
      <button type="button" onclick="closeEditModal()"
       class="flex-1 py-2.5 rounded-xl border border-slate-200 text-sm font-medium text-slate-600 hover:bg-slate-50 transition-colors">Cancel</button>
      <button type="submit" id="edit-rec-save"
       class="flex-1 py-2.5 rounded-xl bg-gradient-to-r from-rose-600 to-rose-700 text-white text-sm font-medium hover:from-rose-700 hover:to-rose-800 transition-all">Save</button>
     </div>
    </form>
   </div>
  </div>

  <!-- ── DELETE CONFIRM MODAL ──────────────────────────── -->
  <div id="del-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
   <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" onclick="closeDelModal()"></div>
   <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-xs p-7 animate-slide-up text-center">
    <div class="w-14 h-14 rounded-full bg-red-100 flex items-center justify-center mx-auto mb-4">
     <svg class="w-7 h-7 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
       d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
     </svg>
    </div>
    <h3 class="font-bold text-slate-800 mb-1">Delete User?</h3>
    <p id="del-modal-name" class="text-slate-500 text-sm mb-5"></p>
    <div class="flex gap-3">
     <button onclick="closeDelModal()" class="flex-1 py-2.5 rounded-xl border border-slate-200 text-sm font-medium text-slate-600 hover:bg-slate-50">Cancel</button>
     <button id="del-confirm-btn" class="flex-1 py-2.5 rounded-xl bg-red-500 text-white text-sm font-medium hover:bg-red-600 transition-colors">Delete</button>
    </div>
   </div>
  </div>

  <!-- Toast -->
  <div id="toast-container" class="fixed bottom-4 right-4 z-50 space-y-2"></div>

  <script src="../assets/js/admin.js"></script>
 </body>
</html>
