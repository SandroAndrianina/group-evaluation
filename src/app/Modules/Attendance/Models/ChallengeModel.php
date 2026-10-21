<?php

declare(strict_types=1);

namespace Modules\Attendance\Models;

use CodeIgniter\Model;

final class ChallengeModel extends Model
{
    protected $table         = 'challenges';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $allowedFields = ['challenge', 'session_id', 'student_id', 'purpose', 'expires_at', 'used_at', 'created_at'];
    protected $useTimestamps = false;

    /** Issue a DB-backed, HMAC-signed QR ticket valid for exactly 10 seconds. */
    public function issueQrToken(int $sessionId, string $sessionSecret): array
    {
        $expires = time() + 10;
        $payload = $sessionId . '.' . $expires . '.' . self::base64UrlEncode(random_bytes(8));
        $payloadPart = self::base64UrlEncode($payload);
        $signature = self::base64UrlEncode(substr(hash_hmac('sha256', $payloadPart, $sessionSecret, true), 0, 16));
        $token = $payloadPart . '.' . $signature;

        $this->insert([
            'challenge'  => $token,
            'session_id' => $sessionId,
            'student_id' => null,
            'purpose'    => 'qr',
            'expires_at' => gmdate('Y-m-d H:i:s', $expires),
            'used_at'    => null,
            'created_at' => gmdate('Y-m-d H:i:s'),
        ]);

        return ['token' => $token, 'expires_in' => 10, 'expires_at' => $expires];
    }

    /** Returns a ticket only if the database row, expiry, payload and HMAC all agree. */
    public function findValidQr(string $token): ?array
    {
        if (strlen($token) > 100 || !preg_match('/^[A-Za-z0-9_-]+\.[A-Za-z0-9_-]+$/', $token)) {
            return null;
        }

        $row = $this->where('challenge', $token)
            ->where('purpose', 'qr')
            ->where('expires_at >=', gmdate('Y-m-d H:i:s'))
            ->first();
        if (!$row) {
            return null;
        }

        $parts = explode('.', $token, 2);
        if (count($parts) !== 2) {
            return null;
        }
        $payload = self::base64UrlDecode($parts[0]);
        if ($payload === null) {
            return null;
        }
        $payloadParts = explode('.', $payload, 3);
        if (count($payloadParts) !== 3 || !ctype_digit($payloadParts[0]) || !ctype_digit($payloadParts[1])) {
            return null;
        }
        [$sid, $expires, $nonce] = $payloadParts;
        if ((int) $sid !== (int) $row['session_id'] || (int) $expires <= time() || $nonce === '') {
            return null;
        }

        $session = db_connect()->table('attendance_sessions')->where('id', (int) $sid)->get()->getRowArray();
        if (!$session || $session['status'] !== 'open') {
            return null;
        }
        $expected = self::base64UrlEncode(substr(hash_hmac('sha256', $parts[0], (string) $session['secret'], true), 0, 16));
        if (!hash_equals($expected, $parts[1])) {
            return null;
        }

        return $row;
    }

    /** A valid scan creates a short-lived page ticket so students can finish the form after the 10-second QR rotates. */
    public function issueScanTicket(int $sessionId): array
    {
        $ticket = self::base64UrlEncode(random_bytes(32));
        $expires = time() + 300;
        $this->insert([
            'challenge'  => $ticket,
            'session_id' => $sessionId,
            'student_id' => null,
            'purpose'    => 'scan',
            'expires_at' => gmdate('Y-m-d H:i:s', $expires),
            'used_at'    => null,
            'created_at' => gmdate('Y-m-d H:i:s'),
        ]);
        return ['token' => $ticket, 'expires_at' => $expires];
    }

    public function findValidScanTicket(string $ticket): ?array
    {
        if (strlen($ticket) > 100 || !preg_match('/^[A-Za-z0-9_-]+$/', $ticket)) {
            return null;
        }
        $row = $this->where('challenge', $ticket)
            ->where('purpose', 'scan')
            ->where('expires_at >=', gmdate('Y-m-d H:i:s'))
            ->first();
        if (!$row) {
            return null;
        }
        $session = db_connect()->table('attendance_sessions')->where('id', (int) $row['session_id'])->get()->getRowArray();
        return $session && $session['status'] === 'open' ? $row : null;
    }

    /** $challenge is the base64url challenge sent to the browser by WebAuthn. */
    public function issueWebAuthnChallenge(int $sessionId, int $studentId, string $purpose, string $challenge): void
    {
        if (!in_array($purpose, ['register', 'login'], true)) {
            throw new \InvalidArgumentException('Invalid WebAuthn challenge purpose.');
        }
        $this->insert([
            'challenge'  => $challenge,
            'session_id' => $sessionId,
            'student_id' => $studentId,
            'purpose'    => $purpose,
            'expires_at' => gmdate('Y-m-d H:i:s', time() + 120),
            'used_at'    => null,
            'created_at' => gmdate('Y-m-d H:i:s'),
        ]);
    }

    /** Atomic one-time consumption. A concurrent replay can update at most one row. */
    public function consumeWebAuthn(string $challenge, string $purpose, int $studentId): ?array
    {
        $now = gmdate('Y-m-d H:i:s');
        $db = db_connect();
        $db->table($this->table)
            ->where('challenge', $challenge)
            ->where('purpose', $purpose)
            ->where('student_id', $studentId)
            ->where('used_at', null)
            ->where('expires_at >=', $now)
            ->update(['used_at' => $now]);

        if ($db->affectedRows() !== 1) {
            return null;
        }

        return $this->where('challenge', $challenge)->where('purpose', $purpose)->first();
    }

    public function purgeOld(): void
    {
        $this->where('expires_at <', gmdate('Y-m-d H:i:s', time() - 3600))->delete();
    }

    public static function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    public static function base64UrlDecode(string $value): ?string
    {
        $decoded = base64_decode(strtr($value, '-_', '+/') . str_repeat('=', (4 - strlen($value) % 4) % 4), true);
        return $decoded === false ? null : $decoded;
    }
}
