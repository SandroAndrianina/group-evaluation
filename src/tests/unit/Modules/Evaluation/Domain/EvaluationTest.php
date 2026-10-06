<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Evaluation\Domain;

use Modules\Evaluation\Domain\Entity\Evaluation;
use Modules\Evaluation\Domain\Exception\InvalidEvaluation;
use Modules\Evaluation\Domain\ValueObject\Score;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class EvaluationTest extends TestCase
{
    private function make(float $p, float $s, float $m, float $o, ?string $remark = null): Evaluation
    {
        return Evaluation::create(1, 2, Score::of($p), Score::of($s), Score::of($m), Score::of($o), null, $remark);
    }

    #[DataProvider('sheetCases')]
    public function testTotalMatchesTheSheet(float $p, float $s, float $m, float $o, float $expected): void
    {
        self::assertSame($expected, $this->make($p, $s, $m, $o)->total());
    }

    public static function sheetCases(): array
    {
        return [
            'Extra Pizza → Eau Vive'     => [1, 1, 1.5, 1, 4.5],
            'Colagate → Star'            => [1.25, 1, 1, 1, 4.25],
            'Alpha Ciment → Airtel'      => [1.5, 1, 1.5, 1.5, 5.5],
            'Aigle d\'or → Coca Cola 1'  => [1, 0.5, 1, 1, 3.5],
            'Ibaneze → Coca Cola 2'      => [2, 2, 1.5, 1, 6.5],
            'Star → Star (THB)'          => [2, 2, 2, 1, 7.0],
        ];
    }

    public function testTeacherScoreIsZeroByDefault(): void
    {
        self::assertSame(0.0, $this->make(1, 1, 1, 1)->teacher()->value());
    }

    public function testTeacherScoreCanBeAddedLaterWithoutChangingTheOriginal(): void
    {
        $original = $this->make(1, 1, 1.5, 1);
        $updated  = $original->withTeacherScore(Score::of(2));

        self::assertSame(4.5, $original->total());
        self::assertSame(6.5, $updated->total());
    }

    public function testGroupCannotEvaluateItself(): void
    {
        $this->expectException(InvalidEvaluation::class);
        Evaluation::create(3, 3, Score::of(1), Score::of(1), Score::of(1), Score::of(1));
    }

    public function testRemarkIsTrimmedAndEmptyBecomesNull(): void
    {
        self::assertSame('Oral clair', $this->make(1, 1, 1, 1, '  Oral clair  ')->remark());
        self::assertNull($this->make(1, 1, 1, 1, '   ')->remark());
    }

    public function testRemarkCannotBeTooLong(): void
    {
        $this->expectException(InvalidEvaluation::class);
        $this->make(1, 1, 1, 1, str_repeat('a', Evaluation::MAX_REMARK_LENGTH + 1));
    }
}