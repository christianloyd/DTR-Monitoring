<?php
// ============================================================
// api/attendance.php  — Attendance Endpoints
// Actions: time_in | time_out | get_my_records | add_log | get_today
// ============================================================

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');
startSession();
requireLogin();

$user   = getCurrentUser();
$userId = (int) $user['id'];
$action = $_POST['action'] ?? $_GET['action'] ?? '';
$pdo    = getDB();

// ============================================================
// Shared attendance calculation (mirrors JS logic)
// Work: 08:00–17:00 | Lunch: 12:00–13:00 (auto-deducted)
// ============================================================
function calcAttendance(string $timeIn, ?string $timeOut): array {
    $WORK_START    = 8;
    $LUNCH_START   = 12;
    $LUNCH_END     = 13;
    $REQUIRED_HOURS = 8;

    $inDT = new DateTime($timeIn);
    $workStart = (clone $inDT)->setTime($WORK_START, 0, 0);

    $lateMinutes = 0;
    if ($inDT > $workStart) {
        $lateMinutes = (int) floor(($inDT->getTimestamp() - $workStart->getTimestamp()) / 60);
    }

    $totalHours = 0.0;
    $status     = $lateMinutes > 0 ? 'Late' : 'On Time';

    if ($timeOut) {
        $outDT  = new DateTime($timeOut);
        $workMs = $outDT->getTimestamp() - $inDT->getTimestamp();

        // Deduct lunch break if it falls within the shift
        $inHour  = (int) $inDT->format('G');
        $outHour = (int) $outDT->format('G');
        if ($inHour < $LUNCH_END && $outHour >= $LUNCH_START) {
            $workMs -= 3600; // 1 hour
        }

        $totalHours = max(0.0, $workMs / 3600.0);
        $totalHours = round($totalHours, 2);

        if ($lateMinutes > 0) {
            $status = 'Late';
        } elseif ($totalHours > $REQUIRED_HOURS) {
            $status = 'Overtime';
        } elseif ($totalHours < $REQUIRED_HOURS) {
            $status = 'Undertime';
        } else {
            $status = 'On Time';
        }
    }

    return ['totalHours' => $totalHours, 'lateMinutes' => $lateMinutes, 'status' => $status];
}

// ============================================================
switch ($action) {

    // --------------------------------------------------------
    case 'get_today':
        $today = date('Y-m-d');
        $stmt  = $pdo->prepare(
            'SELECT * FROM attendance WHERE user_id = ? AND work_date = ? LIMIT 1'
        );
        $stmt->execute([$userId, $today]);
        $record = $stmt->fetch();
        jsonResponse(['ok' => true, 'record' => $record ?: null]);
        break;

    // --------------------------------------------------------
    case 'get_my_records':
        $from = $_GET['from'] ?? null;
        $to   = $_GET['to']   ?? null;

        $sql    = 'SELECT * FROM attendance WHERE user_id = ?';
        $params = [$userId];

        if ($from) { $sql .= ' AND work_date >= ?'; $params[] = $from; }
        if ($to)   { $sql .= ' AND work_date <= ?'; $params[] = $to;   }

        $sql .= ' ORDER BY work_date DESC';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $records = $stmt->fetchAll();

        jsonResponse(['ok' => true, 'records' => $records]);
        break;

    // --------------------------------------------------------
    case 'get_summary':
        $stmt = $pdo->prepare(
            'SELECT * FROM attendance WHERE user_id = ? AND time_out IS NOT NULL'
        );
        $stmt->execute([$userId]);
        $records = $stmt->fetchAll();

        $totalHours    = 0;
        $totalOvertime = 0;
        $totalUndertime= 0;
        $lateCount     = 0;
        $daysWorked    = 0;
        $REQUIRED      = 8;

        foreach ($records as $r) {
            $h = (float) $r['total_hours'];
            $totalHours += $h;
            $daysWorked++;
            if ($h > $REQUIRED) $totalOvertime  += $h - $REQUIRED;
            if ($h < $REQUIRED) $totalUndertime += $REQUIRED - $h;
            if ((int)$r['late_minutes'] > 0) $lateCount++;
        }

        jsonResponse([
            'ok'             => true,
            'totalHours'     => round($totalHours, 2),
            'totalOvertime'  => round($totalOvertime, 2),
            'totalUndertime' => round($totalUndertime, 2),
            'lateCount'      => $lateCount,
            'daysWorked'     => $daysWorked,
        ]);
        break;

    // --------------------------------------------------------
    case 'time_in':
        $today = date('Y-m-d');
        // Check duplicate
        $stmt = $pdo->prepare(
            'SELECT id FROM attendance WHERE user_id = ? AND work_date = ? LIMIT 1'
        );
        $stmt->execute([$userId, $today]);
        if ($stmt->fetch()) {
            jsonResponse(['ok' => false, 'message' => 'You have already timed in today.'], 409);
        }

        $now  = date('Y-m-d H:i:s');
        $calc = calcAttendance($now, null);

        $stmt = $pdo->prepare(
            'INSERT INTO attendance
               (user_id, work_date, time_in, time_out, total_hours, late_minutes, status)
             VALUES (?, ?, ?, NULL, 0, ?, ?)'
        );
        $stmt->execute([
            $userId, $today, $now,
            $calc['lateMinutes'],
            $calc['lateMinutes'] > 0 ? 'Late' : 'On Time',
        ]);

        jsonResponse([
            'ok'          => true,
            'message'     => 'Time in recorded.',
            'time_in'     => $now,
            'lateMinutes' => $calc['lateMinutes'],
        ]);
        break;

    // --------------------------------------------------------
    case 'time_out':
        $today = date('Y-m-d');
        $stmt  = $pdo->prepare(
            'SELECT * FROM attendance WHERE user_id = ? AND work_date = ? LIMIT 1'
        );
        $stmt->execute([$userId, $today]);
        $record = $stmt->fetch();

        if (!$record) {
            jsonResponse(['ok' => false, 'message' => 'No time-in record found for today.'], 404);
        }
        if ($record['time_out']) {
            jsonResponse(['ok' => false, 'message' => 'Already timed out today.'], 409);
        }

        $now  = date('Y-m-d H:i:s');
        $calc = calcAttendance($record['time_in'], $now);

        // Preserve "Late" status if time-in was late
        $status = (int)$record['late_minutes'] > 0 ? 'Late' : $calc['status'];

        $stmt = $pdo->prepare(
            'UPDATE attendance
             SET time_out = ?, total_hours = ?, status = ?
             WHERE id = ?'
        );
        $stmt->execute([$now, $calc['totalHours'], $status, $record['id']]);

        jsonResponse([
            'ok'         => true,
            'message'    => 'Time out recorded.',
            'time_out'   => $now,
            'totalHours' => $calc['totalHours'],
            'status'     => $status,
        ]);
        break;

    // --------------------------------------------------------
    case 'add_log':
        $date     = trim($_POST['date']     ?? '');
        $amIn     = trim($_POST['am_in']    ?? '');
        $amOut    = trim($_POST['am_out']   ?? '');
        $pmIn     = trim($_POST['pm_in']    ?? '');
        $pmOut    = trim($_POST['pm_out']   ?? '');
        $otIn     = trim($_POST['ot_in']    ?? '');
        $otOut    = trim($_POST['ot_out']   ?? '');

        // Required fields
        if (!$date || !$amIn || !$amOut || !$pmIn || !$pmOut) {
            jsonResponse(['ok' => false, 'message' => 'Date, Morning, and Afternoon sessions are required.'], 400);
        }
        if ($date > date('Y-m-d')) {
            jsonResponse(['ok' => false, 'message' => 'Cannot add time log for future dates.'], 400);
        }

        // Validate time ordering within each session
        if ($amOut <= $amIn) {
            jsonResponse(['ok' => false, 'message' => 'Morning Out must be after Morning In.'], 400);
        }
        if ($pmOut <= $pmIn) {
            jsonResponse(['ok' => false, 'message' => 'Afternoon Out must be after Afternoon In.'], 400);
        }
        if ($otIn && $otOut && $otOut <= $otIn) {
            jsonResponse(['ok' => false, 'message' => 'Overtime Out must be after Overtime In.'], 400);
        }

        // Check duplicate date
        $stmt = $pdo->prepare('SELECT id FROM attendance WHERE user_id = ? AND work_date = ? LIMIT 1');
        $stmt->execute([$userId, $date]);
        if ($stmt->fetch()) {
            jsonResponse(['ok' => false, 'message' => 'A record already exists for this date. Use Edit to modify.'], 409);
        }

        // Calculate hours per session
        $toSecs = fn($t) => strtotime("{$date} {$t}:00");

        $amHours = max(0, ($toSecs($amOut) - $toSecs($amIn)) / 3600);
        $pmHours = max(0, ($toSecs($pmOut) - $toSecs($pmIn)) / 3600);
        $otHours = ($otIn && $otOut) ? max(0, ($toSecs($otOut) - $toSecs($otIn)) / 3600) : 0;

        $totalHours = round($amHours + $pmHours + $otHours, 2);

        // For the record: time_in = morning in, time_out = last out (ot out if present, else pm out)
        $timeIn  = $date . ' ' . $amIn  . ':00';
        $timeOut = $date . ' ' . ($otIn && $otOut ? $otOut : $pmOut) . ':00';

        // Determine lateness (vs 08:00 AM)
        $workStart   = strtotime("{$date} 08:00:00");
        $actualIn    = strtotime($timeIn);
        $lateMinutes = ($actualIn > $workStart) ? (int) floor(($actualIn - $workStart) / 60) : 0;

        $requiredHours = 8;
        if ($lateMinutes > 0)              $status = 'Late';
        elseif ($totalHours > $requiredHours) $status = 'Overtime';
        elseif ($totalHours < $requiredHours) $status = 'Undertime';
        else                               $status = 'On Time';

        $stmt = $pdo->prepare(
            'INSERT INTO attendance
               (user_id, work_date, time_in, am_in, am_out, pm_in, pm_out, time_out, total_hours, late_minutes, status)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $userId, $date, $timeIn,
            $amIn ?: null, $amOut ?: null,
            $pmIn ?: null, $pmOut ?: null,
            $timeOut, $totalHours, $lateMinutes, $status,
        ]);

        jsonResponse([
            'ok'         => true,
            'message'    => "Time log added for {$date} ({$totalHours} hrs, Status: {$status}).",
            'totalHours' => $totalHours,
        ]);
        break;


    // --------------------------------------------------------
    // QR Scanner: records time-in or time-out for any user by email
    // --------------------------------------------------------
    case 'scanner_submit':
        $email = trim($_POST['email'] ?? '');
        if (!$email) {
            jsonResponse(['ok' => false, 'message' => 'Email is required.'], 400);
        }

        // Look up the target user
        $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $targetUser = $stmt->fetch();

        if (!$targetUser) {
            jsonResponse(['ok' => false, 'message' => 'User not found. Please check the email.'], 404);
        }

        $today = date('Y-m-d');
        $now   = date('Y-m-d H:i:s');

        $stmt = $pdo->prepare(
            'SELECT * FROM attendance WHERE user_id = ? AND work_date = ? LIMIT 1'
        );
        $stmt->execute([$targetUser['id'], $today]);
        $todayRecord = $stmt->fetch();

        if ($todayRecord && !$todayRecord['time_out']) {
            // Time OUT
            $calc   = calcAttendance($todayRecord['time_in'], $now);
            $status = (int)$todayRecord['late_minutes'] > 0 ? 'Late' : $calc['status'];
            $pdo->prepare(
                'UPDATE attendance SET time_out=?, total_hours=?, status=? WHERE id=?'
            )->execute([$now, $calc['totalHours'], $status, $todayRecord['id']]);

            jsonResponse([
                'ok'      => true,
                'message' => "✓ Time out recorded for {$targetUser['name']} ({$calc['totalHours']} hrs)",
            ]);
        } elseif (!$todayRecord) {
            // Time IN
            $calc = calcAttendance($now, null);
            $pdo->prepare(
                'INSERT INTO attendance (user_id, work_date, time_in, late_minutes, status)
                 VALUES (?, ?, ?, ?, ?)'
            )->execute([
                $targetUser['id'], $today, $now,
                $calc['lateMinutes'],
                $calc['lateMinutes'] > 0 ? 'Late' : 'On Time',
            ]);

            $lateMsg = $calc['lateMinutes'] > 0 ? " ({$calc['lateMinutes']} min late)" : '';
            jsonResponse([
                'ok'      => true,
                'message' => "✓ Time in recorded for {$targetUser['name']}{$lateMsg}",
            ]);
        } else {
            jsonResponse([
                'ok'      => false,
                'message' => "{$targetUser['name']} already timed out today.",
            ], 409);
        }
        break;

    // --------------------------------------------------------
    default:
        jsonResponse(['ok' => false, 'message' => 'Unknown action.'], 400);
}
