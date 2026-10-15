<?php
declare(strict_types=1);

namespace Modules\Attendance\Models;

use CodeIgniter\Model;

class ChallengeModel extends Model
{
    protected $table         = 'challenges';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $allowedFields = ['challenge','session_id','student_id','purpose','expires_at','used_at'];
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = '';

    public function issueQrToken(int $sessionId): array
    {
        $token = bin2hex(random_bytes(16));
        $this->insert([
            'challenge'  => $token,
            'session_id' => $sessionId,
            'student_id' => null,
            'purpose'    => 'qr',
            'expires_at' => date('Y-m-d H:i:s', time() + 20),
        ]);
        return ['token' => $token, 'expires_in' => 20];
    }

    public function findValidQr(string $token): ?array
    {
        return $this->where('challenge', $token)
                    ->where('purpose', 'qr')
                    ->where('expires_at >=', date('Y-m-d H:i:s'))
                    ->first();
    }

    public function issueWebAuthnChallenge(int $sessionId, int $studentId): string
    {
        $token = bin2hex(random_bytes(32));
        $this->insert([
            'challenge'  => $token,
            'session_id' => $sessionId,
            'student_id' => $studentId,
            'purpose'    => 'webauthn',
            'expires_at' => date('Y-m-d H:i:s', time() + 60),
        ]);
        return $token;
    }

    public function consumeWebAuthn(string $token): ?array
    {
        $row = $this->where('challenge', $token)
                    ->where('purpose', 'webauthn')
                    ->where('used_at', null)
                    ->where('expires_at >=', date('Y-m-d H:i:s'))
                    ->first();
        if (!$row) return null;
        $this->update($row['id'], ['used_at' => date('Y-m-d H:i:s')]);
        return $row;
    }

    public function purgeOld(): void
    {
        $this->where('expires_at <', date('Y-m-d H:i:s', time() - 3600))->delete();
    }
}