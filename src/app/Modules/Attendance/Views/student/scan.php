<?php
/** @var array $session @var array $students @var string $token */
$tokenSafe = esc($token, 'js');
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="<?= esc(csrf_header(), 'attr') ?>" content="<?= esc(csrf_hash(), 'attr') ?>">
    <meta name="theme-color" content="#172554">
    <title>Présence · <?= esc($session['label']) ?></title>
    <style>
        :root{color-scheme:light;--ink:#172033;--muted:#667085;--line:#e4e8f0;--blue:#1d4ed8;--bg:#f5f7fb;--ok:#166534;--bad:#991b1b}
        *{box-sizing:border-box}body{margin:0;background:var(--bg);font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;color:var(--ink);line-height:1.45}
        main{width:min(100% - 28px,520px);margin:24px auto 44px}.brand{font-size:.8rem;font-weight:800;letter-spacing:.12em;color:var(--blue);text-transform:uppercase}
        h1{font-size:1.7rem;line-height:1.15;margin:.45rem 0}.sub{color:var(--muted);margin:.45rem 0 1.2rem}.session{background:#e8efff;border:1px solid #cfddff;border-radius:14px;padding:14px 16px;margin:18px 0 20px}
        .session strong{display:block}.tabs{display:grid;grid-template-columns:1fr 1fr;gap:8px;margin:20px 0 12px}.tab{border:1px solid var(--line);background:white;color:var(--ink);border-radius:10px;padding:12px 8px;font-weight:700;font-size:.91rem}.tab[aria-selected="true"]{background:#172554;color:#fff;border-color:#172554}
        .panel{background:white;border:1px solid var(--line);border-radius:16px;padding:18px;box-shadow:0 8px 24px rgba(22,34,60,.045)}.panel h2{font-size:1.15rem;margin:0 0 8px}
        label{display:block;font-weight:650;font-size:.9rem;margin:14px 0 6px}input,select{display:block;width:100%;min-height:48px;padding:11px 12px;border:1px solid #cbd2df;border-radius:10px;background:#fff;font:inherit;color:inherit}
        input:focus,select:focus,button:focus-visible{outline:3px solid #93c5fd;outline-offset:2px}.hint{font-size:.84rem;color:var(--muted);margin:6px 0}.primary{display:block;width:100%;border:0;border-radius:10px;min-height:49px;padding:12px 16px;background:var(--blue);color:white;font-weight:750;font-size:1rem;margin-top:18px;cursor:pointer}.primary:disabled{opacity:.6;cursor:wait}.message{margin-top:15px;padding:12px;border-radius:10px;display:none;overflow-wrap:anywhere}.message.show{display:block}.message.ok{background:#dcfce7;color:var(--ok)}.message.bad{background:#fee2e2;color:var(--bad)}.foot{text-align:center;color:var(--muted);font-size:.8rem;margin:20px 0}
        [hidden]{display:none!important}
    </style>
</head>
<body>
<main>
    <div class="brand">Présence · Classe</div>
    <h1>Confirmer ma présence</h1>
    <p class="sub">Scanne le QR affiché par le chef de classe, puis valide avec Face ID, Touch ID ou l’empreinte Android.</p>
    <div class="session"><strong><?= esc($session['label']) ?></strong><span>Appel en cours · Le code QR expire après 10 secondes.</span></div>

    <div class="tabs" role="tablist" aria-label="Type de vérification">
        <button type="button" class="tab" id="tab-login" role="tab" aria-selected="true" aria-controls="panel-login">Déjà inscrit</button>
        <button type="button" class="tab" id="tab-register" role="tab" aria-selected="false" aria-controls="panel-register">Première fois</button>
    </div>

    <section class="panel" id="panel-login" role="tabpanel" aria-labelledby="tab-login">
        <h2>Je suis déjà inscrit</h2>
        <p class="hint">Choisis ton nom, puis confirme avec ta passkey. Une seule présence sera enregistrée pour cet appel.</p>
        <label for="login-student">Ton nom complet</label>
        <input type="search" id="login-search" placeholder="Rechercher un nom…" autocomplete="off" aria-label="Filtrer les noms">
        <select id="login-student" required>
            <option value="">Choisir mon nom</option>
            <?php foreach ($students as $student): ?>
                <option value="<?= (int) $student['id'] ?>"><?= esc($student['name']) ?> · …<?= esc(substr((string) $student['matricule'], -3)) ?></option>
            <?php endforeach; ?>
        </select>
        <button type="button" class="primary" id="login-button">Valider avec ma passkey</button>
        <div class="message" id="login-message" role="status" aria-live="polite"></div>
    </section>

    <section class="panel" id="panel-register" role="tabpanel" aria-labelledby="tab-register" hidden>
        <h2>Créer ma passkey</h2>
        <p class="hint">Le code individuel confirme que tu es autorisé à associer ce nom à ton téléphone. Il ne doit pas être partagé.</p>
        <label for="register-student">Ton nom complet</label>
        <input type="search" id="register-search" placeholder="Rechercher un nom…" autocomplete="off" aria-label="Filtrer les noms">
        <select id="register-student" required>
            <option value="">Choisir mon nom</option>
            <?php foreach ($students as $student): ?>
                <option value="<?= (int) $student['id'] ?>" data-matricule="<?= esc($student['matricule'], 'attr') ?>"><?= esc($student['name']) ?> · …<?= esc(substr((string) $student['matricule'], -3)) ?></option>
            <?php endforeach; ?>
        </select>
        <label for="matricule">Matricule</label>
        <input id="matricule" inputmode="numeric" autocomplete="off" maxlength="20" placeholder="Ton matricule exact" required>
        <label for="enrollment-code">Code individuel d’inscription</label>
        <input id="enrollment-code" autocapitalize="characters" spellcheck="false" autocomplete="one-time-code" maxlength="20" placeholder="Code remis par le chef" required>
        <button type="button" class="primary" id="register-button">Créer ma passkey et marquer ma présence</button>
        <div class="message" id="register-message" role="status" aria-live="polite"></div>
    </section>
    <p class="foot">La biométrie est vérifiée par ton téléphone. Le site ne reçoit ni ton empreinte ni ton visage.</p>
</main>
<script>
(() => {
    'use strict';
    const token = '<?= $tokenSafe ?>';
    const csrfMeta = document.querySelector('meta[name="<?= esc(csrf_header(), 'attr') ?>"]');
    const headers = {'Content-Type': 'application/json', 'Accept': 'application/json'};
    if (csrfMeta) headers[csrfMeta.name] = csrfMeta.content;

    const $ = (id) => document.getElementById(id);
    const b64decode = (value) => {
        const normalized = value.replace(/-/g, '+').replace(/_/g, '/');
        const binary = atob(normalized + '='.repeat((4 - normalized.length % 4) % 4));
        const bytes = new Uint8Array(binary.length);
        for (let i = 0; i < binary.length; i++) bytes[i] = binary.charCodeAt(i);
        return bytes.buffer;
    };
    const b64encode = (buffer) => {
        const bytes = new Uint8Array(buffer);
        let binary = '';
        for (let i = 0; i < bytes.length; i++) binary += String.fromCharCode(bytes[i]);
        return btoa(binary).replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/g, '');
    };
    async function post(url, body) {
        // CI4 may regenerate CSRF after every POST; refresh the header before each next request.
        if (csrfMeta) headers[csrfMeta.name] = csrfMeta.content;
        const response = await fetch(url, {method: 'POST', headers, body: JSON.stringify(body), cache: 'no-store', credentials: 'same-origin'});
        const data = await response.json().catch(() => ({}));
        if (data.csrf && csrfMeta) {
            csrfMeta.content = data.csrf;
            headers[csrfMeta.name] = data.csrf;
        }
        if (!response.ok || data.ok === false) throw new Error(data.message || 'Une erreur est survenue. Réessaie.');
        return data;
    }
    function showMessage(kind, message) {
        const box = $(kind + '-message');
        box.textContent = message;
        box.className = 'message show ' + (kind === 'success' ? 'ok' : 'bad');
    }
    function showResult(kind, data) {
        const box = $(kind + '-message');
        box.textContent = data.message || 'Présence confirmée.';
        box.className = 'message show ok';
    }
    function setBusy(button, busy, text) {
        button.disabled = busy;
        if (busy) { button.dataset.oldText = button.textContent; button.textContent = text || 'Validation…'; }
        else if (button.dataset.oldText) { button.textContent = button.dataset.oldText; delete button.dataset.oldText; }
    }
    function setupFilter(searchId, selectId) {
        const search = $(searchId), select = $(selectId);
        search.addEventListener('input', () => {
            const needle = search.value.trim().toLocaleLowerCase('fr');
            Array.from(select.options).forEach((option, index) => {
                if (index === 0) return;
                option.hidden = !option.text.toLocaleLowerCase('fr').includes(needle);
            });
            if (select.selectedOptions[0]?.hidden) select.value = '';
        });
    }
    setupFilter('login-search', 'login-student');
    setupFilter('register-search', 'register-student');
    $('tab-login').addEventListener('click', () => {
        $('tab-login').setAttribute('aria-selected', 'true'); $('tab-register').setAttribute('aria-selected', 'false');
        $('panel-login').hidden = false; $('panel-register').hidden = true;
    });
    $('tab-register').addEventListener('click', () => {
        $('tab-login').setAttribute('aria-selected', 'false'); $('tab-register').setAttribute('aria-selected', 'true');
        $('panel-login').hidden = true; $('panel-register').hidden = false;
    });

    $('register-button').addEventListener('click', async () => {
        const button = $('register-button');
        const studentId = Number($('register-student').value);
        const matricule = $('matricule').value.trim();
        const enrollmentCode = $('enrollment-code').value.trim().toUpperCase();
        if (!studentId || !matricule || !enrollmentCode) { showMessage('register', 'Remplis les trois champs avant de continuer.'); return; }
        if (!window.PublicKeyCredential || !navigator.credentials) { showMessage('register', 'Ce navigateur ne prend pas en charge les passkeys. Essaie Safari récent ou Chrome récent.'); return; }
        setBusy(button, true, 'Préparation de la passkey…');
        try {
            const options = await post('<?= esc(site_url('attendance/passkey/register/options'), 'js') ?>', {token, student_id: studentId, matricule, enrollment_code: enrollmentCode});
            options.publicKey.challenge = b64decode(options.publicKey.challenge);
            options.publicKey.user.id = b64decode(options.publicKey.user.id);
            if (options.publicKey.excludeCredentials) options.publicKey.excludeCredentials.forEach((item) => item.id = b64decode(item.id));
            setBusy(button, true, 'Confirme sur ton téléphone…');
            const credential = await navigator.credentials.create({publicKey: options.publicKey});
            const result = await post('<?= esc(site_url('attendance/passkey/register/finish'), 'js') ?>', {
                student_id: studentId, enrollment_code: enrollmentCode, attendanceChallenge: options.attendanceChallenge,
                response: {clientDataJSON: b64encode(credential.response.clientDataJSON), attestationObject: b64encode(credential.response.attestationObject)}
            });
            showResult('register', result);
            button.textContent = 'Présence confirmée';
        } catch (error) {
            showMessage('register', error.name === 'NotAllowedError' ? 'Action annulée ou délai dépassé. Scanne le QR actuel et réessaie.' : (error.message || 'Échec de la création de passkey.'));
            setBusy(button, false);
        }
    });

    $('login-button').addEventListener('click', async () => {
        const button = $('login-button');
        const studentId = Number($('login-student').value);
        if (!studentId) { showMessage('login', 'Choisis ton nom avant de continuer.'); return; }
        if (!window.PublicKeyCredential || !navigator.credentials) { showMessage('login', 'Ce navigateur ne prend pas en charge les passkeys.'); return; }
        setBusy(button, true, 'Préparation…');
        try {
            const options = await post('<?= esc(site_url('attendance/passkey/login/options'), 'js') ?>', {token, student_id: studentId});
            options.publicKey.challenge = b64decode(options.publicKey.challenge);
            if (options.publicKey.allowCredentials) options.publicKey.allowCredentials.forEach((item) => item.id = b64decode(item.id));
            setBusy(button, true, 'Confirme avec Face ID / empreinte…');
            const credential = await navigator.credentials.get({publicKey: options.publicKey});
            const result = await post('<?= esc(site_url('attendance/passkey/login/finish'), 'js') ?>', {
                student_id: studentId, credentialId: credential.id, attendanceChallenge: options.attendanceChallenge,
                response: {
                    clientDataJSON: b64encode(credential.response.clientDataJSON),
                    authenticatorData: b64encode(credential.response.authenticatorData),
                    signature: b64encode(credential.response.signature)
                }
            });
            showResult('login', result);
            button.textContent = 'Présence confirmée';
        } catch (error) {
            showMessage('login', error.name === 'NotAllowedError' ? 'Action annulée ou délai dépassé. Scanne le QR actuel et réessaie.' : (error.message || 'Authentification refusée.'));
            setBusy(button, false);
        }
    });
})();
</script>
</body>
</html>
