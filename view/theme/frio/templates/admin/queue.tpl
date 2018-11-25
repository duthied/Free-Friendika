<div id="adminpage">
	<h1>{{$title}} - {{$page}} ({{$count}})</h1>
	
	<p>{{$info}}</p>
	<table class="table">
		<tr>
			<th>{{$id_header}}</th>
			<th>{{$to_header}}</th>
			<th>{{$url_header}}</th>
			<th>{{$network_header}}</th>
			<th>{{$created_header}}</th>
			<th>{{$last_header}}</th>
		</tr>
		{{foreach $entries as $e}}
		<tr>
			<td>{{$e.id|escape}}</td>
			<td>{{$e.name|escape}}</td>
			<td><a href="{{$e.nurl}}">{{$e.nurl|escape}}</a></td>
			<td>{{$e.network|escape}}</td>
			<td>{{$e.created|escape}}</td>
			<td>{{$e.last|escape}}</td>
		</tr>
		{{/foreach}}
	</table>
</div>
