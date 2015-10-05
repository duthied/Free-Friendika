
<div class="profile-match-wrapper">
	<div class="profile-match-photo" id="contact-entry-photo-{{$id}}"
                onmouseover="if (typeof t{{$id}} != 'undefined') clearTimeout(t{{$id}}); openMenu('contact-photo-menu-button-{{$id}}')" 
                onmouseout="t{{$id}}=setTimeout('closeMenu(\'contact-photo-menu-button-{{$id}}\'); closeMenu(\'contact-photo-menu-{{$id}}\');',200)" >
		<a href="{{$url}}">
			<img width="80" height="80" src="{{$photo}}" alt="{{$name}}" title="{{$name}}[{{$tags}}]" />
		</a>
		{{if $photo_menu}}
                        <span onclick="openClose('contact-photo-menu-{{$id}}');" class="fakelink contact-photo-menu-button" id="contact-photo-menu-button-{{$id}}">menu</span>
                        <div class="contact-photo-menu" id="contact-photo-menu-{{$id}}">
                                <ul>
                                        {{foreach $photo_menu as $k=>$c}}
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
		<a href="{{$url}}" title="{{$name}}[{{$tags}}]">{{$name}}</a>
	</div>
	<div class="profile-match-end"></div>
	{{if $connlnk}}
	<div class="profile-match-connect"><a href="{{$connlnk}}" title="{{$conntxt}}">{{$conntxt}}</a></div>
	{{/if}}

</div>
