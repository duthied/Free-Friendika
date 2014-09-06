
<h1>{{$title}}</h1>


<form action="settings/features" method="post" autocomplete="off">
<input type='hidden' name='form_security_token' value='{{$form_security_token}}'>

{{foreach $features as $f}}
<h3 class="settings-heading">{{$f.0}}</h3>
<div class="settings-block">

{{foreach $f.1 as $fcat}}
	{{include file="field_yesno.tpl" field=$fcat}}
{{/foreach}}
<div class="settings-submit-wrapper" >
<input type="submit" name="submit" class="settings-features-submit" value="{{$submit}}" />
</div>
</div>
{{/foreach}}

</form>

