<?= $this->extend('Modules\Evaluation\UI\Views\layout') ?>
<?= $this->section('content') ?>
<section>
    <h1><?= esc($group['name']) ?> — membres</h1>

    <form method="post" action="<?= site_url('evaluations/groupes/' . $group['id'] . '/membres') ?>" class="row">
        <?= csrf_field() ?>
        <input type="text" name="matricule" placeholder="Matricule" required maxlength="20">
        <input type="text" name="first_name" placeholder="Prénom" required maxlength="80">
        <button type="submit">Ajouter</button>
    </form>

    <table class="ranking">
        <thead><tr><th>Matricule</th><th>Prénom</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($members as $m): ?>
            <tr>
                <td><?= esc($m['matricule']) ?></td>
                <td><?= esc($m['first_name']) ?></td>
                <td><a href="<?= site_url('evaluations/groupes/' . $group['id'] . '/membres/' . $m['id'] . '/supprimer') ?>"
                       onclick="return confirm('Retirer ce membre ?')">Retirer</a></td>
            </tr>
        <?php endforeach; ?>
        <?php if ($members === []): ?>
            <tr><td colspan="3">Aucun membre.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>

    <p><a class="btn-secondary" href="<?= site_url('evaluations/groupes') ?>">Retour</a></p>
</section>
<?= $this->endSection() ?>