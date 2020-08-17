
<div class="vevent">
	<div class="summary event-summary">{{$title nofilter}}</div>

	<div class="event-start">
		<span class="event-label">{{$dtstart_label}}</span>&nbsp;
		<span class="dtstart" title="{{$dtstart_title}}">{{$dtstart_dt}}</span>
	</div>

	{{if $finish}}
	<div class="event-end">
		<span class="event-label">{{$dtend_label}}</span>&nbsp;
		<span class="dtend" title="{{$dtend_title}}">{{$dtend_dt}}</span>
	</div>
	{{/if}}

	{{if $description}}
	<div class="description event-description">{{$description nofilter}}</div>
	{{/if}}

	{{if $location}}
	<div class="event-location">
		<span class="event-label">{{$location_label}}</span>&nbsp;
		{{if $location.name}}
		<span class="location">{{$location.name nofilter}}</span>
		{{/if}}
		{{if $location.map}}{{$location.map nofilter}}{{/if}}
		
	</div>
	{{/if}}
</div>
