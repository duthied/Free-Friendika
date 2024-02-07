
<div class="contact-block-textdiv {{if $class}}{{$class}}{{/if}}">
	<a class="contact-block-link {{if $class}}{{$class }}{{/if}} {{if $sparkle}}sparkle{{/if}} {{if $click}}fakelink{{/if}}" {{if $redir}}target="redir"{{/if}} {{if $url}}href="{{$url}}"{{/if}} {{if $click}}onclick="{{$click}}"{{/if}} title="{{$title}}" alt="{{$name}}" />
		{{$name}}
	</a>
</div>
