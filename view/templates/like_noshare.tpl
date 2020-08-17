
<div class="wall-item-like-buttons" id="wall-item-like-buttons-{{$id}}">
	<a href="#" class="icon like" title="{{$likethis}}" onclick="dolike({{$id}},'like'); return false"></a>
	{{if $dislike}}
	<a href="#" class="icon dislike" title="{{$dislike}}" onclick="dolike({{$id}},'dislike'); return false"></a>
	{{/if}}
	<img id="like-rotator-{{$id}}" class="like-rotator" src="images/rotator.gif" alt="{{$wait}}" title="{{$wait}}" style="display: none;" />
</div>
