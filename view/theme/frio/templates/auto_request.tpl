<div class="generic-page-wrapper">
	<h1>{{$header}}</h1>

{{if !$myaddr}}
	<p id="dfrn-request-intro">
		{{$page_desc}}
	</p>
	<ul id="dfrn-request-networks">
		<li><a href="http://friendi.ca" title="{{$friendica}}">{{$friendica}}</a></li>
		<li><a href="https://diasporafoundation.org" title="{{$diaspora}}">{{$diaspora}}</a> {{$diasnote}}</li>
		<li><a href="https://gnu.io/social/" title="{{$statusnet}}">{{$statusnet}}</a></li>
	</ul>
	<p>
		{{$invite_desc nofilter}}
	</p>
	<p>
		{{$desc}}
	</p>
{{/if}}

	<form action="{{$request|default:"dfrn_request/$nickname"}}" method="post">

{{if $photo}}
		<img src="{{$photo}}" alt="" id="dfrn-request-photo">
{{/if}}
		<dl>
{{if $url}}
			<dt>{{$url_label}}</dt>
			<dd><a target="blank" href="{{$zrl}}">{{$url}}</a></dd>
{{/if}}
{{if $location}}
			<dt>{{$location_label}}</dt>
			<dd>{{$location}}</dd>
{{/if}}
{{if $keywords}}
			<dt>{{$keywords_label}}</dt>
			<dd>{{$keywords}}</dd>
{{/if}}
{{if $about}}
			<dt>{{$about_label}}</dt>
			<dd>{{$about}}</dd>
{{/if}}
		</dl>

		<div id="dfrn-request-url-wrapper">
			<label id="dfrn-url-label" for="dfrn-url">{{$your_address}}</label>
			{{if $myaddr}}
				{{$myaddr}}
				<input type="hidden" name="dfrn_url" id="dfrn-url" value="{{$myaddr}}">
			{{else}}
				<input type="text" name="dfrn_url" id="dfrn-url" size="32" value="{{$myaddr}}">
			{{/if}}
			{{if $url}}
				<input type="hidden" name="url" id="url" value="{{$url}}">
			{{/if}}
			<div id="dfrn-request-url-end"></div>
		</div>

		<div id="dfrn-request-submit-wrapper">
			{{if $submit}}
				<input class="btn btn-primary" type="submit" name="submit" id="dfrn-request-submit-button" value="{{$submit}}">
			{{/if}}
			<input class="btn btn-default" type="submit" name="cancel" id="dfrn-request-cancel-button" value="{{$cancel}}">
		</div>
	</form>
</div>
