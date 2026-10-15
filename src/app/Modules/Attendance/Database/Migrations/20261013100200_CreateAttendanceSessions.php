<?php

declare(strict_types=1);

namespace Modules\Attendance\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateAttendanceSessions extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'         => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'auto_increment' => true],
            'label'      => ['type' => 'VARCHAR', 'constraint' => 150],
            'status'     => ['type' => 'VARCHAR', 'constraint' => 10, 'default' => 'open'],
            'secret'     => ['type' => 'VARCHAR', 'constraint' => 64],
            'opened_at'  => ['type' => 'DATETIME'],
            'closed_at'  => ['type' => 'DATETIME', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('status');
        $this->forge->createTable('attendance_sessions');
    }

    public function down(): void
    {
        $this->forge->dropTable('attendance_sessions', true);
    }
}