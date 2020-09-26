<div id="sidebar-community-no-sharer" class="widget">
	<h3>{{$title}}</h3>

	<ul class="sidebar-community-no-sharer-ul">
		<li role="menuitem" class="sidebar-community-no-sharer-li{{if !$no_sharer}} selected{{/if}}"><a href="community/{{$path_all}}">{{$all}}</a></li>
		<li role="menuitem" class="sidebar-community-no-sharer-li{{if $no_sharer}} selected{{/if}}"><a href="community/{{$path_no_sharer}}">{{$no_sharer_label}}</a></li>
	</ul>
</div>
