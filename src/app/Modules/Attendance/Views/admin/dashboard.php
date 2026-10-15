<?= $this->extend('Modules\Evaluation\UI\Views\layout') ?>

<?= $this->section('content') ?>
<h1>Appel</h1>
<?php if (! empty($error)): ?><p class="err" role="alert"><?= esc($error) ?></p><?php endif; ?>

<?php if ($open === null): ?>
    <p class="sub">Aucun appel en cours.</p>
    <form method="post" action="<?= site_url('attendance/admin/sessions/open') ?>">
        <label for="label">Titre de l'appel</label>
        <input type="text" id="label" name="label" value="<?= esc($defaultLabel) ?>" maxlength="150" required>
        <div class="actions"><button type="submit">Ouvrir l'appel</button></div>
    </form>
<?php else: ?>
    <?php
    $openedAt = (new DateTime($open['opened_at'], new DateTimeZone('UTC')))
        ->setTimezone(new DateTimeZone('Indian/Antananarivo'))->format('H:i');
    ?>
    <div class="card">
        <strong><?= esc($open['label']) ?></strong>
        <p class="sub">Ouvert à <?= esc($openedAt) ?></p>
        <p class="count"><span id="count">0</span> / <?= (int) $total ?> présents</p>
    </div>

    <ul id="present" class="list"
        data-url="<?= esc(site_url('attendance/admin/sessions/' . (int) $open['id'] . '/present'), 'attr') ?>"></ul>

    <form method="post" action="<?= site_url('attendance/admin/sessions/' . (int) $open['id'] . '/close') ?>"
          onsubmit="return confirm('Fermer l\'appel ?');">
        <div class="actions"><button type="submit" class="secondary">Fermer l'appel</button></div>
    </form>
<?php endif; ?>

<p class="foot"><a href="<?= site_url('attendance/admin/logout') ?>">Se déconnecter</a></p>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
(() => {
    const list = document.getElementById('present');
    if (!list) return;
    const count = document.getElementById('count');
    const time = (s) => new Date(s.replace(' ', 'T') + 'Z')
        .toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });

    const refresh = async () => {
        try {
            const res = await fetch(list.dataset.url, { headers: { Accept: 'application/json' }, cache: 'no-store' });
            if (!res.ok) return;
            const data = await res.json();
            count.textContent = data.count;
            list.replaceChildren(...data.present.map((p) => {
                const li = document.createElement('li');
                li.textContent = `${p.name} · ${time(p.marked_at)}`;
                if (Number(p.flagged) === 1) li.classList.add('flag');
                return li;
            }));
        } catch (e) { /* nouvel essai au prochain tour */ }
    };

    refresh();
    setInterval(refresh, 5000);
})();
</script>
<?= $this->endSection() ?>