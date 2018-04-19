/**
 * @brief Javascript for the admin module
 */
$(function() {
	$('body').on('click', '.selectall', function() {
		selectall($(this).data('selectAll'));
	});
	$('body').on('click', '.selectnone', function() {
		selectnone($(this).data('selectNone'));
	});

	$('body').on('change', 'input[type=checkbox].select', function() {
		$this = $(this);
		if ($this.prop('checked')) {
			selectall($this.data('selectClass'));
			$this.attr('title', $this.data('selectNone'));
		} else {
			selectnone($this.data('selectClass'));
			$this.attr('title', $this.data('selectAll'));
		}
	});


	function selectall(cls) {
		$('.' + cls).prop('checked', true);
		return false;
	}
	function selectnone(cls) {
		$('.' + cls).prop('checked', false);
		return false;
	}


});

// Users
function confirm_delete(msg, uname){
	return confirm(msg.format(uname));
}

function details(uid) {
	$("#user-"+uid+"-detail").toggleClass("hidden");
	$("#user-"+uid).toggleClass("opened");
	return false;
}
