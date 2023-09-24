{{* The padding-top height allocation trick only works if the <figure> fills its parent's width completely or with flex. 🤷‍♂️
	As a result, we need to add a wrapping element for non-flex (non-image grid) environments, mostly single-image cases.
 *}}
{{if $allocated_max_width}}
<div style="max-width: {{$allocated_max_width|default:"auto"}};">
{{/if}}

<figure class="img-allocated-height" style="width: {{$allocated_width|default:"auto"}}; padding-bottom: {{$allocated_height}}">
{{if $image.preview}}
	<a data-fancybox="{{$image.uri_id}}" href="{{$image.attachment.url}}">
		<img src="{{$image.preview}}" alt="{{$image.attachment.description}}" title="{{$image.attachment.description}}">
	</a>
{{else}}
	<img src="{{$image.src}}" alt="{{$image.attachment.description}}" title="{{$image.attachment.description}}">
{{/if}}
</figure>

{{if $allocated_max_width}}
</div>
{{/if}}
