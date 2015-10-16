{{include file="section_title.tpl"}}

{{foreach $entries as $entry}}
	<div class="profile-match-wrapper">
		<div class="profile-match-photo" id="contact-entry-photo-{{$entry.id}}"
			onmouseover="if (typeof t{{$entry.id}} != 'undefined') clearTimeout(t{{$id}}); openMenu('contact-photo-menu-button-{{$entry.id}}')" 
			onmouseout="t{{$entry.id}}=setTimeout('closeMenu(\'contact-photo-menu-button-{{$entry.id}}\'); closeMenu(\'contact-photo-menu-{{$entry.id}}\');',200)" >
			<a href="{{$entry.url}}">
				<img width="80" height="80" src="{{$entry.photo}}" alt="{{$entry.name}}" title="{{$entry.name}}[{{$entry.tags}}]" />
			</a>
			{{if $entry.photo_menu}}
				<span onclick="openClose('contact-photo-menu-{{$entry.id}}');" class="fakelink contact-photo-menu-button" id="contact-photo-menu-button-{{$entry.id}}">menu</span>
				<div class="contact-photo-menu" id="contact-photo-menu-{{$entry.id}}">
					<ul>
						{{foreach $entry.photo_menu as $k=>$c}}
						{{if $c.2}}
						<li><a class="{{$k}}" target="redir" href="{{$c.1}}">{{$c.0}}</a></li>
						{{else}}
						<li><a class="{{$k}}" href="{{$c.1}}">{{$c.0}}</a></li>
						{{/if}}
						{{/foreach}}
					</ul>
				</div>
				{{/if}}
		</div>
		<div class="profile-match-break"></div>
		<div class="profile-match-name">
			<a href="{{$entry.url}}" title="{{$entry.name}}[{{$entry.tags}}]">{{$entry.name}}</a>
		</div>
		<div class="profile-match-end"></div>
		{{if $entry.connlnk}}
		<div class="profile-match-connect"><a href="{{$entry.connlnk}}" title="{{$entry.conntxt}}">{{$entry.conntxt}}</a></div>
		{{/if}}

	</div>
{{/foreach}}

<div class="clear"></div>
