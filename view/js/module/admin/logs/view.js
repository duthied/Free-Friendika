(function(){
	function log_show_details(elm) {
		const id = elm.id;
		var hidden = true;
		document
			.querySelectorAll('[data-id="' + id + '"]')
			.forEach(edetails => {
				hidden = edetails.classList.toggle('hidden');
			});
		document
			.querySelectorAll('[aria-expanded="true"]')
			.forEach(eexpanded => {
				eexpanded.setAttribute('aria-expanded', false);
			});
		
		if (!hidden) {
			elm.setAttribute('aria-expanded', true);
		}
	}

	document
		.querySelectorAll('.log-event')
		.forEach(elm => {
			elm.addEventListener("click", evt => {
				log_show_details(evt.currentTarget);
			});
			elm.addEventListener("keydown", evt => {
				if (evt.keyCode == 13 || evt.keyCode == 32) {
					log_show_details(evt.currentTarget);
				}
			});
		});
})();