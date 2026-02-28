// ============================================================
// assets/js/admin.js — Admin Dashboard JS
// ============================================================

// ── Helpers ──────────────────────────────────────────────────
function fmt(dt) {
    if (!dt) return '--';
    return new Date(dt).toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', hour12: true });
}
function badge(status) {
    const map = {
        'On Time': 'bg-emerald-100 text-emerald-700',
        'Late': 'bg-rose-100 text-rose-700',
        'Overtime': 'bg-blue-100 text-blue-700',
        'Undertime': 'bg-amber-100 text-amber-700',
        'Pending': 'bg-slate-100 text-slate-600',
    };
    return `<span class="px-2 py-1 rounded-full text-xs font-medium ${map[status] || 'bg-slate-100 text-slate-500'}">${status || '—'}</span>`;
}
function toast(msg, type = 'success') {
    const el = document.createElement('div');
    const bg = type === 'success' ? 'bg-emerald-500' : type === 'error' ? 'bg-rose-500' : 'bg-amber-500';
    el.className = `${bg} text-white px-4 py-3 rounded-xl shadow-lg animate-slide-up text-sm`;
    el.textContent = msg;
    document.getElementById('toast-container').appendChild(el);
    setTimeout(() => el.remove(), 4000);
}
async function apiGet(url) { const r = await fetch(url); return r.json(); }
async function apiPost(url, data) {
    const fd = new FormData(); Object.entries(data).forEach(([k, v]) => fd.append(k, v ?? ''));
    const r = await fetch(url, { method: 'POST', body: fd }); return r.json();
}

// ── Tabs ──────────────────────────────────────────────────────
function setupTabs() {
    document.querySelectorAll('.adm-tab').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('.adm-tab').forEach(b => {
                b.classList.remove('bg-rose-500', 'text-white', 'shadow-md', 'shadow-rose-500/30', 'active');
                b.classList.add('text-slate-400', 'hover:bg-slate-800', 'hover:text-slate-200');
            });
            btn.classList.add('active', 'bg-rose-500', 'text-white', 'shadow-md', 'shadow-rose-500/30');
            btn.classList.remove('text-slate-400', 'hover:bg-slate-800', 'hover:text-slate-200');

            document.querySelectorAll('.adm-content').forEach(c => c.classList.add('hidden'));
            const id = `tab-${btn.dataset.tab}`;
            document.getElementById(id).classList.remove('hidden');

            if (window.innerWidth < 1024) closeSidebar(); // Auto-close on mobile

            if (btn.dataset.tab === 'trainees') loadUsers();
            if (btn.dataset.tab === 'records') loadRecords();
            if (btn.dataset.tab === 'scanner') setTimeout(() => document.getElementById('admin-scanner-input').focus(), 100);
            if (btn.dataset.tab === 'reports') loadReportTrainees();
            if (btn.dataset.tab === 'logs') loadLogs();
        });
    });
    // Activate first tab style
    const first = document.querySelector('.adm-tab.active') || document.querySelector('.adm-tab');
    if (first) {
        first.classList.add('active', 'bg-rose-500', 'text-white', 'shadow-md', 'shadow-rose-500/30');
        first.classList.remove('text-slate-400', 'hover:bg-slate-800', 'hover:text-slate-200');
    }
}

// ── Mobile Sidebar Toggle ──────────────────────────────────────
function openSidebar() {
    document.getElementById('sidebar').classList.remove('-translate-x-full');
    document.getElementById('sidebar-backdrop').classList.remove('hidden');
}
function closeSidebar() {
    document.getElementById('sidebar').classList.add('-translate-x-full');
    document.getElementById('sidebar-backdrop').classList.add('hidden');
}

// ── Logout ────────────────────────────────────────────────────
document.getElementById('logout-btn').addEventListener('click', async () => {
    await apiPost('../api/auth.php', { action: 'logout' });
    window.location.href = 'login.php';
});

// ── Overview: Stats + Today Snapshot ─────────────────────────
async function loadStats() {
    const d = await apiGet('../api/admin.php?action=get_stats');
    if (!d.ok) return;
    const s = d.stats;
    document.getElementById('stat-trainees').textContent = s.total_trainees;
    document.getElementById('stat-admins').textContent = s.total_admins;
    document.getElementById('stat-records').textContent = s.total_records;
    document.getElementById('stat-today').textContent = s.today_records;
    document.getElementById('stat-hours').textContent = s.total_hours.toFixed(1);
    document.getElementById('stat-late').textContent = s.late_count;
}

async function loadTodaySnapshot() {
    const today = new Date().toISOString().slice(0, 10);
    const d = await apiGet(`../api/admin.php?action=get_all_records&from=${today}&to=${today}`);
    const tbody = document.getElementById('today-tbody');
    if (!d.ok || !d.records.length) {
        tbody.innerHTML = '<tr><td colspan="5" class="py-8 text-center text-slate-400">No attendance recorded today</td></tr>';
        return;
    }
    tbody.innerHTML = d.records.map(r => `
    <tr class="hover:bg-slate-50 transition-colors border-t border-slate-50">
     <td class="py-3 px-4 font-medium text-slate-800">${r.user_name}</td>
     <td class="py-3 px-4 text-slate-600">${fmt(r.time_in)}</td>
     <td class="py-3 px-4 text-slate-600">${r.time_out ? fmt(r.time_out) : '<span class="text-amber-500">Active</span>'}</td>
     <td class="py-3 px-4 font-medium text-slate-800">${parseFloat(r.total_hours || 0).toFixed(2)} hrs</td>
     <td class="py-3 px-4">${badge(r.status)}</td>
    </tr>`).join('');
}

// ── Trainees ──────────────────────────────────────────────────
async function loadUsers() {
    const d = await apiGet('../api/admin.php?action=get_all_users');
    const tbody = document.getElementById('users-tbody');
    if (!d.ok || !d.users.length) {
        tbody.innerHTML = '<tr><td colspan="7" class="py-8 text-center text-slate-400">No users found</td></tr>';
        return;
    }
    tbody.innerHTML = d.users.map(u => {
        const hrs = parseFloat(u.total_hours || 0);
        const target = parseInt(u.required_hours || 486);
        const pct = Math.min(100, (hrs / target) * 100).toFixed(0);
        const roleBadge = u.role === 'admin'
            ? '<span class="px-2 py-1 rounded-full text-xs font-semibold bg-rose-100 text-rose-700">Admin</span>'
            : '<span class="px-2 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-700">OJT</span>';

        return `
    <tr class="hover:bg-slate-50 transition-colors border-t border-slate-50">
     <td class="py-3 px-4">
      <p class="font-medium text-slate-800">${u.name}</p>
      <p class="text-xs text-slate-400">${u.email}</p>
     </td>
     <td class="py-3 px-4 text-slate-600 text-xs">@${u.username || '—'}</td>
     <td class="py-3 px-4 text-slate-500 text-xs">${u.email}</td>
     <td class="py-3 px-4">${roleBadge}</td>
     <td class="py-3 px-4 min-w-[140px]">
      <div class="flex items-center gap-2">
       <div class="flex-1 h-1.5 bg-slate-200 rounded-full overflow-hidden">
        <div class="h-1.5 bg-primary-500 rounded-full" style="width:${pct}%"></div>
       </div>
       <span class="text-xs text-slate-500 whitespace-nowrap">${hrs.toFixed(1)}/${target}h</span>
      </div>
     </td>
     <td class="py-3 px-4 text-slate-600">${u.days_worked}</td>
     <td class="py-3 px-4">
      <button onclick="confirmDelete(${u.id},'${u.name.replace(/'/g, "\\'")} — ${u.email}')"
       class="text-xs text-red-500 hover:text-red-700 font-medium hover:underline">Delete</button>
     </td>
    </tr>`;
    }).join('');
}

// ── Attendance Records ────────────────────────────────────────
async function loadRecords() {
    const from = document.getElementById('rec-from').value;
    const to = document.getElementById('rec-to').value;
    let url = '../api/admin.php?action=get_all_records';
    if (from) url += `&from=${from}`;
    if (to) url += `&to=${to}`;

    const d = await apiGet(url);
    const tbody = document.getElementById('records-tbody');
    if (!d.ok || !d.records.length) {
        tbody.innerHTML = '<tr><td colspan="8" class="py-8 text-center text-slate-400">No records found</td></tr>';
        return;
    }
    tbody.innerHTML = d.records.map(r => `
    <tr class="hover:bg-slate-50 transition-colors border-t border-slate-50">
     <td class="py-3 px-4 font-medium text-slate-800">${r.user_name}</td>
     <td class="py-3 px-4 text-slate-600">${r.work_date}</td>
     <td class="py-3 px-4 text-slate-600">${fmt(r.time_in)}</td>
     <td class="py-3 px-4 text-slate-600">${r.time_out ? fmt(r.time_out) : '—'}</td>
     <td class="py-3 px-4 font-medium text-slate-800">${parseFloat(r.total_hours || 0).toFixed(2)} hrs</td>
     <td class="py-3 px-4 ${parseInt(r.late_minutes) > 0 ? 'text-rose-600' : 'text-slate-500'}">${r.late_minutes || 0} min</td>
     <td class="py-3 px-4">${badge(r.status)}</td>
     <td class="py-3 px-4">
      <button onclick="openEditModal(${r.id},'${r.work_date}','${r.time_in}','${r.time_out || ''}')"
       class="text-xs text-primary-600 hover:text-primary-800 font-medium hover:underline">Edit</button>
     </td>
    </tr>`).join('');
}

// ── Audit Logs ────────────────────────────────────────────────
async function loadLogs() {
    const d = await apiGet('../api/admin.php?action=get_audit_logs');
    const tbody = document.getElementById('logs-tbody');
    if (!d.ok || !d.logs.length) {
        tbody.innerHTML = '<tr><td colspan="7" class="py-8 text-center text-slate-400">No audit logs</td></tr>';
        return;
    }
    tbody.innerHTML = d.logs.map(l => `
    <tr class="hover:bg-slate-50 transition-colors border-t border-slate-50">
     <td class="py-3 px-4 font-medium text-slate-800">${l.user_name}</td>
     <td class="py-3 px-4 text-slate-500 text-xs">${new Date(l.edited_at).toLocaleString()}</td>
     <td class="py-3 px-4 text-slate-600">${fmt(l.old_time_in)}</td>
     <td class="py-3 px-4 text-slate-600">${fmt(l.new_time_in)}</td>
     <td class="py-3 px-4 text-slate-600">${l.old_time_out ? fmt(l.old_time_out) : '—'}</td>
     <td class="py-3 px-4 text-slate-600">${l.new_time_out ? fmt(l.new_time_out) : '—'}</td>
     <td class="py-3 px-4 text-slate-500 text-xs max-w-xs truncate" title="${l.reason}">${l.reason}</td>
    </tr>`).join('');
}

// ── Edit Record Modal ─────────────────────────────────────────
function openEditModal(id, workDate, timeIn, timeOut) {
    document.getElementById('edit-rec-id').value = id;
    document.getElementById('edit-rec-date').value = workDate;
    const inDT = new Date(timeIn);
    document.getElementById('edit-rec-in').value =
        `${String(inDT.getHours()).padStart(2, '0')}:${String(inDT.getMinutes()).padStart(2, '0')}`;
    if (timeOut) {
        const outDT = new Date(timeOut);
        document.getElementById('edit-rec-out').value =
            `${String(outDT.getHours()).padStart(2, '0')}:${String(outDT.getMinutes()).padStart(2, '0')}`;
    } else {
        document.getElementById('edit-rec-out').value = '';
    }
    document.getElementById('edit-rec-reason').value = '';
    document.getElementById('edit-rec-error').classList.add('hidden');
    document.getElementById('edit-modal').classList.remove('hidden');
}
function closeEditModal() {
    document.getElementById('edit-modal').classList.add('hidden');
}
document.getElementById('edit-modal-backdrop').addEventListener('click', closeEditModal);

document.getElementById('edit-record-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    const id = document.getElementById('edit-rec-id').value;
    const timeIn = document.getElementById('edit-rec-in').value;
    const timeOut = document.getElementById('edit-rec-out').value;
    const reason = document.getElementById('edit-rec-reason').value.trim();
    const errEl = document.getElementById('edit-rec-error');
    const btn = document.getElementById('edit-rec-save');

    if (!reason) { errEl.textContent = 'Reason is required.'; errEl.classList.remove('hidden'); return; }

    btn.disabled = true; btn.textContent = 'Saving…';
    const d = await apiPost('../api/admin.php', {
        action: 'admin_edit_record', record_id: id, time_in: timeIn, time_out: timeOut, reason
    });
    btn.disabled = false; btn.textContent = 'Save';

    if (d.ok) {
        closeEditModal();
        toast('Record updated.');
        loadRecords();
        loadTodaySnapshot();
    } else {
        errEl.textContent = d.message || 'Error saving.';
        errEl.classList.remove('hidden');
    }
});

// ── Delete User ───────────────────────────────────────────────
let pendingDeleteId = null;
function confirmDelete(userId, label) {
    pendingDeleteId = userId;
    document.getElementById('del-modal-name').textContent = label;
    document.getElementById('del-modal').classList.remove('hidden');
}
function closeDelModal() {
    document.getElementById('del-modal').classList.add('hidden');
    pendingDeleteId = null;
}
document.getElementById('del-confirm-btn').addEventListener('click', async () => {
    if (!pendingDeleteId) return;
    const btn = document.getElementById('del-confirm-btn');
    btn.disabled = true; btn.textContent = 'Deleting…';
    const d = await apiPost('../api/admin.php', { action: 'delete_user', user_id: pendingDeleteId });
    btn.disabled = false; btn.textContent = 'Delete';
    closeDelModal();
    if (d.ok) { toast('User deleted.'); loadUsers(); loadStats(); }
    else toast(d.message || 'Error.', 'error');
});

// ── Init ──────────────────────────────────────────────────────
setupTabs();
loadStats();
loadTodaySnapshot();

// ── Reports ───────────────────────────────────────────────────
const DAYS = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
let reportTraineesLoaded = false;
let lastReportUid = null;

function fmtTime(t) {
    if (!t) return '—';
    const parts = t.split(':');
    const h = parseInt(parts[0]); const m = parts[1];
    return `${h % 12 || 12}:${m} ${h >= 12 ? 'PM' : 'AM'}`;
}

async function loadReportTrainees() {
    if (reportTraineesLoaded) return;
    const data = await apiGet('../api/admin.php?action=get_trainees_list');
    if (!data.ok) return;
    const sel = document.getElementById('report-trainee');
    data.trainees.forEach(t => {
        const o = document.createElement('option');
        o.value = t.id; o.textContent = t.name;
        sel.appendChild(o);
    });
    reportTraineesLoaded = true;
}

async function generateReport() {
    const uid = document.getElementById('report-trainee').value;
    const from = document.getElementById('report-from').value;
    const to = document.getElementById('report-to').value;
    if (!uid) { toast('Please select a trainee.', 'error'); return; }

    lastReportUid = uid;
    const btn = document.getElementById('report-generate-btn');
    btn.disabled = true; btn.textContent = 'Loading...';

    let url = `../api/admin.php?action=get_report&user_id=${uid}`;
    if (from) url += `&from=${from}`;
    if (to) url += `&to=${to}`;

    const data = await apiGet(url);
    btn.disabled = false; btn.textContent = 'Generate Preview';
    if (!data.ok) { toast(data.message || 'Error loading report.', 'error'); return; }

    const s = data.summary;
    // Summary cards
    document.getElementById('rs-days').textContent = s.daysWorked;
    document.getElementById('rs-hours').textContent = s.totalHours;
    document.getElementById('rs-progress').textContent = s.progressPct + '%';
    document.getElementById('rs-remaining').textContent = s.remaining;
    document.getElementById('rs-late').textContent = s.lateCount;
    document.getElementById('rs-ot').textContent = s.totalOT;
    document.getElementById('report-summary-row').classList.remove('hidden');

    // Preview title
    document.getElementById('report-preview-title').textContent = data.trainee.name;
    document.getElementById('report-preview-sub').textContent =
        `${s.totalHours} hrs rendered / ${s.requiredHours} required (${s.progressPct}%)`;

    // Table
    const body = document.getElementById('report-table-body');
    if (!data.records.length) {
        document.getElementById('report-preview').classList.add('hidden');
        document.getElementById('report-empty').classList.remove('hidden');
        document.getElementById('report-print-btn').disabled = true;
        return;
    }
    const statusBadge = s => {
        const map = {
            'on time': 'bg-emerald-100 text-emerald-700',
            'late': 'bg-amber-100 text-amber-700',
            'undertime': 'bg-rose-100 text-rose-700',
            'overtime': 'bg-violet-100 text-violet-700'
        };
        return map[(s || '').toLowerCase()] || 'bg-slate-100 text-slate-600';
    };
    body.innerHTML = data.records.map(r => {
        const dt = new Date(r.work_date + 'T00:00:00');
        const day = DAYS[dt.getDay()];
        const amIn = r.am_in ? fmtTime(r.am_in) : fmtTime((r.time_in || '').substring(11, 16));
        const amOut = r.am_out ? fmtTime(r.am_out) : '—';
        const pmIn = r.pm_in ? fmtTime(r.pm_in) : '—';
        const pmOut = r.pm_out ? fmtTime(r.pm_out) : fmtTime((r.time_out || '').substring(11, 16));
        const status = r.status || 'Pending';
        return `<tr class="border-b border-slate-50 hover:bg-slate-50">
          <td class="py-2.5 px-4 font-medium text-slate-700">${r.work_date}</td>
          <td class="py-2.5 px-3 text-slate-500">${day}</td>
          <td class="py-2.5 px-3 text-center">${amIn}</td>
          <td class="py-2.5 px-3 text-center">${amOut}</td>
          <td class="py-2.5 px-3 text-center">${pmIn}</td>
          <td class="py-2.5 px-3 text-center">${pmOut}</td>
          <td class="py-2.5 px-3 text-center font-semibold">${parseFloat(r.total_hours).toFixed(2)}</td>
          <td class="py-2.5 px-3 text-center">${parseInt(r.late_minutes) > 0 ? r.late_minutes : '—'}</td>
          <td class="py-2.5 px-3 text-center"><span class="px-2 py-0.5 rounded-full text-xs font-semibold ${statusBadge(status)}">${status}</span></td>
        </tr>`;
    }).join('');

    document.getElementById('report-empty').classList.add('hidden');
    document.getElementById('report-preview').classList.remove('hidden');
    document.getElementById('report-print-btn').disabled = false;
}

document.getElementById('report-generate-btn')?.addEventListener('click', generateReport);
document.getElementById('report-print-btn')?.addEventListener('click', () => {
    const uid = document.getElementById('report-trainee').value;
    const from = document.getElementById('report-from').value;
    const to = document.getElementById('report-to').value;
    if (!uid) { toast('Generate a report first.', 'error'); return; }
    let url = `report-print.php?user_id=${uid}`;
    if (from) url += `&from=${from}`;
    if (to) url += `&to=${to}`;
    window.open(url, '_blank');
});

// ── Scanner: Camera + jsQR decode loop ───────────────────────
let cameraStream = null;
let scannerRafId = null;
let lastScannedEmail = null;
let lastScanTime = 0;

async function processScannedEmail(email) {
    const errEl = document.getElementById('admin-scanner-error');
    const errTxt = document.getElementById('admin-scanner-error-text');
    const succEl = document.getElementById('admin-scanner-success');
    errEl.classList.add('hidden');
    succEl.classList.add('hidden');

    const data = await apiPost('../api/attendance.php', { action: 'scanner_submit', email });

    if (data.ok) {
        document.getElementById('admin-scanner-success-text').textContent = data.message;
        succEl.classList.remove('hidden');
        setTimeout(() => succEl.classList.add('hidden'), 5000);
        loadTodaySnapshot(); loadStats();
        if (!document.getElementById('tab-records').classList.contains('hidden')) loadRecords();
    } else {
        errTxt.textContent = data.message || 'Failed to process attendance.';
        errEl.classList.remove('hidden');
        setTimeout(() => errEl.classList.add('hidden'), 5000);
    }
}

function decodeQRPayload(raw) {
    try {
        if (!raw.includes('@')) return atob(raw);
    } catch (e) { }
    return raw;
}

function scanFrame() {
    const video = document.getElementById('qr-video');
    const canvas = document.getElementById('qr-canvas');
    if (!video || video.readyState < 2) { scannerRafId = requestAnimationFrame(scanFrame); return; }

    canvas.width = video.videoWidth;
    canvas.height = video.videoHeight;
    const ctx = canvas.getContext('2d');
    ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

    const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
    const code = jsQR(imageData.data, imageData.width, imageData.height, { inversionAttempts: 'dontInvert' });

    if (code && code.data) {
        const now = Date.now();
        const email = decodeQRPayload(code.data.trim());
        // Debounce: don't re-submit the same email within 5 seconds
        if (email !== lastScannedEmail || now - lastScanTime > 5000) {
            lastScannedEmail = email;
            lastScanTime = now;
            processScannedEmail(email);
        }
    }

    scannerRafId = requestAnimationFrame(scanFrame);
}

async function startCamera() {
    const placeholder = document.getElementById('camera-placeholder');
    const startBtn = document.getElementById('start-camera-btn');
    const stopBtn = document.getElementById('stop-camera-btn');
    try {
        cameraStream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } });
        const video = document.getElementById('qr-video');
        video.srcObject = cameraStream;
        await video.play();
        placeholder.classList.add('hidden');
        startBtn.disabled = true;
        stopBtn.disabled = false;
        scannerRafId = requestAnimationFrame(scanFrame);
    } catch (err) {
        const errTxt = document.getElementById('admin-scanner-error-text');
        document.getElementById('admin-scanner-error').classList.remove('hidden');
        errTxt.textContent = `Camera error: ${err.message}. Try the manual input below.`;
    }
}

function stopCamera() {
    if (scannerRafId) { cancelAnimationFrame(scannerRafId); scannerRafId = null; }
    if (cameraStream) { cameraStream.getTracks().forEach(t => t.stop()); cameraStream = null; }
    const video = document.getElementById('qr-video');
    if (video) video.srcObject = null;
    document.getElementById('camera-placeholder')?.classList.remove('hidden');
    document.getElementById('start-camera-btn').disabled = false;
    document.getElementById('stop-camera-btn').disabled = true;
    lastScannedEmail = null;
}

// Manual fallback submit
async function handleAdminScanner() {
    const inputEl = document.getElementById('admin-scanner-input');
    const scannedVal = inputEl.value.trim();
    const errEl = document.getElementById('admin-scanner-error');
    const errTxt = document.getElementById('admin-scanner-error-text');
    const succEl = document.getElementById('admin-scanner-success');
    const btn = document.getElementById('admin-scanner-btn');

    errEl.classList.add('hidden'); succEl.classList.add('hidden');
    if (!scannedVal) { errTxt.textContent = 'Please enter a student email or QR token.'; errEl.classList.remove('hidden'); return; }

    const email = decodeQRPayload(scannedVal);
    btn.disabled = true; btn.textContent = 'Processing...';
    await processScannedEmail(email);
    btn.disabled = false; btn.textContent = 'Submit';
    inputEl.value = '';
}

document.getElementById('start-camera-btn')?.addEventListener('click', startCamera);
document.getElementById('stop-camera-btn')?.addEventListener('click', stopCamera);
document.getElementById('admin-scanner-btn')?.addEventListener('click', handleAdminScanner);
document.getElementById('admin-scanner-input')?.addEventListener('keypress', e => { if (e.key === 'Enter') handleAdminScanner(); });

// Stop camera when leaving Scanner tab
document.querySelectorAll('.adm-tab').forEach(btn => {
    btn.addEventListener('click', () => { if (btn.dataset.tab !== 'scanner') stopCamera(); });
});

