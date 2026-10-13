<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= esc($title ?? 'Évaluation') ?></title>
    <link rel="stylesheet" href="<?= base_url('css/app.css') ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>

<input type="checkbox" id="menu-toggle" hidden>
<label for="menu-toggle" class="burger" aria-label="Menu">☰</label>

<aside class="sidebar">
    <div class="brand">Marketing Digital</div>
    <nav>
        <a href="<?= site_url('evaluations/new') ?>" class="<?= str_contains(current_url(), '/new') ? 'on' : '' ?>">Évaluer</a>
        <a href="<?= site_url('evaluations/classement') ?>" class="<?= str_contains(current_url(), 'classement') ? 'on' : '' ?>">Classement</a>
        <a href="<?= site_url('evaluations/notes-enseignant') ?>" class="<?= str_contains(current_url(), 'notes-enseignant') ? 'on' : '' ?>">Enseignant</a>
        <a href="<?= site_url('evaluations/groupes') ?>" class="<?= str_contains(current_url(), 'groupes') ? 'on' : '' ?>">Groupes</a>
        <a href="<?= site_url('evaluations/liste') ?>" class="<?= str_contains(current_url(), '/liste') ? 'on' : '' ?>">Évaluations</a>
    </nav>
</aside>

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