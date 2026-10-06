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
}