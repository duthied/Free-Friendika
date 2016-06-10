
<script>
$(document).ready(function() {
	$("#nav-search-input-field").search_autocomplete(baseurl + '/acl');
	
	$("#loginModal").on("show.bs.modal", function(e) {
	    var link = $(e.relatedTarget);
		$(this).find(".modal-body").load(link.attr("href"));
	});
	
});
</script>
