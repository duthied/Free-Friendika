<div class="generic-page-wrapper">
	{{* include the title template for the settings title *}}
	{{include file="section_title.tpl" title=$title}}
	<table id="application-block" class="table table-condensed table-striped">
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
				<td>
					<form action="{{$baseurl}}/profile/{{$nickname}}/schedule" method="post">
						<input type="hidden" name="form_security_token" value="{{$form_security_token}}">
						<button class="btn btn-default" type="submit" name="delete" value="{{$row.id}}" title="{{$delete}}">
							<i class="fa fa-trash" aria-hidden="true"></i>
						</button>
					</form>
				</td>
			</tr>
			{{/foreach}}
		</tbody>
	</table>
</div>
