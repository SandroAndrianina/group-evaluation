<?php

declare(strict_types=1);

namespace Modules\Attendance\Libraries;

final class StudentCsvParser
{
    /**
     * @param list<list<string>> $rows colonnes : horodateur, ETU, nom de famille, prénom
     *
     * @return array{students: array<int|string, string>, errors: list<string>, duplicates: int}
     */
    public function parse(array $rows): array
    {
        $students   = [];
        $errors     = [];
        $duplicates = 0;

        foreach ($rows as [, $etu, $lastName, $firstName]) {
            $etu  = preg_replace('/\s+/', '', $etu) ?? '';
            $name = trim(preg_replace('/\s+/', ' ', $lastName . ' ' . $firstName) ?? '');

            if ($name === '') {
                $errors[] = sprintf('ETU « %s » : nom et prénom vides.', $etu);

                continue;
            }

            if ($etu === '' || ! ctype_digit($etu)) {
                $errors[] = sprintf('« %s » : ETU « %s » invalide (chiffres uniquement).', $name, $etu);

                continue;
            }

            if (isset($students[$etu])) {
                if (mb_strtolower($students[$etu]) === mb_strtolower($name)) {
                    $duplicates++;
                } else {
                    $errors[] = sprintf('ETU %s utilisé par « %s » et par « %s ».', $etu, $students[$etu], $name);
                }

                continue;
            }

            $students[$etu] = $name;
        }

        return ['students' => $students, 'errors' => $errors, 'duplicates' => $duplicates];
    }
}