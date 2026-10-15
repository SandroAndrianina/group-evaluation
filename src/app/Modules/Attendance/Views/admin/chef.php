<?= $this->extend('Modules\Evaluation\UI\Views\layout') ?>

<?= $this->section('content') ?>
<h1>Écran de projection</h1>

<?php if ($open === null): ?>
    <p class="sub">Aucun appel en cours. Ouvre un appel depuis le <a href="<?= site_url('attendance/admin') ?>">dashboard</a>.</p>
<?php else: ?>
    <div class="card">
        <strong><?= esc($open['label']) ?></strong>
        <p class="sub">Scannez le QR avec votre téléphone, puis validez avec Face ID / empreinte.</p>
    </div>

    <div style="display:flex; justify-content:center; margin:2rem 0;">
        <canvas id="qr" width="600" height="600" style="background:#fff; padding:16px; border-radius:12px;"></canvas>
    </div>

    <p id="timer" style="text-align:center; font-size:1.5rem; font-weight:600;">…</p>
    <p id="status" style="text-align:center;" class="sub">Chargement…</p>
<?php endif; ?>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="https://cdn.jsdelivr.net/npm/qrcode@1.5.3/build/qrcode.min.js"></script>
<script>
(() => {
    const canvas = document.getElementById('qr');
    if (!canvas) return;

    const base = '<?= base_url() ?>';
    const statusEl = document.getElementById('status');
    const timerEl  = document.getElementById('timer');

    async function rotate() {
        try {
            const r = await fetch(base + 'attendance/admin/qr/token', {
                headers: { Accept: 'application/json' },
                cache: 'no-store'
            });

            if (!r.ok) {
                statusEl.textContent = 'Appel fermé ou indisponible.';
                timerEl.textContent = '';
                return;
            }

            const { token } = await r.json();
            const url = base + 'attendance/qr/verify?token=' + token;

            await QRCode.toCanvas(canvas, url, { width: 600, margin: 2 });

            statusEl.textContent = 'QR à jour';

            let s = 10;
            timerEl.textContent = s + ' s';
            const iv = setInterval(() => {
                s -= 1;
                timerEl.textContent = s + ' s';
                if (s <= 0) clearInterval(iv);
            }, 1000);
        } catch (e) {
            statusEl.textContent = 'Erreur réseau, nouvelle tentative…';
        }
    }

    rotate();
    setInterval(rotate, 10000);
})();
</script>
<?= $this->endSection() ?>