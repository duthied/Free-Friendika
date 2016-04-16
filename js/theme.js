$(document).ready(function(){
	//fade in/out based on scrollTop value
	$(window).scroll(function () {
		if ($(this).scrollTop() > 1000) {
			$("#back-to-top").fadeIn();
		} else {
			$("#back-to-top").fadeOut();
		}
	});
 
	// scroll body to 0px on click
	$("#back-to-top").click(function () {
		$("body,html").animate({
			scrollTop: 0
		}, 400);
		return false;
	});

	// Clear bs modal on close
	// We need this to prevent that the modal displays old content
	$('body').on('hidden.bs.modal', '.modal', function () {
		$(this).removeData('bs.modal');
		$("#modal-title").empty();
		$('#modal-body').empty();
	});

	// add the class "selected" to group widges li if li > a does have the class group-selected
	if( $("#sidebar-group-ul li a").hasClass("group-selected")) {
		$("#sidebar-group-ul li a.group-selected").parent("li").addClass("selected");
	}

	// add the class "selected" to forums widges li if li > a does have the class forum-selected
	if( $("#forumlist-sidbar-ul li a").hasClass("forum-selected")) {
		$("#forumlist-sidbar-ul li a.forum-selected").parent("li").addClass("selected");
	}

	// add the class "active" to tabmenuli if li > a does have the class active
	if( $("#tabmenu ul li a").hasClass("active")) {
		$("#tabmenu ul li a.active").parent("li").addClass("active");
	}

	// give select fields an boostrap classes
	$(".field.select, .field.custom").addClass("form-group");
	$(".field.select > select, .field.custom > select").addClass("form-control");

	if( $("ul.tabs")) {
		$("ul.tabs").appendTo("#topbar-second > .container > #tabmenu");
	}

	// add Jot botton to the scecond navbar
	if( $("section #jotOpen")) {
		$("section #jotOpen").appendTo("#topbar-second > .container > #navbar-button");
		if( $("#jot-popup").is(":hidden")) $("#topbar-second > .container > #navbar-button #jotOpen").hide();
	}

	// Loading remote bootstrap remote modals
	// This is uses to load tradional friendica pages into bootstrap modals
	// 
	$('a[rel=modal]').on('click', function(evt) {
		evt.preventDefault();
		var modal = $('#modal').modal();
		modal
			.find('#modal-body')
			.load($(this).attr("href"), function (responseText, textStatus) {
				if ( textStatus === 'success' || 
					textStatus === 'notmodified') 
				{
					modal.show();

					//Get first h3 element and use it as title
					loadModalTitle();
				}
			});
	});

	// Add Colorbox for viewing Network page images
	//var cBoxClasses = new Array();
	$(".wall-item-body a img").each(function(){
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

					// get nickname & filebrowser type from the modal content
					var nickname = $("#fb-nickname").attr("value");
					var type = $("#fb-type").attr("value");

					// try to fetch the hash form the url
					var match = url.match(/fbrowser\/[a-z]+\/\?mode=modal(.*)/);
					var hash = match[1];

					// initialize the filebrowser
					var jsbrowser = function() {
						FileBrowser.init(nickname, type, hash);
					}
					loadScript("view/theme/frio/js/filebrowser.js", jsbrowser);
				}
			});
	};

	// overwrite the function _get_url from main.js
	Dialog._get_url = function(type, name, id) {
		var hash = name;
		if (id !== undefined) hash = hash + "-" + id;
		return "fbrowser/"+type+"/?mode=modal#"+hash;
	};




});
//function commentOpenUI(obj, id) {
//	$(document).unbind( "click.commentOpen", handler );
//
//	var handler = function() {
//		if(obj.value == '{{$comment}}') {
//			obj.value = '';
//			$("#comment-edit-text-" + id).addClass("comment-edit-text-full").removeClass("comment-edit-text-empty");
//			// Choose an arbitrary tab index that's greater than what we're using in jot (3 of them)
//			// The submit button gets tabindex + 1
//			$("#comment-edit-text-" + id).attr('tabindex','9');
//			$("#comment-edit-submit-" + id).attr('tabindex','10');
//			$("#comment-edit-submit-wrapper-" + id).show();
//		}
//	};
//
//	$(document).bind( "click.commentOpen", handler );
//}
//
//function commentCloseUI(obj, id) {
//	$(document).unbind( "click.commentClose", handler );
//
//	var handler = function() {
//		if(obj.value === '') {
//		obj.value = '{{$comment}}';
//			$("#comment-edit-text-" + id).removeClass("comment-edit-text-full").addClass("comment-edit-text-empty");
//			$("#comment-edit-text-" + id).removeAttr('tabindex');
//			$("#comment-edit-submit-" + id).removeAttr('tabindex');
//			$("#comment-edit-submit-wrapper-" + id).hide();
//		}
//	};
//
//	$(document).bind( "click.commentClose", handler );
//}

function openClose(theID) {
	var elem = document.getElementById(theID);

	if( $(elem).is(':visible')) {
		$(elem).slideUp(200);
	}
	else {
		$(elem).slideDown(200);
	}
}

function showHide(theID) {
	if(document.getElementById(theID).style.display == "block") {
		document.getElementById(theID).style.display = "none"
	}
	else {
		document.getElementById(theID).style.display = "block"
	}
}


function showHideComments(id) {
	if( $('#collapsed-comments-' + id).is(':visible')) {
		$('#collapsed-comments-' + id).slideUp();
		$('#hide-comments-' + id).html(window.showMore);
		$('#hide-comments-total-' + id).show();
	}
	else {
		$('#collapsed-comments-' + id).slideDown();
		$('#hide-comments-' + id).html(window.showFewer);
		$('#hide-comments-total-' + id).hide();
	}
}


function justifyPhotos() {
	justifiedGalleryActive = true;
	$('#photo-album-contents').justifiedGallery({
		margins: 3,
		border: 0,
		sizeRangeSuffixes: {
			'lt100': '-2',
			'lt240': '-2',
			'lt320': '-2',
			'lt500': '',
			'lt640': '-1',
			'lt1024': '-0'
		}
	}).on('jg.complete', function(e){ justifiedGalleryActive = false; });
}

function justifyPhotosAjax() {
	justifiedGalleryActive = true;
	$('#photo-album-contents').justifiedGallery('norewind').on('jg.complete', function(e){ justifiedGalleryActive = false; });
}

function loadScript(url, callback) {
	// Adding the script tag to the head as suggested before
	var head = document.getElementsByTagName('head')[0];
	var script = document.createElement('script');
	script.type = 'text/javascript';
	script.src = url;

	// Then bind the event to the callback function.
	// There are several events for cross browser compatibility.
	script.onreadystatechange = callback;
	script.onload = callback;

	// Fire the loading
	head.appendChild(script);
}

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
	var url = url + '?mode=modal';
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

function random_digits(digits) {
	var rn = "";
	var rnd = "";

	for(var i = 0; i < digits; i++) {
		var rn = Math.round(Math.random() * (9));
		rnd += rn;
	}

	return rnd;
}

function insertFormatting(comment,BBcode,id) {

		var tmpStr = $("#comment-edit-text-" + id).val();
		if(tmpStr == comment) {
			tmpStr = "";
			$("#comment-edit-text-" + id).addClass("comment-edit-text-full");
			$("#comment-edit-text-" + id).removeClass("comment-edit-text-empty");
			openMenu("comment-edit-submit-wrapper-" + id);
			$("#comment-edit-text-" + id).val(tmpStr);
		}

	textarea = document.getElementById("comment-edit-text-" +id);
	if (document.selection) {
		textarea.focus();
		selected = document.selection.createRange();
		if (BBcode == "url"){
			selected.text = "["+BBcode+"]" + "http://" +  selected.text + "[/"+BBcode+"]";
			} else
		selected.text = "["+BBcode+"]" + selected.text + "[/"+BBcode+"]";
	} else if (textarea.selectionStart || textarea.selectionStart == "0") {
		var start = textarea.selectionStart;
		var end = textarea.selectionEnd;
		if (BBcode == "url"){
			textarea.value = textarea.value.substring(0, start) + "["+BBcode+"]" + "http://" + textarea.value.substring(start, end) + "[/"+BBcode+"]" + textarea.value.substring(end, textarea.value.length);
			} else
		textarea.value = textarea.value.substring(0, start) + "["+BBcode+"]" + textarea.value.substring(start, end) + "[/"+BBcode+"]" + textarea.value.substring(end, textarea.value.length);
	}
	return true;
}


function showThread(id) {
	$("#collapsed-comments-" + id).show()
	$("#collapsed-comments-" + id + " .collapsed-comments").show()
}
function hideThread(id) {
	$("#collapsed-comments-" + id).hide()
	$("#collapsed-comments-" + id + " .collapsed-comments").hide()
}


function cmtBbOpen(id) {
	$("#comment-edit-bb-" + id).show();
}
function cmtBbClose(id) {
	$("#comment-edit-bb-" + id).hide();
}