<div id="sidebar-photos-albums" class="widget">
	<h3>{{$title}}</h3>
	<ul role="menubar" class="sidebar-photos-albums-ul">
		<li role="menuitem" class="sidebar-photos-albums-li">
			<a href="{{$baseurl}}/photos/{{$nick}}" class="sidebar-photos-albums-element" title="{{$title}}" >{{$recent}}</a>
		</li>

		{{if $albums}}
		{{foreach $albums as $al}}
		{{if $al.text}}
		<li role="menuitem" class="sidebar-photos-albums-li">
			<a href="{{$baseurl}}/photos/{{$nick}}/album/{{$al.bin2hex}}" class="sidebar-photos-albums-element">
				<span class="badge pull-right">{{$al.total}}</span>{{$al.text}}
			</a>
		</li>
		{{/if}}
		{{/foreach}}
		{{/if}}
	</ul>

	{{if $can_post}}
	<div class="photos-upload-link" ><a href="{{$upload.1}}">{{$upload.0}}</a></div>
	{{/if}}
</div>
