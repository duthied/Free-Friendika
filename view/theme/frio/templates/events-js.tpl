<div class="generic-page-wrapper">
	{{$tabs}}
	{{include file="section_title.tpl" title=$title}}
<div id="new-event-link"><a href="{{$new_event.0}}" >{{$new_event.1}}</a></div>
	<div id="new-event-link"><a onclick="addToModal('{{$new_event.0}}')" >{{$new_event.1}}</a></div>

	<div id="fc-header">
		<div id="fc-header-right" class="pull-right">
			<ul class="nav nav-pills">
				<li class="dropdown pull-right">
					<a class="btn btn-link btn-sm dropdown-toggle" type="button" id="event-calendar-views" data-toggle="dropdown" aria-expanded="true">
						<i class="fa fa-angle-down"></i> Views
					</a>
					<ul class="dropdown-menu pull-right" role="menu" aria-labelledby="event-calendar-views">
						<li role="menuitem">

							<a onclick="changeView('changeView', 'month')">{{$month}}</a>
						</li>
						<li role="menuitem">

							<a onclick="changeView('changeView', 'agendaWeek')">{{$week}}</a>
						</li>
						<li role="menuitem">

							<a onclick="changeView('changeView', 'agendaDay')">{{$day}}</a>
						</li>
					</ul>
				</li>
			</ul>
		</div>
		<div id="fc-fc-header-left" class="btn-group">
			<button class="btn btn-eventnav" onclick="changeView('prev', false);" title="{{$prev}}"><i class="fa fa-angle-up" aria-hidden="true"></i></i></button>
			<button class="btn btn-eventnav btn-separator" onclick="changeView('next', false);" title="{{$next}}"><i class="fa fa-angle-down" aria-hidden="true"></i></i></button>
			<button class="btn btn-eventnav btn-separator" onclick="changeView('today', false);" title="{{$today}}"><i class="fa fa-bullseye"></i></button>
		</div>

		<div id="event-calendar-title"><h4 id="fc-title"></h4></div>

	</div>
	<div id="events-calendar"></div>
</div>
