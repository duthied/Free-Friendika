
<div id="adminpage">
	<h2>{{$banner}}</h2>

	<div id="failed_updates_desc">{{$desc nofilter}}</div>

	{{if $failed}}
	{{foreach $failed as $f}}
		<h4>{{$f}}</h4>

		<ul>
			<li><a href="{{$baseurl}}/admin/dbsync/mark/{{$f}}">{{$mark}}</a></li>
			<li><a href="{{$baseurl}}/admin/dbsync/update/{{$f}}">{{$apply}}</a></li>
		</ul>

		<hr />
	{{/foreach}}
	{{/if}}
</div>
