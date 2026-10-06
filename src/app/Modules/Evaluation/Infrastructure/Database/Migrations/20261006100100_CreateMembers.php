<?php

declare(strict_types=1);

namespace Modules\Evaluation\Infrastructure\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateMembers extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'         => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'auto_increment' => true],
            'group_id'   => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true],
            'matricule'  => ['type' => 'VARCHAR', 'constraint' => 10],
            'first_name' => ['type' => 'VARCHAR', 'constraint' => 100],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['group_id', 'matricule']);
        $this->forge->addForeignKey('group_id', 'student_groups', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('members');
    }

    public function down(): void
    {
        $this->forge->dropTable('members', true);
    }
}