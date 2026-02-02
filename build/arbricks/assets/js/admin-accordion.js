/**
 * ArBricks Accordion Toggle
 *
 * Handles accordion functionality with localStorage persistence
 * and keyboard accessibility.
 *
 * @package ArBricks
 * @since 2.0.0
 */

(function() {
	'use strict';

	const STORAGE_KEY = 'arbricks_acc_open';

	/**
	 * Initialize accordion functionality
	 */
	function initAccordion() {
		const accordion = document.querySelector('[data-arbricks-accordion]');
		if (!accordion) {
			return;
		}

		const buttons = accordion.querySelectorAll('.arbricks-accordion__btn');
		if (!buttons.length) {
			return;
		}

		// Attach click listeners
		buttons.forEach(function(btn) {
			btn.addEventListener('click', handleButtonClick);
		});

		// Restore last open state from localStorage
		restoreOpenState(buttons);
	}

	/**
	 * Handle accordion button click
	 *
	 * @param {Event} e Click event
	 */
	function handleButtonClick(e) {
		const btn = e.currentTarget;
		const isExpanded = btn.getAttribute('aria-expanded') === 'true';
		const panelId = btn.getAttribute('aria-controls');
		const panel = document.getElementById(panelId);
		const item = btn.closest('.arbricks-accordion__item');
		const category = item ? item.dataset.category : '';

		if (!panel) {
			return;
		}

		if (isExpanded) {
			// Close this panel
			closePanel(btn, panel);
			localStorage.removeItem(STORAGE_KEY);
		} else {
			// Close all other panels (single-open mode)
			closeAllPanels();

			// Open clicked panel
			openPanel(btn, panel);

			// Save to localStorage
			if (category) {
				localStorage.setItem(STORAGE_KEY, category);
			}
		}
	}

	/**
	 * Open an accordion panel
	 *
	 * @param {HTMLElement} btn Button element
	 * @param {HTMLElement} panel Panel element
	 */
	function openPanel(btn, panel) {
		btn.setAttribute('aria-expanded', 'true');
		panel.removeAttribute('hidden');
	}

	/**
	 * Close an accordion panel
	 *
	 * @param {HTMLElement} btn Button element
	 * @param {HTMLElement} panel Panel element
	 */
	function closePanel(btn, panel) {
		btn.setAttribute('aria-expanded', 'false');
		panel.setAttribute('hidden', '');
	}

	/**
	 * Close all accordion panels
	 */
	function closeAllPanels() {
		const buttons = document.querySelectorAll('.arbricks-accordion__btn[aria-expanded="true"]');
		buttons.forEach(function(btn) {
			const panelId = btn.getAttribute('aria-controls');
			const panel = document.getElementById(panelId);
			if (panel) {
				closePanel(btn, panel);
			}
		});
	}

	/**
	 * Restore open state from localStorage
	 *
	 * @param {NodeList} buttons All accordion buttons
	 */
	function restoreOpenState(buttons) {
		const savedCategory = localStorage.getItem(STORAGE_KEY);
		let opened = false;

		if (savedCategory) {
			// Try to open the saved category
			buttons.forEach(function(btn) {
				const item = btn.closest('.arbricks-accordion__item');
				if (item && item.dataset.category === savedCategory) {
					const panelId = btn.getAttribute('aria-controls');
					const panel = document.getElementById(panelId);
					if (panel) {
						openPanel(btn, panel);
						opened = true;
					}
				}
			});
		}

		// If no saved state or category not found, open first panel
		if (!opened && buttons.length > 0) {
			const firstBtn = buttons[0];
			const panelId = firstBtn.getAttribute('aria-controls');
			const panel = document.getElementById(panelId);
			if (panel) {
				openPanel(firstBtn, panel);
			}
		}
	}

	// Initialize on DOM ready
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', initAccordion);
	} else {
		initAccordion();
	}
})();
