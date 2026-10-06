<?php

declare(strict_types=1);

namespace Modules\Evaluation\Infrastructure\Import;

use RuntimeException;

final class CsvReader
{
    /**
     * Lit un CSV : saute l'en-tête, garde les $columns premières colonnes,
     * nettoie les espaces et ignore les lignes vides.
     *
     * @return list<list<string>>
     */
    public function read(string $path, int $columns): array
    {
        if (! is_file($path) || ! is_readable($path)) {
            throw new RuntimeException(sprintf('Fichier introuvable ou illisible : %s', $path));
        }

        $handle = fopen($path, 'rb');
        $rows   = [];
        $header = true;

        while (($line = fgetcsv($handle, 0, ',', '"', '')) !== false) {
            if ($header) {
                $header = false;

                continue;
            }

            $cells = array_map(
                static fn ($cell): string => trim((string) $cell),
                array_slice($line, 0, $columns),
            );
            $cells = array_pad($cells, $columns, '');

            if (implode('', $cells) === '') {
                continue;
            }

            $rows[] = $cells;
        }

        fclose($handle);

        return $rows;
    }
}