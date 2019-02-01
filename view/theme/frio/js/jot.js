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

/**
 * Get in a textarea the previous word before the cursor.
 * 
 * @param {object} text Textarea elemet.
 * @param {integer} caretPos Cursor position.
 * 
 * @returns {string} Previous word.
 */
function returnWord(text, caretPos) {
	var index = text.indexOf(caretPos);
	var preText = text.substring(0, caretPos);
	// If the last charachter is a space remove the one space
	// We need this in friendica for the url  preview.
	if (preText.slice(-1) == " ") {
		preText = preText.substring(0, preText.length -1);
	}
//	preText = preText.replace(/^\s+|\s+$/g, "");
	if (preText.indexOf(" ") > 0) {
		var words = preText.split(" ");
		return words[words.length - 1]; //return last word
	}
	else {
		return preText;
	}
}

/**
 * Get in a textarea the previous word before the cursor.
 * 
 * @param {string} id The ID of a textarea element.
 * @returns {sting|null} Previous word or null if no word is available.
 */
function getPrevWord(id) {
	var text = document.getElementById(id);
	var caretPos = getCaretPosition(text);
	var word = returnWord(text.value, caretPos);
	if (word != null) {
		return word
	}

}

/**
 * Get the cursor posiotion in an text element.
 * 
 * @param {object} ctrl Textarea elemet.
 * @returns {integer} Position of the cursor.
 */
function getCaretPosition(ctrl) {
	var CaretPos = 0;   // IE Support
	if (document.selection) {
		ctrl.focus();
		var Sel = document.selection.createRange();
		Sel.moveStart('character', -ctrl.value.length);
		CaretPos = Sel.text.length;
	}
	// Firefox support
	else if (ctrl.selectionStart || ctrl.selectionStart == '0') {
		CaretPos = ctrl.selectionStart;
	}
	return (CaretPos);
}
