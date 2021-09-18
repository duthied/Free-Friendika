
<div class="notif-item {{if !$item_seen}}unseen{{/if}}">
	<a href="{{$notification.link}}"><img src="{{$notification.image}}" class="notif-image">{{$notification.text nofilter}} <span class="notif-when">{{$notification.ago}}</span></a>
</div>