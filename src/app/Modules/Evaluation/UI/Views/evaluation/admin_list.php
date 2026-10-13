<?= $this->extend('Modules\Evaluation\UI\Views\layout') ?>
<?= $this->section('content') ?>
<section>
    <h1>Évaluations</h1>
    <p>
        <a class="btn-primary" href="<?= site_url('evaluations/new') ?>">+ Nouvelle</a>
        <a class="btn-secondary" href="<?= site_url('evaluations/liste/export') ?>">Export CSV</a>
    </p>
    <table class="ranking">
        <thead><tr><th>Évaluateur → Évalué</th><th>Total</th><th>Ens.</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($rows as $row): ?>
            <tr>
                <td><?= esc($row['evaluator']) ?> → <?= esc($row['evaluated']) ?></td>
                <td><?= esc(str_replace('.', ',', (string) $row['total'])) ?> /10</td>
                <td><?= esc(str_replace('.', ',', (string) $row['teacher'])) ?></td>
                <td class="actions">
                    <a href="<?= site_url('evaluations/liste/' . $row['id']) ?>">Modifier</a>
                    <a href="<?= site_url('evaluations/liste/' . $row['id'] . '/supprimer') ?>"
                       onclick="return confirm('Supprimer cette évaluation ?')">Supprimer</a>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if ($rows === []): ?>
            <tr><td colspan="4">Aucune évaluation.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</section>
<?= $this->endSection() ?>