<?= $this->extend('Modules\Evaluation\UI\Views\layout') ?>

<?= $this->section('content') ?>
<h1>Écran de projection</h1>
<?php if ($open === null): ?>
    <p class="sub">Aucun appel en cours. <a href="<?= site_url('attendance/admin') ?>">Ouvre un appel</a> depuis le tableau de bord.</p>
<?php else: ?>
    <div class="card">
        <strong><?= esc($open['label']) ?></strong>
        <p class="sub">Les étudiants scannent ce QR avec l’appareil photo du téléphone.</p>
    </div>
    <div style="display:flex;justify-content:center;margin:1.5rem 0;">
        <canvas id="qr" width="520" height="520" style="width:min(80vw,520px);height:auto;aspect-ratio:1;background:#fff;padding:14px;border-radius:12px;"></canvas>
    </div>
    <p id="timer" style="text-align:center;font-size:1.5rem;font-weight:700;">…</p>
    <p id="status" style="text-align:center;" class="sub">Chargement du QR…</p>
<?php endif; ?>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="https://cdn.jsdelivr.net/npm/qrcode@1.5.3/build/qrcode.min.js"></script>
<script>
(() => {
    const canvas = document.getElementById('qr');
    if (!canvas) return;
    const tokenUrl = '<?= esc(site_url('attendance/admin/qr/token'), 'js') ?>';
    const scanUrl = '<?= esc(site_url('attendance/scan'), 'js') ?>';
    const statusEl = document.getElementById('status');
    const timerEl = document.getElementById('timer');
    let expiresAt = 0;
    let retryAt = 0;
    let rotating = false;
    async function rotate() {
        if (rotating) return;
        rotating = true;
        const requestStartedAt = Date.now();
        try {
            const response = await fetch(tokenUrl, {headers:{Accept:'application/json'}, cache:'no-store'});
            const data = await response.json();
            if (!response.ok || !data.token) throw new Error(data.message || data.error || 'Appel fermé ou indisponible.');
            // Use a local duration, not the server's epoch, so laptop clock skew won't cause a tight refresh loop.
            const latency = Date.now() - requestStartedAt;
            const remainingMs = Number(data.expires_in || 10) * 1000 - latency;
            if (remainingMs < 1000) throw new Error('Réseau trop lent pour afficher ce QR. Nouvelle tentative…');
            await QRCode.toCanvas(canvas, scanUrl + '?token=' + encodeURIComponent(data.token), {width:520, margin:2, errorCorrectionLevel:'M'});
            expiresAt = Date.now() + remainingMs;
            retryAt = 0;
            statusEl.textContent = 'QR à jour · scanne maintenant';
        } catch (error) {
            expiresAt = 0;
            retryAt = Date.now() + 3000;
            statusEl.textContent = error.message || 'Erreur réseau, nouvelle tentative…';
            timerEl.textContent = '';
        } finally {
            rotating = false;
        }
    }
    function tick() {
        if (expiresAt) {
            const remaining = Math.max(0, Math.ceil((expiresAt - Date.now()) / 1000));
            timerEl.textContent = remaining + ' s';
            if (remaining <= 0) rotate();
        } else if (!rotating && Date.now() >= retryAt) {
            rotate();
        }
    }
    rotate();
    window.setInterval(tick, 250);
})();
</script>
<?= $this->endSection() ?>
