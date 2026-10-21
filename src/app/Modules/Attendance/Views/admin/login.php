<?= $this->extend('Modules\Evaluation\UI\Views\layout') ?>

<?= $this->section('content') ?>
<h1>Espace chef de classe</h1>
<p class="sub">Connecte-toi pour gérer les appels et les présences.</p>

<form method="post" action="<?= site_url('attendance/admin/login') ?>" autocomplete="on">
    <?= csrf_field() ?>
    <label for="password">Mot de passe administrateur</label>
    <input type="password" id="password" name="password" autocomplete="current-password" required autofocus>
    <?php if (! empty($error)): ?><p class="err" role="alert"><?= esc($error) ?></p><?php endif; ?>
    <div class="actions"><button type="submit">Se connecter</button></div>
</form>
<?= $this->endSection() ?>
