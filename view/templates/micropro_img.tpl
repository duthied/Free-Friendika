
<div class="contact-block-div {{if $class}}{{$class}}{{/if}}">
	<a class="contact-block-link {{if $class}}{{$class }}{{/if}} {{if $sparkle}}sparkle{{/if}} {{if $click}}fakelink{{/if}}" {{if $redir}}target="redir"{{/if}} {{if $url}}href="{{$url}}"{{/if}} {{if $click}}onclick="{{$click}}"{{/if}} >
		<img class="contact-block-img {{if $class}}{{$class }}{{/if}} {{if $sparkle}}sparkle{{/if}}" src="{{$photo}}" title="{{$title}}" alt="{{$name}}" />
	</a>
</div>
