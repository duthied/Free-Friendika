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

	<input type="submit" name="submit" value="{{$lbl_submit}}" />

</form>
