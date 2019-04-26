<h2>Item Source</h2>
<form action="admin/item/source" method="get" class="panel panel-default">
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
			<h3 class="panel-title">Item Id</h3>
		</div>
		<div class="panel-body">
			{{$item_id}}
		</div>
	</div>
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
			<h3 class="panel-title">Terms</h3>
		</div>
		<div class="panel-body">
			<table class="table table-condensed table-striped">
				<tr>
					<th>Type</th>
					<th>Term</th>
					<th>URL</th>
				</tr>
		{{foreach $terms as $term}}
				<tr>
					<td>
			{{if $term.type == 1}}Tag{{/if}}
			{{if $term.type == 2}}Mention{{/if}}
			{{if $term.type == 8}}Implicit Mention{{/if}}
					</td>
					<td>
						{{$term.term}}
					</td>
					<td>
						{{$term.url}}
					</td>
				</tr>
		{{/foreach}}
			</table>
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
