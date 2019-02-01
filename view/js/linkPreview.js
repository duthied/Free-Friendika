/**
 * Copyright (c) 2014 Leonardo Cardoso (http://leocardz.com)
 * Dual licensed under the MIT (http://www.opensource.org/licenses/mit-license.php)
 * and GPL (http://www.opensource.org/licenses/gpl-license.php) licenses.
 * 
 * restructured from rabzuarus (https://friendica.kommune4.de/profile/rabuzarus)
 * for the decental social network Friendica.
 * 
 * Version: 1.4.0
 */
(function ($) {
	$.fn.linkPreview = function (options) {
		var opts = jQuery.extend({}, $.fn.linkPreview.defaults, options);

		var selector = $(this).selector;
		selector = selector.substr(1);

		var previewTpl = '\
			<div id="preview_' + selector + '" class="preview {0}">{1}</div>\
			<div style="clear: both"></div>';
		var attachmentTpl = '\
			<hr class="previewseparator">\
			<div id="closePreview_' + selector + '" title="Remove" class="closePreview" >\
				<button type="button" class="previewActionBtn">×</button>\
			</div>\
			<div id="previewImages_' + selector + '" class="previewImages">\
				<div id="previewImgBtn_' + selector + '" class="previewImgBtn">\
					<button type="button" id="previewChangeImg_' + selector + '" class="buttonChangeDeactive previewActionBtn" style="display: none">\
						<i class="fa fa-exchange" aria-hidden="true"></i>\
					</button>\
				</div>\
				<div id="previewImage_' + selector + '" class="previewImage">\
				</div>\
				<input type="hidden" id="photoNumber_' + selector + '" class="photoNumber" value="0" />\
			</div>\
			<div id="previewContent_' + selector + '" class="previewContent">\
				<h4 id="previewTitle_' + selector + '" class="previewTitle"></h4>\
				<blockquote id="previewDescription_' + selector + '" class="previewDescription"></blockquote>\
				<div id="hiddenDescription_' + selector + '" class="hiddenDescription"></div>\
				<sup id="previewUrl_' + selector + '" class="previewUrl"></sup>\
			</div>\
			<div class="clear"></div>\
			<hr class="previewseparator">';
		var text;
		var urlRegex = /(https?\:\/\/|\s)[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,4})(\/+[a-z0-9_.\:\;-]*)*(\?[\&\%\|\+a-z0-9_=,\.\:\;-]*)?([\&\%\|\+&a-z0-9_=,\:\;\.-]*)([\!\#\/\&\%\|\+a-z0-9_=,\:\;\.-]*)}*/i;
		var binurl;
		var block = false;
		var blockTitle = false;
		var blockDescription = false;
		var cache = {};
		var images = "";
		var isExtern = false;
		var photoNumber = 0;
		var firstPosted = false;
		var isActive = false;
		var isCrawling = false;
		var defaultTitle = opts.defaultTitle;
		var defaultDescription = opts.defaultDescription;

		var init = function() {
			$('#' + selector).bind({
				paste: function () {
					setTimeout(function () {
						crawlText();
					}, 100);
				},
				keyup: function (e) {
					// on enter, space, ctrl
					if ((e.which === 13 || e.which === 32 || e.which === 17)) {
						crawlText();
					}
				}
			});
		};
		var resetPreview = function() {
			$('#previewChangeImg_' + selector).removeClass('buttonChangeActive');
			$('#previewChangeImg_' + selector).addClass('buttonChangeDeactive');
			photoNumber = 0;
			images = "";
		}

		var crawlText = function (text) {
			block = false;
			images = '';
			isExtern = false;

			if (typeof text === 'undefined') {
				text = getPrevWord(selector);
			} else {
				isExtern = true;
			}

			if (trim(text) !== "") {
				if (block === false && urlRegex.test(text)) {
					binurl = bin2hex(text);
					block = true;

					isCrawling = true;
					$('#profile-rotator').show();

					if (binurl in cache) {
						isCrawling = false;
						processContentData(cache[binurl]);
					} else {
						getContentData(binurl, processContentData);
					}
				}
			}
		};

		var processContentData = function(result) {
			if (result.contentType === 'image') {
				insertImage(result.data);
			}
			if (result.contentType === 'attachment') {
				insertAttachment(result.data);
			}
			$('#profile-rotator').hide();
		}

		var getContentData = function(binurl, callback) {
			$.get('parse_url?binurl='+ binurl + '&dataType=json', function (answer) {
				if (typeof answer.contentType === 'undefined'
					|| answer.contentType === null)
				{
					answer.contentType = "";
				}
				if (typeof answer.data.url === 'undefined'
					|| answer.data.url === null)
				{
					answer.data.url = "";
				}
				if (typeof answer.data.title === 'undefined'
					|| answer.data.title === null
					|| answer.data.title === "")
				{
					answer.data.title = defaultTitle;
				}
				if (typeof answer.data.text === 'undefined'
					|| answer.data.text === null
					|| answer.data.text === "")
				{
					answer.data.text = "";
				}
				if (typeof answer.data.images === 'undefined'
					|| answer.data.images === null)
				{
					answer.data.images = "";
				}

				// Put the data into a cache
				cache[binurl] = answer;

				callback(answer);

				isCrawling = false;
			});
		}

		var insertImage = function(json) {
			if (!isExtern) {
				return
			}
			var bbcode = '\n[img]' + json.url + '[/img]\n';
			addeditortext(bbcode);
		};

		var insertAudio = function(json) {
			if (!isExtern) {
				return
			}
			var bbcode = '\n[audio]' + json.url + '[/audio]\n';
			addeditortext(bbcode);
		};

		var insertVideo = function(json) {
			if (!isExtern) {
				return
			}
			var bbcode = '\n[video]' + json.url + '[/video]\n';
			addeditortext(bbcode);
		};

		var insertAttachment = function(json) {
			// If we have already a preview, leaver here.
			// Note: if we finish the Preview of other media content type,
			// we can move this condition to the beggining of crawlText();
			if (isActive) {
				return;
			}

			if (json.type != 'link' && json.type != 'video' && json.type != 'photo' || json.url == json.title) {
				return;
			}

			$('#photoNumber_' + selector).val(0);
			resetPreview();

			var typeClass = 'type-' + json.type;
			var imageClass = 'attachment-preview';
			var urlHost = "";
			var description = json.text;

			// Load and add the template if it isn't allready loaded.
			if ($('#preview_' + selector).length == 0) {
				var tpl = previewTpl.format(typeClass, attachmentTpl);
				$('#' + selector).after(tpl);
			}

			isActive = true;

			if (description === '') {
				description = defaultDescription;
			}

			$('#previewTitle_' + selector).html("\
				<span id='previewSpanTitle_" + selector + "' class='previewSpanTitle' >" + escapeHTML(json.title) + "</span>\
				<input type='text' value='" + escapeHTML(json.title) + "' id='previewInputTitle_" + selector + "' class='previewInputTitle inputPreview' style='display: none;'/>"
			);


			$('#previewDescription_' + selector).html("\
				<span id='previewSpanDescription_" + selector + "' class='previewSpanDescription' >" + escapeHTML(description) + "</span>\n\
				<textarea id='previewInputDescription_" + selector + "' class='previewInputDescription' style='display: none;' class='inputPreview' >" + escapeHTML(json.text) + "</textarea>"
			);

			if (json.url) {
				var regexpr = "(https?://)([^:^/]*)(:\\d*)?(.*)?";
				var regResult = json.url.match(regexpr);
				var urlHost = regResult[1] + regResult[2];
				$('#previewUrl_' + selector).html("<a href='" + json.url + "'>" + urlHost + "</a>");
			}

			images = json.images;

			if (Array.isArray(images)) {
				$('#previewImages_' + selector).show();
			} else {
				$('#previewImages_' + selector).hide();
			}

			images.length = parseInt(images.length);
			var appendImage = "";

			for (i = 0; i < images.length; i++) {
				// For small preview images we use a smaller attachment format.
//				if (Array.isArray(images) && typeof images[i].width !== 'undefined') {
					///@todo here we need to add a check for !Config::get('system', 'always_show_preview').
					if (images[i].width >= 500 && images[i].width >= images[i].height) {
							imageClass = 'attachment-image';
					}
//				}
				if (i === 0) {
					appendImage += "<img id='imagePreview_" + selector + "_" + i + "' src='" + images[i].src + "' class='" + imageClass + "' ></img>";
				} else {
					appendImage += "<img id='imagePreview_" + selector + "_" + i + "' src='" + images[i].src + "' class='" + imageClass + "' style='display: none;'></img>";
				}
			}

			$('#previewImage_' + selector).html(appendImage + "<div id='whiteImage' style='color: transparent; display:none;'>...</div>");

			// more than just one image.
			if (images.length > 1) {
				// Enable the the button to change the preview pictures.
				$('#previewChangeImg_' + selector).show();

				if (firstPosted === false) {
					firstPosted = true;

					$('#previewChangeImg_' + selector).unbind('click').click(function (e) {
						e.stopPropagation();
						if (images.length > 1) {
//							photoNumber = parseInt($('#photoNumber_' + selector).val());
							$('#imagePreview_' + selector + '_' + photoNumber).css({
								'display': 'none'
							});
							photoNumber += 1;

							// If have reached the last image, begin with the first image.
							if (photoNumber === images.length) {
								photoNumber = 0;
							}

							$('#imagePreview_' + selector + '_' + photoNumber).css({
								'display': 'block'
							});
							$('#photoNumber_' + selector).val(photoNumber);
						}
					});
				}
			}

			processEventListener();
			$('#profile-rotator').hide();
		};

		var processEventListener = function() {
			$('#previewSpanTitle_' + selector).unbind('click').click(function (e) {
				e.stopPropagation();
				if (blockTitle === false) {
					blockTitle = true;
					$('#previewSpanTitle_' + selector).hide();
					$('#previewInputTitle_' + selector).show();
					$('#previewInputTitle_' + selector).val($('#previewInputTitle_' + selector).val());
					$('#previewInputTitle_' + selector).focus().select();
				}
			});

			$('#previewInputTitle_' + selector).blur(function () {
				blockTitle = false;
				$('#previewSpanTitle_' + selector).html($('#previewInputTitle_' + selector).val());
				$('#previewSpanTitle_' + selector).show();
				$('#previewInputTitle_' + selector).hide();
			});

			$('#previewInputTitle_' + selector).keypress(function (e) {
				if (e.which === 13) {
					blockTitle = false;
					$('#previewSpanTitle_' + selector).html($('#previewInputTitle_' + selector).val());
					$('#previewSpanTitle_' + selector).show();
					$('#previewInputTitle_' + selector).hide();
				}
			});

			$('#previewSpanDescription_' + selector).unbind('click').click(function (e) {
				e.stopPropagation();
				if (blockDescription === false) {
					blockDescription = true;
					$('#previewSpanDescription_' + selector).hide();
					$('#previewInputDescription_' + selector).show();
					$('#previewInputDescription_' + selector).val($('#previewInputDescription_' + selector).val());
					$('#previewInputDescription_' + selector).focus().select();
				}
			});

			$('#previewInputDescription_' + selector).blur(function () {
				blockDescription = false;
				$('#previewSpanDescription_' + selector).html($('#previewInputDescription_' + selector).val());
				$('#previewSpanDescription_' + selector).show();
				$('#previewInputDescription_' + selector).hide();
			});

			$('#previewInputDescription_' + selector).keypress(function (e) {
				if (e.which === 13) {
					blockDescription = false;
					$('#previewSpanDescription_' + selector).html($('#previewInputDescription_' + selector).val());
					$('#previewSpanDescription_' + selector).show();
					$('#previewInputDescription_' + selector).hide();
				}
			});

			$('#previewSpanTitle_' + selector).mouseover(function () {
				$('#previewSpanTitle_' + selector).css({
					"background-color": "#ff9"
				});
			});

			$('#previewSpanTitle_' + selector).mouseout(function () {
				$('#previewSpanTitle_' + selector).css({
					"background-color": "transparent"
				});
			});

			$('#previewSpanDescription_' + selector).mouseover(function () {
				$('#previewSpanDescription_' + selector).css({
					"background-color": "#ff9"
				});
			});

			$('#previewSpanDescription_' + selector).mouseout(function () {
				$('#previewSpanDescription_' + selector).css({
					"background-color": "transparent"
				});
			});

			$('#closePreview_' + selector).unbind('click').click(function (e) {
				e.stopPropagation();
				block = false;
				images = '';
				isActive = false;
				firstPosted = false;
				$('#preview_' + selector).fadeOut("fast", function () {
					$('#preview_' + selector).remove();
					$('#profile-rotator').hide();
				});

			});
		};

		var trim = function(str) {
			return str.replace(/^\s+|\s+$/g, "");
		};
		var escapeHTML = function(unsafe_str) {
			return unsafe_str
				.replace(/&/g, '&amp;')
				.replace(/</g, '&lt;')
				.replace(/>/g, '&gt;')
				.replace(/\"/g, '&quot;')
				.replace(/\[/g, '&#91;')
				.replace(/\]/g, '&#93;')
				.replace(/\'/g, '&#39;'); // '&apos;' is not valid HTML 4
		}

		// Initialize LinkPreview 
		init();

		return {
			// make crawlText() accessable from the outside.
			crawlText: function (text) {
				crawlText(text);
			}
		};
	};

	$.fn.linkPreview.defaults = {
		defaultDescription: "Enter a description",
		defaultTitle: "Enter a title"
	};
})(jQuery);
