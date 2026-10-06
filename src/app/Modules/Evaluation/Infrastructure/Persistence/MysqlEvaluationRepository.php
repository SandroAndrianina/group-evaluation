<?php

declare(strict_types=1);

namespace Modules\Evaluation\Infrastructure\Persistence;

use CodeIgniter\Database\BaseConnection;
use Modules\Evaluation\Domain\Entity\Evaluation;
use Modules\Evaluation\Domain\Repository\EvaluationRepository;
use Modules\Evaluation\Domain\ValueObject\Score;

final class MysqlEvaluationRepository implements EvaluationRepository
{
    public function __construct(private readonly BaseConnection $db)
    {
    }

    public function save(Evaluation $evaluation): Evaluation
    {
        $now = date('Y-m-d H:i:s');

        $this->db->table('evaluations')->insert([
            'evaluator_group_id' => $evaluation->evaluatorGroupId(),
            'evaluated_group_id' => $evaluation->evaluatedGroupId(),
            'score_presentation' => $evaluation->presentation()->value(),
            'score_swot'         => $evaluation->swot()->value(),
            'score_marketing'    => $evaluation->marketing()->value(),
            'score_oral'         => $evaluation->oral()->value(),
            'score_teacher'      => $evaluation->teacher()->value(),
            'remark'             => $evaluation->remark(),
            'created_at'         => $now,
            'updated_at'         => $now,
        ]);

        return Evaluation::create(
            $evaluation->evaluatorGroupId(),
            $evaluation->evaluatedGroupId(),
            $evaluation->presentation(),
            $evaluation->swot(),
            $evaluation->marketing(),
            $evaluation->oral(),
            $evaluation->teacher(),
            $evaluation->remark(),
            (int) $this->db->insertID(),
        );
    }

    public function existsBetween(int $evaluatorGroupId, int $evaluatedGroupId): bool
    {
        return $this->db->table('evaluations')
            ->where('evaluator_group_id', $evaluatorGroupId)
            ->where('evaluated_group_id', $evaluatedGroupId)
            ->countAllResults() > 0;
    }

        public function allGroupedByEvaluated(): array
    {
        $rows = $this->db->table('evaluations')->get()->getResultArray();
        $grouped = [];
        foreach ($rows as $row) {
            $grouped[(int) $row['evaluated_group_id']][] = Evaluation::create(
                (int) $row['evaluator_group_id'],
                (int) $row['evaluated_group_id'],
                Score::of($row['score_presentation']),
                Score::of($row['score_swot']),
                Score::of($row['score_marketing']),
                Score::of($row['score_oral']),
                Score::of($row['score_teacher']),
                $row['remark'],
                (int) $row['id'],
            );
        }

        return $grouped;
    }

        public function find(int $id): ?Evaluation
    {
        $row = $this->db->table('evaluations')->where('id', $id)->get()->getRowArray();
        if ($row === null) {
            return null;
        }

        return Evaluation::create(
            (int) $row['evaluator_group_id'],
            (int) $row['evaluated_group_id'],
            Score::of($row['score_presentation']),
            Score::of($row['score_swot']),
            Score::of($row['score_marketing']),
            Score::of($row['score_oral']),
            Score::of($row['score_teacher']),
            $row['remark'],
            (int) $row['id'],
        );
    }

    public function updateTeacherScore(int $id, Score $score): void
    {
        $this->db->table('evaluations')->where('id', $id)->update([
            'score_teacher' => $score->value(),
            'updated_at'    => date('Y-m-d H:i:s'),
        ]);
    }
}