// @license magnet:?xt=urn:btih:0b31508aeb0634b347b8270c7bee4d411b5d4109&dn=agpl-3.0.txt AGPLv3-or-later

// https://developer.mozilla.org/en-US/docs/Web/API/Element/matches#Polyfill
if (!Element.prototype.matches) {
	Element.prototype.matches =
		Element.prototype.matchesSelector ||
		Element.prototype.mozMatchesSelector ||
		Element.prototype.msMatchesSelector ||
		Element.prototype.oMatchesSelector ||
		Element.prototype.webkitMatchesSelector ||
		function(s) {
			var matches = (this.document || this.ownerDocument).querySelectorAll(s),
				i = matches.length;
			while (--i >= 0 && matches.item(i) !== this) {}
			return i > -1;
		};
}

function resizeIframe(obj) {
	_resizeIframe(obj, 0);
}

function _resizeIframe(obj, desth) {
	var h = obj.style.height;
	var ch = obj.contentWindow.document.body.scrollHeight;

	if (h == (ch + 'px')) {
		return;
	}
	if (desth == ch && ch > 0) {
		obj.style.height  = ch + 'px';
	}
	setTimeout(_resizeIframe, 100, obj, ch);
}

function initWidget(inflated, deflated) {
	var elInf = document.getElementById(inflated);
	var elDef = document.getElementById(deflated);

	if (!elInf || !elDef) {
		return;
	}
	if (localStorage.getItem(window.location.pathname.split("/")[1] + ":" + inflated) != "none") {
		elInf.style.display = "block";
		elDef.style.display = "none";
	} else {
		elInf.style.display = "none";
		elDef.style.display = "block";
	}
}

function openCloseWidget(inflated, deflated) {
	var elInf = document.getElementById(inflated);
	var elDef = document.getElementById(deflated);

	if (!elInf || !elDef) {
		return;
	}

	if (window.getComputedStyle(elInf).display === "none") {
		elInf.style.display = "block";
		elDef.style.display = "none";
		localStorage.setItem(window.location.pathname.split("/")[1] + ":" + inflated, "block");
	} else {
		elInf.style.display = "none";
		elDef.style.display = "block";
		localStorage.setItem(window.location.pathname.split("/")[1] + ":" + inflated, "none");
	}
}

function openClose(theID) {
	var el = document.getElementById(theID);
	if (el) {
		if (window.getComputedStyle(el).display === "none") {
			openMenu(theID);
		} else {
			closeMenu(theID);
		}
	}
}

function openMenu(theID) {
	var el = document.getElementById(theID);
	if (el) {
		if (!el.dataset.display) {
			el.dataset.display = 'block';
		}
		el.style.display = el.dataset.display;
	}
}

function closeMenu(theID) {
	var el = document.getElementById(theID);
	if (el) {
		el.dataset.display = window.getComputedStyle(el).display;
		el.style.display = "none";
	}
}

function decodeHtml(html) {
	var txt = document.createElement("textarea");

	txt.innerHTML = html;
	return txt.value;
}

/**
 * Retrieves a single named query string parameter
 *
 * @param {string} name
 * @returns {string}
 * @see https://davidwalsh.name/query-string-javascript
 */
function getUrlParameter(name) {
	name = name.replace(/[\[]/, '\\[').replace(/[\]]/, '\\]');
	var regex = new RegExp('[\\?&]' + name + '=([^&#]*)');
	var results = regex.exec(location.search);
	return results === null ? '' : decodeURIComponent(results[1].replace(/\+/g, ' '));
};

var src = null;
var prev = null;
var livetime = null;
var force_update = false;
var update_item = 0;
var stopped = false;
var totStopped = false;
var timer = null;
var pr = 0;
var liking = 0;
var in_progress = false;
var langSelect = false;
var commentBusy = false;
var last_popup_menu = null;
var last_popup_button = null;
var lockLoadContent = false;
var originalTitle = document.title;

const urlRegex = /^(?:https?:\/\/|\s)[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,4})(?:\/+[a-z0-9_.:;-]*)*(?:\?[&%|+a-z0-9_=,.:;-]*)?(?:[&%|+&a-z0-9_=,:;.-]*)(?:[!#\/&%|+a-z0-9_=,:;.-]*)}*$/i;

$(function() {
	$.ajaxSetup({cache: false});

	/* setup comment textarea buttons */
	/* comment textarea buttons needs some "data-*" attributes to work:
	 * 		data-role="insert-formatting" : to mark the element as a formatting button
	 * 		data-bbcode="<string>" : name of the bbcode element to insert. insertFormatting() will insert it as "[name][/name]"
	 * 		data-id="<string>" : id of the comment, used to find other comment-related element, like the textarea
	 * */
	$('body').on('click','[data-role="insert-formatting"]', function(e) {
		e.preventDefault();
		var o = $(this);
		var bbcode = o.data('bbcode');
		var id = o.data('id');
		if (bbcode == "img") {
			Dialog.doImageBrowser("comment", id);
			return;
		}

		if (bbcode == "imgprv") {
			bbcode = "img";
		}

		insertFormatting(bbcode, id);
	});

	/* event from comment textarea button popups */
	/* insert returned bbcode at cursor position or replace selected text */
	$("body").on("fbrowser.image.comment", function(e, filename, bbcode, id) {
		$.colorbox.close();
		var textarea = document.getElementById("comment-edit-text-" +id);
		var start = textarea.selectionStart;
		var end = textarea.selectionEnd;
		textarea.value = textarea.value.substring(0, start) + bbcode + textarea.value.substring(end, textarea.value.length);
		$(textarea).trigger('change');
	});

	$(".comment-edit-wrapper textarea, .wall-item-comment-wrapper textarea")
		.editor_autocomplete(baseurl + '/search/acl')
		.bbco_autocomplete('bbcode');

	// Ensures asynchronously-added comment forms recognize mentions, tags and BBCodes as well
	document.addEventListener("postprocess_liveupdate", function() {
		$(".comment-edit-wrapper textarea, .wall-item-comment-wrapper textarea")
			.editor_autocomplete(baseurl + '/search/acl')
			.bbco_autocomplete('bbcode');
	});

	/* popup menus */
	function close_last_popup_menu() {
		if (last_popup_menu) {
			last_popup_menu.hide();
			last_popup_menu.off('click', function(e) {e.stopPropagation()});
			last_popup_button.removeClass("selected");
			last_popup_menu = null;
			last_popup_button = null;
		}
	}
	$('a[rel^="#"]').click(function(e) {
		e.preventDefault();
		var parent = $(this).parent();
		var isSelected = (last_popup_button && parent.attr('id') == last_popup_button.attr('id'));
		close_last_popup_menu();
		if (isSelected) {
			return false;
		}
		menu = $($(this).attr('rel'));
		e.preventDefault();
		e.stopPropagation();
		if (menu.attr('popup') == "false") {
			return false;
		}
		parent.toggleClass("selected");
		menu.toggle();
		if (menu.css("display") == "none") {
			last_popup_menu = null;
			last_popup_button = null;
		} else {
			last_popup_menu = menu;
			last_popup_menu.on('click', function(e) {e.stopPropagation()});
			last_popup_button = parent;
			$('#nav-notifications-menu').perfectScrollbar('update');
		}
		return false;
	});
	$('html').click(function() {
		close_last_popup_menu();
	});

	// fancyboxes
	$("a.popupbox").colorbox({
		'inline' : true,
		'transition' : 'elastic',
		'maxWidth' : '100%'
	});
	$("a.ajax-popupbox").colorbox({
		'transition' : 'elastic',
		'maxWidth' : '100%'
	});

	/* notifications template */
	var notifications_tpl= unescape($("#nav-notifications-template[rel=template]").html());
	var notifications_all = unescape($('<div>').append($("#nav-notifications-see-all").clone()).html()); //outerHtml hack
	var notifications_mark = unescape($('<div>').append($("#nav-notifications-mark-all").clone()).html()); //outerHtml hack
	var notifications_empty = unescape($("#nav-notifications-menu").html());

	/* enable perfect-scrollbars for different elements */
	$('#nav-notifications-menu, aside').perfectScrollbar();

	/* nav update event  */
	$('nav').bind('nav-update', function(e, data) {
		var invalid = data.invalid || 0;
		if (invalid == 1) {
			window.location.href=window.location.href
		}

		let tabNotifications = data.mail + data.notification;
		if (tabNotifications > 0) {
			document.title = '(' + tabNotifications + ') ' + originalTitle;
		} else {
			document.title = originalTitle;
		}

		['net', 'home', 'intro', 'mail', 'events', 'birthdays', 'notification'].forEach(function(type) {
			var number = data[type];
			if (number == 0) {
				number = '';
				$('#' + type + '-update').removeClass('show');
			} else {
				$('#' + type + '-update').addClass('show');
			}
			$('#' + type + '-update').text(number);
		});

		var intro = data['intro'];
		if (intro == 0) {
			intro = ''; $('#intro-update-li').removeClass('show')
		} else {
			$('#intro-update-li').addClass('show')
		}

		$('#intro-update-li').html(intro);

		var mail = data['mail'];
		if (mail == 0) {
			mail = ''; $('#mail-update-li').removeClass('show')
		} else {
			$('#mail-update-li').addClass('show')
		}

		$('#mail-update-li').html(mail);

		$(".sidebar-group-li .notify").removeClass("show");
		$(data.groups).each(function(key, group) {
			var gid = group.id;
			var gcount = group.count;
			$(".group-"+gid+" .notify").addClass("show").text(gcount);
		});

		$(".forum-widget-entry .notify").removeClass("show");
		$(data.forums).each(function(key, forum) {
			var fid = forum.id;
			var fcount = forum.count;
			$(".forum-"+fid+" .notify").addClass("show").text(fcount);
		});

		if (data.notifications.length == 0) {
			$("#nav-notifications-menu").html(notifications_empty);
		} else {
			var nnm = $("#nav-notifications-menu");
			nnm.html(notifications_all + notifications_mark);

			var lastItemStorageKey = "notification-lastitem:" + localUser;
			var notification_lastitem = parseInt(localStorage.getItem(lastItemStorageKey));
			var notification_id = 0;

			// Insert notifs into the notifications-menu
			$(data.notifications).each(function(key, notification) {
				var text = notification.message.format('<span class="contactname">' + notification.name + '</span>');
				var contact = ('<a href="' + notification.url + '"><span class="contactname">' + notification.name + '</span></a>');
				var seenclass = (notification.seen == 1) ? "notification-seen" : "notification-unseen";
				var html = notifications_tpl.format(
					notification.href,                     // {0}  // link to the source
					notification.photo,                    // {1}  // photo of the contact
					text,                           // {2}  // preformatted text (autor + text)
					notification.date,                     // {3}  // date of notification (time ago)
					seenclass,                      // {4}  // visited status of the notification
					new Date(notification.timestamp*1000), // {5}  // date of notification
					notification.url,                      // {6}  // profile url of the contact
					notification.message.format(contact),  // {7}  // preformatted html (text including author profile url)
					''                              // {8}  // Deprecated
				);
				nnm.append(html);
			});

			// Desktop Notifications
			$(data.notifications.reverse()).each(function(key, e) {
				notification_id = parseInt(e.timestamp);
				if (notification_lastitem !== null && notification_id > notification_lastitem && Number(e.seen) === 0) {
					if (getNotificationPermission() === "granted") {
						var notification = new Notification(document.title, {
										  body: decodeHtml(e.message.replace('&rarr; ', '').format(e.name)),
										  icon: e.photo,
										});
						notification['url'] = e.href;
						notification.addEventListener("click", function(ev) {
							window.location = ev.target.url;
						});
					}
				}

			});
			notification_lastitem = notification_id;
			localStorage.setItem(lastItemStorageKey, notification_lastitem)

			$("img[data-src]", nnm).each(function(i, el) {
				// Add src attribute for images with a data-src attribute
				// However, don't bother if the data-src attribute is empty, because
				// an empty "src" tag for an image will cause some browsers
				// to prefetch the root page of the Friendica hub, which will
				// unnecessarily load an entire profile/ or network/ page
				if ($(el).data("src") != '') {
					$(el).attr('src', $(el).data("src"));
				}
			});
		}

		var notif = data['notification'];
		if (notif > 0) {
			$("#nav-notifications-linkmenu").addClass("on");
		} else {
			$("#nav-notifications-linkmenu").removeClass("on");
		}

		$(data.sysmsgs.notice).each(function(key, message) {
			$.jGrowl(message, {sticky: true, theme: 'notice'});
		});
		$(data.sysmsgs.info).each(function(key, message) {
			$.jGrowl(message, {sticky: false, theme: 'info', life: 5000});
		});

		// Update the js scrollbars
		$('#nav-notifications-menu').perfectScrollbar('update');
	});

	// Asynchronous calls are deferred until the very end of the page load to ease on slower connections
	window.addEventListener("load", function(){
		NavUpdate();
		if (typeof acl !== 'undefined') {
			acl.get(0, 100);
		}
	});

	// Allow folks to stop the ajax page updates with the pause/break key
	$(document).keydown(function(event) {
		if (event.keyCode == '8') {
			var target = event.target || event.srcElement;
			if (!/input|textarea/i.test(target.nodeName)) {
				return false;
			}
		}

		if (event.keyCode == '19' || (event.ctrlKey && event.which == '32')) {
			event.preventDefault();
			if (stopped == false) {
				stopped = true;
				if (event.ctrlKey) {
					totStopped = true;
				}
				$('#pause').html('<img src="images/pause.gif" alt="pause" style="border: 1px solid black;" />');
			} else {
				unpause();
			}
		} else if (!totStopped) {
			unpause();
		}
	});

	// Scroll to the next/previous thread when pressing J and K
	$(document).keydown(function (event) {
		var threads = $('.thread_level_1');
		if ((event.keyCode === 74 || event.keyCode === 75) && !$(event.target).is('textarea, input')) {
			var scrollTop = $(window).scrollTop();
			if (event.keyCode === 75) {
				threads = $(threads.get().reverse());
			}
			threads.each(function(key, item) {
				var comparison;
				var top = $(item).offset().top - 100;
				if (event.keyCode === 74) {
					comparison = top > scrollTop + 1;
				} else if (event.keyCode === 75) {
					comparison = top < scrollTop - 1;
				}
				if (comparison) {
					$('html, body').animate({scrollTop: top}, 200);
					return false;
				}
			});
		}
	});

	// Set an event listener for infinite scroll
	if (typeof infinite_scroll !== 'undefined') {
		$(window).scroll(function(e) {
			if ($(document).height() != $(window).height()) {
				// First method that is expected to work - but has problems with Chrome
				if ($(window).scrollTop() > ($(document).height() - $(window).height() * 1.5))
					loadScrollContent();
			} else {
				// This method works with Chrome - but seems to be much slower in Firefox
				if ($(window).scrollTop() > (($("section").height() + $("header").height() + $("footer").height()) - $(window).height() * 1.5)) {
					loadScrollContent();
				}
			}
		});
	}
});

/**
 * Inserts a BBCode tag in the comment textarea identified by id
 *
 * @param {string} BBCode
 * @param {int} id
 * @returns {boolean}
 */
function insertFormatting(BBCode, id) {
	let textarea = document.getElementById('comment-edit-text-' + id);

	if (textarea.value === '') {
		$(textarea)
			.addClass("comment-edit-text-full")
			.removeClass("comment-edit-text-empty");
		closeMenu("comment-fake-form-" + id);
		openMenu("item-comments-" + id);
	}

	insertBBCodeInTextarea(BBCode, textarea);

	return true;
}

/**
 * Inserts a BBCode tag in the provided textarea element, wrapping the currently selected text.
 * For URL BBCode, it discriminates between link text and non-link text to determine where to insert the selected text.
 *
 * @param {string} BBCode
 * @param {HTMLTextAreaElement} textarea
 */
function insertBBCodeInTextarea(BBCode, textarea) {
	let selectionStart = textarea.selectionStart;
	let selectionEnd = textarea.selectionEnd;
	let selectedText = textarea.value.substring(selectionStart, selectionEnd);
	let openingTag = '[' + BBCode + ']';
	let closingTag = '[/' + BBCode + ']';
	let cursorPosition = selectionStart + openingTag.length + selectedText.length;

	if (BBCode === 'url') {
		if (urlRegex.test(selectedText)) {
			openingTag = '[' + BBCode + '=' + selectedText + ']';
			selectedText = '';
			cursorPosition = selectionStart + openingTag.length;
		} else {
			openingTag = '[' + BBCode + '=]';
			cursorPosition = selectionStart + openingTag.length - 1;
		}
	}

	textarea.value = textarea.value.substring(0, selectionStart) + openingTag + selectedText + closingTag + textarea.value.substring(selectionEnd, textarea.value.length);
	textarea.setSelectionRange(cursorPosition, cursorPosition);
	textarea.dispatchEvent(new Event('change'));
	textarea.focus();
}

function NavUpdate() {
	if (!stopped) {
		var pingCmd = 'ping?format=json' + ((localUser != 0) ? '&uid=' + localUser : '');
		$.get(pingCmd, function(data) {
			if (data.result) {
				// send nav-update event
				$('nav').trigger('nav-update', data.result);

				// start live update
				['network', 'profile', 'community', 'notes', 'display', 'contact'].forEach(function (src) {
					if ($('#live-' + src).length) {
						liveUpdate(src);
					}
				});
				if ($('#live-photos').length) {
					if (liking) {
						liking = 0;
						window.location.href = window.location.href;
					}
				}
			}
		});
	}
	timer = setTimeout(NavUpdate, updateInterval);
}

function updateConvItems(data) {
	// add a new thread
	$('.toplevel_item',data).each(function() {
		var ident = $(this).attr('id');

		// Add new top-level item.
		if ($('#' + ident).length === 0
			&& (!getUrlParameter('page')
				&& !getUrlParameter('max_id')
				&& !getUrlParameter('min_id')
				|| getUrlParameter('page') === '1'
			)
		) {
			$('#' + prev).after($(this));

		// Replace already existing thread.
		} else {
			// Find out if the hidden comments are open, so we can keep it that way
			// if a new comment has been posted
			var id = $('.hide-comments-total', this).attr('id');
			if (typeof id != 'undefined') {
				id = id.split('-')[3];
				var commentsOpen = $("#collapsed-comments-" + id).is(":visible");
			}

			$('#' + ident).replaceWith($(this));

			if (typeof id != 'undefined') {
				if (commentsOpen) {
					showHideComments(id);
				}
			}
		}
		prev = ident;
	});

	$('.like-rotator').hide();
	if (commentBusy) {
		commentBusy = false;
		$('body').css('cursor', 'auto');
	}
}

function liveUpdate(src) {
	if ((src == null) || stopped || !profile_uid) {
		$('.like-rotator').hide(); return;
	}

	if (($('.comment-edit-text-full').length) || in_progress) {
		if (livetime) {
			clearTimeout(livetime);
		}
		livetime = setTimeout(function() {liveUpdate(src)}, 5000);
		return;
	}

	if (livetime != null) {
		livetime = null;
	}
	prev = 'live-' + src;

	in_progress = true;

	let force = force_update || $(document).scrollTop() === 0;

	var orgHeight = $("section").height();

	var udargs = ((netargs.length) ? '/' + netargs : '');

	var update_url = 'update_' + src + udargs + '&p=' + profile_uid + '&force=' + (force ? 1 : 0) + '&item=' + update_item;

	if (force_update) {
		force_update = false;
	}

	if (getUrlParameter('page')) {
		update_url += '&page=' + getUrlParameter('page');
	}
	if (getUrlParameter('min_id')) {
		update_url += '&min_id=' + getUrlParameter('min_id');
	}
	if (getUrlParameter('max_id')) {
		update_url += '&max_id=' + getUrlParameter('max_id');
	}

	$.get(update_url, function(data) {
		in_progress = false;
		update_item = 0;

		if ($('.wall-item-body', data).length == 0) {
			return;
		}

		$('.wall-item-body', data).imagesLoaded(function() {
			updateConvItems(data);

			document.dispatchEvent(new Event('postprocess_liveupdate'));

			// Update the scroll position.
			$(window).scrollTop($(window).scrollTop() + $("section").height() - orgHeight);
		});
	});
}

function imgbright(node) {
	$(node).removeClass("drophide").addClass("drop");
}

function imgdull(node) {
	$(node).removeClass("drop").addClass("drophide");
}

// Since our ajax calls are asynchronous, we will give a few
// seconds for the first ajax call (setting like/dislike), then
// run the updater to pick up any changes and display on the page.
// The updater will turn any rotators off when it's done.
// This function will have returned long before any of these
// events have completed and therefore there won't be any
// visible feedback that anything changed without all this
// trickery. This still could cause confusion if the "like" ajax call
// is delayed and NavUpdate runs before it completes.

/**
 * @param {int}     ident The id of the relevant item
 * @param {string}  verb  The verb of the action
 * @param {boolean} un    Whether to perform an activity removal instead of creation
 */
function doActivityItem(ident, verb, un) {
	unpause();
	$('#like-rotator-' + ident.toString()).show();
	verb = un ? 'un' + verb : verb;
	$.post('item/' + ident.toString() + '/activity/' + verb, NavUpdate);
	liking = 1;
	force_update = true;
	update_item = ident.toString();
}

function doFollowThread(ident) {
	unpause();
	$('#like-rotator-' + ident.toString()).show();
	$.post('item/' + ident.toString() + '/follow', NavUpdate);
	liking = 1;
	force_update = true;
	update_item = ident.toString();
}

function doStar(ident) {
	ident = ident.toString();
	$('#like-rotator-' + ident).show();
	$.post('item/' + ident + '/star')
	.then(function(data) {
		if (data.state === 1) {
			$('#starred-' + ident)
				.addClass('starred')
				.removeClass('unstarred');
			$('#star-' + ident).addClass('hidden');
			$('#unstar-' + ident).removeClass('hidden');
		} else {
			$('#starred-' + ident)
				.addClass('unstarred')
				.removeClass('starred');
			$('#star-' + ident).removeClass('hidden');
			$('#unstar-' + ident).addClass('hidden');
		}
	})
	.always(function () {
		$('#like-rotator-' + ident).hide();
	});
}

function doPin(ident) {
	ident = ident.toString();
	$('#like-rotator-' + ident).show();
	$.post('item/' + ident + '/pin')
	.then(function(data) {
		if (data.state === 1) {
			$('#pinned-' + ident)
				.addClass('pinned')
				.removeClass('unpinned');
			$('#pin-' + ident).addClass('hidden');
			$('#unpin-' + ident).removeClass('hidden');
		} else {
			$('#pinned-' + ident)
				.addClass('unpinned')
				.removeClass('pinned');
			$('#pin-' + ident).removeClass('hidden');
			$('#unpin-' + ident).addClass('hidden');
		}
	})
	.always(function () {
		$('#like-rotator-' + ident).hide();
	});
}

function doIgnoreThread(ident) {
	ident = ident.toString();
	$('#like-rotator-' + ident).show();
	$.post('item/' + ident + '/ignore', function(data) {
		if (data.state === 1) {
			$('#ignored-' + ident)
				.addClass('ignored')
				.removeClass('unignored');
			$('#ignore-' + ident).addClass('hidden');
			$('#unignore-' + ident).removeClass('hidden');
		} else {
			$('#ignored-' + ident)
				.addClass('unignored')
				.removeClass('ignored');
			$('#ignore-' + ident).removeClass('hidden');
			$('#unignore-' + ident).addClass('hidden');
		}
		$('#like-rotator-' + ident).hide();
	});
}

function getPosition(e) {
	var cursor = {x:0, y:0};

	if (e.pageX || e.pageY) {
		cursor.x = e.pageX;
		cursor.y = e.pageY;
	} else {
		if (e.clientX || e.clientY) {
			cursor.x = e.clientX + (document.documentElement.scrollLeft || document.body.scrollLeft) - document.documentElement.clientLeft;
			cursor.y = e.clientY + (document.documentElement.scrollTop  || document.body.scrollTop)  - document.documentElement.clientTop;
		} else if (e.x || e.y) {
			cursor.x = e.x;
			cursor.y = e.y;
		}
	}
	return cursor;
}

var lockvisible = false;

function lockview(event, type, id) {
	event = event || window.event;
	cursor = getPosition(event);
	if (lockvisible) {
		lockvisible = false;
		$('#panel').hide();
	} else {
		lockvisible = true;
		$.get('permission/tooltip/' + type + '/' + id, function(data) {
			$('#panel')
				.html(data)
				.css({'left': cursor.x + 5 , 'top': cursor.y + 5})
				.show();
		});
	}
}

function post_comment(id) {
	unpause();
	commentBusy = true;
	$('body').css('cursor', 'wait');
	$.post(
		"item",
		$("#comment-edit-form-" + id).serialize(),
		function(data) {
			if (data.success) {
				$("#comment-edit-wrapper-" + id).hide();
				$("#comment-edit-text-" + id).val('');
				var tarea = document.getElementById("comment-edit-text-" + id);
				if (tarea) {
					commentClose(tarea,id);
				}
				if (timer) {
					clearTimeout(timer);
				}
				timer = setTimeout(NavUpdate,10);
				force_update = true;
				update_item = id;
			}
			if (data.reload) {
				window.location.href=data.reload;
			}
		},
		"json"
	);
	return false;
}

function preview_comment(id) {
	$("#comment-edit-preview-" + id).show();
	$.post(
		"item",
		$("#comment-edit-form-" + id).serialize() + '&preview=1',
		function(data) {
			if (data.preview) {
				$("#comment-edit-preview-" + id).html(data.preview);
				$("#comment-edit-preview-" + id + " a").click(function() {return false;});
			}
		},
		"json"
	);
	return true;
}

function showHideComments(id) {
	if ($('#collapsed-comments-' + id).is(':visible')) {
		$('#collapsed-comments-' + id).slideUp();
		$('#hide-comments-' + id).hide();
		$('#hide-comments-total-' + id).show();
	} else {
		$('#collapsed-comments-' + id).slideDown();
		$('#hide-comments-' + id).show();
		$('#hide-comments-total-' + id).hide();
	}
}

function preview_post() {
	$("#jot-preview-content").show();
	$.post(
		"item",
		$("#profile-jot-form").serialize() + '&preview=1',
		function(data) {
			if (data.preview) {
				$("#jot-preview-content").html(data.preview);
				$("#jot-preview-content" + " a").click(function() {return false;});
				document.dispatchEvent(new Event('postprocess_liveupdate'));
			}
		},
		"json"
	);
	return true;
}

function unpause() {
	// unpause auto reloads if they are currently stopped
	totStopped = false;
	stopped = false;
	$('#pause').html('');
}

// load more network content (used for infinite scroll)
function loadScrollContent() {
	if (lockLoadContent) {
		return;
	}
	lockLoadContent = true;

	$("#scroll-loader").fadeIn('normal');

	match = $("span.received").last();
	if (match.length > 0) {
		received = match[0].innerHTML;
	} else {
		received = "0000-00-00 00:00:00";
	}

	match = $("span.created").last();
	if (match.length > 0) {
		created = match[0].innerHTML;
	} else {
		created = "0000-00-00 00:00:00";
	}

	match = $("span.commented").last();
	if (match.length > 0) {
		commented = match[0].innerHTML;
	} else {
		commented = "0000-00-00 00:00:00";
	}

	match = $("span.uriid").last();
	if (match.length > 0) {
		uriid = match[0].innerHTML;
	} else {
		uriid = "0";
	}

	// get the raw content from the next page and insert this content
	// right before "#conversation-end"
	$.get({
		url: infinite_scroll.reload_uri,
		data: {
			'mode'          : 'raw',
			'last_received' : received,
			'last_commented': commented,
			'last_created'  : created,
			'last_uriid'    : uriid
		}
	})
	.done(function(data) {
		$("#scroll-loader").hide();
		if ($(data).length > 0) {
			$(data).insertBefore('#conversation-end');
		} else {
			$("#scroll-end").fadeIn('normal');
		}

		document.dispatchEvent(new Event('postprocess_liveupdate'));
	})
	.always(function () {
		$("#scroll-loader").hide();
		lockLoadContent = false;
	});
}

function bin2hex(s) {
	// Converts the binary representation of data to hex
	//
	// version: 812.316
	// discuss at: http://phpjs.org/functions/bin2hex
	// +   original by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
	// +   bugfixed by: Onno Marsman
	// +   bugfixed by: Linuxworld
	// *     example 1: bin2hex('Kev');
	// *     returns 1: '4b6576'
	// *     example 2: bin2hex(String.fromCharCode(0x00));
	// *     returns 2: '00'
	var v,i, f = 0, a = [];
	s += '';
	f = s.length;

	for (i = 0; i<f; i++) {
		a[i] = s.charCodeAt(i).toString(16).replace(/^([\da-f])$/,"0$1");
	}

	return a.join('');
}

function groupChangeMember(gid, cid, sec_token) {
	$('body .fakelink').css('cursor', 'wait');
	$.get('group/' + gid + '/' + cid + "?t=" + sec_token, function(data) {
			$('#group-update-wrapper').html(data);
			$('body .fakelink').css('cursor', 'auto');
	});
}

function contactgroupChangeMember(checkbox, gid, cid) {
	let url;
	// checkbox.checked is the checkbox state after the click
	if (checkbox.checked) {
		url = 'group/' + gid + '/add/' + cid;
	} else {
		url = 'group/' + gid + '/remove/' + cid;
	}
	$('body').css('cursor', 'wait');
	$.post(url)
	.error(function () {
		// Restores previous state in case of error
		checkbox.checked = !checkbox.checked;
	})
	.always(function() {
		$('body').css('cursor', 'auto');
	});

	return true;
}

function checkboxhighlight(box) {
	if ($(box).is(':checked')) {
		$(box).addClass('checkeditem');
	} else {
		$(box).removeClass('checkeditem');
	}
}

function notificationMarkAll() {
	$.get('notification/mark/all', function(data) {
		if (timer) {
			clearTimeout(timer);
		}
		timer = setTimeout(NavUpdate,1000);
		force_update = true;
	});
}

/**
 * sprintf in javascript
 *	"{0} and {1}".format('zero','uno');
 **/
String.prototype.format = function() {
	var formatted = this;
	for (var i = 0; i < arguments.length; i++) {
		var regexp = new RegExp('\\{'+i+'\\}', 'gi');
		formatted = formatted.replace(regexp, arguments[i]);
	}
	return formatted;
};
// Array Remove
Array.prototype.remove = function(item) {
	to=undefined; from=this.indexOf(item);
	var rest = this.slice((to || from) + 1 || this.length);
	this.length = from < 0 ? this.length + from : from;
	return this.push.apply(this, rest);
};

function previewTheme(elm) {
	theme = $(elm).val();
	$.getJSON('pretheme?theme=' + theme,function(data) {
			$('#theme-preview').html('<div id="theme-desc">' + data.desc + '</div><div id="theme-version">' + data.version + '</div><div id="theme-credits">' + data.credits + '</div><a href="' + data.img + '"><img src="' + data.img + '" width="320" height="240" alt="' + theme + '" /></a>');
	});

}

// notification permission settings in localstorage
// set by settings page
function getNotificationPermission() {
	if (window["Notification"] === undefined) {
		return null;
	}

	if (Notification.permission === 'granted') {
		var val = localStorage.getItem('notification-permissions');
		if (val === null) {
			return 'denied';
		}
		return val;
	} else {
		return Notification.permission;
	}
}

/**
 * Show a dialog loaded from an url
 * By defaults this load the url in an iframe in colorbox
 * Themes can overwrite `show()` function to personalize it
 */
var Dialog = {
	/**
	 * Show the dialog
	 *
	 * @param string url
	 * @return object colorbox
	 */
	show : function (url) {
		var size = Dialog._get_size();
		return $.colorbox({href: url, iframe:true,innerWidth: size.width+'px',innerHeight: size.height+'px'})
	},

	/**
	 * Show the Image browser dialog
	 *
	 * @param string name
	 * @param string id (optional)
	 * @return object
	 *
	 * The name will be used to build the event name
	 * fired by image browser dialog when the user select
	 * an image. The optional id will be passed as argument
	 * to the event handler
	 */
	doImageBrowser : function (name, id) {
		var url = Dialog._get_url("image",name,id);
		return Dialog.show(url);
	},

	/**
	 * Show the File browser dialog
	 *
	 * @param string name
	 * @param string id (optional)
	 * @return object
	 *
	 * The name will be used to build the event name
	 * fired by file browser dialog when the user select
	 * a file. The optional id will be passed as argument
	 * to the event handler
	 */
	doFileBrowser : function (name, id) {
		var url = Dialog._get_url("file",name,id);
		return Dialog.show(url);
	},

	_get_url : function(type, name, id) {
		var hash = name;
		if (id !== undefined) {
			hash = hash + "-" + id;
		}
		return baseurl + "/fbrowser/"+type+"/?mode=minimal#"+hash;
	},

	_get_size: function() {
		return {
			width: window.innerWidth-50,
			height: window.innerHeight-100
		};
	}
}
// @license-end
