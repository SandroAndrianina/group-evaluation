<?= $this->extend('Modules\Evaluation\UI\Views\layout') ?>
<?= $this->section('content') ?>
<section>
    <h1>Groupes</h1>
    <p><a class="btn-primary" href="<?= site_url('evaluations/groupes/nouveau') ?>">+ Nouveau groupe</a></p>
    <table class="ranking">
        <thead><tr><th>Nom</th><th>Membres</th><th>Évals</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($groups as $g): ?>
            <tr>
                <td><?= esc($g['name']) ?></td>
                <td><?= $g['members'] ?></td>
                <td><?= $g['evaluated'] ? '✓' : '—' ?></td>
                <td class="actions">
                    <a href="<?= site_url('evaluations/groupes/' . $g['id'] . '/membres') ?>">Membres</a>
                    <a href="<?= site_url('evaluations/groupes/' . $g['id']) ?>">Renommer</a>
                    <?php if (! $g['evaluated']): ?>
                        <a href="<?= site_url('evaluations/groupes/' . $g['id'] . '/supprimer') ?>"
                           onclick="return confirm('Supprimer ce groupe ?')">Supprimer</a>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</section>
<?= $this->endSection() ?>