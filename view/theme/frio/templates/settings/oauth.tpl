<div class="generic-page-wrapper">
	{{* include the title template for the settings title *}}
	{{include file="section_title.tpl" title=$title}}
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
					<td><a href="{{$baseurl}}/settings/oauth/delete/{{$app.id}}?t={{$form_security_token}}" class="btn" title="{{$delete}}"><i class="fa fa-trash" aria-hidden="true"></i></a></td>
				</tr>
				{{/foreach}}
			</tbody>
		</table>
	</form>
</div>
