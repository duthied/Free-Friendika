
<div class="wall-item-actions" id="wall-item-like-buttons-{{$id}}">
	<button type="button" class="btn-link button-likes" id="like-{{$id}}" title="{{$likethis}}" onclick="dolike({{$id}},'like'); return false;" data-toggle="button"><i class="fa fa-thumbs-up" aria-hidden="true"></i>&nbsp;</button>
	{{if $nolike}}
	<button type="button" class="btn-link button-likes" id="dislike-{{$id}}" title="{{$nolike}}" onclick="dolike({{$id}},'dislike'); return false;" data-toggle="button"><i class="fa fa-thumbs-down" aria-hidden="true"></i>&nbsp;</button>
	{{/if}}
	<img id="like-rotator-{{$id}}" class="like-rotator" src="images/rotator.gif" alt="{{$wait}}" title="{{$wait}}" style="display: none;" />
</div>
