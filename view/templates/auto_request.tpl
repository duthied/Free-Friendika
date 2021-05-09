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
{{if $url}}
	<dl>
		<dt>{{$url_label}}</dt>
		<dd><a target="blank" href="{{$zrl}}">{{$url}}</a></dd>
	</dl>
{{/if}}
{{if $keywords}}
	<dl>
		<dt>{{$keywords_label}}</dt>
		<dd>{{$keywords}}</dd>
	</dl>
{{/if}}

	<div id="dfrn-request-url-wrapper">
		<label id="dfrn-url-label" for="dfrn-url">{{$your_address}}</label>
{{if $myaddr}}
		{{$myaddr}}
		<input type="hidden" name="dfrn_url" id="dfrn-url" value="{{$myaddr}}">
{{else}}
		<input type="text" name="dfrn_url" id="dfrn-url" size="32" value="{{$myaddr}}">
{{/if}}
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
