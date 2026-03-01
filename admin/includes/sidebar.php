<!-- ── Mobile sidebar backdrop ───────────────────────── -->
<div id="sidebar-backdrop" class="fixed inset-0 bg-slate-900/40 z-40 hidden lg:hidden backdrop-blur-sm transition-opacity" onclick="closeSidebar()"></div>

<!-- ══════════════════════════════════════════════════
     LEFT SIDEBAR
══════════════════════════════════════════════════ -->
<aside id="sidebar"
 class="fixed inset-y-0 left-0 z-50 w-64 bg-slate-900 text-white flex flex-col shadow-2xl 
        transform -translate-x-full lg:translate-x-0 transition-transform duration-300 ease-in-out border-r border-slate-800">

 <!-- Brand -->
 <div class="px-6 py-6 border-b border-slate-800/60 flex-shrink-0">
  <div class="flex items-center gap-3">
   <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-rose-500 to-rose-700 flex items-center justify-center shadow-lg shadow-rose-500/30">
    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
     <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
      d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
    </svg>
   </div>
   <div>
    <p class="font-bold text-base leading-tight">OJT DTR</p>
    <p class="text-xs font-medium text-slate-400">Admin Panel</p>
   </div>
  </div>
 </div>

 <!-- Nav items -->
 <nav class="flex-1 px-4 py-6 space-y-1.5 overflow-y-auto custom-scrollbar">

  <p class="px-3 text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-3">Main Menu</p>

  <button data-tab="overview" class="adm-tab w-full flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium transition-all text-left">
   <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
   </svg>
   Overview
  </button>

  <button data-tab="trainees" class="adm-tab w-full flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium transition-all text-left">
   <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0"/>
   </svg>
   Trainees
  </button>

  <button data-tab="records" class="adm-tab w-full flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium transition-all text-left">
   <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
   </svg>
   Attendance
  </button>

  <button data-tab="scanner" class="adm-tab w-full flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium transition-all text-left">
   <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/>
    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/>
   </svg>
   QR Scanner
  </button>

  <button data-tab="reports" class="adm-tab w-full flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium transition-all text-left">
   <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
   </svg>
   Reports
  </button>

  <p class="px-3 pt-4 text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-3">System</p>

  <button data-tab="logs" class="adm-tab w-full flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium transition-all text-left">
   <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
   </svg>
   Audit Logs
  </button>

 </nav>

 <!-- Bottom actions -->
 <div class="px-4 py-5 border-t border-slate-800/60 flex-shrink-0 space-y-2">
  <a href="../index.php"
   class="w-full flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl text-sm font-medium text-slate-300 hover:text-white hover:bg-slate-800 transition-all border border-slate-700/50 hover:border-slate-600">
   <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0zM2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
   </svg>
   Switch to Trainee
  </a>
  <button id="logout-btn"
   class="w-full flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl text-sm font-bold text-rose-400 hover:text-white bg-rose-500/10 hover:bg-rose-600 transition-all border border-rose-500/20 hover:border-rose-500">
   <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
   </svg>
   Sign Out
  </button>
 </div>

</aside>
