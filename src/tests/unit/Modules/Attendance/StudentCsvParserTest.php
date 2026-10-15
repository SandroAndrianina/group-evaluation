<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Attendance;

use Modules\Attendance\Libraries\StudentCsvParser;
use PHPUnit\Framework\TestCase;

final class StudentCsvParserTest extends TestCase
{
    public function testBuildsStudentsAndIgnoresTheTimestamp(): void
    {
        $result = (new StudentCsvParser())->parse([
            ['13/10/2026 08:00:00', '4149', 'Rakoto', 'Jean'],
            ['13/10/2026 08:01:00', ' 4150 ', 'Rasoa', 'Marie  Claire'],
        ]);

        self::assertSame([4149 => 'Rakoto Jean', 4150 => 'Rasoa Marie Claire'], $result['students']);
        self::assertSame([], $result['errors']);
    }

    public function testSameStudentSubmittedTwiceIsIgnored(): void
    {
        $result = (new StudentCsvParser())->parse([
            ['', '4149', 'Rakoto', 'Jean'],
            ['', '4149', 'RAKOTO', 'jean'],
        ]);

        self::assertCount(1, $result['students']);
        self::assertSame(1, $result['duplicates']);
        self::assertSame([], $result['errors']);
    }

    public function testSameEtuForTwoDifferentPeopleIsAnError(): void
    {
        $result = (new StudentCsvParser())->parse([
            ['', '4149', 'Rakoto', 'Jean'],
            ['', '4149', 'Rasoa', 'Marie'],
        ]);

        self::assertCount(1, $result['errors']);
    }

    public function testInvalidEtuIsAnError(): void
    {
        $result = (new StudentCsvParser())->parse([['', 'ETU4149', 'Rakoto', 'Jean']]);

        self::assertSame([], $result['students']);
        self::assertCount(1, $result['errors']);
    }

    public function testEmptyNameIsAnError(): void
    {
        $result = (new StudentCsvParser())->parse([['', '4149', '', '']]);

        self::assertCount(1, $result['errors']);
    }
}