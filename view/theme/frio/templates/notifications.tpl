
<div class="generic-page-wrapper">
	{{include file="section_title.tpl" title=$notif_header}}

	{{if $tabs }}{{include file="common_tabs.tpl"}}{{/if}}

	<div class="notif-network-wrapper">
		{{* The "show ignored" link *}}
		{{if $notif_ignored_lnk}}{{$notif_ignored_lnk}}{{/if}}

		{{* The notifications *}}
		{{if $notif_content}}
		<ul class="notif-network-list media-list">
		{{foreach $notif_content as $notification}}
			<li>{{$notification}}</li>
		{{/foreach}}
		</ul>
		{{/if}}

		{{* If no notifications messages available *}}
		{{if $notif_nocontent}}
		<div class="notif_nocontent">{{$notif_nocontent}}</div>
		{{/if}}
	</div>
</div>
