{{*
	Please don't use this template as is, this is a placeholder that needs to be
	overriden with specific icons to avoid taking too much visual space
*}}
{{if $delivery.queue_count == 0}}
	{{$delivery.notifier_pending|escape}}
{{elseif $delivery.queue_done == 0}}
	{{$delivery.delivery_pending|escape}} {{$item.delivery.queue_done}}/{{$item.delivery.queue_count}}
{{elseif $delivery.queue_done / $delivery.queue_count < 0.75}}
	{{$delivery.delivery_underway|escape}} {{$item.delivery.queue_done}}/{{$item.delivery.queue_count}}
{{else}}
	{{$delivery.delivery_almost|escape}} {{$item.delivery.queue_done}}/{{$item.delivery.queue_count}}
{{/if}}
