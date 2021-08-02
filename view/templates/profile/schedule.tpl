<div class="generic-page-wrapper">
	<h1>{{$title}}</h1>
	<table id="application-block" class="table table-condensed table-striped">
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
				<td>
					<form action="{{$baseurl}}/profile/{{$nickname}}/schedule" method="post">
						<input type="hidden" name="form_security_token" value="{{$form_security_token}}">
						<button type="submit" name="delete" value="{{$row.id}}" title="{{$delete}}">
							<i class="fa fa-trash" aria-hidden="true">
						</button>
					</form>
				</td>
			</tr>
			{{/foreach}}
		</tbody>
	</table>
</div>
