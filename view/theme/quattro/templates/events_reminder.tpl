
<link rel="stylesheet" type="text/css" href="{{$baseurl}}/view/asset/fullcalendar/dist/fullcalendar.min.css?v={{$smarty.const.FRIENDICA_VERSION}}" />
<link rel="stylesheet" type="text/css" href="{{$baseurl}}/view/asset/fullcalendar/dist/fullcalendar.print.min.css?v={{$smarty.const.FRIENDICA_VERSION}}" />
<script type="text/javascript" src="{{$baseurl}}/view/asset/moment/min/moment-with-locales.min.js?v={{$smarty.const.FRIENDICA_VERSION}}"></script>
<script type="text/javascript" src="{{$baseurl}}/view/asset/fullcalendar/dist/fullcalendar.min.js?v={{$smarty.const.FRIENDICA_VERSION}}"></script>
<script>
	// start calendar from yesterday
	var yesterday= new Date()
	yesterday.setDate(yesterday.getDate()-1)

	function showEvent(eventid) {
		$.get(
			'{{$event_api}}/'+eventid,
			function(data){
				$.colorbox({html:data});
			}
		);
	}
	$(document).ready(function() {
		$('#events-reminder').fullCalendar({
			firstDay: yesterday.getDay(),
			year: yesterday.getFullYear(),
			month: yesterday.getMonth(),
			date: yesterday.getDate(),
			events: 'calendar/api/get',
			header: false,
			timeFormat: 'H(:mm)',
			defaultView: 'basicWeek',
			contentHeight: 50,
			eventClick: function(calEvent, jsEvent, view) {
				showEvent(calEvent.id);
			}
		});
	});
</script>
<div id="events-reminder"></div>
<br>
