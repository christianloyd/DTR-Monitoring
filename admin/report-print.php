<?php
// admin/report-print.php — Print-optimized DTR Report (Multi-month layout)
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

startSession();
requireAdmin();

$uid  = (int)($_GET['user_id'] ?? 0);
$from = $_GET['from'] ?? '';
$to   = $_GET['to']   ?? '';

if (!$uid) { die('<p style="color:red;font-family:sans-serif;padding:2rem;">Error: No trainee selected.</p>'); }

$pdo = getDB();

// ── Trainee info ────────────────────────────────────────────────
$uStmt = $pdo->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
$uStmt->execute([$uid]);
$trainee = $uStmt->fetch();
if (!$trainee) { die('<p style="color:red;font-family:sans-serif;padding:2rem;">Error: Trainee not found.</p>'); }

// ── Attendance records ───────────────────────────────────────────
$sql    = 'SELECT * FROM attendance WHERE user_id = ?';
$params = [$uid];
if ($from) { $sql .= ' AND work_date >= ?'; $params[] = $from; }
if ($to)   { $sql .= ' AND work_date <= ?'; $params[] = $to;   }
$sql .= ' ORDER BY work_date ASC';
$aStmt = $pdo->prepare($sql);
$aStmt->execute($params);
$records = $aStmt->fetchAll();

// Lookup map: work_date => record row
$recordMap = [];
foreach ($records as $r) { $recordMap[$r['work_date']] = $r; }

// ── Summary totals ───────────────────────────────────────────────
$totalHours = 0; $totalOT = 0; $totalUndertime = 0; $lateCount = 0;
$required   = (int)($trainee['required_hours'] ?? 486);
foreach ($records as $r) {
    $h = (float)$r['total_hours'];
    $totalHours += $h;
    if ($h > 8) $totalOT        += $h - 8;
    if ($h < 8) $totalUndertime += 8 - $h;
    if ((int)$r['late_minutes'] > 0) $lateCount++;
}
$remaining   = max(0, $required - $totalHours);
$progressPct = min(100, round(($totalHours / $required) * 100, 1));

// ── Date bounds ──────────────────────────────────────────────────
if ($from && $to) {
    $startDate = $from; $endDate = $to;
} elseif ($from) {
    $startDate = $from;
    $endDate   = !empty($records) ? end($records)['work_date'] : date('Y-m-t', strtotime($from));
} elseif ($to) {
    $startDate = !empty($records) ? $records[0]['work_date'] : date('Y-m-01', strtotime($to));
    $endDate   = $to;
} else {
    $startDate = !empty($records) ? $records[0]['work_date'] : date('Y-m-01');
    $endDate   = !empty($records) ? end($records)['work_date'] : date('Y-m-t');
}

// ── Build month list ─────────────────────────────────────────────
$months    = [];
$cursor    = new DateTime($startDate); $cursor->modify('first day of this month');
$endDtFull = (new DateTime($endDate))->modify('last day of this month');
while ($cursor <= $endDtFull) { $months[] = $cursor->format('Y-m'); $cursor->modify('+1 month'); }

// ── Helpers ──────────────────────────────────────────────────────
$dayNames   = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];
$monthNames = ['','January','February','March','April','May','June',
               'July','August','September','October','November','December'];

function fmt(?string $t, string $fallback = ''): string {
    if (!$t) return $fallback;
    $parts = explode(':', $t);
    if (count($parts) < 2) return $fallback;
    $h = (int)$parts[0]; $m = $parts[1];
    return sprintf('%d:%s %s', $h % 12 ?: 12, $m, $h >= 12 ? 'PM' : 'AM');
}

// Date label for header
$dateLabel = '';
if ($from && $to)    $dateLabel = date('F j, Y', strtotime($from)) . ' — ' . date('F j, Y', strtotime($to));
elseif ($from)       $dateLabel = 'From ' . date('F j, Y', strtotime($from));
elseif ($to)         $dateLabel = 'Up to ' . date('F j, Y', strtotime($to));
else                 $dateLabel = date('F j, Y', strtotime($startDate)) . ' — ' . date('F j, Y', strtotime($endDate));

// Logo & establishment
$logoFile = !empty($trainee['establishment_logo'])
            ? '../' . ltrim($trainee['establishment_logo'], '/')
            : '../assets/img/prclogo.png';
$logoUrl  = htmlspecialchars($logoFile);
$estName  = !empty($trainee['establishment_name'])
            ? $trainee['establishment_name']
            : 'Professional Regulation Commission';
?><!DOCTYPE html>
<html lang="en">
<head>
 <meta charset="UTF-8">
 <meta name="viewport" content="width=device-width, initial-scale=1.0">
 <title>DTR Report — <?= htmlspecialchars($trainee['name']) ?></title>
 <style>
  /* ── Base ─────────────────────────────────────────────── */
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
  body { font-family: 'Segoe UI', Arial, sans-serif; background: #f5f6f8; color: #1e293b; font-size: 12px; }

  /* ── Screen toolbar ───────────────────────────────────── */
  .toolbar { background: #0f172a; color: #fff; padding: 12px 24px; display: flex; align-items: center; justify-content: space-between; gap: 12px; position: sticky; top: 0; z-index: 99; }
  .toolbar h1 { font-size: 15px; font-weight: 600; }
  .toolbar-actions { display: flex; gap: 10px; }
  .btn { padding: 8px 18px; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer; border: none; transition: background .15s; }
  .btn-print { background: #4f46e5; color: #fff; } .btn-print:hover { background: #4338ca; }
  .btn-back  { background: rgba(255,255,255,.12); color: #fff; } .btn-back:hover { background: rgba(255,255,255,.22); }

  /* ── Report wrapper ───────────────────────────────────── */
  .report-wrapper { max-width: 960px; margin: 24px auto 48px; background: #fff; border-radius: 12px; box-shadow: 0 4px 32px rgba(0,0,0,.1); overflow: hidden; }

  /* ── Report header (gradient blue) ───────────────────── */
  .report-header { background: linear-gradient(135deg, #1e3a5f 0%, #1e40af 100%); color: #fff; padding: 22px 28px 18px; }
  .report-header-inner { display: flex; align-items: center; gap: 20px; }
  .report-logo-wrap { flex-shrink: 0; width: 76px; height: 76px; background: #fff; border-radius: 10px; display: flex; align-items: center; justify-content: center; padding: 6px; box-shadow: 0 2px 10px rgba(0,0,0,.3); }
  .report-logo-wrap img { width: 100%; height: 100%; object-fit: contain; display: block; }
  .report-header-text { flex: 1; min-width: 0; }
  .report-establishment { font-size: 11.5px; font-weight: 600; letter-spacing: .06em; text-transform: uppercase; color: rgba(255,255,255,.7); margin-bottom: 3px; }
  .report-meta-top { font-size: 10.5px; letter-spacing: .08em; text-transform: uppercase; color: rgba(255,255,255,.5); margin-bottom: 5px; }
  .report-title { font-size: 19px; font-weight: 700; margin-bottom: 2px; }
  .report-period { font-size: 12.5px; color: rgba(255,255,255,.75); }

  /* ── Student info strip ───────────────────────────────── */
  .student-info { display: grid; grid-template-columns: repeat(3, 1fr); border-bottom: 1px solid #e2e8f0; }
  .si-item { padding: 10px 18px; border-right: 1px solid #e2e8f0; }
  .si-item:last-child { border-right: none; }
  .si-item label { display: block; font-size: 9px; text-transform: uppercase; letter-spacing: .07em; color: #94a3b8; margin-bottom: 3px; }
  .si-item span  { font-size: 12px; font-weight: 600; color: #1e293b; }

  /* ── Summary bar ──────────────────────────────────────── */
  .summary-bar { display: grid; grid-template-columns: repeat(6, 1fr); background: #f8fafc; border-bottom: 1px solid #e2e8f0; }
  .summary-card { padding: 12px 8px; text-align: center; border-right: 1px solid #e2e8f0; }
  .summary-card:last-child { border-right: none; }
  .summary-card .val { font-size: 17px; font-weight: 700; color: #1e40af; }
  .summary-card .lbl { font-size: 9px; color: #94a3b8; margin-top: 2px; text-transform: uppercase; letter-spacing: .04em; }
  .summary-card.warn .val { color: #d97706; }
  .summary-card.good .val { color: #059669; }

  /* ── Progress bar ─────────────────────────────────────── */
  .progress-row { padding: 10px 28px; background: #f8fafc; border-bottom: 1px solid #e2e8f0; }
  .progress-top { display: flex; justify-content: space-between; font-size: 10.5px; color: #64748b; margin-bottom: 5px; }
  .progress-bg  { height: 6px; background: #e2e8f0; border-radius: 999px; overflow: hidden; }
  .progress-fill{ height: 100%; background: linear-gradient(90deg, #2563eb, #4f46e5); border-radius: 999px; }

  /* ── Monthly grid ─────────────────────────────────────── */
  .section-heading { padding: 10px 18px; background: #f1f5f9; border-bottom: 1px solid #e2e8f0; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: .08em; color: #475569; }
  .monthly-grid { display: grid; grid-template-columns: 1fr 1fr; }
  .month-block { border-right: 1px solid #e2e8f0; border-bottom: 1px solid #e2e8f0; }
  .month-block:nth-child(even) { border-right: none; }

  /* Month title bar */
  .month-title { background: linear-gradient(90deg, #1e3a5f, #1e40af); color: #fff; padding: 7px 12px; font-size: 10.5px; font-weight: 700; text-transform: uppercase; letter-spacing: .08em; }

  /* Month table */
  .month-table { width: 100%; border-collapse: collapse; font-size: 9.5px; }
  .month-table thead tr { background: #1e40af; color: #fff; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
  .month-table thead th { padding: 5px 3px; text-align: center; font-size: 8.5px; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; white-space: nowrap; }
  .month-table thead th:first-child { text-align: left; padding-left: 10px; width: 28px; }
  .month-table thead th:nth-child(2) { width: 28px; }
  .month-table tbody tr { border-bottom: 1px solid #f1f5f9; }
  .month-table tbody tr:nth-child(even):not(.row-weekend):not(.row-special) { background: #f8fbff; }
  .month-table tbody td { padding: 4px 3px; text-align: center; white-space: nowrap; font-size: 9.5px; }
  .month-table tbody td:first-child { text-align: left; padding-left: 10px; font-weight: 700; color: #334155; }
  .month-table tbody td:nth-child(2) { color: #64748b; font-size: 9px; }

  /* Weekend rows */
  .row-weekend { background: #f1f5f9 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
  .row-weekend td { color: #94a3b8 !important; }
  .row-weekend .cell-label { text-align: left; padding-left: 6px; font-style: italic; font-size: 9px; }

  /* Empty / no-record weekday */
  .row-empty td { color: #cbd5e1; }
  .row-empty .cell-label { text-align: left; padding-left: 6px; color: #e2e8f0; }

  /* Attendance row */
  .row-present td.time-cell { color: #1e40af; font-weight: 600; }
  .row-present td.hrs-cell  { color: #059669; font-weight: 700; }

  /* Late badge inline */
  .late-tag { font-size: 7.5px; background: #fef3c7; color: #92400e; border-radius: 3px; padding: 1px 4px; margin-left: 2px; font-weight: 600; }

  /* Month total footer */
  .month-table tfoot td { padding: 6px 10px; font-size: 10px; font-weight: 700; color: #1e40af; border-top: 2px solid #1e40af; background: #eff6ff; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
  .month-table tfoot .total-val { text-align: right; padding-right: 8px; font-size: 11px; color: #059669; }

  /* ── Signature section ────────────────────────────────── */
  .signature-section { padding: 22px 28px 18px; border-top: 2px solid #e2e8f0; background: #fff; }
  .signature-section h4 { font-size: 10.5px; font-weight: 700; text-transform: uppercase; letter-spacing: .08em; color: #64748b; margin-bottom: 18px; }
  .signature-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 32px; }
  .sig-block { display: flex; flex-direction: column; }
  .sig-block .sig-label { font-size: 10px; color: #94a3b8; text-transform: uppercase; letter-spacing: .06em; margin-bottom: 32px; }
  .sig-block .sig-name  { font-size: 13px; font-weight: 700; color: #1e293b; border-top: 1.5px solid #334155; padding-top: 6px; }
  .sig-block .sig-title { font-size: 10px; color: #64748b; margin-top: 2px; }
  .sig-date-row { margin-top: 18px; font-size: 11px; color: #64748b; }
  .sig-date-row span { display: inline-block; min-width: 140px; border-bottom: 1px solid #94a3b8; margin-left: 6px; }

  /* ── Footer ───────────────────────────────────────────── */
  .report-footer { padding: 13px 28px; border-top: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; font-size: 10.5px; color: #94a3b8; background: #f8fafc; }
  .report-footer strong { color: #475569; }

  /* ── Print ────────────────────────────────────────────── */
  @media print {
   body { background: #fff; font-size: 9px; }
   .toolbar { display: none !important; }
   .report-wrapper { box-shadow: none; border-radius: 0; margin: 0; max-width: 100%; }
   .report-header  { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
   .month-title    { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
   .report-logo-wrap { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
   .summary-bar    { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
   .row-weekend    { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
   .month-table tfoot td { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
   .month-block    { page-break-inside: avoid; }
   .signature-section { page-break-inside: avoid; }
  }
 </style>
</head>
<body>

<!-- Screen-only toolbar -->
<div class="toolbar">
 <h1>📄 DTR Report Preview</h1>
 <div class="toolbar-actions">
  <button class="btn btn-back"  onclick="window.close()">← Close</button>
  <button class="btn btn-print" onclick="window.print()">🖨 Print / Save PDF</button>
 </div>
</div>

<div class="report-wrapper">

 <!-- ── Report Header ──────────────────────────────────── -->
 <div class="report-header">
  <div class="report-header-inner">
   <div class="report-logo-wrap">
    <img src="<?= $logoUrl ?>" alt="<?= htmlspecialchars($estName) ?> Logo"
         onerror="this.style.display='none'">
   </div>
   <div class="report-header-text">
    <div class="report-establishment"><?= htmlspecialchars($estName) ?></div>
    <div class="report-meta-top">OJT Daily Time Record (DTR)</div>
    <div class="report-title"><?= htmlspecialchars($trainee['name']) ?></div>
    <div class="report-period"><?= htmlspecialchars($dateLabel) ?></div>
   </div>
  </div>
 </div>

 <!-- ── Student Info Strip ────────────────────────────── -->
 <div class="student-info">
  <div class="si-item">
   <label>Full Name</label>
   <span><?= htmlspecialchars($trainee['name']) ?></span>
  </div>
  <div class="si-item">
   <label>Assigned Agency / Establishment</label>
   <span><?= htmlspecialchars($estName) ?></span>
  </div>
  <div class="si-item">
   <label>Inclusive Dates</label>
   <span><?= htmlspecialchars($dateLabel) ?></span>
  </div>
  <div class="si-item">
   <label>Email / Student ID</label>
   <span><?= htmlspecialchars($trainee['email']) ?></span>
  </div>
  <div class="si-item">
   <label>Training Supervisor</label>
   <span><?= htmlspecialchars($trainee['training_supervisor'] ?? '—') ?></span>
  </div>
  <div class="si-item">
   <label>Required OJT Hours</label>
   <span><?= $required ?> hours</span>
  </div>
 </div>

 <!-- ── Summary Cards ──────────────────────────────────── -->
 <div class="summary-bar">
  <div class="summary-card">
   <div class="val"><?= count($records) ?></div>
   <div class="lbl">Days Present</div>
  </div>
  <div class="summary-card">
   <div class="val"><?= round($totalHours, 2) ?></div>
   <div class="lbl">Total Hours</div>
  </div>
  <div class="summary-card good">
   <div class="val"><?= $progressPct ?>%</div>
   <div class="lbl">Progress</div>
  </div>
  <div class="summary-card">
   <div class="val"><?= round($remaining, 2) ?></div>
   <div class="lbl">Hrs Remaining</div>
  </div>
  <div class="summary-card warn">
   <div class="val"><?= $lateCount ?></div>
   <div class="lbl">Late Days</div>
  </div>
  <div class="summary-card good">
   <div class="val"><?= round($totalOT, 2) ?></div>
   <div class="lbl">Overtime Hrs</div>
  </div>
 </div>

 <!-- ── Progress Bar ────────────────────────────────────── -->
 <div class="progress-row">
  <div class="progress-top">
   <span>OJT Hour Progress</span>
   <span><?= round($totalHours, 2) ?> / <?= $required ?> hours rendered</span>
  </div>
  <div class="progress-bg">
   <div class="progress-fill" style="width:<?= $progressPct ?>%"></div>
  </div>
 </div>

 <!-- ── Monthly DTR Grid ───────────────────────────────── -->
 <div class="section-heading">📅 Daily Time Records — Per Month</div>
 <div class="monthly-grid">
<?php
$rangeStart = new DateTime($startDate);
$rangeEnd   = new DateTime($endDate);

foreach ($months as $ym):
    [$yr, $mo] = explode('-', $ym);
    $monthLabel  = $monthNames[(int)$mo] . ' ' . $yr;
    $daysInMonth = (int)date('t', mktime(0, 0, 0, $mo, 1, $yr));
    $monthHours  = 0;

    // Pre-compute monthly total for display in title
    for ($d = 1; $d <= $daysInMonth; $d++) {
        $ds = sprintf('%04d-%02d-%02d', $yr, $mo, $d);
        if (isset($recordMap[$ds])) $monthHours += (float)$recordMap[$ds]['total_hours'];
    }
?>
  <div class="month-block">
   <div class="month-title"><?= $monthLabel ?></div>
   <table class="month-table">
    <thead>
     <tr>
      <th>#</th>
      <th>Day</th>
      <th>AM In</th>
      <th>AM Out</th>
      <th>PM In</th>
      <th>PM Out</th>
      <th>Hrs</th>
     </tr>
    </thead>
    <tbody>
<?php
    for ($d = 1; $d <= $daysInMonth; $d++):
        $dateStr = sprintf('%04d-%02d-%02d', $yr, $mo, $d);
        $dt      = new DateTime($dateStr);

        // Skip days outside the selected range
        if ($dt < $rangeStart || $dt > $rangeEnd) continue;

        $dow       = (int)$dt->format('w'); // 0=Sun,6=Sat
        $dayAbbr   = $dayNames[$dow];
        $isWeekend = ($dow === 0 || $dow === 6);
        $rec       = $recordMap[$dateStr] ?? null;

        if ($isWeekend):
?>
     <tr class="row-weekend">
      <td><?= $d ?></td>
      <td><?= $dayAbbr ?></td>
      <td colspan="5" class="cell-label"><?= ($dow === 6 ? 'Saturday' : 'Sunday') ?></td>
     </tr>
<?php   elseif ($rec):
            $amIn  = $rec['am_in']  ? fmt($rec['am_in'])  : fmt(substr($rec['time_in']  ?? '', 11, 5));
            $amOut = $rec['am_out'] ? fmt($rec['am_out']) : '—';
            $pmIn  = $rec['pm_in']  ? fmt($rec['pm_in'])  : '—';
            $pmOut = $rec['pm_out'] ? fmt($rec['pm_out']) : fmt(substr($rec['time_out'] ?? '', 11, 5));
            $hrs   = number_format((float)$rec['total_hours'], 2);
            $isLate = (int)$rec['late_minutes'] > 0;
?>
     <tr class="row-present">
      <td><?= $d ?></td>
      <td><?= $dayAbbr ?></td>
      <td class="time-cell"><?= $amIn ?: '—' ?></td>
      <td class="time-cell"><?= $amOut ?></td>
      <td class="time-cell"><?= $pmIn ?></td>
      <td class="time-cell"><?= $pmOut ?: '—' ?></td>
      <td class="hrs-cell"><?= $hrs ?><?php if ($isLate): ?><span class="late-tag">L</span><?php endif; ?></td>
     </tr>
<?php   else: ?>
     <tr class="row-empty">
      <td><?= $d ?></td>
      <td><?= $dayAbbr ?></td>
      <td colspan="5" class="cell-label"></td>
     </tr>
<?php   endif; ?>
<?php endfor; ?>
    </tbody>
    <tfoot>
     <tr>
      <td colspan="6">Total Monthly Hours:</td>
      <td class="total-val"><?= number_format($monthHours, 2) ?> hrs</td>
     </tr>
    </tfoot>
   </table>
  </div><!-- /month-block -->
<?php endforeach; ?>

 </div><!-- /monthly-grid -->

 <!-- ── Signature / Certification ──────────────────────── -->
 <div class="signature-section">
  <h4>Certification &amp; Signatures</h4>
  <div class="signature-grid">
   <div class="sig-block">
    <span class="sig-label">Prepared by (Trainee)</span>
    <span class="sig-name"><?= htmlspecialchars($trainee['name']) ?></span>
    <span class="sig-title">OJT Trainee</span>
   </div>
   <div class="sig-block">
    <span class="sig-label">Training Supervisor / Noted by</span>
    <span class="sig-name"><?= htmlspecialchars($trainee['training_supervisor'] ?? '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;') ?></span>
    <span class="sig-title">Training Supervisor</span>
   </div>
  </div>
  <div class="sig-date-row">Date: <span></span></div>
 </div>

 <!-- ── Report Footer ───────────────────────────────────── -->
 <div class="report-footer">
  <span>OJT DTR Monitoring System &mdash; Generated <?= date('Y-m-d H:i') ?></span>
  <span>Trainee: <strong><?= htmlspecialchars($trainee['name']) ?></strong> &bull; <?= round($totalHours, 2) ?> / <?= $required ?> hrs rendered</span>
 </div>

</div><!-- /report-wrapper -->
</body>
</html>
