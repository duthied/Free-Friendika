<div id="photo-album-edit-wrapper">
	<form name="photo-album-edit-form" id="photo-album-edit-form" action="photos/{{$nickname}}/album/{{$hexalbum}}" method="post">
		<label id="photo-album-edit-name-label" for="photo-album-edit-name">{{$nametext}}</label>
		<div class="pull-left photo-album-edit-name">
			<input class="form-control" type="text" size="64" name="albumname" value="{{$album}}" id="photo-album-edit-name">
		</div>

		<div class="pull-right">
			<input class="btn-primary btn btn-small" id="photo-album-edit-submit" type="submit" name="submit" value="{{$submit}}" />
		</div>
	</form>
	<div class="clear"></div>
</div>
<div class="clear"></div>
