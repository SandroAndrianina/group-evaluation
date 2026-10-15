<?php

declare(strict_types=1);

namespace Modules\Attendance\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePasskeys extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'            => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'auto_increment' => true],
            'student_id'    => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true],
            'credential_id' => ['type' => 'VARCHAR', 'constraint' => 255],
            'public_key'    => ['type' => 'TEXT'],
            'sign_count'    => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'default' => 0],
            'last_used_at'  => ['type' => 'DATETIME', 'null' => true],
            'created_at'    => ['type' => 'DATETIME', 'null' => true],
            'updated_at'    => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('student_id');
        $this->forge->addUniqueKey('credential_id');
        $this->forge->addForeignKey('student_id', 'students', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('passkeys');
    }

    public function down(): void
    {
        $this->forge->dropTable('passkeys', true);
    }
}