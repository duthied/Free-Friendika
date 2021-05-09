
<div class="notif-item {{if !$item_seen}}unseen{{/if}}">
	<a href="{{$item_link}}"><img src="{{$item_image}}" class="notif-image">{{$item_text nofilter}} <span class="notif-when">{{$item_ago}}</span></a>
</div>