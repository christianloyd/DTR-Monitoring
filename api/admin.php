<?php
// ============================================================
// api/admin.php  — Admin-only Endpoints
// ============================================================
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');
startSession();
requireAdmin();

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$pdo    = getDB();

switch ($action) {

    // ── All attendance records ───────────────────────────────
    case 'get_all_records':
        $from = $_GET['from'] ?? '';
        $to   = $_GET['to']   ?? '';
        $uid  = $_GET['user_id'] ?? '';

        $sql    = 'SELECT a.*, u.name AS user_name, u.username
                   FROM attendance a
                   JOIN users u ON u.id = a.user_id
                   WHERE 1=1';
        $params = [];
        if ($from)  { $sql .= ' AND a.work_date >= ?'; $params[] = $from; }
        if ($to)    { $sql .= ' AND a.work_date <= ?'; $params[] = $to; }
        if ($uid)   { $sql .= ' AND a.user_id = ?';   $params[] = (int)$uid; }
        $sql .= ' ORDER BY a.work_date DESC, u.name ASC';

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        jsonResponse(['ok' => true, 'records' => $stmt->fetchAll()]);
        break;

    // ── Audit logs ───────────────────────────────────────────
    case 'get_audit_logs':
        $stmt = $pdo->query(
            'SELECT el.*, u.name AS user_name
             FROM edit_logs el
             JOIN users u ON u.id = el.user_id
             ORDER BY el.edited_at DESC'
        );
        jsonResponse(['ok' => true, 'logs' => $stmt->fetchAll()]);
        break;

    // ── All users with progress ──────────────────────────────
    case 'get_all_users':
        $stmt = $pdo->query(
            'SELECT u.id, u.name, u.username, u.email, u.role, u.required_hours, u.created_at,
                    COALESCE(SUM(a.total_hours),0) AS total_hours,
                    COUNT(a.id) AS days_worked
             FROM users u
             LEFT JOIN attendance a ON a.user_id = u.id
             GROUP BY u.id
             ORDER BY u.role ASC, u.name ASC'
        );
        jsonResponse(['ok' => true, 'users' => $stmt->fetchAll()]);
        break;

    // ── Dashboard stats ──────────────────────────────────────
    case 'get_stats':
        $totalUsers  = $pdo->query("SELECT COUNT(*) FROM users WHERE role='ojt'")->fetchColumn();
        $totalAdmins = $pdo->query("SELECT COUNT(*) FROM users WHERE role='admin'")->fetchColumn();
        $totalRecs   = $pdo->query("SELECT COUNT(*) FROM attendance")->fetchColumn();
        $todayRecs   = $pdo->query("SELECT COUNT(*) FROM attendance WHERE work_date = CURDATE()")->fetchColumn();
        $totalHours  = $pdo->query("SELECT COALESCE(SUM(total_hours),0) FROM attendance")->fetchColumn();
        $lateCount   = $pdo->query("SELECT COUNT(*) FROM attendance WHERE status='Late'")->fetchColumn();
        jsonResponse(['ok' => true, 'stats' => [
            'total_trainees' => (int)$totalUsers,
            'total_admins'   => (int)$totalAdmins,
            'total_records'  => (int)$totalRecs,
            'today_records'  => (int)$todayRecs,
            'total_hours'    => round((float)$totalHours, 2),
            'late_count'     => (int)$lateCount,
        ]]);
        break;

    // ── Delete user ──────────────────────────────────────────
    case 'delete_user':
        $userId = (int)($_POST['user_id'] ?? 0);
        if (!$userId) {
            jsonResponse(['ok' => false, 'message' => 'Invalid user ID.'], 400);
        }
        // Prevent admin from deleting themselves
        $self = getCurrentUser();
        if ($self['id'] == $userId) {
            jsonResponse(['ok' => false, 'message' => 'You cannot delete your own account.'], 403);
        }
        $stmt = $pdo->prepare('DELETE FROM users WHERE id = ?');
        $stmt->execute([$userId]);
        jsonResponse(['ok' => true, 'message' => 'User deleted.']);
        break;

    // ── Edit any attendance record (admin override) ──────────
    case 'admin_edit_record':
        $recordId = (int)($_POST['record_id'] ?? 0);
        $timeIn   = $_POST['time_in']  ?? '';
        $timeOut  = $_POST['time_out'] ?? '';
        $reason   = trim($_POST['reason'] ?? '');

        if (!$recordId || !$timeIn || !$reason) {
            jsonResponse(['ok' => false, 'message' => 'Record ID, time in, and reason are required.'], 400);
        }

        $stmt = $pdo->prepare('SELECT * FROM attendance WHERE id = ? LIMIT 1');
        $stmt->execute([$recordId]);
        $rec = $stmt->fetch();
        if (!$rec) jsonResponse(['ok' => false, 'message' => 'Record not found.'], 404);

        $workDate = $rec['work_date'];
        $newIn    = $workDate . ' ' . $timeIn . ':00';
        $newOut   = $timeOut ? $workDate . ' ' . $timeOut . ':00' : null;

        // Calculate total hours
        $totalHours  = 0; $lateMin = 0; $status = 'Pending';
        if ($newOut) {
            $inTs  = strtotime($newIn);
            $outTs = strtotime($newOut);

            // Deduct lunch
            $lunchStart = strtotime($workDate . ' 12:00:00');
            $lunchEnd   = strtotime($workDate . ' 13:00:00');
            $worked = $outTs - $inTs;
            if ($inTs < $lunchEnd && $outTs > $lunchStart) {
                $worked -= min($outTs, $lunchEnd) - max($inTs, $lunchStart);
            }
            $totalHours = max(0, $worked / 3600);

            $reqStart = strtotime($workDate . ' 08:00:00');
            $reqEnd   = strtotime($workDate . ' 17:00:00');
            $lateMin  = max(0, (int)(($inTs - $reqStart) / 60));
            $status   = $totalHours >= 8 ? 'On Time' : 'Undertime';
            if ($lateMin > 0) $status = $outTs >= $reqEnd ? 'Late' : 'Late';
            if ($totalHours > 8) $status = 'Overtime';
        }

        // Log the edit
        $admin = getCurrentUser();
        $logStmt = $pdo->prepare(
            'INSERT INTO edit_logs (attendance_id, user_id, old_time_in, new_time_in, old_time_out, new_time_out, reason)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $logStmt->execute([$recordId, $admin['id'], $rec['time_in'], $newIn, $rec['time_out'], $newOut, $reason]);

        $updStmt = $pdo->prepare(
            'UPDATE attendance SET time_in=?, time_out=?, total_hours=?, late_minutes=?, status=?, last_edited_at=NOW()
             WHERE id=?'
        );
        $updStmt->execute([$newIn, $newOut, round($totalHours, 2), $lateMin, $status, $recordId]);

        jsonResponse(['ok' => true, 'message' => 'Record updated.']);
        break;

    // ── Get trainees list (for report dropdown) ─────────────
    case 'get_trainees_list':
        $stmt = $pdo->query(
            "SELECT id, name, email, required_hours FROM users WHERE role='ojt' ORDER BY name ASC"
        );
        jsonResponse(['ok' => true, 'trainees' => $stmt->fetchAll()]);
        break;

    // ── DTR Report data ──────────────────────────────────────
    case 'get_report':
        $uid  = (int)($_GET['user_id'] ?? 0);
        $from = $_GET['from'] ?? '';
        $to   = $_GET['to']   ?? '';

        if (!$uid) {
            jsonResponse(['ok' => false, 'message' => 'user_id is required.'], 400);
        }

        // Get trainee info
        $uStmt = $pdo->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
        $uStmt->execute([$uid]);
        $trainee = $uStmt->fetch();
        if (!$trainee) jsonResponse(['ok' => false, 'message' => 'Trainee not found.'], 404);

        // Get attendance records for range
        $sql = 'SELECT * FROM attendance WHERE user_id = ?';
        $params = [$uid];
        if ($from) { $sql .= ' AND work_date >= ?'; $params[] = $from; }
        if ($to)   { $sql .= ' AND work_date <= ?'; $params[] = $to;   }
        $sql .= ' ORDER BY work_date ASC';

        $aStmt = $pdo->prepare($sql);
        $aStmt->execute($params);
        $records = $aStmt->fetchAll();

        // Compute summary totals
        $totalHours    = 0;
        $totalOT       = 0;
        $totalUndertime= 0;
        $lateCount     = 0;
        $daysWorked    = count($records);
        $required      = (int)($trainee['required_hours'] ?? 486);

        foreach ($records as $r) {
            $h = (float)$r['total_hours'];
            $totalHours += $h;
            if ($h > 8) $totalOT       += $h - 8;
            if ($h < 8) $totalUndertime += 8 - $h;
            if ((int)$r['late_minutes'] > 0) $lateCount++;
        }

        jsonResponse([
            'ok'         => true,
            'trainee'    => $trainee,
            'records'    => $records,
            'summary'    => [
                'totalHours'     => round($totalHours, 2),
                'totalOT'        => round($totalOT, 2),
                'totalUndertime' => round($totalUndertime, 2),
                'lateCount'      => $lateCount,
                'daysWorked'     => $daysWorked,
                'requiredHours'  => $required,
                'remaining'      => max(0, round($required - $totalHours, 2)),
                'progressPct'    => min(100, round(($totalHours / $required) * 100, 1)),
            ],
        ]);
        break;

    // ────────────────────────────────────────────────────────
    default:
        jsonResponse(['ok' => false, 'message' => 'Unknown action.'], 400);
}
