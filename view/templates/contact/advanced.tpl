{{include file="section_title.tpl"}}

{{$tab_str nofilter}}

<form id="contact-advanced-form" action="contact/{{$contact_id}}/advanced" method="post">

	<!-- <h4>{{$contact_name}}</h4> -->

	{{include file="field_input.tpl" field=$name}}

	{{include file="field_input.tpl" field=$nick}}

	{{include file="field_input.tpl" field=$url}}

	{{include file="field_input.tpl" field=$poll}}

	{{include file="field_input.tpl" field=$photo}}

	<input type="submit" name="submit" value="{{$lbl_submit}}" />

</form>
