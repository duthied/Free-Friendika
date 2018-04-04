
<h3>{{$title}}</h3>

<p>{{$desc}}</p>

<form id="event-edit-form" action="{{$post}}" method="post" >

	<input type="hidden" name="event_id" value="{{$eid}}" />
	<input type="hidden" name="cid" value="{{$cid}}" />
	<input type="hidden" name="uri" value="{{$uri}}" />
	<input type="hidden" name="preview" id="event-edit-preview" value="0" />

	{{$s_dsel}}

	{{$f_dsel}}

	{{include file="field_checkbox.tpl" field=$nofinish}}

	{{include file="field_checkbox.tpl" field=$adjust}}
	<hr>
	{{include file="field_input.tpl" field=$summary}}
	{{include file="field_textarea.tpl" field=array('desc', $d_text, $d_orig, "")}}

	{{include file="field_textarea.tpl" field=array('location', $l_text, $l_orig, "")}}
	<hr>

	{{if ! $eid}}
	{{include file="field_checkbox.tpl" field=$share}}
	{{/if}}

	{{$acl}}

	<div class="settings-submit-wrapper" >
		<input id="event-edit-preview" type="submit" name="preview" value="{{$preview|escape:'html'}}" onclick="doEventPreview(); return false;" />
		<input id="event-submit" type="submit" name="submit" value="{{$submit|escape:'html'}}" />
	</div>
</form>

<script language="javascript" type="text/javascript">
	$(document).ready(function() {
		$('#id_share').change(function() {

			if ($('#id_share').is(':checked')) {
				$('#acl-wrapper').show();
			}
			else {
				$('#acl-wrapper').hide();
			}
		}).trigger('change');
	});
</script>
