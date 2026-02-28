// ============================================================
// assets/js/app.js — OJT DTR Monitoring System
// ============================================================

// ============================================================
// CONFIG
// ============================================================
const OJT_TARGET_HOURS = 486;
const WORK_START_HOUR = 8;
const LUNCH_START_HOUR = 12;
const LUNCH_END_HOUR = 13;
const REQUIRED_DAILY_HRS = 8;

let currentUser = null;
let clockInterval = null;
let currentQRToken = null;

// ============================================================
// UTILITY
// ============================================================
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
    toast.className = `${bg} text-white px-4 py-3 rounded-xl shadow-lg animate-slide-up flex items-center gap-2`;
    toast.innerHTML = `
    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      ${type === 'success'
            ? '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>'
            : '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>'}
    </svg>
    <span>${message}</span>`;
    container.appendChild(toast);
    setTimeout(() => toast.remove(), 4000);
}

// ============================================================
// CLOCK
// ============================================================
function updateClock() {
    const now = new Date();
    document.getElementById('live-clock').textContent =
        now.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false });
    document.getElementById('live-date').textContent =
        now.toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
}
function startClock() {
    updateClock();
    clockInterval = setInterval(updateClock, 1000);
}

// ============================================================
// API HELPERS
// ============================================================
async function apiGet(url) {
    const res = await fetch(url);
    return res.json();
}
async function apiPost(url, body) {
    const fd = new FormData();
    Object.entries(body).forEach(([k, v]) => fd.append(k, v ?? ''));
    const res = await fetch(url, { method: 'POST', body: fd });
    return res.json();
}

// ============================================================
// NOTICE MODAL
// ============================================================
function showNotice() {
    document.getElementById('notice-modal').classList.remove('hidden');
}
function hideNotice() {
    document.getElementById('notice-modal').classList.add('hidden');
    sessionStorage.setItem('notice_accepted', '1');
}

// ============================================================
// AUTH
// ============================================================
async function handleLogin(e) {
    e.preventDefault();
    const email = document.getElementById('login-email').value.trim();
    const password = document.getElementById('login-password').value;
    const errorEl = document.getElementById('login-error');

    const btn = document.getElementById('login-btn');
    btn.disabled = true;
    btn.textContent = 'Signing in...';

    const data = await apiPost('api/auth.php', { action: 'login', email, password });

    btn.disabled = false;
    btn.textContent = 'Sign In';

    if (data.ok) {
        errorEl.classList.add('hidden');
        currentUser = data.user;
        showDashboard();
        showToast(`Welcome back, ${currentUser.name}!`);
    } else {
        errorEl.textContent = data.message || 'Invalid email or password.';
        errorEl.classList.remove('hidden');
    }
}

async function handleRegister(e) {
    e.preventDefault();
    const name = document.getElementById('register-name').value.trim();
    const username = document.getElementById('register-username').value.trim();
    const email = document.getElementById('register-email').value.trim();
    const password = document.getElementById('register-password').value;
    const errorEl = document.getElementById('register-error');

    // Resolve required hours (preset or custom)
    const hoursSelect = document.getElementById('register-required-hours');
    let requiredHours = hoursSelect.value;
    if (requiredHours === 'custom') {
        requiredHours = document.getElementById('register-custom-hours').value;
        if (!requiredHours || parseInt(requiredHours) < 1) {
            errorEl.textContent = 'Please enter a valid custom hour value.';
            errorEl.classList.remove('hidden');
            return;
        }
    }

    const btn = document.getElementById('register-btn');
    btn.disabled = true;
    btn.textContent = 'Creating...';

    const data = await apiPost('api/auth.php', {
        action: 'register', name, username, email, password,
        required_hours: requiredHours
    });

    btn.disabled = false;
    btn.textContent = 'Create Account';

    if (data.ok) {
        errorEl.classList.add('hidden');
        showToast('Account created! Please log in.');
        document.getElementById('register-form').reset();
        document.getElementById('custom-hours-container').classList.add('hidden');
        toggleForms('login');
    } else {
        errorEl.textContent = data.message || 'Registration failed.';
        errorEl.classList.remove('hidden');
    }
}

async function handleLogout() {
    await apiPost('api/auth.php', { action: 'logout' });
    currentUser = null;
    if (clockInterval) clearInterval(clockInterval);
    document.getElementById('login-page').classList.remove('hidden');
    document.getElementById('dashboard-page').classList.add('hidden');
    document.getElementById('login-form').reset();
    showNotice();
}

function toggleForms(show) {
    const isRegister = show === 'register';
    document.getElementById('login-form-container').classList.toggle('hidden', isRegister);
    document.getElementById('register-form-container').classList.toggle('hidden', !isRegister);
    document.getElementById('login-error').classList.add('hidden');
    document.getElementById('register-error').classList.add('hidden');
}

// ============================================================
// DASHBOARD
// ============================================================
function showDashboard() {
    document.getElementById('login-page').classList.add('hidden');
    document.getElementById('dashboard-page').classList.remove('hidden');
    document.getElementById('user-name-display').textContent = currentUser.name;
    document.getElementById('user-role-display').textContent =
        currentUser.role === 'admin' ? 'Administrator' : 'OJT Trainee';

    if (currentUser.role === 'admin') {
        document.getElementById('admin-tab-btn').classList.remove('hidden');
    } else {
        document.getElementById('admin-tab-btn').classList.add('hidden');
    }

    startClock();
    loadTodayRecord();
    loadSummary();
    initQRRefresh();
}

async function loadTodayRecord() {
    const data = await apiGet('api/attendance.php?action=get_today');
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

async function loadSummary() {
    const data = await apiGet('api/attendance.php?action=get_summary');
    if (!data.ok) return;
    document.getElementById('total-hours-rendered').textContent = `${data.totalHours.toFixed(2)} hrs`;
    document.getElementById('total-overtime').textContent = `${data.totalOvertime.toFixed(2)} hrs`;
    document.getElementById('total-undertime').textContent = `${data.totalUndertime.toFixed(2)} hrs`;
    document.getElementById('total-late-count').textContent = data.lateCount;
    document.getElementById('total-days-worked').textContent = data.daysWorked;
    const targetHrs = (currentUser && currentUser.required_hours) ? parseInt(currentUser.required_hours) : OJT_TARGET_HOURS;
    const progress = Math.min(100, (data.totalHours / targetHrs) * 100);
    document.getElementById('hours-progress').style.width = `${progress}%`;
}

// ============================================================
// TIME IN / OUT
// ============================================================
async function handleTimeIn() {
    const btn = document.getElementById('time-in-btn');
    btn.disabled = true;
    btn.innerHTML = '<span class="animate-pulse-gentle">Recording...</span>';

    const data = await apiPost('api/attendance.php', { action: 'time_in' });

    btn.innerHTML = `<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
      d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
  </svg> Time In`;

    if (data.ok) {
        showToast(`Time in recorded at ${formatTime(data.time_in)}${data.lateMinutes > 0 ? ` (${data.lateMinutes} min late)` : ''}`);
        loadTodayRecord();
        loadSummary();
    } else {
        btn.disabled = false;
        showToast(data.message || 'Failed to record time in.', 'error');
    }
}

async function handleTimeOut() {
    const btn = document.getElementById('time-out-btn');
    btn.disabled = true;
    btn.innerHTML = '<span class="animate-pulse-gentle">Recording...</span>';

    const data = await apiPost('api/attendance.php', { action: 'time_out' });

    btn.innerHTML = `<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
      d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
  </svg> Time Out`;

    if (data.ok) {
        showToast(`Time out recorded. Total: ${parseFloat(data.totalHours).toFixed(2)} hours`);
        loadTodayRecord();
        loadSummary();
    } else {
        btn.disabled = false;
        showToast(data.message || 'Failed to record time out.', 'error');
    }
}

// ============================================================
// HISTORY TABLE
// ============================================================
async function updateHistoryTable() {
    const from = document.getElementById('filter-date-from').value;
    const to = document.getElementById('filter-date-to').value;
    let url = 'api/attendance.php?action=get_my_records';
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
        return `
      <tr class="hover:bg-slate-50 transition-colors">
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

// ============================================================
// EDIT MODAL
// ============================================================
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
function closeEditModal() {
    document.getElementById('edit-modal').classList.add('hidden');
}

async function handleEditSubmit(e) {
    e.preventDefault();
    const recordId = document.getElementById('edit-record-id').value;
    const timeIn = document.getElementById('edit-time-in').value;
    const timeOut = document.getElementById('edit-time-out').value;
    const reason = document.getElementById('edit-reason').value.trim();
    const errorEl = document.getElementById('edit-error');

    if (!reason) {
        errorEl.textContent = 'Please provide a reason for this edit.';
        errorEl.classList.remove('hidden');
        return;
    }

    const btn = document.getElementById('save-edit-btn');
    btn.disabled = true;
    btn.textContent = 'Saving...';

    const data = await apiPost('api/edit.php', {
        action: 'edit_record', record_id: recordId, time_in: timeIn, time_out: timeOut, reason
    });

    btn.disabled = false;
    btn.textContent = 'Save Changes';

    if (data.ok) {
        closeEditModal();
        showToast('Record updated successfully.');
        updateHistoryTable();
        loadTodayRecord();
        loadSummary();
    } else {
        errorEl.textContent = data.message || 'Failed to update record.';
        errorEl.classList.remove('hidden');
    }
}

// ============================================================
// ADD TIME LOG MODAL
// ============================================================
function openAddLogModal() {
    ['add-log-date', 'add-log-time-in', 'add-log-time-out'].forEach(id => document.getElementById(id).value = '');
    document.getElementById('add-log-error').classList.add('hidden');
    document.getElementById('add-log-modal').classList.remove('hidden');
}
function closeAddLogModal() {
    document.getElementById('add-log-modal').classList.add('hidden');
}

async function handleAddLogSubmit(e) {
    e.preventDefault();
    const date = document.getElementById('add-log-date').value;
    const timeIn = document.getElementById('add-log-time-in').value;
    const timeOut = document.getElementById('add-log-time-out').value;
    const errorEl = document.getElementById('add-log-error');

    const btn = document.getElementById('save-add-log-btn');
    btn.disabled = true;
    btn.textContent = 'Adding...';

    const data = await apiPost('api/attendance.php', {
        action: 'add_log', date, time_in: timeIn, time_out: timeOut
    });

    btn.disabled = false;
    btn.textContent = 'Add Time Log';

    if (data.ok) {
        closeAddLogModal();
        showToast(data.message || 'Time log added successfully.');
        updateHistoryTable();
        loadSummary();
    } else {
        errorEl.textContent = data.message || 'Failed to add time log.';
        errorEl.classList.remove('hidden');
    }
}

// ============================================================
// ADMIN TABLES
// ============================================================
async function updateAdminTables() {
    const recData = await apiGet('api/admin.php?action=get_all_records');
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

    const logData = await apiGet('api/admin.php?action=get_audit_logs');
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

// ============================================================
// QR CODE
// ============================================================
function simpleHash(str) {
    let hash = 0;
    for (let i = 0; i < str.length; i++) {
        hash = ((hash << 5) - hash) + str.charCodeAt(i);
        hash = hash & hash;
    }
    return hash.toString(16);
}
function generateQRToken() {
    const minutes = Math.floor(Date.now() / (5 * 60 * 1000));
    return simpleHash(`${minutes}-ojt-dtr-qr`);
}
function generateQRCode() {
    currentQRToken = generateQRToken();
    const container = document.getElementById('qrcode-container');
    container.innerHTML = '';
    new QRCode(container, {
        text: `${window.location.origin}${window.location.pathname}?dtr_token=${currentQRToken}&ts=${Date.now()}`,
        width: 280, height: 280,
        colorDark: '#0f172a', colorLight: '#ffffff',
        correctLevel: QRCode.CorrectLevel.H
    });
}
function initQRRefresh() {
    generateQRCode();
    setInterval(() => {
        generateQRCode();
        document.getElementById('scanner-token').value = '';
        document.getElementById('scanner-error').classList.add('hidden');
        document.getElementById('scanner-success').classList.add('hidden');
    }, 5 * 60 * 1000);
}

async function handleScannerSubmit() {
    const email = document.getElementById('scanner-email').value.trim();
    const token = document.getElementById('scanner-token').value.trim();
    const errorEl = document.getElementById('scanner-error');
    const successEl = document.getElementById('scanner-success');

    errorEl.classList.add('hidden');
    successEl.classList.add('hidden');

    if (!email || !token) {
        errorEl.textContent = 'Please enter both email and token.';
        errorEl.classList.remove('hidden');
        return;
    }
    if (token !== currentQRToken && token !== generateQRToken()) {
        errorEl.textContent = 'Invalid or expired QR token. Please scan a valid QR code.';
        errorEl.classList.remove('hidden');
        return;
    }

    const btn = document.getElementById('submit-scanner-btn');
    btn.disabled = true;
    btn.textContent = 'Processing...';

    const data = await apiPost('api/attendance.php', { action: 'scanner_submit', email });

    btn.disabled = false;
    btn.textContent = 'Process Attendance';

    if (data.ok) {
        document.getElementById('scanner-success-text').textContent = data.message;
        successEl.classList.remove('hidden');
        document.getElementById('scanner-email').value = '';
        document.getElementById('scanner-token').value = '';
    } else {
        errorEl.textContent = data.message || 'Failed to process attendance.';
        errorEl.classList.remove('hidden');
    }
}

// ============================================================
// TABS
// ============================================================
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
        });
    });
    document.querySelector('.tab-btn[data-tab="overview"]').click();
}

// ============================================================
// EVENT LISTENERS
// ============================================================
function setupEventListeners() {
    document.getElementById('login-form').addEventListener('submit', handleLogin);
    document.getElementById('register-form').addEventListener('submit', handleRegister);
    document.getElementById('show-register').addEventListener('click', () => toggleForms('register'));
    document.getElementById('show-login').addEventListener('click', () => toggleForms('login'));
    document.getElementById('logout-btn').addEventListener('click', handleLogout);
    document.getElementById('time-in-btn').addEventListener('click', handleTimeIn);
    document.getElementById('time-out-btn').addEventListener('click', handleTimeOut);
    document.getElementById('edit-form').addEventListener('submit', handleEditSubmit);
    document.getElementById('cancel-edit-btn').addEventListener('click', closeEditModal);
    document.getElementById('edit-modal-backdrop').addEventListener('click', closeEditModal);
    document.getElementById('filter-btn').addEventListener('click', updateHistoryTable);
    document.getElementById('submit-scanner-btn').addEventListener('click', handleScannerSubmit);
    document.getElementById('scanner-token').addEventListener('keypress', e => {
        if (e.key === 'Enter') handleScannerSubmit();
    });
    document.getElementById('add-log-btn').addEventListener('click', openAddLogModal);
    document.getElementById('cancel-add-log-btn').addEventListener('click', closeAddLogModal);
    document.getElementById('add-log-modal-backdrop').addEventListener('click', closeAddLogModal);
    document.getElementById('add-log-form').addEventListener('submit', handleAddLogSubmit);
    document.getElementById('notice-accept-btn').addEventListener('click', hideNotice);

    // Password eye toggle
    const togglePwBtn = document.getElementById('toggle-password');
    if (togglePwBtn) {
        togglePwBtn.addEventListener('click', () => {
            const pwInput = document.getElementById('register-password');
            const isHidden = pwInput.type === 'password';
            pwInput.type = isHidden ? 'text' : 'password';
            document.getElementById('eye-show').classList.toggle('hidden', isHidden);
            document.getElementById('eye-hide').classList.toggle('hidden', !isHidden);
        });
    }
}

// ============================================================
// INIT — check PHP session then boot app
// ============================================================
async function init() {
    setupEventListeners();
    setupTabs();

    if (!sessionStorage.getItem('notice_accepted')) {
        showNotice();
    }

    const data = await apiGet('api/auth.php?action=session');
    if (data.loggedIn) {
        currentUser = data.user;
        showDashboard();
    }
}

init();
