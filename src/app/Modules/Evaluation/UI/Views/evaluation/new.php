<?= $this->extend('Modules\Evaluation\UI\Views\layout') ?>

<?= $this->section('content') ?>
<form id="form" method="post" action="<?= site_url('evaluations') ?>">
<?= csrf_field() ?>
<input type="hidden" name="evaluator_group_id" id="in-evaluator">
<input type="hidden" name="evaluated_group_id" id="in-evaluated">
<input type="hidden" name="score_presentation" id="in-presentation">
<input type="hidden" name="score_swot" id="in-swot">
<input type="hidden" name="score_marketing" id="in-marketing">
<input type="hidden" name="score_oral" id="in-oral">

<div class="steps" aria-hidden="true"><i class="on"></i><i></i><i></i><i></i></div>

<?php if (session('error')): ?>
    <p class="err" role="alert"><?= esc(session('error')) ?></p>
<?php endif; ?>

<section id="step-1">
    <h1>Qui évalue qui ?</h1>
    <p class="sub">Choisis les deux groupes.</p>

    <label for="evaluator">Groupe évaluateur</label>
    <select id="evaluator"></select>

    <label for="evaluated">Groupe évalué</label>
    <select id="evaluated"></select>

    <p class="err" id="err" role="alert"></p>

    <div class="card" id="members" hidden>
        <h2>Membres du groupe évalué</h2>
        <ul id="members-list"></ul>
    </div>
</section>

<section id="step-2" hidden>
    <h1>Notes</h1>
    <p class="sub">Touche une note pour chaque critère.</p>

    <div class="criteria" data-key="presentation">
        <h2>Présentation de l'entreprise</h2>
        <div class="scores" role="radiogroup" aria-label="Présentation de l'entreprise">
            <button type="button" data-value="0">0</button>
            <button type="button" data-value="0.5">0,5</button>
            <button type="button" data-value="1">1</button>
            <button type="button" data-value="1.5">1,5</button>
            <button type="button" data-value="2">2</button>
        </div>
    </div>

    <div class="criteria" data-key="swot">
        <h2>Analyse SWOT</h2>
        <div class="scores" role="radiogroup" aria-label="Analyse SWOT">
            <button type="button" data-value="0">0</button>
            <button type="button" data-value="0.5">0,5</button>
            <button type="button" data-value="1">1</button>
            <button type="button" data-value="1.5">1,5</button>
            <button type="button" data-value="2">2</button>
        </div>
    </div>

    <div class="criteria" data-key="marketing">
        <h2>Action marketing</h2>
        <div class="scores" role="radiogroup" aria-label="Action marketing">
            <button type="button" data-value="0">0</button>
            <button type="button" data-value="0.5">0,5</button>
            <button type="button" data-value="1">1</button>
            <button type="button" data-value="1.5">1,5</button>
            <button type="button" data-value="2">2</button>
        </div>
    </div>

    <div class="criteria" data-key="oral">
        <h2>Présentation orale</h2>
        <div class="scores" role="radiogroup" aria-label="Présentation orale">
            <button type="button" data-value="0">0</button>
            <button type="button" data-value="0.5">0,5</button>
            <button type="button" data-value="1">1</button>
            <button type="button" data-value="1.5">1,5</button>
            <button type="button" data-value="2">2</button>
        </div>
    </div>

    <div class="criteria teacher">
        <h2>Note de l'enseignant</h2>
        <div class="scores" role="radiogroup" aria-label="Note de l'enseignant">
            <button type="button" disabled>0</button>
        </div>
    </div>

    <p class="total">Total : <strong id="total">0</strong> /10</p>
</section>

<section id="step-3" hidden>
    <h1>Remarque</h1>
    <p class="sub">Facultatif (500 caractères max).</p>
    <textarea id="remark" name="remark" maxlength="500" rows="5" placeholder="Commentaire court sur la présentation…"></textarea>
    <p class="count"><span id="remark-count">0</span> / 500</p>
</section>

<section id="step-4" hidden>
    <h1>Récapitulatif</h1>
    <p class="sub">Vérifie avant d'enregistrer.</p>

    <div class="card">
        <dl class="recap">
            <dt>Évaluateur</dt><dd id="r-evaluator">—</dd>
            <dt>Évalué</dt><dd id="r-evaluated">—</dd>
            <dt>Présentation</dt><dd id="r-presentation">—</dd>
            <dt>SWOT</dt><dd id="r-swot">—</dd>
            <dt>Marketing</dt><dd id="r-marketing">—</dd>
            <dt>Oral</dt><dd id="r-oral">—</dd>
            <dt>Enseignant</dt><dd id="r-teacher">0</dd>
            <dt class="strong">Total</dt><dd class="strong" id="r-total">0 /10</dd>
            <dt>Remarque</dt><dd id="r-remark">—</dd>
        </dl>
    </div>
</section>

<div class="bar"><div class="inner">
    <button type="button" id="prev" hidden>Précédent</button>
    <button type="button" id="next" disabled>Suivant</button>
</div></div>
</form>

<script type="application/json" id="groups-data"><?= json_encode($groups, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?></script>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
(() => {
    const groups = JSON.parse(document.getElementById('groups-data').textContent);
    const $ = (id) => document.getElementById(id);
    const form = $('form');
    const evaluator = $('evaluator');
    const evaluated = $('evaluated');
    const next = $('next');
    const prev = $('prev');
    const err = $('err');
    const remark = $('remark');
    const remarkCount = $('remark-count');

    const steps = ['step-1', 'step-2', 'step-3', 'step-4'];
    const dots = document.querySelectorAll('.steps i');
    const CRITERIA = ['presentation', 'swot', 'marketing', 'oral'];
    const scores = { presentation: null, swot: null, marketing: null, oral: null };

    let currentStep = 1;

    // --- Étape 1 : groupes ---
    const fill = (select) => {
        select.append(new Option('— Choisir —', ''));
        groups.forEach((g) => select.append(new Option(g.name, g.id)));
    };
    fill(evaluator);
    fill(evaluated);

    const refreshStep1 = () => {
        const same = evaluator.value !== '' && evaluator.value === evaluated.value;
        err.textContent = same ? "Un groupe ne peut pas s'évaluer lui-même." : '';

        const group = groups.find((g) => String(g.id) === evaluated.value);
        const list = $('members-list');
        list.replaceChildren();
        $('members').hidden = !group;

        if (group) {
            const items = group.members.length
                ? group.members.map((m) => `${m.matricule} · ${m.first_name}`)
                : ['Aucun membre enregistré.'];
            items.forEach((text) => {
                const li = document.createElement('li');
                li.textContent = text;
                list.append(li);
            });
        }

        $('in-evaluator').value = evaluator.value;
        $('in-evaluated').value = evaluated.value;
        render();
    };

    evaluator.addEventListener('change', refreshStep1);
    evaluated.addEventListener('change', refreshStep1);

    // --- Étape 2 : notes ---
    const fmt = (n) => String(n).replace('.', ',');

    const refreshTotal = () => {
        const sum = CRITERIA.reduce((acc, k) => acc + (scores[k] ?? 0), 0);
        $('total').textContent = fmt(sum);
        render();
    };

    document.querySelectorAll('#step-2 .criteria[data-key]').forEach((block) => {
        const key = block.dataset.key;
        block.querySelectorAll('.scores button').forEach((btn) => {
            btn.addEventListener('click', () => {
                scores[key] = parseFloat(btn.dataset.value);
                block.querySelectorAll('.scores button').forEach((b) => {
                    b.setAttribute('aria-pressed', b === btn ? 'true' : 'false');
                });
                $('in-' + key).value = btn.dataset.value;
                refreshTotal();
            });
        });
    });

    // --- Étape 3 : remarque ---
    remark.addEventListener('input', () => {
        remarkCount.textContent = remark.value.length;
    });

    // --- Récap ---
    const groupName = (id) => (groups.find((g) => String(g.id) === String(id))?.name ?? '—');

    const fillRecap = () => {
        $('r-evaluator').textContent = groupName(evaluator.value);
        $('r-evaluated').textContent = groupName(evaluated.value);
        CRITERIA.forEach((k) => {
            $('r-' + k).textContent = scores[k] === null ? '—' : fmt(scores[k]);
        });
        $('r-teacher').textContent = '0';
        const sum = CRITERIA.reduce((acc, k) => acc + (scores[k] ?? 0), 0);
        $('r-total').textContent = fmt(sum) + ' /10';
        $('r-remark').textContent = remark.value.trim() === '' ? '—' : remark.value.trim();
    };

    // --- Navigation ---
    const allScored = () => CRITERIA.every((k) => scores[k] !== null);

    const canProceed = () => {
        if (currentStep === 1) {
            return Boolean(evaluator.value && evaluated.value && evaluator.value !== evaluated.value);
        }
        if (currentStep === 2) return allScored();
        if (currentStep === 3) return true;
        if (currentStep === 4) return true;
        return false;
    };

    const render = () => {
        steps.forEach((id, i) => {
            const el = $(id);
            if (el) el.hidden = (i !== currentStep - 1);
        });
        dots.forEach((d, i) => d.classList.toggle('on', i < currentStep));
        prev.hidden = currentStep === 1;
        next.disabled = !canProceed();
        next.textContent = currentStep === 4 ? 'Enregistrer' : 'Suivant';
        prev.textContent = currentStep === 4 ? 'Modifier' : 'Précédent';
        if (currentStep === 4) fillRecap();
        window.scrollTo(0, 0);
    };

    next.addEventListener('click', () => {
        if (currentStep < 4) {
            currentStep++;
            render();
        } else {
            next.disabled = true;
            form.submit();
        }
    });

    prev.addEventListener('click', () => {
        if (currentStep > 1) {
            currentStep--;
            render();
        }
    });

    render();
})();
</script>
<?= $this->endSection() ?>