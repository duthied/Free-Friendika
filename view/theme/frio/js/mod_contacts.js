
var batchConfirmed = false;

$(document).ready(function() {
	// Add contact_filter autocompletion to the search field.
	$("#contacts-search").contact_filter(baseurl + '/acl', 'r', true);

	// Hide the viewcontact_wrapper if there is an input in the search field
	// We are doing this to let the the contact_filter replace the original
	// shown contacts.
	$("#contacts-search").keyup(function(){
		var elText = $(this).val();
		if (elText.length !== 0) {
			$("#viewcontact_wrapper").hide();
			$("ul.textcomplete-dropdown").addClass("show media-list");
		} else {
			$("#viewcontact_wrapper").show();
			$("ul.textcomplete-dropdown").removeClass("show");
		}
	});
	// Initiale autosize for the textareas.
	autosize($("textarea.text-autosize"));


	// Replace the drop contact link of the photo menu
	// with a confirmation modal.
	$("body").on("click", ".contact-photo-menu a", function(e) {
		var photoMenuLink = $(this).attr('href');
		if (typeof photoMenuLink !== "undefined" && photoMenuLink.indexOf("/drop?confirm=1") !== -1) {
			e.preventDefault();
			addToModal(photoMenuLink);
			return false;
		}
	});

});

/**
 * @brief This function submits the form with the batch action values.
 *
 * @param {string} name The name of the batch action.
 * @param {string} value If it isn't empty the action will be posted.
 * 
 * @return {void}
 */
function batch_submit_handler(name, value) {
	if (confirm(value + " ?")) {
		// Set the value of the hidden input element with the name batch_submit.
		document.batch_actions_submit.batch_submit.value = value;
		// Change the name of the input element from batch_submit according to the
		// name which is transmitted to this function.
		document.batch_actions_submit.batch_submit.name = name;
		// Transmit the form.
		document.batch_actions_submit.submit();

		return true;
	} else {
		return false;
	}
}
