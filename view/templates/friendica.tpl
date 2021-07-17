<div id="friendica" class="generic-page-wrapper">
	<h1>Friendica</h1>
	<br>
	<p>{{$about nofilter}}</p>
	<br>
	<p>{{$friendica nofilter}}</p>
	<br>
	<p>{{$bugs nofilter}}</p>
	<br>
	<p>{{$info nofilter}}</p>
	<br>

	<p>{{$visible_addons.title nofilter}}</p>
	{{if $visible_addons.list}}
	<div style="margin-left: 25px; margin-right: 25px; margin-bottom: 25px;">{{$visible_addons.list nofilter}}</div>
	{{/if}}

	{{if $tos}}
	<p>{{$tos nofilter}}</p>
	{{/if}}

	{{if $block_list}}
	<div id="about_blocklist">
		<p>{{$block_list.title nofilter}}</p>
		<br>
		<table class="table">
			<thead>
				<tr>
					<th>{{$block_list.header[0] nofilter}}</th>
					<th>{{$block_list.header[1] nofilter}}</th>
				</tr>
			</thead>
			<tbody>
			{{foreach $block_list.list as $blocked}}
				<tr>
					<td>{{$blocked.domain nofilter}}</td>
					<td>{{$blocked.reason nofilter}}</td>
				</tr>
			{{/foreach}}
			</tbody>
		</table>
	</div>

	{{/if}}

{{$hooked nofilter}}
</div>
