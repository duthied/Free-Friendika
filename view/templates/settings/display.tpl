<h1>{{$ptitle}}</h1>

<form action="settings/display" id="settings-form" method="post" autocomplete="off">
	<input type='hidden' name='form_security_token' value='{{$form_security_token}}'>

	{{include file="field_themeselect.tpl" field=$theme}}
	{{include file="field_input.tpl" field=$itemspage_network}}

	{{* Show the mobile theme selection only if mobile themes are available *}}
	{{if count($mobile_theme.4) > 1}}
		{{include file="field_themeselect.tpl" field=$mobile_theme}}
	{{/if}}

	{{include file="field_input.tpl" field=$itemspage_mobile_network}}
	{{include file="field_input.tpl" field=$ajaxint}}
	{{include file="field_checkbox.tpl" field=$no_auto_update}}
	{{include file="field_checkbox.tpl" field=$nosmile}}
	{{include file="field_checkbox.tpl" field=$infinite_scroll}}
	{{include file="field_checkbox.tpl" field=$no_smart_threading}}
	{{include file="field_checkbox.tpl" field=$hide_dislike}}

	<h2>{{$calendar_title}}</h2>
	{{include file="field_select.tpl" field=$first_day_of_week}}

	<div class="settings-submit-wrapper">
		<input type="submit" name="submit" class="settings-submit" value="{{$submit}}"/>
	</div>

	{{if $theme_config}}
		<h2>{{$stitle}}</h2>
		{{$theme_config nofilter}}
	{{/if}}

</form>
