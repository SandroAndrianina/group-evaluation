<?= $this->extend('Modules\Evaluation\UI\Views\layout') ?>
<?= $this->section('content') ?>
<section>
    <h1>Évaluations</h1>
    <p class="sub">Toutes les évaluations enregistrées.</p>

    <div class="toolbar">
        <a class="btn-primary" href="<?= site_url('evaluations/new') ?>">+ Nouvelle</a>
        <a class="btn-secondary" href="<?= site_url('evaluations/liste/export') ?>">Export CSV</a>
    </div>

    <table class="ranking">
        <thead>
            <tr>
                <th class="col-rank">#</th>
                <th class="col-code">Éval.</th>
                <th>Nom évaluateur</th>
                <th class="col-code">Évalué</th>
                <th>Nom évalué</th>
                <th class="col-num">Total</th>
                <th class="col-action"></th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($rows as $row): ?>
            <tr>
                <td class="col-rank"><?= $row['id'] ?></td>
                <td class="col-code"><?= esc($row['evaluator_code']) ?></td>
                <td><?= esc($row['evaluator_name']) ?></td>
                <td class="col-code"><?= esc($row['evaluated_code']) ?></td>
                <td><?= esc($row['evaluated_name']) ?></td>
                <td class="col-num"><?= esc(str_replace('.', ',', (string) $row['total'])) ?> /10</td>
                <td class="col-action actions">
                    <a href="<?= site_url('evaluations/liste/' . $row['id']) ?>">Modifier</a>
                    <a href="<?= site_url('evaluations/liste/' . $row['id'] . '/supprimer') ?>"
                       onclick="return confirm('Supprimer cette évaluation ?')">Supprimer</a>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if ($rows === []): ?>
            <tr><td colspan="7">Aucune évaluation.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</section>
<?= $this->endSection() ?>