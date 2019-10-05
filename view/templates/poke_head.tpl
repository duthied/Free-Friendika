
<script>
$(document).ready(function() {
	$("#poke-recip").name_autocomplete(baseurl + '/search/acl', 'a', true, function(data) {
		$("#poke-recip-complete").val(data.id);
	});
});
</script>
