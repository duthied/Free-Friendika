
<div class="vevent event-card">
	<div class="vevent-header">
		<div class="event-card-details">
			<div class="event-card-header">
				<div class="event-card-left-date">
					<span class="event-date-wrapper medium">
						<span class="event-card-short-month">{{$month_short}}</span>
						<span class="event-card-short-date">{{$date_short}}</span>
					</span>
				</div>
				<div class="event-card-content media-body">
					<div class="event-title event-card-title summary event-summary">{{$title}}</div>
					{{if $location.map}}<button id="event-map-btn-{{$id}}" class="event-map-btn btn-link fakelink nav nav-pills preferences" data-map-id="event-location-map-{{$id}}" data-show-label="{{$show_map_label}}" data-hide-label="{{$hide_map_label}}">{{$map_btn_label}}</button>{{/if}}
					<div class="event-property">
						<span class="event-date">
							<span class="event-start dtstart" title="{{$dtstart_title}}">{{$start_short}}</span>
							{{if $finish}} - <span class="event-end dtend" title="{{$dtend_title}}">{{if $same_date}}{{$end_time}}{{else}}{{$end_short}}{{/if}}</span>{{/if}}
						</span>
						{{if $location.name}}
						<span role="presentation" aria-hidden="true"> · </span>
						<span class="event-location event-card-location">{{$location.name}}</span>
						{{/if}}
					</div>
					<div class="event-card-profile-name profile-entry-name">
						<a href="{{$author_link}}" class="userinfo">{{$author_name}}</a>
					</div>
					{{if $location.map}}
					<div id="event-location-map-{{$id}}" class="event-location-map">{{$location.map}}</div>
					{{/if}}
				</div>
				<div class="clearfix"></div>
			</div>
		</div>
	</div>
	<div class="clearfix"></div>

	{{if $description}}
	<div class="description event-description">
		<hr class="seperator" />
		{{$description}}
	</div>
{{/if}}
</div>
