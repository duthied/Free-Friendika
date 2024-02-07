
<center>
<form action="{{$confirm_url}}" id="confirm-form" method="{{$method}}">

	<h3 id="confirm-message">{{$l10n.message}}</h3>

	<button class="confirm-button" id="confirm-submit-button" type="submit" name="{{$confirm_name}}" value="{{$confirm_value}}">{{$l10n.confirm}}</button>
	<button class="confirm-button" id="confirm-cancel-button" type="submit" name="canceled" value="{{$l10n.cancel}}">{{$l10n.cancel}}</button>

</form>
</center>

