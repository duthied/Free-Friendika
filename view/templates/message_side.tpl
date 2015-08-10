
<div id="message-sidebar" class="widget">
	<div id="message-new"><a href="{{$new.url}}" accesskey="m" class="{{if $new.sel}}newmessage-selected{{/if}}">{{$new.label}}</a> </div>
	
	<ul role="menu" class="message-ul">
		{{foreach $tabs as $t}}
			<li role="menuitem" class="tool"><a href="{{$t.url}}" {{if $t.accesskey}}accesskey="$t.accesskey"{{/if}} class="message-link{{if $t.sel}}message-selected{{/if}}">{{$t.label}}</a></li>
		{{/foreach}}
	</ul>
	
</div>
