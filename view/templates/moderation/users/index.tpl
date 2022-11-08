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
		<table id="users">
			<thead>
			<tr>
				<th></th>
				{{foreach $th_users as $th}}
					<th>
						<a href="{{$baseurl}}/moderation/users?o={{if $order_direction_users == "+"}}-{{/if}}{{$th.1}}">
						{{if $order_users == $th.1}}
							{{if $order_direction_users == "+"}}
								&#8595;
							{{else}}
								&#8593;
							{{/if}}
						{{else}}
							&#8597;
						{{/if}}
							{{$th.0}}
						</a>
					</th>
				{{/foreach}}
				<th></th>
				<th></th>
			</tr>
			</thead>
			<tbody>
			{{foreach $users as $u}}
				<tr>
					<td><img class="icon" src="{{$u.micro}}" alt="{{$u.nickname}}" title="{{$u.nickname}}"></td>
					<td class="name"><a href="{{$u.url}}" title="{{$u.nickname}}">{{$u.name}}</a></td>
					<td class="email">{{$u.email}}</td>
					<td class="register_date">{{$u.register_date}}</td>
					<td class="login_date">{{$u.login_date}}</td>
					<td class="lastitem_date">{{$u.lastitem_date}}</td>
					<td class="login_date">{{$u.page_flags}} {{if $u.is_admin}}({{$siteadmin}}){{/if}} {{if $u.account_expired}}({{$accountexpired}}){{/if}} {{if $u.blocked}}{{$blocked}}{{/if}}</td>
					<td class="checkbox">
						{{if $u.is_deletable}}
						<input type="checkbox" class="users_ckbx" id="id_user_{{$u.uid}}" name="user[]" value="{{$u.uid}}"/>
						{{else}}
							&nbsp;
						{{/if}}
					</td>

					<td class="tools">
						{{if $u.is_deletable}}
							<a href="{{$baseurl}}/moderation/users/block/{{$u.uid}}?t={{$form_security_token}}" title="{{if $u.blocked}}{{$unblock}}{{else}}{{$block}}{{/if}}">
								<span class="icon block {{if $u.blocked==0}}dim{{/if}}"></span>
							</a>
							<a href="{{$baseurl}}/moderation/users/delete/{{$u.uid}}?t={{$form_security_token}}" title="{{$delete}}" onclick="return confirm_delete('{{$u.name}}')">
								<span class="icon drop"></span>
							</a>
						{{else}}
							&nbsp;
						{{/if}}
					</td>
				</tr>
			{{/foreach}}
			</tbody>
		</table>
		<div class="selectall"><a href="#" onclick="return selectall('users_ckbx');">{{$select_all}}</a></div>
		<div class="submit">
			<input type="submit" name="page_users_block" value="{{$block}}"/>
			<input type="submit" name="page_users_unblock" value="{{$unblock}}"/>
			<input type="submit" name="page_users_delete" value="{{$delete}}" onclick="return confirm_delete_multi()"/>
		</div>
	</form>
	{{$pager nofilter}}
	<p>
		<a href="{{$base_url}}/moderation/users/create">{{$h_newuser}}</a>
	</p>
</div>
