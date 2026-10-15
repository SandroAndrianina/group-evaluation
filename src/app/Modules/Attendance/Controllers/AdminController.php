<?php

declare(strict_types=1);

namespace Modules\Attendance\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\ResponseInterface;
use Modules\Attendance\Models\SessionModel;
use Modules\Attendance\Models\StudentModel;

final class AdminController extends BaseController
{
    public function loginForm(): ResponseInterface|string
    {
        if (session()->get('attendance_admin') === true) {
            return redirect()->to('attendance/admin');
        }

        return view('Modules\Attendance\Views\admin\login', ['error' => session()->getFlashdata('error')]);
    }

    public function login(): ResponseInterface
    {
        $expected = (string) env('ATTENDANCE_ADMIN_PASSWORD', '');

        if ($expected === '') {
            return redirect()->to('attendance/admin/login')
                ->with('error', 'ATTENDANCE_ADMIN_PASSWORD manque dans le fichier .env.');
        }

        if (! hash_equals($expected, (string) $this->request->getPost('password'))) {
            return redirect()->to('attendance/admin/login')->with('error', 'Mot de passe incorrect.');
        }

        session()->regenerate();
        session()->set('attendance_admin', true);

        return redirect()->to('attendance/admin');
    }

    public function logout(): ResponseInterface
    {
        session()->remove('attendance_admin');

        return redirect()->to('attendance/admin/login');
    }

    public function dashboard(): string
    {
        $open = (new SessionModel())->where('status', 'open')->orderBy('id', 'DESC')->first();

        return view('Modules\Attendance\Views\admin\dashboard', [
            'open'         => $open,
            'total'        => (new StudentModel())->countAllResults(),
            'defaultLabel' => 'Appel du ' . date('d/m/Y'),
            'error'        => session()->getFlashdata('error'),
        ]);
    }

    public function open(): ResponseInterface
    {
        $sessions = new SessionModel();

        if ($sessions->where('status', 'open')->countAllResults() > 0) {
            return redirect()->to('attendance/admin')->with('error', 'Un appel est déjà ouvert.');
        }

        $label = trim((string) $this->request->getPost('label'));
        if ($label === '' || mb_strlen($label) > 150) {
            return redirect()->to('attendance/admin')->with('error', 'Donne un titre de 150 caractères maximum.');
        }

        $sessions->insert([
            'label'     => $label,
            'status'    => 'open',
            'secret'    => bin2hex(random_bytes(32)),
            'opened_at' => date('Y-m-d H:i:s'),
        ]);

        return redirect()->to('attendance/admin');
    }

    public function close(int $id): ResponseInterface
    {
        $sessions = new SessionModel();
        $session  = $sessions->find($id);

        if ($session !== null && $session['status'] === 'open') {
            $sessions->update($id, ['status' => 'closed', 'closed_at' => date('Y-m-d H:i:s')]);
        }

        return redirect()->to('attendance/admin');
    }

    public function present(int $id): ResponseInterface
    {
        $present = db_connect()->table('attendances a')
            ->select('s.matricule, s.name, a.marked_at, a.flagged')
            ->join('students s', 's.id = a.student_id')
            ->where('a.session_id', $id)
            ->orderBy('a.marked_at', 'DESC')
            ->get()->getResultArray();

        return $this->response->setJSON([
            'count'   => count($present),
            'total'   => (new StudentModel())->countAllResults(),
            'present' => $present,
        ]);
    }

    public function chef()
    {
        $sessionModel = new \Modules\Attendance\Models\SessionModel();
        $open = $sessionModel->where('status', 'open')->first();

        return view('Modules\Attendance\Views\admin\chef', [
            'open' => $open,
        ]);
    }
}