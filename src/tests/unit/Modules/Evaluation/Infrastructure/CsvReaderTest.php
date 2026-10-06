<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Evaluation\Infrastructure;

use Modules\Evaluation\Infrastructure\Import\CsvReader;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class CsvReaderTest extends TestCase
{
    /** @var list<string> */
    private array $files = [];

    protected function tearDown(): void
    {
        foreach ($this->files as $file) {
            @unlink($file);
        }
    }

    private function csv(string $content): string
    {
        $path = tempnam(sys_get_temp_dir(), 'csv');
        file_put_contents($path, $content);
        $this->files[] = $path;

        return $path;
    }

    public function testSkipsHeaderAndKeepsFrenchDecimalsAsText(): void
    {
        $path = $this->csv("Nom,Note\nEau Vive,\"1,25\"\n");

        self::assertSame([['Eau Vive', '1,25']], (new CsvReader())->read($path, 2));
    }

    public function testIgnoresExtraColumnsAndPadsMissingOnes(): void
    {
        $path = $this->csv("A,B\nx,y,z,w\nonly\n");

        self::assertSame([['x', 'y'], ['only', '']], (new CsvReader())->read($path, 2));
    }

    public function testSkipsEmptyRows(): void
    {
        $path = $this->csv("A,B\n,\n\nx,y\n , \n");

        self::assertSame([['x', 'y']], (new CsvReader())->read($path, 2));
    }

    public function testTrimsSpaces(): void
    {
        $path = $this->csv("A,B\n Eau Vive , 1 \n");

        self::assertSame([['Eau Vive', '1']], (new CsvReader())->read($path, 2));
    }

    public function testThrowsWhenFileIsMissing(): void
    {
        $this->expectException(RuntimeException::class);
        (new CsvReader())->read('/fichier/inexistant.csv', 2);
    }
}