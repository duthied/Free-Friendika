<div id="message-sidebar" class="widget">
	<div id="message-new" class="{{if $new.sel}}selected{{/if}}"><a href="{{$new.url}}" accesskey="m">{{$new.label}}</a> </div>
	
	<ul role="menu" class="message-ul">
		{{foreach $tabs as $t}}
			<li role="menuitem" class="tool {{if $t.sel}}selected{{/if}}"><a href="{{$t.url}}" {{if $t.accesskey}}accesskey="$t.accesskey"{{/if}} class="message-link">{{$t.label}}</a></li>
		{{/foreach}}
	</ul>
	
</div>
