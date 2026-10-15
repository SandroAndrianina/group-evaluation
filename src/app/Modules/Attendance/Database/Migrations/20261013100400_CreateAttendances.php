<?php

declare(strict_types=1);

namespace Modules\Attendance\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateAttendances extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'            => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'auto_increment' => true],
            'session_id'    => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true],
            'student_id'    => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true],
            'marked_at'     => ['type' => 'DATETIME'],
            'ip'            => ['type' => 'VARCHAR', 'constraint' => 45, 'null' => true],
            'user_agent'    => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'delay_seconds' => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'null' => true],
            'flagged'       => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'created_at'    => ['type' => 'DATETIME', 'null' => true],
            'updated_at'    => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['session_id', 'student_id']);
        $this->forge->addForeignKey('session_id', 'attendance_sessions', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('student_id', 'students', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->createTable('attendances');
    }

    public function down(): void
    {
        $this->forge->dropTable('attendances', true);
    }
}