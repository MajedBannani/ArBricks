/**
 * ArBricks Admin JavaScript
 *
 * Minimal admin functionality for copy shortcode button
 *
 * @package ArBricks
 * @since 2.0.0
 */

(function ($) {
	'use strict';

	$(document).ready(function () {
		// Copy shortcode functionality
		$('.arbricks-copy-shortcode').on('click', function (e) {
			e.preventDefault();

			const button = $(this);
			const shortcode = button.data('shortcode');

			// Use modern clipboard API
			if (navigator.clipboard && navigator.clipboard.writeText) {
				navigator.clipboard.writeText(shortcode)
					.then(function () {
						showCopySuccess(button);
					})
					.catch(function () {
						// Fallback to older method
						copyToClipboardFallback(shortcode, button);
					});
			} else {
				// Fallback for older browsers
				copyToClipboardFallback(shortcode, button);
			}
		});

		/**
		 * Show copy success feedback
		 *
		 * @param {jQuery} button Button element
		 */
		function showCopySuccess(button) {
			const originalText = button.find('.button-text').text() || button.text();

			// Update button text
			button.addClass('arbricks-copied');
			button.find('.dashicons')
				.removeClass('dashicons-clipboard')
				.addClass('dashicons-yes');

			if (button.find('.button-text').length) {
				button.find('.button-text').text(arbricksAdmin.copySuccess);
			}

			// Reset after 2 seconds
			setTimeout(function () {
				button.removeClass('arbricks-copied');
				button.find('.dashicons')
					.removeClass('dashicons-yes')
					.addClass('dashicons-clipboard');

				if (button.find('.button-text').length) {
					button.find('.button-text').text(originalText);
				}
			}, 2000);
		}

		/**
		 * Fallback clipboard copy method
		 *
		 * @param {string} text Text to copy
		 * @param {jQuery} button Button element
		 */
		function copyToClipboardFallback(text, button) {
			const tempInput = $('<textarea>')
				.val(text)
				.appendTo('body')
				.select();

			try {
				document.execCommand('copy');
				showCopySuccess(button);
			} catch (err) {
				console.error('Failed to copy shortcode:', err);
				alert(arbricksAdmin.copyError);
			}

			tempInput.remove();
		}

		// Card Helper Accordion (INNER accordion - inside feature cards)
		const $helperToggles = $('.arbricks-help-toggle');

		$helperToggles.on('click', function () {
			const $toggle = $(this);
			const panelId = $toggle.attr('aria-controls');
			const $panel = $('#' + panelId);
			const isExpanded = $toggle.attr('aria-expanded') === 'true';

			if (window.console && window.console.log) {
				console.log('[ArBricks] Helper toggle clicked, panel found:', $panel.length, 'ID:', panelId);
			}

			if (isExpanded) {
				$panel.slideUp(200, function() {
					$panel.attr('hidden', '').attr('aria-hidden', 'true');
					$toggle.attr('aria-expanded', 'false');
				});
			} else {
				// Remove hidden attribute so slideDown works, but keep it invisible for a split sec
				$panel.hide().removeAttr('hidden').attr('aria-hidden', 'false').slideDown(200);
				$toggle.attr('aria-expanded', 'true');
			}
		});

		// Sidebar Category Filtering
		const $categoryLinks = $('.arbricks-category-link');
		const $sections = $('.arbricks-section');

		/**
		 * Filter sections based on category
		 * 
		 * @param {string} filter Category slug or 'all'
		 */
		function filterSections(filter) {
			if (filter === 'all') {
				$sections.removeClass('is-hidden');
			} else {
				$sections.addClass('is-hidden');
				$sections.filter('[data-section="' + filter + '"]').removeClass('is-hidden');
			}

			// Update active link state
			$categoryLinks.removeClass('is-active');
			$categoryLinks.filter('[data-filter="' + filter + '"]').addClass('is-active');

			// Store selection
			localStorage.setItem('arbricks_admin_filter', filter);
		}

		$categoryLinks.on('click', function (e) {
			e.preventDefault();
			const filter = $(this).data('filter');
			filterSections(filter);
		});

		// Restore last selection
		const lastFilter = localStorage.getItem('arbricks_admin_filter');
		if (lastFilter && $('.arbricks-category-link[data-filter="' + lastFilter + '"]').length) {
			filterSections(lastFilter);
		}

		// Media Picker Logic
		$('.arbricks-media-upload').on('click', function(e) {
			e.preventDefault();
			const $button = $(this);
			const $container = $button.closest('.arbricks-media-picker');
			const $preview = $container.find('.arbricks-media-preview');
			const $input = $container.find('input[type="hidden"]');
			const $removeBtn = $container.find('.arbricks-media-remove');

			const frame = wp.media({
				title: arbricksAdmin.selectImage,
				button: { text: arbricksAdmin.useThisImage },
				multiple: false
			});

			frame.on('select', function() {
				const attachment = frame.state().get('selection').first().toJSON();
				$input.val(attachment.id);
				
				const url = attachment.sizes.thumbnail ? attachment.sizes.thumbnail.url : attachment.url;
				$preview.html('<img src="' + url + '" style="max-width: 100px; height: auto; display: block; border: 1px solid #ddd; padding: 5px;">');
				$removeBtn.show();
			});

			frame.open();
		});

		$('.arbricks-media-remove').on('click', function(e) {
			e.preventDefault();
			const $button = $(this);
			const $container = $button.closest('.arbricks-media-picker');
			$container.find('input[type="hidden"]').val('');
			$container.find('.arbricks-media-preview').empty();
			$button.hide();
		});
	});

})(jQuery);
