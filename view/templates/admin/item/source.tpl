<div id="source" class="generic-page-wrapper">
	<h2>{{$title}}</h2>
	<form action="admin/item/source" method="get" class="panel panel-default">
		<div class="panel-body">
			<div class="form-group">
				{{include file="field_input.tpl" field=$guid}}
			</div>
			<p><button type="submit" class="btn btn-primary">{{$submit}}</button></p>
		</div>
	</form>

	{{if $source}}
	<div class="itemsource-results">
		<div class="panel panel-default">
			<div class="panel-heading">
				<h3 class="panel-title">{{$itemidlbl}}</h3>
			</div>
			<div class="panel-body">
				{{$item_id}}
			</div>
		</div>
		<div class="panel panel-default">
			<div class="panel-heading">
				<h3 class="panel-title">{{$itemurilbl}}</h3>
			</div>
			<div class="panel-body">
				{{$item_uri}}
			</div>
		</div>
		<div class="panel panel-default">
			<div class="panel-heading">
				<h3 class="panel-title">{{$termslbl}}</h3>
			</div>
			<div class="panel-body">
				<table class="table table-condensed table-striped">
					<tr>
						<th>{{$typelbl}}</th>
						<th>{{$termlbl}}</th>
						<th>{{$urllbl}}</th>
					</tr>
			{{foreach $terms as $term}}
					<tr>
						<td>
				{{if $term.type == 1}}{{$tag}}{{/if}}
				{{if $term.type == 2}}{{$mentionlbl}}{{/if}}
				{{if $term.type == 8}}{{$implicitlbl}}{{/if}}
						</td>
						<td>
							{{$term.name}}
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
				<h3 class="panel-title">{{$sourcelbl}}</h3>
			</div>
			<pre><code class="language-php">{{$source}}</code></pre>
		</div>
	</div>
</div>
{{/if}}
