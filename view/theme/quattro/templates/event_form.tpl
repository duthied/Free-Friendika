
<h3>{{$title}}</h3>

<p>{{$desc}}</p>

<form id="event-edit-form" action="{{$post}}" method="post" >

	<input type="hidden" name="event_id" value="{{$eid}}" />
	<input type="hidden" name="cid" value="{{$cid}}" />
	<input type="hidden" name="uri" value="{{$uri}}" />
	<input type="hidden" name="preview" id="event-edit-preview" value="0" />

	{{include file="field_custom.tpl" field=array('start_text', $s_text, $s_dsel, "")}}
	{{include file="field_custom.tpl" field=array('finish_text', $f_text, $f_dsel, "")}}

	{{include file="field_checkbox.tpl" field=array('nofinish', $n_text, $n_checked, "")}}
	{{include file="field_checkbox.tpl" field=array('adjust', $a_text, $a_checked, "")}}
	<hr>
	{{include file="field_input.tpl" field=array('summary', $t_text, $t_orig, "")}}
	{{include file="field_textarea.tpl" field=array('desc', $d_text, $d_orig, "")}}

	{{include file="field_textarea.tpl" field=array('location', $l_text, $l_orig, "")}}
	<hr>

	<div class='field checkbox' id='div_id_share'>
		<label for='id_share'>{{$sh_text}}</label>
		<input type="checkbox" name='share' id='id_share' aria-describedby='share_tip' value="1" {{$sh_checked}}>
		
	</div>

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
