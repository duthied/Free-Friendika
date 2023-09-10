<div id="adminpage">
	<h1>{{$title}} - {{$page}}</h1>
	<p>{{$description nofilter}}</p>

	<h3>{{$h_reports}}</h3>
	{{if $reports}}
		<table class="table table-condensed table-striped table-bordered">
			<thead>
				<tr>
					{{foreach $th_reports as $th}}
					<th>
					{{$th}}
					</th>
					{{/foreach}}
				</tr>
			</thead>
			<tbody>
				{{foreach $reports as $report}}
				<tr>
					<td>
						{{$report.created}}
					</td>
					<td><img class="icon" src="{{$report.micro}}" alt="{{$report.nickname}}" title="{{$report.nickname}}"></td>
					<td class="name">
						<a href="contact/{{$report.cid}}" title="{{$report.nickname}}">{{$report.name}}</><br>
						<a href="{{$report.url}}" title="{{$report.nickname}}">{{if $report.addr}}{{$report.addr}}{{else}}{{$report.url}}{{/if}}</a>
					</td>
					<td class="comment">{{if $report.comment}}{{$report.comment}}{{else}}N/A{{/if}}</td>
					<td class="category">{{if $report.category}}{{$report.category}}{{else}}N/A{{/if}}</td>
				</tr>
				{{if $report.posts}}
				<tr>
					<td colspan="5">
					<table class="table table-condensed table-striped table-bordered">
					{{foreach $report.posts as $post}}
						<tr>
						<td>
							<a href="display/{{$post.guid}}">{{$post.created}}</><br>
						</td>
						<td>
							{{$post.body}}
						</td>
						</tr>
					{{/foreach}}
					</table>
					</td>
				</tr>
				{{/if}}
				{{/foreach}}
			</tbody>
		</table>
		{{$paginate nofilter}}
	{{else}}
		<p>{{$no_data}}</p>
	{{/if}}
</div>
