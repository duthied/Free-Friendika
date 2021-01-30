
<div class="wall-item-like-buttons" id="wall-item-like-buttons-{{$id}}">
	<a href="#" class="icon like" title="{{$like_title}}" onclick="doActivityItem({{$id}}, 'like'{{if $responses.like.self}}, true{{/if}}); return false"></a>
	{{if $dislike}}
	<a href="#" class="icon dislike" title="{{$dislike_title}}" onclick="doActivityItem({{$id}}, 'dislike'{{if $responses.dislike.self}}, true{{/if}}); return false"></a>
	{{/if}}
	<img id="like-rotator-{{$id}}" class="like-rotator" src="images/rotator.gif" alt="{{$wait}}" title="{{$wait}}" style="display: none;" />
</div>
