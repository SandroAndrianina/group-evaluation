<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Evaluation\Domain;

use Modules\Evaluation\Domain\Exception\InvalidScore;
use Modules\Evaluation\Domain\ValueObject\Score;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ScoreTest extends TestCase
{
    #[DataProvider('validScores')]
    public function testAcceptsValidScores(float|int|string $input, float $expected): void
    {
        self::assertSame($expected, Score::of($input)->value());
    }

    public static function validScores(): array
    {
        return [
            'zéro'              => [0, 0.0],
            'quart'             => [0.25, 0.25],
            'demi'              => [0.5, 0.5],
            'un'                => [1, 1.0],
            'un quart (Sheets)' => [1.25, 1.25],
            'virgule française' => ['1,5', 1.5],
            'texte avec point'  => ['0.5', 0.5],
            'maximum'           => [2, 2.0],
        ];
    }

    #[DataProvider('invalidScores')]
    public function testRejectsInvalidScores(float|int|string $input): void
    {
        $this->expectException(InvalidScore::class);
        Score::of($input);
    }

    public static function invalidScores(): array
    {
        return [
            'négatif'      => [-0.5],
            'trop grand'   => [2.5],
            'beaucoup'     => [3],
            'pas de 0,25'  => [0.3],
            'pas de 0,25 bis' => [1.1],
            'texte'        => ['abc'],
            'vide'         => [''],
        ];
    }

    public function testZero(): void
    {
        self::assertSame(0.0, Score::zero()->value());
    }
}