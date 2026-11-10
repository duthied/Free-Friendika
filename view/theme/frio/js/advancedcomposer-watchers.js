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

	api.watchers = api.watchers || {};

	api.watchers.setup = function (options) {
		if (!options || !options.textarea) {
			return null;
		}

		const textarea = options.textarea;
		const parentForm = options.parentForm || (textarea.closest('form') || textarea.parentNode);
		const acState = options.acState || (window.__advancedcomposer = window.__advancedcomposer || {});
		const runAnalysis = options.runAnalysis;
		const syncPreviewLayout = options.syncPreviewLayout;
		const adjustTextareaHeight = options.adjustTextareaHeight;
		const getScrollOffsets = options.getScrollOffsets;
		const restoreScrollOffsets = options.restoreScrollOffsets;

		let lastValue = textarea.value;
		let savedSelectionStart = 0;
		let savedSelectionEnd = 0;
		try {
			savedSelectionStart = textarea.selectionStart || 0;
			savedSelectionEnd = textarea.selectionEnd || 0;
		} catch (e) { }

		let watcherDebounce;
		let inputDebounce;
		let normalPreviewDebounceTimer;
		let valuePollingInterval = null;

		function saveSelection() {
			try {
				savedSelectionStart = textarea.selectionStart;
				savedSelectionEnd = textarea.selectionEnd;
			} catch (e) { }
		}

		function restoreCaretAfterProgrammaticChange() {
			const diff = textarea.value.length - lastValue.length;
			lastValue = textarea.value;

			const active = document.activeElement;
			if (active && active !== textarea && (active.tagName === 'INPUT' || active.tagName === 'TEXTAREA')) {
				return;
			}

			let newStart = savedSelectionStart;
			let newEnd = savedSelectionEnd;
			if (diff > 0) {
				newStart = savedSelectionStart + diff;
				newEnd = savedSelectionStart + diff;
			}

			const scrollOffsets = typeof getScrollOffsets === 'function' ? getScrollOffsets(textarea) : [];
			setTimeout(function () {
				try {
					textarea.focus({ preventScroll: true });
					textarea.setSelectionRange(newStart, newEnd);
					savedSelectionStart = newStart;
					savedSelectionEnd = newEnd;
					if (typeof restoreScrollOffsets === 'function') {
						restoreScrollOffsets(scrollOffsets);
					}
				} catch (e) { }
			}, 10);
		}

		function startValuePolling() {
			if (valuePollingInterval) return;
			valuePollingInterval = setInterval(function () {
				if (!document.body.contains(textarea)) {
					stopValuePolling();
					return;
				}
				if (textarea.value !== lastValue) {
					restoreCaretAfterProgrammaticChange();
					if (typeof runAnalysis === 'function') {
						runAnalysis(textarea.value);
					}
					if (typeof syncPreviewLayout === 'function') {
						syncPreviewLayout();
					}
					if (typeof adjustTextareaHeight === 'function') {
						adjustTextareaHeight();
					}
				}
			}, 800);
		}

		function stopValuePolling() {
			if (valuePollingInterval) {
				clearInterval(valuePollingInterval);
				valuePollingInterval = null;
			}
		}

		if (!acState.emojiCaptureBound) {
			acState.emojiCaptureBound = true;
			document.addEventListener('click', function (e) {
				const emojiLink = e.target.closest('.fg-emoji-list a');
				if (!emojiLink) return;
				if (!document.body.contains(textarea)) return;

				const emojiText = emojiLink.textContent;
				if (!emojiText) return;

				const savedStart = savedSelectionStart;
				const savedEnd = savedSelectionEnd;
				const newPos = savedStart + emojiText.length;
				lastValue = textarea.value.substring(0, savedStart) + emojiText + textarea.value.substring(savedEnd);

				clearTimeout(inputDebounce);
				inputDebounce = setTimeout(function () {
					if (typeof runAnalysis === 'function') {
						runAnalysis(lastValue);
					}
					if (typeof syncPreviewLayout === 'function') {
						syncPreviewLayout();
					}
				}, 300);

				const scrollOffsets = typeof getScrollOffsets === 'function' ? getScrollOffsets(textarea) : [];
				[10, 50, 100, 200].forEach(function (delay) {
					setTimeout(function () {
						try {
							textarea.focus({ preventScroll: true });
							textarea.setSelectionRange(newPos, newPos);
							savedSelectionStart = newPos;
							savedSelectionEnd = newPos;
							if (typeof restoreScrollOffsets === 'function') {
								restoreScrollOffsets(scrollOffsets);
							}
						} catch (err) { }
					}, delay);
				});
			}, true);
		}

		textarea.addEventListener('keyup', function () {
			lastValue = textarea.value;
			saveSelection();
			if (typeof adjustTextareaHeight === 'function') {
				adjustTextareaHeight();
			}
		});
		textarea.addEventListener('click', saveSelection);
		textarea.addEventListener('focus', saveSelection);
		textarea.addEventListener('drop', function () {
			setTimeout(function () {
				lastValue = textarea.value;
				saveSelection();
			}, 1);
		});
		textarea.addEventListener('input', function () {
			lastValue = textarea.value;
			saveSelection();
			if (typeof adjustTextareaHeight === 'function') {
				adjustTextareaHeight();
			}
			clearTimeout(inputDebounce);
			inputDebounce = setTimeout(function () {
				if (typeof runAnalysis === 'function') {
					runAnalysis(textarea.value);
				}
				if (typeof syncPreviewLayout === 'function') {
					syncPreviewLayout();
				}
			}, 300);
		});

		let normalPreviewBtn = null;
		let normalPreviewFormId = null;
		let normalPreviewRefreshBtn = null;
		const textareaIdMatch = textarea.id.match(/comment-edit-text-([a-zA-Z0-9_-]+)/);
		if (textareaIdMatch) {
			normalPreviewFormId = textareaIdMatch[1];
			normalPreviewBtn = document.getElementById(`comment-edit-preview-link-${normalPreviewFormId}`)
				|| (parentForm && parentForm.querySelector('[id^="comment-edit-preview-link-"]'))
				|| document.getElementById('jot-preview-link');
			normalPreviewRefreshBtn = document.getElementById(`fc-toolbar-refresh-${normalPreviewFormId}`)
				|| (parentForm && parentForm.querySelector('.ec-refresh-btn'));
		} else {
			normalPreviewBtn = document.getElementById('jot-preview-link');
			normalPreviewRefreshBtn = parentForm && parentForm.querySelector('.ec-refresh-btn');
		}

		let normalPreviewContainer = null;
		if (normalPreviewBtn || normalPreviewRefreshBtn) {
			if (normalPreviewFormId && parentForm) {
				normalPreviewContainer = parentForm.querySelector(`#comment-edit-preview-${normalPreviewFormId}`)
					|| parentForm.querySelector('.comment-edit-preview')
					|| document.getElementById('jot-preview-content');
			} else if (parentForm) {
				normalPreviewContainer = parentForm.querySelector('.comment-edit-preview')
					|| parentForm.querySelector('[id^="comment-edit-preview-"]')
					|| document.getElementById('jot-preview-content');
			}
		}

		if (normalPreviewBtn || normalPreviewRefreshBtn) {
			let lastScrollHeight = 0;
			const normalPreviewInputHandler = function () {
				clearTimeout(normalPreviewDebounceTimer);
				normalPreviewDebounceTimer = setTimeout(function () {
					let isPreviewVisible = false;
					if (normalPreviewContainer && document.body.contains(normalPreviewContainer)) {
						isPreviewVisible = window.getComputedStyle(normalPreviewContainer).display !== 'none';
						lastScrollHeight = normalPreviewContainer.scrollHeight;
					}
					if (isPreviewVisible) {
						const scrollToBottom = function () {
							if (normalPreviewContainer && document.body.contains(normalPreviewContainer)) {
								try {
									const currentScrollHeight = normalPreviewContainer.scrollHeight;
									if (currentScrollHeight > lastScrollHeight) {
										normalPreviewContainer.scrollTop = currentScrollHeight;
										lastScrollHeight = currentScrollHeight;
									} else {
										normalPreviewContainer.scrollTop = currentScrollHeight;
									}
								} catch (e) { }
							}
						};
						try {
							if (normalPreviewRefreshBtn && document.body.contains(normalPreviewRefreshBtn)) {
								normalPreviewRefreshBtn.click();
							} else if (typeof preview_comment === 'function' && normalPreviewFormId) {
								preview_comment(normalPreviewFormId);
							} else if (normalPreviewBtn && document.body.contains(normalPreviewBtn)) {
								normalPreviewBtn.click();
							}
							setTimeout(scrollToBottom, 800);
							setTimeout(scrollToBottom, 1500);
						} catch (e) { }
					}
				}, 2000);
			};
			textarea.addEventListener('input', normalPreviewInputHandler);
		}

		if (parentForm) {
			parentForm.addEventListener('click', function (e) {
				if (e.target.closest('button') || e.target.closest('.fakelink') || e.target.closest('a') || e.target.closest('#profile-smiley-wrapper span')) {
					setTimeout(function () {
						lastValue = textarea.value;
						if (typeof runAnalysis === 'function') {
							runAnalysis(textarea.value);
						}
						if (typeof syncPreviewLayout === 'function') {
							syncPreviewLayout();
						}
						if (typeof adjustTextareaHeight === 'function') {
							adjustTextareaHeight();
						}
					}, 200);
				}
			});
		}

		const watcherObserver = new MutationObserver(function () {
			if (!document.body.contains(textarea)) {
				watcherObserver.disconnect();
				return;
			}
			if (textarea.value !== lastValue) {
				restoreCaretAfterProgrammaticChange();
				if (typeof adjustTextareaHeight === 'function') {
					adjustTextareaHeight();
				}
				clearTimeout(watcherDebounce);
				watcherDebounce = setTimeout(function () {
					if (typeof runAnalysis === 'function') {
						runAnalysis(textarea.value);
					}
					if (typeof syncPreviewLayout === 'function') {
						syncPreviewLayout();
					}
				}, 300);
			}
		});
		watcherObserver.observe(textarea.parentNode || document.body, {
			childList: true,
			subtree: true,
			attributes: true,
			attributeFilter: ['value', 'data-value']
		});

		const watcherRemovalObserver = new MutationObserver(function () {
			if (!document.body.contains(textarea)) {
				watcherObserver.disconnect();
				watcherRemovalObserver.disconnect();
				stopValuePolling();
			}
		});
		watcherRemovalObserver.observe(document.body, { childList: true, subtree: true });

		return {
			startValuePolling,
			stopValuePolling
		};
	};
})(window);
