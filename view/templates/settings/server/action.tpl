<div id="settings-server" class="generic-page-wrapper">
	<h1>{{$l10n.title}}</h1>

	<form action="{{$action}}" method="POST">
		<input type="hidden" name="form_security_token" value="{{$form_security_token}}">
		<input type="hidden" name="redirect_url" value="settings/server">

		<p>{{$l10n.action}}</p>

		{{if $l10n.desc}}
		<p>{{$l10n.desc}}</p>
        {{/if}}

		<table>
			<tr>
				<th>{{$l10n.siteName}}</th>
				<td>{{$GServer->siteName}}</td>
			</tr>
			<tr>
				<th>{{$l10n.siteUrl}}</th>
				<td><a href="{{$GServer->url}}">{{$GServer->url}}</a></td>
			</tr>
		</table>

		<p><button type="submit" class="btn btn-primary">{{$l10n.submit}}</button></p>
	</form>
</div>
