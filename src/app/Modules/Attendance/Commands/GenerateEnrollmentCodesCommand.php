<?php

declare(strict_types=1);

namespace Modules\Attendance\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Config\Database;
use RuntimeException;

final class GenerateEnrollmentCodesCommand extends BaseCommand
{
    protected $group = 'Attendance';
    protected $name = 'attendance:generate-enrollment-codes';
    protected $description = 'Génère un CSV de codes individuels à distribuer en privé aux étudiants non encore inscrits.';
    protected $usage = 'attendance:generate-enrollment-codes';

    public function run(array $params): int
    {
        $db = Database::connect();
        $students = $db->table('students s')
            ->select('s.id, s.matricule, s.name')
            ->join('passkeys p', 'p.student_id = s.id', 'left')
            ->where('p.id', null)
            ->orderBy('s.matricule', 'ASC')
            ->get()->getResultArray();

        if ($students === []) {
            CLI::write('Aucun étudiant sans passkey. Aucun fichier généré.', 'yellow');
            return EXIT_SUCCESS;
        }

        $exportDir = WRITEPATH . 'export';
        if (!is_dir($exportDir) && !mkdir($exportDir, 0775, true) && !is_dir($exportDir)) {
            CLI::error('Impossible de créer writable/export.');
            return EXIT_ERROR;
        }

        $path = $exportDir . DIRECTORY_SEPARATOR . 'codes-inscription-' . gmdate('Ymd-His') . '.csv';
        $handle = fopen($path, 'wb');
        if ($handle === false) {
            CLI::error('Impossible de créer le CSV de codes.');
            return EXIT_ERROR;
        }

        // Excel/Sheets detect UTF-8; the codes are only written once and are never stored in plaintext.
        fwrite($handle, "\xEF\xBB\xBF");
        fputcsv($handle, ['matricule', 'nom', 'code_inscription'], ',', '"', '');
        $db->transBegin();
        try {
            foreach ($students as $student) {
                $code = self::makeCode();
                $existing = $db->table('attendance_enrollment_codes')->where('student_id', $student['id'])->get()->getRowArray();
                $values = [
                    'student_id' => (int) $student['id'],
                    'code_hash'  => password_hash($code, PASSWORD_DEFAULT),
                    'issued_at'  => gmdate('Y-m-d H:i:s'),
                    'used_at'    => null,
                ];
                if ($existing) {
                    $db->table('attendance_enrollment_codes')->where('student_id', $student['id'])->update($values);
                } else {
                    $db->table('attendance_enrollment_codes')->insert($values);
                }
                fputcsv($handle, [$student['matricule'], $student['name'], $code], ',', '"', '');
            }
            if ($db->transStatus() === false) {
                throw new RuntimeException('Erreur de base de données pendant la génération.');
            }
            $db->transCommit();
        } catch (\Throwable $e) {
            $db->transRollback();
            fclose($handle);
            @unlink($path);
            CLI::error('Génération annulée : ' . $e->getMessage());
            return EXIT_ERROR;
        }

        fclose($handle);
        CLI::write('CSV créé : ' . $path, 'green');
        CLI::write('Codes générés : ' . count($students) . '. Distribue chaque code en privé et supprime le CSV après distribution.', 'yellow');
        CLI::write('Les étudiants ayant déjà une passkey ont été ignorés.');
        return EXIT_SUCCESS;
    }

    private static function makeCode(): string
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $max = strlen($alphabet) - 1;
        $code = '';
        for ($i = 0; $i < 12; $i++) {
            $code .= $alphabet[random_int(0, $max)];
        }
        return $code;
    }
}
