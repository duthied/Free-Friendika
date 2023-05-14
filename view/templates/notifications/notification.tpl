
<div class="notif-item {{if !$item_seen}}unseen{{/if}}" {{if $item_seen}}aria-hidden="true"{{/if}}>
	<a href="{{$notification.link}}"><img src="{{$notification.image}}" aria-hidden="true" class="notif-image">{{$notification.text}} <span class="notif-when">{{$notification.ago}}</span></a>
</div>
