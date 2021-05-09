<script>
	$(document).ready(function() {
		$('.settings-content-block').hide();
		$('.settings-heading').click(function(){
			$('.settings-content-block').hide();
			$(this).next('.settings-content-block').toggle();
		});
	});
</script>
