// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

// @license magnet:?xt=urn:btih=0b31508aeb0634b347b8270c7bee4d411b5d4109&dn=agpl-3.0.txt AGPLv3-or-later
(function (window) {
	'use strict';

	const api = window.AdvancedComposerLibrary;
	if (!api) {
		return;
	}

	// Compatibility shim: real focus-preview implementation is in advancedcomposer-preview.js.
	// Register a proxy handler only when no concrete handler was registered yet.
	if (!api.getHandler('openFocusPreview')) {
		api.registerHandler('openFocusPreview', function () {
			const context = api.getContext();
			if (typeof context.openFocusPreview === 'function') {
				return context.openFocusPreview();
			}
		});
	}

	api.bindGlobalAction('openFocusPreview', 'AdvancedComposerOpenFocusPreview');
})(window);