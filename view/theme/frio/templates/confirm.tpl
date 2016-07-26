
<form action="{{$confirm_url}}" id="confirm-form" method="{{$method}}">

	<div id="confirm-message">{{$message}}</div>
	{{foreach $extra_inputs as $input}}
	<input type="hidden" name="{{$input.name}}" value="{{$input.value|escape:'html'}}" />
	{{/foreach}}

	<div class="form-group pull-right settings-submit-wrapper" >
		<button type="submit" name="{{$confirm_name}}" id="confirm-submit-button" class="btn btn-primary confirm-button" value="{{$confirm|escape:'html'}}">{{$confirm|escape:'html'}}</button>
		<button type="submit" name="canceled" id="confirm-cancel-button" class="btn confirm-button" data-dismiss="modal">{{$cancel|escape:'html'}}</button>
	</div>

</form>
