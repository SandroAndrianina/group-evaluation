<?php

declare(strict_types=1);

namespace Modules\Evaluation\Domain\Repository;

use Modules\Evaluation\Domain\Entity\Evaluation;

interface EvaluationRepository
{
    /** Enregistre l'évaluation et la renvoie avec son identifiant. */
    public function save(Evaluation $evaluation): Evaluation;

    public function existsBetween(int $evaluatorGroupId, int $evaluatedGroupId): bool;

    /** @return array<int, list<Evaluation>> groupId évalué => évaluations */
    public function allGroupedByEvaluated(): array;

    public function find(int $id): ?Evaluation;

    public function updateTeacherScore(int $id, \Modules\Evaluation\Domain\ValueObject\Score $score): void;

        /** @return list<Evaluation> */
    public function all(): array;

    public function delete(int $id): void;

    public function update(int $id, \Modules\Evaluation\Domain\ValueObject\Score $presentation, \Modules\Evaluation\Domain\ValueObject\Score $swot, \Modules\Evaluation\Domain\ValueObject\Score $marketing, \Modules\Evaluation\Domain\ValueObject\Score $oral, ?string $remark): void;
}