{{* $image.widthRatio is only set in the context of Model\Item->makeImageGrid *}}
<figure class="img-allocated-height" style="width: {{if $image.widthRatio}}{{$image.widthRatio}}%{{else}}auto{{/if}}; padding-bottom: {{$allocated_height}}">
{{if $image.preview}}
	<a data-fancybox="{{$image.uri_id}}" href="{{$image.attachment.url}}">
		<img src="{{$image.preview}}" alt="{{$image.attachment.description}}" title="{{$image.attachment.description}}">
	</a>
{{else}}
	<img src="{{$image.src}}" alt="{{$image.attachment.description}}" title="{{$image.attachment.description}}">
{{/if}}
</figure>
