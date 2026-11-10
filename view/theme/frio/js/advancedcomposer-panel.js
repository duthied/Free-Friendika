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
	const panelApi = api.panel || (api.panel = {});

	panelApi.createPanel = function (options) {
		const textarea = options && options.textarea;
		const parentForm = options && options.parentForm;
		const jotWrap = options && options.jotWrap;
		const toggleBtn = options && options.toggleBtn;
		if (!textarea || !parentForm || !jotWrap) {
			return null;
		}

		const panel = document.createElement('div');
		panel.id = 'easy-compose-panel';
		panel.className = 'easy-compose-panel collapsed';

		try {
			if (sessionStorage.getItem('ec_panel_open') === 'true') {
				panel.classList.remove('collapsed');
				if (toggleBtn) {
					toggleBtn.classList.add('active');
				}
			}
		} catch (e) { }

		try {
			const computedBg = window.getComputedStyle(textarea).backgroundColor;
			const rgb = computedBg.match(/\d+/g);
			if (rgb && rgb.length >= 3) {
				const r = parseInt(rgb[0], 10);
				const g = parseInt(rgb[1], 10);
				const b = parseInt(rgb[2], 10);
				const brightness = (r * 299 + g * 587 + b * 114) / 1000;
				if (brightness < 120) {
					panel.classList.add('dark-theme');
					parentForm.classList.add('ec-dark-theme');
					document.body.classList.add('ec-dark-theme');
				} else {
					parentForm.classList.remove('ec-dark-theme');
					document.body.classList.remove('ec-dark-theme');
				}
			}
		} catch (e) { }

		panel.innerHTML = '';

		const header = document.createElement('div');
		header.className = 'ec-header';

		const headerTitle = document.createElement('div');
		headerTitle.className = 'ec-header-title';

		const titleSpan = document.createElement('span');
		titleSpan.className = 'ec-title-text';
		titleSpan.textContent = l10n.title;

		const subtitleSpan = document.createElement('span');
		subtitleSpan.className = 'ec-subtitle-text';
		subtitleSpan.textContent = l10n.subtitle;

		headerTitle.appendChild(titleSpan);
		headerTitle.appendChild(subtitleSpan);

		const charCountSlot = document.createElement('span');
		charCountSlot.id = 'ec-char-count-slot';
		charCountSlot.className = 'ec-char-count';

		const headerCloseBtn = document.createElement('button');
		headerCloseBtn.type = 'button';
		headerCloseBtn.id = 'easy-compose-close';
		headerCloseBtn.className = 'ec-close-btn';
		headerCloseBtn.title = l10n.btnClose;
		headerCloseBtn.textContent = l10n.btnCloseSymbol;

		header.appendChild(headerTitle);
		header.appendChild(charCountSlot);
		header.appendChild(headerCloseBtn);

		const helpDisclosure = document.createElement('details');
		helpDisclosure.className = 'ec-help-disclosure';

		const helpSummary = document.createElement('summary');
		helpSummary.className = 'ec-help-summary';

		const helpIconSpan = document.createElement('span');
		helpIconSpan.className = 'ec-help-icon';
		helpIconSpan.setAttribute('aria-hidden', 'true');

		const helpIcon = document.createElement('i');
		helpIcon.className = 'ri ri-question-line';
		helpIcon.setAttribute('aria-hidden', 'true');
		helpIconSpan.appendChild(helpIcon);

		const helpTextSpan = document.createElement('span');
		helpTextSpan.textContent = l10n.helpToggleLabel;

		helpSummary.appendChild(helpIconSpan);
		helpSummary.appendChild(helpTextSpan);

		const helpBody = document.createElement('div');
		helpBody.className = 'ec-help-body';

		const privacyBadge = document.createElement('div');
		privacyBadge.className = 'ec-help-privacy-badge';

		const privacyIcon = document.createElement('i');
		privacyIcon.className = 'ri ri-shield-check-line';
		privacyIcon.setAttribute('aria-hidden', 'true');
		privacyBadge.appendChild(privacyIcon);

		const privacyBadgeStrong = document.createElement('strong');
		privacyBadgeStrong.textContent = l10n.helpPrivacyBadge;
		privacyBadge.appendChild(privacyBadgeStrong);

		const privacyText = document.createElement('p');
		privacyText.className = 'ec-help-privacy-text';
		privacyText.textContent = l10n.helpPrivacyDetail;

		const divider = document.createElement('hr');
		divider.className = 'ec-help-divider';

		const criteriaDl = document.createElement('dl');
		criteriaDl.className = 'ec-help-criteria';

		const criteria = [
			{ title: l10n.helpParaTitle, body: l10n.helpParaBody },
			{ title: l10n.helpBalanceTitle, body: l10n.helpBalanceBody },
			{ title: l10n.helpLinkTitle, body: l10n.helpLinkBody },
			{ title: l10n.helpHashtagTitle, body: l10n.helpHashtagBody },
			{ title: l10n.helpAltTitle, body: l10n.helpAltBody },
			{ title: l10n.helpEmojiTitle, body: l10n.helpEmojiBody },
			{ title: l10n.helpParagraphA11yTitle, body: l10n.helpParagraphA11yBody }
		];

		criteria.forEach(function (item) {
			const dt = document.createElement('dt');
			dt.textContent = item.title;
			const dd = document.createElement('dd');
			dd.textContent = item.body;
			criteriaDl.appendChild(dt);
			criteriaDl.appendChild(dd);
		});

		helpBody.appendChild(privacyBadge);
		helpBody.appendChild(privacyText);
		helpBody.appendChild(divider);
		helpBody.appendChild(criteriaDl);

		helpDisclosure.appendChild(helpSummary);
		helpDisclosure.appendChild(helpBody);

		const body = document.createElement('div');
		body.className = 'ec-body';

		const structureSection = document.createElement('div');
		structureSection.className = 'ec-section ec-structure';
		const structureTitle = document.createElement('h4');
		structureTitle.className = 'ec-section-title';
		structureTitle.textContent = l10n.structureTitle;
		const indicatorGroup = document.createElement('div');
		indicatorGroup.className = 'ec-indicator-group';

		const indicators = [
			{ id: 'paragraphs', label: l10n.lblParagraphs },
			{ id: 'sentence-length', label: l10n.lblSentenceLength },
			{ id: 'links', label: l10n.lblLinks },
			{ id: 'hashtags', label: l10n.lblHashtags }
		];

		indicators.forEach(function (ind) {
			const indicator = document.createElement('div');
			indicator.className = 'ec-indicator';
			const labelDiv = document.createElement('div');
			labelDiv.className = 'ec-indicator-label';
			const labelSpan = document.createElement('span');
			labelSpan.textContent = ind.label;
			const valSpan = document.createElement('span');
			valSpan.id = `ec-val-${ind.id}`;
			valSpan.className = 'ec-indicator-value';
			valSpan.textContent = '-';
			labelDiv.appendChild(labelSpan);
			labelDiv.appendChild(valSpan);
			const progressBar = document.createElement('div');
			progressBar.className = 'ec-progress-bar';
			const progressFill = document.createElement('div');
			progressFill.id = `ec-bar-${ind.id}`;
			progressFill.className = 'ec-progress-fill';
			progressBar.appendChild(progressFill);
			indicator.appendChild(labelDiv);
			indicator.appendChild(progressBar);
			indicatorGroup.appendChild(indicator);
		});

		structureSection.appendChild(structureTitle);
		structureSection.appendChild(indicatorGroup);

		const a11ySection = document.createElement('div');
		a11ySection.className = 'ec-section ec-a11y';
		const a11yTitle = document.createElement('h4');
		a11yTitle.className = 'ec-section-title';
		a11yTitle.textContent = l10n.a11yTitle;
		const checklistUl = document.createElement('ul');
		checklistUl.className = 'ec-checklist';

		const checklistItems = [
			{ id: 'ec-chk-alt', text: l10n.a11yAltOk },
			{ id: 'ec-chk-emoji', text: l10n.a11yEmojiOk },
			{ id: 'ec-chk-paragraphs', text: l10n.a11yParagraphOk }
		];

		checklistItems.forEach(function (item) {
			const li = document.createElement('li');
			li.id = item.id;
			li.className = 'ec-checklist-item';
			const iconSpan = document.createElement('span');
			iconSpan.className = 'ec-icon';
			const textSpan = document.createElement('span');
			textSpan.className = 'ec-chk-text';
			textSpan.textContent = item.text;
			li.appendChild(iconSpan);
			li.appendChild(textSpan);
			checklistUl.appendChild(li);
		});

		a11ySection.appendChild(a11yTitle);
		a11ySection.appendChild(checklistUl);

		const tipsSection = document.createElement('div');
		tipsSection.className = 'ec-section ec-tips';
		const tipsTitle = document.createElement('h4');
		tipsTitle.className = 'ec-section-title';
		tipsTitle.textContent = l10n.readabilityTitle;
		const tipsContainer = document.createElement('div');
		tipsContainer.id = 'ec-tips-container';
		tipsContainer.className = 'ec-tips-box';
		const tipSuccess = document.createElement('div');
		tipSuccess.className = 'ec-tip ec-tip-success';
		tipSuccess.textContent = l10n.tipExcellent;
		tipsContainer.appendChild(tipSuccess);
		tipsSection.appendChild(tipsTitle);
		tipsSection.appendChild(tipsContainer);

		body.appendChild(structureSection);
		body.appendChild(a11ySection);
		body.appendChild(tipsSection);

		const brandDiv = document.createElement('div');
		brandDiv.className = 'ec-addon-brand';
		const fullText = l10n.brandText;
		const match = fullText.match(/(settings|Einstellungen)/i);
		if (match) {
			const keyword = match[0];
			const parts = fullText.split(keyword);
			const textBefore = document.createTextNode(parts[0]);
			const link = document.createElement('a');
			link.href = 'settings/addons';
			link.className = 'ec-brand-settings-link';
			link.textContent = keyword;
			const textAfter = document.createTextNode(parts[1]);
			brandDiv.appendChild(textBefore);
			brandDiv.appendChild(link);
			brandDiv.appendChild(textAfter);
		} else {
			brandDiv.textContent = fullText;
		}

		panel.appendChild(header);
		panel.appendChild(helpDisclosure);
		panel.appendChild(body);
		panel.appendChild(brandDiv);

		const jotPreview = document.getElementById('jot-preview-content')
			|| document.querySelector('[id^="comment-edit-preview-"]');
		if (jotPreview && jotPreview.parentNode === jotWrap.parentNode) {
			jotPreview.parentNode.insertBefore(panel, jotPreview.nextSibling);
		} else {
			jotWrap.parentNode.insertBefore(panel, jotWrap.nextSibling);
		}

		return panel;
	};
})(window);
