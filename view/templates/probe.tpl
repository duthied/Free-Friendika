<div id="probe" class="generic-page-wrapper">
	<h2>Probe Diagnostic</h2>
	<form action="probe" method="get" class="panel panel-default">
		<div class="panel-body">
			<div class="form-group">
				{{include file="field_input.tpl" field=$addr}}
			</div>
			<p><button type="submit" class="btn btn-primary">Submit</button></p>
		</div>
	</form>

	{{if $res}}
		<div class="probe-result">
			<div class="panel panel-default">
				<div class="panel-heading">
					<h3 class="panel-title">Output</h3>
				</div>
				<div class="panel-body">
					<pre>{{$res}}</pre>
				</div>
			</div>
		</div>
	{{/if}}
</div>
