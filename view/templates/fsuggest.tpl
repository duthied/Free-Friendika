<div class="generic-page-wrapper">
	<h2>{{$fsuggest_title}}</h2>
	<form id="fsuggest-form" action="fsuggest/{{$contact_id}}" method="post">
		{{include file="field_select.tpl" field=$fsuggest_select}}
		<div id="fsuggest-submit-wrapper">
			<input id="fsuggest-submit" type="submit" name="submit" value="{{$submit}}" />
		</div>
	</form>
</div>
