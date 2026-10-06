<?php

declare(strict_types=1);

namespace Modules\Evaluation\Infrastructure\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Config\Database;
use Modules\Evaluation\Infrastructure\Import\CsvReader;
use Modules\Evaluation\Infrastructure\Import\SheetImporter;
use Throwable;

final class ImportCommand extends BaseCommand
{
    protected $group       = 'Evaluation';
    protected $name        = 'evaluation:import';
    protected $description = 'Importe groupes.csv et donnees.csv (dossier writable/import) dans MySQL.';
    protected $usage       = 'evaluation:import [--dry-run]';
    protected $options     = ['--dry-run' => 'Teste l\'import sans rien enregistrer.'];

    public function run(array $params): int
    {
        $dryRun   = CLI::getOption('dry-run') !== null;
        $importer = new SheetImporter(Database::connect(), new CsvReader());

        try {
            $result = $importer->import(
                WRITEPATH . 'import/groupes.csv',
                WRITEPATH . 'import/donnees.csv',
                $dryRun,
            );
        } catch (Throwable $e) {
            CLI::error('Import annulé (rien n\'a été enregistré) : ' . $e->getMessage());

            return EXIT_ERROR;
        }

        foreach ($result['details'] as $line) {
            CLI::write('  ' . $line);
        }

        CLI::newLine();
        CLI::write(sprintf(
            'Groupes créés : %d · Membres : %d · Évaluations : %d · Ignorées (déjà présentes) : %d',
            $result['groups'],
            $result['members'],
            $result['evaluations'],
            $result['skipped'],
        ), 'green');
        CLI::write($dryRun ? 'Mode test (--dry-run) : rien n\'a été enregistré.' : 'Import enregistré.', 'yellow');

        return EXIT_SUCCESS;
    }
}