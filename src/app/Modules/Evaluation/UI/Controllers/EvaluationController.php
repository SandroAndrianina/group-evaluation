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
            $totalsByGroup[$groupId] = array_map(static fn ($e) => $e->total(), $list);
        }

        $ranked = (new RankingCalculator())->compute($totalsByGroup);

        $all = $groups->all();
        $byId = [];
        foreach ($all as $g) {
            $byId[$g['id']] = $g;
        }

        $rows = [];
        $evaluatedIds = [];
        foreach ($ranked as $r) {
            $evaluatedIds[] = $r['groupId'];
            $g = $byId[$r['groupId']] ?? null;
            $members = $groups->membersOf($r['groupId']);
            $rows[] = [
                'code'    => $g['code'] ?? '—',
                'name'    => $g['name'] ?? '?',
                'average' => $r['average'],
                'count'   => $r['count'],
                'rank'    => $r['rank'],
                'members' => $members,
            ];
        }
        foreach ($all as $g) {
            if (! in_array($g['id'], $evaluatedIds, true)) {
                $rows[] = [
                    'code' => $g['code'] ?? '—', 'name' => $g['name'],
                    'average' => null, 'count' => 0, 'rank' => null,
                    'members' => $groups->membersOf($g['id']),
                ];
            }
        }

        return view('Modules\Evaluation\UI\Views\evaluation\ranking', ['rows' => $rows]);
    }

    public function teacherList(): string
{
    $db = \Config\Database::connect();
    $groups = new MysqlGroupRepository($db);
    $evaluations = new MysqlEvaluationRepository($db);

    $byId = [];
    foreach ($groups->all() as $g) {
        $byId[$g['id']] = $g;
    }

    $rows = [];
    foreach ($evaluations->allGroupedByEvaluated() as $list) {
        foreach ($list as $e) {
            $ev = $byId[$e->evaluatorGroupId()] ?? null;
            $ed = $byId[$e->evaluatedGroupId()] ?? null;
            $rows[] = [
                'id'             => $e->id(),
                'evaluator_code' => $ev['code'] ?? '—',
                'evaluator_name' => $ev['name'] ?? '?',
                'evaluated_code' => $ed['code'] ?? '—',
                'evaluated_name' => $ed['name'] ?? '?',
                'teacher'        => $e->teacher()->value(),
                'total'          => $e->total(),
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

    public function adminList(): string
{
    $db = \Config\Database::connect();
    $groups = new MysqlGroupRepository($db);
    $evaluations = new MysqlEvaluationRepository($db);

    $byId = [];
    foreach ($groups->all() as $g) {
        $byId[$g['id']] = $g;
    }

    $rows = [];
    foreach ($evaluations->all() as $e) {
        $ev = $byId[$e->evaluatorGroupId()] ?? null;
        $ed = $byId[$e->evaluatedGroupId()] ?? null;
        $rows[] = [
            'id'               => $e->id(),
            'evaluator_code'   => $ev['code'] ?? '—',
            'evaluator_name'   => $ev['name'] ?? '?',
            'evaluated_code'   => $ed['code'] ?? '—',
            'evaluated_name'   => $ed['name'] ?? '?',
            'total'            => $e->total(),
            'teacher'          => $e->teacher()->value(),
        ];
    }

    return view('Modules\Evaluation\UI\Views\evaluation\admin_list', ['rows' => $rows]);
}

public function edit(int $id): string|\CodeIgniter\HTTP\RedirectResponse
{
    $db = \Config\Database::connect();
    $repo = new MysqlEvaluationRepository($db);
    $evaluation = $repo->find($id);
    if ($evaluation === null) {
        return redirect()->to(site_url('evaluations/liste'))->with('error', 'Évaluation introuvable.');
    }

    $groups = new MysqlGroupRepository($db);
    $names = [];
    foreach ($groups->all() as $g) {
        $names[$g['id']] = $g['name'];
    }

    return view('Modules\Evaluation\UI\Views\evaluation\admin_edit', [
        'id'        => $id,
        'evaluator' => $names[$evaluation->evaluatorGroupId()] ?? '?',
        'evaluated' => $names[$evaluation->evaluatedGroupId()] ?? '?',
        'scores'    => [
            'presentation' => $evaluation->presentation()->value(),
            'swot'         => $evaluation->swot()->value(),
            'marketing'    => $evaluation->marketing()->value(),
            'oral'         => $evaluation->oral()->value(),
        ],
        'remark'    => $evaluation->remark(),
    ]);
}

public function editStore(int $id): \CodeIgniter\HTTP\RedirectResponse
{
    $post = $this->request->getPost([
        'score_presentation', 'score_swot', 'score_marketing', 'score_oral', 'remark',
    ]);

    try {
        $p = \Modules\Evaluation\Domain\ValueObject\Score::of((string) $post['score_presentation']);
        $s = \Modules\Evaluation\Domain\ValueObject\Score::of((string) $post['score_swot']);
        $m = \Modules\Evaluation\Domain\ValueObject\Score::of((string) $post['score_marketing']);
        $o = \Modules\Evaluation\Domain\ValueObject\Score::of((string) $post['score_oral']);
    } catch (\DomainException $e) {
        return redirect()->back()->with('error', $e->getMessage());
    }

    $remark = trim((string) ($post['remark'] ?? ''));
    if ($remark === '') {
        $remark = null;
    } elseif (mb_strlen($remark) > 500) {
        return redirect()->back()->with('error', 'Remarque trop longue (500 max).');
    }

    $db = \Config\Database::connect();
    (new MysqlEvaluationRepository($db))->update($id, $p, $s, $m, $o, $remark);

    return redirect()->to(site_url('evaluations/liste'))->with('success', 'Évaluation modifiée.');
}

public function destroy(int $id): \CodeIgniter\HTTP\RedirectResponse
{
    $db = \Config\Database::connect();
    (new MysqlEvaluationRepository($db))->delete($id);
    return redirect()->to(site_url('evaluations/liste'))->with('success', 'Évaluation supprimée.');
}

public function exportCsv(): \CodeIgniter\HTTP\ResponseInterface
{
    $db = \Config\Database::connect();
    $groups = new MysqlGroupRepository($db);
    $evaluations = new MysqlEvaluationRepository($db);

    $names = [];
    foreach ($groups->all() as $g) {
        $names[$g['id']] = $g['name'];
    }

    $totalsByGroup = [];
    foreach ($evaluations->allGroupedByEvaluated() as $gid => $list) {
        $totalsByGroup[$gid] = array_map(static fn ($e) => $e->total(), $list);
    }

    $ranked = (new RankingCalculator())->compute($totalsByGroup);

    $csv = "rang;groupe;moyenne;nb_evaluations\n";
    foreach ($ranked as $r) {
        $csv .= sprintf(
            "%d;%s;%s;%d\n",
            $r['rank'],
            str_replace(';', ',', $names[$r['groupId']] ?? '?'),
            str_replace('.', ',', (string) $r['average']),
            $r['count'],
        );
    }

    return $this->response
        ->setHeader('Content-Type', 'text/csv; charset=utf-8')
        ->setHeader('Content-Disposition', 'attachment; filename="classement.csv"')
        ->setBody("\xEF\xBB\xBF" . $csv);
}

public function exportRankingCsv(): \CodeIgniter\HTTP\ResponseInterface
{
    $db = \Config\Database::connect();
    $groups = new MysqlGroupRepository($db);
    $evaluations = new MysqlEvaluationRepository($db);

    $byId = [];
    foreach ($groups->all() as $g) {
        $byId[$g['id']] = $g;
    }

    $totalsByGroup = [];
    foreach ($evaluations->allGroupedByEvaluated() as $gid => $list) {
        $totalsByGroup[$gid] = array_map(static fn ($e) => $e->total(), $list);
    }

    $ranked = (new RankingCalculator())->compute($totalsByGroup);

    $csv = "rang;code;groupe;moyenne;nb_evaluations;membres\n";
    foreach ($ranked as $r) {
        $g = $byId[$r['groupId']] ?? ['code' => '', 'name' => '?'];
        $members = $groups->membersOf($r['groupId']);
        $memberList = implode(' | ', array_map(
            static fn ($m) => $m['matricule'] . ' ' . $m['first_name'],
            $members,
        ));
        $csv .= sprintf(
            "%d;%s;%s;%s;%d;%s\n",
            $r['rank'],
            $g['code'] ?? '',
            str_replace(';', ',', $g['name']),
            str_replace('.', ',', (string) $r['average']),
            $r['count'],
            str_replace(';', ',', $memberList),
        );
    }

    return $this->response
        ->setHeader('Content-Type', 'text/csv; charset=utf-8')
        ->setHeader('Content-Disposition', 'attachment; filename="classement.csv"')
        ->setBody("\xEF\xBB\xBF" . $csv);
}
}