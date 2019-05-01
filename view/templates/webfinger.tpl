<div class="generic-page-wrapper">
	<h3>Webfinger Diagnostic</h3>

	<form action="webfinger" method="get">
		Lookup address: <input type="text" style="width: 250px;" name="addr" value="{{$addr}}" />
		<input type="submit" name="submit" value="Submit" />
	</form>

	<br /><br />

	{{if $res}}
	<pre>
		{{$res}}
	</pre>
	{{/if}}
</div>
