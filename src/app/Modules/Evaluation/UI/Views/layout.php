<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= esc($title ?? 'Évaluation') ?></title>
    <link rel="stylesheet" href="<?= base_url('css/app.css') ?>">
</head>
<body>

<nav class="nav">
    <a href="<?= site_url('evaluations/new') ?>" class="<?= (current_url() === site_url('evaluations/new')) ? 'on' : '' ?>">Évaluer</a>
    <a href="<?= site_url('evaluations/classement') ?>" class="<?= str_contains(current_url(), 'classement') ? 'on' : '' ?>">Classement</a>
    <a href="<?= site_url('evaluations/notes-enseignant') ?>" class="<?= str_contains(current_url(), 'notes-enseignant') ? 'on' : '' ?>">Enseignant</a>
    <a href="<?= site_url('evaluations/groupes') ?>" class="<?= str_contains(current_url(), 'groupes') ? 'on' : '' ?>">Groupes</a>
    <a href="<?= site_url('evaluations/liste') ?>" class="<?= str_contains(current_url(), '/liste') ? 'on' : '' ?>">Évaluations</a>
</nav>

<main class="container">
    <?php if (session('success')): ?>
        <p class="flash ok" role="status"><?= esc(session('success')) ?></p>
    <?php endif; ?>
    <?php if (session('error')): ?>
        <p class="flash err" role="alert"><?= esc(session('error')) ?></p>
    <?php endif; ?>
    <?= $this->renderSection('content') ?>
</main>

<?= $this->renderSection('scripts') ?>
</body>
</html>