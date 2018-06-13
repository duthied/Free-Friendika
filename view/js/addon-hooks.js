/**
 * @file addon-hooks.js
 * @brief Provide a way for add-ons to register a JavaScript hook
 */

var addon_hooks = {};

/**
 * @brief Register a JavaScript hook to be called from other Javascript files
 * @pre the .js file from which the hook will be called is included in the document response
 * @param type which type of hook i.e. where should it be called along with other hooks of the same type
 * @param hookfnstr name of the JavaScript function name that needs to be called
 */
function Addon_registerHook(type, hookfnstr)
{
	if (!addon_hooks.hasOwnProperty(type)) {
		addon_hooks[type] = [];
	}

	addon_hooks[type].push(hookfnstr);
}

/**
 * @brief Call all registered hooks of a certain type, i.e. at the same point of the JavaScript code execution
 * @param typeOfHook string indicating which type of hooks to be called among the registered hooks
 */
function callAddonHooks(typeOfHook)
{
	if (typeof addon_hooks !== 'undefined') {
		var myTypeOfHooks = addon_hooks[typeOfHook];
		if (typeof myTypeOfHooks !== 'undefined') {
			for (addon_hook_idx = 0; addon_hook_idx < myTypeOfHooks.length; addon_hook_idx++) {
				var hookfnstr = myTypeOfHooks[addon_hook_idx];
				var hookfn = window[hookfnstr];
				if (typeof hookfn === "function") {
					hookfn();
				}
			}
		}
	}
}
