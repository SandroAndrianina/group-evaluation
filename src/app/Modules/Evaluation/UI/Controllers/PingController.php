<?php

declare(strict_types=1);

namespace Modules\Evaluation\UI\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\ResponseInterface;

class PingController extends BaseController
{
    public function index(): ResponseInterface
    {
        return $this->response->setJSON(['module' => 'Evaluation', 'status' => 'ok']);
    }
}