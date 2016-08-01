

<h1>{{$notif_header}}</h1>

{{if $tabs }}{{include file="common_tabs.tpl"}}{{/if}}

<div class="notif-network-wrapper">
	{{* The "show ignored" link *}}
	{{if $notif_ignored_lnk}}{{$notif_ignored_lnk}}{{/if}}

	{{* The notifications *}}
	{{if $notif_content}}
	{{foreach $notif_content as $notification}}
		{{$notification}}
	{{/foreach}}
	{{/if}}

	{{* If no notifications messages available *}}
	{{if $notif_nocontent}}
		<div class="notif_nocontent">{{$notif_nocontent}}</div>
	{{/if}}
</div>
