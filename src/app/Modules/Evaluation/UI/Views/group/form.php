<?= $this->extend('Modules\Evaluation\UI\Views\layout') ?>
<?= $this->section('content') ?>
<section>
    <h1><?= $group ? 'Renommer' : 'Nouveau groupe' ?></h1>
    <form method="post" action="<?= site_url($group ? 'evaluations/groupes/' . $group['id'] : 'evaluations/groupes') ?>">
        <?= csrf_field() ?>
        <label for="name">Nom</label>
        <input type="text" id="name" name="name" required maxlength="100" value="<?= esc($group['name'] ?? '') ?>">
        <div class="bar"><div class="inner">
            <a class="btn-secondary" href="<?= site_url('evaluations/groupes') ?>">Annuler</a>
            <button type="submit">Enregistrer</button>
        </div></div>
    </form>
</section>
<?= $this->endSection() ?>