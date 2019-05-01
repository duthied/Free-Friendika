
<form action="{{$confirm_url}}" id="confirm-form" method="{{$method}}" class="generic-page-wrapper">
	<div id="confirm-message">{{$message}}</div>
	{{foreach $extra_inputs as $input}}
	<input type="hidden" name="{{$input.name}}" value="{{$input.value}}" />
	{{/foreach}}

	<div class="form-group pull-right settings-submit-wrapper" >
		<button type="submit" name="{{$confirm_name}}" id="confirm-submit-button" class="btn btn-primary confirm-button" value="{{$confirm}}">{{$confirm}}</button>
		<button type="submit" name="canceled" id="confirm-cancel-button" class="btn confirm-button" data-dismiss="modal">{{$cancel}}</button>
	</div>
</form>
