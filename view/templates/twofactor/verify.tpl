<div class="generic-page-wrapper">
	<h1>{{$title}}</h1>
	<div>{{$message nofilter}}</div>

{{if $errors}}
	<div class="panel panel-danger">
		<div class="panel-heading">{{$errors_label}}</div>
		<ul class="list-group">
	{{foreach $errors as $error}}
			<li class="list-group-item">{{$error}}</li>
	{{/foreach}}
		</ul>
	</div>
{{/if}}

	<form action="" method="post">
		<input type="hidden" name="form_security_token" value="{{$form_security_token}}">

		{{include file="field_input.tpl" field=$verify_code}}

		<div class="form-group settings-submit-wrapper">
			<button type="submit" name="action" id="confirm-submit-button" class="btn btn-primary confirm-button" value="verify">{{$verify_label}}</button>
		</div>
	</form>
	<div>{{$recovery_message nofilter}}</div>
</div>
