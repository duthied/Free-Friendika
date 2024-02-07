{{*
	Please don't use this template as is, this is a placeholder that needs to be
	overridden with specific icons to avoid taking too much visual space
*}}
{{if $delivery.queue_count >= -1 && $delivery.queue_count !== '' && $delivery.queue_count !== null}}
<span class="delivery">
	&bull;
	{{if $delivery.queue_count == 0}}
		{{$delivery.notifier_pending}}
	{{elseif $delivery.queue_done == 0}}
		{{$delivery.delivery_pending}} {{$item.delivery.queue_done}}/{{$item.delivery.queue_count}}
	{{elseif $delivery.queue_done / $delivery.queue_count < 0.75}}
		{{$delivery.delivery_underway}} {{$item.delivery.queue_done}}/{{$item.delivery.queue_count}}
	{{elseif $delivery.queue_done < $delivery.queue_count}}
		{{$delivery.delivery_almost}} {{$item.delivery.queue_done}}/{{$item.delivery.queue_count}}
	{{else}}
		{{$delivery.delivery_done}} {{$item.delivery.queue_done}}/{{$item.delivery.queue_count}}
	{{/if}}
</span>
{{/if}}
