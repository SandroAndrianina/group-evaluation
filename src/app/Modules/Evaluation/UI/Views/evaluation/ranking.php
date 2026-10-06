<?= $this->extend('Modules\Evaluation\UI\Views\layout') ?>

<?= $this->section('content') ?>
<section>
    <h1>Classement</h1>
    <p class="sub">Moyenne des totaux par groupe évalué.</p>

    <table class="ranking">
        <thead>
            <tr><th>#</th><th>Groupe</th><th>Moyenne</th><th>Éval.</th></tr>
        </thead>
        <tbody>
        <?php foreach ($rows as $row): ?>
            <tr>
                <td><?= $row['rank'] !== null ? $row['rank'] : '—' ?></td>
                <td><?= esc($row['name']) ?></td>
                <td>
                    <?php if ($row['average'] === null): ?>
                        —
                    <?php else: ?>
                        <?= esc(str_replace('.', ',', (string) $row['average'])) ?> /10
                    <?php endif; ?>
                </td>
                <td><?= $row['count'] ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <p><a class="btn-secondary" href="<?= site_url('evaluations/new') ?>">Nouvelle évaluation</a></p>
</section>
<?= $this->endSection() ?>