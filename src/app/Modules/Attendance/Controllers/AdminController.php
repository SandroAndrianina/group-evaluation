<?php

declare(strict_types=1);

namespace Modules\Attendance\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\ResponseInterface;
use Modules\Attendance\Models\SessionModel;
use Modules\Attendance\Models\StudentModel;
use Throwable;

final class AdminController extends BaseController
{
    public function loginForm(): ResponseInterface|string
    {
        if (session()->get('attendance_admin') === true) {
            return redirect()->to(site_url('attendance/admin'));
        }
        return view('Modules\Attendance\Views\admin\login', ['error' => session()->getFlashdata('error')]);
    }

    public function login(): ResponseInterface
    {
        $expected = (string) env('ATTENDANCE_ADMIN_PASSWORD', '');
        if ($expected === '') {
            return redirect()->to(site_url('attendance/admin/login'))
                ->with('error', 'ATTENDANCE_ADMIN_PASSWORD manque dans le fichier .env.');
        }

        try {
            $throttler = service('throttler');
            if (!$throttler->check('attendance-admin-login:' . sha1((string) $this->request->getIPAddress()), 5, 60)) {
                return redirect()->to(site_url('attendance/admin/login'))->with('error', 'Trop de tentatives. Attends une minute avant de réessayer.');
            }
        } catch (Throwable $e) {
            // Login remains usable if the optional throttler backend isn't available.
            log_message('warning', 'Attendance login throttler unavailable: {message}', ['message' => $e->getMessage()]);
        }

        if (!hash_equals($expected, (string) $this->request->getPost('password'))) {
            return redirect()->to(site_url('attendance/admin/login'))->with('error', 'Mot de passe incorrect.');
        }

        session()->regenerate(true);
        session()->set('attendance_admin', true);
        return redirect()->to(site_url('attendance/admin'));
    }

    public function logout(): ResponseInterface
    {
        session()->remove('attendance_admin');
        session()->regenerate(true);
        return redirect()->to(site_url('attendance/admin/login'));
    }

    public function dashboard(): string
    {
        $sessions = new SessionModel();
        $open = $sessions->where('status', 'open')->orderBy('id', 'DESC')->first();
        return view('Modules\Attendance\Views\admin\dashboard', [
            'open'         => $open,
            'total'        => (new StudentModel())->countAllResults(),
            'defaultLabel' => 'Appel du ' . date('d/m/Y'),
            'error'        => session()->getFlashdata('error'),
            'history'      => (new SessionModel())->orderBy('id', 'DESC')->findAll(8),
        ]);
    }

    public function open(): ResponseInterface
    {
        $db = db_connect();
        $sessions = new SessionModel();
        $db->transBegin();
        try {
            // MySQL transaction and recheck reduce the chance of opening two concurrent sessions.
            $db->query('SELECT id FROM attendance_sessions WHERE status = ? FOR UPDATE', ['open']);
            if ($sessions->where('status', 'open')->countAllResults() > 0) {
                $db->transRollback();
                return redirect()->to(site_url('attendance/admin'))->with('error', 'Un appel est déjà ouvert.');
            }

            $label = trim((string) $this->request->getPost('label'));
            if ($label === '' || mb_strlen($label) > 150) {
                $db->transRollback();
                return redirect()->to(site_url('attendance/admin'))->with('error', 'Donne un titre de 150 caractères maximum.');
            }
            $sessions->insert([
                'label'     => $label,
                'status'    => 'open',
                'secret'    => bin2hex(random_bytes(32)),
                'opened_at' => gmdate('Y-m-d H:i:s'),
                'closed_at' => null,
            ]);
            $db->transCommit();
            return redirect()->to(site_url('attendance/admin'));
        } catch (Throwable $e) {
            $db->transRollback();
            log_message('error', 'Attendance session open failed: {message}', ['message' => $e->getMessage()]);
            return redirect()->to(site_url('attendance/admin'))->with('error', 'Impossible d’ouvrir l’appel. Vérifie la base de données.');
        }
    }

    public function close(int $id): ResponseInterface
    {
        $sessions = new SessionModel();
        $session = $sessions->find($id);
        if ($session !== null && $session['status'] === 'open') {
            $sessions->update($id, ['status' => 'closed', 'closed_at' => gmdate('Y-m-d H:i:s')]);
        }
        return redirect()->to(site_url('attendance/admin'));
    }

    public function present(int $id): ResponseInterface
    {
        $session = (new SessionModel())->find($id);
        if (!$session) {
            return $this->response->setStatusCode(404)->setJSON(['error' => 'Session introuvable.']);
        }
        $present = db_connect()->table('attendances a')
            ->select('s.matricule, s.name, a.marked_at, a.flagged, a.delay_seconds')
            ->join('students s', 's.id = a.student_id')
            ->where('a.session_id', $id)
            ->orderBy('a.marked_at', 'DESC')
            ->get()->getResultArray();
        return $this->response->setHeader('Cache-Control', 'no-store')->setJSON([
            'count'   => count($present),
            'total'   => (new StudentModel())->countAllResults(),
            'present' => $present,
        ]);
    }

    public function chef(): string
    {
        $open = (new SessionModel())->where('status', 'open')->orderBy('id', 'DESC')->first();
        return view('Modules\Attendance\Views\admin\chef', ['open' => $open]);
    }

    public function history(): string
    {
        $sessions = (new SessionModel())->orderBy('id', 'DESC')->findAll();
        $rows = [];
        foreach ($sessions as $session) {
            $count = db_connect()->table('attendances')->where('session_id', $session['id'])->countAllResults();
            $rows[] = $session + ['present_count' => $count];
        }
        return view('Modules\Attendance\Views\admin\history', ['sessions' => $rows]);
    }

    public function export(int $id)
    {
        $session = (new SessionModel())->find($id);
        if (!$session) {
            return redirect()->to(site_url('attendance/admin/history'))->with('error', 'Appel introuvable.');
        }
        $db = db_connect();
        $students = $db->table('students s')
            ->select('s.matricule, s.name, a.marked_at, a.ip, a.delay_seconds, a.flagged')
            ->join('attendances a', 'a.student_id = s.id AND a.session_id = ' . (int) $id, 'left', false)
            ->orderBy('s.matricule', 'ASC')->get()->getResultArray();

        $filename = 'appel-' . $id . '-' . preg_replace('/[^A-Za-z0-9_-]+/', '-', (string) $session['label']) . '.csv';
        $stream = fopen('php://temp', 'w+');
        fwrite($stream, "\xEF\xBB\xBF");
        fputcsv($stream, ['matricule', 'nom', 'statut', 'heure_locale', 'ip', 'delai_secondes', 'signale'], ',', '"', '');
        foreach ($students as $row) {
            $localTime = '';
            if (!empty($row['marked_at'])) {
                $dt = new \DateTimeImmutable((string) $row['marked_at'], new \DateTimeZone('UTC'));
                $localTime = $dt->setTimezone(new \DateTimeZone('Indian/Antananarivo'))->format('Y-m-d H:i:s');
            }
            fputcsv($stream, [
                $row['matricule'],
                $row['name'],
                $row['marked_at'] === null ? 'ABSENT' : 'PRESENT',
                $localTime,
                $row['ip'] ?? '',
                $row['delay_seconds'] ?? '',
                isset($row['flagged']) && (int) $row['flagged'] === 1 ? 'OUI' : 'NON',
            ], ',', '"', '');
        }
        rewind($stream);
        $csv = stream_get_contents($stream);
        fclose($stream);
        return $this->response->setHeader('Content-Type', 'text/csv; charset=UTF-8')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->setHeader('Cache-Control', 'no-store')
            ->setBody($csv);
    }

    public function spotCheck(int $id): ResponseInterface
    {
        $session = (new SessionModel())->find($id);
        if (!$session) {
            return $this->response->setStatusCode(404)->setJSON(['error' => 'Appel introuvable.']);
        }
        $rows = db_connect()->table('attendances a')
            ->select('s.matricule, s.name, a.marked_at, a.ip')
            ->join('students s', 's.id = a.student_id')
            ->where('a.session_id', $id)
            ->orderBy('a.student_id', 'ASC')->get()->getResultArray();
        shuffle($rows);
        return $this->response->setJSON(['session' => $session['label'], 'selected' => array_slice($rows, 0, 5), 'available' => count($rows), 'csrf' => csrf_hash()]);
    }
}
