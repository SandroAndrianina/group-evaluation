<?= $this->extend('Modules\Evaluation\UI\Views\layout') ?>
<?= $this->section('content') ?>
<section>
    <h1>Classement</h1>
    <p class="sub">Moyenne des totaux par groupe évalué.</p>

    <div class="toolbar no-print">
        <a class="btn-secondary" href="<?= site_url('evaluations/classement/export-csv') ?>">Export CSV</a>
        <button type="button" class="btn-secondary" onclick="window.print()">Export PDF</button>
    </div>

    <table class="ranking">
        <thead>
            <tr>
                <th class="col-rank">#</th>
                <th class="col-code">Code</th>
                <th>Nom</th>
                <th>Membres</th>
                <th class="col-num">Moyenne</th>
                <th class="col-num">Éval.</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($rows as $row): ?>
            <tr>
                <td class="col-rank"><?= $row['rank'] ?? '—' ?></td>
                <td class="col-code"><?= esc($row['code']) ?></td>
                <td><?= esc($row['name']) ?></td>
                <td>
                    <?php if ($row['members'] !== []): ?>
                        <ul class="members">
                            <?php foreach ($row['members'] as $m): ?>
                                <li><?= esc($m['matricule']) ?> · <?= esc($m['first_name']) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <span class="muted">—</span>
                    <?php endif; ?>
                </td>
                <td class="col-num"><?= $row['average'] === null ? '—' : esc(str_replace('.', ',', (string) $row['average'])) . ' /10' ?></td>
                <td class="col-num"><?= $row['count'] ?></td>
            </tr>
        <?php endforeach; ?>
        <?php if ($rows === []): ?>
            <tr><td colspan="6">Aucune évaluation.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>

    <p class="no-print"><a class="btn-secondary" href="<?= site_url('evaluations/new') ?>">Nouvelle évaluation</a></p>
</section>
<?= $this->endSection() ?>