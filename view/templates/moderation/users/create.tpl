<div id="adminpage">
	<h1>{{$title}} - {{$page}}</h1>

	<form action="{{$baseurl}}/{{$query_string}}" method="post">
		<input type="hidden" name="form_security_token" value="{{$form_security_token}}">
		<table id="users">
			<tbody>
			<tr>
				<td>{{include file="field_input.tpl" field=$newusername}}</td>
			</tr>
			<tr>
				<td>{{include file="field_input.tpl" field=$newusernickname}}</td>
			</tr>
			<tr>
				<td>{{include file="field_input.tpl" field=$newuseremail}}</td>
			</tr>
			</tbody>
		</table>
		<div class="submit"><input type="submit" name="add_new_user_submit" value="{{$submit}}"/></div>
	</form>
</div>
