
<div class="vevent">
	<div class="summary event-summary">{{$title}}</div>

	<div class="event-start">
		<span class="event-label">{{$dtstart_label}}</span>&nbsp;
		<span class="dtstart" title="$dtstart_title">{{$dtstart_dt}}</span>
	</div>

	{{if $finish}}
	<div class="event-end">
		<span class="event-label">{{$dtstart_label}}</span>&nbsp;
		<span class="dend" title="$dtend_title">{{$dtend_dt}}</span>
	</div>
	{{/if}}

	<div class="description event-description">{{$description}}</div>

	{{if $location}}
	<div class="event-location">
		<span class="event-label">{{$location_label}}</span>&nbsp;
		{{if $location.name}}
		<span class="event-location">{{$location.name}}</span>
		{{/if}}
		{{if $location.map}}{{$location.map}}{{/if}}
		
	</div>
	{{/if}}
</div>
