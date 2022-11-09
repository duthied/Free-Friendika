<script>
	function confirm_delete(uname){
		return confirm("{{$l10n.confirm_delete}}".format(uname));
	}
</script>
<div id="adminpage">
	<h1>{{$l10n.title}} - {{$l10n.page}}</h1>
	<p>{{$l10n.intro}}</p>
	<p>{{$l10n.public nofilter}}</p>

	<h2>{{$l10n.importtitle}}</h2>
    {{$l10n.download nofilter}}

	<form action="{{$baseurl}}/moderation/blocklist/server/import" method="post" enctype="multipart/form-data">
		<input type="hidden" name="form_security_token" value="{{$form_security_token_import}}">
        {{include file="field_input.tpl" field=$listfile}}
		<div class="submit">
			<button type="submit" class="btn btn-primary" name="page_blocklist_upload">{{$l10n.importsubmit}}</button>
		</div>
	</form>

	<h2>{{$l10n.addtitle}}</h2>
    {{$l10n.syntax nofilter}}
	<form action="{{$baseurl}}/moderation/blocklist/server/add" method="get">
		{{include file="field_input.tpl" field=$newdomain}}
		<div class="submit">
			<button type="submit" class="btn btn-primary">{{$l10n.addsubmit}}</button>
		</div>
	</form>

	{{if $entries}}
	<h2>{{$l10n.currenttitle}}</h2>
	<form action="{{$baseurl}}/moderation/blocklist/server" method="post">
		<input type="hidden" name="form_security_token" value="{{$form_security_token}}">
		{{foreach $entries as $e}}
			{{include file="field_input.tpl" field=$e.domain}}
			{{include file="field_input.tpl" field=$e.reason}}
			{{include file="field_checkbox.tpl" field=$e.delete}}
		{{/foreach}}
		<div class="submit">
			<button type="submit" class="btn btn-primary" name="page_blocklist_edit" value="{{$l10n.savechanges}}">{{$l10n.savechanges}}</button>
		</div>
		{{/if}}
	</form>
</div>
