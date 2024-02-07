$(document).ready(function() {
    $.fancybox.defaults.loop = "true";
	$.fancybox.defaults.afterLoad = function(instance, current) {
		current.$image.attr('alt', current.opts.$orig.find('img').attr('alt') );
		current.$image.attr('title', current.opts.$orig.find('img').attr('title') );
	};
    $.fancybox.defaults.caption = function (instance, slide, caption) {
		return slide.$thumb.attr('alt');
	};
});
