<div id="delegation" class="generic-page-wrapper">
<h1>{{$header}}</h1>

{{if !$is_child_user}}
<h2>{{$account_header}}</h2>
<div id="add-account-desc" class="add-account-desc"><p>{{$account_desc}}</p></div>
<p><a href='register'>{{$add_account}}</a></p>
{{/if}}

{{if $parent_user}}
<h2>{{$parent_header}}</h2>
<div id="delegate-parent-desc" class="delegate-parent-desc"><p>{{$parent_desc}}</p></div>
<div id="delegate-parent" class="delegate-parent">
	<form action="settings/delegation" method="post">
		<input type='hidden' name='form_security_token' value='{{$form_security_token}}'>
        {{include file="field_select.tpl" field=$parent_user}}
        {{include file="field_password.tpl" field=$parent_password}}
		<div class="submit"><input type="submit" name="delegate" value="{{$submit}}"/></div>
	</form>
</div>
{{/if}}

<h2>{{$delegates_header}}</h2>

<div id="delegate-desc" class="delegate-desc"><p>{{$desc nofilter}}</p></div>

<h3>{{$head_delegates}}</h3>
{{if $delegates}}
    {{foreach $delegates as $x}}
<div class="contact-block-div">
	<a class="contact-block-link" href="settings/delegation/remove/{{$x.uid}}">
		<img class="contact-block-img" src="photo/thumb/{{$x.uid}}" title="{{$x.username}} ({{$x.nickname}})">
	</a>
</div>
    {{/foreach}}
<div class="clear"></div>
{{else}}
    <p>{{$none}}</p>
{{/if}}

<h3>{{$head_potentials}}</h3>
{{if $potentials}}
    {{foreach $potentials as $x}}
<div class="contact-block-div">
	<a class="contact-block-link" href="settings/delegation/add/{{$x.uid}}">
		<img class="contact-block-img" src="photo/thumb/{{$x.uid}}" title="{{$x.username}} ({{$x.nickname}})">
	</a>
</div>
    {{/foreach}}
<div class="clear"></div>
{{else}}
    <p>{{$none}}</p>
{{/if}}
</div>
