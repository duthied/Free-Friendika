<div id="adminpage">
	<h1>{{$title}} - {{$page}}</h1>

	<h3>{{$logname}}</h3>
	{{if $error }}
		<div id="admin-error-message-wrapper" class="alert alert-warning">
			<p>{{$error nofilter}}</p>
		</div>
		{{else}}
		<table class="table table-hover">
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
				<tr id="ev-{{$row->id}}" class="log-event"
					data-data="{{$row->data}}" data-source="{{$row->source}}">
					<td>{{$row->date}}</td>
					<td class="
						{{if $row->level == "CRITICAL"}}bg-danger
						{{elseif $row->level == "ERROR"}}bg-danger
						{{elseif $row->level == "WARNING"}}bg-warinig
						{{elseif $row->level == "NOTICE"}}bg-info
						{{elseif $row->level == "DEBUG"}}text-muted
						{{/if}}
					   ">{{$row->level}}</td>
					<td>{{$row->context}}</td>
					<td style="width:80%">{{$row->message}}</td>
				</tr>
				{{/foreach}}
			</tbody>
		</table>
		{{/if}}
</div>

<div id="logdetail" class="modal fade" tabindex="-1" role="dialog">
	<div class="modal-dialog modal-lg" style="width:90%" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
				<h4 class="modal-title">Event details</h4>
			</div>
			<div class="modal-body">
				<table class="table main-data">
					<thead>
						<tr>
							<th>Date</th>
							<th>Level</th>
							<th>Context</th>
							<th>Message</th>
						</tr>
					</thead>
					<tbody><tr></tr></tbody>
				</table>
				<table class="table source-data">
					<thead>
						<tr>
							<th>File</th>
							<th>Line</th>
							<th>Function</th>
							<th>UID</th>
							<th>Process ID</th>
						</tr>
					</thead>
					<tbody>
						<tr>
							<td data-value="file"></td>
							<td data-value="line"></td>
							<td data-value="function" style="width:70%"></td>
							<td data-value="uid"></td>
							<td data-value="process_id"></td>
						</tr>
					</tbody>
				</table>


				<div class="event-source">
				</div>
				<div class="event-data">
				</div>

			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-primary" data-dismiss="modal">Close</button>
			</div>
		</div><!-- /.modal-content -->
	</div><!-- /.modal-dialog -->
</div><!-- /.modal -->
