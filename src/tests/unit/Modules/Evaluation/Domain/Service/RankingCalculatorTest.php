<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Evaluation\Domain\Service;

use Modules\Evaluation\Domain\Service\RankingCalculator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class RankingCalculatorTest extends TestCase
{
    /**
     * @param array<int, list<float>> $input
     * @param list<array{groupId:int, average:float, count:int, rank:int}> $expected
     */
    #[DataProvider('cases')]
    public function test_compute(array $input, array $expected): void
    {
        $this->assertSame($expected, (new RankingCalculator())->compute($input));
    }

    public static function cases(): array
    {
        return [
            'jeu de données réel' => [
                [
                    10 => [7.0],
                    20 => [6.5],
                    30 => [5.5],
                    40 => [4.5],
                    50 => [4.25],
                    60 => [3.5],
                ],
                [
                    ['groupId' => 10, 'average' => 7.0,  'count' => 1, 'rank' => 1],
                    ['groupId' => 20, 'average' => 6.5,  'count' => 1, 'rank' => 2],
                    ['groupId' => 30, 'average' => 5.5,  'count' => 1, 'rank' => 3],
                    ['groupId' => 40, 'average' => 4.5,  'count' => 1, 'rank' => 4],
                    ['groupId' => 50, 'average' => 4.25, 'count' => 1, 'rank' => 5],
                    ['groupId' => 60, 'average' => 3.5,  'count' => 1, 'rank' => 6],
                ],
            ],
            'égalité partagée' => [
                [1 => [5.0], 2 => [5.0], 3 => [3.0]],
                [
                    ['groupId' => 1, 'average' => 5.0, 'count' => 1, 'rank' => 1],
                    ['groupId' => 2, 'average' => 5.0, 'count' => 1, 'rank' => 1],
                    ['groupId' => 3, 'average' => 3.0, 'count' => 1, 'rank' => 3],
                ],
            ],
            'moyennes multiples' => [
                [1 => [7.0, 6.0], 2 => [8.0]],
                [
                    ['groupId' => 2, 'average' => 8.0, 'count' => 1, 'rank' => 1],
                    ['groupId' => 1, 'average' => 6.5, 'count' => 2, 'rank' => 2],
                ],
            ],
        ];
    }
}