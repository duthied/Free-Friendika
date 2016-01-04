
<div id="sidebar_photos_widget" class="widget">
	<h3><a href="{{$photo_albums}}" title="{{$photo_albums_page_title}}">{{$title}}</a></h3>

	<div id="widget_photos_wrapper">
	{{foreach $photos as $photo}}
		<div class="widget_photo_container">
			<div class="widget-photo" id="widget-photo-{{$photo.id}}" >
				<a href="{{$photo.src}}" class="widget-photo-link" id="widget-photo-link-{{$photo.id}}" >
					<img class="widget-photo-img" src="{{$photo.photo}}" alt="{{$photo.alt_text}}" title="{{$photo.alt_text}}" />
				</a>
			</div>
		</div>
	{{/foreach}}
	</div>

	<div class="clear"></div>
</div>
