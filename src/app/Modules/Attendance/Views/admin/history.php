<?= $this->extend('Modules\Evaluation\UI\Views\layout') ?>

<?= $this->section('content') ?>
<h1>Historique des appels</h1>
<p class="sub">Chaque export contient toute la classe : présents et absents.</p>
<?php if (empty($sessions)): ?>
    <p class="sub">Aucun appel enregistré.</p>
<?php else: ?>
    <div class="table-responsive">
        <table>
            <thead><tr><th>Appel</th><th>État</th><th>Présents</th><th>Actions</th></tr></thead>
            <tbody>
            <?php foreach ($sessions as $item): ?>
                <tr>
                    <td><?= esc($item['label']) ?></td>
                    <td><?= esc($item['status'] === 'open' ? 'En cours' : 'Fermé') ?></td>
                    <td><?= (int) $item['present_count'] ?></td>
                    <td><a href="<?= site_url('attendance/admin/sessions/' . (int) $item['id'] . '/export') ?>">Télécharger le CSV</a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
<p class="foot"><a href="<?= site_url('attendance/admin') ?>">← Retour au tableau de bord</a></p>
<?= $this->endSection() ?>
