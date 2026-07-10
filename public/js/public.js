(function () {
	'use strict';

	function canWebShare() {
		return typeof navigator !== 'undefined' && typeof navigator.share === 'function';
	}

	document.querySelectorAll('[data-db-share]').forEach(function (button) {
		if (canWebShare()) {
			button.removeAttribute('hidden');
		}
	});

	document.addEventListener('click', function (event) {
		var target = event.target;
		if (!(target instanceof Element)) {
			return;
		}

		var shareButton = target.closest('[data-db-share]');
		if (shareButton) {
			event.preventDefault();
			if (!canWebShare()) {
				return;
			}

			var payload = {
				title: shareButton.getAttribute('data-db-share-title') || document.title,
				text: shareButton.getAttribute('data-db-share-text') || '',
				url: shareButton.getAttribute('data-db-share-url') || window.location.href
			};

			navigator.share(payload).catch(function () {
				// User cancelled or share failed — no-op.
			});
			return;
		}

		var toggle = target.closest('[data-db-toggle]');
		if (toggle) {
			event.preventDefault();
			var panelSelector = toggle.getAttribute('data-db-toggle');
			if (!panelSelector) {
				return;
			}

			var panel = document.querySelector(panelSelector);
			if (!(panel instanceof HTMLElement)) {
				return;
			}

			var willShow = panel.hasAttribute('hidden');
			if (willShow) {
				panel.removeAttribute('hidden');
			} else {
				panel.setAttribute('hidden', '');
			}
			toggle.setAttribute('aria-expanded', willShow ? 'true' : 'false');
			return;
		}

		var button = target.closest('[data-db-copy]');
		if (!button) {
			return;
		}

		var selector = button.getAttribute('data-db-copy');
		if (!selector) {
			return;
		}

		var field = document.querySelector(selector);
		if (!(field instanceof HTMLTextAreaElement) && !(field instanceof HTMLInputElement)) {
			return;
		}

		field.select();
		field.setSelectionRange(0, field.value.length);

		if (navigator.clipboard && navigator.clipboard.writeText) {
			navigator.clipboard.writeText(field.value).catch(function () {
				document.execCommand('copy');
			});
		} else {
			document.execCommand('copy');
		}
	});
})();
