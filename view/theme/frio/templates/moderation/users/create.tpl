<div id="admin-users" class="adminpage generic-page-wrapper">
	<h1>{{$title}} - {{$page}}</h1>

	<form action="{{$baseurl}}/{{$query_string}}" method="post">
		<input type="hidden" name="form_security_token" value="{{$form_security_token}}">

		{{include file="field_input.tpl" field=$newusername}}
		{{include file="field_input.tpl" field=$newusernickname}}
		{{include file="field_input.tpl" field=$newuseremail}}
		<p>
			<button type="submit" class="btn btn-primary">{{$submit}}</button>
		</p>
	</form>
</div>
