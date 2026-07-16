(function () {
	'use strict';

	var i18n = window.fendigibadgePublic || {};
	var shareLabels = i18n.shareDestinations || {};

	function canWebShare() {
		return typeof navigator !== 'undefined' && typeof navigator.share === 'function';
	}

	function isDesktopShare() {
		return window.matchMedia('(hover: hover) and (pointer: fine)').matches;
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

	function buildShareDestinationUrls(text, url) {
		var encodedText = encodeURIComponent(text);
		var encodedUrl = encodeURIComponent(url);

		return {
			linkedin: 'https://www.linkedin.com/sharing/share-offsite/?url=' + encodedUrl,
			mastodon: 'https://mastodon.social/share?text=' + encodedText,
			bluesky: 'https://bsky.app/intent/compose?text=' + encodedText,
			x: 'https://twitter.com/intent/tweet?text=' + encodedText
		};
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

	function closeShareMenus() {
		document.querySelectorAll('.fendigibadge-share-menu').forEach(function (menu) {
			menu.setAttribute('hidden', '');
		});

		document.querySelectorAll('[data-fendigibadge-share][aria-expanded="true"]').forEach(function (button) {
			button.setAttribute('aria-expanded', 'false');
		});
	}

	function getShareMenu(button) {
		var wrapper = button.closest('.fendigibadge-share');
		if (!(wrapper instanceof HTMLElement)) {
			return null;
		}

		var menu = wrapper.querySelector('.fendigibadge-share-menu');
		if (menu instanceof HTMLElement) {
			return menu;
		}

		menu = document.createElement('div');
		menu.className = 'fendigibadge-share-menu';
		menu.setAttribute('role', 'menu');
		menu.setAttribute('hidden', '');

		[
			{ key: 'linkedin', label: shareLabels.linkedin || 'LinkedIn' },
			{ key: 'mastodon', label: shareLabels.mastodon || 'Mastodon' },
			{ key: 'bluesky', label: shareLabels.bluesky || 'Bluesky' },
			{ key: 'x', label: shareLabels.x || 'X' }
		].forEach(function (destination) {
			var link = document.createElement('a');
			link.className = 'fendigibadge-share-menu__item';
			link.setAttribute('role', 'menuitem');
			link.setAttribute('data-fendigibadge-share-destination', destination.key);
			link.target = '_blank';
			link.rel = 'noopener noreferrer';
			link.textContent = destination.label;
			menu.appendChild(link);
		});

		wrapper.appendChild(menu);
		return menu;
	}

	function toggleShareMenu(button, urls) {
		var menu = getShareMenu(button);
		if (!menu) {
			return;
		}

		var isOpen = !menu.hasAttribute('hidden');
		closeShareMenus();

		if (isOpen) {
			return;
		}

		menu.querySelectorAll('[data-fendigibadge-share-destination]').forEach(function (link) {
			var key = link.getAttribute('data-fendigibadge-share-destination');
			if (key && urls[key]) {
				link.href = urls[key];
			}
		});

		menu.removeAttribute('hidden');
		button.setAttribute('aria-expanded', 'true');
	}

	function shareWithWebApi(shareButton) {
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
	}

	document.querySelectorAll('[data-fendigibadge-share]').forEach(function (button) {
		if (canWebShare() || isDesktopShare()) {
			button.removeAttribute('hidden');
		}
	});

	document.addEventListener('keydown', function (event) {
		if (event.key === 'Escape') {
			closeShareMenus();
		}
	});

	document.addEventListener('click', function (event) {
		var target = event.target;
		if (!(target instanceof Element)) {
			return;
		}

		if (target.closest('[data-fendigibadge-share-destination]')) {
			closeShareMenus();
			return;
		}

		if (!target.closest('.fendigibadge-share')) {
			closeShareMenus();
		}

		var shareButton = target.closest('[data-fendigibadge-share]');
		if (shareButton) {
			event.preventDefault();

			if (isDesktopShare()) {
				var title = shareButton.getAttribute('data-fendigibadge-share-title') || document.title;
				var description = shareButton.getAttribute('data-fendigibadge-share-text') || '';
				var url = shareButton.getAttribute('data-fendigibadge-share-url') || window.location.href;
				var text = buildShareText(title, description, url);
				toggleShareMenu(shareButton, buildShareDestinationUrls(text, url));
				return;
			}

			if (!canWebShare()) {
				return;
			}

			shareWithWebApi(shareButton);
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
