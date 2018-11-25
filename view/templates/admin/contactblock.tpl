<script>
	function selectall(cls) {
		$('.' + cls).prop('checked', true);
		return false;
	}
	function selectnone(cls) {
		$('.' + cls).prop('checked', false);
		return false;
	}
</script>
<div id="adminpage">
	<h1>{{$title}} - {{$page}}</h1>
	<p>{{$description}}</p>
	<form action="{{$baseurl}}/admin/contactblock" method="post">
        <input type="hidden" name="form_security_token" value="{{$form_security_token}}">

		<h3>{{$h_contacts}}</h3>
	{{if $contacts}}
		<table id="contactblock">
			<thead>
				<tr>
					<th></th>
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
					<td class="checkbox"><input type="checkbox" class="contacts_ckbx" id="id_contact_{{$contact.id}}" name="contacts[]" value="{{$contact.id}}"/></td>
					<td><img class="icon" src="{{$contact.micro}}" alt="{{$contact.nickname|escape}}" title="{{$contact.nickname|escape}}"></td>
					<td class="name">{{$contact.name}}</td>
					<td class="addr">{{$contact.addr}}</td>
					<td class="addr"><a href="{{$contact.url}}" title="{{$contact.nickname|escape}}" >{{$contact.url}}</a></td>
				</tr>
				{{/foreach}}
			</tbody>
		</table>
		<p><a href="#" onclick="return selectall('contacts_ckbx');">{{$select_all}}</a> | <a href="#" onclick="return selectnone('contacts_ckbx');">{{$select_none}}</a></p>
		{{$paginate}}
		<div class="submit"><input type="submit" name="page_contactblock_unblock" value="{{$unblock|escape:'html'}}" /></div>
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
		<div class="submit"><input type="submit" name="page_contactblock_block" value="{{$submit|escape:'html'}}" /></div>
	</form>
</div>
