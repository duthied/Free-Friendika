<script language="javascript" type="text/javascript">
	$("#id_body").editor_autocomplete(baseurl + '/search/acl');
</script>
<script>
	function jotGetLink() {
		reply = prompt("{{$linkurl}}");
		if (reply && reply.length) {
			$('#profile-rotator').show();
			$.get('parseurl?url=' + reply, function (data) {
				addeditortext(data);
				$('#profile-rotator').hide();
			});
		}
	}
</script>
