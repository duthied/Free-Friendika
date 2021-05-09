<script>
	function confirm_delete(uname){
		return confirm("{{$confirm_delete}}".format(uname));
	}
</script>
<div id="adminpage">
	<h1>{{$title}} - {{$page}}</h1>
	<p>{{$intro}}</p>
	<p>{{$public nofilter}}</p>
	{{$syntax nofilter}}

	<h2>{{$addtitle}}</h2>
	<form action="{{$baseurl}}/admin/blocklist/server" method="post">
		<input type="hidden" name="form_security_token" value="{{$form_security_token}}">
		{{include file="field_input.tpl" field=$newdomain}}
		{{include file="field_input.tpl" field=$newreason}}
		<div class="submit">
			<button type="submit" class="btn btn-primary" name="page_blocklist_save" value="{{$submit}}">{{$submit}}</button>
		</div>
	</form>

	{{if $entries}}
	<h2>{{$currenttitle}}</h2>
	<p>{{$currentintro}}</p>
	<form action="{{$baseurl}}/admin/blocklist/server" method="post">
		<input type="hidden" name="form_security_token" value="{{$form_security_token}}">
		{{foreach $entries as $e}}
			{{include file="field_input.tpl" field=$e.domain}}
			{{include file="field_input.tpl" field=$e.reason}}
			{{include file="field_checkbox.tpl" field=$e.delete}}
		{{/foreach}}
		<div class="submit">
			<button type="submit" class="btn btn-primary" name="page_blocklist_edit" value="{{$savechanges}}">{{$savechanges}}</button>
		</div>
		{{/if}}
	</form>
</div>
