<?php

declare(strict_types=1);

namespace Modules\Evaluation\UI\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\RedirectResponse;
use Modules\Evaluation\Infrastructure\Persistence\MysqlGroupRepository;

final class GroupController extends BaseController
{
    private function repo(): MysqlGroupRepository
    {
        return new MysqlGroupRepository(\Config\Database::connect());
    }

    public function index(): string
    {
        $repo = $this->repo();
        $groups = $repo->all();
        foreach ($groups as &$g) {
            $g['members'] = count($repo->membersOf($g['id']));
            $g['evaluated'] = $repo->hasEvaluations($g['id']);
        }
        return view('Modules\Evaluation\UI\Views\group\index', ['groups' => $groups]);
    }

    public function create(): string
    {
        return view('Modules\Evaluation\UI\Views\group\form', ['group' => null]);
    }

    public function store(): RedirectResponse
    {
        $name = trim((string) $this->request->getPost('name'));
        if ($name === '') {
            return redirect()->back()->with('error', 'Nom obligatoire.');
        }
        try {
            $this->repo()->create($name);
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', 'Ce nom existe déjà.');
        }
        return redirect()->to(site_url('evaluations/groupes'))->with('success', 'Groupe créé.');
    }

    public function edit(int $id): string|RedirectResponse
    {
        $group = $this->repo()->find($id);
        if ($group === null) {
            return redirect()->to(site_url('evaluations/groupes'))->with('error', 'Groupe introuvable.');
        }
        return view('Modules\Evaluation\UI\Views\group\form', ['group' => $group]);
    }

    public function update(int $id): RedirectResponse
    {
        $name = trim((string) $this->request->getPost('name'));
        if ($name === '') {
            return redirect()->back()->with('error', 'Nom obligatoire.');
        }
        try {
            $this->repo()->rename($id, $name);
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', 'Ce nom existe déjà.');
        }
        return redirect()->to(site_url('evaluations/groupes'))->with('success', 'Groupe renommé.');
    }

    public function delete(int $id): RedirectResponse
    {
        $repo = $this->repo();
        if ($repo->hasEvaluations($id)) {
            return redirect()->to(site_url('evaluations/groupes'))
                ->with('error', 'Impossible : ce groupe a des évaluations.');
        }
        $repo->delete($id);
        return redirect()->to(site_url('evaluations/groupes'))->with('success', 'Groupe supprimé.');
    }

    public function members(int $id): string|RedirectResponse
    {
        $repo = $this->repo();
        $group = $repo->find($id);
        if ($group === null) {
            return redirect()->to(site_url('evaluations/groupes'))->with('error', 'Groupe introuvable.');
        }
        return view('Modules\Evaluation\UI\Views\group\members', [
            'group' => $group,
            'members' => $repo->membersDetailed($id),
        ]);
    }

    public function addMember(int $id): RedirectResponse
    {
        $matricule = trim((string) $this->request->getPost('matricule'));
        $firstName = trim((string) $this->request->getPost('first_name'));
        if ($matricule === '' || $firstName === '') {
            return redirect()->back()->with('error', 'Matricule et prénom obligatoires.');
        }
        try {
            $this->repo()->addMember($id, $matricule, $firstName);
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', 'Ce matricule existe déjà dans ce groupe.');
        }
        return redirect()->to(site_url('evaluations/groupes/' . $id . '/membres'))->with('success', 'Membre ajouté.');
    }

    public function removeMember(int $id, int $memberId): RedirectResponse
    {
        $this->repo()->removeMember($memberId);
        return redirect()->to(site_url('evaluations/groupes/' . $id . '/membres'))->with('success', 'Membre retiré.');
    }
}