<div id="message-sidebar" class="widget">
	<div id="message-new" class="{{if $new.sel}}selected{{/if}}"><a href="{{$new.url}}" accesskey="m">{{$new.label}}</a> </div>

 {{if $tabs}}
	<div id="message-preview">
		{{$tabs}}
	</div>
  {{/if}}

</div>
