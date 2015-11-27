<script>

function showHideForumlist() {
	if( $("li[id^='forum-widget-entry-extended-']").is(':visible')) {
		$("li[id^='forum-widget-entry-extended-']").hide();
		$("li#forum-widget-collapse").html(window.showMore);

	}
	else {
		$("li[id^='forum-widget-entry-extended-']").show();
		$("li#forum-widget-collapse").html(window.showFewer);
	}
}
</script>

<div id="forumlist-sidebar-right" class="widget">
	<h3 id="forumlist">{{$title}}</h3>

	<ul id="forumlist-sidbar-ul" role="menu">
		{{foreach $forums as $forum}}
		{{if $forum.id <= $visible_forums}}
		<li class="forum-widget-entry forum-{{$forum.cid}}" id="forum-widget-entry-{{$forum.id}}" role="menuitem">
			<a href="{{$forum.external_url}}" title="{{$forum.link_desc}}" class="label sparkle" target="_blank">
				<img class="forumlist-img" src="{{$forum.micro}}" alt="{{$forum.link_desc}}" />
			</a>
			<a class="forum-widget-link" id="forum-widget-link-{{$forum.id}}" href="{{$forum.url}}" >{{$forum.name}}</a>
			<span class="notify badge pull-right"></span>
		</li>
		{{/if}}
	
		{{if $forum.id > $visible_forums}}
		<li class="forum-widget-entry forum-{{$forum.cid}}" id="forum-widget-entry-extended-{{$forum.id}}" role="menuitem" style="display: none;">
			<a href="{{$forum.external_url}}" title="{{$forum.link_desc}}" class="label sparkle" target="_blank">
				<img class="forumlist-img" src="{{$forum.micro}}" alt="{{$forum.link_desc}}" />
			</a>
			<a class="forum-widget-link" id="forum-widget-link-{{$forum.id}}" href="{{$forum.url}}" >{{$forum.name}}</a>
			<span class="notify padge pull-right"></span>
		</li>
		{{/if}}
		{{/foreach}}

		{{if $total > $visible_forums }}
		<li onclick="showHideForumlist(); return false;" id="forum-widget-collapse" class="forum-widget-link tool fakelink">{{$showmore}}</li>
		{{/if}}
	</ul>

</div>
