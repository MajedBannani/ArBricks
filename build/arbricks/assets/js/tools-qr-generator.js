/**
 * QR Code Generator Tool JavaScript
 *
 * Handles QR code generation tool UI interactions (client-side).
 *
 * @package ArBricks
 * @since 2.1.0
 */

(function($) {
	'use strict';

	$(document).ready(function() {
		const $tool = $('.arbricks-tool-qr-generator');
		if (!$tool.length) return;

		const $urlInput = $('#qr-url-input');
		const $sizeSelect = $('#qr-size-select');
		const $eccSelect = $('#qr-ecc-select');
		const $generateBtn = $('#qr-generate-btn');
		const $downloadBtn = $('#qr-download-btn');
		const $preview = $('.qr-preview', $tool);
		const $notice = $('.tool-notice', $tool);

		let currentQrCode = null;

		/**
		 * Show notice
		 *
		 * @param {string} message Notice message.
		 * @param {string} type Notice type ('success' or 'error').
		 */
		function showNotice(message, type) {
			$notice
				.removeClass('success error')
				.addClass(type)
				.text(message)
				.show();

			setTimeout(function() {
				$notice.fadeOut();
			}, 3000);
		}

		/**
		 * Validate URL
		 *
		 * @param {string} url URL to validate.
		 * @return {boolean} True if valid.
		 */
		function isValidUrl(url) {
			try {
				new URL(url);
				return true;
			} catch(e) {
				return false;
			}
		}

		/**
		 * Generate QR code (client-side)
		 */
		$generateBtn.on('click', function() {
		// Check if QR library is available
		if (typeof QRCode === 'undefined') {
			showNotice(arbricksQrGenerator.libraryMissing || 'QR code library not loaded. Please refresh the page.', 'error');
			return;
		}

		const url = $urlInput.val().trim();
			const size = parseInt($sizeSelect.val(), 10);
			const ecc = $eccSelect.val();

			// Validate URL
			if (!url) {
				showNotice(arbricksQrGenerator.invalidUrl, 'error');
				$urlInput.focus();
				return;
			}

			if (!isValidUrl(url)) {
				showNotice(arbricksQrGenerator.invalidUrl, 'error');
				$urlInput.focus();
				return;
			}

			// Check if QRCode library is available
			if (typeof QRCode === 'undefined') {
				showNotice(arbricksQrGenerator.libraryMissing, 'error');
				return;
			}

			// Clear previous QR code
			$preview.empty().removeClass('empty');

			// Map error correction level
			const eccLevels = {
				'L': QRCode.CorrectLevel.L,
				'M': QRCode.CorrectLevel.M,
				'Q': QRCode.CorrectLevel.Q,
				'H': QRCode.CorrectLevel.H
			};

			try {
				// Generate QR code
				currentQrCode = new QRCode($preview[0], {
					text: url,
					width: size,
					height: size,
					correctLevel: eccLevels[ecc] || QRCode.CorrectLevel.M
				});

				$downloadBtn.show();
				showNotice(arbricksQrGenerator.generateSuccess, 'success');
			} catch(error) {
				showNotice(arbricksQrGenerator.generateError, 'error');
				console.error('QR generation error:', error);
			}
		});

		/**
		 * Download QR code as PNG
		 */
		$downloadBtn.on('click', function() {
			const $img = $preview.find('img');
			
			if (!$img.length) {
				showNotice(arbricksQrGenerator.noQrToDownload, 'error');
				return;
			}

			// Create download link
			const link = document.createElement('a');
			link.href = $img.attr('src');
			link.download = 'qr-code.png';
			document.body.appendChild(link);
			link.click();
			document.body.removeChild(link);
		});

		// Hide download button initially
		$downloadBtn.hide();
	});

})(jQuery);
