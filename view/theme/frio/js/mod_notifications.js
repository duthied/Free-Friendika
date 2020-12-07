
/**
 * Javascript for the notifications module
 */

// Catch the intro ID from the URL
var introID = location.pathname.split("/").pop();

$(document).ready(function(){
	// Since only the DIV's inside the notification-list are marked
	// with the class "unseen", we need some js to transfer this class
	// to the parent li list-elements.
	if($(".notif-item").hasClass("unseen")) {
		$(".notif-item.unseen").parent("li").addClass("unseen");
	}
});

$(window).load(function(){
	// Scroll to the intro by its intro ID.
	if (isIntroID()) {
		scrollToItem('intro-' + introID);
	}
});

// Check if it is a real introduction ID.
function isIntroID() {
	// Check for the correct path.
	if (window.location.href.indexOf("/notifications/intros/") !== -1) {
		// Make sure the introID is a positive Integer value.
		var intVal = Number(introID);
		if (Math.floor(intVal) !== Infinity && String(intVal) === introID && intVal > 0) {
			return true;
		}
	}
	return false;
}
