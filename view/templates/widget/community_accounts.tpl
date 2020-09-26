<span id="sidebar-community-accounts-inflated" class="widget fakelink" onclick="openCloseWidget('sidebar-community-accounts', 'sidebar-community-accounts-inflated');">
	<h3>{{$title}}</h3>
</span>
<div id="sidebar-community-accounts" class="widget">
	<span class="fakelink" onclick="openCloseWidget('sidebar-community-accounts', 'sidebar-community-accounts-inflated');">
		<h3>{{$title}}</h3>
	</span>
	<ul class="sidebar-community-accounts-ul">
		<li role="menuitem" class="sidebar-community-accounts-li{{if !$accounttype}} selected{{/if}}"><a href="community/{{$content}}">{{$all}}</a></li>
		<li role="menuitem" class="sidebar-community-accounts-li{{if $accounttype == 'person'}} selected{{/if}}"><a href="community/{{$content}}/person">{{$person}}</a></li>
		<li role="menuitem" class="sidebar-community-accounts-li{{if $accounttype == 'organisation'}} selected{{/if}}"><a href="community/{{$content}}/organisation">{{$organisation}}</a></li>
		<li role="menuitem" class="sidebar-community-accounts-li{{if $accounttype == 'news'}} selected{{/if}}"><a href="community/{{$content}}/news">{{$news}}</a></li>
		<li role="menuitem" class="sidebar-community-accounts-li{{if $accounttype == 'community'}} selected{{/if}}"><a href="community/{{$content}}/community">{{$community}}</a></li>
	</ul>
</div>
<script>
initWidget('sidebar-community-accounts', 'sidebar-community-accounts-inflated');
</script>
