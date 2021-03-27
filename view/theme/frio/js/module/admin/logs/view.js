$(function(){
	$(".log-event").on("click", function(ev) {
		var $modal = $("#logdetail");
		var tr = $modal.find(".main-data tbody tr")[0];
		tr.innerHTML = ev.currentTarget.innerHTML;

		var data = JSON.parse(ev.currentTarget.dataset.source);
		$modal.find(".source-data td").each(function(i,elm){
			var k = elm.dataset.value;
			elm.innerText = data[k];
		});

		var elm = $modal.find(".event-data")[0];
		elm.innerHTML = "";
		var data = ev.currentTarget.dataset.data;
		if (data !== "") {
			elm.innerHTML = "<h3>Data</h3>";
			data = JSON.parse(data);
			elm.innerHTML += recursive_details("", data);
		}

		$modal.modal({})
	})

	function recursive_details(s, data, lev=0) {
		for(var k in data) {
			if (data.hasOwnProperty(k)) {
				var v = data[k];
				var open = lev > 1 ? "" : "open";
				s += "<details " + open + "><summary>" + k + "</summary>";
				if (typeof v === 'object' && v !== null) {
					s = recursive_details(s, v, lev+1);
				} else {
					s +=  $("<pre>").text(v)[0].outerHTML;
				}
				s += "</details>";
			}
		}
		return s;
	}
});
