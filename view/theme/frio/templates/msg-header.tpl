

<script language="javascript" type="text/javascript" src="{{$baseurl}}/library/tinymce/jscripts/tiny_mce/tiny_mce_src.js"></script>
<script language="javascript" type="text/javascript">

var plaintext = '{{$editselect}}';

if(plaintext != 'none') {
	tinyMCE.init({
		theme : "advanced",
		mode : "specific_textareas",
		editor_selector: /(profile-jot-text|prvmail-text)/,
		plugins : "bbcode,paste",
		theme_advanced_buttons1 : "bold,italic,underline,undo,redo,link,unlink,image,forecolor",
		theme_advanced_buttons2 : "",
		theme_advanced_buttons3 : "",
		theme_advanced_toolbar_location : "top",
		theme_advanced_toolbar_align : "center",
		theme_advanced_blockformats : "blockquote,code",
		theme_advanced_resizing : true,
		gecko_spellcheck : true,
		paste_text_sticky : true,
		entity_encoding : "raw",
		add_unload_trigger : false,
		remove_linebreaks : false,
		//force_p_newlines : false,
		//force_br_newlines : true,
		forced_root_block : 'div',
		convert_urls: false,
		content_css: "{{$baseurl}}/view/custom_tinymce.css",
		     //Character count
		theme_advanced_path : false,
		setup : function(ed) {
			ed.onInit.add(function(ed) {
				ed.pasteAsPlainText = true;
				var editorId = ed.editorId;
				var textarea = $('#'+editorId);
				if (typeof(textarea.attr('tabindex')) != "undefined") {
					$('#'+editorId+'_ifr').attr('tabindex', textarea.attr('tabindex'));
					textarea.attr('tabindex', null);
				}
			});
		}
	});
}
else
	$("#comment-edit-text-input").editor_autocomplete(baseurl+"/acl");


</script>

<script>
	$(document).ready(function() {
		{{if $editselect = 'none'}}
		$("#comment-edit-text-input").bbco_autocomplete('bbcode');
		{{/if}}

		//var objDiv = document.getElementById("mail-conversation");
		//objDiv.scrollTop = objDiv.scrollHeight;
		$('#mail-conversation').perfectScrollbar();
		$('#message-preview').perfectScrollbar();
		$('#mail-conversation').scrollTop($('#mail-conversation')[0].scrollHeight);


	});
</script>

