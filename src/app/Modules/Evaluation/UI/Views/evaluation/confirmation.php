<?= $this->extend('Modules\Evaluation\UI\Views\layout') ?>

<?= $this->section('content') ?>
<div class="steps" aria-hidden="true"><i class="on"></i><i class="on"></i><i class="on"></i><i class="on"></i></div>

<section>
    <h1>Merci !</h1>
    <p class="sub">Ton évaluation a bien été enregistrée.</p>

    <div class="card">
        <a class="btn-primary" href="<?= site_url('evaluations/new') ?>">Nouvelle évaluation</a>
        <a class="btn-secondary" href="<?= site_url('evaluations/classement') ?>">Voir le classement</a>
    </div>
</section>
<?= $this->endSection() ?>