/**
 * Fenton Digital Badges — admin scripts.
 */
(function ($) {
	'use strict';

	function initLogoPicker($root) {
		var frame;
		var $input = $root.find('[data-fendigibadge-logo-id]');
		var $preview = $root.find('[data-fendigibadge-logo-preview]');
		var $select = $root.find('[data-fendigibadge-logo-select]');
		var $remove = $root.find('[data-fendigibadge-logo-remove]');
		var i18n = window.fendigibadgeAdmin || {};

		$select.on('click', function (event) {
			event.preventDefault();

			if (frame) {
				frame.open();
				return;
			}

			frame = wp.media({
				title: i18n.selectLogoTitle || 'Select issuer logo',
				button: {
					text: i18n.selectLogoButton || 'Use this logo'
				},
				library: {
					type: 'image'
				},
				multiple: false
			});

			frame.on('select', function () {
				var attachment = frame.state().get('selection').first().toJSON();
				var url = (attachment.sizes && attachment.sizes.medium)
					? attachment.sizes.medium.url
					: attachment.url;

				$input.val(attachment.id);
				$preview.html('<img src="' + url + '" alt="" />').prop('hidden', false);
				$remove.prop('hidden', false);
				$select.text(i18n.changeLogo || 'Change logo');
			});

			frame.open();
		});

		$remove.on('click', function (event) {
			event.preventDefault();
			$input.val('0');
			$preview.empty().prop('hidden', true);
			$remove.prop('hidden', true);
			$select.text(i18n.selectLogo || 'Select logo');
		});
	}

	function initEmailTabs($root) {
		var $tabs = $root.find('[data-fendigibadge-email-tab]');
		var $panels = $root.find('[data-fendigibadge-email-panel]');

		$tabs.on('click', function (event) {
			event.preventDefault();

			var tab = $(this).attr('data-fendigibadge-email-tab');

			$tabs.removeClass('nav-tab-active').attr('aria-selected', 'false');
			$(this).addClass('nav-tab-active').attr('aria-selected', 'true');

			$panels.each(function () {
				var $panel = $(this);
				var match = $panel.attr('data-fendigibadge-email-panel') === tab;
				$panel.prop('hidden', !match);
			});
		});
	}

	$(function () {
		$('[data-fendigibadge-logo-picker]').each(function () {
			initLogoPicker($(this));
		});

		$('[data-fendigibadge-email-tabs]').each(function () {
			initEmailTabs($(this));
		});
	});
})(jQuery);
