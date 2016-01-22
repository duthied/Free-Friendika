
<script>
$(document).ready(function() {
	$("#poke-recip").name_autocomplete(baseurl + '/acl', 'a', true, function(data) {
		$("#poke-recip-complete").val(data.id);
	});
});
</script>
