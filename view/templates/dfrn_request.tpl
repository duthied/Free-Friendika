<h1>{{$header}}</h1>

{{if !$myaddr}}
<p id="dfrn-request-intro">
	{{$page_desc nofilter}}
</p>
<p>
	{{$invite_desc nofilter}}
</p>
{{/if}}

<form action="{{$request}}" method="post">
	<div id="dfrn-request-url-wrapper">
		<label id="dfrn-url-label" for="dfrn-url">{{$your_address}}</label>
{{if $myaddr}}
		{{$myaddr}}
		<input type="hidden" name="dfrn_url" id="dfrn-url" value="{{$myaddr}}">
{{else}}
		<input type="text" name="dfrn_url" id="dfrn-url" size="32" value="{{$myaddr}}">
{{/if}}
		<div id="dfrn-request-url-end"></div>
	</div>

	<p id="dfrn-request-options">
		{{$pls_answer}}
	</p>

	<div id="dfrn-request-info-wrapper">
		{{include file="field_checkbox.tpl" field=$does_know_you}}

		{{include file="field_textarea.tpl" field=$addnote_field}}
	</div>

	<div id="dfrn-request-submit-wrapper">
		<input type="submit" name="submit" id="dfrn-request-submit-button" value="{{$submit}}">
		<input type="submit" name="cancel" id="dfrn-request-cancel-button" value="{{$cancel}}">
	</div>
</form>
