<?php

declare(strict_types=1);

namespace Modules\Attendance\Models;

use CodeIgniter\Model;

final class SessionModel extends Model
{
    protected $table         = 'attendance_sessions';
    protected $primaryKey    = 'id';
    protected $allowedFields = ['label', 'status', 'secret', 'opened_at', 'closed_at'];
    protected $useTimestamps = true;
}