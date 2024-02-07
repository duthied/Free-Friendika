<div class="generic-page-wrapper">
	{{* include the title template for the settings title *}}
	{{include file="section_title.tpl" title=$title}}
	<form action="settings/oauth" method="post" autocomplete="off">
		<input type="hidden" name="form_security_token" value="{{$form_security_token}}">
		<table id='application-block' class='table table-condensed table-striped'>
			<thead>
				<tr>
					<th>{{$name}}</th>
					<th>{{$website}}</th>
					<th>{{$created_at}}</th>
					<th></th>
				</tr>
			</thead>
			<tbody>
				{{foreach $apps as $app}}
				<tr>
					<td>{{$app.name}}</td>
					<td>{{$app.website}}</td>
					<td>{{$app.created_at}}</td>
					<td>
						<button type="submit" class="btn" title="{{$delete}}" name="delete" value="{{$app.id}}">
							<i class="fa fa-trash" aria-hidden="true"></i>
						</button>
					</td>
				</tr>
				{{/foreach}}
			</tbody>
		</table>
	</form>
</div>
