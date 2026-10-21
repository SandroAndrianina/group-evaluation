<?php

declare(strict_types=1);

namespace Modules\Attendance\Controllers;

use App\Controllers\BaseController;
use Modules\Attendance\Libraries\AttendanceRecorder;
use Modules\Attendance\Libraries\WebAuthnSupport;
use Modules\Attendance\Models\ChallengeModel;
use Modules\Attendance\Models\SessionModel;
use Modules\Attendance\Models\StudentModel;
use Throwable;

final class QrController extends BaseController
{
    public function token()
    {
        $session = (new SessionModel())->where('status', 'open')->orderBy('id', 'DESC')->first();
        if (!$session) {
            return $this->response->setStatusCode(409)->setJSON(['error' => 'Aucun appel ouvert.']);
        }

        $challenges = new ChallengeModel();
        $challenges->purgeOld();
        return $this->response->setJSON($challenges->issueQrToken((int) $session['id'], (string) $session['secret']));
    }

    /** QR destination: render a real student page instead of raw JSON. */
    public function verify()
    {
        $token = trim((string) $this->request->getGet('token'));
        $challenges = new ChallengeModel();
        $qr = $token !== '' ? $challenges->findValidQr($token) : null;
        if (!$qr) {
            return view('Modules\Attendance\Views\student\expired');
        }
        $session = (new SessionModel())->find((int) $qr['session_id']);
        if (!$session || $session['status'] !== 'open') {
            return view('Modules\Attendance\Views\student\expired');
        }

        $scanTicket = $challenges->issueScanTicket((int) $session['id']);
        return view('Modules\Attendance\Views\student\scan', [
            'token'    => $scanTicket['token'],
            'session'  => $session,
            'students' => (new StudentModel())->select('id, matricule, name')->orderBy('name', 'ASC')->findAll(),
        ]);
    }

    public function registerOptions()
    {
        $input = $this->input();
        $token = trim((string) ($input['token'] ?? ''));
        $studentId = (int) ($input['student_id'] ?? 0);
        $matricule = trim((string) ($input['matricule'] ?? ''));
        $code = strtoupper(trim((string) ($input['enrollment_code'] ?? '')));

        $context = $this->qrContext($token);
        if (isset($context['error'])) {
            return $context['error'];
        }
        if ($studentId < 1 || $matricule === '' || $code === '') {
            return $this->jsonError(422, 'Choisis ton nom et saisis ton matricule et ton code individuel.');
        }

        try {
            $bucket = 'attendance-enrollment:' . hash('sha256', (string) $this->request->getIPAddress() . ':' . $studentId);
            if (service('throttler')->check($bucket, 5, 60) === false) {
                return $this->jsonError(429, 'Trop de tentatives pour cette inscription. Attends une minute avant de réessayer.');
            }
        } catch (Throwable $e) {
            // The enrollment code has high entropy; keep the registration flow available if no cache handler is configured.
            log_message('warning', 'Attendance enrollment throttler unavailable: {message}', ['message' => $e->getMessage()]);
        }

        $student = (new StudentModel())->find($studentId);
        if (!$student || !hash_equals((string) $student['matricule'], $matricule)) {
            return $this->jsonError(403, 'Matricule ou étudiant incorrect.');
        }
        if (db_connect()->table('passkeys')->where('student_id', $studentId)->countAllResults() > 0) {
            return $this->jsonError(409, 'Une passkey existe déjà pour ce nom. Utilise « Je suis déjà inscrit ».');
        }
        $codeRow = db_connect()->table('attendance_enrollment_codes')->where('student_id', $studentId)->where('used_at', null)->get()->getRowArray();
        if (!$codeRow || !password_verify($code, (string) $codeRow['code_hash'])) {
            return $this->jsonError(403, 'Code d’inscription invalide ou déjà utilisé. Demande un nouveau code au chef de classe.');
        }

        try {
            $wa = WebAuthnSupport::server();
            $options = WebAuthnSupport::jsonOptions($wa->getCreateArgs(
                (string) $studentId,
                (string) $student['matricule'],
                (string) $student['name'],
                60,
                true,
                true,
                false,
                []
            ));
            $challenges = new ChallengeModel();
            $challenges->issueWebAuthnChallenge((int) $context['session']['id'], $studentId, 'register', (string) $options['attendanceChallenge']);
            $options['csrf'] = csrf_hash();
            return $this->response->setJSON($options);
        } catch (Throwable $e) {
            log_message('error', 'Attendance registration options failed: {message}', ['message' => $e->getMessage()]);
            return $this->jsonError(500, 'Impossible de préparer la biométrie. Vérifie la configuration HTTPS et réessaie.');
        }
    }

    public function registerFinish()
    {
        $input = $this->input();
        $studentId = (int) ($input['student_id'] ?? 0);
        $code = strtoupper(trim((string) ($input['enrollment_code'] ?? '')));
        $challenge = trim((string) ($input['attendanceChallenge'] ?? ''));
        $response = $input['response'] ?? [];
        if ($studentId < 1 || $challenge === '' || $code === '' || !is_array($response)) {
            return $this->jsonError(422, 'Réponse de création de passkey incomplète.');
        }

        $challenges = new ChallengeModel();
        $challengeRow = $challenges->consumeWebAuthn($challenge, 'register', $studentId);
        if (!$challengeRow) {
            return $this->jsonError(410, 'Cette demande a expiré ou a déjà été utilisée. Scanne le QR actuel.');
        }

        $session = (new SessionModel())->find((int) $challengeRow['session_id']);
        if (!$session || $session['status'] !== 'open') {
            return $this->jsonError(409, 'L’appel est fermé.');
        }
        $clientDataJson = $this->binaryField($response, 'clientDataJSON');
        $attestationObject = $this->binaryField($response, 'attestationObject');
        if ($clientDataJson === null || $attestationObject === null) {
            return $this->jsonError(422, 'Données biométriques incomplètes.');
        }

        try {
            WebAuthnSupport::assertClientOrigin($clientDataJson);
            $wa = WebAuthnSupport::server();
            $created = $wa->processCreate(
                $clientDataJson,
                $attestationObject,
                WebAuthnSupport::decode($challenge),
                true,
                true,
                false,
                false
            );
        } catch (Throwable $e) {
            log_message('warning', 'Attendance passkey registration rejected: {message}', ['message' => $e->getMessage()]);
            return $this->jsonError(403, 'La vérification biométrique a échoué. Scanne un nouveau QR puis réessaie.');
        }

        $db = db_connect();
        $db->transBegin();
        try {
            $student = (new StudentModel())->find($studentId);
            $codeRow = $db->table('attendance_enrollment_codes')->where('student_id', $studentId)->where('used_at', null)->get()->getRowArray();
            if (!$student || !$codeRow || !password_verify($code, (string) $codeRow['code_hash'])) {
                throw new \RuntimeException('Code d’inscription invalide ou déjà utilisé.');
            }
            if ($db->table('passkeys')->where('student_id', $studentId)->countAllResults() > 0) {
                throw new \RuntimeException('Une passkey est déjà enregistrée.');
            }
            $db->table('attendance_enrollment_codes')->where('id', $codeRow['id'])->where('used_at', null)->update(['used_at' => gmdate('Y-m-d H:i:s')]);
            if ($db->affectedRows() !== 1) {
                throw new \RuntimeException('Ce code a déjà été utilisé.');
            }

            $credentialId = ChallengeModel::base64UrlEncode((string) $created->credentialId);
            $db->table('passkeys')->insert([
                'student_id'    => $studentId,
                'credential_id' => $credentialId,
                'public_key'    => (string) $created->credentialPublicKey,
                'sign_count'    => (int) ($created->signatureCounter ?? 0),
                'last_used_at'  => gmdate('Y-m-d H:i:s'),
                'created_at'    => gmdate('Y-m-d H:i:s'),
                'updated_at'    => gmdate('Y-m-d H:i:s'),
            ]);
            $marked = AttendanceRecorder::mark((int) $session['id'], $studentId, $this->request);
            if ($db->transStatus() === false) {
                throw new \RuntimeException('Transaction de présence échouée.');
            }
            $db->transCommit();
            return $this->response->setJSON(['ok' => true, 'message' => 'Passkey créée : présence enregistrée.', 'attendance' => $marked, 'csrf' => csrf_hash()]);
        } catch (Throwable $e) {
            $db->transRollback();
            log_message('warning', 'Attendance registration transaction rolled back: {message}', ['message' => $e->getMessage()]);
            $knownMessages = [
                'Code d’inscription invalide ou déjà utilisé.',
                'Une passkey est déjà enregistrée.',
                'Ce code a déjà été utilisé.',
            ];
            $safeMessage = in_array($e->getMessage(), $knownMessages, true)
                ? $e->getMessage()
                : 'Impossible de terminer l’inscription. Vérifie le code et réessaie avec un QR récent.';
            return $this->jsonError(409, $safeMessage);
        }
    }

    public function loginOptions()
    {
        $input = $this->input();
        $context = $this->qrContext(trim((string) ($input['token'] ?? '')));
        if (isset($context['error'])) {
            return $context['error'];
        }
        $studentId = (int) ($input['student_id'] ?? 0);
        $student = (new StudentModel())->find($studentId);
        if (!$student) {
            return $this->jsonError(404, 'Étudiant introuvable.');
        }
        $keys = db_connect()->table('passkeys')->where('student_id', $studentId)->get()->getResultArray();
        if ($keys === []) {
            return $this->jsonError(404, 'Aucune passkey enregistrée pour ce nom. Utilise « Première inscription ».');
        }

        try {
            $ids = [];
            foreach ($keys as $key) {
                $rawId = ChallengeModel::base64UrlDecode((string) $key['credential_id']);
                if ($rawId !== null) {
                    $ids[] = $rawId;
                }
            }
            $wa = WebAuthnSupport::server();
            $options = WebAuthnSupport::jsonOptions($wa->getGetArgs($ids, 60, true, true, true, true, true, true));
            (new ChallengeModel())->issueWebAuthnChallenge((int) $context['session']['id'], $studentId, 'login', (string) $options['attendanceChallenge']);
            $options['csrf'] = csrf_hash();
            return $this->response->setJSON($options);
        } catch (Throwable $e) {
            log_message('error', 'Attendance login options failed: {message}', ['message' => $e->getMessage()]);
            return $this->jsonError(500, 'Impossible de préparer la validation biométrique.');
        }
    }

    public function loginFinish()
    {
        $input = $this->input();
        $studentId = (int) ($input['student_id'] ?? 0);
        $challenge = trim((string) ($input['attendanceChallenge'] ?? ''));
        $credentialId = trim((string) ($input['credentialId'] ?? ''));
        $response = $input['response'] ?? [];
        if ($studentId < 1 || $challenge === '' || $credentialId === '' || !is_array($response)) {
            return $this->jsonError(422, 'Réponse de validation incomplète.');
        }

        $challengeRow = (new ChallengeModel())->consumeWebAuthn($challenge, 'login', $studentId);
        if (!$challengeRow) {
            return $this->jsonError(410, 'Cette validation a expiré ou a déjà été utilisée. Scanne le QR actuel.');
        }
        $session = (new SessionModel())->find((int) $challengeRow['session_id']);
        if (!$session || $session['status'] !== 'open') {
            return $this->jsonError(409, 'L’appel est fermé.');
        }

        $key = db_connect()->table('passkeys')->where('student_id', $studentId)->where('credential_id', $credentialId)->get()->getRowArray();
        if (!$key) {
            return $this->jsonError(403, 'Passkey inconnue pour cet étudiant.');
        }
        $clientDataJson = $this->binaryField($response, 'clientDataJSON');
        $authenticatorData = $this->binaryField($response, 'authenticatorData');
        $signature = $this->binaryField($response, 'signature');
        if ($clientDataJson === null || $authenticatorData === null || $signature === null) {
            return $this->jsonError(422, 'Données biométriques incomplètes.');
        }

        try {
            WebAuthnSupport::assertClientOrigin($clientDataJson);
            $wa = WebAuthnSupport::server();
            $valid = $wa->processGet(
                $clientDataJson,
                $authenticatorData,
                $signature,
                (string) $key['public_key'],
                WebAuthnSupport::decode($challenge),
                (int) $key['sign_count'],
                true,
                true
            );
            if (!$valid) {
                return $this->jsonError(403, 'Signature biométrique invalide.');
            }
        } catch (Throwable $e) {
            log_message('warning', 'Attendance passkey login rejected: {message}', ['message' => $e->getMessage()]);
            return $this->jsonError(403, 'Authentification biométrique refusée. Scanne le QR actuel puis réessaie.');
        }

        $db = db_connect();
        $db->transBegin();
        try {
            $marked = AttendanceRecorder::mark((int) $session['id'], $studentId, $this->request);
            $counter = $wa->getSignatureCounter();
            $updates = ['last_used_at' => gmdate('Y-m-d H:i:s'), 'updated_at' => gmdate('Y-m-d H:i:s')];
            if (is_int($counter) && $counter >= (int) $key['sign_count']) {
                $updates['sign_count'] = $counter;
            }
            $db->table('passkeys')->where('id', $key['id'])->update($updates);
            if ($db->transStatus() === false) {
                throw new \RuntimeException('Transaction de présence échouée.');
            }
            $db->transCommit();
            $message = $marked['already_present'] ? 'Tu es déjà marqué présent pour cet appel.' : 'Présence enregistrée.';
            return $this->response->setJSON(['ok' => true, 'message' => $message, 'attendance' => $marked, 'csrf' => csrf_hash()]);
        } catch (Throwable $e) {
            $db->transRollback();
            log_message('error', 'Attendance check-in failed: {message}', ['message' => $e->getMessage()]);
            return $this->jsonError(409, 'L’appel a peut-être été fermé. Vérifie auprès du chef de classe.');
        }
    }

    private function input(): array
    {
        $json = $this->request->getJSON(true);
        return is_array($json) ? $json : $this->request->getPost();
    }

    private function qrContext(string $token): array
    {
        if ($token === '') {
            return ['error' => $this->jsonError(400, 'QR absent. Scanne le QR affiché en ce moment.')];
        }
        $qr = (new ChallengeModel())->findValidScanTicket($token);
        if (!$qr) {
            return ['error' => $this->jsonError(410, 'La session de scan a expiré. Scanne le QR actuel.')];
        }
        $session = (new SessionModel())->find((int) $qr['session_id']);
        if (!$session || $session['status'] !== 'open') {
            return ['error' => $this->jsonError(409, 'L’appel est fermé.')];
        }
        return ['qr' => $qr, 'session' => $session];
    }

    private function binaryField(array $response, string $field): ?string
    {
        $value = $response[$field] ?? null;
        if (!is_string($value) || $value === '') {
            return null;
        }
        return ChallengeModel::base64UrlDecode($value);
    }

    private function jsonError(int $status, string $message)
    {
        return $this->response->setStatusCode($status)->setJSON(['ok' => false, 'message' => $message, 'csrf' => csrf_hash()]);
    }
}
