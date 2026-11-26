/**
 * Waitlist form (/waitlist/). Posts email, first name and one consent value to
 * admin-ajax. Sends nothing else, and asks no health question.
 */
(function () {
	'use strict';

	var form = document.getElementById('vance-waitlist-form');
	var msg = document.getElementById('vance-waitlist-msg');
	if (!form || !msg || !window.vanceWaitlist) {
		return;
	}

	form.addEventListener('submit', function (event) {
		event.preventDefault();
		var email = form.elements.email.value.trim();
		if (!email || email.indexOf('@') < 1) {
			msg.textContent = 'Please enter a valid email address.';
			return;
		}
		if (!form.elements.consent.checked) {
			msg.textContent = 'Please tick the box to agree. We cannot add you without it.';
			return;
		}
		var body = new URLSearchParams();
		body.set('action', 'vance_waitlist_signup');
		body.set('nonce', window.vanceWaitlist.nonce);
		body.set('email', email);
		body.set('first_name', form.elements.first_name.value.trim());
		body.set('website', form.elements.website.value);
		body.set('consent', '1');
		var button = form.querySelector('button[type="submit"]');
		button.disabled = true;
		msg.textContent = '';
		fetch(window.vanceWaitlist.ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
			body: body.toString()
		}).then(function (response) {
			return response.json();
		}).then(function (data) {
			msg.textContent = (data && data.data && data.data.message) || 'Something went wrong. Please try again.';
			if (data && data.success) {
				form.reset();
			}
		}).catch(function () {
			msg.textContent = 'Something went wrong. Please try again.';
		}).then(function () {
			button.disabled = false;
		});
	});
}());
