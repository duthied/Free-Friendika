// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

// @license magnet:?xt=urn:btih:0b31508aeb0634b347b8270c7bee4d411b5d4109&dn=agpl-3.0.txt AGPLv3-or-later
(function (window) {
	'use strict';

	const api = window.AdvancedComposerLibrary;
	if (!api) {
		return;
	}

	api.registerHandler('togglePanel', function () {
		const context = api.getContext();
		if (!context.panel || !context.toggleBtn || !context.textarea) {
			return;
		}

		context.panel.classList.toggle('collapsed');
		const isOpen = !context.panel.classList.contains('collapsed');
		context.toggleBtn.classList.toggle('active', isOpen);
		try {
			sessionStorage.setItem('ec_panel_open', isOpen ? 'true' : 'false');
		} catch (e) { }
		if (isOpen) {
			if (typeof context.runAnalysis === 'function') {
				context.runAnalysis(context.textarea.value);
			}
			if (typeof context.startValuePolling === 'function') {
				context.startValuePolling();
			}
		} else if (typeof context.stopValuePolling === 'function') {
			context.stopValuePolling();
		}
	});

	api.bindGlobalAction('togglePanel', 'AdvancedComposerTogglePanel');
})(window);