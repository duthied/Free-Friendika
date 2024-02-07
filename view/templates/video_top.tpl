<div class="video-top-wrapper lframe" id="video-top-wrapper-{{$video.id}}">
	{{* set preloading to none to lessen the load on the server *}}
	<video src="{{$video.src}}" controls {{if $video.preview}}preload="none" poster="{{$video.preview}}" {else}preload="metadata" {{/if}}width="100%" height="auto">
		<a href="{{$video.src}}">{{$video.name}}</a>
	</video>

{{if $delete_url }}
	<form method="post" action="{{$delete_url}}">
		<input type="submit" name="delete" value="X" class="video-delete"></input>
		<input type="hidden" name="id" value="{{$video.id}}"></input>
	</form>
{{/if}}
</div>
