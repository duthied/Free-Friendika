
<div class="notif-item {{if !$item_seen}}unseen{{/if}} {{$item_label}} media">
	<div class="notif-photo-wrapper media-object pull-left">
		<img src="{{$item_image}}" class="notif-image">
	</div>
	<div class="notif-desc-wrapper media-body">
		<a href="{{$item_link}}">
			{{$item_text}}
			<div><time class="notif-when time" data-toggle="tooltip">{{$item_when}}</time></div>
		</a>
	</div>
</div>
