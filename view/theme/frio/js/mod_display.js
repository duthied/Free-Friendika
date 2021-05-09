// @license magnet:?xt=urn:btih:0b31508aeb0634b347b8270c7bee4d411b5d4109&dn=agpl-3.0.txt AGPLv3-or-later
/**
 * Javascript for the display module
 */

// Catch the GUID from the URL
var itemGuid = window.location.pathname.split("/").pop();

$(window).load(function () {
	// Scroll to the Item by its GUID
	scrollToItem("item-" + itemGuid);
});
// @license-end
