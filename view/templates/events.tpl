
{{$tabs nofilter}}
{{include file="section_title.tpl"}}

<div id="new-event-link"><a href="{{$new_event.0}}" >{{$new_event.1}}</a></div>

<div id="event-calendar-wrapper">
	<a href="{{$previus.0}}" class="prevcal {{$previus.2}}"><div id="event-calendar-prev" class="icon s22 prev" title="{{$previus.1}}"></div></a>
	{{$calendar}}
	<a href="{{$next.0}}" class="nextcal {{$next.2}}"><div id="event-calendar-prev" class="icon s22 next" title="{{$next.1}}"></div></a>
</div>
<div class="event-calendar-end"></div>


{{foreach $events as $event}}
	<div class="event">
	{{if $event.is_first}}<hr /><a name="link-{{$event.j}}" ><div class="event-list-date">{{$event.d}}</div></a>{{/if}}
	{{if $event.item.author_name}}<a href="{{$event.item.author_link}}" ><img src="{{$event.item.author_avatar}}" height="32" width="32" />{{$event.item.author_name}}</a>{{/if}}
	{{$event.html nofilter}}
	{{if $event.item.plink}}<a href="{{$event.plink.0}}" title="{{$event.plink.1}}" target="_blank" rel="noopener noreferrer" class="plink-event-link icon s22 remote-link"></a>{{/if}}
	{{if $event.edit}}<a href="{{$event.edit.0}}" title="{{$event.edit.1}}" class="edit-event-link icon s22 pencil"></a>{{/if}}
	</div>
	<div class="clear"></div>

{{/foreach}}
