// We append the linkPreview to a global Variable to make linkPreview
// accessable on other places. Note: search on other places before you
// delete or move the variable.
var linkPreview;

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
		if (typeof linkPreview === 'object') {
			linkPreview.crawlText(reply + noAttachment);

		// Fallback: insert the attachment bbcode directly into the textarea
		// if the attachment live preview isn't available
		} else {
			$.get('parse_url?binurl=' + bin2hex(reply) + noAttachment, function(data) {
				addeditortext(data);
				$('#profile-rotator').hide();
			});
		}
		autosize.update($("#profile-jot-text"));
	}
}
