<h2>Item Source</h2>
<form action="itemsource" method="get" class="panel panel-default">
	<div class="panel-body">
		<div class="form-group">
			{{include file="field_input.tpl" field=$guid}}
		</div>
		<p><button type="submit" class="btn btn-primary">Submit</button></p>
	</div>
</form>

{{if $source}}
<div class="itemsource-results">
	<div class="panel panel-default">
		<div class="panel-heading">
			<h3 class="panel-title">Item URI</h3>
		</div>
		<div class="panel-body">
			{{$item_uri}}
		</div>
	</div>
	<div class="panel panel-default">
		<div class="panel-heading">
			<h3 class="panel-title">Source</h3>
		</div>
		<pre><code class="language-php">{{$source}}</code></pre>
	</div>
</div>
{{/if}}
