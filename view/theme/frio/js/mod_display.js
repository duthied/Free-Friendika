/**
 * @brief Javascript for the display module
 */

// Catch the GUID from the URL
var itemID = window.location.pathname.split("/").pop();

$(document).ready(function(){
	// Scroll to the Item by its GUID
	scrollToItem(itemID);
});
