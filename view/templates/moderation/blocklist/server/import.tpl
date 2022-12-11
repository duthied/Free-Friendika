<div id="adminpage">
	<p><a href="{{$baseurl}}/moderation/blocklist/server">{{$l10n.return_list}}</a></p>
	<h1>{{$l10n.title}} - {{$l10n.page}}</h1>
{{if !$blocklist}}
    {{$l10n.download nofilter}}

	<form action="{{$baseurl}}/moderation/blocklist/server/import" method="post" enctype="multipart/form-data">
		<input type="hidden" name="form_security_token" value="{{$form_security_token}}">
        {{include file="field_input.tpl" field=$listfile}}
		<div class="submit">
			<button type="submit" class="btn btn-primary" name="page_blocklist_upload" value="{{$l10n.upload}}">{{$l10n.upload}}</button>
		</div>
	</form>
{{else}}
	<h2>{{$l10n.patterns}}</h2>
	<form action="{{$baseurl}}/moderation/blocklist/server/import" method="post">
		<input type="hidden" name="form_security_token" value="{{$form_security_token}}">
        <input type="hidden" name="blocklist" value="{{$blocklist|json_encode}}">
		<table class="table table-condensed table-striped table-bordered">
			<thead>
				<tr>
					<th>{{$l10n.domain_pattern}}</th>
					<th>{{$l10n.block_reason}}</th>
				</tr>
			</thead>
			<tfoot>
				<tr>
					<td colspan="4">{{$l10n.pattern_count}}</td>
				</tr>
			</tfoot>
			<tbody>
            {{foreach $blocklist as $block}}
				<tr>
					<th>{{$block.domain}}</th>
					<td>{{$block.reason}}</td>
				</tr>
            {{/foreach}}
			</tbody>
		</table>

		<div role="radiogroup" aria-labelledby="mode">
			<label id="mode">{{$l10n.mode}}</label>
            {{include file="field_radio.tpl" field=$mode_append}}
            {{include file="field_radio.tpl" field=$mode_replace}}
		</div>

		<div class="submit">
			<button type="submit" class="btn btn-primary" name="page_blocklist_import" value="{{$l10n.import}}">{{$l10n.import}}</button>
		</div>
	</form>
{{/if}}
</div>
