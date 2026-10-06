<?php

declare(strict_types=1);

namespace Modules\Evaluation\Infrastructure\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateEvaluations extends Migration
{
    public function up(): void
    {
        $score = ['type' => 'DECIMAL', 'constraint' => '3,2', 'default' => 0];

        $this->forge->addField([
            'id'                 => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'auto_increment' => true],
            'evaluator_group_id' => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true],
            'evaluated_group_id' => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true],
            'score_presentation' => $score,
            'score_swot'         => $score,
            'score_marketing'    => $score,
            'score_oral'         => $score,
            'score_teacher'      => $score,
            'remark'             => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'created_at'         => ['type' => 'DATETIME', 'null' => true],
            'updated_at'         => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('evaluator_group_id', 'student_groups', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('evaluated_group_id', 'student_groups', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->createTable('evaluations');

        $this->db->query(
            'ALTER TABLE `evaluations` ADD CONSTRAINT `chk_scores_range` CHECK (
                `score_presentation` BETWEEN 0 AND 2 AND `score_swot` BETWEEN 0 AND 2 AND
                `score_marketing` BETWEEN 0 AND 2 AND `score_oral` BETWEEN 0 AND 2 AND
                `score_teacher` BETWEEN 0 AND 2)'
        );
    }

    public function down(): void
    {
        $this->forge->dropTable('evaluations', true);
    }
}