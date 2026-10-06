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
        $rows = $this->db->table('student_groups')->select('id, name')->orderBy('name')->get()->getResultArray();

        return array_map(
            static fn (array $r): array => ['id' => (int) $r['id'], 'name' => (string) $r['name']],
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
}