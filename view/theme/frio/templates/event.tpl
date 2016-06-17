
{{foreach $events as $event}}
	<div class="event-wrapper">
		<div class="event">
			<div class="event-owner">
				{{if $event.item.author_name}}
				<a href="{{$event.item.author_link}}" ><img src="{{$event.item.author_avatar}}" />{{$event.item.author_name}}</a>
				{{/if}}
			</div>
			{{$event.html}}

			<div class="event-buttons pull-right">
				{{if $event.item.plink}}<a href="{{$event.plink.0}}" title="{{$event.plink.1}}" target="_blank" class="btn "><i class="fa fa-external-link" aria-hidden="true"></i></a>{{/if}}
				{{if $event.edit}}<a onclick="eventEdit('{{$event.edit.0}}')" title="{{$event.edit.1}}" class="btn"><i class="fa fa-pencil" aria-hidden="true"></i></a>{{/if}}
			</div>
			<div class="clear"></div>
		</div>
	</div>
{{/foreach}}
