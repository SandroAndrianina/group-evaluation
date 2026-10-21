<?php

declare(strict_types=1);

namespace Modules\Attendance\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Config\Database;

final class ResetStudentAccessCommand extends BaseCommand
{
    protected $group = 'Attendance';
    protected $name = 'attendance:reset-student-access';
    protected $description = 'Réinitialise la passkey d’un étudiant et affiche un nouveau code d’inscription à lui remettre en privé.';
    protected $usage = 'attendance:reset-student-access <matricule>';
    protected $arguments = ['matricule' => 'Matricule exact de l’étudiant.'];

    public function run(array $params): int
    {
        $matricule = trim((string) ($params[0] ?? ''));
        if ($matricule === '') {
            CLI::error('Usage : php spark attendance:reset-student-access <matricule>');
            return EXIT_ERROR;
        }
        $db = Database::connect();
        $student = $db->table('students')->where('matricule', $matricule)->get()->getRowArray();
        if (!$student) {
            CLI::error('Matricule introuvable.');
            return EXIT_ERROR;
        }
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $code = '';
        for ($i = 0, $max = strlen($alphabet) - 1; $i < 12; $i++) {
            $code .= $alphabet[random_int(0, $max)];
        }
        $db->transBegin();
        try {
            $db->table('passkeys')->where('student_id', $student['id'])->delete();
            $row = $db->table('attendance_enrollment_codes')->where('student_id', $student['id'])->get()->getRowArray();
            $values = ['code_hash' => password_hash($code, PASSWORD_DEFAULT), 'issued_at' => gmdate('Y-m-d H:i:s'), 'used_at' => null];
            if ($row) {
                $db->table('attendance_enrollment_codes')->where('student_id', $student['id'])->update($values);
            } else {
                $values['student_id'] = (int) $student['id'];
                $db->table('attendance_enrollment_codes')->insert($values);
            }
            if ($db->transStatus() === false) {
                throw new \RuntimeException('La transaction a échoué.');
            }
            $db->transCommit();
        } catch (\Throwable $e) {
            $db->transRollback();
            CLI::error('Réinitialisation annulée : ' . $e->getMessage());
            return EXIT_ERROR;
        }
        CLI::write('Nouveau code pour ' . $student['name'] . ' (' . $matricule . ') : ' . $code, 'green');
        CLI::write('Remets-le directement à l’étudiant, sans le publier dans un groupe.');
        return EXIT_SUCCESS;
    }
}
