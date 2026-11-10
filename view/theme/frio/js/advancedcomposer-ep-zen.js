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

	api.registerHandler('toggleEpZen', function () {
		const context = api.getContext();
		if (!context.epZenToggleBtn) {
			return;
		}

		const active = document.body.classList.toggle('ec-show-ep-list-in-zen');
		context.epZenToggleBtn.classList.toggle('active', active);
	});

	api.bindGlobalAction('toggleEpZen', 'AdvancedComposerToggleEpZen');
})(window);