<?php
declare(strict_types=1);

namespace Modules\Attendance\Controllers;

use CodeIgniter\Controller;
use Modules\Attendance\Models\ChallengeModel;
use Modules\Attendance\Models\SessionModel;
use Modules\Attendance\Models\StudentModel;

class QrController extends Controller
{
    public function token()
    {
        $sessionId = session('active_session_id');
        if (!$sessionId) {
            return $this->response->setStatusCode(409)->setJSON(['error' => 'no_session']);
        }

        $model = new ChallengeModel();
        $model->purgeOld();
        return $this->response->setJSON($model->issueQrToken((int)$sessionId));
    }

    public function verify()
    {
        $token = $this->request->getGet('token');
        if (!$token) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'missing_token']);
        }

        $model = new ChallengeModel();
        $qrRow = $model->findValidQr($token);
        if (!$qrRow) {
            return $this->response->setStatusCode(410)->setJSON(['error' => 'expired']);
        }

        $session = (new SessionModel())->find($qrRow['session_id']);
        if (!$session || $session['status'] !== 'open') {
            return $this->response->setStatusCode(409)->setJSON(['error' => 'closed']);
        }

        return $this->response->setJSON([
            'session_id' => (int)$session['id'],
            'students'   => (new StudentModel())->select('id,matricule,name')->findAll(),
        ]);
    }
}