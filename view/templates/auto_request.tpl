<h1>{{$header}}</h1>

<form action="{{$request}}" method="post">
	<dl>
		<dt>{{$url_label}}</dt>
		<dd><a target="blank" href="{{$zrl}}">{{$url}}</a></dd>
{{if $keywords}}
		<dt>{{$keywords_label}}</dt>
		<dd>{{$keywords}}</dd>
{{/if}}
	</dl>

	<div id="dfrn-request-url-wrapper">
		<label id="dfrn-url-label" for="dfrn-url">{{$your_address}}</label>
		{{$myaddr}}
		<input type="hidden" name="dfrn_url" id="dfrn-url" value="{{$myaddr}}">
		<input type="hidden" name="url" id="url" value="{{$url}}">
		<div id="dfrn-request-url-end"></div>
	</div>

	<div id="dfrn-request-submit-wrapper">
{{if $submit}}
		<input type="submit" name="submit" id="dfrn-request-submit-button" value="{{$submit}}">
{{/if}}
		<input type="submit" name="cancel" id="dfrn-request-cancel-button" value="{{$cancel}}">
	</div>
</form>
