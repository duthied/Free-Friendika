<div class="generic-page-wrapper">

	<form action="register" method="post" id="register-form">

		<input type="hidden" name="photo" value="{{$photo}}" />
		<input type="hidden" name="form_security_token" value="{{$form_security_token}}">

		<h3 class="heading">{{$regtitle}}</h3>

		{{if $registertext != ""}}<div class="error-message">{{$registertext nofilter}}</div>{{/if}}

		{{if $explicit_content}} <p id="register-explicid-content">{{$explicit_content_note}}</p> {{/if}}

		{{if $oidlabel}}
		<div id="register-openid-wrapper" class="form-group">
			<label for="register-openid" id="label-register-openid" >{{$oidlabel}}</label>
			<input type="text" maxlength="60" size="32" name="openid_url" class="openid form-control" id="register-openid" value="{{$openid}}">
			<span class="help-block" id="openid_url_tip">{{$fillwith}}&nbsp;{{$fillext}}</span>
		</div>
		<div id="register-openid-end" ></div>
		{{/if}}

		{{if $invitations}}
		<div id="register-invite-wrapper" class="form-group">
			<label for="register-invite" id="label-register-invite" >{{$invite_label}}</label>
			<input type="text" maxlength="60" size="32" name="invite_id" id="register-invite" class="form-control" value="{{$invite_id}}">
			<span class="help-block" id="invite_id_tip">{{$invite_desc nofilter}}</span>
		</div>
		<div id="register-name-end" ></div>
		{{/if}}

		<div id="register-name-wrapper" class="form-group">
			<label for="register-name" id="label-register-name" >{{$namelabel}}</label>
			<input type="text" maxlength="60" size="32" name="username" id="register-name" class="form-control" value="{{$username}}">
		</div>
		<div id="register-name-end" ></div>


		<div id="register-email-wrapper" class="form-group">
			<label for="register-email" id="label-register-email" >{{$addrlabel}}</label>
			<input type="text" maxlength="60" size="32" name="email" id="register-email" class="form-control" value="{{$email}}">
		</div>
		<div id="register-email-end" ></div>

		{{if $ask_password}}
		{{include file="field_password.tpl" field=$password1}}
		{{include file="field_password.tpl" field=$password2}}
		{{/if}}

		<div id="register-nickname-wrapper" class="form-group">
			<label for="register-nickname" id="label-register-nickname" >{{$nicklabel}}</label>
			<input type="text" maxlength="60" size="32" name="nickname" id="register-nickname" class="form-control" value="{{$nickname}}">
			<span class="help-block" id="nickname_tip">{{$nickdesc nofilter}}</span>
		</div>
		<div id="register-nickname-end" ></div>

		{{if $permonly}}
		{{include file="field_textarea.tpl" field=$permonlybox}}
		<input type="input" id="registertarpit" style="display: none;" placeholder="Don't enter anything here"/>
		{{/if}}

		{{$publish nofilter}}

		{{if $showtoslink}}
		<p><a href="{{$baseurl}}/tos">{{$tostext}}</a></p>
		{{/if}}
		{{if $showprivstatement}}
		<h4>{{$privstatement.0}}</h4>
		{{for $i=1 to 3}}
		<p>{{$privstatement[$i] nofilter}}</p>
		{{/for}}
		{{/if}}

		<div id="register-submit-wrapper" class="pull-right">
			<button type="submit" name="submit" id="register-submit-button" class="btn btn-primary" value="{{$regbutt}}">{{$regbutt}}</button>
		</div>
		<div id="register-submit-end" class="clear"></div>

		<h3>{{$importh}}</h3>
		<div id ="import-profile">
			<a href="uimport">{{$importt}}</a>
		</div>
	</form>
</div>
