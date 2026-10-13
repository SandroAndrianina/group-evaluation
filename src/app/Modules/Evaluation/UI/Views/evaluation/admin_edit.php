<?= $this->extend('Modules\Evaluation\UI\Views\layout') ?>
<?= $this->section('content') ?>
<section>
    <h1>Modifier l'évaluation</h1>
    <p class="sub"><?= esc($evaluator) ?> → <?= esc($evaluated) ?></p>

    <form method="post" action="<?= site_url('evaluations/liste/' . $id) ?>">
        <?= csrf_field() ?>

        <?php foreach (['presentation' => "Présentation", 'swot' => 'SWOT', 'marketing' => 'Marketing', 'oral' => 'Oral'] as $key => $label): ?>
            <div class="criteria">
                <h2><?= $label ?></h2>
                <div class="scores">
                    <?php foreach ([0, 0.5, 1, 1.5, 2] as $v): ?>
                        <label class="score-opt">
                            <input type="radio" name="score_<?= $key ?>" value="<?= $v ?>"
                                <?= ((float) $scores[$key] === (float) $v) ? 'checked' : '' ?>>
                            <span><?= esc(str_replace('.', ',', (string) $v)) ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>

        <label for="remark">Remarque</label>
        <textarea id="remark" name="remark" maxlength="500" rows="4"><?= esc($remark ?? '') ?></textarea>

        <div class="bar"><div class="inner">
            <a class="btn-secondary" href="<?= site_url('evaluations/liste') ?>">Annuler</a>
            <button type="submit">Enregistrer</button>
        </div></div>
    </form>
</section>
<?= $this->endSection() ?>