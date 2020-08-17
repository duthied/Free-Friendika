
<script>
$(document).ready(function() {
	$("#recip").name_autocomplete(baseurl + '/search/acl', 'm', false, function(data) {
		$("#recip-complete").val(data.id);
	});
});
</script>
