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
    protected $group = 'Attendance';
    protected $name = 'attendance:import-students';
    protected $description = 'Importe la classe depuis writable/import/etudiants.csv.';
    protected $usage = 'attendance:import-students [--dry-run]';
    protected $options = ['--dry-run' => 'Teste l’import sans rien enregistrer.'];

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
            CLI::error('Import annulé : aucune donnée enregistrée. Corrige le CSV :');
            foreach ($parsed['errors'] as $error) CLI::write(' - ' . $error);
            return EXIT_ERROR;
        }
        if ($parsed['students'] === []) {
            CLI::error('Aucun étudiant valide trouvé dans le CSV.');
            return EXIT_ERROR;
        }

        $db = Database::connect();
        $created = $updated = $unchanged = 0;
        $db->transBegin();
        try {
            foreach ($parsed['students'] as $matricule => $name) {
                $matricule = (string) $matricule;
                $existing = $db->table('students')->select('id, name')->where('matricule', $matricule)->get()->getRowArray();
                $now = gmdate('Y-m-d H:i:s');
                if ($existing === null) {
                    $db->table('students')->insert(['matricule' => $matricule, 'name' => $name, 'created_at' => $now, 'updated_at' => $now]);
                    $created++;
                } elseif ($existing['name'] !== $name) {
                    $db->table('students')->where('id', $existing['id'])->update(['name' => $name, 'updated_at' => $now]);
                    $updated++;
                } else {
                    $unchanged++;
                }
            }
            if ($db->transStatus() === false) throw new \RuntimeException('La base a refusé une ou plusieurs opérations.');
            if ($dryRun) $db->transRollback(); else $db->transCommit();
        } catch (Throwable $e) {
            $db->transRollback();
            CLI::error('Import annulé : ' . $e->getMessage());
            return EXIT_ERROR;
        }
        CLI::write(sprintf('Créés : %d · Mis à jour : %d · Inchangés : %d · Doublons identiques ignorés : %d', $created, $updated, $unchanged, $parsed['duplicates']), 'green');
        CLI::write($dryRun ? 'Mode test (--dry-run) : aucune modification conservée.' : 'Import enregistré. Lance ensuite attendance:generate-enrollment-codes.', 'yellow');
        return EXIT_SUCCESS;
    }
}
