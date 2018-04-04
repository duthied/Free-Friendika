<script type="text/javascript" src="view/theme/frio/js/mod_admin.js"></script>
<div id="adminpage">
	<h1>{{$title}} - {{$page}}</h1>
	<p>{{$description}}</p>
	<form action="{{$baseurl}}/admin/contactblock" method="post">
        <input type="hidden" name="form_security_token" value="{{$form_security_token}}">

		<h3>{{$h_contacts}}</h3>
	{{if $contacts}}
		<table id="contactblock" class="table table-condensed table-striped">
			<thead>
				<tr>
					<th><input type="checkbox" class="select contacts_ckbx" data-select-class="contacts_ckbx" data-select-all="{{$select_all}}"  data-select-none="{{$select_none}}" title="{{$select_all}}"/></th>
						{{foreach $th_contacts as $th}}
					<th>
						{{$th}}
					</th>
					{{/foreach}}
					<th></th>
				</tr>
			</thead>
			<tbody>
				{{foreach $contacts as $contact}}
				<tr>
					<td><input type="checkbox" class="contacts_ckbx" id="id_contact_{{$contact.id}}" name="contacts[]" value="{{$contact.id}}"/></td>
					<td><img class="icon" src="{{$contact.micro}}" alt="{{$contact.nickname}}" title="{{$contact.addr}}"></td>
					<td class="name">{{$contact.name}}</td>
					<td class="addr" colspan="2"><a href="{{$contact.url}}" title="{{$contact.addr}}" >{{$contact.url}}</a></td>
				</tr>
				{{/foreach}}
			</tbody>
			<tfoot>
				<tr>
					<td><input type="checkbox" class="select contacts_ckbx" data-select-class="contacts_ckbx" data-select-all="{{$select_all}}"  data-select-none="{{$select_none}}" title="{{$select_all}}"/></td>
					<td colspan="3">
						{{$total_contacts}}
					</td>
				</tr>
			</tfoot>
		</table>
		<div class="submit"><button type="submit" class="btn btn-small btn-default" name="page_contactblock_unblock" value="1">{{$unblock|escape:'html'}}</button></div>
		{{$paginate}}
	{{else}}
		<p>{{$no_data|escape:'html'}}</p>
	{{/if}}
	</form>

	<h3>{{$h_newblock}}</h3>
	<form action="{{$baseurl}}/admin/contactblock" method="post">
		<input type="hidden" name="form_security_token" value="{{$form_security_token}}">
		<table id="contactblock">
			<tbody>
				<tr>
					<td>{{include file="field_input.tpl" field=$contacturl}}</td>
				</tr>
			</tbody>
		</table>
		<div class="submit"><button type="submit" class="btn btn-primary" name="page_contactblock_block" value="1">{{$submit|escape:'html'}}</button></div>
	</form>
</div>
