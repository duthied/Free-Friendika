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

	const l10n = window.AdvancedComposerL10n || {};
	const layout = api.layout || (api.layout = {});

	layout.syncPreviewLayout = function () {
		try {
			const textarea = document.getElementById('profile-jot-text')
				|| document.querySelector('textarea.profile-jot-text')
				|| document.querySelector('textarea.comment-edit-text')
				|| document.querySelector('[id^="comment-edit-text-"]');
			if (!textarea) return;

			const parentForm = textarea.closest('form') || textarea.parentNode;
			if (!parentForm) return;

			const jotPreview = document.getElementById('jot-preview-content');
			const idMatch = textarea.id.match(/comment-edit-text-([a-zA-Z0-9_-]+)/);
			const commentPreview = idMatch ? document.getElementById(`comment-edit-preview-${idMatch[1]}`) : null;
			const previewContainer = commentPreview || jotPreview;

			const isVisible = previewContainer && window.getComputedStyle(previewContainer).display !== 'none';

			const refreshClone = parentForm.querySelector('.ec-refresh-btn');
			if (refreshClone) {
				if (isVisible) {
					refreshClone.style.setProperty('display', 'inline-flex', 'important');
				} else {
					refreshClone.style.setProperty('display', 'none', 'important');
				}
			}

			const dropzone = parentForm.querySelector('.dropzone')
				|| parentForm.querySelector('[id^="dropzone-"]')
				|| document.getElementById('jot-text-wrap');

			if (isVisible && dropzone && previewContainer) {
				parentForm.classList.add('ec-preview-active-form');
				const layoutParent = dropzone.parentNode;
				if (layoutParent) {
					layoutParent.classList.add('ec-split-layout');
				}

				if (textarea.value.length >= 100000) {
					if (!previewContainer.querySelector('.ec-preview-too-long-warning')) {
						if (window.getComputedStyle(previewContainer).position === 'static') {
							previewContainer.style.position = 'relative';
						}

						const warnNode = document.createElement('div');
						warnNode.className = 'ec-preview-too-long-warning';
						warnNode.style.cssText = [
							'position:absolute', 'inset:0', 'z-index:10',
							'display:flex', 'flex-direction:column',
							'align-items:center', 'justify-content:center',
							'text-align:center',
							'background:var(--background-color,#fff)',
							'color:var(--text-color,#333)',
							"font-family:'Outfit','Inter',sans-serif",
							'padding:24px', 'min-height:250px'
						].join(';');

						const warnIcon = document.createElement('i');
						warnIcon.className = 'ri ri-error-warning-line';
						warnIcon.style.fontSize = '48px';
						warnIcon.style.color = '#e05d5d';
						warnIcon.style.marginBottom = '16px';
						const warnTitle = document.createElement('h3');
						warnTitle.style.cssText = 'margin:0 0 8px;font-weight:600;font-size:18px';
						warnTitle.textContent = l10n.previewTooLongTitle;

						const warnDesc = document.createElement('p');
						warnDesc.style.cssText = 'margin:0;font-size:14px;opacity:.8;line-height:1.6;max-width:320px';
						warnDesc.textContent = l10n.previewTooLongDesc;

						warnNode.appendChild(warnIcon);
						warnNode.appendChild(warnTitle);
						warnNode.appendChild(warnDesc);
						previewContainer.appendChild(warnNode);
					}
				} else {
					const warnNode = previewContainer.querySelector('.ec-preview-too-long-warning');
					if (warnNode) {
						warnNode.remove();
					}
				}
			} else {
				parentForm.classList.remove('ec-preview-active-form');
				if (dropzone && dropzone.parentNode) {
					dropzone.parentNode.classList.remove('ec-split-layout');
				}
			}
		} catch (err) {
			// Silent fallback
		}
	};

	layout.getScrollOffsets = function (el) {
		const offsets = [];
		let parent = el;
		while (parent && parent !== document.body && parent !== document.documentElement) {
			if (parent.scrollTop || parent.scrollLeft) {
				offsets.push({
					element: parent,
					top: parent.scrollTop,
					left: parent.scrollLeft
				});
			}
			parent = parent.parentNode;
		}
		offsets.push({
			element: window,
			top: window.scrollY || window.pageYOffset || document.documentElement.scrollTop,
			left: window.scrollX || window.pageXOffset || document.documentElement.scrollLeft
		});
		return offsets;
	};

	layout.restoreScrollOffsets = function (offsets) {
		if (!offsets) return;
		offsets.forEach(function (offset) {
			try {
				if (offset.element === window) {
					window.scrollTo(offset.left, offset.top);
				} else {
					offset.element.scrollTop = offset.top;
					offset.element.scrollLeft = offset.left;
				}
			} catch (e) { }
		});
	};
})(window);