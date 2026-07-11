(function () {
	'use strict';

	function canWebShare() {
		return typeof navigator !== 'undefined' && typeof navigator.share === 'function';
	}

	function buildShareText(title, description, url) {
		var parts = [];
		if (title) {
			parts.push(title);
		}
		if (description) {
			parts.push(description);
		}
		if (url) {
			parts.push(url);
		}
		return parts.join('\n\n');
	}

	function extensionForMime(mime) {
		if (mime === 'image/jpeg') {
			return 'jpg';
		}
		if (mime === 'image/webp') {
			return 'webp';
		}
		if (mime === 'image/gif') {
			return 'gif';
		}
		return 'png';
	}

	function fetchShareImage(imageUrl) {
		return fetch(imageUrl)
			.then(function (response) {
				if (!response.ok) {
					throw new Error('Image fetch failed');
				}
				return response.blob();
			})
			.then(function (blob) {
				var type = blob.type || 'image/png';
				return new File([blob], 'badge.' + extensionForMime(type), { type: type });
			});
	}

	function sharePayload(payload) {
		return navigator.share(payload).catch(function () {
			// User cancelled or share failed — no-op.
		});
	}

	document.querySelectorAll('[data-fendigibadge-share]').forEach(function (button) {
		if (canWebShare()) {
			button.removeAttribute('hidden');
		}
	});

	document.addEventListener('click', function (event) {
		var target = event.target;
		if (!(target instanceof Element)) {
			return;
		}

		var shareButton = target.closest('[data-fendigibadge-share]');
		if (shareButton) {
			event.preventDefault();
			if (!canWebShare()) {
				return;
			}

			var title = shareButton.getAttribute('data-fendigibadge-share-title') || document.title;
			var description = shareButton.getAttribute('data-fendigibadge-share-text') || '';
			var url = shareButton.getAttribute('data-fendigibadge-share-url') || window.location.href;
			var imageUrl = shareButton.getAttribute('data-fendigibadge-share-image') || '';
			var text = buildShareText(title, description, url);

			var basePayload = {
				title: title,
				text: text,
				url: url
			};

			if (!imageUrl || typeof navigator.canShare !== 'function') {
				sharePayload(basePayload);
				return;
			}

			fetchShareImage(imageUrl)
				.then(function (file) {
					var withFiles = {
						title: title,
						text: text,
						files: [file]
					};

					if (navigator.canShare(withFiles)) {
						return sharePayload(withFiles);
					}

					return sharePayload(basePayload);
				})
				.catch(function () {
					sharePayload(basePayload);
				});
			return;
		}

		var toggle = target.closest('[data-fendigibadge-toggle]');
		if (toggle) {
			event.preventDefault();
			var panelSelector = toggle.getAttribute('data-fendigibadge-toggle');
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

		var button = target.closest('[data-fendigibadge-copy]');
		if (!button) {
			return;
		}

		var selector = button.getAttribute('data-fendigibadge-copy');
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
