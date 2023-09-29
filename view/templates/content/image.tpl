{{if $image.preview}}
<a data-fancybox="{{$image.attachment->uriId}}" href="{{$image.attachment->url}}"><img src="{{$image.preview}}" alt="{{$image.attachment->description}}" title="{{$image.attachment->description}}" loading="lazy"></a>
{{else}}
<img src="{{$image.src}}" alt="{{$image.attachment->description}}" title="{{$image.attachment->description}}" loading="lazy">
{{/if}}
