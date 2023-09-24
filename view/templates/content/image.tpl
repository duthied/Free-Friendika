{{if $image.preview}}
<a data-fancybox="{{$image.uri_id}}" href="{{$image.attachment.url}}"><img src="{{$image.preview}}" alt="{{$image.attachment.description}}" title="{{$image.attachment.description}}"></a>
{{else}}
<img src="{{$image.src}}" alt="{{$image.attachment.description}}" title="{{$image.attachment.description}}">
{{/if}}
