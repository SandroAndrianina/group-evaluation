<?php

declare(strict_types=1);

namespace Modules\Evaluation\Application\UseCase;

use Modules\Evaluation\Domain\Entity\Evaluation;
use Modules\Evaluation\Domain\Exception\InvalidEvaluation;
use Modules\Evaluation\Domain\Repository\EvaluationRepository;
use Modules\Evaluation\Domain\Repository\GroupRepository;
use Modules\Evaluation\Domain\ValueObject\Score;

final class RecordEvaluation
{
    public function __construct(
        private readonly EvaluationRepository $evaluations,
        private readonly GroupRepository $groups,
    ) {
    }

    public function __invoke(
        int $evaluatorGroupId,
        int $evaluatedGroupId,
        string $presentation,
        string $swot,
        string $marketing,
        string $oral,
        ?string $remark = null,
    ): Evaluation {
        $evaluation = Evaluation::create(
            $evaluatorGroupId,
            $evaluatedGroupId,
            Score::of($presentation),
            Score::of($swot),
            Score::of($marketing),
            Score::of($oral),
            null,
            $remark,
        );

        foreach ([$evaluatorGroupId, $evaluatedGroupId] as $groupId) {
            if (! $this->groups->exists($groupId)) {
                throw InvalidEvaluation::unknownGroup($groupId);
            }
        }

        if ($this->evaluations->existsBetween($evaluatorGroupId, $evaluatedGroupId)) {
            throw InvalidEvaluation::alreadyEvaluated();
        }

        return $this->evaluations->save($evaluation);
    }
}