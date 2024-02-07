<div class="generic-page-wrapper">
	<h1>{{$title}}</h1>
	<div>{{$message nofilter}}</div>

	<form action="" method="post">
		<input type="hidden" name="form_security_token" value="{{$form_security_token}}">

		<div class="form-group settings-submit-wrapper">
			<button type="submit" name="action" id="trust-submit-button" class="btn btn-primary confirm-button" value="sign_out">{{$sign_out_label}}</button>
			<button type="submit" name="action" id="dont-trust-submit-button" class="btn confirm-button" value="cancel">{{$cancel_label}}</button>
			<button type="submit" name="action" id="not-now-submit-button" class="right-aligned btn confirm-button" value="trust_and_sign_out">{{$trust_and_sign_out_label}}</button>
		</div>
	</form>
</div>
