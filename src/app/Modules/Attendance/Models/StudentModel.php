<?php

declare(strict_types=1);

namespace Modules\Attendance\Models;

use CodeIgniter\Model;

final class StudentModel extends Model
{
    protected $table         = 'students';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $allowedFields = ['matricule', 'name', 'created_at', 'updated_at'];
    protected $useTimestamps = false;
}
