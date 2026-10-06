<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Evaluation\Application;

use Modules\Evaluation\Application\UseCase\RecordEvaluation;
use Modules\Evaluation\Domain\Exception\InvalidEvaluation;
use Modules\Evaluation\Domain\Exception\InvalidScore;
use Modules\Evaluation\Domain\Repository\EvaluationRepository;
use Modules\Evaluation\Domain\Repository\GroupRepository;
use PHPUnit\Framework\TestCase;

final class RecordEvaluationTest extends TestCase
{
    private function useCase(bool $groupsExist = true, bool $alreadyDone = false): RecordEvaluation
    {
        $groups = $this->createStub(GroupRepository::class);
        $groups->method('exists')->willReturn($groupsExist);

        $evaluations = $this->createStub(EvaluationRepository::class);
        $evaluations->method('existsBetween')->willReturn($alreadyDone);
        $evaluations->method('save')->willReturnArgument(0);

        return new RecordEvaluation($evaluations, $groups);
    }

    public function testRecordsAValidEvaluation(): void
    {
        $evaluation = ($this->useCase())(1, 2, '1', '0,5', '1', '1', 'Oral clair');

        self::assertSame(3.5, $evaluation->total());
        self::assertSame('Oral clair', $evaluation->remark());
    }

    public function testRejectsAnUnknownGroup(): void
    {
        $this->expectException(InvalidEvaluation::class);
        ($this->useCase(groupsExist: false))(1, 2, '1', '1', '1', '1');
    }

    public function testRejectsADuplicateEvaluation(): void
    {
        $this->expectException(InvalidEvaluation::class);
        ($this->useCase(alreadyDone: true))(1, 2, '1', '1', '1', '1');
    }

    public function testRejectsSelfEvaluation(): void
    {
        $this->expectException(InvalidEvaluation::class);
        ($this->useCase())(3, 3, '1', '1', '1', '1');
    }

    public function testRejectsAnInvalidScore(): void
    {
        $this->expectException(InvalidScore::class);
        ($this->useCase())(1, 2, '2,5', '1', '1', '1');
    }
}