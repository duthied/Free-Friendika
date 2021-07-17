<div class="generic-page-wrapper">
	<h1>{{$title}}</h1>
	<form action="settings/oauth" method="post" autocomplete="off">
		<input type='hidden' name='form_security_token' value='{{$form_security_token}}'>
		<table id='application-block' class='table table-condensed table-striped'>
			<thead>
				<tr>
					<th>{{$name}}</th>
					<th>{{$website}}</th>
					<th>{{$created_at}}</th>
				</tr>
			</thead>
			<tbody>
				{{foreach $apps as $app}}
				<tr>
					<td>{{$app.name}}</td>
					<td>{{$app.website}}</td>
					<td>{{$app.created_at}}</td>
					<td><a href="{{$baseurl}}/settings/oauth/delete/{{$app.id}}?t={{$form_security_token}}" class="icon s22 delete" title="{{$delete}}">&nbsp;</a></td>
				</tr>
				{{/foreach}}
			</tbody>
		</table>
	</form>
</div>
