
<h3>{{$title}}</h3>

<p>{{$desc nofilter}}</p>

<form id="event-edit-form" action="{{$post}}" method="post">

	<input type="hidden" name="event_id" value="{{$eid}}" />
	<input type="hidden" name="cid" value="{{$cid}}" />
	<input type="hidden" name="uri" value="{{$uri}}" />
	<input type="hidden" name="preview" id="event-edit-preview" value="0" />

	{{$s_dsel nofilter}}

	{{$f_dsel nofilter}}

	{{include file="field_checkbox.tpl" field=$nofinish}}
	<hr>
	{{include file="field_input.tpl" field=$summary}}
	{{include file="field_textarea.tpl" field=array('desc', $d_text, $d_orig, "")}}

	{{include file="field_textarea.tpl" field=array('location', $l_text, $l_orig, "")}}
	<hr>

	{{if ! $eid}}
	{{include file="field_checkbox.tpl" field=$share}}
	{{/if}}

	{{$acl nofilter}}

	<div class="settings-submit-wrapper">
		<input id="event-edit-preview" type="submit" name="preview" value="{{$preview}}" onclick="doEventPreview(); return false;" />
		<input id="event-submit" type="submit" name="submit" value="{{$submit}}" />
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
