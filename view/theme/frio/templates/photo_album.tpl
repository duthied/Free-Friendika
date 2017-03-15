
<div class="generic-page-wrapper">

	<h3 id="photo-album-title">{{$album}}</h3>

	<div class="photo-album-actions">
	{{if $can_post}}
		<a class="photos-upload-link" href="{{$upload.1}}">{{$upload.0}}</a>
		{{/if}}
		{{if $can_post && $edit}}<span role="presentation" class="separator">&nbsp;•&nbsp;</span>{{/if}}
		{{if $edit}}
		<a id="album-edit-link" href="{{$edit.1}}" title="{{$edit.0}}">{{$edit.0}}</a>
		{{/if}}
		<a class="photos-order-link" href="{{$order.1}}" title="{{$order.0}}">{{$order.0}}</a>
	</div>
	<div class="clear"></div>

	<div class="photo-album-wrapper" id="photo-album-contents">
	{{foreach $photos as $photo}}
		{{include file="photo_top.tpl"}}
	{{/foreach}}

	<div class="photo-album-end"></div>

	{{$paginate}}
</div>

<script>$(document).ready(function() { loadingPage = false; justifyPhotos('photo-album-contents-{{$album_id}}'); });</script>
