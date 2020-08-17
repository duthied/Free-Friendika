{{if $direction.direction > 0}}
<span class="direction">
	&bull;
	{{if $direction.direction == 1}}
		<i class="icon-inbox" aria-hidden="true" title="{{$direction.title}}"></i>
	{{elseif $direction.direction == 2}}
		<i class="icon-download" aria-hidden="true" title="{{$direction.title}}"></i>
	{{/if}}
</span>
{{/if}}
