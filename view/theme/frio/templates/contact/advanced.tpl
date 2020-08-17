
<div id="contact-advanced" class="generic-page-wrapper">
	{{include file="section_title.tpl"}}

	{{$tab_str nofilter}}

	<div class="contact-advanced-error-message">{{$warning nofilter}}</div><br>
	<div class="contact-advanced-return">
		{{$info nofilter}}<br>
		<!-- <a href="{{$returnaddr}}">{{$return}}</a> -->
	</div>
	<br />

	<form id="contact-advanced-form" action="contact/{{$contact_id}}/advanced" method="post" >

		<!-- <h4>{{$contact_name}}</h4> -->

		<div id="contact-update-profile-wrapper">
		{{if $update_profile}}
			<span id="contact-update-profile-now" class="button"><a href="contact/{{$contact_id}}/updateprofile" >{{$udprofilenow}}</a></span>
		{{/if}}
		</div>

		{{include file="field_input.tpl" field=$name}}

		{{include file="field_input.tpl" field=$nick}}

		{{include file="field_input.tpl" field=$attag}}

		{{include file="field_input.tpl" field=$url}}

		{{include file="field_input.tpl" field=$alias}}

		{{include file="field_input.tpl" field=$request}}

		{{include file="field_input.tpl" field=$confirm}}

		{{include file="field_input.tpl" field=$notify}}

		{{include file="field_input.tpl" field=$poll}}

		{{include file="field_input.tpl" field=$photo}}


		{{if $allow_remote_self eq 1}}
		<h4>{{$label_remote_self}}</h4>
		{{include file="field_select.tpl" field=$remote_self}}
		{{/if}}

		<div class="pull-right settings-submit-wrapper" >
			<button type="submit" name="submit" class="btn btn-primary" value="{{$lbl_submit}}">{{$lbl_submit}}</button>
		</div>
		<div class="clear"></div>

	</form>
</div>
