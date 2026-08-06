<?php
/**
 * "Update Additional Health Details" — the editor behind the dashboard's
 * Health Details & Lifestyle panel.
 *
 * Fields are generated from vance_clinical_profile_fields() (functions.php) so
 * the form, the AJAX writer and the dashboard panel cannot drift apart. Every
 * input's `name` is the meta key it writes to; the handler merges rather than
 * replaces, so this form and the panel's "Additional details" box can both
 * save without clobbering each other.
 *
 * Deliberately limited to information a reader would already keep in a paper
 * health diary: measurements, medication, supplements, diet, symptom timing
 * and appointment prep. No condition diagnoses, ethnicity, sexual health,
 * mental health, genetics or anything else that would raise the sensitivity of
 * this record beyond what the quiz already asks.
 */

$vance_cp        = get_user_meta( get_current_user_id(), '_sla_clinical_profile', true );
$vance_cp        = is_array( $vance_cp ) ? $vance_cp : array();
$vance_cp        = array_merge( vance_clinical_profile_defaults(), $vance_cp );
$vance_cp_diets  = array(
	'No specific diet', 'Low FODMAP', 'Gluten-free', 'Dairy-free', 'Low fibre / low residue',
	'Vegetarian', 'Vegan', 'Exclusive enteral nutrition', 'Other',
);
// value => label. The values are the ones already written to user meta by the
// previous version of this form — changing them would leave existing readers
// with a blank select and no way to tell their answer had been dropped.
$vance_cp_flare  = array(
	'Never'   => 'Never',
	'Rare'    => 'Rare (yearly)',
	'Monthly' => 'Monthly',
	'Weekly'  => 'Weekly',
	'Daily'   => 'Daily',
);
?>
<div id="vance-clinical-info-modal" class="vance-mk-scrim" role="dialog" aria-modal="true"
     aria-hidden="true" aria-labelledby="vance-clinical-modal-title">
	<div class="vance-mk vance-mk--wide" role="document">
		<button type="button" class="vance-mk__close" onclick="closeClinicalInfoModal()" aria-label="Close">&times;</button>

		<div class="vance-mk__header">
			<div class="vance-mk__badge">Health Record</div>
			<h2 id="vance-clinical-modal-title" class="vance-mk__title">Update Additional Health Details</h2>
			<p class="vance-mk__subtitle">Everything here is optional and private to your account. It is stored so you can keep one record over time and take it to appointments; it is not a diagnosis and it is not shared with anyone.</p>
		</div>

		<div class="vance-mk__card">
			<form id="modal-clinical-profile-form" novalidate>
				<?php wp_nonce_field( 'vance_dashboard_nonce', 'nonce' ); ?>
				<input type="hidden" name="action" value="vance_save_clinical_profile">

				<fieldset class="vance-mk__fieldset">
					<legend class="vance-mk__legend">Measurements</legend>
					<div class="vance-mk__row vance-mk__field">
						<div>
							<label class="vance-mk__label" for="modal-weight">Current weight (kg)</label>
							<input class="vance-mk__input" type="text" inputmode="decimal" name="weight" id="modal-weight" placeholder="e.g. 75">
						</div>
						<div>
							<label class="vance-mk__label" for="modal-height">Height (cm)</label>
							<input class="vance-mk__input" type="text" inputmode="decimal" name="height" id="modal-height" placeholder="e.g. 180">
						</div>
					</div>
					<div class="vance-mk__row vance-mk__field">
						<div>
							<label class="vance-mk__label" for="modal-usual_weight">Usual weight when well (kg)</label>
							<input class="vance-mk__input" type="text" inputmode="decimal" name="usual_weight" id="modal-usual_weight" placeholder="e.g. 79">
							<p class="vance-mk__hint">Unintentional weight loss is one of the things the malnutrition screener asks about.</p>
						</div>
						<div>
							<label class="vance-mk__label" for="modal-blood_pressure">Blood pressure</label>
							<input class="vance-mk__input" type="text" name="blood_pressure" id="modal-blood_pressure" placeholder="e.g. 120/80">
						</div>
					</div>
				</fieldset>

				<fieldset class="vance-mk__fieldset">
					<legend class="vance-mk__legend">Medication &amp; supplements</legend>
					<div class="vance-mk__field">
						<label class="vance-mk__label" for="modal-medication">Current medication</label>
						<textarea class="vance-mk__textarea" name="medication" id="modal-medication" rows="2" placeholder="Name and dose, one per line"></textarea>
					</div>
					<div class="vance-mk__field">
						<label class="vance-mk__label" for="modal-supplements">Supplements</label>
						<textarea class="vance-mk__textarea" name="supplements" id="modal-supplements" rows="2" placeholder="e.g. Vitamin D 1000 IU, Omega-3"></textarea>
					</div>
					<div class="vance-mk__field">
						<label class="vance-mk__label" for="modal-allergies">Allergies &amp; intolerances</label>
						<textarea class="vance-mk__textarea" name="allergies" id="modal-allergies" rows="2" placeholder="e.g. penicillin, lactose"></textarea>
					</div>
				</fieldset>

				<fieldset class="vance-mk__fieldset">
					<legend class="vance-mk__legend">Diet &amp; lifestyle</legend>
					<div class="vance-mk__field">
						<label class="vance-mk__label" for="modal-dietary_pattern">Dietary pattern</label>
						<select class="vance-mk__select" name="dietary_pattern" id="modal-dietary_pattern">
							<option value="">Select an option</option>
							<?php foreach ( $vance_cp_diets as $vance_cp_diet ) : ?>
								<option value="<?php echo esc_attr( $vance_cp_diet ); ?>"><?php echo esc_html( $vance_cp_diet ); ?></option>
							<?php endforeach; ?>
						</select>
					</div>
					<div class="vance-mk__field">
						<label class="vance-mk__label" for="modal-trigger_foods">Foods that seem to trigger symptoms</label>
						<textarea class="vance-mk__textarea" name="trigger_foods" id="modal-trigger_foods" rows="2" placeholder="Anything you have noticed a pattern with"></textarea>
					</div>
					<div class="vance-mk__field">
						<label class="vance-mk__label" for="modal-lifestyle_changes">Recent changes (diet, exercise, routine)</label>
						<textarea class="vance-mk__textarea" name="lifestyle_changes" id="modal-lifestyle_changes" rows="2" placeholder="Anything you have changed lately"></textarea>
					</div>
					<div class="vance-mk__field">
						<label class="vance-mk__label" for="modal-digital_apps">Health apps or trackers you use</label>
						<textarea class="vance-mk__textarea" name="digital_apps" id="modal-digital_apps" rows="2" placeholder="e.g. MyFitnessPal, Oura"></textarea>
					</div>
				</fieldset>

				<fieldset class="vance-mk__fieldset">
					<legend class="vance-mk__legend">Symptoms &amp; appointments</legend>
					<div class="vance-mk__row vance-mk__field">
						<div>
							<label class="vance-mk__label" for="modal-flare_up_freq">Flare-up frequency</label>
							<select class="vance-mk__select" name="flare_up_freq" id="modal-flare_up_freq">
								<option value="">Select an option</option>
								<?php foreach ( $vance_cp_flare as $vance_cp_fv => $vance_cp_fl ) : ?>
									<option value="<?php echo esc_attr( $vance_cp_fv ); ?>"><?php echo esc_html( $vance_cp_fl ); ?></option>
								<?php endforeach; ?>
							</select>
						</div>
						<div>
							<label class="vance-mk__label" for="modal-last_flare_up">Most recent flare-up</label>
							<input class="vance-mk__input" type="text" name="last_flare_up" id="modal-last_flare_up" placeholder="e.g. 2 weeks ago">
						</div>
					</div>
					<div class="vance-mk__field">
						<label class="vance-mk__label" for="modal-next_appointment">Next appointment</label>
						<input class="vance-mk__input" type="date" name="next_appointment" id="modal-next_appointment">
					</div>
					<div class="vance-mk__field">
						<label class="vance-mk__label" for="modal-appointment_questions">Questions for my next appointment</label>
						<textarea class="vance-mk__textarea" name="appointment_questions" id="modal-appointment_questions" rows="3" placeholder="Jot things down as you think of them so you have them to hand on the day"></textarea>
					</div>
				</fieldset>

				<div class="vance-mk__nav">
					<button type="button" class="vance-mk__btn vance-mk__btn--ghost" onclick="closeClinicalInfoModal()">Cancel</button>
					<button type="submit" class="vance-mk__btn vance-mk__btn--primary" id="modal-clinical-submit">Save health details</button>
				</div>
				<div class="vance-mk__note" id="modal-clinical-note" role="status" aria-live="polite"></div>
			</form>
		</div>

		<p class="vance-mk__footer">Stored privately against your account. General information only. Not a diagnosis, and not a substitute for advice from your healthcare team.</p>
	</div>
</div>

<script>
(function () {
	// Field values are handed over as data rather than as a generated line per
	// field, so adding a field to vance_clinical_profile_fields() needs no
	// change here — the loop below fills whatever inputs exist by name.
	var CLINICAL_DATA = <?php echo wp_json_encode( $vance_cp ); ?>;

	var modal = document.getElementById('vance-clinical-info-modal');
	var form  = document.getElementById('modal-clinical-profile-form');
	var note  = document.getElementById('modal-clinical-note');
	var btn   = document.getElementById('modal-clinical-submit');

	function showNote(msg, kind) {
		if (!note) { return; }
		note.textContent = msg;
		note.className = 'vance-mk__note is-visible vance-mk__note--' + (kind || 'error');
	}

	function clearNote() {
		if (note) { note.className = 'vance-mk__note'; note.textContent = ''; }
	}

	window.openClinicalInfoModal = function () {
		clearNote();
		if (form) {
			Object.keys(CLINICAL_DATA).forEach(function (key) {
				var field = form.elements[key];
				if (field && typeof field.value !== 'undefined') {
					field.value = CLINICAL_DATA[key] == null ? '' : CLINICAL_DATA[key];
				}
			});
		}
		modal.classList.add('is-open');
		modal.setAttribute('aria-hidden', 'false');
		document.body.style.overflow = 'hidden';
		var first = form && form.querySelector('input, select, textarea');
		if (first) { first.focus(); }
	};

	window.closeClinicalInfoModal = function () {
		modal.classList.remove('is-open');
		modal.setAttribute('aria-hidden', 'true');
		document.body.style.overflow = '';
	};

	// Backdrop + Escape close, matching every other modal on the site.
	modal.addEventListener('click', function (e) {
		if (e.target === modal) { window.closeClinicalInfoModal(); }
	});
	document.addEventListener('keydown', function (e) {
		if (e.key === 'Escape' && modal.classList.contains('is-open')) { window.closeClinicalInfoModal(); }
	});

	if (form) {
		form.addEventListener('submit', function (e) {
			e.preventDefault();
			clearNote();
			btn.disabled = true;
			btn.textContent = 'Saving…';

			var restore = function () {
				btn.disabled = false;
				btn.textContent = 'Save health details';
				btn.classList.remove('vance-mk__btn--ok');
			};

			// Every branch below restores the button. The previous version had a
			// success-only jQuery callback, so the 403 from a mismatched nonce
			// left it stuck on "Updating…" with no way forward — the freeze.
			fetch(<?php echo wp_json_encode( admin_url( 'admin-ajax.php' ) ); ?>, {
				method: 'POST',
				credentials: 'same-origin',
				body: new FormData(form)
			})
				.then(function (r) {
					if (!r.ok) { throw new Error('HTTP ' + r.status); }
					return r.json();
				})
				.then(function (res) {
					if (res && res.success) {
						btn.textContent = 'Saved ✓';
						btn.classList.add('vance-mk__btn--ok');
						showNote('Your health details have been saved.', 'ok');
						setTimeout(function () { window.location.reload(); }, 900);
						return;
					}
					showNote((res && res.data) ? String(res.data) : 'Could not save, please try again.', 'error');
					restore();
				})
				.catch(function (err) {
					showNote('Could not save (' + err.message + '). Please check your connection and try again.', 'error');
					restore();
				});
		});
	}
})();
</script>
