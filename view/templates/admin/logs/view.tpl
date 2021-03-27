<div id="adminpage">
	<h1>{{$title}} - {{$page}}</h1>

	<h3>{{$logname}}</h3>
	{{if $error }}
		<div id="admin-error-message-wrapper" class="alert alert-warning">
			<p>{{$error nofilter}}</p>
		</div>
	{{else}}
		<table>
			<thead>
				<tr>
					<th>Date</th>
					<th>Level</th>
					<th>Context</th>
					<th>Message</th>
				</tr>
			</thead>
			<tbody>
				{{foreach $data as $row}}
				<tr id="ev-{{$row->id}}" onClick="log_show_details('ev-{{$row->id}}')">
					<td>{{$row->date}}</td>
					<td>{{$row->level}}</td>
					<td>{{$row->context}}</td>
					<td>{{$row->message}}</td>
				</tr>
				{{foreach $row->get_data() as $k=>$v}}
					<tr class="hidden" data-id="ev-{{$row->id}}">
						<th>{{$k}}</th>
						<td colspan="3">
							<pre>{{$v nofilter}}</pre>
						</td>
					</tr>
					{{/foreach}}
					<tr class="hidden" data-id="ev-{{$row->id}}"><th colspan="4">Source</th></tr>
					{{foreach $row->get_source() as $k=>$v}}
						<tr class="hidden" data-id="ev-{{$row->id}}">
							<th>{{$k}}</th>
							<td colspan="3">{{$v}}</td>
						</tr>
					{{/foreach}}
				{{/foreach}}
			</tbody>
		</table>
	{{/if}}
</div>
