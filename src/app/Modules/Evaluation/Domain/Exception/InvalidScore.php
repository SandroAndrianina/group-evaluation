<?php

declare(strict_types=1);

namespace Modules\Evaluation\Domain\Exception;

use DomainException;

final class InvalidScore extends DomainException
{
    public static function notANumber(string $input): self
    {
        return new self(sprintf('« %s » n\'est pas un nombre.', $input));
    }

    public static function outOfRange(float $value): self
    {
        return new self(sprintf('La note doit être comprise entre 0 et 2 (reçu : %s).', $value));
    }

    public static function badStep(float $value): self
    {
        return new self(sprintf('La note doit être un multiple de 0,25 (reçu : %s).', $value));
    }
}