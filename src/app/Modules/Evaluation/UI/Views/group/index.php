<?= $this->extend('Modules\Evaluation\UI\Views\layout') ?>
<?= $this->section('content') ?>
<section>
    <h1>Groupes</h1>
    <p class="sub">Liste des groupes d'étudiants.</p>

    <p><a class="btn-primary" href="<?= site_url('evaluations/groupes/nouveau') ?>">+ Nouveau groupe</a></p>

    <table class="ranking">
        <thead>
            <tr>
                <th class="col-code">Code</th>
                <th>Nom</th>
                <th class="col-num">Membres</th>
                <th class="col-num">Évals</th>
                <th class="col-action">Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($groups as $g): ?>
            <tr>
                <td class="col-code"><?= esc($g['code'] ?? '—') ?></td>
                <td><?= esc($g['name']) ?></td>
                <td class="col-num"><?= $g['members'] ?></td>
                <td class="col-num"><?= $g['evaluated'] ? '✓' : '—' ?></td>
                <td class="col-action actions">
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