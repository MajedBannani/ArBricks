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
	});

})(jQuery);
