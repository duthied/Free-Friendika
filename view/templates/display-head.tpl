{{if $alternate}}
<link href='{{$alternate}}' rel='alternate' type='application/atom+xml'>
{{/if}}
{{if $conversation}}
<link href='{{$conversation}}' rel='conversation' type='application/atom+xml'>
{{/if}}
<script>
$(document).ready(function() {
	$(".comment-edit-wrapper textarea").editor_autocomplete(baseurl + '/search/acl');
	// make auto-complete work in more places
	$(".wall-item-comment-wrapper textarea").editor_autocomplete(baseurl + '/search/acl');
});
</script>
