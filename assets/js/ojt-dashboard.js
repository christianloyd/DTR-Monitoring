// ============================================================
// assets/js/ojt-dashboard.js — OJT Dashboard JS
// All API paths are relative to ojt/ → ../api/
// ============================================================

const OJT_TARGET_HOURS = 486;
const WORK_START_HOUR = 8;
const LUNCH_START_HOUR = 12;
const LUNCH_END_HOUR = 13;
const REQUIRED_DAILY_HRS = 8;

let currentUser = null;
let clockInterval = null;
let currentQRToken = null;

// ── Utility ───────────────────────────────────────────────
function formatTime(dtStr) {
    if (!dtStr) return '--:--';
    return new Date(dtStr).toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', hour12: true });
}
function formatDate(dtStr) {
    if (!dtStr) return '--';
    return new Date(dtStr).toLocaleDateString('en-US', { weekday: 'short', month: 'short', day: 'numeric', year: 'numeric' });
}
function getStatusBadgeClass(status) {
    switch (status) {
        case 'On Time': return 'bg-emerald-100 text-emerald-700';
        case 'Late': return 'bg-rose-100 text-rose-700';
        case 'Overtime': return 'bg-blue-100 text-blue-700';
        case 'Undertime': return 'bg-amber-100 text-amber-700';
        default: return 'bg-slate-100 text-slate-600';
    }
}
function showToast(message, type = 'success') {
    const container = document.getElementById('toast-container');
    const toast = document.createElement('div');
    const bg = type === 'success' ? 'bg-emerald-500' : type === 'error' ? 'bg-rose-500' : 'bg-amber-500';
    toast.className = `${bg} text-white px-4 py-3 rounded-xl shadow-lg animate-slide-up flex items-center gap-2 text-sm`;
    toast.innerHTML = `<svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      ${type === 'success'
            ? '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>'
            : '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>'}
    </svg><span>${message}</span>`;
    container.appendChild(toast);
    setTimeout(() => toast.remove(), 4000);
}

// ── Clock ─────────────────────────────────────────────────
function updateClock() {
    const now = new Date();
    document.getElementById('live-clock').textContent =
        now.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false });
    document.getElementById('live-date').textContent =
        now.toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
}
function startClock() { updateClock(); clockInterval = setInterval(updateClock, 1000); }

// ── API helpers ───────────────────────────────────────────
async function apiGet(url) { const r = await fetch(url); return r.json(); }
async function apiPost(url, body) {
    const fd = new FormData();
    Object.entries(body).forEach(([k, v]) => fd.append(k, v ?? ''));
    const r = await fetch(url, { method: 'POST', body: fd });
    return r.json();
}

// ── Notice modal (disclaimer) ────────────────────────────
function showNotice() { document.getElementById('notice-modal').classList.remove('hidden'); }
function hideNotice() {
    document.getElementById('notice-modal').classList.add('hidden');
    sessionStorage.setItem('notice_accepted', '1');
}

// ── Training Supervisor modal ─────────────────────────────
function showSupervisorModal() {
    document.getElementById('supervisor-modal').classList.remove('hidden');
    document.getElementById('supervisor-modal-input').focus();
}
function hideSupervisorModal() {
    document.getElementById('supervisor-modal').classList.add('hidden');
}
async function handleSupervisorModalSave() {
    const input = document.getElementById('supervisor-modal-input');
    const errEl = document.getElementById('supervisor-modal-error');
    const btn = document.getElementById('supervisor-modal-save-btn');
    const value = input.value.trim();
    if (!value) {
        errEl.textContent = 'Please enter your Training Supervisor\'s name.';
        errEl.classList.remove('hidden');
        return;
    }
    errEl.classList.add('hidden');
    btn.disabled = true; btn.textContent = 'Saving...';
    const data = await apiPost('../api/auth.php', { action: 'update_settings', training_supervisor: value });
    btn.disabled = false; btn.textContent = 'Save & Continue';
    if (data.ok) {
        if (currentUser) currentUser.training_supervisor = value;
        // Pre-fill settings tab input too
        const settingsEl = document.getElementById('settings-supervisor');
        if (settingsEl) settingsEl.value = value;
        hideSupervisorModal();
        showToast('Training Supervisor saved!');
    } else {
        errEl.textContent = data.message || 'Save failed. Try again.';
        errEl.classList.remove('hidden');
    }
}

// ── Logout ────────────────────────────────────────────────
async function handleLogout() {
    await apiPost('../api/auth.php', { action: 'logout' });
    if (clockInterval) clearInterval(clockInterval);
    sessionStorage.removeItem('notice_accepted'); // Reset so notice shows on next login
    window.location.href = 'login.php';
}

// ── Dashboard load ────────────────────────────────────────
function bootDashboard(user) {
    currentUser = user;
    document.getElementById('user-name-display').textContent = user.name;
    document.getElementById('user-role-display').textContent =
        user.role === 'admin' ? 'Administrator' : 'OJT Trainee';
    if (user.role === 'admin') {
        document.getElementById('admin-tab-btn').classList.remove('hidden');
    }
    startClock();
    loadTodayRecord();
    loadSummary();
    generateQRCode();
    document.getElementById('id-card-name').textContent = user.name;
    document.getElementById('id-card-email').textContent = user.email;
    // Populate settings tab
    populateSettingsTab(user);
    // Show supervisor setup modal if not yet set
    if (!user.training_supervisor) {
        showSupervisorModal();
    }
}

// ── Settings tab ──────────────────────────────────────────
function populateSettingsTab(user) {
    if (document.getElementById('settings-name')) document.getElementById('settings-name').textContent = user.name;
    if (document.getElementById('settings-email')) document.getElementById('settings-email').textContent = user.email;
    if (document.getElementById('settings-hours')) document.getElementById('settings-hours').textContent = (user.required_hours || 486) + ' hours';
    if (document.getElementById('settings-supervisor')) document.getElementById('settings-supervisor').value = user.training_supervisor || '';
}
async function handleSaveSettings() {
    const supervisor = (document.getElementById('settings-supervisor')?.value || '').trim();
    const msgEl = document.getElementById('settings-msg');
    const btn = document.getElementById('save-settings-btn');
    if (!supervisor) {
        msgEl.textContent = 'Training Supervisor name is required.';
        msgEl.className = 'mb-4 text-sm p-3 rounded-lg bg-red-50 text-red-600 border border-red-100';
        msgEl.classList.remove('hidden');
        return;
    }
    btn.disabled = true; btn.textContent = 'Saving...';
    const data = await apiPost('../api/auth.php', { action: 'update_settings', training_supervisor: supervisor });
    btn.disabled = false; btn.textContent = 'Save Settings';
    if (data.ok) {
        if (currentUser) currentUser.training_supervisor = supervisor;
        // Keep supervisor modal in sync
        const modalInput = document.getElementById('supervisor-modal-input');
        if (modalInput) modalInput.value = supervisor;
        msgEl.textContent = '✓ Settings saved successfully!';
        msgEl.className = 'mb-4 text-sm p-3 rounded-lg bg-emerald-50 text-emerald-700 border border-emerald-200';
        msgEl.classList.remove('hidden');
        setTimeout(() => msgEl.classList.add('hidden'), 3000);
        showToast('Settings saved!');
    } else {
        msgEl.textContent = data.message || 'Failed to save settings.';
        msgEl.className = 'mb-4 text-sm p-3 rounded-lg bg-red-50 text-red-600 border border-red-100';
        msgEl.classList.remove('hidden');
    }
}

// ── Today record ──────────────────────────────────────────
async function loadTodayRecord() {
    const data = await apiGet('../api/attendance.php?action=get_today');
    const r = data.record;
    if (r) {
        document.getElementById('today-time-in').textContent = formatTime(r.time_in);
        document.getElementById('today-time-out').textContent = r.time_out ? formatTime(r.time_out) : '--:--';
        document.getElementById('today-total-hours').textContent = `${parseFloat(r.total_hours || 0).toFixed(2)} hrs`;
        const statusEl = document.getElementById('today-status');
        statusEl.textContent = r.status || 'Pending';
        statusEl.className = `px-3 py-1 rounded-full text-xs font-medium ${getStatusBadgeClass(r.status)}`;
        document.getElementById('time-in-btn').disabled = true;
        document.getElementById('time-in-btn').classList.add('opacity-50', 'cursor-not-allowed');
        const timedOut = !!r.time_out;
        document.getElementById('time-out-btn').disabled = timedOut;
        document.getElementById('time-out-btn').classList.toggle('opacity-50', timedOut);
        document.getElementById('time-out-btn').classList.toggle('cursor-not-allowed', timedOut);
    } else {
        document.getElementById('today-time-in').textContent = '--:--';
        document.getElementById('today-time-out').textContent = '--:--';
        document.getElementById('today-total-hours').textContent = '0.00 hrs';
        const statusEl = document.getElementById('today-status');
        statusEl.textContent = 'No Record';
        statusEl.className = 'px-3 py-1 rounded-full text-xs font-medium bg-slate-100 text-slate-600';
        document.getElementById('time-in-btn').disabled = false;
        document.getElementById('time-in-btn').classList.remove('opacity-50', 'cursor-not-allowed');
        document.getElementById('time-out-btn').disabled = true;
        document.getElementById('time-out-btn').classList.add('opacity-50', 'cursor-not-allowed');
    }
}

// ── Summary ───────────────────────────────────────────────
async function loadSummary() {
    const data = await apiGet('../api/attendance.php?action=get_summary');
    if (!data.ok) return;
    document.getElementById('total-hours-rendered').textContent = `${data.totalHours.toFixed(2)} hrs`;
    document.getElementById('total-overtime').textContent = `${data.totalOvertime.toFixed(2)} hrs`;
    document.getElementById('total-undertime').textContent = `${data.totalUndertime.toFixed(2)} hrs`;
    document.getElementById('total-late-count').textContent = data.lateCount;
    document.getElementById('total-days-worked').textContent = data.daysWorked;
    const targetHrs = (currentUser?.required_hours) ? parseInt(currentUser.required_hours) : OJT_TARGET_HOURS;
    document.getElementById('hours-progress').style.width = `${Math.min(100, (data.totalHours / targetHrs) * 100)}%`;
}

// ── Time In / Out ─────────────────────────────────────────
async function handleTimeIn() {
    const btn = document.getElementById('time-in-btn');
    btn.disabled = true;
    btn.innerHTML = '<span class="animate-pulse-gentle">Recording...</span>';
    const data = await apiPost('../api/attendance.php', { action: 'time_in' });
    btn.innerHTML = `<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/></svg> Time In`;
    if (data.ok) {
        showToast(`Time in recorded at ${formatTime(data.time_in)}${data.lateMinutes > 0 ? ` (${data.lateMinutes} min late)` : ''}`);
        loadTodayRecord(); loadSummary();
    } else {
        btn.disabled = false;
        showToast(data.message || 'Failed to record time in.', 'error');
    }
}
async function handleTimeOut() {
    const btn = document.getElementById('time-out-btn');
    btn.disabled = true;
    btn.innerHTML = '<span class="animate-pulse-gentle">Recording...</span>';
    const data = await apiPost('../api/attendance.php', { action: 'time_out' });
    btn.innerHTML = `<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg> Time Out`;
    if (data.ok) {
        showToast(`Time out recorded. Total: ${parseFloat(data.totalHours).toFixed(2)} hours`);
        loadTodayRecord(); loadSummary();
    } else {
        btn.disabled = false;
        showToast(data.message || 'Failed to record time out.', 'error');
    }
}

// ── History table ─────────────────────────────────────────
async function updateHistoryTable() {
    const from = document.getElementById('filter-date-from').value;
    const to = document.getElementById('filter-date-to').value;
    let url = '../api/attendance.php?action=get_my_records';
    if (from) url += `&from=${from}`;
    if (to) url += `&to=${to}`;
    const data = await apiGet(url);
    const tbody = document.getElementById('history-table-body');
    if (!data.ok || !data.records.length) {
        tbody.innerHTML = '<tr><td colspan="7" class="py-8 text-center text-slate-500">No attendance records found</td></tr>';
        return;
    }
    tbody.innerHTML = data.records.map(r => {
        const edited = r.last_edited_at
            ? '<span class="ml-1 px-1.5 py-0.5 bg-amber-100 text-amber-700 text-xs rounded">Edited</span>' : '';
        return `<tr class="hover:bg-slate-50 transition-colors">
          <td class="py-3 px-2"><span class="font-medium text-slate-800">${r.work_date}</span>${edited}</td>
          <td class="py-3 px-2 text-slate-600">${formatTime(r.time_in)}</td>
          <td class="py-3 px-2 text-slate-600">${r.time_out ? formatTime(r.time_out) : '--:--'}</td>
          <td class="py-3 px-2 font-medium text-slate-800">${parseFloat(r.total_hours || 0).toFixed(2)} hrs</td>
          <td class="py-3 px-2 ${parseInt(r.late_minutes) > 0 ? 'text-rose-600' : 'text-slate-600'}">${r.late_minutes || 0} min</td>
          <td class="py-3 px-2"><span class="px-2 py-1 rounded-full text-xs font-medium ${getStatusBadgeClass(r.status)}">${r.status}</span></td>
          <td class="py-3 px-2">
            <button onclick="openEditModal(${r.id},'${r.work_date}','${r.time_in}','${r.time_out || ''}')"
              class="text-primary-600 hover:text-primary-800 text-sm font-medium hover:underline">Edit</button>
          </td>
        </tr>`;
    }).join('');
}

// ── Edit modal ────────────────────────────────────────────
function openEditModal(id, workDate, timeIn, timeOut) {
    document.getElementById('edit-record-id').value = id;
    document.getElementById('edit-date').value = workDate;
    const inDT = new Date(timeIn);
    document.getElementById('edit-time-in').value =
        `${String(inDT.getHours()).padStart(2, '0')}:${String(inDT.getMinutes()).padStart(2, '0')}`;
    if (timeOut) {
        const outDT = new Date(timeOut);
        document.getElementById('edit-time-out').value =
            `${String(outDT.getHours()).padStart(2, '0')}:${String(outDT.getMinutes()).padStart(2, '0')}`;
    } else {
        document.getElementById('edit-time-out').value = '';
    }
    document.getElementById('edit-reason').value = '';
    document.getElementById('edit-error').classList.add('hidden');
    document.getElementById('edit-modal').classList.remove('hidden');
}
function closeEditModal() { document.getElementById('edit-modal').classList.add('hidden'); }

async function handleEditSubmit(e) {
    e.preventDefault();
    const recordId = document.getElementById('edit-record-id').value;
    const timeIn = document.getElementById('edit-time-in').value;
    const timeOut = document.getElementById('edit-time-out').value;
    const reason = document.getElementById('edit-reason').value.trim();
    const errorEl = document.getElementById('edit-error');
    if (!reason) { errorEl.textContent = 'Please provide a reason.'; errorEl.classList.remove('hidden'); return; }
    const btn = document.getElementById('save-edit-btn');
    btn.disabled = true; btn.textContent = 'Saving...';
    const data = await apiPost('../api/edit.php', { action: 'edit_record', record_id: recordId, time_in: timeIn, time_out: timeOut, reason });
    btn.disabled = false; btn.textContent = 'Save Changes';
    if (data.ok) {
        closeEditModal();
        showToast('Record updated successfully.');
        updateHistoryTable(); loadTodayRecord(); loadSummary();
    } else {
        errorEl.textContent = data.message || 'Failed to update record.';
        errorEl.classList.remove('hidden');
    }
}

// ── Add Log modal ─────────────────────────────────────────
function openAddLogModal() {
    ['add-log-date', 'add-log-am-in', 'add-log-am-out',
        'add-log-pm-in', 'add-log-pm-out',
        'add-log-ot-in', 'add-log-ot-out']
        .forEach(id => { const el = document.getElementById(id); if (el) el.value = ''; });
    document.getElementById('add-log-error').classList.add('hidden');
    document.getElementById('add-log-modal').classList.remove('hidden');
}
function closeAddLogModal() { document.getElementById('add-log-modal').classList.add('hidden'); }

async function handleAddLogSubmit(e) {
    e.preventDefault();
    const date = document.getElementById('add-log-date').value;
    const amIn = document.getElementById('add-log-am-in').value;
    const amOut = document.getElementById('add-log-am-out').value;
    const pmIn = document.getElementById('add-log-pm-in').value;
    const pmOut = document.getElementById('add-log-pm-out').value;
    const otIn = document.getElementById('add-log-ot-in').value;
    const otOut = document.getElementById('add-log-ot-out').value;
    const errorEl = document.getElementById('add-log-error');
    const btn = document.getElementById('save-add-log-btn');
    btn.disabled = true; btn.textContent = 'Saving...';
    const data = await apiPost('../api/attendance.php', {
        action: 'add_log',
        date, am_in: amIn, am_out: amOut,
        pm_in: pmIn, pm_out: pmOut,
        ot_in: otIn, ot_out: otOut
    });
    btn.disabled = false; btn.textContent = 'Save Log Entry';
    if (data.ok) {
        closeAddLogModal();
        showToast(data.message || 'Time log added.');
        updateHistoryTable(); loadSummary();
    } else {
        errorEl.textContent = data.message || 'Failed to add time log.';
        errorEl.classList.remove('hidden');
    }
}

// ── Admin tables ──────────────────────────────────────────
async function updateAdminTables() {
    const recData = await apiGet('../api/admin.php?action=get_all_records');
    const adminBody = document.getElementById('admin-table-body');
    if (!recData.ok || !recData.records.length) {
        adminBody.innerHTML = '<tr><td colspan="6" class="py-8 text-center text-slate-500">No records found</td></tr>';
    } else {
        adminBody.innerHTML = recData.records.map(r => `
          <tr class="hover:bg-slate-50 transition-colors">
            <td class="py-3 px-2 font-medium text-slate-800">${r.user_name}</td>
            <td class="py-3 px-2 text-slate-600">${r.work_date}</td>
            <td class="py-3 px-2 text-slate-600">${formatTime(r.time_in)}</td>
            <td class="py-3 px-2 text-slate-600">${r.time_out ? formatTime(r.time_out) : '--:--'}</td>
            <td class="py-3 px-2 font-medium text-slate-800">${parseFloat(r.total_hours || 0).toFixed(2)} hrs</td>
            <td class="py-3 px-2"><span class="px-2 py-1 rounded-full text-xs font-medium ${getStatusBadgeClass(r.status)}">${r.status}</span></td>
          </tr>`).join('');
    }
    const logData = await apiGet('../api/admin.php?action=get_audit_logs');
    const auditBody = document.getElementById('audit-table-body');
    if (!logData.ok || !logData.logs.length) {
        auditBody.innerHTML = '<tr><td colspan="7" class="py-8 text-center text-slate-500">No edit logs found</td></tr>';
    } else {
        auditBody.innerHTML = logData.logs.map(l => `
          <tr class="hover:bg-slate-50 transition-colors">
            <td class="py-3 px-2 font-medium text-slate-800">${l.user_name}</td>
            <td class="py-3 px-2 text-slate-600">${formatDate(l.edited_at)}</td>
            <td class="py-3 px-2 text-slate-600">${formatTime(l.old_time_in)}</td>
            <td class="py-3 px-2 text-slate-600">${formatTime(l.new_time_in)}</td>
            <td class="py-3 px-2 text-slate-600">${l.old_time_out ? formatTime(l.old_time_out) : '--'}</td>
            <td class="py-3 px-2 text-slate-600">${l.new_time_out ? formatTime(l.new_time_out) : '--'}</td>
            <td class="py-3 px-2 text-slate-600 max-w-xs truncate" title="${l.reason}">${l.reason}</td>
          </tr>`).join('');
    }
}

// ── QR Code (Digital ID) ──────────────────────────────────
function generateQRCode() {
    if (!currentUser || !currentUser.email) return;

    // We encode the user's email into the QR Code.
    // The admin scanner will read this email to identify the user.
    const tokenPayload = btoa(currentUser.email); // Simple base64 encoding for the QR

    const container = document.getElementById('qrcode-container');
    container.innerHTML = '';
    try {
        new QRCode(container, {
            text: tokenPayload,
            width: 200, height: 200, colorDark: '#0f172a', colorLight: '#ffffff',
            correctLevel: QRCode.CorrectLevel.H
        });
    } catch (e) { container.innerHTML = '<p class="text-slate-400 text-sm">QR library not loaded</p>'; }
}

// ── Tabs ──────────────────────────────────────────────────
function setupTabs() {
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('.tab-btn').forEach(b => {
                b.classList.remove('active', 'bg-primary-100', 'text-primary-700');
                b.classList.add('text-slate-600', 'hover:bg-slate-100');
            });
            btn.classList.add('active', 'bg-primary-100', 'text-primary-700');
            btn.classList.remove('text-slate-600', 'hover:bg-slate-100');
            document.querySelectorAll('.tab-content').forEach(c => c.classList.add('hidden'));
            const tabId = btn.dataset.tab;
            document.getElementById(`tab-${tabId}`).classList.remove('hidden');
            if (tabId === 'history') updateHistoryTable();
            if (tabId === 'admin' && currentUser?.role === 'admin') updateAdminTables();
            if (tabId === 'settings' && currentUser) populateSettingsTab(currentUser);
        });
    });
    document.querySelector('.tab-btn[data-tab="overview"]').click();
}

// ── Event Listeners ───────────────────────────────────────
function setupEventListeners() {
    document.getElementById('logout-btn')?.addEventListener('click', handleLogout);
    document.getElementById('time-in-btn')?.addEventListener('click', handleTimeIn);
    document.getElementById('time-out-btn')?.addEventListener('click', handleTimeOut);
    document.getElementById('edit-form')?.addEventListener('submit', handleEditSubmit);
    document.getElementById('cancel-edit-btn')?.addEventListener('click', closeEditModal);
    document.getElementById('edit-modal-backdrop')?.addEventListener('click', closeEditModal);
    document.getElementById('filter-btn')?.addEventListener('click', updateHistoryTable);
    document.getElementById('add-log-btn')?.addEventListener('click', openAddLogModal);
    document.getElementById('cancel-add-log-btn')?.addEventListener('click', closeAddLogModal);
    document.getElementById('add-log-modal-backdrop')?.addEventListener('click', closeAddLogModal);
    document.getElementById('add-log-form')?.addEventListener('submit', handleAddLogSubmit);
    document.getElementById('notice-accept-btn')?.addEventListener('click', hideNotice);
    // Supervisor modal
    document.getElementById('supervisor-modal-save-btn')?.addEventListener('click', handleSupervisorModalSave);
    document.getElementById('supervisor-modal-input')?.addEventListener('keydown', e => {
        if (e.key === 'Enter') handleSupervisorModalSave();
    });
    // Settings tab
    document.getElementById('save-settings-btn')?.addEventListener('click', handleSaveSettings);
}

// ── Init ──────────────────────────────────────────────────
async function init() {
    setupEventListeners();
    setupTabs();

    if (!sessionStorage.getItem('notice_accepted')) showNotice();

    const data = await apiGet('../api/auth.php?action=session');
    if (!data.loggedIn) {
        window.location.href = 'login.php';
        return;
    }
    bootDashboard(data.user);
}

init();
