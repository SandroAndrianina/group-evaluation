<?php

declare(strict_types=1);

namespace Modules\Evaluation\Infrastructure\Import;

use CodeIgniter\Database\BaseConnection;
use DomainException;
use Modules\Evaluation\Domain\Entity\Evaluation;
use Modules\Evaluation\Domain\ValueObject\Score;
use RuntimeException;
use Throwable;

final class SheetImporter
{
    /** @var array<string, int> */
    private array $groupIds = [];

    private int $createdGroups = 0;

    public function __construct(
        private readonly BaseConnection $db,
        private readonly CsvReader $reader,
    ) {
    }

    /**
     * @return array{groups:int, members:int, evaluations:int, skipped:int, details:list<string>}
     */
    public function import(string $groupsCsv, string $dataCsv, bool $dryRun = false): array
    {
        $memberRows = $this->reader->read($groupsCsv, 3);
        $evalRows   = $this->reader->read($dataCsv, 9);

        $this->groupIds      = [];
        $this->createdGroups = 0;
        $members             = 0;
        $evaluations         = 0;
        $skipped             = 0;
        $details             = [];

        $this->db->transBegin();

        try {
            foreach ($memberRows as $i => [$group, $matricule, $firstName]) {
                if ($group === '' || $matricule === '' || $firstName === '') {
                    throw new RuntimeException(sprintf(
                        'Groupes, membre n°%d : groupe, matricule et prénom sont obligatoires.',
                        $i + 1,
                    ));
                }

                $groupId = $this->groupId($group);

                if ($this->exists('members', ['group_id' => $groupId, 'matricule' => $matricule])) {
                    continue;
                }

                $now = date('Y-m-d H:i:s');
                $this->db->table('members')->insert([
                    'group_id'   => $groupId,
                    'matricule'  => $matricule,
                    'first_name' => $firstName,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
                $members++;
            }

            foreach ($evalRows as $i => $row) {
                [$evaluator, $evaluated, $p, $s, $m, $o, $teacher, , $remark] = $row;
                $n = $i + 1;

                if ($evaluator === '' || $evaluated === '') {
                    throw new RuntimeException(sprintf('Données, évaluation n°%d : les deux groupes sont obligatoires.', $n));
                }

                $evaluatorId = $this->groupId($evaluator);
                $evaluatedId = $this->groupId($evaluated);

                try {
                    $evaluation = Evaluation::create(
                        $evaluatorId,
                        $evaluatedId,
                        Score::of($p),
                        Score::of($s),
                        Score::of($m),
                        Score::of($o),
                        $teacher === '' ? null : Score::of($teacher),
                        $remark,
                    );
                } catch (DomainException $e) {
                    throw new RuntimeException(sprintf('Données, évaluation n°%d : %s', $n, $e->getMessage()), 0, $e);
                }

                if ($this->exists('evaluations', [
                    'evaluator_group_id' => $evaluatorId,
                    'evaluated_group_id' => $evaluatedId,
                ])) {
                    $skipped++;

                    continue;
                }

                $now = date('Y-m-d H:i:s');
                $this->db->table('evaluations')->insert([
                    'evaluator_group_id' => $evaluatorId,
                    'evaluated_group_id' => $evaluatedId,
                    'score_presentation' => $evaluation->presentation()->value(),
                    'score_swot'         => $evaluation->swot()->value(),
                    'score_marketing'    => $evaluation->marketing()->value(),
                    'score_oral'         => $evaluation->oral()->value(),
                    'score_teacher'      => $evaluation->teacher()->value(),
                    'remark'             => $evaluation->remark(),
                    'created_at'         => $now,
                    'updated_at'         => $now,
                ]);
                $evaluations++;
                $details[] = sprintf(
                    '%s → %s : %s/10',
                    $evaluator,
                    $evaluated,
                    number_format($evaluation->total(), 2, ',', ''),
                );
            }
        } catch (Throwable $e) {
            $this->db->transRollback();

            throw $e;
        }

        if ($dryRun) {
            $this->db->transRollback();
        } else {
            $this->db->transCommit();
        }

        return [
            'groups'      => $this->createdGroups,
            'members'     => $members,
            'evaluations' => $evaluations,
            'skipped'     => $skipped,
            'details'     => $details,
        ];
    }

    private function groupId(string $name): int
    {
        if (isset($this->groupIds[$name])) {
            return $this->groupIds[$name];
        }

        $row = $this->db->table('student_groups')->select('id')->where('name', $name)->get()->getRowArray();

        if ($row !== null) {
            return $this->groupIds[$name] = (int) $row['id'];
        }

        $now = date('Y-m-d H:i:s');
        $next = (int) ($this->db->table('student_groups')->selectMax('id', 'max')->get()->getRowArray()['max'] ?? 0) + 1;
        $this->db->table('student_groups')->insert([
            'code'       => 'G' . $next,
            'name'       => $name,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    /**
     * @param array<string, int|string> $where
     */
    private function exists(string $table, array $where): bool
    {
        return $this->db->table($table)->where($where)->countAllResults() > 0;
    }
}