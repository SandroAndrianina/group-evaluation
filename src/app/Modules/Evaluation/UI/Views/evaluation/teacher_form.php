<?= $this->extend('Modules\Evaluation\UI\Views\layout') ?>

<?= $this->section('content') ?>
<section>
    <h1>Note de l'enseignant</h1>
    <p class="sub"><?= esc($evaluator) ?> → <?= esc($evaluated) ?></p>

    <?php if (session('error')): ?>
        <p class="err" role="alert"><?= esc(session('error')) ?></p>
    <?php endif; ?>

    <form method="post" action="<?= site_url('evaluations/notes-enseignant/' . $id) ?>">
        <?= csrf_field() ?>
        <div class="criteria">
            <h2>Note (0 à 2)</h2>
            <div class="scores" role="radiogroup" aria-label="Note de l'enseignant">
                <?php foreach ([0, 0.5, 1, 1.5, 2] as $v): ?>
                    <button type="submit" name="score_teacher" value="<?= $v ?>"
                        <?= ((float) $current === (float) $v) ? 'class="on"' : '' ?>>
                        <?= esc(str_replace('.', ',', (string) $v)) ?>
                    </button>
                <?php endforeach; ?>
            </div>
        </div>
        <p class="total">Total actuel : <strong><?= esc(str_replace('.', ',', (string) $total)) ?></strong> /10</p>
    </form>

    <p><a class="btn-secondary" href="<?= site_url('evaluations/notes-enseignant') ?>">Retour</a></p>
</section>
<?= $this->endSection() ?>