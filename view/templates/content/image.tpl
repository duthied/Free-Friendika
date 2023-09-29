{{if $image->preview}}
<a data-fancybox="{{$image->uriId}}" href="{{$image->url}}"><img src="{{$image->preview}}" alt="{{$image->description}}" title="{{$image->description}}" loading="lazy"></a>
{{else}}
<img src="{{$image->url}}" alt="{{$image->description}}" title="{{$image->description}}" loading="lazy">
{{/if}}
