<div id="apconversion" class="generic-page-wrapper">
	<h2>{{$title}}</h2>
	<form action="debug/ap" method="post" class="panel panel-default">
		<div class="panel-body">
			<div class="form-group">
				{{include file="field_textarea.tpl" field=$source}}
			</div>
			<p><button type="submit" class="btn btn-primary">{{$submit}}</button></p>
		</div>
	</form>

	{{if $results}}
	<div class="babel-results">
		{{foreach $results as $result}}
		<div class="panel panel-default">
			<div class="panel-heading">
				<h3 class="panel-title">{{$result.title}}</h3>
			</div>
			<div class="panel-body">
				{{$result.content nofilter}}
			</div>
		</div>
		{{/foreach}}
	</div>
	{{/if}}
</div>