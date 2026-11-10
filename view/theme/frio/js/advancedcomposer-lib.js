// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

// @license magnet:?xt=urn:btih:0b31508aeb0634b347b8270c7bee4d411b5d4109&dn=agpl-3.0.txt AGPLv3-or-later
(function (window) {
	'use strict';

	const state = window.__advancedcomposer || (window.__advancedcomposer = {});
	state.handlers = state.handlers || {};

	window.AdvancedComposerLibrary = window.AdvancedComposerLibrary || {};
	const api = window.AdvancedComposerLibrary;

	api.state = state;
	api.analysis = api.analysis || {};
	api.preview = api.preview || {};
	api.panel = api.panel || {};
	api.getContext = function () {
		return state.context || {};
	};

	api.buildButtonContent = function (btn, iconClass, labelText) {
		const icon = document.createElement('i');
		icon.className = iconClass;
		const span = document.createElement('span');
		span.className = 'ec-btn-text';
		span.textContent = labelText;
		btn.textContent = '';
		btn.appendChild(icon);
		btn.appendChild(document.createTextNode(' '));
		btn.appendChild(span);
	};

	api.registerHandler = function (name, handler) {
		state.handlers[name] = handler;
		return handler;
	};

	api.getHandler = function (name) {
		return state.handlers[name] || null;
	};

	api.invokeHandler = function (name) {
		const handler = api.getHandler(name);
		if (typeof handler === 'function') {
			return handler.apply(window, Array.prototype.slice.call(arguments, 1));
		}
		return null;
	};

	api.bindGlobalAction = function (name, globalName) {
		window[globalName] = function () {
			return api.invokeHandler.apply(api, [name].concat(Array.prototype.slice.call(arguments)));
		};
		return window[globalName];
	};
})(window);