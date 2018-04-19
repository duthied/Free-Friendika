<script type="text/javascript" src="view/theme/frio/js/mod_admin.js"></script>
<link rel="stylesheet" href="view/theme/frio/css/mod_admin.css" type="text/css" media="screen"/>

<div id="admin-users" class="adminpage  generic-page-wrapper">
	<h1>{{$title}} - {{$page}}</h1>

	<form action="{{$baseurl}}/admin/users" method="post">
		<input type="hidden" name="form_security_token" value="{{$form_security_token}}">


		<!--
			**
			*
			*		PENDING Users table
			*
			**
		-->
		<div class="panel panel-default">
			<div class="panel-heading"><h3 class="panel-title">{{$h_pending}}</h3></div>

			{{if $pending}}
				<table id="pending" class="table table-hover">
					<thead>
					<tr>
						<th></th>
						{{foreach $th_pending as $th}}<th>{{$th}}</th>{{/foreach}}
						<th></th>
					</tr>
					</thead>
					<tbody>
				{{foreach $pending as $u}}
					<tr>
						<td><input type="checkbox" class="pending_ckbx" id="id_pending_{{$u.hash}}" name="pending[]" value="{{$u.hash}}" /></td>
						<td>{{$u.created}}</td>
						<td>{{$u.name}}</td>
						<td>{{$u.email}}</td>
						<td>
							<a href="{{$baseurl}}/regmod/allow/{{$u.hash}}" title="{{$approve}}"><i class="fa fa-thumbs-up" aria-hidden="true"></i></a>
							<a href="{{$baseurl}}/regmod/deny/{{$u.hash}}" title="{{$deny}}"><i class="fa fa-thumbs-down" aria-hidden="true"></i></a>
						</td>
					</tr>
					<tr class="details">
						<td></td>
						<th>{{$pendingnotetext}}</th>
						<td colspan="4">{{$u.note}}</td>
					</tr>
				{{/foreach}}
					</tbody>
				</table>
				<div class="panel-footer">
					<div class="row">
						<div class="col-xs-3">
							<div class="btn-group" role="group">
								<button type="button" class="btn btn-default selectall" data-select-all="pending_ckbx"><i class="fa fa-check-square-o" aria-hidden="true"></i></button>
								<button type="button" class="btn btn-default selectnone" data-select-none="pending_ckbx"><i class="fa fa-square-o" aria-hidden="true"></i></button>
							</div>
						</div>
						<div class="col-xs-9">
							<button type="submit" name="page_users_deny" class="btn btn-primary"><i class="fa fa-thumbs-down" aria-hidden="true"></i> {{$deny}}</button>
							<button type="submit" name="page_users_approve" class="btn btn-warinig"><i class="fa fa-thumbs-up" aria-hidden="true"></i> {{$approve}}</button>
						</div>
					</div>
				</div>
			{{else}}
				<div class="panel-body text-center text-muted">{{$no_pending}}</div>
			{{/if}}
		</div>

<!--
	**
	*
	*		USERS Table
	*
	**
-->
	<div class="panel panel-default">
		<div class="panel-heading"><h3 class="panel-title">{{$h_users}}</h3></div>
		{{if $users}}

			<table id="users" class="table table-hover">
				<thead>
				<tr>
					<th></th>
					<th></th>
					{{foreach $th_users as $k=>$th}}
					{{if $k < 2 || $order_users == $th.1 || ($k==5 && !in_array($order_users,[$th_users.2.1, $th_users.3.1, $th_users.4.1])) }}
					<th>
						<a href="{{$baseurl}}/admin/users/?o={{if $order_direction_users == "+"}}-{{/if}}{{$th.1}}">
							{{if $order_users == $th.1}}
								{{if $order_direction_users == "+"}}
								&#8595;
								{{else}}
								&#8593;
								{{/if}}
							{{else}}
								&#8597;
							{{/if}}
						{{$th.0}}</a>
					</th>
					{{/if}}
					{{/foreach}}
					<th></th>
				</tr>
				</thead>
				<tbody>
				{{foreach $users as $u}}
					<tr id="user-{{$u.uid}}">
						<td>
						{{if $u.is_deletable}}
							<input type="checkbox" class="users_ckbx" id="id_user_{{$u.uid}}" name="user[]" value="{{$u.uid}}"/>
						{{else}}
							&nbsp;
						{{/if}}
						</td>
						<td><img class="icon" src="{{$u.micro}}" title="{{$u.nickname}}"></td>
						<td><a href="{{$u.url}}" title="{{$u.nickname}}"> {{$u.name}}</a></td>
						<td>{{$u.email}}</td>
						{{if $order_users == $th_users.2.1}}
						<td>{{$u.register_date}}</td>
						{{/if}}

						{{if $order_users == $th_users.3.1}}
						<td>{{$u.login_date}}</td>
						{{/if}}

						{{if $order_users == $th_users.4.1}}
						<td>{{$u.lastitem_date}}</td>
						{{/if}}

						{{if !in_array($order_users,[$th_users.2.1, $th_users.3.1, $th_users.4.1]) }}
						<td>{{$u.page_flags}} {{if $u.is_admin}}({{$siteadmin}}){{/if}} {{if $u.account_expired}}({{$accountexpired}}){{/if}}</td>
						{{/if}}
						<td class="text-right">
							<button type="button" class="btn-link" onclick="return details({{$u.uid}})"><span class="caret"></span></button>
						</td>
					</tr>
					<tr id="user-{{$u.uid}}-detail" class="hidden details">
						<td>&nbsp;</td>
						<td colspan="4">
							{{if $order_users != $th_users.2.1}}
								<p><a href="{{$baseurl}}/admin/users/?o={{if $order_direction_users == "+"}}-{{/if}}{{$th_users.2.1}}">
									&#8597; {{$th_users.2.0}}</a> : {{$u.register_date}}</p>
							{{/if}}

							{{if $order_users != $th_users.3.1}}
								<p><a href="{{$baseurl}}/admin/users/?o={{if $order_direction_users == "+"}}-{{/if}}{{$th_users.3.1}}">
										&#8597; {{$th_users.3.0}}</a> : {{$u.login_date}}</p>
							{{/if}}

							{{if $order_users != $th_users.4.1}}
								<p><a href="{{$baseurl}}/admin/users/?o={{if $order_direction_users == "+"}}-{{/if}}{{$th_users.4.1}}">
										&#8597; {{$th_users.4.0}}</a> : {{$u.lastitem_date}}</p>
							{{/if}}

							{{if in_array($order_users,[$th_users.2.1, $th_users.3.1, $th_users.4.1]) }}
								<p><a href="{{$baseurl}}/admin/users/?o={{if $order_direction_users == "+"}}-{{/if}}{{$th_users.5.1}}">
										&#8597; {{$th_users.5.0}}</a> : {{$u.page_flags}} {{if $u.is_admin}}({{$siteadmin}}){{/if}} {{if $u.account_expired}}({{$accountexpired}}){{/if}}</p>
							{{/if}}

						</td>
						<td class="text-right">
							{{if $u.is_deletable}}
								<a href="{{$baseurl}}/admin/users/block/{{$u.uid}}?t={{$form_security_token}}" title="{{if $u.blocked}}{{$unblock}}{{else}}{{$block}}{{/if}}">
									{{if $u.blocked==0}}
									<i class="fa fa-ban" aria-hidden="true"></i>
									{{else}}
									<i class="fa fa-circle-o" aria-hidden="true"></i>
									{{/if}}
								</a>
								<a href="{{$baseurl}}/admin/users/delete/{{$u.uid}}?t={{$form_security_token}}" title="{{$delete}}" onclick="return confirm_delete('{{$confirm_delete}}','{{$u.name}}')"><i class="fa fa-trash" aria-hidden="true"></i></a>
							{{else}}
								&nbsp;
							{{/if}}
						</td>
					</tr>
				{{/foreach}}
				</tbody>
			</table>
			<div class="panel-footer">
				<div class="row">
					<div class="col-xs-3">
						<div class="btn-group" role="group">
							<button type="button" class="btn btn-default selectall" data-select-all="users_ckbx"><i class="fa fa-check-square-o" aria-hidden="true"></i></button>
							<button type="button" class="btn btn-default selectnone" data-select-none="users_ckbx"><i class="fa fa-square-o" aria-hidden="true"></i></button>
						</div>
					</div>
					<div class="col-xs-9 text-right">
							<button type="submit" name="page_users_block" class="btn btn-warning">	<i class="fa fa-ban" aria-hidden="true"></i> {{$block}} / <i class="fa fa-circle-o" aria-hidden="true"></i> {{$unblock}}</button>
							<button type="submit" name="page_users_delete" class="btn btn-danger" onclick="return confirm_delete('{{$confirm_delete_multi}}')"><i class="fa fa-trash" aria-hidden="true"></i> {{$delete}}</button>
					</div>
				</div>
			</div>
		{{else}}
			<div class="panel-body text-center bg-danger">NO USERS?!?</div>
		{{/if}}
		</div>



	</form>





<!--
	**
	*
	*		DELETED Users table
	*
	**
-->
	{{if $deleted}}
	<div class="panel panel-default">
		<div class="panel-heading"><h3 class="panel-title">{{$h_deleted}}</h3></div>
		<table id="deleted" class="table table-hover">
			<thead>
			<tr>
				<th></th>
				{{foreach $th_deleted as $k=>$th}}
					{{if in_array($k,[0,1,5])}}
					<th>{{$th}}</th>
					{{/if}}
				{{/foreach}}
			</tr>
			</thead>
			<tbody>
			{{foreach $deleted as $u}}
				<tr>
					<td><img class="icon" src="{{$u.micro}}" title="{{$u.nickname}}"></td>
					<td><a href="{{$u.url}}" title="{{$u.nickname}}" >{{$u.name}}</a></td>
					<td>{{$u.email}}</td>
					<td>{{$u.deleted}}</td>
				</tr>
			{{/foreach}}
			</tbody>
		</table>
	</div>
{{/if}}



<!--
	**
	*
	*		NEW USER Form
	*
	**
-->
	<form action="{{$baseurl}}/admin/users" method="post">
		<input type="hidden" name="form_security_token" value="{{$form_security_token}}">

		<div class="panel panel-default">
			<div class="panel-heading"><h3 class="panel-title">{{$h_newuser}}</h3></div>
			<div class="panel-body">
				{{include file="field_input.tpl" field=$newusername}}
				{{include file="field_input.tpl" field=$newusernickname}}
				{{include file="field_input.tpl" field=$newuseremail}}
			</div>
			<div class="panel-footer text-right">
				<button type="submit" class="btn btn-primary">{{$submit}}</button>
			</form>
		</div>
	</form>

</div>
