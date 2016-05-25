/* 
 * @brief contains functions for bootstrap modal handling
 */
$(document).ready(function(){
	// Clear bs modal on close
	// We need this to prevent that the modal displays old content
	$('body, footer').on('hidden.bs.modal', '.modal', function () {
		$(this).removeData('bs.modal');
		$("#modal-title").empty();
		$('#modal-body').empty();
		// remove the file browser from jot (else we would have problems
		// with ajaxupload
		$(".fbrowser").remove();
	});

		// Add Colorbox for viewing Network page images
	//var cBoxClasses = new Array();
	$("body").on("click", ".wall-item-body a img", function(){
		var aElem = $(this).parent();
		var imgHref = aElem.attr("href");

		// We need to make sure we only put a Colorbox on links to Friendica images
		// We'll try to do this by looking for links of the form
		// .../photo/ab803d8eg08daf85023adfec08 (with nothing more following), in hopes
		// that that will be unique enough
		if(imgHref.match(/\/photo\/[a-fA-F0-9]+(-[0-9]\.[\w]+?)?$/)) {

			// Add a unique class to all the images of a certain post, to allow scrolling through
			var cBoxClass = $(this).closest(".wall-item-body").attr("id") + "-lightbox";
			$(this).addClass(cBoxClass);

//			if( $.inArray(cBoxClass, cBoxClasses) < 0 ) {
//				cBoxClasses.push(cBoxClass);
//			}

			aElem.colorbox({
				maxHeight: '90%',
				photo: true, // Colorbox doesn't recognize a URL that don't end in .jpg, etc. as a photo
				rel: cBoxClass //$(this).attr("class").match(/wall-item-body-[\d]+-lightbox/)[0]
			});
		}
	});



	// Jot nav menu.
	$("body").on("click", "#jot-modal .jot-nav li a", function(e){
		e.preventDefault();
		toggleJotNav(this);
	});

	// Open filebrowser for elements with the class "image-select"
	// The following part handles the filebrowser for field_fileinput.tpl
	$("body").on("click", ".image-select", function(e){
		// set a extra attribute to mark the clicked button
		this.setAttribute("image-input", "select");
		Dialog.doImageBrowser("input");
	});

	// Insert filebrowser images into the input field (field_fileinput.tpl)
	$("body").on("fbrowser.image.input", function(e, filename, embedcode, id, img) {
		// select the clicked button by it's attribute
		var elm = $("[image-input='select']")
		// select the input field which belongs to this button
		var input = elm.parent(".input-group").children("input");
		// remove the special indicator attribut from the button
		elm.removeAttr("image-input");
		// inserte the link from the image into the input field
		input.val(img);
		
	});
});

// overwrite Dialog.show from main js to load the filebrowser into a bs modal
Dialog.show = function(url) {
	var modal = $('#modal').modal();
	modal
		.find('#modal-body')
		.load(url, function (responseText, textStatus) {
			if ( textStatus === 'success' || 
				textStatus === 'notmodified') 
			{
				modal.show();

				$(function() {Dialog._load(url);});
			}
		});
};

// overwrite the function _get_url from main.js
Dialog._get_url = function(type, name, id) {
	var hash = name;
	if (id !== undefined) hash = hash + "-" + id;
	return "fbrowser/"+type+"/?mode=none#"+hash;
};

// does load the filebrowser into the jot modal
Dialog.showJot = function() {
	var type = "image";
	var name = "main";

	var url = Dialog._get_url(type, name);
	if(($(".modal-body #jot-fbrowser-wrapper .fbrowser").length) < 1 ) {
		// load new content to fbrowser window
		$("#jot-fbrowser-wrapper").load(url,function(responseText, textStatus){
			if ( textStatus === 'success' || 
				textStatus === 'notmodified') 
			{
				$(function() {Dialog._load(url);});
			}
		});
	}
};

// init the filebrowser after page load
Dialog._load = function(url) {
	// get nickname & filebrowser type from the modal content
	var nickname = $("#fb-nickname").attr("value");
	var type = $("#fb-type").attr("value");

	// try to fetch the hash form the url
	var match = url.match(/fbrowser\/[a-z]+\/\?mode=none(.*)/);
	var hash = match[1];

	// initialize the filebrowser
	var jsbrowser = function() {
		FileBrowser.init(nickname, type, hash);
	}
	loadScript("view/theme/frio/js/filebrowser.js", jsbrowser);
};

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
	$("#modal-body .heading").first().hide();

	// get the text of the first element with heading class
	var title = $("#modal-body .heading").first().text();

	// and append it to modal title
	if (title!=="") {
		$("#modal-title").append(title);
	}
}

// This function loads html content from a friendica page
// into a modal
function addToModal(url) {
	var char = qOrAmp(url);

	var url = url + char + 'mode=none';
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

// function to load the html from the edit post page into
// the jot modal
function editpost(url) {
	var modal = $('#jot-modal').modal();
	var url = url + " #profile-jot-form";
	//var rand_num = random_digits(12);
	$(".jot-nav #jot-perms-lnk").parent("li").hide();

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

// remove content from the jot modal
function jotreset() {
	// Clear bs modal on close
	// We need this to prevent that the modal displays old content
	$('body').on('hidden.bs.modal', '#jot-modal', function () {
		$(this).removeData('bs.modal');
		$(".jot-nav #jot-perms-lnk").parent("li").show();
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

// Give the active "jot-nav" list element the class "active"
function toggleJotNav (elm) {
	// select all li of jot-nav and remove the active class
	$(elm).closest(".jot-nav").children("li").removeClass("active");
	// add the active class to the parent of the link which was selected
	$(elm).parent("li").addClass("active");
}

