/* 
 * @brief contains functions for bootstrap modal handling
 */

// Clear bs modal on close
// We need this to prevent that the modal displays old content
$('body').on('hidden.bs.modal', '.modal', function () {
	$(this).removeData('bs.modal');
	$("#modal-title").empty();
	$('#modal-body').empty();
});

/**
 * @brief Add first h3 element as modal title
 * 
 * Note: this should be really done in the template
 * and is the solution where we havent done it until this
 * moment or where it isn't possible because of design
 */
function loadModalTitle() {
	// clear the text of the title
	//$("#modal-title").empty();

	// hide the first h3 child element of the modal body
	$("#modal-body > h3").first().hide();

	// get the text of the first h3 child element
	var title = $("#modal-body > h3").first().text();

	// and append it to modal title
	if (title!=="") {
		$("#modal-title").append(title);
	}
}


function addToModal(url) {
	var char = qOrAmp(url);

	var url = url + char + 'mode=modal';
	var modal = $('#modal').modal();

	modal
		.find('#modal-body')
		.load(url, function (responseText, textStatus) {
			if ( textStatus === 'success' || 
				textStatus === 'notmodified') 
			{
				modal.show();

				//Get first h3 element and use it as title
				loadModalTitle();
			}
		});
};

function editpost(url) {
	var modal = $('#jot-modal').modal();
	var url = url + " #profile-jot-form";
	//var rand_num = random_digits(12);
	$("#jot-perms-lnk").hide();

	// rename the the original div jot-preview-content because the edit function
	// does load the content for the modal from another source and preview won't work
	// if this div would exist twice
	// $("#jot-content #profile-jot-form").attr("id","#profile-jot-form-renamed");
	// $("#jot-content #jot-preview-content").attr("id","#jot-preview-content-renamed");

	// For editpost we load the modal html form the edit page. So we would have two jot forms in
	// the page html. To avoid js conflicts we move the original jot to the end of the page
	// so the editpost jot would be the first jot in html structure.
	// After closing the modal we move the original jot back to it's orginal position in the html structure.
	// 
	// Note: For now it seems to work but this isn't optimal because we have doubled ID names for the jot div's.
	// We need to have a better solution for this in the future. 
	$("section #jot-content #profile-jot-form").appendTo("footer #cache-container");

	jotreset();

	modal
		.find('#jot-modal-body')
		.load(url, function (responseText, textStatus) {
			if ( textStatus === 'success' || 
				textStatus === 'notmodified') 
			{
				// get the item type and hide the input for title and category if it isn't needed
				var type = $(responseText).find("#profile-jot-form input[name='type']").val();
				if(type === "wall-comment" || type === "remote-comment")
				{
					$("#profile-jot-form #jot-title-wrap").hide();
					$("#profile-jot-form #jot-category-wrap").hide();
				}

				modal.show();
				$("#jot-popup").show();
			}
		});
}

function jotreset() {
	// Clear bs modal on close
	// We need this to prevent that the modal displays old content
	$('body').on('hidden.bs.modal', '#jot-modal', function () {
		$(this).removeData('bs.modal');
		$("#jot-perms-lnk").show();
		$("#profile-jot-form #jot-title-wrap").show();
		$("#profile-jot-form #jot-category-wrap").show();

		// the following was commented out because it is needed anymore
		// because we changed the behavior at an other place
//		var rand_num = random_digits(12);
//		$('#jot-title, #jot-category, #profile-jot-text').val("");
//		$( "#profile-jot-form input[name='type']" ).val("wall");
//		$( "#profile-jot-form input[name='post_id']" ).val("");
//		$( "#profile-jot-form input[name='post_id_random']" ).val(rand_num);
		$("#jot-modal-body").empty();

		// rename the div #jot-preview-content-renamed back to it's original
		// name. Have a look at function editpost() for further explanation
		//$("#jot-content #profile-jot-form-renamed").attr("id","#profile-jot-form");
		//$("#jot-content #jot-preview-content-renamed").attr("id","#jot-preview-content");

		// Move the original jot back to it's old place in the html structure
		// For explaination have a look at function editpost()
		$("footer #cache-container #profile-jot-form").appendTo("section #jot-content");
	});
}
