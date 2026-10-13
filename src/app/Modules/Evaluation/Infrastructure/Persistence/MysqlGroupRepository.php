<?php

declare(strict_types=1);

namespace Modules\Evaluation\Infrastructure\Persistence;

use CodeIgniter\Database\BaseConnection;
use Modules\Evaluation\Domain\Repository\GroupRepository;

final class MysqlGroupRepository implements GroupRepository
{
    public function __construct(private readonly BaseConnection $db)
    {
    }

    public function all(): array
    {
        $rows = $this->db->table('student_groups')->select('id, code, name')->orderBy('code')->get()->getResultArray();

        return array_map(
            static fn (array $r): array => [
                'id'   => (int) $r['id'],
                'code' => (string) ($r['code'] ?? ''),
                'name' => (string) $r['name'],
            ],
            $rows,
        );
    }

    public function membersOf(int $groupId): array
    {
        $rows = $this->db->table('members')
            ->select('matricule, first_name')
            ->where('group_id', $groupId)
            ->orderBy('first_name')
            ->get()->getResultArray();

        return array_map(
            static fn (array $r): array => ['matricule' => (string) $r['matricule'], 'first_name' => (string) $r['first_name']],
            $rows,
        );
    }

    public function exists(int $groupId): bool
    {
        return $this->db->table('student_groups')->where('id', $groupId)->countAllResults() > 0;
    }

        public function find(int $id): ?array
    {
        $row = $this->db->table('student_groups')->where('id', $id)->get()->getRowArray();
        return $row ?: null;
    }

    public function create(string $name): int
    {
        $now = date('Y-m-d H:i:s');
        $next = (int) ($this->db->table('student_groups')->selectMax('id', 'max')->get()->getRowArray()['max'] ?? 0) + 1;
        $this->db->table('student_groups')->insert([
            'code' => 'G' . $next, 'name' => $name,
            'created_at' => $now, 'updated_at' => $now,
        ]);
        return (int) $this->db->insertID();
    }

    public function rename(int $id, string $name): void
    {
        $this->db->table('student_groups')->where('id', $id)->update([
            'name' => $name, 'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function delete(int $id): void
    {
        $this->db->table('members')->where('group_id', $id)->delete();
        $this->db->table('student_groups')->where('id', $id)->delete();
    }

    public function hasEvaluations(int $id): bool
    {
        return $this->db->table('evaluations')
            ->groupStart()
                ->where('evaluator_group_id', $id)
                ->orWhere('evaluated_group_id', $id)
            ->groupEnd()
            ->countAllResults() > 0;
    }

    public function membersDetailed(int $groupId): array
    {
        return $this->db->table('members')
            ->select('id, matricule, first_name')
            ->where('group_id', $groupId)
            ->orderBy('matricule')
            ->get()->getResultArray();
    }

    public function addMember(int $groupId, string $matricule, string $firstName): void
    {
        $now = date('Y-m-d H:i:s');
        $this->db->table('members')->insert([
            'group_id' => $groupId, 'matricule' => $matricule, 'first_name' => $firstName,
            'created_at' => $now, 'updated_at' => $now,
        ]);
    }

    public function removeMember(int $memberId): void
    {
        $this->db->table('members')->where('id', $memberId)->delete();
    }
}