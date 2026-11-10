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
	const analysis = api.analysis || (api.analysis = {});

	analysis.updateCharacterCountSlot = function () {
		try {
			const nativeCounter = document.getElementById('character-counter');
			const ecSlot = document.getElementById('ec-char-count-slot');
			if (nativeCounter && ecSlot) {
				ecSlot.textContent = nativeCounter.textContent;
				ecSlot.style.display = nativeCounter.style.display || '';
			}
		} catch (e) { }
	};

	analysis.updateIndicator = function (id, score, label) {
		const valSpan = document.getElementById(`ec-val-${id}`);
		const barFill = document.getElementById(`ec-bar-${id}`);

		if (valSpan) {
			valSpan.textContent = label;
		}

		if (barFill) {
			barFill.style.width = `${score}%`;

			barFill.classList.remove('fill-green', 'fill-yellow', 'fill-red');
			if (score >= 80) {
				barFill.classList.add('fill-green');
			} else if (score >= 50) {
				barFill.classList.add('fill-yellow');
			} else {
				barFill.classList.add('fill-red');
			}
		}
	};

	analysis.runAnalysis = function (text) {
		analysis.updateCharacterCountSlot();
		const cleanText = text.trim();
		const textLength = cleanText.length;

		if (textLength > 100000) {
			const tipsContainer = document.getElementById('ec-tips-container');
			if (tipsContainer) {
				tipsContainer.textContent = '';
				const warnDiv = document.createElement('div');
				warnDiv.className = 'ec-tip ec-tip-warn';
				warnDiv.textContent = l10n.tipTooLong;
				tipsContainer.appendChild(warnDiv);
			}
			return;
		}

		const paragraphs = cleanText.split(/\n\s*\n/).filter(function (p) { return p.trim().length > 0; });
		const paragraphCount = paragraphs.length;

		let paragraphScore = 100;
		let paragraphLabel = l10n.lblParaBalanced;
		if (textLength > 600 && paragraphCount === 1) {
			paragraphScore = 30;
			paragraphLabel = l10n.lblParaOneBlock;
		} else if (textLength > 1200 && paragraphCount < 3) {
			paragraphScore = 50;
			paragraphLabel = l10n.lblParaCompact;
		} else if (paragraphCount >= 3) {
			paragraphScore = 90;
			paragraphLabel = l10n.lblParaStructured;
		}

		const sentences = cleanText.split(/[.!?]+(?:\s|$)/).filter(function (s) { return s.trim().length > 0; });
		let totalWords = 0;
		let maxSentenceWords = 0;
		let longSentencesCount = 0;

		sentences.forEach(function (sentence) {
			const words = sentence.trim().split(/\s+/).filter(function (w) { return w.length > 0; });
			const count = words.length;
			totalWords += count;
			if (count > maxSentenceWords) {
				maxSentenceWords = count;
			}
			if (count > 25) {
				longSentencesCount++;
			}
		});

		const avgSentenceWords = sentences.length > 0 ? (totalWords / sentences.length) : 0;
		let balanceScore = 100;
		let balanceLabel = l10n.lblBalanceEasy;

		if (avgSentenceWords > 24 || longSentencesCount > 1) {
			balanceScore = 40;
			balanceLabel = l10n.lblBalanceNested;
		} else if (avgSentenceWords > 16 || longSentencesCount > 0) {
			balanceScore = 75;
			balanceLabel = l10n.lblBalanceMedium;
		}

		const linkMatches = cleanText.match(/https?:\/\/[^\s\[\]]+/g) || [];
		const linkCount = linkMatches.length;
		let linkScore = 100;
		let linkLabel = l10n.lblLinkSubtle;

		if (linkCount > 5) {
			linkScore = 30;
			linkLabel = l10n.lblLinkDense;
		} else if (linkCount > 3) {
			linkScore = 60;
			linkLabel = l10n.lblLinkMany;
		}

		const hashtagMatches = cleanText.match(/#\w+/g) || [];
		const hashtagCount = hashtagMatches.length;
		let hashtagScore = 100;
		let hashtagLabel = l10n.lblHashtagSubtle;

		if (hashtagCount > 6) {
			hashtagScore = 30;
			hashtagLabel = l10n.lblHashtagDense;
		} else if (hashtagCount > 3) {
			hashtagScore = 60;
			hashtagLabel = l10n.lblHashtagMany;
		}

		const imgMatches = cleanText.match(/\[img(.*?)\](.*?)\[\/img\]/gi) || [];
		let imagesMissingAlt = 0;
		imgMatches.forEach(function (match) {
			const parts = match.match(/\[img(.*?)\](.*?)\[\/img\]/i);
			if (parts) {
				const attrs = parts[1] || '';
				const body = (parts[2] || '').trim();
				const hasAltAttr = /alt\s*=\s*(["']).*?\1/i.test(attrs);
				const hasPipeAlt = body.includes('|');
				if (!hasAltAttr && !hasPipeAlt && body.length < 3) {
					imagesMissingAlt++;
				}
			}
		});

		const mdImgMatches = cleanText.match(/!\[(.*?)\]\((.*?)\)/gi) || [];
		let mdImagesMissingAlt = 0;
		mdImgMatches.forEach(function (match) {
			const parts = match.match(/!\[(.*?)\]\((.*?)\)/i);
			if (parts) {
				const altText = (parts[1] || '').trim();
				if (!altText) {
					mdImagesMissingAlt++;
				}
			}
		});

		const hasImages = imgMatches.length > 0 || mdImgMatches.length > 0;
		const altOk = !hasImages || (imagesMissingAlt === 0 && mdImagesMissingAlt === 0);
		const emojiFloodRegex = /(?:\p{Emoji_Presentation}|\p{Extended_Pictographic}){5,}/u;
		const hasEmojiFlood = emojiFloodRegex.test(cleanText);
		const needsParagraphs = textLength >= 300 && paragraphCount === 1;

		let hasShouting = false;
		sentences.forEach(function (s) {
			const words = s.trim().split(/\s+/).filter(function (w) { return w.length > 0; });
			if (words.length > 4 && s === s.toUpperCase() && /[A-Z]/.test(s)) {
				hasShouting = true;
			}
		});

		analysis.updateIndicator('paragraphs', paragraphScore, paragraphLabel);
		analysis.updateIndicator('sentence-length', balanceScore, balanceLabel);
		analysis.updateIndicator('links', linkScore, linkLabel);
		analysis.updateIndicator('hashtags', hashtagScore, hashtagLabel);

		const chkAlt = document.getElementById('ec-chk-alt');
		if (chkAlt) {
			chkAlt.classList.toggle('ec-ok', altOk);
			chkAlt.classList.toggle('ec-warn', !altOk);
		}

		const chkEmoji = document.getElementById('ec-chk-emoji');
		if (chkEmoji) {
			chkEmoji.classList.toggle('ec-ok', !hasEmojiFlood);
			chkEmoji.classList.toggle('ec-warn', hasEmojiFlood);
		}

		const chkPara = document.getElementById('ec-chk-paragraphs');
		if (chkPara) {
			chkPara.classList.toggle('ec-ok', !needsParagraphs);
			chkPara.classList.toggle('ec-warn', needsParagraphs);
		}

		const tipsContainer = document.getElementById('ec-tips-container');
		if (tipsContainer) {
			tipsContainer.textContent = '';
			const activeTips = [];

			if (imagesMissingAlt > 0 || mdImagesMissingAlt > 0) {
				activeTips.push(l10n.tipMissingAlt);
			}
			if (needsParagraphs) {
				activeTips.push(l10n.tipNoParagraphs);
			}
			if (longSentencesCount > 0) {
				activeTips.push(l10n.tipLongSentences);
			}
			if (hasShouting) {
				activeTips.push(l10n.tipShouting);
			}
			if (hashtagCount > 5) {
				activeTips.push(l10n.tipTooManyHashtags);
			}
			if (hasEmojiFlood) {
				activeTips.push(l10n.tipEmojiFlood);
			}

			if (activeTips.length === 0) {
				const okTip = document.createElement('div');
				okTip.className = 'ec-tip ec-tip-success';
				okTip.textContent = l10n.tipExcellent;
				tipsContainer.appendChild(okTip);
			} else {
				activeTips.forEach(function (tip) {
					const tipNode = document.createElement('div');
					tipNode.className = 'ec-tip ec-tip-warn';
					tipNode.textContent = tip;
					tipsContainer.appendChild(tipNode);
				});
			}
		}
	};
})(window);