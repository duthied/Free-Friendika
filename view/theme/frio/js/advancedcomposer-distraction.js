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

	api.registerHandler('toggleDistractionFree', function () {
		const context = api.getContext();
		if (!context.distractionBtn || !context.textarea) {
			return;
		}

		const active = document.body.classList.toggle('ec-distraction-free');
		context.distractionBtn.classList.toggle('active', active);
		if (active) {
			api.buildButtonContent(context.distractionBtn, 'ri ri-fullscreen-exit-line', context.l10n.btnZen);
			try {
				const previewContainer = context.parentForm && (context.parentForm.querySelector('.comment-edit-preview') || context.parentForm.querySelector('[id^="comment-edit-preview-"]'));
				const previewBtn = context.parentForm && context.parentForm.querySelector('[id^="comment-edit-preview-link-"]');
				if (previewContainer && previewBtn && window.getComputedStyle(previewContainer).display !== 'none') {
					previewBtn.click();
				}
			} catch (err) { }
		} else {
			api.buildButtonContent(context.distractionBtn, 'ri ri-fullscreen-line', context.l10n.btnZen);
			document.body.classList.remove('ec-show-ep-list-in-zen');
			if (context.epZenToggleBtn) {
				context.epZenToggleBtn.classList.remove('active');
			}
		}
		if (typeof context.adjustTextareaHeight === 'function') {
			context.adjustTextareaHeight(true);
		}
	});

	api.bindGlobalAction('toggleDistractionFree', 'AdvancedComposerToggleDistractionFree');
})(window);