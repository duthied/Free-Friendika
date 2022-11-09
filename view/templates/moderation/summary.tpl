
<div id="adminpage">
	<h1>{{$title}} - {{$page}}</h1>

	<dl>
		<dt>{{$users.0}}</dt>
		<dd>{{$users.1}}</dd>
	</dl>
	{{foreach $accounts as $p}}
		<dl>
			<dt>{{$p.0}}</dt>
			<dd>{{if $p.1}}{{$p.1}}{{else}}0{{/if}}</dd>
		</dl>
	{{/foreach}}

</div>
