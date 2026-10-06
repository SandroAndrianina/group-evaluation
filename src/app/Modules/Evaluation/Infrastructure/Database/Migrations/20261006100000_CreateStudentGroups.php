<?php

declare(strict_types=1);

namespace Modules\Evaluation\Infrastructure\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateStudentGroups extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'         => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'auto_increment' => true],
            'name'       => ['type' => 'VARCHAR', 'constraint' => 100],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('name');
        $this->forge->createTable('student_groups');
    }

    public function down(): void
    {
        $this->forge->dropTable('student_groups', true);
    }
}