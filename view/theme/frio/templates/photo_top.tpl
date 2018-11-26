<a href="{{$photo.link}}" id="photo-top-photo-link-{{$photo.id}}" title="{{$photo.title|escape}}">
	<img src="{{$photo.src}}" alt="{{if $photo.album.name}}{{$photo.album.name|escape}}{{elseif $photo.desc}}{{$photo.desc|escape}}{{elseif $photo.alt}}{{$photo.alt|escape}}{{else}}{{$photo.unknown|escape}}{{/if}}" title="{{$photo.title|escape}}" id="photo-top-photo-{{$photo.id}}" />
</a>

