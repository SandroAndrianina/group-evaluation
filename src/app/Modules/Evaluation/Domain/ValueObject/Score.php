<?php

declare(strict_types=1);

namespace Modules\Evaluation\Domain\ValueObject;

use Modules\Evaluation\Domain\Exception\InvalidScore;

final class Score
{
    public const MIN  = 0.0;
    public const MAX  = 2.0;
    private const STEP = 0.25;

    private function __construct(private readonly float $value)
    {
    }

    public static function of(float|int|string $input): self
    {
        if (is_string($input)) {
            $clean = str_replace(',', '.', trim($input));
            if (! is_numeric($clean)) {
                throw InvalidScore::notANumber($input);
            }
            $input = $clean;
        }

        $value = (float) $input;

        if ($value < self::MIN || $value > self::MAX) {
            throw InvalidScore::outOfRange($value);
        }

        $steps = $value / self::STEP;
        if (abs($steps - round($steps)) > 1e-9) {
            throw InvalidScore::badStep($value);
        }

        return new self($value);
    }

    public static function zero(): self
    {
        return new self(0.0);
    }

    public function value(): float
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}