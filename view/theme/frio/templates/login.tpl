

<form id="login-form" action="{{$dest_url}}" role="form" method="post" >
<div id="login-group" role="group" aria-labelledby="login-head">
	<input type="hidden" name="auth-params" value="login" />

	<div id="login-head" class="sr-only">{{$login}}</div>

	<div id="login_standard">
		{{include file="field_input.tpl" field=$lname}}
		{{include file="field_password.tpl" field=$lpassword}}
	</div>
	
	{{if $openid}}
	<div id="login_openid">
		{{include file="field_openid.tpl" field=$lopenid}}
	</div>
	{{/if}}

	{{include file="field_checkbox.tpl" field=$lremember}}

	<div id="login-extra-links" class="list-unstyled">
		{{if $register}}<a href="register" title="{{$register.title|escape:'html'}}" id="register-link">{{$register.desc}}</a>{{/if}}
		<a href="lostpass" title="{{$lostpass|escape:'html'}}" id="lost-password-link" class="pull-right">{{$lostlink}}</a>
	</div>

	<div id="login-submit-wrapper" class="pull-right" >
		<button type="submit" name="submit" id="login-submit-button" class="btn btn-primary" value="{{$login|escape:'html'}}">{{$login|escape:'html'}}</button>
	</div>
	<div class="clear"></div>
	
	{{foreach $hiddens as $k=>$v}}
		<input type="hidden" name="{{$k}}" value="{{$v|escape:'html'}}" />
	{{/foreach}}
	
</div>
</form>


<script type="text/javascript"> $(document).ready(function() { $("#id_{{$lname.0}}").focus();} );</script>
