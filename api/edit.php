<?php
// ============================================================
// api/edit.php  — Edit Attendance + Audit Log
// Actions: edit_record
// ============================================================

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');
startSession();
requireLogin();

$user   = getCurrentUser();
$userId = (int) $user['id'];
$action = $_POST['action'] ?? '';
$pdo    = getDB();

// Reuse calculation logic
function calcAttendanceEdit(string $timeIn, ?string $timeOut): array {
    $WORK_START     = 8;
    $LUNCH_START    = 12;
    $LUNCH_END      = 13;
    $REQUIRED_HOURS = 8;

    $inDT      = new DateTime($timeIn);
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
        $inHour  = (int) $inDT->format('G');
        $outHour = (int) $outDT->format('G');
        if ($inHour < $LUNCH_END && $outHour >= $LUNCH_START) {
            $workMs -= 3600;
        }
        $totalHours = max(0.0, round($workMs / 3600.0, 2));
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

switch ($action) {

    // --------------------------------------------------------
    case 'edit_record':
        $recordId   = (int)($_POST['record_id']  ?? 0);
        $newTimeIn  = trim($_POST['time_in']      ?? '');  // "HH:MM"
        $newTimeOut = trim($_POST['time_out']     ?? '');  // "HH:MM" or empty
        $reason     = trim($_POST['reason']       ?? '');

        if (!$recordId || !$newTimeIn || !$reason) {
            jsonResponse(['ok' => false, 'message' => 'Record ID, time in, and reason are required.'], 400);
        }

        // Fetch the existing record — must belong to the current user
        $stmt = $pdo->prepare('SELECT * FROM attendance WHERE id = ? AND user_id = ? LIMIT 1');
        $stmt->execute([$recordId, $userId]);
        $record = $stmt->fetch();

        if (!$record) {
            jsonResponse(['ok' => false, 'message' => 'Record not found or access denied.'], 404);
        }

        $workDate      = $record['work_date'];
        $newTimeInFull  = $workDate . ' ' . $newTimeIn . ':00';
        $newTimeOutFull = $newTimeOut ? $workDate . ' ' . $newTimeOut . ':00' : null;

        if ($newTimeOutFull && strtotime($newTimeOutFull) <= strtotime($newTimeInFull)) {
            jsonResponse(['ok' => false, 'message' => 'Time out must be after time in.'], 400);
        }

        // Insert audit log FIRST
        $stmt = $pdo->prepare(
            'INSERT INTO edit_logs
               (attendance_id, user_id, old_time_in, new_time_in, old_time_out, new_time_out, reason)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $recordId,
            $userId,
            $record['time_in'],
            $newTimeInFull,
            $record['time_out'] ?: null,
            $newTimeOutFull,
            $reason,
        ]);

        // Recalculate attendance
        $calc   = calcAttendanceEdit($newTimeInFull, $newTimeOutFull);
        $now    = date('Y-m-d H:i:s');

        $stmt = $pdo->prepare(
            'UPDATE attendance
             SET time_in = ?, time_out = ?, total_hours = ?, late_minutes = ?,
                 status = ?, last_edited_at = ?
             WHERE id = ?'
        );
        $stmt->execute([
            $newTimeInFull,
            $newTimeOutFull,
            $calc['totalHours'],
            $calc['lateMinutes'],
            $calc['lateMinutes'] > 0 ? 'Late' : $calc['status'],
            $now,
            $recordId,
        ]);

        jsonResponse(['ok' => true, 'message' => 'Record updated successfully.']);
        break;

    // --------------------------------------------------------
    default:
        jsonResponse(['ok' => false, 'message' => 'Unknown action.'], 400);
}
