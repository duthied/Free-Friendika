<div class="generic-page-wrapper">
	{{* include the title template for the settings title *}}
	{{include file="section_title.tpl" title=$title}}
	<form action="profile/{{$nickname}}/schedule" method="post" autocomplete="off">
		<input type='hidden' name='form_security_token' value='{{$form_security_token}}'>
		<table id='application-block' class='table table-condensed table-striped'>
			<thead>
				<tr>
					<th>{{$scheduled_at}}</th>
					<th>{{$content}}</th>
				</tr>
			</thead>
			<tbody>
				{{foreach $schedule as $row}}
				<tr>
					<td>{{$row.scheduled_at}}</td>
					<td>{{$row.content}}</td>
					<td><a href="{{$baseurl}}/profile/{{$nickname}}/schedule/delete/{{$row.id}}?t={{$form_security_token}}" class="btn" title="{{$delete}}"><i class="fa fa-trash" aria-hidden="true"></i></a></td>
				</tr>
				{{/foreach}}
			</tbody>
		</table>
	</form>
</div>
