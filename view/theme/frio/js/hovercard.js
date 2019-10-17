/*
 * The javascript for friendicas hovercard. Bootstraps popover is needed.
 *
 * Much parts of the code are from Hannes Mannerheims <h@nnesmannerhe.im>
 * qvitter code (https://github.com/hannesmannerheim/qvitter)
 *
 * It is licensed under the GNU Affero General Public License <http://www.gnu.org/licenses/>
 *
 */
$(document).ready(function () {
	// Elements with the class "userinfo" will get a hover-card.
	// Note that this elements does need a href attribute which links to
	// a valid profile url
	$("body").on("mouseover", ".userinfo, .wall-item-responses a, .wall-item-bottom .mention a", function (e) {
		let timeNow = new Date().getTime();
		removeAllHovercards(e, timeNow);
		let contact_url = false;
		let targetElement = $(this);

		// get href-attribute
		if (targetElement.is('[href]')) {
			contact_url = targetElement.attr('href');
		} else {
			return true;
		}

		// no hover card if the element has the no-hover-card class
		if (targetElement.hasClass('no-hover-card')) {
			return true;
		}

		// no hovercard for anchor links
		if (contact_url.substring(0, 1) === '#') {
			return true;
		}

		targetElement.attr('data-awaiting-hover-card', timeNow);

		// store the title in an other data attribute beause bootstrap
		// popover destroys the title.attribute. We can restore it later
		let title = targetElement.attr("title");
		targetElement.attr({"data-orig-title": title, title: ""});

		// if the device is a mobile open the hover card by click and not by hover
		if (typeof is_mobile != "undefined") {
			targetElement[0].removeAttribute("href");
			var hctrigger = 'click';
		} else {
			var hctrigger = 'manual';
		}

		// Timeout until the hover-card does appear
		setTimeout(function () {
			if (
				targetElement.is(":hover")
				&& parseInt(targetElement.attr('data-awaiting-hover-card'), 10) === timeNow
				&& $('.hovercard').length === 0
			) {	// no card if there already is one open
				// get an additional data atribute if the card is active
				targetElement.attr('data-hover-card-active', timeNow);
				// get the whole html content of the hover card and
				// push it to the bootstrap popover
				getHoverCardContent(contact_url, function (data) {
					if (data) {
						targetElement.popover({
							html: true,
							placement: function () {
								// Calculate the placement of the the hovercard (if top or bottom)
								// The placement depence on the distance between window top and the element
								// which triggers the hover-card
								var get_position = $(targetElement).offset().top - $(window).scrollTop();
								if (get_position < 270) {
									return "bottom";
								}
								return "top";
							},
							trigger: hctrigger,
							template: '<div class="popover hovercard" data-card-created="' + timeNow + '"><div class="arrow"></div><div class="popover-content hovercard-content"></div></div>',
							content: data,
							container: "body",
							sanitizeFn: function (content) {
								return DOMPurify.sanitize(content)
							},
						}).popover('show');
					}
				});
			}
		}, 500);
	}).on("mouseleave", ".userinfo, .wall-item-responses a, .wall-item-bottom .mention a", function (e) { // action when mouse leaves the hover-card
		var timeNow = new Date().getTime();
		// copy the original title to the title atribute
		var title = $(this).attr("data-orig-title");
		$(this).attr({"data-orig-title": "", title: title});
		removeAllHovercards(e, timeNow);
	});

	// hover cards should be removed very easily, e.g. when any of these events happen
	$('body').on("mouseleave touchstart scroll click dblclick mousedown mouseup submit keydown keypress keyup", function (e) {
		// remove hover card only for desktiop user, since on mobile we openen the hovercards
		// by click event insteadof hover
		if (typeof is_mobile == "undefined") {
			var timeNow = new Date().getTime();
			removeAllHovercards(e, timeNow);
		}
	});

	// if we're hovering a hover card, give it a class, so we don't remove it
	$('body').on('mouseover', '.hovercard', function (e) {
		$(this).addClass('dont-remove-card');
	});

	$('body').on('mouseleave', '.hovercard', function (e) {
		$(this).removeClass('dont-remove-card');
		$(this).popover("hide");
	});
}); // End of $(document).ready

// removes all hover cards
function removeAllHovercards(event, priorTo) {
	// don't remove hovercards until after 100ms, so user have time to move the cursor to it (which gives it the dont-remove-card class)
	setTimeout(function () {
		$.each($('.hovercard'), function () {
			var title = $(this).attr("data-orig-title");
			// don't remove card if it was created after removeAllhoverCards() was called
			if ($(this).data('card-created') < priorTo) {
				// don't remove it if we're hovering it right now!
				if (!$(this).hasClass('dont-remove-card')) {
					$('[data-hover-card-active="' + $(this).data('card-created') + '"]').removeAttr('data-hover-card-active');
					$(this).popover("hide");
				}
			}
		});
	}, 100);
}

getHoverCardContent.cache = {};

function getHoverCardContent(contact_url, callback) {
	let postdata = {
		url: contact_url,
	};

	// Normalize and clean the profile so we can use a standardized url
	// as key for the cache
	let nurl = cleanContactUrl(contact_url).normalizeLink();

	// If the contact is already in the cache use the cached result instead
	// of doing a new ajax request
	if (nurl in getHoverCardContent.cache) {
		callback(getHoverCardContent.cache[nurl]);
		return;
	}

	$.ajax({
		url: baseurl + "/contact/hovercard",
		data: postdata,
		success: function (data, textStatus, request) {
			getHoverCardContent.cache[nurl] = data;
			callback(data);
		},
	});
}
