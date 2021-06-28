<div class="generic-page-wrapper">
	{{* include the title template for the settings title *}}
	{{include file="section_title.tpl" title=$title}}

{{foreach $addon_settings_forms as $addon_settings_form}}

	<form action="settings/addon" method="post" autocomplete="off">
		<input type="hidden" name="form_security_token" value="{{$form_security_token}}">
		{{$addon_settings_form nofilter}}
	</form>

{{foreachelse}}

	<div class="alert alert-info" role="alert">{{$no_addon_settings_configured}}</div>

{{/foreach}}

</div>