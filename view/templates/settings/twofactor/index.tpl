<div class="generic-page-wrapper">
	<h1>{{$title}} <a href="help/Two-Factor-Authentication" title="{{$help_label}}" class="btn btn-default btn-sm"><i aria-hidden="true" class="fa fa-question fa-2x"></i></a></h1>
	<div>{{$message nofilter}}</div>
	<h2>{{$status_title}}</h2>
	<p><strong>{{$auth_app_label}}</strong>: {{$app_status}} </p>
{{if $has_secret && $verified}}
	<div>{{$configured_message nofilter}}</div>
{{/if}}
{{if $has_secret && !$verified}}
	<div>{{$not_configured_message nofilter}}</div>
{{/if}}

{{if $has_secret && $verified}}
	<h2>{{$recovery_codes_title}}</h2>
	<p><strong>{{$recovery_codes_remaining}}</strong>: {{$recovery_codes_count}}</p>
	<div>{{$recovery_codes_message nofilter}}</div>
{{/if}}

	<form action="settings/2fa" method="post">
		<h2>{{$action_title}}</h2>
		<input type="hidden" name="form_security_token" value="{{$form_security_token}}">

		{{include file="field_password.tpl" field=$password}}

		<div class="form-group settings-submit-wrapper" >
{{if !$has_secret}}
			<button type="submit" name="action" id="confirm-submit-button" class="btn btn-primary confirm-button" value="enable">{{$enable_label}}</button>
{{else}}
			<button type="submit" name="action" id="confirm-submit-button" class="btn btn-primary confirm-button" value="disable">{{$disable_label}}</button>
{{/if}}
{{if $has_secret && $verified}}
			<button type="submit" name="action" id="confirm-submit-button" class="btn btn-primary confirm-button" value="recovery">{{$recovery_codes_label}}</button>
{{/if}}
{{if $has_secret && !$verified}}
			<button type="submit" name="action" id="confirm-submit-button" class="btn btn-primary confirm-button" value="configure">{{$configure_label}}</button>
{{/if}}
		</div>
	</form>
</div>
