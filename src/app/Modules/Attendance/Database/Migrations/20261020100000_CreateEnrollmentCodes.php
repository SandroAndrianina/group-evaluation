<?php

declare(strict_types=1);

namespace Modules\Attendance\Database\Migrations;

use CodeIgniter\Database\Migration;

final class CreateEnrollmentCodes extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'         => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'auto_increment' => true],
            'student_id' => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true],
            'code_hash'  => ['type' => 'VARCHAR', 'constraint' => 255],
            'issued_at'  => ['type' => 'DATETIME'],
            'used_at'    => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('student_id');
        $this->forge->addForeignKey('student_id', 'students', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('attendance_enrollment_codes');
    }

    public function down(): void
    {
        $this->forge->dropTable('attendance_enrollment_codes', true);
    }
}
