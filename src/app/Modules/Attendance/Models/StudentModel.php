<?php

declare(strict_types=1);

namespace Modules\Attendance\Models;

use CodeIgniter\Model;

final class StudentModel extends Model
{
    protected $table         = 'students';
    protected $primaryKey    = 'id';
    protected $allowedFields = ['matricule', 'name'];
    protected $useTimestamps = true;
}