
<form method="post" action="{{$action}}">
    <input type="hidden" name="form_security_token" value="{{$form_security_token}}">
	{{$form nofilter}}
</form>
