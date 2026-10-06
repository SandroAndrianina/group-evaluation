<?php

declare(strict_types=1);

namespace Modules\Evaluation\Domain\Exception;

use DomainException;

final class InvalidEvaluation extends DomainException
{
    public static function selfEvaluation(): self
    {
        return new self('Un groupe ne peut pas s\'évaluer lui-même.');
    }

    public static function remarkTooLong(int $max): self
    {
        return new self(sprintf('La remarque ne doit pas dépasser %d caractères.', $max));
    }

        public static function unknownGroup(int $id): self
    {
        return new self(sprintf('Le groupe n°%d n\'existe pas.', $id));
    }

    public static function alreadyEvaluated(): self
    {
        return new self('Ce groupe a déjà évalué ce groupe.');
    }
}