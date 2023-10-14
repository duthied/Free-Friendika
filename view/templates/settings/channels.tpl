<div class="generic-page-wrapper">
	<h1>{{$l10n.title}}</h1>
	<p>{{$l10n.intro}}</p>
	<h2>{{$l10n.addtitle}}</h2>
	<form action="{{$baseurl}}/settings/channels" method="post">
	<input type="hidden" name="form_security_token" value="{{$form_security_token}}">
	{{include file="field_input.tpl" field=$label}}
	{{include file="field_input.tpl" field=$description}}
	{{include file="field_input.tpl" field=$access_key}}
	{{include file="field_select.tpl" field=$circle}}
	{{include file="field_textarea.tpl" field=$include_tags}}
	{{include file="field_textarea.tpl" field=$exclude_tags}}
	{{include file="field_textarea.tpl" field=$text_search}}
	{{include file="field_checkbox.tpl" field=$image}}
	{{include file="field_checkbox.tpl" field=$video}}
	{{include file="field_checkbox.tpl" field=$audio}}
		<div class="submit">
			<button type="submit" class="btn btn-primary" name="add_channel" value="{{$l10n.addsubmit}}">{{$l10n.addsubmit}}</button>
		</div>
	</form>

	{{if $entries}}
	<h2>{{$l10n.currenttitle}}</h2>
	<form action="{{$baseurl}}/settings/channels" method="post">
		<input type="hidden" name="form_security_token" value="{{$form_security_token}}">
		{{foreach $entries as $e}}
			{{include file="field_input.tpl" field=$e.label}}
			{{include file="field_input.tpl" field=$e.description}}
			{{include file="field_input.tpl" field=$e.access_key}}
			{{include file="field_select.tpl" field=$e.circle}}
			{{include file="field_textarea.tpl" field=$e.include_tags}}
			{{include file="field_textarea.tpl" field=$e.exclude_tags}}
			{{include file="field_textarea.tpl" field=$e.text_search}}
			{{include file="field_checkbox.tpl" field=$e.image}}
			{{include file="field_checkbox.tpl" field=$e.video}}
			{{include file="field_checkbox.tpl" field=$e.audio}}
			{{include file="field_checkbox.tpl" field=$e.delete}}
			<hr>
		{{/foreach}}
		<div class="submit">
			<button type="submit" class="btn btn-primary" name="edit_channel" value="{{$l10n.savechanges}}">{{$l10n.savechanges}}</button>
		</div>
		{{/if}}
	</form>
</div>
