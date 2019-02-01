var linkPreview;

$(document).ready(function() {
	linkPreview = $('#profile-jot-text').linkPreview();
});


/**
 * Insert a link into friendica jot.
 * 
 * @returns {void}
 */
function jotGetLink() {
	var currentText = $("#profile-jot-text").val();
	var noAttachment = '';
	reply = prompt(aStr.linkurl);
	if(reply && reply.length) {
		// There should be only one attachment per post.
		// So we need to remove the old one.
		$('#jot-attachment-preview').empty();
		$('#profile-rotator').show();
		if (currentText.includes("[attachment") && currentText.includes("[/attachment]")) {
			noAttachment = '&noAttachment=1';
		}

		// We use the linkPreview library to have a preview
		// of the attachments.
		linkPreview.crawlText(reply + noAttachment);
		autosize.update($("#profile-jot-text"));
	}
}
