{{include file="section_title.tpl"}}

{{$tab_str}}

<div class="crepair-error-message">{{$warning}}</div><br>
<div class="crepair-return">
	{{$info}}<br>
	<!-- <a href="{{$returnaddr}}">{{$return}}</a> -->
</div>
<br />

<form id="crepair-form" action="crepair/{{$contact_id}}" method="post" >

	<!-- <h4>{{$contact_name}}</h4> -->

	<div id="contact-update-profile-wrapper">
	{{if $update_profile}}
		<span id="contact-update-profile-now" class="button"><a href="contacts/{{$contact_id}}/updateprofile" >{{$udprofilenow}}</a></span>
	{{/if}}
	</div>

	{{include file="field_input.tpl" field=$name}}

	{{include file="field_input.tpl" field=$nick}}

	{{include file="field_input.tpl" field=$attag}}

	{{include file="field_input.tpl" field=$url}}

	{{include file="field_input.tpl" field=$request}}

	{{include file="field_input.tpl" field=$confirm}}

	{{include file="field_input.tpl" field=$notify}}

	{{include file="field_input.tpl" field=$poll}}

	{{include file="field_input.tpl" field=$photo}}


	{{if $allow_remote_self eq 1}}
	<h4>{{$label_remote_self}}</h4>
	{{include file="field_select.tpl" field=$remote_self}}
	{{/if}}

	<input type="submit" name="submit" value="{{$lbl_submit|escape:'html'}}" />

</form>
