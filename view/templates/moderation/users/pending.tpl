<script>
	function confirm_delete(uname) {
		return confirm("{{$confirm_delete}}".format(uname));
	}

	function confirm_delete_multi() {
		return confirm("{{$confirm_delete_multi}}");
	}

	function selectall(cls) {
		$("." + cls).attr('checked', 'checked');
		return false;
	}
</script>
<div id="adminpage">
	<h1>{{$title}} - {{$page}} ({{$count}})</h1>

	<form action="{{$baseurl}}/{{$query_string}}" method="post">
		<input type="hidden" name="form_security_token" value="{{$form_security_token}}">

	{{if $pending}}
		<table id="pending">
			<thead>
			<tr>
				{{foreach $th_pending as $th}}
					<th>{{$th}}</th>{{/foreach}}
				<th></th>
				<th></th>
			</tr>
			</thead>
			<tbody>
			{{foreach $pending as $u}}
				<tr>
					<td class="created">{{$u.created}}</td>
					<td class="name">{{$u.name}}</td>
					<td class="email">{{$u.email}}</td>
					<td class="checkbox">
						<input type="checkbox" class="pending_ckbx" id="id_pending_{{$u.hash}}" name="pending[]" value="{{$u.hash}}"/>
					</td>
					<td class="tools">
						<a href="{{$baseurl}}/moderation/users/pending/allow/{{$u.uid}}?t={{$form_security_token}}" title="{{$approve}}">
							<span class="icon like"></span>
						</a>
						<a href="{{$baseurl}}/moderation/users/pending/deny/{{$u.uid}}?t={{$form_security_token}}" title="{{$deny}}">
							<span class="icon dislike"></span>
						</a>
					</td>
				</tr>
				<tr>
					<td class="pendingnote"><p><span>{{$pendingnotetext}}:</span> {{$u.note}}</p></td>
				</tr>
			{{/foreach}}
			</tbody>
		</table>
		<div class="selectall"><a href="#" onclick="return selectall('pending_ckbx');">{{$select_all}}</a></div>
		<div class="submit">
			<input type="submit" name="page_users_deny" value="{{$deny}}"/>
			<input type="submit" name="page_users_approve" value="{{$approve}}"/>
		</div>
	{{else}}
		<p>{{$no_pending}}</p>
	{{/if}}
	</form>
</div>
