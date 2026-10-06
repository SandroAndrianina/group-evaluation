<?php

declare(strict_types=1);

namespace Modules\Evaluation\UI\Controllers;

use App\Controllers\BaseController;
use Config\Database;
use Modules\Evaluation\Infrastructure\Persistence\MysqlGroupRepository;
use Modules\Evaluation\Domain\Service\RankingCalculator;
use Modules\Evaluation\Infrastructure\Persistence\MysqlEvaluationRepository;

final class EvaluationController extends BaseController
{
    public function create(): string
    {
        $repository = new MysqlGroupRepository(Database::connect());

        $groups = array_map(
            static fn (array $group): array => $group + ['members' => $repository->membersOf($group['id'])],
            $repository->all(),
        );

        return view('Modules\Evaluation\UI\Views\evaluation\new', ['groups' => $groups]);
    }

    public function store(): \CodeIgniter\HTTP\ResponseInterface
    {
        $post = $this->request->getPost([
            'evaluator_group_id', 'evaluated_group_id',
            'score_presentation', 'score_swot', 'score_marketing', 'score_oral',
            'remark',
        ]);

        $db = \Config\Database::connect();
        $useCase = new \Modules\Evaluation\Application\UseCase\RecordEvaluation(
            new \Modules\Evaluation\Infrastructure\Persistence\MysqlEvaluationRepository($db),
            new \Modules\Evaluation\Infrastructure\Persistence\MysqlGroupRepository($db),
        );

        try {
            $useCase(
                (int) ($post['evaluator_group_id'] ?? 0),
                (int) ($post['evaluated_group_id'] ?? 0),
                (string) ($post['score_presentation'] ?? ''),
                (string) ($post['score_swot'] ?? ''),
                (string) ($post['score_marketing'] ?? ''),
                (string) ($post['score_oral'] ?? ''),
                ($post['remark'] ?? '') !== '' ? (string) $post['remark'] : null,
            );
        } catch (\DomainException $e) {
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->to(site_url('evaluations/confirmation'));
    }

    public function confirmation(): string
    {
        return view('Modules\Evaluation\UI\Views\evaluation\confirmation');
    }

    public function ranking(): string
    {
        $db = \Config\Database::connect();
        $groups = new MysqlGroupRepository($db);
        $evaluations = new MysqlEvaluationRepository($db);

        $totalsByGroup = [];
        foreach ($evaluations->allGroupedByEvaluated() as $groupId => $list) {
            $totalsByGroup[$groupId] = array_map(
                static fn ($e) => $e->total(),
                $list,
            );
        }

        $ranked = (new RankingCalculator())->compute($totalsByGroup);

        $all = $groups->all();
        $names = [];
        foreach ($all as $g) {
            $names[$g['id']] = $g['name'];
        }

        $rows = [];
        $evaluatedIds = [];
        foreach ($ranked as $r) {
            $evaluatedIds[] = $r['groupId'];
            $rows[] = [
                'name'    => $names[$r['groupId']] ?? '?',
                'average' => $r['average'],
                'count'   => $r['count'],
                'rank'    => $r['rank'],
            ];
        }
        foreach ($all as $g) {
            if (! in_array($g['id'], $evaluatedIds, true)) {
                $rows[] = ['name' => $g['name'], 'average' => null, 'count' => 0, 'rank' => null];
            }
        }

        return view('Modules\Evaluation\UI\Views\evaluation\ranking', ['rows' => $rows]);
    }

    public function teacherList(): string
    {
        $db = \Config\Database::connect();
        $groups = new MysqlGroupRepository($db);
        $evaluations = new MysqlEvaluationRepository($db);

        $names = [];
        foreach ($groups->all() as $g) {
            $names[$g['id']] = $g['name'];
        }

        $rows = [];
        foreach ($evaluations->allGroupedByEvaluated() as $list) {
            foreach ($list as $e) {
                $rows[] = [
                    'id'        => $e->id(),
                    'evaluator' => $names[$e->evaluatorGroupId()] ?? '?',
                    'evaluated' => $names[$e->evaluatedGroupId()] ?? '?',
                    'teacher'   => $e->teacher()->value(),
                    'total'     => $e->total(),
                ];
            }
        }

        return view('Modules\Evaluation\UI\Views\evaluation\teacher_list', ['rows' => $rows]);
    }

    public function teacherForm(int $id): string|\CodeIgniter\HTTP\RedirectResponse
    {
        $db = \Config\Database::connect();
        $groups = new MysqlGroupRepository($db);
        $evaluations = new MysqlEvaluationRepository($db);

        $evaluation = $evaluations->find($id);
        if ($evaluation === null) {
            return redirect()->to(site_url('evaluations/notes-enseignant'));
        }

        $names = [];
        foreach ($groups->all() as $g) {
            $names[$g['id']] = $g['name'];
        }

        return view('Modules\Evaluation\UI\Views\evaluation\teacher_form', [
            'id'        => $id,
            'evaluator' => $names[$evaluation->evaluatorGroupId()] ?? '?',
            'evaluated' => $names[$evaluation->evaluatedGroupId()] ?? '?',
            'current'   => $evaluation->teacher()->value(),
            'total'     => $evaluation->total(),
        ]);
    }

    public function teacherStore(int $id): \CodeIgniter\HTTP\RedirectResponse
    {
        $value = (string) ($this->request->getPost('score_teacher') ?? '');

        try {
            $score = \Modules\Evaluation\Domain\ValueObject\Score::of($value);
        } catch (\DomainException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        $db = \Config\Database::connect();
        (new MysqlEvaluationRepository($db))->updateTeacherScore($id, $score);

        return redirect()->to(site_url('evaluations/notes-enseignant'));
    }
}