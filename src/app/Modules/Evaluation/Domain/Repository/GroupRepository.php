<?php

declare(strict_types=1);

namespace Modules\Evaluation\Domain\Repository;

interface GroupRepository
{
    /** @return list<array{id:int, name:string}> */
    public function all(): array;

    /** @return list<array{matricule:string, first_name:string}> */
    public function membersOf(int $groupId): array;

    public function exists(int $groupId): bool;

    public function find(int $id): ?array;

    public function create(string $name): int;

    public function rename(int $id, string $name): void;

    public function delete(int $id): void;

    public function hasEvaluations(int $id): bool;

    public function membersDetailed(int $groupId): array;

    public function addMember(int $groupId, string $matricule, string $firstName): void;

    public function removeMember(int $memberId): void;
}