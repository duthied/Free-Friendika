<div class="generic-page-wrapper">
	<h1>{{$l10n.ptitle}}</h1>

	<div id="settings-nick-wrapper">
		<div id="settings-nickname-desc" class="info-message">{{$l10n.desc}}</div>
	</div>
	<div id="settings-nick-end"></div>

	<div id="settings-form">
		<form class="settings-content-block" action="security/password_too_long" method="post" autocomplete="off" enctype="multipart/form-data">
			<input type="hidden" name="form_security_token" value="{{$form_security_token}}">
			<input type="hidden" name="return_url" value="{{$return_url}}">
			{{include file="field_password.tpl" field=$password_current}}
			{{include file="field_password.tpl" field=$password}}
			{{include file="field_password.tpl" field=$password_confirm}}

			<div class="settings-submit-wrapper">
				<button type="submit" name="password-submit" class="btn btn-primary" value="{{$l10n.submit}}">{{$l10n.submit}}</button>
			</div>
		</form>
	</div>
</div>
