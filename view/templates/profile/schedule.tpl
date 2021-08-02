<div class="generic-page-wrapper">
	<h1>{{$title}}</h1>
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
				{{foreach $schedule as $entry}}
				<tr>
					<td>{{$entry.scheduled_at}}</td>
					<td>{{$entry.content}}</td>
					<td><a href="{{$baseurl}}/profile/{{$nickname}}/schedule/delete/{{$entry.id}}?t={{$form_security_token}}" class="icon s22 delete" title="{{$delete}}">&nbsp;</a></td>
				</tr>
				{{/foreach}}
			</tbody>
		</table>
	</form>
</div>
