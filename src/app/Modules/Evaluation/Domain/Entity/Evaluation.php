<?php

declare(strict_types=1);

namespace Modules\Evaluation\Domain\Entity;

use Modules\Evaluation\Domain\Exception\InvalidEvaluation;
use Modules\Evaluation\Domain\ValueObject\Score;

final class Evaluation
{
    public const MAX_REMARK_LENGTH = 500;

    private function __construct(
        private readonly ?int $id,
        private readonly int $evaluatorGroupId,
        private readonly int $evaluatedGroupId,
        private readonly Score $presentation,
        private readonly Score $swot,
        private readonly Score $marketing,
        private readonly Score $oral,
        private readonly Score $teacher,
        private readonly ?string $remark,
    ) {
    }

    public static function create(
        int $evaluatorGroupId,
        int $evaluatedGroupId,
        Score $presentation,
        Score $swot,
        Score $marketing,
        Score $oral,
        ?Score $teacher = null,
        ?string $remark = null,
        ?int $id = null,
    ): self {
        if ($evaluatorGroupId === $evaluatedGroupId) {
            throw InvalidEvaluation::selfEvaluation();
        }

        $remark = $remark === null ? null : trim($remark);
        if ($remark === '') {
            $remark = null;
        }
        if ($remark !== null && mb_strlen($remark) > self::MAX_REMARK_LENGTH) {
            throw InvalidEvaluation::remarkTooLong(self::MAX_REMARK_LENGTH);
        }

        return new self(
            $id, $evaluatorGroupId, $evaluatedGroupId,
            $presentation, $swot, $marketing, $oral,
            $teacher ?? Score::zero(), $remark,
        );
    }

    public function withTeacherScore(Score $teacher): self
    {
        return new self(
            $this->id, $this->evaluatorGroupId, $this->evaluatedGroupId,
            $this->presentation, $this->swot, $this->marketing, $this->oral,
            $teacher, $this->remark,
        );
    }

    public function total(): float
    {
        return round(
            $this->presentation->value() + $this->swot->value() + $this->marketing->value()
            + $this->oral->value() + $this->teacher->value(),
            2,
        );
    }

    public function id(): ?int { return $this->id; }
    public function evaluatorGroupId(): int { return $this->evaluatorGroupId; }
    public function evaluatedGroupId(): int { return $this->evaluatedGroupId; }
    public function presentation(): Score { return $this->presentation; }
    public function swot(): Score { return $this->swot; }
    public function marketing(): Score { return $this->marketing; }
    public function oral(): Score { return $this->oral; }
    public function teacher(): Score { return $this->teacher; }
    public function remark(): ?string { return $this->remark; }
}