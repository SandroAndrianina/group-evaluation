<?php

declare(strict_types=1);

namespace Modules\Attendance\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateChallenges extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'         => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'auto_increment' => true],
            'challenge'  => ['type' => 'VARCHAR', 'constraint' => 100],
            'session_id' => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true],
            'student_id' => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'null' => true],
            'purpose'    => ['type' => 'VARCHAR', 'constraint' => 10],
            'expires_at' => ['type' => 'DATETIME'],
            'used_at'    => ['type' => 'DATETIME', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('challenge');
        $this->forge->addKey('expires_at');
        $this->forge->addForeignKey('session_id', 'attendance_sessions', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('student_id', 'students', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('challenges');
    }

    public function down(): void
    {
        $this->forge->dropTable('challenges', true);
    }
}