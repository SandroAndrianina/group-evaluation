<?= $this->extend('Modules\Evaluation\UI\Views\layout') ?>

<?= $this->section('content') ?>
<section>
    <h1>Note de l'enseignant</h1>
    <p class="sub">Complète la note (0 à 2) pour chaque évaluation.</p>

    <table class="ranking">
        <thead>
            <tr><th>Évaluateur → Évalué</th><th>Ens.</th><th>Total</th><th></th></tr>
        </thead>
        <tbody>
        <?php foreach ($rows as $row): ?>
            <tr>
                <td><?= esc($row['evaluator']) ?> → <?= esc($row['evaluated']) ?></td>
                <td><?= esc(str_replace('.', ',', (string) $row['teacher'])) ?></td>
                <td><?= esc(str_replace('.', ',', (string) $row['total'])) ?> /10</td>
                <td><a href="<?= site_url('evaluations/notes-enseignant/' . $row['id']) ?>">Modifier</a></td>
            </tr>
        <?php endforeach; ?>
        <?php if ($rows === []): ?>
            <tr><td colspan="4">Aucune évaluation.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</section>
<?= $this->endSection() ?>