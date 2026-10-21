<?php

declare(strict_types=1);

namespace Modules\Attendance\Libraries;

use CodeIgniter\HTTP\IncomingRequest;

final class AttendanceRecorder
{
    /** Inserts once per student/session; duplicate scans remain successful and do not create duplicates. */
    public static function mark(int $sessionId, int $studentId, IncomingRequest $request): array
    {
        $db = db_connect();
        // Callers start a transaction before this method; FOR UPDATE serializes check-in with session closure.
        $session = $db->query(
            'SELECT id, status, opened_at FROM attendance_sessions WHERE id = ? AND status = ? FOR UPDATE',
            [$sessionId, 'open']
        )->getRowArray();
        if (!$session) {
            throw new \RuntimeException('Cet appel est fermé.');
        }

        $now = time();
        $opened = strtotime((string) $session['opened_at'] . ' UTC');
        $delay = max(0, $now - ($opened === false ? $now : $opened));
        $userAgent = $request->getUserAgent();
        $agent = substr($userAgent ? $userAgent->getAgentString() : '', 0, 255);
        $ip = substr((string) $request->getIPAddress(), 0, 45);
        $markedAt = gmdate('Y-m-d H:i:s', $now);

        $db->query(
            'INSERT IGNORE INTO attendances (session_id, student_id, marked_at, ip, user_agent, delay_seconds, flagged, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [$sessionId, $studentId, $markedAt, $ip, $agent, $delay, $delay > 600 ? 1 : 0, $markedAt, $markedAt]
        );

        $created = $db->affectedRows() === 1;
        $row = $db->table('attendances')->where('session_id', $sessionId)->where('student_id', $studentId)->get()->getRowArray();
        if (!$row) {
            throw new \RuntimeException('Impossible de confirmer la présence. Réessaie.');
        }

        return [
            'already_present' => !$created,
            'marked_at' => $row['marked_at'],
            'delay_seconds' => (int) $row['delay_seconds'],
            'flagged' => (int) $row['flagged'] === 1,
        ];
    }
}
