<?php

declare(strict_types=1);

namespace Modules\Attendance\Libraries;

final class StudentCsvParser
{
    /** @param list<list<string>> $rows Columns expected: timestamp, matricule, family name, first name. */
    public function parse(array $rows): array
    {
        $students = [];
        $errors = [];
        $duplicates = 0;

        foreach ($rows as $line => $row) {
            if (!is_array($row) || count($row) < 4) {
                $errors[] = sprintf('Ligne %d : 4 colonnes sont attendues (horodateur, matricule, nom, prénom).', $line + 1);
                continue;
            }
            $etu = preg_replace('/\s+/', '', (string) ($row[1] ?? '')) ?? '';
            $lastName = (string) ($row[2] ?? '');
            $firstName = (string) ($row[3] ?? '');
            $name = trim(preg_replace('/\s+/', ' ', $lastName . ' ' . $firstName) ?? '');
            if ($name === '') {
                $errors[] = sprintf('Ligne %d, ETU « %s » : nom et prénom vides.', $line + 1, $etu);
                continue;
            }
            if ($etu === '' || !ctype_digit($etu)) {
                $errors[] = sprintf('Ligne %d, « %s » : matricule « %s » invalide (chiffres uniquement).', $line + 1, $name, $etu);
                continue;
            }
            if (isset($students[$etu])) {
                if (mb_strtolower($students[$etu]) === mb_strtolower($name)) {
                    $duplicates++;
                } else {
                    $errors[] = sprintf('Matricule %s utilisé par « %s » et « %s ».', $etu, $students[$etu], $name);
                }
                continue;
            }
            $students[$etu] = $name;
        }
        return ['students' => $students, 'errors' => $errors, 'duplicates' => $duplicates];
    }
}
