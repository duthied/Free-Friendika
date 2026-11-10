// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

// @license magnet:?xt=urn:btih:0b31508aeb0634b347b8270c7bee4d411b5d4109&dn=agpl-3.0.txt AGPLv3-or-later
/**
 * Advanced Composer - Writing & Accessibility Assistant
 */
(function () {
	'use strict';

	// URL-Guard: Exit immediately if we are not on the standalone compose page.
	// Friendica 2026.05 uses clean routing (/compose); the search-string check is
	// retained only as a cheap, harmless tolerance for proxy/rewrite edge cases and
	// never broadens the match beyond compose pages.
	if (!window.location.pathname.includes('/compose') && !window.location.search.includes('compose')) {
		return;
	}

	// Single namespaced state object on window to avoid polluting the global scope
	// with multiple ad-hoc flags. This minimises the risk of name collisions when
	// Advanced Composer runs alongside many other addons on the same page.
	const acState = window.__advancedcomposer || (window.__advancedcomposer = {
		emojiCaptureBound: false,
		keydownBound: false,
		wasNativePreviewVisible: null,
		previewDebounceTimer: null
	});

	// Localizations passed from PHP (must be defined by server-side PHP)
	const l10n = window.AdvancedComposerL10n || {};
	const acLib = window.AdvancedComposerLibrary || null;
	const analysisApi = (acLib && acLib.analysis) || {};
	const layoutApi = (acLib && acLib.layout) || {};
	const panelApi = (acLib && acLib.panel) || {};
	const watchersApi = (acLib && acLib.watchers) || {};
	const runAnalysis = typeof analysisApi.runAnalysis === 'function'
		? function (text) { return analysisApi.runAnalysis(text); }
		: function () {};
	const updateCharacterCountSlot = typeof analysisApi.updateCharacterCountSlot === 'function'
		? function () { return analysisApi.updateCharacterCountSlot(); }
		: function () {};
	const updateIndicator = typeof analysisApi.updateIndicator === 'function'
		? function (id, score, label) { return analysisApi.updateIndicator(id, score, label); }
		: function () {};
	const syncPreviewLayout = typeof layoutApi.syncPreviewLayout === 'function'
		? function () { return layoutApi.syncPreviewLayout(); }
		: function () {};
	const getScrollOffsets = typeof layoutApi.getScrollOffsets === 'function'
		? function (el) { return layoutApi.getScrollOffsets(el); }
		: function () { return []; };
	const restoreScrollOffsets = typeof layoutApi.restoreScrollOffsets === 'function'
		? function (offsets) { return layoutApi.restoreScrollOffsets(offsets); }
		: function () {};

	let initInterval;

	// Robust polling to safely attach to the composer whenever it enters the DOM
	function initAdvancedComposer() {
		// Completely abort on mobile/tablet viewports to protect mobile composer UX and save resources
		if (window.innerWidth < 992) {
			if (initInterval) {
				clearInterval(initInterval);
				initInterval = null;
			}
			return;
		}

		// 1. Find the editor textarea (supports standard composer and standalone /compose)
		const textarea = document.getElementById('profile-jot-text')
			|| document.querySelector('textarea.profile-jot-text')
			|| document.querySelector('textarea.comment-edit-text')
			|| document.querySelector('[id^="comment-edit-text-"]');
		if (!textarea) return;

		const parentForm = textarea.closest('form') || textarea.parentNode;
		if (!parentForm) return;

		// Prevent double-binding
		if (textarea.dataset.advancedComposerAttached === 'true') {
			if (initInterval) {
				clearInterval(initInterval);
				initInterval = null;
			}
			return;
		}
		textarea.dataset.advancedComposerAttached = 'true';

		// Stop dynamic polling since editor is found and bound
		if (initInterval) {
			clearInterval(initInterval);
			initInterval = null;
		}

		// 2. Find the wrapper container for inserting the panel (directly below the editor box)
		const jotWrap = document.getElementById('jot-text-wrap')
			|| textarea.closest('.dropzone')
			|| textarea.parentNode;
		if (!jotWrap) return;

		// 3. Find the visible toolbar/button group
		const toolbarGroup = document.querySelector('.btn-toolbar')
			|| document.querySelector('#profile-jot-submit-wrapper')
			|| document.querySelector('.profile-jot-submit-wrapper');
		if (toolbarGroup) {
			toolbarGroup.classList.add('ec-toolbar-active');
		}

		// 4. Get references to the static buttons from the template
		const toggleBtn = document.getElementById('easy-compose-toggle');
		const distractionBtn = document.getElementById('easy-compose-distraction-toggle');
		const focusPreviewBtn = document.getElementById('easy-compose-focus-preview-toggle');
		const epZenToggleBtn = document.getElementById('easy-compose-ep-zen-toggle');

		// If buttons don't exist, Advanced Composer is disabled - abort
		if (!toggleBtn || !distractionBtn || !focusPreviewBtn || !epZenToggleBtn) {
			return;
		}

		acState.handlers = acState.handlers || {};
		acState.context = acState.context || {};
		Object.assign(acState.context, {
			l10n,
			textarea,
			parentForm,
			toggleBtn,
			distractionBtn,
			focusPreviewBtn,
			epZenToggleBtn
		});

		const toggleEpZenHandler = acLib && typeof acLib.getHandler === 'function'
			? acLib.getHandler('toggleEpZen')
			: null;
		acState.context.toggleEpZen = function () {
			if (typeof toggleEpZenHandler === 'function') {
				return toggleEpZenHandler();
			}
			if (acLib && typeof acLib.invokeHandler === 'function') {
				return acLib.invokeHandler('toggleEpZen');
			}
		};

		const panel = panelApi && typeof panelApi.createPanel === 'function'
			? panelApi.createPanel({
				textarea,
				parentForm,
				jotWrap,
				toggleBtn
			})
			: null;
		if (!panel) {
			return;
		}

		// Trigger initial sync of character counter
		updateCharacterCountSlot();

		acState.context.panel = panel;
		acState.context.runAnalysis = runAnalysis;
		acState.context.syncPreviewLayout = syncPreviewLayout;
		acState.context.adjustTextareaHeight = adjustTextareaHeight;

		function adjustTextareaHeight(forceReset) {
			if (!document.body.classList.contains('ec-distraction-free')) {
				if (forceReset) {
					textarea.style.height = ''; // Reset height only when exiting Zen mode
				}
				return;
			}
			// Capture the current window scroll position before modifying heights.
			// This prevents the browser from jumping to the top of the page when
			// the textarea height momentarily collapses during height calculation.
			const scrollPos = window.scrollY || window.pageYOffset;
			textarea.style.height = 'auto';
			const sh = textarea.scrollHeight;
			textarea.style.height = (sh > 0 ? sh : textarea.offsetHeight) + 'px';
			window.scrollTo(window.scrollX, scrollPos);
		}

		const watcherControls = watchersApi && typeof watchersApi.setup === 'function'
			? watchersApi.setup({
				acState,
				textarea,
				parentForm,
				runAnalysis,
				syncPreviewLayout,
				adjustTextareaHeight,
				getScrollOffsets,
				restoreScrollOffsets
			})
			: null;
		const startValuePolling = watcherControls && watcherControls.startValuePolling
			? watcherControls.startValuePolling
			: function () {};
		const stopValuePolling = watcherControls && watcherControls.stopValuePolling
			? watcherControls.stopValuePolling
			: function () {};

		const togglePanel = function () {
			if (acLib && typeof acLib.invokeHandler === 'function') {
				return acLib.invokeHandler('togglePanel');
			}
		};

		acState.context.togglePanel = togglePanel;
		acState.context.startValuePolling = startValuePolling;
		acState.context.stopValuePolling = stopValuePolling;

		const toggleDistractionFree = function () {
			if (acLib && typeof acLib.invokeHandler === 'function') {
				return acLib.invokeHandler('toggleDistractionFree');
			}
		};

		acState.context.toggleDistractionFree = toggleDistractionFree;

		// Event: Escape key exits distraction free mode
		// Guard: register only once per page load, even if initAdvancedComposer runs multiple times
		if (!acState.keydownBound) {
			acState.keydownBound = true;
			document.addEventListener('keydown', function (e) {
				if (e.key === 'Escape' && document.body.classList.contains('ec-distraction-free')) {
					toggleDistractionFree();
				}
			});
		}

		// Trigger initial analysis & preview check
		runAnalysis(textarea.value);
		syncPreviewLayout();

		// Re-check shortly after startup so integrations loaded later,
		// especially EasyPhoto's .ep-list, are detected reliably.
		[250, 1000].forEach(function (delay) {
			setTimeout(function () {
				if (document.body.contains(textarea)) {
					runAnalysis(textarea.value);
				}
			}, delay);
		});


		// If panel was restored as open, start the fallback value watcher immediately
		if (!panel.classList.contains('collapsed')) {
			startValuePolling();
		}
	}

	// Attach to composer on load and periodically to handle AJAX-loaded editors
	initInterval = setInterval(initAdvancedComposer, 1000);
	initAdvancedComposer();

	// Cleanup: stop all intervals when the page is unloaded to prevent memory leaks
	// in browsers that keep the JS context alive across navigation (bfcache).
	window.addEventListener('pagehide', function () {
		if (initInterval) {
			clearInterval(initInterval);
			initInterval = null;
		}
	});
})();
