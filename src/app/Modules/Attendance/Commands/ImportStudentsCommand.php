<?php

declare(strict_types=1);

namespace Modules\Attendance\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Config\Database;
use Modules\Attendance\Libraries\StudentCsvParser;
use Modules\Evaluation\Infrastructure\Import\CsvReader;
use Throwable;

final class ImportStudentsCommand extends BaseCommand
{
    protected $group       = 'Attendance';
    protected $name        = 'attendance:import-students';
    protected $description = 'Importe la liste de la classe (writable/import/etudiants.csv) dans la table students.';
    protected $usage       = 'attendance:import-students [--dry-run]';
    protected $options     = ['--dry-run' => 'Teste l\'import sans rien enregistrer.'];

    public function run(array $params): int
    {
        $dryRun = CLI::getOption('dry-run') !== null;

        try {
            $rows = (new CsvReader())->read(WRITEPATH . 'import/etudiants.csv', 4);
        } catch (Throwable $e) {
            CLI::error($e->getMessage());

            return EXIT_ERROR;
        }

        $parsed = (new StudentCsvParser())->parse($rows);

        if ($parsed['errors'] !== []) {
            CLI::error('Import annulé (rien n\'a été enregistré). Corrige le CSV :');
            foreach ($parsed['errors'] as $error) {
                CLI::write(' - ' . $error);
            }

            return EXIT_ERROR;
        }

        $db        = Database::connect();
        $created   = 0;
        $updated   = 0;
        $unchanged = 0;

        $db->transBegin();

        foreach ($parsed['students'] as $matricule => $name) {
            $matricule = (string) $matricule;
            $existing  = $db->table('students')->select('id, name')->where('matricule', $matricule)->get()->getRowArray();
            $now       = date('Y-m-d H:i:s');

            if ($existing === null) {
                $db->table('students')->insert([
                    'matricule' => $matricule, 'name' => $name, 'created_at' => $now, 'updated_at' => $now,
                ]);
                $created++;
            } elseif ($existing['name'] !== $name) {
                $db->table('students')->where('id', $existing['id'])->update(['name' => $name, 'updated_at' => $now]);
                $updated++;
            } else {
                $unchanged++;
            }
        }

        $dryRun ? $db->transRollback() : $db->transCommit();

        CLI::write(sprintf(
            'Créés : %d · Mis à jour : %d · Inchangés : %d · Doublons ignorés : %d',
            $created, $updated, $unchanged, $parsed['duplicates'],
        ), 'green');
        CLI::write($dryRun ? 'Mode test (--dry-run) : rien n\'a été enregistré.' : 'Import enregistré.', 'yellow');

        return EXIT_SUCCESS;
    }
}