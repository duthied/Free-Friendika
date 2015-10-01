<div id="pos_null" style="margin-bottom:-30px;">
</div>

<div id="sortable_boxes">

<div id="close_pages" style="margin-top:30px;" class="widget">
{{if $page}}
<div>{{$page}}</div>
{{/if}}
</div>

<div id="close_profiles" class="widget">
{{if $comunity_profiles_title}}
<h3>{{$comunity_profiles_title}}</h3>
<div id='lastusers-wrapper' class='items-wrapper'>
{{foreach $comunity_profiles_items as $i}}
	{{$i}}
{{/foreach}}
</div>
{{/if}}
</div>

<div id="close_helpers" class="widget">
{{if $helpers}}
<h3>{{$helpers.title.1}}</h3>
<ul role="menu">
<li class="tool" role="menuitem"><a href="http://friendica.com/resources" title="How-tos" style="margin-left: 10px; " target="blank">How-To Guides</a></li>
<li class="tool" role="menuitem"><a href="http://kakste.com/profile/newhere" title="@NewHere" style="margin-left: 10px; " target="blank">NewHere</a></li>
<li class="tool" role="menuitem"><a href="https://helpers.pyxis.uberspace.de/profile/helpers" style="margin-left: 10px; " title="Friendica Support" target="blank">Friendica Support</a></li>
</ul>
{{/if}}
</div>

<div id="close_services" class="widget">
{{if $con_services}}
<h3>{{$con_services.title.1}}</h3>
<div id="right_service_icons" style="margin-left: 16px; margin-top: 5px;">
<a href="{{$url}}/facebook"><img alt="Facebook" src="view/theme/diabook/icons/facebook.png" title="Facebook"></a>
<a href="{{$url}}/settings/connectors"><img alt="StatusNet" src="view/theme/diabook/icons/StatusNet.png?" title="StatusNet"></a>
<a href="{{$url}}/settings/connectors"><img alt="LiveJournal" src="view/theme/diabook/icons/livejournal.png?" title="LiveJournal"></a>
<a href="{{$url}}/settings/connectors"><img alt="Posterous" src="view/theme/diabook/icons/posterous.png?" title="Posterous"></a>
<a href="{{$url}}/settings/connectors"><img alt="Tumblr" src="view/theme/diabook/icons/tumblr.png?" title="Tumblr"></a>
<a href="{{$url}}/settings/connectors"><img alt="Twitter" src="view/theme/diabook/icons/twitter.png?" title="Twitter"></a>
<a href="{{$url}}/settings/connectors"><img alt="WordPress" src="view/theme/diabook/icons/wordpress.png?" title="WordPress"></a>
<a href="{{$url}}/settings/connectors"><img alt="E-Mail" src="view/theme/diabook/icons/email.png?" title="E-Mail"></a>
</div>
{{/if}}
</div>

<div id="close_friends" style="margin-bottom:53px;" class="widget">
{{if $nv}}
<h3>{{$nv.title.1}}</h3>
<ul role="menu">
<li class="tool" role="menuitem"><a class="{{$nv.directory.2}}" href="{{$nv.directory.0}}" style="margin-left: 10px; " title="{{$nv.directory.3}}" >{{$nv.directory.1}}</a></li>
<li class="tool" role="menuitem"><a class="{{$nv.global_directory.2}}" href="{{$nv.global_directory.0}}" target="blank" style="margin-left: 10px; " title="{{$nv.global_directory.3}}" >{{$nv.global_directory.1}}</a></li>
<li class="tool" role="menuitem"><a class="{{$nv.match.2}}" href="{{$nv.match.0}}" style="margin-left: 10px; " title="{{$nv.match.3}}" >{{$nv.match.1}}</a></li>
<li class="tool" role="menuitem"><a class="{{$nv.suggest.2}}" href="{{$nv.suggest.0}}" style="margin-left: 10px; " title="{{$nv.suggest.3}}" >{{$nv.suggest.1}}</a></li>
<li class="tool" role="menuitem"><a class="{{$nv.invite.2}}" href="{{$nv.invite.0}}" style="margin-left: 10px; " title="{{$nv.invite.3}}" >{{$nv.invite.1}}</a></li>
</ul>
{{$nv.search}}
{{/if}}
</div>

<div id="close_lastusers" class="widget">
{{if $lastusers_title}}
<h3>{{$lastusers_title}}</h3>
<div id='lastusers-wrapper' class='items-wrapper'>
{{foreach $lastusers_items as $i}}
	{{$i}}
{{/foreach}}
</div>
{{/if}}
</div>

{{if $activeusers_title}}
<h3>{{$activeusers_title}}</h3>
<div class='items-wrapper'>
{{foreach $activeusers_items as $i}}
	{{$i}}
{{/foreach}}
</div>
{{/if}}

<div id="close_lastphotos">
{{if $photos_title}}
<h3>{{$photos_title}}</h3>
<div id='ra-photos-wrapper' class='items-wrapper'>
{{foreach $photos_items as $i}}
	{{$i}}
{{/foreach}}
</div>
{{/if}}
</div>

<div id="close_lastlikes">
{{if $like_title}}
<h3>{{$like_title}}</h3>
<ul id='likes'>
{{foreach $like_items as $i}}
	<li id='ra-photos-wrapper'>{{$i}}</li>
{{/foreach}}
</ul>
{{/if}}
</div>

</div>
</div>
