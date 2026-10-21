<?= $this->extend('Modules\Evaluation\UI\Views\layout') ?>

<?= $this->section('content') ?>
<h1>Appel de présence</h1>
<?php if (! empty($error)): ?><p class="err" role="alert"><?= esc($error) ?></p><?php endif; ?>

<?php if ($open === null): ?>
    <p class="sub">Aucun appel n’est ouvert. Donne un titre puis ouvre l’appel.</p>
    <form method="post" action="<?= site_url('attendance/admin/sessions/open') ?>">
        <?= csrf_field() ?>
        <label for="label">Titre de l’appel</label>
        <input type="text" id="label" name="label" value="<?= esc($defaultLabel) ?>" maxlength="150" required>
        <div class="actions"><button type="submit">Ouvrir l’appel</button></div>
    </form>
<?php else: ?>
    <?php $openedAt = (new DateTimeImmutable($open['opened_at'], new DateTimeZone('UTC')))->setTimezone(new DateTimeZone('Indian/Antananarivo'))->format('H:i'); ?>
    <div class="card">
        <strong><?= esc($open['label']) ?></strong>
        <p class="sub">Ouvert à <?= esc($openedAt) ?> (heure de Madagascar)</p>
        <p class="count"><span id="count">0</span> / <?= (int) $total ?> présents</p>
        <p class="sub"><a href="<?= site_url('attendance/admin/chef') ?>" target="_blank" rel="noopener">Ouvrir l’écran de projection QR ↗</a></p>
    </div>

    <div class="actions" style="display:flex;gap:.6rem;flex-wrap:wrap;margin-bottom:1rem;">
        <button type="button" id="spot-check" class="secondary">Tirer 5 présents au hasard</button>
        <a class="button" href="<?= site_url('attendance/admin/sessions/' . (int) $open['id'] . '/export') ?>">Exporter CSV</a>
    </div>
    <div id="spot-result" class="card" hidden aria-live="polite"></div>

    <ul id="present" class="list" data-url="<?= esc(site_url('attendance/admin/sessions/' . (int) $open['id'] . '/present'), 'attr') ?>"></ul>

    <form method="post" action="<?= site_url('attendance/admin/sessions/' . (int) $open['id'] . '/close') ?>" onsubmit="return confirm('Fermer l’appel ? Les nouveaux scans seront refusés.');">
        <?= csrf_field() ?>
        <div class="actions"><button type="submit" class="secondary">Fermer l’appel</button></div>
    </form>
<?php endif; ?>

<h2 style="margin-top:2rem;">Derniers appels</h2>
<?php if (empty($history)): ?>
    <p class="sub">Aucun historique pour le moment.</p>
<?php else: ?>
    <ul class="list">
        <?php foreach ($history as $item): ?>
            <li>
                <strong><?= esc($item['label']) ?></strong>
                <span class="sub"> · <?= esc($item['status'] === 'open' ? 'En cours' : 'Fermé') ?></span>
                <a href="<?= site_url('attendance/admin/sessions/' . (int) $item['id'] . '/export') ?>">CSV</a>
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>
<p class="foot"><a href="<?= site_url('attendance/admin/history') ?>">Voir tout l’historique</a></p>
<form method="post" action="<?= site_url('attendance/admin/logout') ?>">
    <?= csrf_field() ?>
    <div class="actions"><button type="submit" class="secondary">Se déconnecter</button></div>
</form>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<?= csrf_meta() ?>
<script>
(() => {
    const list = document.getElementById('present');
    const csrfMeta = document.querySelector('meta[name="<?= esc(csrf_header(), 'attr') ?>"]');
    const headers = {'Accept': 'application/json'};
    if (csrfMeta) headers[csrfMeta.name] = csrfMeta.content;
    const time = (s) => new Date(s.replace(' ', 'T') + 'Z').toLocaleTimeString('fr-FR', {hour:'2-digit', minute:'2-digit'});
    async function refresh() {
        if (!list) return;
        try {
            const response = await fetch(list.dataset.url, {headers:{Accept:'application/json'}, cache:'no-store'});
            if (!response.ok) return;
            const data = await response.json();
            document.getElementById('count').textContent = data.count;
            list.replaceChildren(...data.present.map((p) => {
                const li = document.createElement('li');
                const late = Number(p.flagged) === 1 ? ' · à vérifier (retard > 10 min)' : '';
                li.textContent = `${p.name} · ${time(p.marked_at)}${late}`;
                if (Number(p.flagged) === 1) li.classList.add('flag');
                return li;
            }));
        } catch (_) { /* tentative suivante */ }
    }
    refresh();
    if (list) window.setInterval(refresh, 4000);

    const button = document.getElementById('spot-check');
    if (button && list) button.addEventListener('click', async () => {
        const resultBox = document.getElementById('spot-result');
        button.disabled = true;
        button.textContent = 'Tirage…';
        try {
            const tokenInputs = Array.from(document.querySelectorAll('input[name="<?= esc(csrf_token(), 'attr') ?>"]'));
            const tokenInput = tokenInputs[0] || null;
            if (tokenInput) headers['Content-Type'] = 'application/x-www-form-urlencoded';
            const body = new URLSearchParams();
            if (tokenInput) body.set(tokenInput.name, tokenInput.value);
            const response = await fetch('<?= esc(site_url('attendance/admin/sessions/' . (int) ($open['id'] ?? 0) . '/spot-check'), 'js') ?>', {method:'POST', headers, body, cache:'no-store'});
            const data = await response.json();
            if (data.csrf) {
                if (csrfMeta) { csrfMeta.content = data.csrf; headers[csrfMeta.name] = data.csrf; }
                tokenInputs.forEach((input) => input.value = data.csrf);
            }
            if (!response.ok) throw new Error(data.error || 'Le tirage a échoué.');
            resultBox.hidden = false;
            resultBox.replaceChildren();
            const title = document.createElement('strong'); title.textContent = `Contrôle aléatoire · ${data.selected.length} étudiant(s) sur ${data.available}`; resultBox.appendChild(title);
            const ul = document.createElement('ul');
            data.selected.forEach((p) => { const li = document.createElement('li'); li.textContent = `${p.name} · ${p.matricule}`; ul.appendChild(li); });
            resultBox.appendChild(ul);
        } catch (error) { window.alert(error.message || 'Erreur pendant le tirage.'); }
        finally { button.disabled = false; button.textContent = 'Tirer 5 présents au hasard'; }
    });
})();
</script>
<?= $this->endSection() ?>
