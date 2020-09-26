<div id="sidebar-community-accounts" class="widget">
	<h3>{{$title}}</h3>

	<ul class="sidebar-community-accounts-ul">
		<li role="menuitem" class="sidebar-community-accounts-li {{$all_selected}}"><a href="community/{{$content}}">{{$all}}</a></li>
		<li role="menuitem" class="sidebar-community-accounts-li {{$person_selected}}"><a href="community/{{$content}}/person">{{$person}}</a></li>
		<li role="menuitem" class="sidebar-community-accounts-li {{$organisation_selected}}"><a href="community/{{$content}}/organisation">{{$organisation}}</a></li>
		<li role="menuitem" class="sidebar-community-accounts-li {{$news_selected}}"><a href="community/{{$content}}/news">{{$news}}</a></li>
		<li role="menuitem" class="sidebar-community-accounts-li {{$community_selected}}"><a href="community/{{$content}}/community">{{$community}}</a></li>
	</ul>
</div>
