<div class="generic-page-wrapper">
	<h1>{{$title}}</h1>
	<div>{{$message nofilter}}</div>

	<form action="" method="post">
		<input type="hidden" name="form_security_token" value="{{$form_security_token}}">

		{{include file="field_input.tpl" field=$recovery_code}}

		<div class="form-group settings-submit-wrapper">
			<button type="submit" name="action" id="confirm-submit-button" class="btn btn-primary confirm-button" value="recover">{{$recovery_label}}</button>
		</div>
	</form>
</div>
