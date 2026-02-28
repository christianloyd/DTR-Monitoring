<?php
// ============================================================
// ojt/dashboard.php — OJT Trainee Dashboard
// ============================================================
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
startSession();

$user = getCurrentUser();
if (!$user || $user['role'] === 'admin') {
    // Admins have their own dashboard; guests go to login
    header('Location: login.php');
    exit;
}
?>
<!doctype html>
<html lang="en" class="h-full">
 <head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>OJT Trainee Dashboard — DTR System</title>
  <link rel="stylesheet" href="../assets/css/style.css?v=3">
  <!-- QR Code library (local, avoids browser tracking prevention blocks) -->
  <script src="../assets/js/qrcode.min.js"></script>
 </head>
 <body class="h-full bg-slate-100 font-sans">
  <div id="app" class="h-full w-full overflow-auto">

   <!-- ======================================================
        DASHBOARD PAGE
   ====================================================== -->
   <div id="dashboard-page" class="min-h-full bg-slate-100">
    <!-- Header -->
    <header class="bg-white border-b border-slate-200 sticky top-0 z-50 shadow-sm">
     <div class="max-w-7xl mx-auto px-4 py-3 flex items-center justify-between">
      <div class="flex items-center gap-3">
       <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-primary-500 to-primary-700 flex items-center justify-center shadow-lg shadow-primary-500/20">
        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
       </div>
       <div>
        <h1 class="font-bold text-slate-800 text-lg">OJT DTR</h1>
        <p class="text-xs text-slate-500">Time Record System</p>
       </div>
      </div>
      <div class="flex items-center gap-3">
       <div class="text-right hidden sm:block">
        <p id="user-name-display" class="text-sm font-medium text-slate-800"></p>
        <p id="user-role-display" class="text-xs text-slate-500"></p>
       </div>
       <button id="logout-btn" class="p-2 rounded-lg hover:bg-slate-100 text-slate-600 transition-all" title="Logout">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
       </button>
      </div>
     </div>
     <!-- Navigation Tabs -->
     <nav class="max-w-7xl mx-auto px-4 pb-2">
      <div class="flex gap-1 overflow-x-auto">
       <button data-tab="overview"  class="tab-btn active px-4 py-2 text-sm font-medium rounded-lg whitespace-nowrap transition-all text-primary-700 bg-primary-100">Dashboard</button>
       <button data-tab="history"   class="tab-btn px-4 py-2 text-sm font-medium rounded-lg whitespace-nowrap transition-all text-slate-600 hover:bg-slate-100">Attendance History</button>
       <button data-tab="qr"        class="tab-btn px-4 py-2 text-sm font-medium rounded-lg whitespace-nowrap transition-all text-slate-600 hover:bg-slate-100">My Digital ID</button>
       <button data-tab="settings"  class="tab-btn px-4 py-2 text-sm font-medium rounded-lg whitespace-nowrap transition-all text-slate-600 hover:bg-slate-100">⚙️ Settings</button>
       <button data-tab="admin" id="admin-tab-btn" class="hidden tab-btn px-4 py-2 text-sm font-medium rounded-lg whitespace-nowrap transition-all text-slate-600 hover:bg-slate-100">Admin Panel</button>
      </div>
     </nav>
    </header>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-4 py-6">

     <!-- OVERVIEW TAB -->
     <div id="tab-overview" class="tab-content space-y-6 animate-fade-in">
      <!-- Live Clock -->
      <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-100">
       <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
         <p class="text-sm text-slate-500 mb-1">Current Time</p>
         <div id="live-clock" class="text-4xl md:text-5xl font-bold text-slate-800 tabular-nums">--:--:--</div>
         <p id="live-date" class="text-slate-500 mt-1">Loading...</p>
        </div>
        <div id="time-actions" class="flex flex-col sm:flex-row gap-3">
         <button id="time-in-btn" class="px-6 py-3 bg-gradient-to-r from-emerald-500 to-emerald-600 text-white font-medium rounded-xl hover:from-emerald-600 hover:to-emerald-700 transition-all shadow-lg shadow-emerald-500/30 flex items-center justify-center gap-2">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/></svg>
          Time In
         </button>
         <button id="time-out-btn" disabled class="px-6 py-3 bg-gradient-to-r from-rose-500 to-rose-600 text-white font-medium rounded-xl hover:from-rose-600 hover:to-rose-700 transition-all shadow-lg shadow-rose-500/30 flex items-center justify-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
          Time Out
         </button>
         <button id="add-log-btn" class="px-6 py-3 bg-gradient-to-r from-slate-600 to-slate-700 text-white font-medium rounded-xl hover:from-slate-700 hover:to-slate-800 transition-all shadow-lg shadow-slate-500/20 flex items-center justify-center gap-2">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
          Add Time Log
         </button>
        </div>
       </div>
      </div>
      <!-- Today's Status + Hours Summary -->
      <div class="grid md:grid-cols-2 gap-4">
       <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-100">
        <h3 class="text-sm font-medium text-slate-500 mb-4 flex items-center gap-2">
         <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
         Today's Record
        </h3>
        <div class="space-y-3">
         <div class="flex justify-between items-center py-2 border-b border-slate-100"><span class="text-slate-600">Time In</span><span id="today-time-in" class="font-semibold text-slate-800">--:--</span></div>
         <div class="flex justify-between items-center py-2 border-b border-slate-100"><span class="text-slate-600">Time Out</span><span id="today-time-out" class="font-semibold text-slate-800">--:--</span></div>
         <div class="flex justify-between items-center py-2 border-b border-slate-100"><span class="text-slate-600">Total Hours</span><span id="today-total-hours" class="font-semibold text-slate-800">0.00 hrs</span></div>
         <div class="flex justify-between items-center py-2"><span class="text-slate-600">Status</span><span id="today-status" class="px-3 py-1 rounded-full text-xs font-medium bg-slate-100 text-slate-600">No Record</span></div>
        </div>
       </div>
       <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-100">
        <h3 class="text-sm font-medium text-slate-500 mb-4 flex items-center gap-2">
         <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
         Hours Summary
        </h3>
        <div class="space-y-4">
         <div>
          <div class="flex justify-between text-sm mb-1"><span class="text-slate-600">Total Hours Rendered</span><span id="total-hours-rendered" class="font-semibold text-slate-800">0.00 hrs</span></div>
          <div class="w-full bg-slate-200 rounded-full h-2">
           <div id="hours-progress" class="bg-gradient-to-r from-primary-500 to-primary-600 h-2 rounded-full transition-all duration-500" style="width:0%"></div>
          </div>
          <p class="text-xs text-slate-500 mt-1">Target: <span id="target-hours">486</span> hours (OJT requirement)</p>
         </div>
         <div class="grid grid-cols-2 gap-3 pt-2">
          <div class="bg-emerald-50 rounded-xl p-3"><p class="text-xs text-emerald-600 mb-1">Overtime</p><p id="total-overtime" class="text-lg font-bold text-emerald-700">0.00 hrs</p></div>
          <div class="bg-amber-50  rounded-xl p-3"><p class="text-xs text-amber-600  mb-1">Undertime</p><p id="total-undertime" class="text-lg font-bold text-amber-700">0.00 hrs</p></div>
          <div class="bg-rose-50   rounded-xl p-3"><p class="text-xs text-rose-600   mb-1">Late Instances</p><p id="total-late-count" class="text-lg font-bold text-rose-700">0</p></div>
          <div class="bg-primary-50 rounded-xl p-3"><p class="text-xs text-primary-600 mb-1">Days Worked</p><p id="total-days-worked" class="text-lg font-bold text-primary-700">0</p></div>
         </div>
        </div>
       </div>
      </div>
      <!-- Working Hours Schedule -->
      <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-100">
       <h3 class="text-sm font-medium text-slate-500 mb-3 flex items-center gap-2">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        Working Hours Schedule
       </h3>
       <div class="grid sm:grid-cols-2 gap-4">
        <div class="flex items-center gap-3 p-3 bg-slate-50 rounded-xl">
         <div class="w-10 h-10 rounded-lg bg-primary-100 flex items-center justify-center">
          <svg class="w-5 h-5 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707"/></svg>
         </div>
         <div><p class="text-xs text-slate-500">Morning</p><p class="font-semibold text-slate-800">8:00 AM – 12:00 PM</p></div>
        </div>
        <div class="flex items-center gap-3 p-3 bg-slate-50 rounded-xl">
         <div class="w-10 h-10 rounded-lg bg-amber-100 flex items-center justify-center">
          <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
         </div>
         <div><p class="text-xs text-slate-500">Afternoon</p><p class="font-semibold text-slate-800">1:00 PM – 5:00 PM</p></div>
        </div>
       </div>
       <p class="text-xs text-slate-500 mt-3"><span class="text-amber-600">Note:</span> Required 8 hours/day. 1-hour lunch break (12:00 PM–1:00 PM) is auto-deducted.</p>
      </div>
     </div>

     <!-- HISTORY TAB -->
     <div id="tab-history" class="tab-content hidden space-y-4 animate-fade-in">
      <div class="bg-white rounded-2xl p-4 shadow-sm border border-slate-100">
       <div class="flex flex-col sm:flex-row gap-3 justify-between items-start sm:items-center mb-4">
        <h2 class="text-lg font-semibold text-slate-800">Attendance History</h2>
        <div class="flex gap-2 w-full sm:w-auto">
         <input type="date" id="filter-date-from" class="flex-1 sm:flex-none px-3 py-2 text-sm border border-slate-200 rounded-lg focus:border-primary-500 outline-none">
         <input type="date" id="filter-date-to"   class="flex-1 sm:flex-none px-3 py-2 text-sm border border-slate-200 rounded-lg focus:border-primary-500 outline-none">
         <button id="filter-btn" class="px-4 py-2 bg-primary-600 text-white text-sm font-medium rounded-lg hover:bg-primary-700 transition-all">Filter</button>
        </div>
       </div>
       <div class="overflow-x-auto">
        <table class="w-full text-sm">
         <thead>
          <tr class="border-b border-slate-200 bg-slate-50">
           <th class="text-left py-3 px-3 font-medium text-slate-600 rounded-tl-lg">Date</th>
           <th class="text-left py-3 px-3 font-medium text-slate-600">Time In</th>
           <th class="text-left py-3 px-3 font-medium text-slate-600">Time Out</th>
           <th class="text-left py-3 px-3 font-medium text-slate-600">Hours</th>
           <th class="text-left py-3 px-3 font-medium text-slate-600">Late</th>
           <th class="text-left py-3 px-3 font-medium text-slate-600">Status</th>
           <th class="text-left py-3 px-3 font-medium text-slate-600 rounded-tr-lg">Actions</th>
          </tr>
         </thead>
         <tbody id="history-table-body" class="divide-y divide-slate-100">
          <tr><td colspan="7" class="py-8 text-center text-slate-500">No attendance records found</td></tr>
         </tbody>
        </table>
       </div>
      </div>
     </div>

     <!-- MY DIGITAL ID TAB -->
     <div id="tab-qr" class="tab-content hidden space-y-4 animate-fade-in max-w-md mx-auto">
      <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
       <!-- ID Card Header -->
       <div class="bg-gradient-to-r from-primary-600 to-primary-700 p-6 text-center">
        <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-white/20 backdrop-blur-sm mb-4">
         <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
        </div>
        <h2 id="id-card-name" class="text-xl font-bold text-white mb-1">Loading Name...</h2>
        <p class="text-primary-100 text-sm">OJT Trainee</p>
       </div>
       <!-- ID Card Body (QR Code) -->
       <div class="p-8 flex flex-col items-center justify-center bg-slate-50">
        <div class="bg-white p-4 rounded-xl shadow-sm border border-slate-100 mb-4">
         <div id="qrcode-container" class="flex items-center justify-center" style="width:200px;height:200px;">
          <span class="text-slate-400">Generating QR...</span>
         </div>
        </div>
        <p class="text-slate-500 text-sm text-center">Show this QR code to the Admin<br>to manually log your attendance.</p>
       </div>
       <!-- ID Card Footer -->
       <div class="bg-white p-4 text-center border-t border-slate-100">
        <p id="id-card-email" class="text-sm font-medium text-slate-800">user@email.com</p>
        <p class="text-xs text-slate-500 mt-1">Digital ID Token</p>
       </div>
      </div>
     </div>

     <!-- ADMIN TAB -->
     <div id="tab-admin" class="tab-content hidden space-y-4 animate-fade-in">
      <div class="bg-white rounded-2xl p-4 shadow-sm border border-slate-100">
       <h2 class="text-lg font-semibold text-slate-800 mb-4">All OJT Records</h2>
       <div class="overflow-x-auto">
        <table class="w-full text-sm">
         <thead>
          <tr class="border-b border-slate-200 bg-slate-50">
           <th class="text-left py-3 px-3 font-medium text-slate-600 rounded-tl-lg">Name</th>
           <th class="text-left py-3 px-3 font-medium text-slate-600">Date</th>
           <th class="text-left py-3 px-3 font-medium text-slate-600">Time In</th>
           <th class="text-left py-3 px-3 font-medium text-slate-600">Time Out</th>
           <th class="text-left py-3 px-3 font-medium text-slate-600">Hours</th>
           <th class="text-left py-3 px-3 font-medium text-slate-600 rounded-tr-lg">Status</th>
          </tr>
         </thead>
         <tbody id="admin-table-body" class="divide-y divide-slate-100">
          <tr><td colspan="6" class="py-8 text-center text-slate-500">No records found</td></tr>
         </tbody>
        </table>
       </div>
      </div>
      <div class="bg-white rounded-2xl p-4 shadow-sm border border-slate-100">
       <h2 class="text-lg font-semibold text-slate-800 mb-4">Edit Audit Logs</h2>
       <div class="overflow-x-auto">
        <table class="w-full text-sm">
         <thead>
          <tr class="border-b border-slate-200 bg-slate-50">
           <th class="text-left py-3 px-3 font-medium text-slate-600 rounded-tl-lg">User</th>
           <th class="text-left py-3 px-3 font-medium text-slate-600">Edited At</th>
           <th class="text-left py-3 px-3 font-medium text-slate-600">Old Time In</th>
           <th class="text-left py-3 px-3 font-medium text-slate-600">New Time In</th>
           <th class="text-left py-3 px-3 font-medium text-slate-600">Old Time Out</th>
           <th class="text-left py-3 px-3 font-medium text-slate-600">New Time Out</th>
           <th class="text-left py-3 px-3 font-medium text-slate-600 rounded-tr-lg">Reason</th>
          </tr>
         </thead>
         <tbody id="audit-table-body" class="divide-y divide-slate-100">
          <tr><td colspan="7" class="py-8 text-center text-slate-500">No edit logs found</td></tr>
         </tbody>
        </table>
       </div>
      </div>
     </div>

     <!-- SETTINGS TAB -->
     <div id="tab-settings" class="tab-content hidden space-y-4 animate-fade-in max-w-xl mx-auto">
      <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-100">
       <div class="flex items-center gap-3 mb-6">
        <div class="w-10 h-10 rounded-xl bg-primary-100 flex items-center justify-center">
         <svg class="w-5 h-5 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
        </div>
        <div>
         <h2 class="text-lg font-bold text-slate-800">Account Settings</h2>
         <p class="text-xs text-slate-500">Manage your profile information</p>
        </div>
       </div>
       <!-- Profile info (read-only) -->
       <div class="space-y-3 mb-6">
        <div class="flex justify-between items-center py-3 border-b border-slate-100">
         <span class="text-sm text-slate-500">Full Name</span>
         <span id="settings-name" class="font-semibold text-slate-800 text-sm"></span>
        </div>
        <div class="flex justify-between items-center py-3 border-b border-slate-100">
         <span class="text-sm text-slate-500">Email</span>
         <span id="settings-email" class="font-semibold text-slate-800 text-sm"></span>
        </div>
        <div class="flex justify-between items-center py-3 border-b border-slate-100">
         <span class="text-sm text-slate-500">Required Hours</span>
         <span id="settings-hours" class="font-semibold text-slate-800 text-sm"></span>
        </div>
       </div>
       <!-- Training Supervisor (editable) -->
       <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 mb-5">
        <label class="block text-xs font-semibold text-amber-700 uppercase tracking-wide mb-1">Training Supervisor <span class="text-rose-500">*</span></label>
        <p class="text-xs text-amber-600 mb-3">This name will appear on your printed DTR report as the certifying supervisor.</p>
        <input type="text" id="settings-supervisor" placeholder="e.g. Juan dela Cruz" class="w-full px-4 py-3 rounded-xl border border-amber-300 bg-white focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 outline-none transition-all text-sm font-medium">
       </div>
       <div id="settings-msg" class="hidden mb-4 text-sm p-3 rounded-lg"></div>
       <button id="save-settings-btn" class="w-full py-3 px-6 bg-gradient-to-r from-primary-600 to-primary-700 text-white font-semibold rounded-xl hover:from-primary-700 hover:to-primary-800 transition-all shadow-lg shadow-primary-500/30 text-sm">Save Settings</button>
      </div>
     </div>

    </main>
   </div><!-- /dashboard-page -->

   <!-- EDIT MODAL -->
   <div id="edit-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm" id="edit-modal-backdrop"></div>
    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md p-6 animate-slide-up border border-slate-100">
     <h3 class="text-xl font-semibold text-slate-800 mb-4">Edit Attendance Record</h3>
     <form id="edit-form" class="space-y-4">
      <input type="hidden" id="edit-record-id">
      <div><label class="block text-sm font-medium text-slate-700 mb-1">Date</label>
       <input type="text" id="edit-date" readonly class="w-full px-4 py-3 rounded-xl border border-slate-200 bg-slate-50 text-slate-500">
      </div>
      <div><label class="block text-sm font-medium text-slate-700 mb-1">Time In</label>
       <input type="time" id="edit-time-in" required class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 outline-none transition-all">
      </div>
      <div><label class="block text-sm font-medium text-slate-700 mb-1">Time Out</label>
       <input type="time" id="edit-time-out" class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 outline-none transition-all">
      </div>
      <div><label class="block text-sm font-medium text-slate-700 mb-1">Reason for Edit <span class="text-rose-500">*</span></label>
       <textarea id="edit-reason" required rows="3" class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 outline-none transition-all resize-none" placeholder="Explain why you need to edit this record..."></textarea>
      </div>
      <div id="edit-error" class="hidden text-red-500 text-sm bg-red-50 p-3 rounded-lg border border-red-100"></div>
      <div class="flex gap-3 pt-2">
       <button type="button" id="cancel-edit-btn" class="flex-1 py-3 px-4 border border-slate-200 text-slate-700 font-medium rounded-xl hover:bg-slate-50 transition-all">Cancel</button>
       <button type="submit"  id="save-edit-btn"   class="flex-1 py-3 px-4 bg-gradient-to-r from-primary-600 to-primary-700 text-white font-medium rounded-xl hover:from-primary-700 hover:to-primary-800 transition-all shadow-lg shadow-primary-500/30">Save Changes</button>
      </div>
     </form>
    </div>
   </div>

   <!-- ADD TIME LOG MODAL -->
   <div id="add-log-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm" id="add-log-modal-backdrop"></div>
    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-2xl p-6 animate-slide-up border border-slate-100">
     <div class="flex items-center gap-3 mb-4">
      <div class="w-9 h-9 rounded-xl bg-primary-100 flex items-center justify-center">
       <svg class="w-5 h-5 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
      </div>
      <div>
       <h3 class="text-lg font-bold text-slate-800">Add Time Log</h3>
       <p class="text-xs text-slate-500">Record attendance for a past date</p>
      </div>
     </div>

     <form id="add-log-form" class="space-y-5">
      <!-- Date row -->
      <div>
       <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Date <span class="text-rose-500">*</span></label>
       <input type="date" id="add-log-date" required class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 outline-none transition-all text-sm">
      </div>

      <!-- Morning row -->
      <div>
       <div class="flex items-center gap-2 mb-2">
        <div class="w-2 h-2 rounded-full bg-primary-500"></div>
        <p class="text-xs font-semibold text-slate-600 uppercase tracking-wide">Morning Session</p>
       </div>
       <div class="grid grid-cols-2 gap-3">
        <div>
         <label class="block text-xs text-slate-500 mb-1">Time In <span class="text-rose-500">*</span></label>
         <input type="time" id="add-log-am-in" required class="w-full px-3 py-2.5 rounded-xl border border-slate-200 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 outline-none transition-all text-sm">
        </div>
        <div>
         <label class="block text-xs text-slate-500 mb-1">Time Out <span class="text-rose-500">*</span></label>
         <input type="time" id="add-log-am-out" required class="w-full px-3 py-2.5 rounded-xl border border-slate-200 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 outline-none transition-all text-sm">
        </div>
       </div>
      </div>

      <!-- Afternoon row -->
      <div>
       <div class="flex items-center gap-2 mb-2">
        <div class="w-2 h-2 rounded-full bg-amber-500"></div>
        <p class="text-xs font-semibold text-slate-600 uppercase tracking-wide">Afternoon Session</p>
       </div>
       <div class="grid grid-cols-2 gap-3">
        <div>
         <label class="block text-xs text-slate-500 mb-1">Time In <span class="text-rose-500">*</span></label>
         <input type="time" id="add-log-pm-in" required class="w-full px-3 py-2.5 rounded-xl border border-slate-200 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 outline-none transition-all text-sm">
        </div>
        <div>
         <label class="block text-xs text-slate-500 mb-1">Time Out <span class="text-rose-500">*</span></label>
         <input type="time" id="add-log-pm-out" required class="w-full px-3 py-2.5 rounded-xl border border-slate-200 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 outline-none transition-all text-sm">
        </div>
       </div>
      </div>

      <!-- Overtime row -->
      <div>
       <div class="flex items-center gap-2 mb-2">
        <div class="w-2 h-2 rounded-full bg-emerald-500"></div>
        <p class="text-xs font-semibold text-slate-600 uppercase tracking-wide">Overtime <span class="text-slate-400 font-normal normal-case">(optional)</span></p>
       </div>
       <div class="grid grid-cols-2 gap-3">
        <div>
         <label class="block text-xs text-slate-500 mb-1">Overtime In</label>
         <input type="time" id="add-log-ot-in" class="w-full px-3 py-2.5 rounded-xl border border-slate-200 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 outline-none transition-all text-sm">
        </div>
        <div>
         <label class="block text-xs text-slate-500 mb-1">Overtime Out</label>
         <input type="time" id="add-log-ot-out" class="w-full px-3 py-2.5 rounded-xl border border-slate-200 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 outline-none transition-all text-sm">
        </div>
       </div>
      </div>

      <div id="add-log-error" class="hidden text-red-500 text-sm bg-red-50 p-3 rounded-lg border border-red-100"></div>
      <div class="flex gap-3 pt-1 border-t border-slate-100">
       <button type="button" id="cancel-add-log-btn" class="flex-1 py-2.5 px-4 border border-slate-200 text-slate-700 font-medium rounded-xl hover:bg-slate-50 transition-all text-sm">Cancel</button>
       <button type="submit"  id="save-add-log-btn"   class="flex-1 py-2.5 px-4 bg-gradient-to-r from-primary-600 to-primary-700 text-white font-medium rounded-xl hover:from-primary-700 hover:to-primary-800 transition-all shadow-lg shadow-primary-500/30 text-sm">Save Log Entry</button>
      </div>
     </form>
    </div>
   </div>


   <!-- NOTICE MODAL -->
   <div id="notice-modal" class="hidden fixed inset-0 z-[999] flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm"></div>
    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-sm p-8 animate-slide-up text-center">
     <!-- Warning Icon -->
     <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-amber-50 mb-5">
      <svg class="w-8 h-8 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
       <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
        d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
      </svg>
     </div>
     <h2 class="text-xl font-bold text-slate-800 mb-4">Notice Before Use</h2>
     <p class="text-slate-600 text-sm leading-relaxed mb-4">
      This tool is a <strong>personal project</strong> created for educational
      purposes. It is <strong>NOT officially affiliated</strong> with any university.
     </p>
     <p class="text-slate-600 text-sm leading-relaxed mb-8">
      Computed hours are for personal tracking only and should not replace
      official records. Follow your coordinator&#39;s official guidelines.
     </p>
     <button id="notice-accept-btn"
      class="w-full py-3 px-6 bg-slate-900 text-white font-semibold rounded-xl hover:bg-slate-700 transition-all text-sm shadow-lg shadow-slate-900/30">
      I Understand, Continue
     </button>
    </div>
   </div>

   <!-- TRAINING SUPERVISOR NOTICE MODAL -->
   <div id="supervisor-modal" class="hidden fixed inset-0 z-[1000] flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-slate-900/70 backdrop-blur-sm"></div>
    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-sm p-8 animate-slide-up">
     <!-- Icon -->
     <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-primary-50 mb-5">
      <svg class="w-8 h-8 text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
       <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
      </svg>
     </div>
     <h2 class="text-xl font-bold text-slate-800 mb-2">Training Supervisor Required</h2>
     <p class="text-slate-500 text-sm leading-relaxed mb-5">
      Please input the name of your <strong class="text-slate-700">Training Supervisor</strong>. This will appear on your printed DTR report as the certifying authority.
     </p>
     <div class="mb-4">
      <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wide mb-1">Training Supervisor Name <span class="text-rose-500">*</span></label>
      <input type="text" id="supervisor-modal-input" placeholder="e.g. Juan dela Cruz"
       class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 outline-none transition-all text-sm font-medium">
     </div>
     <div id="supervisor-modal-error" class="hidden text-red-500 text-xs bg-red-50 p-2 rounded-lg border border-red-100 mb-3"></div>
     <button id="supervisor-modal-save-btn"
      class="w-full py-3 px-6 bg-gradient-to-r from-primary-600 to-primary-700 text-white font-semibold rounded-xl hover:from-primary-700 hover:to-primary-800 transition-all shadow-lg shadow-primary-500/30 text-sm">
      Save &amp; Continue
     </button>
     <p class="text-xs text-slate-400 text-center mt-3">You can update this anytime in ⚙️ Settings</p>
    </div>
   </div>

   <!-- TOAST CONTAINER -->
   <div id="toast-container" class="fixed bottom-4 right-4 z-50 space-y-2"></div>

  </div><!-- /app -->
  
  <!-- App JS -->
  <script src="../assets/js/ojt-dashboard.js?v=3"></script>
 </body>
</html>
