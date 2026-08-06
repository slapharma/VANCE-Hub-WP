<?php
/**
 * Health Discovery Quiz — modal.
 *
 * Styling is the shared modal kit (assets/css/vance-modal-kit.css), a port of
 * the IBD Malnutrition Calculator's design: wash background, one white card,
 * pill badge over an uppercase title, thin progress rail, and large option
 * rows that lift on hover and fill teal when selected.
 *
 * The questions come from vance_quiz_steps() in functions.php — the same
 * definition the dashboard reads to label saved answers and to work out which
 * step a given answer lives on. Do not add a question here.
 *
 * Public API: openQuizModal(step = 1, singleEdit = false), closeQuizModal()
 */

$vance_quiz_saved = is_user_logged_in()
    ? get_user_meta( get_current_user_id(), '_sla_healthcare_quiz_results', true )
    : array();
if ( ! is_array( $vance_quiz_saved ) ) {
    $vance_quiz_saved = array();
}
?>
<div id="vance-quiz-modal" class="vance-mk-scrim" role="dialog" aria-modal="true"
     aria-hidden="true" aria-labelledby="vance-quiz-modal-title">
    <div class="vance-mk" role="document">
        <button type="button" class="vance-mk__close" onclick="closeQuizModal()" aria-label="Close quiz">&times;</button>

        <div class="vance-mk__header">
            <div class="vance-mk__badge">Vance Medical &middot; Health Discovery</div>
            <h2 id="vance-quiz-modal-title" class="vance-mk__title">Health Discovery Quiz</h2>
            <p class="vance-mk__subtitle">Nine short questions so the hub can point you at the research, tools and recipes that actually apply to you. You can change any answer later.</p>
        </div>

        <div class="vance-mk__card">
            <form id="modal-health-quiz-form" novalidate>
                <div class="vance-mk__progress" id="modal-quiz-progress">
                    <div class="vance-mk__progress-meta">
                        <span class="vance-mk__progress-label" id="modal-progress-label">Step 1 of 9</span>
                        <span class="vance-mk__progress-pct" id="modal-progress-pct">0%</span>
                    </div>
                    <div class="vance-mk__progress-track">
                        <div class="vance-mk__progress-fill" id="modal-progress-bar"></div>
                    </div>
                </div>

                <div id="modal-quiz-steps-container"></div>

                <div class="vance-mk__done" id="modal-results-screen" style="display:none;">
                    <div class="vance-mk__done-icon" aria-hidden="true">&#10003;</div>
                    <h3 class="vance-mk__step-title" style="font-size:22px;">Your profile is up to date</h3>
                    <p class="vance-mk__step-subtitle" style="max-width:420px; margin:0 auto 22px;">Thanks, your answers are saved. You can add clinical detail any time from your health profile.</p>
                    <button type="button" class="vance-mk__btn vance-mk__btn--primary vance-mk__btn--auto" onclick="handleQuizCompletion()">View my profile &rarr;</button>
                </div>

                <div class="vance-mk__nav" id="modal-quiz-footer">
                    <button type="button" class="vance-mk__btn vance-mk__btn--ghost" id="modal-btn-prev" style="visibility:hidden;">Back</button>
                    <button type="button" class="vance-mk__btn vance-mk__btn--ghost" id="modal-btn-save" style="display:none;">Save &amp; close</button>
                    <button type="button" class="vance-mk__btn vance-mk__btn--primary" id="modal-btn-next" disabled>Next</button>
                </div>

                <div class="vance-mk__note" id="modal-quiz-note" role="status" aria-live="polite"></div>
            </form>
        </div>

        <p class="vance-mk__footer">Your answers are private to your account and used only to tailor what this hub shows you.</p>
    </div>
</div>

<script>
(function () {
    // Questions come from PHP (vance_quiz_steps) so this file and the dashboard
    // cannot disagree about what is asked, or in what order.
    var quizStepsContent = <?php echo wp_json_encode( vance_quiz_steps() ); ?>;
    var savedData        = <?php echo wp_json_encode( (object) $vance_quiz_saved ); ?>;
    var IS_LOGGED_IN     = <?php echo is_user_logged_in() ? 'true' : 'false'; ?>;
    var AJAX_URL         = <?php echo wp_json_encode( admin_url( 'admin-ajax.php' ) ); ?>;
    var QUIZ_NONCE       = <?php echo wp_json_encode( wp_create_nonce( 'vance_quiz_nonce' ) ); ?>;
    var PROFILE_URL      = <?php echo wp_json_encode( home_url( '/dashboard/?tab=health-profile' ) ); ?>;

    var totalQuizSteps = quizStepsContent.length;
    var currentQuizStep = 1;
    var isSingleEditMode = false;

    // Multi-answer fields are stored as a comma-separated string, so split them
    // back into arrays on the way in.
    var MULTI = {};
    quizStepsContent.forEach(function (s) {
        if (s.type === 'checkbox') { MULTI[s.field] = true; }
        (s.opts || []).forEach(function (o) {
            if (o && typeof o === 'object' && o.depField) { MULTI[o.depField] = true; }
        });
    });

    var quizResults = {};
    Object.keys(savedData || {}).forEach(function (key) {
        var val = savedData[key];
        if (MULTI[key] && typeof val === 'string') {
            quizResults[key] = val.split(',').map(function (s) { return s.trim(); }).filter(Boolean);
        } else {
            quizResults[key] = val;
        }
    });

    var modal     = document.getElementById('vance-quiz-modal');
    var container = document.getElementById('modal-quiz-steps-container');
    var footer    = document.getElementById('modal-quiz-footer');
    var results   = document.getElementById('modal-results-screen');
    var progress  = document.getElementById('modal-quiz-progress');
    var noteEl    = document.getElementById('modal-quiz-note');
    var btnNext   = document.getElementById('modal-btn-next');
    var btnPrev   = document.getElementById('modal-btn-prev');
    var btnSave   = document.getElementById('modal-btn-save');

    function esc(s) {
        return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    }

    function showNote(msg, kind) {
        if (!noteEl) { return; }
        noteEl.textContent = msg;
        noteEl.className = 'vance-mk__note is-visible vance-mk__note--' + (kind || 'error');
    }
    function clearNote() {
        if (noteEl) { noteEl.className = 'vance-mk__note'; noteEl.textContent = ''; }
    }

    <?php // ---- open / close ---- ?>
    window.openQuizModal = function (startStep, singleEdit) {
        isSingleEditMode = !!singleEdit;
        // Clamp: a caller passing a step that no longer exists used to render
        // `undefined.opts` and throw, leaving an empty modal with the page
        // scroll locked behind it.
        var step = parseInt(startStep, 10);
        if (!step || step < 1 || step > totalQuizSteps) { step = 1; }

        clearNote();
        container.style.display = 'block';
        footer.style.display = 'flex';
        progress.style.display = 'block';
        results.style.display = 'none';

        modal.classList.add('is-open');
        modal.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';

        currentQuizStep = step;
        renderQuizStep(currentQuizStep);
    };

    window.closeQuizModal = function () {
        modal.classList.remove('is-open');
        modal.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
    };

    modal.addEventListener('click', function (e) {
        if (e.target === modal) { window.closeQuizModal(); }
    });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && modal.classList.contains('is-open')) { window.closeQuizModal(); }
    });

    <?php // ---- rendering ---- ?>
    function optionMarkup(step, opt, idx, isMulti) {
        var val = (typeof opt === 'object') ? opt.v : opt;
        var txt = (typeof opt === 'object' && opt.t) ? opt.t : val;
        var current = quizResults[step.field];
        var selected = isMulti
            ? (Array.isArray(current) && current.indexOf(val) > -1)
            : current === val;

        var html = '' +
            '<button type="button" class="vance-mk__option' + (selected ? ' is-selected' : '') + '"' +
            ' data-opt="' + idx + '" role="' + (isMulti ? 'checkbox' : 'radio') + '"' +
            ' aria-checked="' + (selected ? 'true' : 'false') + '">' +
                '<span class="vance-mk__option-mark' + (isMulti ? ' vance-mk__option-mark--box' : '') + '">' +
                    (selected ? (isMulti ? '&#10003;' : '<span style="width:8px;height:8px;background:#fff;border-radius:50%;display:block;"></span>') : '') +
                '</span>' +
                '<span><span class="vance-mk__option-label">' + esc(txt) + '</span></span>' +
            '</button>';

        if (typeof opt === 'object' && opt.textInput) {
            html += '' +
                '<div class="vance-mk__dependent" style="display:' + (selected ? 'block' : 'none') + ';">' +
                    (opt.textLabel ? '<label class="vance-mk__dependent-label">' + esc(opt.textLabel) + '</label>' : '') +
                    '<input type="text" class="vance-mk__input" data-txt-field="' + esc(opt.txtField) + '"' +
                    ' value="' + esc(quizResults[opt.txtField] || '') + '" placeholder="Please specify…">' +
                '</div>';
        }

        if (typeof opt === 'object' && opt.depCheckboxes) {
            var depField = opt.depField;
            if (!Array.isArray(quizResults[depField])) { quizResults[depField] = []; }
            var inner = '';
            opt.depCheckboxes.forEach(function (dOpt) {
                var dVal = (typeof dOpt === 'object') ? dOpt.v : dOpt;
                var dSel = quizResults[depField].indexOf(dVal) > -1;
                inner += '' +
                    '<button type="button" class="vance-mk__option' + (dSel ? ' is-selected' : '') + '"' +
                    ' style="padding:11px 13px;" data-dep-field="' + esc(depField) + '" data-dep-val="' + esc(dVal) + '" role="checkbox" aria-checked="' + (dSel ? 'true' : 'false') + '">' +
                        '<span class="vance-mk__option-mark vance-mk__option-mark--box" style="flex:0 0 18px;width:18px;height:18px;">' + (dSel ? '&#10003;' : '') + '</span>' +
                        '<span class="vance-mk__option-label" style="font-size:13px;">' + esc(dVal) + '</span>' +
                    '</button>';
                if (typeof dOpt === 'object' && dOpt.textInput) {
                    inner += '' +
                        '<div style="grid-column:1/-1; display:' + (dSel ? 'block' : 'none') + ';">' +
                            '<input type="text" class="vance-mk__input" data-txt-field="' + esc(dOpt.txtField) + '"' +
                            ' value="' + esc(quizResults[dOpt.txtField] || '') + '" placeholder="Specify other…">' +
                        '</div>';
                }
            });
            html += '' +
                '<div class="vance-mk__dependent" style="display:' + (selected ? 'block' : 'none') + ';">' +
                    '<label class="vance-mk__dependent-label">Select all that apply</label>' +
                    '<div class="vance-mk__dependent-grid">' + inner + '</div>' +
                '</div>';
        }

        return html;
    }

    function renderQuizStep(num) {
        var step = quizStepsContent[num - 1];
        if (!step) { return; }
        var isMulti = step.type === 'checkbox';
        if (isMulti && !Array.isArray(quizResults[step.field])) { quizResults[step.field] = []; }

        var pct = Math.round(((num - 1) / totalQuizSteps) * 100);
        document.getElementById('modal-progress-bar').style.width = pct + '%';
        document.getElementById('modal-progress-label').textContent = 'Step ' + num + ' of ' + totalQuizSteps;
        document.getElementById('modal-progress-pct').textContent = pct + '%';

        var opts = (step.opts || []).map(function (opt, idx) {
            return optionMarkup(step, opt, idx, isMulti);
        }).join('');

        container.innerHTML = '' +
            '<div class="vance-mk__panel">' +
                '<h3 class="vance-mk__step-title">' + esc(step.title) + '</h3>' +
                (step.subtitle ? '<p class="vance-mk__step-subtitle">' + esc(step.subtitle) + '</p>' : '') +
                '<div class="vance-mk__options' + (step.layout === 'grid' ? ' vance-mk__options--grid' : '') + '">' + opts + '</div>' +
            '</div>';

        btnPrev.style.visibility = num === 1 ? 'hidden' : 'visible';
        btnNext.textContent = (num === totalQuizSteps) ? 'Finish' : 'Next';
        btnSave.style.display = isSingleEditMode ? 'block' : 'none';

        checkModalValidity();
    }

    <?php // ---- interaction (delegated: the step markup is re-rendered on every change) ---- ?>
    container.addEventListener('click', function (e) {
        var dep = e.target.closest('[data-dep-field]');
        if (dep) {
            var field = dep.getAttribute('data-dep-field');
            var value = dep.getAttribute('data-dep-val');
            if (!Array.isArray(quizResults[field])) { quizResults[field] = []; }
            var at = quizResults[field].indexOf(value);
            if (at > -1) { quizResults[field].splice(at, 1); } else { quizResults[field].push(value); }
            renderQuizStep(currentQuizStep);
            return;
        }

        var btn = e.target.closest('[data-opt]');
        if (!btn) { return; }
        var step = quizStepsContent[currentQuizStep - 1];
        var opt  = step.opts[parseInt(btn.getAttribute('data-opt'), 10)];
        var val  = (typeof opt === 'object') ? opt.v : opt;

        if (step.type === 'checkbox') {
            var i = quizResults[step.field].indexOf(val);
            if (i > -1) { quizResults[step.field].splice(i, 1); } else { quizResults[step.field].push(val); }
        } else {
            quizResults[step.field] = val;
        }
        renderQuizStep(currentQuizStep);
    });

    // Free-text follow-ups write straight through; re-rendering on each
    // keystroke would blur the field mid-typing.
    container.addEventListener('input', function (e) {
        var field = e.target.getAttribute && e.target.getAttribute('data-txt-field');
        if (!field) { return; }
        quizResults[field] = e.target.value;
        checkModalValidity();
    });

    function checkModalValidity() {
        var step = quizStepsContent[currentQuizStep - 1];
        var val  = quizResults[step.field];
        var valid = step.type === 'checkbox' ? (Array.isArray(val) && val.length > 0) : !!val;

        if (valid) {
            (step.opts || []).forEach(function (opt) {
                if (typeof opt !== 'object') { return; }
                var chosen = step.type === 'checkbox' ? val.indexOf(opt.v) > -1 : val === opt.v;
                if (!chosen) { return; }
                if (opt.textInput && !String(quizResults[opt.txtField] || '').trim()) { valid = false; }
                if (opt.depCheckboxes) {
                    var dep = quizResults[opt.depField];
                    if (!Array.isArray(dep) || !dep.length) {
                        valid = false;
                    } else {
                        opt.depCheckboxes.forEach(function (d) {
                            if (typeof d === 'object' && d.textInput && dep.indexOf(d.v) > -1
                                && !String(quizResults[d.txtField] || '').trim()) { valid = false; }
                        });
                    }
                }
            });
        }

        btnNext.disabled = !valid;
    }

    btnNext.addEventListener('click', function () {
        if (currentQuizStep < totalQuizSteps) {
            currentQuizStep++;
            renderQuizStep(currentQuizStep);
        } else {
            submitQuiz(false);
        }
    });

    btnPrev.addEventListener('click', function () {
        if (currentQuizStep > 1) {
            currentQuizStep--;
            renderQuizStep(currentQuizStep);
        }
    });

    btnSave.addEventListener('click', function () { submitQuiz(true); });

    <?php // ---- save ---- ?>
    function submitQuiz(quickSave) {
        clearNote();

        // Arrays are stored comma-joined, matching the standalone quiz page.
        var payload = {};
        Object.keys(quizResults).forEach(function (k) {
            payload[k] = Array.isArray(quizResults[k]) ? quizResults[k].join(', ') : quizResults[k];
        });

        if (!IS_LOGGED_IN) {
            // Nothing to attach these to yet — stash them so they are saved the
            // moment this reader registers or signs in (flushed on next load).
            try { window.localStorage.setItem('vance_pending_quiz_results', JSON.stringify(payload)); } catch (e) {}
            if (quickSave) { window.closeQuizModal(); } else { showCompletion(); }
            return;
        }

        var body = new FormData();
        body.append('action', 'vance_save_quiz_results');
        body.append('nonce', QUIZ_NONCE);
        Object.keys(payload).forEach(function (k) { body.append('quiz_data[' + k + ']', payload[k]); });

        if (quickSave) { btnSave.disabled = true; btnSave.textContent = 'Saving…'; }
        else { btnNext.disabled = true; btnNext.textContent = 'Saving…'; }

        // The modal used to close before this request resolved and only acted on
        // success, so a failed save looked exactly like a successful one — the
        // answers simply were not there next time. It now stays open until the
        // save is confirmed, and says so when it is not.
        fetch(AJAX_URL, { method: 'POST', credentials: 'same-origin', body: body })
            .then(function (r) {
                if (!r.ok) { throw new Error('HTTP ' + r.status); }
                return r.json();
            })
            .then(function (res) {
                if (!res || !res.success) {
                    throw new Error((res && res.data) ? String(res.data) : 'save failed');
                }
                if (quickSave) { window.location.reload(); } else { showCompletion(); }
            })
            .catch(function (err) {
                showNote('Could not save your answers (' + err.message + '). Please check your connection and try again.', 'error');
                btnSave.disabled = false; btnSave.textContent = 'Save & close';
                btnNext.disabled = false;
                btnNext.textContent = (currentQuizStep === totalQuizSteps) ? 'Finish' : 'Next';
            });
    }

    function showCompletion() {
        document.getElementById('modal-progress-bar').style.width = '100%';
        document.getElementById('modal-progress-pct').textContent = '100%';
        container.style.display = 'none';
        footer.style.display = 'none';
        progress.style.display = 'none';
        results.style.display = 'block';
    }

    window.handleQuizCompletion = function () {
        if (!IS_LOGGED_IN) {
            window.closeQuizModal();
            if (typeof window.openGuestModal === 'function') { window.openGuestModal(); }
            return;
        }
        window.location.href = PROFILE_URL;
    };

    <?php if ( is_user_logged_in() ) : ?>
    // A guest who finished the quiz before signing in had nowhere to save their
    // answers, so they were stashed in localStorage. Now that this page load has
    // a user, flush them once and clear the stash.
    (function flushPendingQuizResults() {
        var raw;
        try { raw = window.localStorage.getItem('vance_pending_quiz_results'); } catch (e) { raw = null; }
        if (!raw) { return; }
        var payload;
        try { payload = JSON.parse(raw); } catch (e) { payload = null; }
        try { window.localStorage.removeItem('vance_pending_quiz_results'); } catch (e) {}
        if (!payload || typeof payload !== 'object') { return; }

        var body = new FormData();
        body.append('action', 'vance_save_quiz_results');
        body.append('nonce', QUIZ_NONCE);
        Object.keys(payload).forEach(function (k) { body.append('quiz_data[' + k + ']', payload[k]); });
        fetch(AJAX_URL, { method: 'POST', credentials: 'same-origin', body: body }).catch(function () {});
    })();
    <?php endif; ?>
})();
</script>
